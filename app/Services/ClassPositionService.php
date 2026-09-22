<?php

namespace App\Services;

use App\Models\Schoolclass;
use App\Models\Studentclass;
use App\Models\Broadsheets;
use Illuminate\Support\Facades\DB;

class ClassPositionService
{
    // =========================================================================
    // GRADE HELPERS
    // =========================================================================

    protected function calculateSeniorGrade($score)
    {
        if ($score === null || $score < 0) return 'F9';
        if ($score >= 75) return 'A1';
        if ($score >= 70) return 'B2';
        if ($score >= 65) return 'B3';
        if ($score >= 60) return 'C4';
        if ($score >= 55) return 'C5';
        if ($score >= 50) return 'C6';
        if ($score >= 45) return 'D7';
        if ($score >= 40) return 'E8';
        return 'F9';
    }

    protected function calculateJuniorGrade($score)
    {
        if ($score === null || $score < 40) return 'F';
        if ($score >= 70) return 'A';
        if ($score >= 60) return 'B';
        if ($score >= 50) return 'C';
        if ($score >= 40) return 'D';
        return 'F';
    }

    protected function getRemark($grade)
    {
        return match ($grade) {
            'A1', 'A' => 'Excellent',
            'B2', 'B3', 'B' => 'Very Good',
            'C4', 'C5', 'C6', 'C' => 'Good',
            'D7', 'D' => 'Pass',
            'E8' => 'Pass',
            'F9', 'F' => 'Fail',
            default => 'Unknown',
        };
    }

    // =========================================================================
    // POSITION HELPER
    // =========================================================================

    protected function calculatePositionsRaw($sortedRecords, $field)
    {
        $positionMap     = [];
        $rank            = 0;
        $lastValue       = null;
        $currentPosition = 0;

        foreach ($sortedRecords as $record) {
            $rank++;
            $currentValue = $record->$field;

            if ($lastValue !== null && $currentValue == $lastValue) {
                $positionMap[$record->id] = $currentPosition;
            } else {
                $currentPosition          = $rank;
                $lastValue                = $currentValue;
                $positionMap[$record->id] = $currentPosition;
            }
        }
        return $positionMap;
    }

    // =========================================================================
    // RECALCULATE CLASS POSITIONS AND AVERAGES
    //
    // Computes, per subject, four position types for every student in the
    // class (across ALL arms of that class):
    //   - subject_position_class       : rank by total, whole class (all arms).
    //                                     `cum` is used only as an eligibility
    //                                     gate ("did this student actually get
    //                                     scored for this subject?" — cum != 0
    //                                     means yes), never as the sort key.
    //                                     This restores the behavior of the
    //                                     original (pre-service) controller
    //                                     logic — see FIX note below.
    //   - subject_position_class_total : rank by total, whole class (all arms)
    //   - arm_position                 : rank by total, within the student's own arm
    //   - arm_position_cum             : rank by cum, within the student's own arm
    //
    // FIX (Aug 2026): the previous implementation (formerly inline in
    // ViewStudentReportController) derived a single $armId from the arm
    // passed in via $schoolclassid, ranked only that arm, and explicitly
    // set arm_position/arm_position_cum to null for every student whose
    // arm didn't match. Because this method runs once per arm (e.g. once
    // when Arm A's report is generated, once for Arm B, etc.), every call
    // silently wiped the arm positions of every OTHER arm's students —
    // only the most-recently-recalculated arm ever had correct values.
    // This version groups students by their own arm and ranks each
    // arm-group independently, so every arm keeps correct arm positions
    // regardless of which arm triggered the recalculation.
    //
    // FIX (Sep 2026): subject_position_class had regressed to being ranked
    // by broadsheets.cum's raw value instead of being used only as a
    // filter. Nothing in the current save flow persists the computed
    // cumulative score (the "columnH" value built in
    // ViewStudentReportController::getStudentResultData()) back into
    // broadsheets.cum, so that column is stale/unpopulated for most rows.
    // Ranking on it produced mass ties, which the tie-handling in
    // calculatePositionsRaw() collapses to rank 1 — hence nearly every
    // subject showing "1st" in generated reports. Restored to the original
    // working logic: cum only gates eligibility, total drives the rank.
    // =========================================================================
    public function recalculate($schoolclassid, $sessionid, $termid): bool
    {
        $schoolclass = Schoolclass::with(['classcategories', 'arms'])
            ->where('id', $schoolclassid)
            ->first(['id', 'schoolclass', 'arm']);

        if (!$schoolclass) return false;

        $className = $schoolclass->schoolclass;
        $classIds  = Schoolclass::where('schoolclass', $className)->pluck('id')->toArray();

        if (empty($classIds)) return false;

        $students = Studentclass::whereIn('schoolclassid', $classIds)
            ->where('sessionid', $sessionid)
            ->pluck('studentId')
            ->toArray();

        if (empty($students)) return false;

        // classcategories() is a belongsTo (see App\Models\Schoolclass) — it
        // resolves to a single Classcategory model or null, never a Collection.
        $isSeniorClass = false;
        if ($schoolclass->classcategories) {
            $isSeniorClass = $schoolclass->classcategories->is_senior ?? false;
        }

        return DB::transaction(function () use (
            $sessionid, $termid, $classIds, $students, $isSeniorClass
        ) {
            $broadsheets = Broadsheets::whereIn('broadsheet_records.student_id', $students)
                ->where('broadsheets.term_id', $termid)
                ->where('broadsheet_records.session_id', $sessionid)
                ->whereIn('broadsheet_records.schoolclass_id', $classIds)
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                ->join('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
                ->join('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
                ->select([
                    'broadsheets.id',
                    'broadsheet_records.student_id',
                    'broadsheet_records.subject_id',
                    'subject.subject as subject_name',
                    'studentRegistration.admissionNo as admission_no',
                    'broadsheets.total',
                    'broadsheets.bf',
                    'broadsheets.cum',
                    'broadsheets.cum_ave',
                    'broadsheets.subject_position_class',
                    'broadsheets.subject_position_class_total',
                    'broadsheets.arm_position',
                    'broadsheets.arm_position_cum',
                    'broadsheets.avg',
                    'broadsheets.grade',
                    'broadsheets.remark',
                    'schoolclass.arm as student_arm_id',
                ])
                ->get();

            if ($broadsheets->isEmpty()) return false;

            $subjectGroups = $broadsheets->groupBy('subject_id');

            foreach ($subjectGroups as $subjectId => $subjectRecords) {
                // Legacy behavior: eligibility was gated on cum != 0 ("did
                // this student actually get scored for this subject?"), with
                // the rank always computed from total. That worked in the
                // old data because cum and total were populated together at
                // score-entry time. In the current data, cum is not reliably
                // persisted (see the Sep 2026 fix note above), so gating on
                // it drops almost every student from ranking. total is the
                // field this system actually keeps in sync, so it now also
                // drives eligibility here, not just the rank.
                $validRecordsCum   = $subjectRecords->filter(fn($r) => $r->total !== null);
                $positionMapCum    = $this->calculatePositionsRaw($validRecordsCum->sortByDesc('total')->values(), 'total');

                $validRecordsTotal = $subjectRecords->filter(fn($r) => $r->total !== null);
                $positionMapTotal  = $this->calculatePositionsRaw($validRecordsTotal->sortByDesc('total')->values(), 'total');

                // Rank arm positions per arm-group instead of against one fixed arm.
                $armPositionMapTotal = [];
                $armPositionMapCum   = [];

                foreach ($subjectRecords->groupBy('student_arm_id') as $armRecords) {
                    $validArmTotal = $armRecords->filter(fn($r) => $r->total !== null);
                    $armPositionMapTotal += $this->calculatePositionsRaw($validArmTotal->sortByDesc('total')->values(), 'total');

                    $validArmCum = $armRecords->filter(fn($r) => $r->cum !== null);
                    $armPositionMapCum += $this->calculatePositionsRaw($validArmCum->sortByDesc('cum')->values(), 'cum');
                }

                $totalScores  = $validRecordsTotal->sum('total');
                $studentCount = $validRecordsTotal->count();
                $classAvg     = $studentCount > 0 ? round($totalScores / $studentCount, 1) : 0;

                foreach ($subjectRecords as $record) {
                    if ($isSeniorClass) {
                        $grade = $record->total == 0 ? 'F9' : $this->calculateSeniorGrade($record->total);
                    } else {
                        $grade = $this->calculateJuniorGrade($record->total);
                    }
                    $remark = $this->getRemark($grade);

                    Broadsheets::where('id', $record->id)->update([
                        'avg'                          => $classAvg,
                        'subject_position_class'       => ($record->total === null) ? null : ($positionMapCum[$record->id]   ?? null),
                        'subject_position_class_total' => ($record->total === null) ? null : ($positionMapTotal[$record->id] ?? null),
                        'arm_position'                 => ($record->total === null) ? null : ($armPositionMapTotal[$record->id] ?? null),
                        'arm_position_cum'             => ($record->cum   === null) ? null : ($armPositionMapCum[$record->id]  ?? null),
                        'grade'                        => $grade,
                        'remark'                       => $remark,
                    ]);
                }
            }

            return true;
        });
    }
}