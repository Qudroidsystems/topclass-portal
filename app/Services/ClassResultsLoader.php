<?php

namespace App\Services;

use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\Schoolterm;
use App\Models\Studentclass;
use Illuminate\Support\Facades\Schema;

/**
 * TopClass — loads student result rows for many classes at once, using
 * EXACTLY the same maths as TopClass BroadsheetController
 * (computeTotal / resolveBf / computeCum + assembleStudentRows):
 *
 *   Total = ((CA1 + CA2 + CA3) / 3 + Exam) / 2           (1 dp)
 *   BF    = previous term's TOTAL (same session) → else stored bf → else 0
 *   Cum   = Term 1: Total;  Terms 2–3: (BF + Total) / 2  (2 dp)
 *   Grade = stored broadsheets.grade
 *   Student cum_ave / term_ave = average over every subject row (0s included,
 *   exactly like the broadsheet)
 *
 * Rows have the same shape as the broadsheet's $studentRows, so they go
 * straight into BroadsheetRankingService. A student is matched to a class the
 * same way the single-class broadsheet does it: studentclass row for the
 * session + broadsheet_records.schoolclass_id = that class.
 *
 * Bulk: ~5 queries in total, however many classes are loaded.
 * If you change the maths in BroadsheetController, change it here too.
 */
class ClassResultsLoader
{
    /**
     * @param bool $activeOnly  Skip withdrawn/inactive students
     *                          (studentRegistration.student_status = 'Active').
     */
    public function load(int $termId, int $sessionId, ?array $classIds = null, bool $activeOnly = true): array
    {
        $classes = Schoolclass::with('classcategory')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.*', 'schoolarm.arm as arm_name'])
            ->when($classIds, fn ($q) => $q->whereIn('schoolclass.id', $classIds))
            ->get()
            ->keyBy('id');

        if ($classes->isEmpty()) {
            return ['rows' => [], 'subjects' => [], 'classes' => $classes];
        }

        // ── Enrolment (student ↔ class for the session) ─────────────
        $filterActive = $activeOnly && Schema::hasColumn('studentRegistration', 'student_status');

        $enrolment = Studentclass::whereIn('studentclass.schoolclassid', $classes->keys())
            ->where('studentclass.sessionid', $sessionId)
            ->join('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->when($filterActive, fn ($q) => $q->where('studentRegistration.student_status', 'Active'))
            ->select([
                'studentclass.schoolclassid',
                'studentRegistration.id as id',
                'studentRegistration.admissionNo as admissionno',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.gender',
                'studentpicture.picture',
            ])
            ->get();

        $pairs      = [];   // "sid:cid" => student info
        $studentIds = [];
        foreach ($enrolment as $e) {
            $key = (int) $e->id . ':' . (int) $e->schoolclassid;
            if (!isset($pairs[$key])) {
                $pairs[$key]  = $e;
                $studentIds[] = (int) $e->id;
            }
        }
        $studentIds = array_values(array_unique($studentIds));

        if (empty($studentIds)) {
            return ['rows' => [], 'subjects' => [], 'classes' => $classes];
        }

        $prevTotals = $this->previousTermTotals($studentIds, $sessionId, $termId);

        // ── Current-term broadsheets for every class at once ────────
        $broadsheets = Broadsheets::whereIn('broadsheet_records.student_id', $studentIds)
            ->where('broadsheets.term_id', $termId)
            ->where('broadsheet_records.session_id', $sessionId)
            ->whereIn('broadsheet_records.schoolclass_id', $classes->keys())
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->select([
                'broadsheet_records.student_id',
                'broadsheet_records.subject_id',
                'broadsheet_records.schoolclass_id',
                'subject.subject as subject_name',
                'subject.subject_code',
                'broadsheets.ca1',
                'broadsheets.ca2',
                'broadsheets.ca3',
                'broadsheets.exam',
                'broadsheets.bf',
                'broadsheets.grade',
            ])
            ->get();

        $subjectsMap = [];
        $scores      = [];   // "sid:cid" => [subject_id => subject data]

        foreach ($broadsheets as $row) {
            $sid = (int) $row->student_id;
            $cid = (int) $row->schoolclass_id;
            $sub = (int) $row->subject_id;
            $key = $sid . ':' . $cid;

            // Same scoping as the single-class broadsheet
            if (!isset($pairs[$key])) continue;

            $subjectsMap[$sub] = $subjectsMap[$sub] ?? [
                'subject_id'   => $sub,
                'subject_name' => $row->subject_name,
                'subject_code' => $row->subject_code ?? '',
            ];

            $ca1   = (float) ($row->ca1 ?? 0);
            $ca2   = (float) ($row->ca2 ?? 0);
            $ca3   = (float) ($row->ca3 ?? 0);
            $exam  = (float) ($row->exam ?? 0);
            $total = round((($ca1 + $ca2 + $ca3) / 3 + $exam) / 2, 1);

            $prev = $prevTotals[$sid][$sub] ?? null;
            if ($prev !== null && (float) $prev > 0) {
                $bf = (float) $prev;
            } elseif (!empty($row->bf) && (float) $row->bf > 0) {
                $bf = (float) $row->bf;
            } else {
                $bf = 0.0;
            }

            $cum = $termId == 1 ? $total : round(($bf + $total) / 2, 2);

            $scores[$key][$sub] = [
                'ca1'   => $ca1,
                'ca2'   => $ca2,
                'ca3'   => $ca3,
                'exam'  => $exam,
                'total' => $total,
                'bf'    => $bf,
                'cum'   => $cum,
                'grade' => $row->grade ?? '-',
            ];
        }

        // ── Assemble rows (same aggregates as assembleStudentRows) ──
        $rows = [];
        foreach ($pairs as $key => $stu) {
            $cid       = (int) $stu->schoolclassid;
            $class     = $classes->get($cid);
            $subScores = $scores[$key] ?? [];

            $termTotals = array_map(fn ($sd) => (float) $sd['total'], $subScores);
            $cumValues  = array_map(fn ($sd) => (float) $sd['cum'],   $subScores);
            $n          = count($subScores);

            $className = (string) ($class->schoolclass ?? '');
            $armName   = (string) ($class->arm_name ?? '');

            $rows[] = [
                'id'            => (int) $stu->id,
                'admissionno'   => $stu->admissionno,
                'firstname'     => $stu->firstname,
                'lastname'      => $stu->lastname,
                'gender'        => $stu->gender,
                'picture'       => $stu->picture,
                'arm'           => $armName,
                'class_name'    => $className,
                'class_label'   => trim($className . ' ' . $armName),
                'schoolclassid' => $cid,
                'is_senior'     => (bool) optional(optional($class)->classcategory)->is_senior,
                'subjects'      => $subScores,
                'total_cum'     => round(array_sum($cumValues), 1),
                'total_term'    => round(array_sum($termTotals), 1),
                'cum_ave'       => $n > 0 ? round(array_sum($cumValues) / $n, 1) : 0,
                'term_ave'      => $n > 0 ? round(array_sum($termTotals) / $n, 1) : 0,
                'num_subjects'  => $n,
            ];
        }

        return ['rows' => $rows, 'subjects' => $subjectsMap, 'classes' => $classes];
    }

    /** Same lookup as TopClass BroadsheetController::fetchPreviousTermCums (uses TOTAL, student + session scope). */
    private function previousTermTotals(array $studentIds, int $sessionId, int $termId): array
    {
        $prevTerm = Schoolterm::where('id', '<', $termId)->orderByDesc('id')->first();
        if (!$prevTerm) return [];

        $rows = Broadsheets::whereIn('broadsheet_records.student_id', $studentIds)
            ->where('broadsheets.term_id', $prevTerm->id)
            ->where('broadsheet_records.session_id', $sessionId)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->select(['broadsheet_records.student_id', 'broadsheet_records.subject_id', 'broadsheets.total'])
            ->get();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->student_id][(int) $r->subject_id] = (float) $r->total;
        }
        return $map;
    }
}
