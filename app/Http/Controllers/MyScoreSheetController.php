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
     * Check whether a teacher is allowed to edit a given broadsheet.
     *
     * Layer 1 — individual row lock (broadsheets.is_locked)
     * Layer 2 — subjectclass-level teacher_editing_enabled flag
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

        // Layer 1 — individual row lock
        if (!$isMock) {
            if (!$model->isEditableByTeacher()) {
                return [
                    'allowed' => false,
                    'message' => $model->lock_reason ?? 'This scoresheet has been locked by an administrator',
                ];
            }
        } else {
            if ($model->is_locked) {
                return [
                    'allowed' => false,
                    'message' => $model->lock_reason ?? 'This scoresheet has been locked by an administrator',
                ];
            }
        }

        // Layer 2 — subjectclass teacher editing
        $subjectClass = Subjectclass::find($model->subjectclass_id);
        if ($subjectClass && !$subjectClass->teacher_editing_enabled) {
            return [
                'allowed' => false,
                'message' => 'Teacher editing has been disabled for this subject by an administrator.',
            ];
        }

        // Layer 3 — global lock
        $record = $isMock
            ? BroadsheetRecordMock::find($model->broadsheet_records_mock_id)
            : BroadsheetRecord::find($model->broadsheet_record_id);

        if ($record) {
            $globalLock = ScoresheetLock::where([
                'subjectclass_id' => $model->subjectclass_id,
                'term_id'         => $model->term_id,
                'session_id'      => $record->session_id,
                'is_active'       => true,
            ])->first();

            if ($globalLock) {
                if ($globalLock->scheduled_unlock_at && $globalLock->scheduled_unlock_at->isPast()) {
                    $globalLock->update(['is_active' => false]);
                } else {
                    return [
                        'allowed' => false,
                        'message' => $globalLock->reason ?? 'This scoresheet is globally locked',
                    ];
                }
            }
        }

        return ['allowed' => true];
    }

    // =========================================================================
    // INDEX — landing page for teacher's scoresheets
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
            return response()->json([
                'success' => true,
                'data'    => ['broadsheets' => $this->getBroadsheets($request->user()->id, $termId, $sessionId)],
            ]);
        }

        return view('subjectscoresheet.index', compact('pagetitle', 'broadsheets'));
    }

    // =========================================================================
    // MAIN SCORESHEET VIEW
    // =========================================================================

    public function subjectscoresheet($schoolclassid, $subjectclassid, $staffid, $termid, $sessionid)
    {
        session([
            'schoolclass_id'  => $schoolclassid,
            'subjectclass_id' => $subjectclassid,
            'staff_id'        => $staffid,
            'term_id'         => $termid,
            'session_id'      => $sessionid,
        ]);

        $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);
        $pagetitle   = 'Subject Scoresheet';

        if ($broadsheets->isNotEmpty()) {
            $this->updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid);
            $this->recalculateTerminalTotals($broadsheets, $termid, $sessionid);
            $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $sessionid);
            $this->updateClassPositions($schoolclassid, $termid, $sessionid);

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
        }

        $schoolclass = Schoolclass::with('classcategory')->find($schoolclassid);
        $is_senior   = $schoolclass && $schoolclass->classcategory
            ? $schoolclass->classcategory->is_senior
            : false;

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
    // SINGLE UPDATE — save one row (all 4 CA fields at once)
    // =========================================================================

    public function update(Request $request, $id)
    {
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
            return response()->json(['success' => false, 'message' => 'Broadsheet record missing.'], 404);
        }

        $ca1  = (float) ($request->ca1  ?? 0);
        $ca2  = (float) ($request->ca2  ?? 0);
        $ca3  = (float) ($request->ca3  ?? 0);
        $exam = (float) ($request->exam ?? 0);

        [$total, $bf, $cum] = $this->computeTerminalValues(
            $ca1, $ca2, $ca3, $exam,
            $record->student_id, $record->subject_id,
            $termId, $record->session_id
        );

        $schoolclass = Schoolclass::with('classcategory')->find($record->schoolclass_id);
        $grade  = $schoolclass && $schoolclass->classcategory
            ? $schoolclass->classcategory->calculateGrade($cum)
            : $this->getDefaultGrade($cum);
        $remark = $this->getRemark($grade);

        $broadsheet->update([
            'ca1'  => $ca1,
            'ca2'  => $ca2,
            'ca3'  => $ca3,
            'exam' => $exam,
            'total' => $total,
            'bf'    => $bf,
            'cum'   => $cum,
            'grade' => $grade,
            'remark' => $remark,
            'last_modified_at' => now(),
            'entry_source'     => $broadsheet->entry_source ?: 'teacher',
        ]);

        $this->updateClassMetrics($broadsheet->subjectclass_id, $broadsheet->staff_id, $termId, $record->session_id);
        $this->updateSubjectPositions($broadsheet->subjectclass_id, $broadsheet->staff_id, $termId, $record->session_id);
        $this->updateClassPositions($record->schoolclass_id, $termId, $record->session_id);

        $fresh = Broadsheets::find($id);

        return response()->json([
            'success' => true,
            'message' => 'Score updated successfully!',
            'data' => [
                'id'    => $fresh->id,
                'ca1'   => (float) $fresh->ca1,
                'ca2'   => (float) $fresh->ca2,
                'ca3'   => (float) $fresh->ca3,
                'exam'  => (float) $fresh->exam,
                'total' => (float) $fresh->total,
                'bf'    => (float) $fresh->bf,
                'cum'   => (float) $fresh->cum,
                'grade' => $fresh->grade,
                'remark'=> $fresh->remark,
                'avg'   => (float) ($fresh->avg ?? 0),
                'subject_position_class'       => $fresh->subject_position_class,
                'subject_position_class_total' => $fresh->subject_position_class_total,
                'arm_position'                 => $fresh->arm_position,
                'arm_position_cum'             => $fresh->arm_position_cum,
            ],
        ]);
    }

    // =========================================================================
    // PROJECT 1 FORMULAS (preserved)
    //   total = ((ca1 + ca2 + ca3) / 3 + exam) / 2
    //   cum   = term 1  ?  total  :  (bf + total) / 2
    // =========================================================================

    protected function computeTerminalValues($ca1, $ca2, $ca3, $exam, $studentId, $subjectId, $termId, $sessionId): array
    {
        $caAvg = ($ca1 + $ca2 + $ca3) / 3;
        $total = round(($caAvg + $exam) / 2, 1);
        $bf    = $this->getPreviousTermCum($studentId, $subjectId, $termId, $sessionId);
        $cum   = $termId == 1 ? $total : round(($bf + $total) / 2, 2);
        return [$total, $bf, $cum];
    }

    protected function recalculateTerminalTotals($broadsheets, $termId, $sessionId): void
    {
        foreach ($broadsheets as $broadsheet) {
            $ca1  = (float) ($broadsheet->ca1  ?? 0);
            $ca2  = (float) ($broadsheet->ca2  ?? 0);
            $ca3  = (float) ($broadsheet->ca3  ?? 0);
            $exam = (float) ($broadsheet->exam ?? 0);

            [$total, $bf, $cum] = $this->computeTerminalValues(
                $ca1, $ca2, $ca3, $exam,
                $broadsheet->student_id, $broadsheet->subject_id,
                $termId, $sessionId
            );

            $schoolclass = Schoolclass::with('classcategory')->find($broadsheet->schoolclass_id);
            $grade  = $schoolclass && $schoolclass->classcategory
                ? $schoolclass->classcategory->calculateGrade($cum)
                : $this->getDefaultGrade($cum);
            $remark = $this->getRemark($grade);

            $changed = abs((float) ($broadsheet->total ?? 0) - $total) > 0.001
                    || abs((float) ($broadsheet->bf    ?? 0) - $bf)    > 0.001
                    || abs((float) ($broadsheet->cum   ?? 0) - $cum)   > 0.001
                    || $broadsheet->grade !== $grade;

            if ($changed) {
                $broadsheet->update([
                    'total' => $total, 'bf' => $bf, 'cum' => $cum,
                    'grade' => $grade, 'remark' => $remark,
                ]);
            }
        }
    }

    // =========================================================================
    // BULK UPDATE — with lock check
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
            return response()->json(['success' => false, 'message' => 'Missing parameters'], 400);
        }

        $schoolclass = Schoolclass::with('classcategory')->find($schoolclass_id);
        if (!$schoolclass) {
            return response()->json(['success' => false, 'message' => 'Class not found'], 404);
        }

        $updatedCount = 0;

        DB::transaction(function () use ($scores, $term_id, $session_id, $schoolclass, &$updatedCount) {
            foreach ($scores as $score) {
                $broadsheet = Broadsheets::find($score['id']);
                if (!$broadsheet) continue;

                $ca1  = (float) ($score['ca1']  ?? 0);
                $ca2  = (float) ($score['ca2']  ?? 0);
                $ca3  = (float) ($score['ca3']  ?? 0);
                $exam = (float) ($score['exam'] ?? 0);

                $record = DB::table('broadsheet_records')
                    ->where('id', $broadsheet->broadsheet_record_id)
                    ->first();
                if (!$record) continue;

                [$total, $bf, $cum] = $this->computeTerminalValues(
                    $ca1, $ca2, $ca3, $exam,
                    $record->student_id, $record->subject_id,
                    $term_id, $session_id
                );

                $grade = $schoolclass->classcategory
                    ? $schoolclass->classcategory->calculateGrade($cum)
                    : $this->getDefaultGrade($cum);

                $broadsheet->update([
                    'ca1'  => $ca1,
                    'ca2'  => $ca2,
                    'ca3'  => $ca3,
                    'exam' => $exam,
                    'total' => $total,
                    'bf'    => $bf,
                    'cum'   => $cum,
                    'grade' => $grade,
                    'remark' => $this->getRemark($grade),
                    'last_modified_at' => now(),
                ]);

                $updatedCount++;
            }

            $this->updateClassMetrics($subjectclass_id, $staff_id, $term_id, $session_id);
        });

        $this->updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id);
        $this->updateClassPositions($schoolclass_id, $term_id, $session_id);

        $updated = $this->getBroadsheets($staff_id, $term_id, $session_id, $schoolclass_id, $subjectclass_id);

        return response()->json([
            'success' => true,
            'message' => "{$updatedCount} score(s) updated!",
            'data'    => ['broadsheets' => $updated],
        ]);
    }

    // =========================================================================
    // DESTROY — with lock check
    // =========================================================================

    public function destroy(Request $request)
    {
        $id        = $request->input('id');
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

        $record = DB::table('broadsheet_records')
            ->where('id', $broadsheet->broadsheet_record_id)
            ->first();

        $broadsheet->delete();

        if ($record) {
            $this->updateClassMetrics($subjectclassid, $staffid, $termid, $record->session_id);
            $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $record->session_id);
            $this->updateClassPositions($record->schoolclass_id, $termid, $record->session_id);
        }

        return response()->json(['success' => true, 'message' => 'Score deleted successfully!']);
    }

    // =========================================================================
    // RESULTS (AJAX refresh) — returns all 4 positions
    // =========================================================================

    public function results()
    {
        $subjectclass_id = session('subjectclass_id');
        $schoolclass_id  = session('schoolclass_id');
        $term_id         = session('term_id');
        $session_id      = session('session_id');

        if (!$subjectclass_id || !$schoolclass_id || !$term_id || !$session_id) {
            return response()->json([
                'success' => false,
                'message' => 'Missing session data',
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
            ]);

        return response()->json([
            'success' => true,
            'scores'  => $broadsheets,
        ]);
    }

    // =========================================================================
    // CLASS METRICS (min / max / avg)
    // =========================================================================

    protected function updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid)
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
            ])->first();

        $classMin = $metrics->class_min ?? 0;
        $classMax = $metrics->class_max ?? 0;
        $classAvg = $metrics->student_count > 0
            ? round($metrics->cum_sum / $metrics->student_count, 1)
            : 0;

        Broadsheets::where('subjectclass_id', $subjectclassid)
            ->where('staff_id', $staffid)
            ->where('term_id', $termid)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->update(['cmin' => $classMin, 'cmax' => $classMax, 'avg' => $classAvg]);
    }

    // =========================================================================
    // 4-DIMENSION POSITION CALCULATION
    // =========================================================================

    protected function updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id)
    {
        Log::info('[updateSubjectPositions] START', compact('subjectclass_id', 'term_id', 'session_id'));

        $subjectClass = DB::table('subjectclass')
            ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->where('subjectclass.id', $subjectclass_id)
            ->first(['subjectclass.schoolclassid', 'subjectteacher.subjectid']);
        if (!$subjectClass) return;

        $subjectId     = $subjectClass->subjectid;
        $schoolclassId = $subjectClass->schoolclassid;

        // Find sibling arms
        $baseClass = DB::table('schoolclass')
            ->where('id', $schoolclassId)
            ->first(['schoolclass', 'classcategoryid']);
        if (!$baseClass) return;

        $allArmIds = DB::table('schoolclass')
            ->where('schoolclass', $baseClass->schoolclass)
            ->where('classcategoryid', $baseClass->classcategoryid)
            ->pluck('id');

        $allSubjectClassIds = DB::table('subjectclass')
            ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->whereIn('subjectclass.schoolclassid', $allArmIds)
            ->where('subjectteacher.subjectid', $subjectId)
            ->pluck('subjectclass.id');

        // All registered students across all arms
        $allStudents = DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->whereIn('broadsheets.subjectclass_id', $allSubjectClassIds)
            ->where('broadsheets.term_id', $term_id)
            ->where('broadsheet_records.session_id', $session_id)
            ->whereExists(function ($q) use ($term_id, $session_id) {
                $q->select(DB::raw(1))
                    ->from('subjectRegistrationStatus')
                    ->join('subjectclass as srs_sc', 'srs_sc.id', '=', 'subjectRegistrationStatus.subjectclassid')
                    ->whereColumn('srs_sc.subjectid', 'broadsheet_records.subject_id')
                    ->whereColumn('subjectRegistrationStatus.studentid', 'broadsheet_records.student_id')
                    ->where('subjectRegistrationStatus.termid', $term_id)
                    ->where('subjectRegistrationStatus.sessionid', $session_id)
                    ->where('subjectRegistrationStatus.Status', 1);
            })
            ->get([
                'broadsheets.id',
                'broadsheets.cum',
                'broadsheets.total',
                'broadsheet_records.schoolclass_id',
            ]);

        if ($allStudents->isEmpty()) {
            $this->nullOutStalePositions($allSubjectClassIds, $term_id, $session_id);
            Log::warning('[updateSubjectPositions] No registered students');
            return;
        }

        // 1) Class position by cum
        $this->denseRank($allStudents, 'cum', 'subject_position_class');

        // 2) Class position by total
        $this->denseRank($allStudents, 'total', 'subject_position_class_total');

        // 3 & 4) Arm-specific positions
        foreach ($allStudents->groupBy('schoolclass_id') as $armClassId => $studentsInArm) {
            $this->denseRank($studentsInArm, 'total', 'arm_position');
            $this->denseRank($studentsInArm, 'cum',   'arm_position_cum');
        }

        // Wipe positions for unregistered students
        $this->nullOutStalePositions($allSubjectClassIds, $term_id, $session_id);

        Log::info('[updateSubjectPositions] DONE', ['students' => $allStudents->count()]);
    }

    /**
     * Dense rank helper — ties share the same rank.
     */
    protected function denseRank($rows, string $sortKey, string $column)
    {
        $sorted  = $rows->sortByDesc($sortKey)->values();
        $lastVal = null;
        $rank    = 0;

        foreach ($sorted as $idx => $row) {
            if ($lastVal === null || $row->$sortKey != $lastVal) {
                $rank    = $idx + 1;
                $lastVal = $row->$sortKey;
            }
            DB::table('broadsheets')->where('id', $row->id)->update([$column => $rank]);
        }
    }

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

    protected function updateClassPositions($schoolclassid, $termid, $sessionid)
    {
        $rank = 0; $lastScore = null; $rows = 0;
        $pos = PromotionStatus::where('schoolclassid', $schoolclassid)
            ->where('termid', $termid)
            ->where('sessionid', $sessionid)
            ->orderBy('subjectstotalscores', 'DESC')
            ->get();

        foreach ($pos as $row) {
            $rows++;
            if ($lastScore !== $row->subjectstotalscores) {
                $lastScore = $row->subjectstotalscores;
                $rank      = $rows;
            }
            $suffix = match ($rank) {
                1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th',
            };
            PromotionStatus::where('id', $row->id)->update(['position' => $rank . $suffix]);
        }
    }

    // =========================================================================
    // BF source (previous term's cum)
    // =========================================================================

    protected function getPreviousTermCum($studentId, $subjectId, $termId, $sessionId)
    {
        if ($termId == 1) return 0;

        $prev = DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->where('broadsheet_records.student_id', $studentId)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->where('broadsheet_records.session_id', $sessionId)
            ->where('broadsheets.term_id', $termId - 1)
            ->value('broadsheets.cum');

        return $prev !== null ? round((float) $prev, 2) : 0;
    }

    protected function getDefaultGrade($score)
    {
        if ($score >= 70) return 'A';
        if ($score >= 60) return 'B';
        if ($score >= 50) return 'C';
        if ($score >= 40) return 'D';
        return 'F';
    }

    protected function getRemark($grade)
    {
        return match ($grade) {
            'A', 'A1'             => 'Excellent',
            'B', 'B2', 'B3'       => 'Very Good',
            'C', 'C4', 'C5', 'C6' => 'Good',
            'D', 'D7', 'E8'       => 'Pass',
            default               => 'Fail',
        };
    }

    // =========================================================================
    // QUERY HELPERS — CRITICAL: CA columns must be selected!
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
            ->where('broadsheet_records.session_id', $sessionId)
            ->orderBy('studentRegistration.lastname')
            ->orderBy('studentRegistration.firstname');

        if ($schoolClassId) {
            $query->where('schoolclass.id', $schoolClassId);
        }

        return $query->get([
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
            'studentpicture.picture',

            // ✅ CRITICAL — CA INPUT FIELDS (these were the missing lines!)
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
        ]);
    }

    // =========================================================================
    // MOCK QUERY
    // =========================================================================

    protected function getMockBroadsheets($staffId, $termId, $sessionId, $schoolClassId = null, $subjectClassId = null)
    {
        $query = BroadsheetsMock::query()
            ->where('broadsheetmock.staff_id', $staffId)
            ->where('broadsheetmock.term_id', $termId)
            ->join('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->join('subjectclass', function ($join) use ($subjectClassId) {
                $join->on('subjectclass.id', '=', 'broadsheetmock.subjectclass_id')
                    ->on('broadsheet_records_mock.subject_id', '=', 'subjectclass.subjectid')
                    ->on('broadsheet_records_mock.schoolclass_id', '=', 'subjectclass.schoolclassid');
                if ($subjectClassId) {
                    $join->where('subjectclass.id', $subjectClassId);
                }
            })
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records_mock.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records_mock.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheetmock.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records_mock.session_id')
            ->where('broadsheet_records_mock.session_id', $sessionId)
            ->orderBy('studentRegistration.lastname');

        if ($schoolClassId) {
            $query->where('schoolclass.id', $schoolClassId);
        }

        return $query->get([
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
            'broadsheetmock.avg',
            'broadsheetmock.cmin',
            'broadsheetmock.cmax',
        ]);
    }

    // =========================================================================
    // MOCK SCORESHEET VIEW
    // =========================================================================

    public function mockIndex(Request $request)
    {
        $pagetitle   = 'My Mock Scoresheets';
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
                return response()->json([
                    'success' => false,
                    'message' => 'Please select both term and session.',
                ], 422);
            }
            return response()->json([
                'success' => true,
                'data'    => ['broadsheets' => $this->getMockBroadsheets($request->user()->id, $termId, $sessionId)],
            ]);
        }

        return view('subjectscoresheet.mock_index', compact('pagetitle', 'broadsheets'));
    }

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
        $pagetitle   = 'Mock Subject Scoresheet';

        if ($broadsheets->isNotEmpty()) {
            $this->updateMockClassMetrics($subjectclassid, $staffid, $termid, $sessionid);
            $this->updateMockSubjectPositions($subjectclassid, $staffid, $termid, $sessionid);

            $firstBroadsheet = $broadsheets->first();
            $pagetitle = sprintf(
                'Mock Scoresheet for %s (%s) - %s %s - %s %s',
                $firstBroadsheet->subject,
                $firstBroadsheet->subject_code,
                $firstBroadsheet->schoolclass,
                $firstBroadsheet->arm,
                $firstBroadsheet->term,
                $firstBroadsheet->session
            );
        }

        return view('subjectscoresheet.subjectscoresheet-mock', compact('broadsheets', 'pagetitle'));
    }

    // =========================================================================
    // MOCK UPDATE — with lock check
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
        $termId     = $broadsheet->term_id;
        $record     = BroadsheetRecordMock::find($broadsheet->broadsheet_records_mock_id);

        if (!$record) {
            return response()->json(['success' => false, 'message' => 'Mock record missing.'], 404);
        }

        $exam  = (float) ($request->exam ?? 0);
        $total = $exam;

        $schoolclass = Schoolclass::with('classcategory')->find($record->schoolclass_id);
        $grade = $schoolclass && $schoolclass->classcategory
            ? $schoolclass->classcategory->calculateGrade($total)
            : $this->getDefaultGrade($total);

        $broadsheet->update([
            'exam'  => $exam,
            'total' => $total,
            'grade' => $grade,
            'remark'=> $this->getRemark($grade),
        ]);

        $this->updateMockClassMetrics($broadsheet->subjectclass_id, $broadsheet->staff_id, $termId, $record->session_id);
        $this->updateMockSubjectPositions($broadsheet->subjectclass_id, $broadsheet->staff_id, $termId, $record->session_id);

        return response()->json([
            'success' => true,
            'message' => 'Mock score updated!',
            'data'    => BroadsheetsMock::find($id),
        ]);
    }

    public function mockBulkUpdateScores(Request $request)
    {
        $scores = $request->input('scores', []);

        foreach ($scores as $scoreData) {
            $broadsheet = BroadsheetsMock::find($scoreData['id']);
            if ($broadsheet) {
                $subjectClass = Subjectclass::find($broadsheet->subjectclass_id);
                if ($subjectClass && !$subjectClass->teacher_editing_enabled) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Teacher editing has been disabled for this subject.',
                        'locked'  => true,
                    ], 423);
                }
            }
        }

        $term_id         = $request->input('term_id');
        $session_id      = $request->input('session_id');
        $subjectclass_id = $request->input('subjectclass_id');
        $staff_id        = $request->input('staff_id');
        $schoolclass_id  = $request->input('schoolclass_id');

        if (!$term_id || !$session_id || !$subjectclass_id || !$staff_id || !$schoolclass_id) {
            return response()->json(['success' => false, 'message' => 'Missing parameters'], 400);
        }

        $schoolclass = Schoolclass::with('classcategory')->find($schoolclass_id);
        if (!$schoolclass) {
            return response()->json(['success' => false, 'message' => 'Class not found'], 404);
        }

        DB::transaction(function () use ($scores, $schoolclass) {
            foreach ($scores as $score) {
                $broadsheet = BroadsheetsMock::find($score['id']);
                if (!$broadsheet) continue;

                $exam  = (float) ($score['exam'] ?? 0);
                $total = $exam;

                $grade = $schoolclass->classcategory
                    ? $schoolclass->classcategory->calculateGrade($total)
                    : $this->getDefaultGrade($total);

                $broadsheet->update([
                    'exam'  => $exam,
                    'total' => $total,
                    'grade' => $grade,
                    'remark'=> $this->getRemark($grade),
                ]);
            }
        });

        $this->updateMockClassMetrics($subjectclass_id, $staff_id, $term_id, $session_id);
        $this->updateMockSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id);

        $updated = $this->getMockBroadsheets($staff_id, $term_id, $session_id, $schoolclass_id, $subjectclass_id);

        return response()->json([
            'success' => true,
            'message' => count($scores) . ' score(s) updated!',
            'data'    => ['broadsheets' => $updated],
        ]);
    }

    public function mockDestroy(Request $request)
    {
        $id        = $request->input('id');
        $broadsheet = BroadsheetsMock::findOrFail($id);

        $subjectClass = Subjectclass::find($broadsheet->subjectclass_id);
        if ($subjectClass && !$subjectClass->teacher_editing_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Teacher editing has been disabled for this subject.',
                'locked'  => true,
            ], 423);
        }

        $subjectclassid = $broadsheet->subjectclass_id;
        $staffid        = $broadsheet->staff_id;
        $termid         = $broadsheet->term_id;
        $mockRecord     = BroadsheetRecordMock::find($broadsheet->broadsheet_records_mock_id);

        $broadsheet->delete();

        if ($mockRecord) {
            $this->updateMockClassMetrics($subjectclassid, $staffid, $termid, $mockRecord->session_id);
            $this->updateMockSubjectPositions($subjectclassid, $staffid, $termid, $mockRecord->session_id);
        }

        return response()->json(['success' => true, 'message' => 'Mock score deleted successfully!']);
    }

    public function mockResults()
    {
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
            'scores'  => $broadsheets,
        ]);
    }

    // =========================================================================
    // MOCK CLASS METRICS + POSITIONS
    // =========================================================================

    protected function updateMockClassMetrics($subjectclassid, $staffid, $termid, $sessionid)
    {
        $subjectClass = DB::table('subjectclass')->where('id', $subjectclassid)->first(['subjectteacherid']);
        if (!$subjectClass) return;

        $subjectTeacher = DB::table('subjectteacher')->where('id', $subjectClass->subjectteacherid)->first(['subjectid']);
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
                DB::raw('SUM(broadsheetmock.total) as total_sum'),
                DB::raw('COUNT(broadsheetmock.id) as student_count'),
            ])->first();

        $classMin = $metrics->class_min ?? 0;
        $classMax = $metrics->class_max ?? 0;
        $classAvg = $metrics->student_count > 0
            ? round((float) $metrics->total_sum / $metrics->student_count, 1)
            : 0;

        BroadsheetsMock::where('subjectclass_id', $subjectclassid)
            ->where('staff_id', $staffid)
            ->where('term_id', $termid)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->where('broadsheet_records_mock.subject_id', $subjectId)
            ->update(['cmin' => $classMin, 'cmax' => $classMax, 'avg' => $classAvg]);
    }

    protected function updateMockSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id)
    {
        $rows = BroadsheetsMock::where('broadsheetmock.subjectclass_id', $subjectclass_id)
            ->where('broadsheetmock.staff_id', $staff_id)
            ->where('broadsheetmock.term_id', $term_id)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->where('broadsheet_records_mock.session_id', $session_id)
            ->orderByDesc('broadsheetmock.total')
            ->orderBy('broadsheetmock.id')
            ->get(['broadsheetmock.id', 'broadsheetmock.total']);

        if ($rows->isEmpty()) return;

        $rank      = 0;
        $lastTotal = null;
        foreach ($rows as $idx => $b) {
            if ($lastTotal === null || $b->total != $lastTotal) {
                $rank      = $idx + 1;
                $lastTotal = $b->total;
            }
            BroadsheetsMock::where('id', $b->id)->update(['subject_position_class' => $rank]);
        }
    }

    // =========================================================================
    // PDF DOWNLOADS
    // =========================================================================

    public function downloadMarksSheet(Request $request)
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
    // EXCEL EXPORT
    // =========================================================================

    public function export(Request $request)
    {
        $schoolclassId  = session('schoolclass_id');
        $subjectclassId = session('subjectclass_id');
        $termId         = session('term_id');
        $sessionId      = session('session_id');
        $staffId        = session('staff_id');

        if (!$schoolclassId || !$subjectclassId || !$termId || !$sessionId || !$staffId) {
            return back()->with('error', 'Missing session data. Please open the scoresheet first.');
        }

        $export = new RecordsheetExport($schoolclassId, $subjectclassId, $termId, $sessionId, $staffId);

        $filename = sprintf(
            'scoresheet_%s_%s_%s.xlsx',
            $subjectclassId,
            $termId,
            date('Y-m-d')
        );

        return Excel::download($export, $filename);
    }

    public function mockExport(Request $request)
    {
        $schoolclassId  = session('schoolclass_id');
        $subjectclassId = session('subjectclass_id');
        $termId         = session('term_id');
        $sessionId      = session('session_id');
        $staffId        = session('staff_id');

        if (!$schoolclassId || !$subjectclassId || !$termId || !$sessionId || !$staffId) {
            return back()->with('error', 'Missing session data.');
        }

        $export = new MockRecordsheetExport($schoolclassId, $subjectclassId, $termId, $sessionId, $staffId, true);

        return Excel::download($export, 'mock-scoresheet-' . date('Y-m-d') . '.xlsx');
    }

    // =========================================================================
    // IMPORT
    // =========================================================================

    public function import(Request $request)
    {
        try {
            $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

            $importData = [
                'subjectclass_id' => $request->input('subjectclass_id', session('subjectclass_id')),
                'staff_id'        => $request->input('staff_id',        session('staff_id')),
                'term_id'         => $request->input('term_id',         session('term_id')),
                'session_id'      => $request->input('session_id',      session('session_id')),
                'schoolclass_id'  => $request->input('schoolclass_id',  session('schoolclass_id')),
            ];

            if (empty($importData['subjectclass_id']) || empty($importData['staff_id'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing session data. Please refresh and try again.',
                ], 422);
            }

            $import = new ScoresheetImport($importData);
            Excel::import($import, $request->file('file'));

            // Recalculate everything after import
            $this->updateClassMetrics(
                $importData['subjectclass_id'],
                $importData['staff_id'],
                $importData['term_id'],
                $importData['session_id']
            );
            $this->updateSubjectPositions(
                $importData['subjectclass_id'],
                $importData['staff_id'],
                $importData['term_id'],
                $importData['session_id']
            );
            $this->updateClassPositions(
                $importData['schoolclass_id'],
                $importData['term_id'],
                $importData['session_id']
            );

            $failures     = $import->getFailures();
            $successCount = method_exists($import, 'getSuccessCount') ? $import->getSuccessCount() : 0;

            $updated = $this->getBroadsheets(
                $importData['staff_id'],
                $importData['term_id'],
                $importData['session_id'],
                $importData['schoolclass_id'],
                $importData['subjectclass_id']
            );

            if ($successCount === 0 && !empty($failures)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No records imported.',
                    'errors'  => $failures,
                ], 422);
            }

            $response = [
                'success' => true,
                'message' => "Successfully imported {$successCount} score(s)!",
                'data'    => ['broadsheets' => $updated],
            ];

            if (!empty($failures)) {
                $response['warning']  = true;
                $response['message']  = "Imported {$successCount} record(s) with " . count($failures) . " warning(s).";
                $response['failures'] = $failures;
            }

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Import failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function mockImport(Request $request)
    {
        try {
            $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

            $importData = [
                'subjectclass_id' => $request->input('subjectclass_id', session('subjectclass_id')),
                'staff_id'        => $request->input('staff_id',        session('staff_id')),
                'term_id'         => $request->input('term_id',         session('term_id')),
                'session_id'      => $request->input('session_id',      session('session_id')),
                'schoolclass_id'  => $request->input('schoolclass_id',  session('schoolclass_id')),
            ];

            $import = new ScoresheetImport($importData, true);
            Excel::import($import, $request->file('file'));

            $this->updateMockClassMetrics(
                $importData['subjectclass_id'],
                $importData['staff_id'],
                $importData['term_id'],
                $importData['session_id']
            );
            $this->updateMockSubjectPositions(
                $importData['subjectclass_id'],
                $importData['staff_id'],
                $importData['term_id'],
                $importData['session_id']
            );

            return response()->json([
                'success' => true,
                'message' => 'Mock scores imported successfully!',
            ]);
        } catch (\Exception $e) {
            Log::error('Mock import failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // GRADE PREVIEW (used by the live tooltip)
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
    // RECALCULATE ALL ARM POSITIONS (admin/teacher button)
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
                $repSubjectclass = DB::table('subjectclass')->where('id', $record->representative_id)->first();
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
}