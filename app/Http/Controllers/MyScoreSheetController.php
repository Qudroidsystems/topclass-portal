<?php
// app/Http/Controllers/MyScoreSheetController.php

namespace App\Http\Controllers;

use App\Exports\MarksSheetExport;
use App\Exports\MockMarksSheetExport;
use App\Exports\MockRecordsheetExport;
use App\Exports\RecordsheetExport;
use App\Imports\ScoresheetImport;
use App\Models\BroadsheetRecord;
use App\Models\BroadsheetRecordMock;
use App\Models\Broadsheets;
use App\Models\BroadsheetsMock;
use App\Models\PromotionStatus;
use App\Models\Schoolclass;
use App\Models\SchoolInformation;
use App\Models\ScoresheetLock;
use App\Models\Subjectclass;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class MyScoreSheetController extends Controller
{
    // =========================================================================
    // 3-LAYER LOCK CHECK
    // =========================================================================

    /**
     * Check whether a teacher can edit a given broadsheet.
     *
     * Layer 1 — individual row lock  (via Broadsheets::isEditableByTeacher)
     * Layer 2 — subjectclass teacher_editing_enabled flag
     * Layer 3 — global ScoresheetLock for this subject/term/session
     *
     * Auto-expires any scheduled_unlock_at that has passed.
     */
    protected function checkTeacherCanEdit($broadsheetId, bool $isMock = false): array
    {
        $model = $isMock
            ? BroadsheetsMock::find($broadsheetId)
            : Broadsheets::find($broadsheetId);

        if (!$model) {
            return ['allowed' => false, 'message' => 'Record not found'];
        }

        if (!$isMock) {
            if (!$model->isEditableByTeacher()) {
                return [
                    'allowed' => false,
                    'message' => $model->lock_reason ?? 'This scoresheet has been locked by an administrator',
                ];
            }
        } else {
            $subjectClass = Subjectclass::find($model->subjectclass_id);
            if ($subjectClass && !$subjectClass->teacher_editing_enabled) {
                return [
                    'allowed' => false,
                    'message' => 'Teacher editing has been disabled for this subject by an administrator.',
                ];
            }
        }

        return ['allowed' => true];
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle   = 'My Scoresheets';
        $broadsheets = collect();

        if (!$request->ajax()) {
            $termId    = $request->query('termid', 'ALL');
            $sessionId = $request->query('sessionid', 'ALL');

            if ($termId !== 'ALL' && $sessionId !== 'ALL') {
                $broadsheets = $this->getBroadsheets($request->user()->id, $termId, $sessionId);
            }
        }

        if ($request->ajax()) {
            $termId    = $request->input('termid', 'ALL');
            $sessionId = $request->input('sessionid', 'ALL');

            if ($termId === 'ALL' || $sessionId === 'ALL') {
                return response()->json([
                    'success' => false,
                    'message' => 'Please select both term and session.',
                ], 422);
            }

            $broadsheets = $this->getBroadsheets($request->user()->id, $termId, $sessionId);

            return response()->json([
                'success' => true,
                'data'    => ['broadsheets' => $broadsheets],
            ]);
        }

        return view('subjectscoresheet.index', compact('pagetitle', 'broadsheets'));
    }

    // =========================================================================
    // MAIN SCORESHEET VIEW
    // =========================================================================

    public function subjectscoresheet($schoolclassid, $subjectclassid, $staffid, $termid, $sessionid)
    {
        Log::info('Subjectscoresheet parameters:', compact('schoolclassid', 'subjectclassid', 'staffid', 'termid', 'sessionid'));

        session([
            'schoolclass_id'  => $schoolclassid,
            'subjectclass_id' => $subjectclassid,
            'staff_id'        => $staffid,
            'term_id'         => $termid,
            'session_id'      => $sessionid,
        ]);

        $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

        if ($broadsheets->isNotEmpty()) {
            $this->updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid);
            $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $sessionid);
            $this->updateClassPositions($schoolclassid, $termid, $sessionid);

            // Refresh so the view shows the freshly computed positions
            $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

            $pagetitle = sprintf(
                'Scoresheet for %s (%s) - %s %s - %s %s',
                $broadsheets->first()->subject,
                $broadsheets->first()->subject_code,
                $broadsheets->first()->schoolclass,
                $broadsheets->first()->arm,
                $broadsheets->first()->term,
                $broadsheets->first()->session
            );
        } else {
            $pagetitle = 'Subject Scoresheet';
            Log::warning('No broadsheets found for the given parameters', compact('schoolclassid', 'subjectclassid', 'staffid', 'termid', 'sessionid'));
        }

        // ── Class category (belongsTo — one per class) ────────────────────
        $schoolclass = Schoolclass::with('classcategory')->find($schoolclassid);
        $is_senior   = $schoolclass && $schoolclass->classcategory
            ? $schoolclass->classcategory->is_senior
            : false;

        // ── Lock info for the view ────────────────────────────────────────
        $globalLock = ScoresheetLock::where([
            'subjectclass_id' => $subjectclassid,
            'term_id'         => $termid,
            'session_id'      => $sessionid,
            'is_active'       => true,
        ])->with('lockedBy')->first();

        $subjectClass          = Subjectclass::find($subjectclassid);
        $teacherEditingEnabled = $subjectClass ? (bool) $subjectClass->teacher_editing_enabled : true;

        $lockedCount = $broadsheets->filter(fn($b) => $b->is_locked)->count();

        return view('subjectscoresheet.index', compact(
            'broadsheets', 'pagetitle', 'is_senior',
            'globalLock', 'teacherEditingEnabled', 'lockedCount', 'schoolclass'
        ));
    }

    // =========================================================================
    // QUERY: getBroadsheets
    // =========================================================================

    protected function getBroadsheets($staffId, $termId, $sessionId, $schoolClassId = null, $subjectClassId = null)
    {
        $query = Broadsheets::query()
            ->where('broadsheets.staff_id', $staffId)
            ->where('broadsheets.term_id', $termId)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->join('subjectclass', function ($join) use ($subjectClassId) {
                $join->on('subjectclass.id', '=', 'broadsheets.subjectclass_id')
                    ->on('broadsheet_records.subject_id', '=', 'subjectclass.subjectid')
                    ->on('broadsheet_records.schoolclass_id', '=', 'subjectclass.schoolclassid');
                if ($subjectClassId) {
                    $join->where('subjectclass.id', $subjectClassId);
                }
            })
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->where('broadsheet_records.session_id', $sessionId);

        if ($schoolClassId) {
            $query->where('schoolclass.id', $schoolClassId);
        }

        $results = $query->get([
            'broadsheets.id',
            'studentRegistration.admissionNO as admissionno',
            'broadsheet_records.student_id as student_id',
            'studentRegistration.firstname as fname',
            'studentRegistration.lastname as lname',
            'studentRegistration.othername as mname',
            'subject.subject as subject',
            'subject.subject_code as subject_code',
            'broadsheet_records.subject_id',
            'schoolclass.schoolclass',
            'schoolclass.id as schoolclass_id',
            'schoolarm.arm',
            'schoolterm.term',
            'schoolsession.session',
            'subjectclass.id as subjectclid',
            'broadsheets.staff_id',
            'broadsheets.term_id',
            'broadsheet_records.session_id as sessionid',
            'classcategories.ca1score as ca1score',
            'classcategories.ca2score as ca2score',
            'classcategories.ca3score as ca3score',
            'classcategories.examscore as examscore',
            'studentpicture.picture',

            // ✅ CRITICAL — CA fields in the select list
            'broadsheets.ca1',
            'broadsheets.ca2',
            'broadsheets.ca3',
            'broadsheets.exam',

            'broadsheets.total',
            'broadsheets.bf',
            'broadsheets.cum',
            'broadsheets.grade',
            'broadsheets.subject_position_class as position',
            'broadsheets.subject_position_class_total as position_total',
            'broadsheets.arm_position',
            'broadsheets.arm_position_cum',
            'broadsheets.remark',
            'broadsheets.vettedstatus',
            'broadsheets.avg',
            'broadsheets.cmin',
            'broadsheets.cmax',
            'broadsheets.is_locked',
            'broadsheets.lock_reason',
            'broadsheets.scheduled_unlock_at',
        ])->sortBy('lastname');

        // Recalculate total/bf/cum/grade for each row (project-1 formulas)
        foreach ($results as $broadsheet) {
            $ca1  = $broadsheet->ca1 ?? 0;
            $ca2  = $broadsheet->ca2 ?? 0;
            $ca3  = $broadsheet->ca3 ?? 0;
            $exam = $broadsheet->exam ?? 0;
            $caAverage = ($ca1 + $ca2 + $ca3) / 3;
            $newTotal  = round(($caAverage + $exam) / 2, 1);

            $newBf = $this->getPreviousTermCum(
                $broadsheet->student_id,
                $broadsheet->subject_id,
                $termId,
                $sessionId
            );

            $newCum = $termId == 1 ? $newTotal : round(($newBf + $newTotal) / 2, 2);

            $schoolclass = Schoolclass::with('classcategory')->find($broadsheet->schoolclass_id);
            $newGrade = $schoolclass && $schoolclass->classcategory
                ? $schoolclass->classcategory->calculateGrade($newCum)
                : $this->getDefaultGrade($newCum);
            $newRemark = $this->getRemark($newGrade);

            $significantChange = abs(($broadsheet->bf ?? 0) - $newBf) > 0.01 ||
                                abs(($broadsheet->total ?? 0) - $newTotal) > 0.01 ||
                                abs(($broadsheet->cum ?? 0) - $newCum) > 0.01 ||
                                $broadsheet->grade !== $newGrade ||
                                $broadsheet->remark !== $newRemark;

            if ($significantChange) {
                $broadsheet->bf     = $newBf;
                $broadsheet->total  = $newTotal;
                $broadsheet->cum    = $newCum;
                $broadsheet->grade  = $newGrade;
                $broadsheet->remark = $newRemark;
                $broadsheet->save();
            }
        }

        return $results;
    }

    // =========================================================================
    // RESULTS (AJAX refresh)
    // =========================================================================

    public function results()
    {
        try {
            $subjectclass_id = session('subjectclass_id');
            $schoolclass_id  = session('schoolclass_id');
            $term_id         = session('term_id');
            $session_id      = session('session_id');

            if (!$subjectclass_id || !$schoolclass_id || !$term_id || !$session_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required session data',
                    'scores'  => [],
                ], 400);
            }

            $broadsheets = Broadsheets::where([
                    'broadsheets.subjectclass_id' => $subjectclass_id,
                    'broadsheets.term_id'         => $term_id,
                ])
                ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
                ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                ->where('broadsheet_records.session_id', $session_id)
                ->get([
                    'broadsheets.id',
                    'studentRegistration.admissionNO as admissionno',
                    'studentRegistration.firstname as fname',
                    'studentRegistration.lastname as lname',
                    'broadsheets.ca1',
                    'broadsheets.ca2',
                    'broadsheets.ca3',
                    'broadsheets.exam',
                    'broadsheets.total',
                    'broadsheets.bf',
                    'broadsheets.cum',
                    'broadsheets.grade',
                    'broadsheets.subject_position_class as position',
                    'broadsheets.subject_position_class_total as position_total',
                    'broadsheets.arm_position',
                    'broadsheets.arm_position_cum',
                    'broadsheets.avg',
                    'broadsheets.term_id',
                ]);

            return response()->json([
                'success' => true,
                'scores'  => $broadsheets->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in results endpoint: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // CLASS METRICS
    // =========================================================================

    protected function updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid)
    {
        $subjectClass = DB::table('subjectclass')
            ->where('id', $subjectclassid)
            ->first(['subjectteacherid']);

        if (!$subjectClass) {
            Log::warning('Subjectclass not found', ['subjectclass_id' => $subjectclassid]);
            return;
        }

        $subjectTeacher = DB::table('subjectteacher')
            ->where('id', $subjectClass->subjectteacherid)
            ->first(['subjectid']);

        if (!$subjectTeacher) {
            Log::warning('Subjectteacher not found', ['subjectteacherid' => $subjectClass->subjectteacherid]);
            return;
        }

        $subjectId = $subjectTeacher->subjectid;

        $metrics = Broadsheets::where('broadsheets.subjectclass_id', $subjectclassid)
            ->where('broadsheets.staff_id', $staffid)
            ->where('broadsheets.term_id', $termid)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->select([
                DB::raw('MIN(broadsheets.cum) as class_min'),
                DB::raw('MAX(broadsheets.cum) as class_max'),
                DB::raw('SUM(broadsheets.cum) as cum_sum'),
                DB::raw('COUNT(broadsheets.id) as student_count'),
            ])
            ->first();

        $classMin = $metrics->class_min ?? 0;
        $classMax = $metrics->class_max ?? 0;
        $classAvg = $metrics->student_count > 0 ? round($metrics->cum_sum / $metrics->student_count, 1) : 0;

        Broadsheets::where('subjectclass_id', $subjectclassid)
            ->where('staff_id', $staffid)
            ->where('term_id', $termid)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->update([
                'cmin' => $classMin,
                'cmax' => $classMax,
                'avg'  => $classAvg,
            ]);
    }

    // =========================================================================
    // 4-DIMENSION POSITION CALCULATION (CROSS-ARM)
    // =========================================================================

    protected function updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id)
    {
        Log::info('[updateSubjectPositions] START', compact('subjectclass_id', 'term_id', 'session_id'));

        // Resolve subjectclass → subject_id + base schoolclass_id
        $subjectClass = DB::table('subjectclass')
            ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->where('subjectclass.id', $subjectclass_id)
            ->first(['subjectclass.schoolclassid', 'subjectteacher.subjectid']);

        if (!$subjectClass) {
            Log::warning('[updateSubjectPositions] subjectClass not found', [
                'subjectclass_id' => $subjectclass_id,
            ]);
            return;
        }

        $subjectId     = $subjectClass->subjectid;
        $schoolclassId = $subjectClass->schoolclassid;

        // Find sibling arms (same class name + category)
        $baseClass = DB::table('schoolclass')
            ->where('id', $schoolclassId)
            ->first(['schoolclass', 'classcategoryid']);

        if (!$baseClass) {
            Log::warning('[updateSubjectPositions] baseClass not found', [
                'schoolclass_id' => $schoolclassId,
            ]);
            return;
        }

        $allArmIds = DB::table('schoolclass')
            ->where('schoolclass', $baseClass->schoolclass)
            ->where('classcategoryid', $baseClass->classcategoryid)
            ->pluck('id');

        // All subjectclass ids for this subject across arms
        $allSubjectClassIds = DB::table('subjectclass')
            ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->whereIn('subjectclass.schoolclassid', $allArmIds)
            ->where('subjectteacher.subjectid', $subjectId)
            ->pluck('subjectclass.id');

        // Fetch every student across all arms
        $allStudents = DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->whereIn('broadsheets.subjectclass_id', $allSubjectClassIds)
            ->where('broadsheets.term_id', $term_id)
            ->where('broadsheet_records.session_id', $session_id)
            ->get([
                'broadsheets.id',
                'broadsheets.cum',
                'broadsheets.total',
                'broadsheet_records.schoolclass_id',
            ]);

        Log::info('[updateSubjectPositions] Students fetched', [
            'count' => $allStudents->count(),
            'all_subjectclass_ids' => $allSubjectClassIds->toArray(),
        ]);

        if ($allStudents->isEmpty()) {
            Log::warning('[updateSubjectPositions] No students to rank');
            return;
        }

        // 1) Class-wide position by cum
        $this->denseRank($allStudents, 'cum', 'subject_position_class');

        // 2) Class-wide position by total
        $this->denseRank($allStudents, 'total', 'subject_position_class_total');

        // 3) & 4) Arm-specific positions
        foreach ($allStudents->groupBy('schoolclass_id') as $armClassId => $studentsInArm) {
            $this->denseRank($studentsInArm, 'total', 'arm_position');
            $this->denseRank($studentsInArm, 'cum',   'arm_position_cum');
        }

        // NOTE: nullOutStalePositions removed — it was wiping every student's
        // position because the query matched all rows in the subjectclass.

        Log::info('[updateSubjectPositions] DONE', [
            'students_ranked' => $allStudents->count(),
        ]);
    }

    /**
     * Dense rank helper — ties share the same rank.
     * Uses numeric comparison so DECIMAL-string values sort correctly.
     */
    protected function denseRank($rows, string $sortKey, string $column)
    {
        $sorted = $rows->sortByDesc(function ($row) use ($sortKey) {
            return (float) ($row->$sortKey ?? 0);
        })->values();

        $lastVal = null;
        $rank    = 0;

        foreach ($sorted as $idx => $row) {
            $currentVal = (float) ($row->$sortKey ?? 0);

            if ($lastVal === null || $currentVal !== $lastVal) {
                $rank    = $idx + 1;
                $lastVal = $currentVal;
            }

            DB::table('broadsheets')
                ->where('id', $row->id)
                ->update([$column => $rank]);
        }
    }

    /**
     * Optional — only call from an admin action, NOT from updateSubjectPositions.
     * Nulls positions for students without a matching subjectRegistrationStatus.
     */
    protected function nullOutStalePositions($subjectClassIds, $termId, $sessionId): void
    {
        DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->whereIn('broadsheets.subjectclass_id', $subjectClassIds)
            ->where('broadsheets.term_id', $termId)
            ->where('broadsheet_records.session_id', $sessionId)
            ->whereNotExists(function ($q) use ($termId, $sessionId) {
                $q->select(DB::raw(1))
                    ->from('subjectRegistrationStatus')
                    ->join('subjectclass as srs_sc', 'srs_sc.id', '=', 'subjectRegistrationStatus.subjectclassid')
                    ->whereColumn('srs_sc.subjectid', 'broadsheet_records.subject_id')
                    ->whereColumn('subjectRegistrationStatus.studentid', 'broadsheet_records.student_id')
                    ->where('subjectRegistrationStatus.termid', $termId)
                    ->where('subjectRegistrationStatus.sessionid', $sessionId)
                    ->where('subjectRegistrationStatus.Status', 1);
            })
            ->update([
                'subject_position_class'       => null,
                'subject_position_class_total' => null,
                'arm_position'                 => null,
                'arm_position_cum'             => null,
            ]);
    }

    // =========================================================================
    // CLASS POSITIONS
    // =========================================================================

    protected function updateClassPositions($schoolclassid, $termid, $sessionid)
    {
        $rank = 0;
        $lastScore = null;
        $rows = 0;

        $pos = PromotionStatus::where('schoolclassid', $schoolclassid)
            ->where('termid', $termid)
            ->where('sessionid', $sessionid)
            ->orderBy('subjectstotalscores', 'DESC')
            ->get();

        foreach ($pos as $row) {
            $rows++;
            if ($lastScore !== $row->subjectstotalscores) {
                $lastScore = $row->subjectstotalscores;
                $rank = $rows;
            }
            $position = match ($rank) {
                1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th',
            };
            $rankPos = $rank . $position;

            PromotionStatus::where('id', $row->id)->update(['position' => $rankPos]);
        }
    }

    // =========================================================================
    // EDIT
    // =========================================================================

    public function edit($id)
    {
        $broadsheet = Broadsheets::where('broadsheets.id', $id)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'broadsheets.subjectclass_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->first([
                'broadsheets.id as bid',
                'studentRegistration.admissionNO as admissionno',
                'studentRegistration.title',
                'studentRegistration.firstname as fname',
                'studentRegistration.lastname as lname',
                'studentpicture.picture',
                'broadsheets.ca1',
                'broadsheets.ca2',
                'broadsheets.ca3',
                'broadsheets.exam',
                'broadsheets.total',
                'broadsheets.bf',
                'broadsheets.cum',
                'broadsheets.grade',
                'schoolterm.term',
                'schoolsession.session',
                'subject.subject',
                'subject.subject_code',
                'schoolclass.schoolclass',
                'schoolarm.id',
                'broadsheets.subject_position_class as position',
                'broadsheets.remark',
                'classcategories.ca1id as id1',
                'classcategories.ca2id as id2',
                'classcategories.ca3id as id3',
                'classcategories.examid as id4',
                'broadsheet_records.student_id',
                'broadsheets.staff_id',
                'broadsheets.term_id',
                'broadsheet_records.session_id as sessionid',
            ]);

        if (!$broadsheet) {
            return view('error', [
                'id'      => $id,
                'title'   => 'Not Found',
                'message' => 'Score not found.',
            ]);
        }

        $pagetitle = sprintf(
            'Edit Score for %s %s - %s (%s)',
            $broadsheet->fname,
            $broadsheet->lname,
            $broadsheet->subject,
            $id
        );

        return view('scoresheet.edit', compact('broadsheet', 'pagetitle'));
    }

    // =========================================================================
    // UPDATE (single) — WITH LOCK CHECK
    // =========================================================================

    public function update(Request $request, $id)
    {
        // 3-layer lock check
        $lockCheck = $this->checkTeacherCanEdit($id);
        if (!$lockCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $lockCheck['message'],
                'locked'  => true,
            ], 423);
        }

        $request->validate([
            'ca1'  => 'nullable|numeric|min:0|max:100',
            'ca2'  => 'nullable|numeric|min:0|max:100',
            'ca3'  => 'nullable|numeric|min:0|max:100',
            'exam' => 'nullable|numeric|min:0|max:100',
        ]);

        $broadsheet = Broadsheets::findOrFail($id);
        $termId     = $broadsheet->term_id;
        $record     = DB::table('broadsheet_records')
            ->where('id', $broadsheet->broadsheet_record_id)
            ->first();

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Broadsheet record not found.'], 404);
        }

        $ca1  = (float) ($request->ca1 ?? 0);
        $ca2  = (float) ($request->ca2 ?? 0);
        $ca3  = (float) ($request->ca3 ?? 0);
        $exam = (float) ($request->exam ?? 0);

        $caAverage = ($ca1 + $ca2 + $ca3) / 3;
        $total = round(($caAverage + $exam) / 2, 1);
        $bf = $this->getPreviousTermCum($record->student_id, $record->subject_id, $termId, $record->session_id);
        $cum = $termId == 1 ? $total : round(($bf + $total) / 2, 2);

        $schoolclass = Schoolclass::with('classcategory')->find($record->schoolclass_id);
        $grade = $schoolclass && $schoolclass->classcategory
            ? $schoolclass->classcategory->calculateGrade($cum)
            : $this->getDefaultGrade($cum);
        $remark = $this->getRemark($grade);

        $broadsheet->update([
            'ca1'              => $ca1,
            'ca2'              => $ca2,
            'ca3'              => $ca3,
            'exam'             => $exam,
            'total'            => $total,
            'bf'               => $bf,
            'cum'              => $cum,
            'grade'            => $grade,
            'remark'           => $remark,
            'last_modified_at' => now(),
        ]);

        $this->updateClassMetrics($broadsheet->subjectclass_id, $broadsheet->staff_id, $termId, $record->session_id);
        $this->updateSubjectPositions($broadsheet->subjectclass_id, $broadsheet->staff_id, $termId, $record->session_id);
        $this->updateClassPositions($record->schoolclass_id, $termId, $record->session_id);

        $fresh = Broadsheets::find($id);

        return response()->json([
            'success' => true,
            'message' => 'Score updated successfully!',
            'data'    => [
                'id'     => $fresh->id,
                'ca1'    => (float) $fresh->ca1,
                'ca2'    => (float) $fresh->ca2,
                'ca3'    => (float) $fresh->ca3,
                'exam'   => (float) $fresh->exam,
                'total'  => (float) $fresh->total,
                'bf'     => (float) $fresh->bf,
                'cum'    => (float) $fresh->cum,
                'grade'  => $fresh->grade,
                'remark' => $fresh->remark,
                'avg'    => (float) ($fresh->avg ?? 0),
                'subject_position_class'       => $fresh->subject_position_class,
                'subject_position_class_total' => $fresh->subject_position_class_total,
                'arm_position'                 => $fresh->arm_position,
                'arm_position_cum'             => $fresh->arm_position_cum,
            ],
        ]);
    }

    // =========================================================================
    // BULK UPDATE — WITH LOCK CHECK
    // =========================================================================

    public function bulkUpdateScores(Request $request)
    {
        $scores    = $request->input('scores', []);
        $lockedIds = [];

        foreach ($scores as $s) {
            $check = $this->checkTeacherCanEdit($s['id']);
            if (!$check['allowed']) {
                $lockedIds[] = $s['id'];
            }
        }

        if (!empty($lockedIds)) {
            return response()->json([
                'success'    => false,
                'message'    => count($lockedIds) . ' score(s) are locked and cannot be edited.',
                'locked_ids' => $lockedIds,
            ], 423);
        }

        $term_id         = $request->input('term_id');
        $session_id      = $request->input('session_id');
        $subjectclass_id = $request->input('subjectclass_id');
        $staff_id        = $request->input('staff_id');
        $schoolclass_id  = $request->input('schoolclass_id');

        if (!$term_id || !$session_id || !$subjectclass_id || !$staff_id || !$schoolclass_id) {
            return response()->json(['success' => false, 'message' => 'Missing required parameters'], 400);
        }

        $schoolclass = Schoolclass::with('classcategory')->find($schoolclass_id);
        if (!$schoolclass) {
            return response()->json(['success' => false, 'message' => 'School class not found'], 404);
        }

        $updatedCount = 0;

        DB::transaction(function () use ($scores, $term_id, $session_id, $subjectclass_id, $staff_id, $schoolclass, &$updatedCount) {
            foreach ($scores as $score) {
                $broadsheet = Broadsheets::find($score['id']);
                if (!$broadsheet) continue;

                $ca1  = (float) ($score['ca1']  ?? 0);
                $ca2  = (float) ($score['ca2']  ?? 0);
                $ca3  = (float) ($score['ca3']  ?? 0);
                $exam = (float) ($score['exam'] ?? 0);

                $caAverage = ($ca1 + $ca2 + $ca3) / 3;
                $total = round(($caAverage + $exam) / 2, 1);

                $record = DB::table('broadsheet_records')
                    ->where('id', $broadsheet->broadsheet_record_id)
                    ->first();
                if (!$record) continue;

                $bf = $this->getPreviousTermCum($record->student_id, $record->subject_id, $term_id, $session_id);
                $cum = $term_id == 1 ? $total : round(($bf + $total) / 2, 2);

                $grade = $schoolclass->classcategory
                    ? $schoolclass->classcategory->calculateGrade($cum)
                    : $this->getDefaultGrade($cum);
                $remark = $this->getRemark($grade);

                $broadsheet->update([
                    'ca1'              => $ca1,
                    'ca2'              => $ca2,
                    'ca3'              => $ca3,
                    'exam'             => $exam,
                    'total'            => $total,
                    'bf'               => $bf,
                    'cum'              => $cum,
                    'grade'            => $grade,
                    'remark'           => $remark,
                    'last_modified_at' => now(),
                ]);

                $updatedCount++;
            }

            $this->updateClassMetrics($subjectclass_id, $staff_id, $term_id, $session_id);
        });

        $this->updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id);
        $this->updateClassPositions($schoolclass_id, $term_id, $session_id);

        $updatedBroadsheets = $this->getBroadsheets($staff_id, $term_id, $session_id, $schoolclass_id, $subjectclass_id);

        return response()->json([
            'success' => true,
            'message' => "{$updatedCount} score(s) updated!",
            'data'    => ['broadsheets' => $updatedBroadsheets->values()->toArray()],
        ], 200);
    }

    // =========================================================================
    // DESTROY — WITH LOCK CHECK
    // =========================================================================

    public function destroy(Request $request)
    {
        $id = $request->input('id');

        $lockCheck = $this->checkTeacherCanEdit($id);
        if (!$lockCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $lockCheck['message'],
                'locked'  => true,
            ], 423);
        }

        $broadsheet     = Broadsheets::findOrFail($id);
        $subjectclassid = $broadsheet->subjectclass_id;
        $staffid        = $broadsheet->staff_id;
        $termid         = $broadsheet->term_id;

        $broadsheetRecord = DB::table('broadsheet_records')
            ->where('id', $broadsheet->broadsheet_record_id)
            ->first();

        $broadsheet->delete();

        if ($broadsheetRecord) {
            $this->updateClassMetrics($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
            $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
            $this->updateClassPositions($broadsheetRecord->schoolclass_id, $termid, $broadsheetRecord->session_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Score deleted successfully!',
        ]);
    }

    // =========================================================================
    // GRADE HELPERS
    // =========================================================================

    protected function getDefaultGrade($score)
    {
        if ($score >= 70 && $score <= 100) return 'A';
        if ($score >= 60) return 'B';
        if ($score >= 50) return 'C';
        if ($score >= 40) return 'D';
        return 'F';
    }

    protected function getRemark($grade)
    {
        $remarks = [
            'A'  => 'Excellent',  'B'  => 'Very Good', 'C'  => 'Good',
            'D'  => 'Pass',       'F'  => 'Fail',
            'A1' => 'Excellent',  'B2' => 'Very Good', 'B3' => 'Good',
            'C4' => 'Credit',     'C5' => 'Credit',    'C6' => 'Credit',
            'D7' => 'Pass',       'E8' => 'Pass',      'F9' => 'Fail',
        ];
        return $remarks[$grade] ?? 'Unknown';
    }

    /**
     * Resolve the value carried forward into a new term's "bf" (brought
     * forward) field, from the previous term's broadsheet row for the same
     * student/subject/session.
     *
     * FIX (Sep 2026): previously read broadsheets.cum only. That column is
     * not reliably populated for every row (see ClassPositionService's Sep
     * 2026 fix note — same underlying issue), so a subject whose previous
     * term's cum was 0/null silently carried forward 0 as bf, overwriting
     * whatever real value was there before (this function runs on every
     * scoresheet view via getBroadsheets(), not just at term creation).
     * total, by contrast, is the field this system reliably keeps in sync
     * (it's the direct output of the CA/exam averaging in update(),
     * bulkUpdateScores(), and getBroadsheets()'s recalculation loop). 0 is
     * already this app's convention for "not really set" (see the cum != 0
     * eligibility checks in ClassPositionService), so a previous-term cum
     * of exactly 0 is treated as unset and total is used instead.
     */
    protected function getPreviousTermCum($studentId, $subjectId, $termId, $sessionId)
    {
        if ($termId == 1) return 0;

        $previousTerm = Broadsheets::where('broadsheet_records.student_id', $studentId)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->where('broadsheets.term_id', $termId - 1)
            ->where('broadsheet_records.session_id', $sessionId)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->select(['broadsheets.cum', 'broadsheets.total'])
            ->first();

        if (!$previousTerm) {
            return 0;
        }

        $value = ($previousTerm->cum !== null && (float) $previousTerm->cum != 0)
            ? $previousTerm->cum
            : $previousTerm->total;

        return $value !== null ? round((float) $value, 2) : 0;
    }

    // =========================================================================
    // IMPORT
    // =========================================================================

    public function import(Request $request)
    {
        $request->validate([
            'file'            => 'required|file|mimes:xlsx,xls',
            'schoolclass_id'  => 'required|integer|exists:schoolclass,id',
            'subjectclass_id' => 'required|integer|exists:subjectclass,id',
            'staff_id'        => 'required|integer|exists:users,id',
            'term_id'         => 'required|integer|in:1,2,3',
            'session_id'      => 'required|integer|exists:schoolsession,id',
        ]);

        try {
            $importData = [
                'schoolclass_id'  => $request->schoolclass_id,
                'subjectclass_id' => $request->subjectclass_id,
                'staff_id'        => $request->staff_id,
                'term_id'         => $request->term_id,
                'session_id'      => $request->session_id,
            ];

            $import = new ScoresheetImport($importData);
            Excel::import($import, $request->file('file'));

            $this->updateClassMetrics($request->subjectclass_id, $request->staff_id, $request->term_id, $request->session_id);
            $this->updateSubjectPositions($request->subjectclass_id, $request->staff_id, $request->term_id, $request->session_id);
            $this->updateClassPositions($request->schoolclass_id, $request->term_id, $request->session_id);

            return response()->json([
                'success'     => true,
                'message'     => 'Scores imported successfully!',
                'broadsheets' => $import->getUpdatedBroadsheets(),
                'failures'    => $import->getFailures(),
            ]);
        } catch (\Exception $e) {
            Log::error('Import: Error', ['message' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function importProgress(Request $request)
    {
        $progressKey = 'import_progress_' . $request->user()->id;
        $progress = session($progressKey, ['progress' => 0, 'total' => 0, 'status' => 'idle']);

        return response()->json([
            'progress' => $progress['progress'],
            'total'    => $progress['total'],
            'status'   => $progress['status'],
            'error'    => $progress['error'] ?? null,
        ]);
    }

    // =========================================================================
    // EXPORT (Excel)
    // =========================================================================

    public function export()
    {
        $schoolclassId  = session('schoolclass_id');
        $subjectclassId = session('subjectclass_id');
        $termId         = session('term_id');
        $sessionId      = session('session_id');
        $staffId        = session('staff_id');

        if (!$schoolclassId || !$subjectclassId || !$termId || !$sessionId || !$staffId) {
            return redirect()->back()->with('error', 'Missing required data for export.');
        }

        $export = new RecordsheetExport($schoolclassId, $subjectclassId, $termId, $sessionId, $staffId);

        $filename = sprintf(
            'Scores_Sheet_%s_%s_%s_%s.xlsx',
            $subjectclassId, $termId, $sessionId, date('Y-m-d')
        );

        return Excel::download($export, $filename);
    }

    // =========================================================================
    // PDF DOWNLOADS (DomPDF)
    // =========================================================================

    public function downloadMarkSheet(Request $request)
    {
        try {
            $subjectclassid = $request->input('subjectclass_id', session('subjectclass_id'));
            $staffid        = $request->input('staff_id',        session('staff_id'));
            $termid         = $request->input('term_id',         session('term_id'));
            $sessionid      = $request->input('session_id',      session('session_id'));
            $schoolclassid  = $request->input('schoolclass_id',  session('schoolclass_id'));

            if (!$subjectclassid || !$staffid || !$termid || !$sessionid || !$schoolclassid) {
                return response()->json(['success' => false, 'message' => 'Missing session data.'], 400);
            }

            $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);
            if ($broadsheets->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No students found.'], 404);
            }

            $teacher = \App\Models\User::find($staffid);
            $teacherName = $teacher ? ($teacher->name ?? trim($teacher->firstname . ' ' . $teacher->lastname)) : '';
            $school = SchoolInformation::first();

            $pdf = Pdf::loadView('subjectscoresheet.marksheet', [
                'broadsheets' => $broadsheets,
                'classInfo'   => $broadsheets->first(),
                'school'      => $school,
                'teacherName' => $teacherName,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('marks-sheet-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            Log::error('Marks sheet error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function downloadScoresPdf(Request $request)
    {
        try {
            $subjectclassid = $request->input('subjectclass_id', session('subjectclass_id'));
            $staffid        = $request->input('staff_id',        session('staff_id'));
            $termid         = $request->input('term_id',         session('term_id'));
            $sessionid      = $request->input('session_id',      session('session_id'));
            $schoolclassid  = $request->input('schoolclass_id',  session('schoolclass_id'));

            if (!$subjectclassid || !$staffid || !$termid || !$sessionid || !$schoolclassid) {
                return response()->json(['success' => false, 'message' => 'Missing session data.'], 400);
            }

            $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);
            if ($broadsheets->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No students found.'], 404);
            }

            $teacher = \App\Models\User::find($staffid);
            $teacherName = $teacher ? ($teacher->name ?? trim($teacher->firstname . ' ' . $teacher->lastname)) : '';
            $school = SchoolInformation::first();

            $pdf = Pdf::loadView('subjectscoresheet.scores-pdf', [
                'broadsheets' => $broadsheets,
                'classInfo'   => $broadsheets->first(),
                'school'      => $school,
                'teacherName' => $teacherName,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('scores-sheet-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            Log::error('Scores PDF error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function mockDownloadMarkSheet(Request $request)
    {
        try {
            $subjectclassid = $request->input('subjectclass_id', session('subjectclass_id'));
            $staffid        = $request->input('staff_id',        session('staff_id'));
            $termid         = $request->input('term_id',         session('term_id'));
            $sessionid      = $request->input('session_id',      session('session_id'));
            $schoolclassid  = $request->input('schoolclass_id',  session('schoolclass_id'));

            if (!$subjectclassid || !$staffid || !$termid || !$sessionid || !$schoolclassid) {
                return response()->json(['success' => false, 'message' => 'Missing session data.'], 400);
            }

            $broadsheets = $this->getMockBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);
            if ($broadsheets->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No mock scores found.'], 404);
            }

            $teacher = \App\Models\User::find($staffid);
            $teacherName = $teacher ? ($teacher->name ?? trim($teacher->firstname . ' ' . $teacher->lastname)) : '';
            $school = SchoolInformation::first();

            $pdf = Pdf::loadView('subjectscoresheet.mock-marksheet', [
                'broadsheets' => $broadsheets,
                'classInfo'   => $broadsheets->first(),
                'school'      => $school,
                'teacherName' => $teacherName,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('mock-marks-sheet-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            Log::error('Mock marks sheet error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // RECALCULATE ALL ARM POSITIONS
    // =========================================================================

    public function updateAllArmPositions(Request $request)
    {
        try {
            $request->validate([
                'schoolclass_id' => 'required|exists:schoolclass,id',
                'term_id'        => 'required|exists:schoolterm,id',
                'session_id'     => 'required|exists:schoolsession,id',
            ]);

            $schoolclassId = $request->schoolclass_id;
            $termId        = $request->term_id;
            $sessionId     = $request->session_id;

            $baseClass = DB::table('schoolclass')
                ->where('id', $schoolclassId)
                ->first(['schoolclass', 'classcategoryid']);

            if (!$baseClass) {
                return response()->json(['success' => false, 'message' => 'Base class not found']);
            }

            $allArms = DB::table('schoolclass')
                ->where('schoolclass', $baseClass->schoolclass)
                ->where('classcategoryid', $baseClass->classcategoryid)
                ->get();

            if ($allArms->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No arms found for this class']);
            }

            $subjectclassRecords = DB::table('subjectclass')
                ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->whereIn('subjectclass.schoolclassid', $allArms->pluck('id'))
                ->select('subjectteacher.subjectid', DB::raw('MIN(subjectclass.id) as representative_id'))
                ->groupBy('subjectteacher.subjectid')
                ->get();

            $subjectsProcessed = 0;
            foreach ($subjectclassRecords as $record) {
                $repSubjectclass = DB::table('subjectclass')
                    ->where('id', $record->representative_id)
                    ->first();
                if (!$repSubjectclass) continue;

                $this->updateSubjectPositions(
                    $record->representative_id,
                    $repSubjectclass->staffid ?? 0,
                    $termId,
                    $sessionId
                );
                $subjectsProcessed++;
            }

            return response()->json([
                'success' => true,
                'message' => "Positions updated! Processed {$subjectsProcessed} subject(s) across " . $allArms->count() . " arms.",
                'data'    => [
                    'arms_count'         => $allArms->count(),
                    'subjects_processed' => $subjectsProcessed,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('updateAllArmPositions error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update positions: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // GRADE PREVIEW (AJAX — live tooltip)
    // =========================================================================

    public function calculateGradePreview(Request $request)
    {
        $request->validate([
            'schoolclass_id' => 'required|exists:schoolclass,id',
            'cum'            => 'required|numeric|min:0|max:100',
        ]);

        $schoolclass = Schoolclass::with('classcategory')->findOrFail($request->schoolclass_id);
        $grade = $schoolclass->classcategory
            ? $schoolclass->classcategory->calculateGrade($request->cum)
            : $this->getDefaultGrade($request->cum);

        return response()->json(['grade' => $grade]);
    }

    // =========================================================================
    // MOCK — INDEX
    // =========================================================================

    public function mockIndex(Request $request)
    {
        $pagetitle = 'My Mock Scoresheets';
        $broadsheets = collect();

        if (!$request->ajax()) {
            $termId    = $request->query('termid', 'ALL');
            $sessionId = $request->query('sessionid', 'ALL');

            if ($termId !== 'ALL' && $sessionId !== 'ALL') {
                $broadsheets = $this->getMockBroadsheets($request->user()->id, $termId, $sessionId);
            }
        }

        if ($request->ajax()) {
            $termId    = $request->input('termid', 'ALL');
            $sessionId = $request->input('sessionid', 'ALL');

            if ($termId === 'ALL' || $sessionId === 'ALL') {
                return response()->json(['success' => false, 'message' => 'Please select both term and session.'], 422);
            }

            $broadsheets = $this->getMockBroadsheets($request->user()->id, $termId, $sessionId);

            return response()->json([
                'success' => true,
                'data'    => ['broadsheets' => $broadsheets],
            ]);
        }

        return view('subjectscoresheet.mock_index', compact('pagetitle', 'broadsheets'));
    }

    // =========================================================================
    // MOCK — SUBJECT SCORESHEET VIEW
    // =========================================================================

    public function mockSubjectscoresheet($schoolclassid, $subjectclassid, $staffid, $termid, $sessionid)
    {
        session([
            'schoolclass_id'  => $schoolclassid,
            'subjectclass_id' => $subjectclassid,
            'staff_id'        => $staffid,
            'term_id'         => $termid,
            'session_id'      => $sessionid,
        ]);

        $broadsheets = $this->getMockBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);
        $pagetitle = 'Mock Subject Scoresheet';

        if ($broadsheets->isNotEmpty()) {
            $this->updateMockClassMetrics($subjectclassid, $staffid, $termid, $sessionid);
            $this->updateMockSubjectPositions($subjectclassid, $staffid, $termid, $sessionid);

            $first = $broadsheets->first();
            $pagetitle = sprintf(
                'Mock Scoresheet for %s (%s) - %s %s - %s %s',
                $first->subject, $first->subject_code, $first->schoolclass,
                $first->arm, $first->term, $first->session
            );
        }

        return view('subjectscoresheet.subjectscoresheet-mock', compact('broadsheets', 'pagetitle'));
    }

    // =========================================================================
    // MOCK — QUERY HELPER
    // =========================================================================

    protected function getMockBroadsheets($staffId, $termId, $sessionId, $schoolClassId = null, $subjectClassId = null)
    {
        $query = BroadsheetsMock::query()
            ->where('broadsheetmock.staff_id', $staffId)
            ->where('broadsheetmock.term_id', $termId)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records_mock.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records_mock.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'broadsheetmock.subjectclass_id')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheetmock.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records_mock.session_id')
            ->where('broadsheet_records_mock.session_id', $sessionId);

        if ($schoolClassId) $query->where('schoolclass.id', $schoolClassId);
        if ($subjectClassId) $query->where('subjectclass.id', $subjectClassId);

        $results = $query->get([
            'broadsheetmock.id',
            'studentRegistration.admissionNO as admissionno',
            'broadsheet_records_mock.student_id as student_id',
            'studentRegistration.firstname as fname',
            'studentRegistration.lastname as lname',
            'studentRegistration.othername as mname',
            'subject.subject as subject',
            'subject.subject_code as subject_code',
            'broadsheet_records_mock.subject_id',
            'schoolclass.schoolclass',
            'schoolclass.id as schoolclass_id',
            'schoolarm.arm',
            'schoolterm.term',
            'schoolsession.session',
            'subjectclass.id as subjectclid',
            'broadsheetmock.staff_id',
            'broadsheetmock.term_id',
            'broadsheet_records_mock.session_id as sessionid',
            'studentpicture.picture',
            'broadsheetmock.exam',
            'broadsheetmock.total',
            'broadsheetmock.grade',
            'broadsheetmock.subject_position_class as position',
            'broadsheetmock.remark',
        ])->sortBy('lname');

        foreach ($results as $broadsheet) {
            $exam = $broadsheet->exam ?? 0;
            $newTotal = $exam;

            $schoolclass = Schoolclass::with('classcategory')->find($broadsheet->schoolclass_id);
            $newGrade = $schoolclass && $schoolclass->classcategory
                ? $schoolclass->classcategory->calculateGrade($newTotal)
                : $this->getDefaultGrade($newTotal);
            $newRemark = $this->getRemark($newGrade);

            if (abs(($broadsheet->total ?? 0) - $newTotal) > 0.01 ||
                $broadsheet->grade !== $newGrade ||
                $broadsheet->remark !== $newRemark) {

                $broadsheet->total  = $newTotal;
                $broadsheet->grade  = $newGrade;
                $broadsheet->remark = $newRemark;
                $broadsheet->save();
            }
        }

        return $results;
    }

    // =========================================================================
    // MOCK — UPDATE
    // =========================================================================

    public function mockUpdate(Request $request, $id)
    {
        $lockCheck = $this->checkTeacherCanEdit($id, true);
        if (!$lockCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $lockCheck['message'],
                'locked'  => true,
            ], 423);
        }

        $request->validate(['exam' => 'nullable|numeric|min:0|max:100']);

        $broadsheet = BroadsheetsMock::findOrFail($id);
        $broadsheetRecord = BroadsheetRecordMock::where('id', $broadsheet->broadsheet_records_mock_id)->first();

        if (!$broadsheetRecord) {
            return response()->json(['success' => false, 'message' => 'Mock broadsheet record not found.'], 404);
        }

        $exam = (float) ($request->exam ?? 0);
        $total = $exam;

        $schoolclass = Schoolclass::with('classcategory')->find($broadsheetRecord->schoolclass_id);
        $grade = $schoolclass && $schoolclass->classcategory
            ? $schoolclass->classcategory->calculateGrade($total)
            : $this->getDefaultGrade($total);
        $remark = $this->getRemark($grade);

        $broadsheet->update([
            'exam'   => $exam,
            'total'  => $total,
            'grade'  => $grade,
            'remark' => $remark,
        ]);

        $this->updateMockClassMetrics($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $broadsheetRecord->session_id);
        $this->updateMockSubjectPositions($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $broadsheetRecord->session_id);

        return response()->json([
            'success' => true,
            'message' => 'Mock score updated!',
            'data'    => BroadsheetsMock::find($id),
        ]);
    }

    // =========================================================================
    // MOCK — DESTROY
    // =========================================================================

    public function mockDestroy(Request $request)
    {
        $id = $request->input('id');

        $lockCheck = $this->checkTeacherCanEdit($id, true);
        if (!$lockCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $lockCheck['message'],
                'locked'  => true,
            ], 423);
        }

        $broadsheet = BroadsheetsMock::findOrFail($id);
        $subjectclassid = $broadsheet->subjectclass_id;
        $staffid = $broadsheet->staff_id;
        $termid = $broadsheet->term_id;

        $mockRecord = BroadsheetRecordMock::where('id', $broadsheet->broadsheet_records_mock_id)->first();

        $broadsheet->delete();

        if ($mockRecord) {
            $this->updateMockClassMetrics($subjectclassid, $staffid, $termid, $mockRecord->session_id);
            $this->updateMockSubjectPositions($subjectclassid, $staffid, $termid, $mockRecord->session_id);
        }

        return response()->json(['success' => true, 'message' => 'Mock score deleted successfully!']);
    }

    // =========================================================================
    // MOCK — BULK UPDATE
    // =========================================================================

    public function mockBulkUpdateScores(Request $request)
    {
        $scores = $request->input('scores', []);
        $term_id = $request->input('term_id');
        $session_id = $request->input('session_id');
        $subjectclass_id = $request->input('subjectclass_id');
        $staff_id = $request->input('staff_id');
        $schoolclass_id = $request->input('schoolclass_id');

        if (!$term_id || !$session_id || !$subjectclass_id || !$staff_id || !$schoolclass_id) {
            return response()->json(['success' => false, 'message' => 'Missing required parameters'], 400);
        }

        foreach ($scores as $scoreData) {
            $broadsheet = BroadsheetsMock::find($scoreData['id']);
            if ($broadsheet) {
                $subjectClass = Subjectclass::find($broadsheet->subjectclass_id);
                if ($subjectClass && !$subjectClass->teacher_editing_enabled) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Teacher editing disabled for this subject.',
                        'locked'  => true,
                    ], 423);
                }
            }
        }

        $schoolclass = Schoolclass::with('classcategory')->find($schoolclass_id);

        DB::transaction(function () use ($scores, $term_id, $session_id, $subjectclass_id, $staff_id, $schoolclass) {
            foreach ($scores as $score) {
                $broadsheet = BroadsheetsMock::find($score['id']);
                if (!$broadsheet) continue;

                $exam = floatval($score['exam'] ?? 0);
                $total = $exam;

                $grade = $schoolclass && $schoolclass->classcategory
                    ? $schoolclass->classcategory->calculateGrade($total)
                    : $this->getDefaultGrade($total);
                $remark = $this->getRemark($grade);

                $broadsheet->update([
                    'exam'   => $exam,
                    'total'  => $total,
                    'grade'  => $grade,
                    'remark' => $remark,
                ]);
            }

            $this->updateMockClassMetrics($subjectclass_id, $staff_id, $term_id, $session_id);
            $this->updateMockSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id);
        });

        return response()->json([
            'success' => true,
            'data'    => ['broadsheets' => $this->getMockBroadsheets($staff_id, $term_id, $session_id, $schoolclass_id, $subjectclass_id)->values()->toArray()],
        ]);
    }

    // =========================================================================
    // MOCK — IMPORT
    // =========================================================================

    public function mockImport(Request $request)
    {
        $request->validate([
            'file'            => 'required|file|mimes:xlsx,xls',
            'schoolclass_id'  => 'required|integer',
            'subjectclass_id' => 'required|integer',
            'staff_id'        => 'required|integer',
            'term_id'         => 'required|integer',
            'session_id'      => 'required|integer',
        ]);

        try {
            $import = new ScoresheetImport([
                'schoolclass_id'  => $request->schoolclass_id,
                'subjectclass_id' => $request->subjectclass_id,
                'staff_id'        => $request->staff_id,
                'term_id'         => $request->term_id,
                'session_id'      => $request->session_id,
            ], true);

            Excel::import($import, $request->file('file'));

            $this->updateMockClassMetrics($request->subjectclass_id, $request->staff_id, $request->term_id, $request->session_id);
            $this->updateMockSubjectPositions($request->subjectclass_id, $request->staff_id, $request->term_id, $request->session_id);

            return response()->json([
                'success'     => true,
                'message'     => 'Mock scores imported successfully!',
                'broadsheets' => $import->getUpdatedBroadsheets(),
                'errors'      => $import->getFailures(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to import mock scores: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // MOCK — RESULTS
    // =========================================================================

    public function mockResults()
    {
        try {
            $subjectclass_id = session('subjectclass_id');
            $schoolclass_id  = session('schoolclass_id');
            $term_id         = session('term_id');
            $session_id      = session('session_id');
            $staff_id        = session('staff_id');

            if (!$subjectclass_id || !$schoolclass_id || !$term_id || !$session_id) {
                return response()->json(['success' => false, 'message' => 'Missing session data.', 'scores' => []], 400);
            }

            $broadsheets = $this->getMockBroadsheets($staff_id, $term_id, $session_id, $schoolclass_id, $subjectclass_id);

            return response()->json([
                'success' => true,
                'scores'  => $broadsheets->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // MOCK — EXPORT
    // =========================================================================

    public function mockExport()
    {
        $schoolclassId  = session('schoolclass_id');
        $subjectclassId = session('subjectclass_id');
        $termId         = session('term_id');
        $sessionId      = session('session_id');
        $staffId        = session('staff_id');

        if (!$schoolclassId || !$subjectclassId || !$termId || !$sessionId || !$staffId) {
            return redirect()->back()->with('error', 'Missing required data for mock export.');
        }

        $export = new MockRecordsheetExport($schoolclassId, $subjectclassId, $termId, $sessionId, $staffId, true);

        return Excel::download($export, 'mock-scoresheet-' . date('Y-m-d') . '.xlsx');
    }

    // =========================================================================
    // MOCK — CLASS METRICS
    // =========================================================================

    protected function updateMockClassMetrics($subjectclassid, $staffid, $termid, $sessionid)
    {
        $subjectClass = DB::table('subjectclass')
            ->where('id', $subjectclassid)
            ->first(['subjectteacherid']);
        if (!$subjectClass) return;

        $subjectTeacher = DB::table('subjectteacher')
            ->where('id', $subjectClass->subjectteacherid)
            ->first(['subjectid']);
        if (!$subjectTeacher) return;

        $subjectId = $subjectTeacher->subjectid;

        $metrics = BroadsheetsMock::where('broadsheetmock.subjectclass_id', $subjectclassid)
            ->where('broadsheetmock.staff_id', $staffid)
            ->where('broadsheetmock.term_id', $termid)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->where('broadsheet_records_mock.subject_id', $subjectId)
            ->select([
                DB::raw('MIN(broadsheetmock.total) as class_min'),
                DB::raw('MAX(broadsheetmock.total) as class_max'),
                DB::raw('AVG(broadsheetmock.total) as class_avg'),
                DB::raw('COUNT(broadsheetmock.id) as student_count'),
            ])
            ->first();

        $classMin = $metrics->class_min ?? 0;
        $classMax = $metrics->class_max ?? 0;
        $classAvg = $metrics->student_count > 0 ? round($metrics->class_avg, 1) : 0;

        BroadsheetsMock::where('subjectclass_id', $subjectclassid)
            ->where('staff_id', $staffid)
            ->where('term_id', $termid)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->where('broadsheet_records_mock.subject_id', $subjectId)
            ->update(['cmin' => $classMin, 'cmax' => $classMax, 'avg' => $classAvg]);
    }

    // =========================================================================
    // MOCK — SUBJECT POSITIONS
    // =========================================================================

    protected function updateMockSubjectPositions($subjectclassid, $staffid, $termid, $sessionid)
    {
        $rank = 0;
        $lastScore = null;
        $rows = 0;

        $classPos = BroadsheetsMock::where('broadsheetmock.subjectclass_id', $subjectclassid)
            ->where('broadsheetmock.staff_id', $staffid)
            ->where('broadsheetmock.term_id', $termid)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->orderBy('broadsheetmock.total', 'DESC')
            ->get(['broadsheetmock.id', 'broadsheetmock.total', 'broadsheetmock.broadsheet_records_mock_id']);

        foreach ($classPos as $row) {
            $rows++;
            if ($lastScore !== $row->total) {
                $lastScore = $row->total;
                $rank = $rows;
            }
            $position = match ($rank) {
                1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th',
            };
            $rankPos = $rank . $position;

            BroadsheetsMock::where('id', $row->id)->update(['subject_position_class' => $rankPos]);
        }
    }
}