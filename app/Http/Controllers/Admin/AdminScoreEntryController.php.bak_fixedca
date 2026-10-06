<?php
// app/Http/Controllers/Admin/AdminScoreEntryController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BroadsheetRecord;
use App\Models\BroadsheetRecordMock;
use App\Models\Broadsheets;
use App\Models\BroadsheetsMock;
use App\Models\PromotionStatus;
use App\Models\Schoolclass;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\ScoresheetLock;
use App\Models\Subjectclass;
use App\Models\SubjectTeacher;
use App\Models\User;
use App\Exports\AdminRecordsheetExport;
use App\Imports\AdminScoresheetImport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class AdminScoreEntryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View admin-score-entry|Create admin-score-entry|Update admin-score-entry|Delete admin-score-entry')->only(['index']);
        $this->middleware('permission:Create admin-score-entry')->only(['create', 'store']);
        $this->middleware('permission:Update admin-score-entry')->only(['edit', 'update', 'bulkUpdate', 'singleUpdate', 'lockManagement', 'getScoresheetsList', 'bulkLockManagement']);
        $this->middleware('permission:Delete admin-score-entry')->only(['destroy']);
    }

    // =========================================================================
    // 3-LAYER LOCK CHECK (mirrors MyScoreSheetController)
    // =========================================================================

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
        $pagetitle = "Admin Score Entry - Teacher Subjects";
        $terms     = Schoolterm::orderBy('id')->get();
        $sessions  = Schoolsession::orderBy('id', 'desc')->get();

        $teacherSubjects   = collect();
        $selectedTermId    = $request->get('termid');
        $selectedSessionId = $request->get('sessionid');

        $dashboardStats = [
            'total_teachers' => 0,
            'total_subjects' => 0,
            'total_classes' => 0,
            'completed_scoresheets' => 0,
            'pending_scoresheets' => 0,
            'completion_rate' => 0,
            'mock_completed' => 0,
            'mock_pending' => 0,
            'teacher_stats' => [],
            'class_stats' => [],
            'total_expected_entries' => 0,
            'total_actual_entries' => 0,
            'entry_completion_rate' => 0,
        ];

        if ($selectedTermId && $selectedSessionId) {
            $teacherSubjects = $this->getTeacherSubjects($selectedTermId, $selectedSessionId);
            $dashboardStats  = $this->calculateDashboardStats($teacherSubjects, $selectedTermId, $selectedSessionId);
        }

        return view('admin.score-entry.index', compact(
            'pagetitle', 'terms', 'sessions', 'teacherSubjects',
            'selectedTermId', 'selectedSessionId', 'dashboardStats'
        ));
    }

    /**
     * Calculate dashboard stats — CA-aware (checks if ca1/ca2/ca3/exam are entered).
     */
    protected function calculateDashboardStats($teacherSubjects, $termId, $sessionId)
    {
        $stats = [
            'total_teachers' => $teacherSubjects->groupBy('teacher_id')->count(),
            'total_subjects' => $teacherSubjects->count(),
            'total_classes' => $teacherSubjects->groupBy('schoolclass_id')->count(),
            'completed_scoresheets' => 0,
            'pending_scoresheets' => 0,
            'completion_rate' => 0,
            'mock_completed' => 0,
            'mock_pending' => 0,
            'teacher_stats' => [],
            'class_stats' => [],
            'total_expected_entries' => 0,
            'total_actual_entries' => 0,
            'entry_completion_rate' => 0,
        ];

        $teacherGroups = $teacherSubjects->groupBy('teacher_id');

        foreach ($teacherGroups as $teacherId => $subjects) {
            $teacherName = $subjects->first()->teacher_name;
            $teacherStats = [
                'teacher_id' => $teacherId,
                'teacher_name' => $teacherName,
                'subjects_count' => $subjects->count(),
                'completed_terminal' => 0,
                'pending_terminal' => 0,
                'completed_mock' => 0,
                'pending_mock' => 0,
                'completion_rate' => 0,
                'expected_entries' => 0,
                'actual_entries' => 0,
                'subjects_details' => [],
                'classes' => [],
            ];

            foreach ($subjects as $subject) {
                $studentCount = $subject->student_count;
                $expectedEntries = $studentCount;
                $actualTerminalEntries = $subject->terminal_entries_count;
                $actualMockEntries = $subject->mock_entries_count;

                if ($subject->has_terminal_scores) $teacherStats['completed_terminal']++;
                else $teacherStats['pending_terminal']++;

                if ($subject->has_mock_scores) $teacherStats['completed_mock']++;
                else $teacherStats['pending_mock']++;

                $teacherStats['expected_entries'] += $expectedEntries;
                $teacherStats['actual_entries'] += $actualTerminalEntries;

                $teacherStats['subjects_details'][] = [
                    'subject_name' => $subject->subject_name,
                    'class_name' => $subject->class_name,
                    'student_count' => $studentCount,
                    'has_terminal' => $subject->has_terminal_scores,
                    'has_mock' => $subject->has_mock_scores,
                    'terminal_entries' => $actualTerminalEntries,
                    'terminal_partial' => $subject->terminal_partial_count,
                    'mock_entries' => $actualMockEntries,
                    'expected_entries' => $expectedEntries,
                    'completion_percentage' => $subject->entry_percentage,
                    'entry_status' => $subject->entry_status,
                ];

                $teacherStats['classes'][] = $subject->class_name;
                $stats['total_expected_entries'] += $expectedEntries;
                $stats['total_actual_entries'] += $actualTerminalEntries;
            }

            $teacherStats['classes'] = array_unique($teacherStats['classes']);
            $teacherStats['completion_rate'] = $teacherStats['subjects_count'] > 0
                ? round(($teacherStats['completed_terminal'] / $teacherStats['subjects_count']) * 100, 1)
                : 0;

            $stats['completed_scoresheets'] += $teacherStats['completed_terminal'];
            $stats['pending_scoresheets'] += $teacherStats['pending_terminal'];
            $stats['mock_completed'] += $teacherStats['completed_mock'];
            $stats['mock_pending'] += $teacherStats['pending_mock'];
            $stats['teacher_stats'][] = $teacherStats;
        }

        $classGroups = $teacherSubjects->groupBy('schoolclass_id');
        foreach ($classGroups as $classId => $subjects) {
            $className = $subjects->first()->class_name;
            $studentCount = $subjects->first()->student_count;
            $totalSubjects = $subjects->count();
            $completedSubjects = $subjects->where('entry_status', 'complete')->count();
            $classExpectedEntries = $totalSubjects * $studentCount;
            $classActualEntries = $subjects->sum('terminal_entries_count');

            $stats['class_stats'][] = [
                'class_name' => $className,
                'class_id' => $classId,
                'student_count' => $studentCount,
                'total_subjects' => $totalSubjects,
                'completed_subjects' => $completedSubjects,
                'pending_subjects' => $totalSubjects - $completedSubjects,
                'completion_rate' => $totalSubjects > 0 ? round(($completedSubjects / $totalSubjects) * 100, 1) : 0,
                'entry_completion_rate' => $classExpectedEntries > 0 ? round(($classActualEntries / $classExpectedEntries) * 100, 1) : 0,
                'subjects' => $subjects->pluck('subject_name')->toArray(),
            ];
        }

        usort($stats['teacher_stats'], fn($a, $b) => $b['completion_rate'] <=> $a['completion_rate']);
        usort($stats['class_stats'],   fn($a, $b) => $b['completion_rate'] <=> $a['completion_rate']);

        $stats['completion_rate'] = $stats['total_subjects'] > 0
            ? round(($stats['completed_scoresheets'] / $stats['total_subjects']) * 100, 1)
            : 0;

        $stats['entry_completion_rate'] = $stats['total_expected_entries'] > 0
            ? round(($stats['total_actual_entries'] / $stats['total_expected_entries']) * 100, 1)
            : 0;

        return $stats;
    }

    /**
     * Get teacher subjects — CA-aware: a scoresheet counts as "entered" for a
     * student when at least one CA/exam value is > 0.
     */
    protected function getTeacherSubjects($termId, $sessionId)
    {
        $subjects = SubjectTeacher::query()
            ->join('users', 'users.id', '=', 'subjectteacher.staffid')
            ->join('subjectclass', 'subjectclass.subjectteacherid', '=', 'subjectteacher.id')
            ->join('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
            ->join('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->join('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->where('subjectteacher.termid', $termId)
            ->where('subjectteacher.sessionid', $sessionId)
            ->whereNotNull('subjectclass.id')
            ->select(
                'subjectteacher.id as subjectteacher_id',
                'users.id as teacher_id',
                'users.name as teacher_name',
                'subject.subject as subject_name',
                'subject.subject_code',
                'subjectclass.id as subjectclass_id',
                'schoolclass.id as schoolclass_id',
                'schoolclass.classcategoryid',
                DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, '')) as class_name"),
                'classcategories.category as class_category',
                'subjectteacher.termid',
                'subjectteacher.sessionid',
                'schoolterm.term as term_name',
                'schoolsession.session as session_name',
                'subjectclass.teacher_editing_enabled'
            )
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->groupBy(
                'subjectteacher.id', 'users.id', 'users.name', 'subject.subject',
                'subject.subject_code', 'subjectclass.id', 'schoolclass.id', 'class_name',
                'schoolclass.classcategoryid', 'classcategories.category',
                'subjectteacher.termid', 'subjectteacher.sessionid',
                'schoolterm.term', 'schoolsession.session', 'subjectclass.teacher_editing_enabled'
            )
            ->orderBy('users.name')
            ->orderBy('schoolclass.schoolclass')
            ->orderBy('schoolarm.arm')
            ->get();

        return $subjects->map(function ($item) use ($termId, $sessionId) {
            $studentCount = DB::table('studentclass')
                ->where('sessionid', $sessionId)
                ->where('termid', $termId)
                ->where('schoolclassid', $item->schoolclass_id)
                ->count();

            $item->student_count = $studentCount;

            // Terminal entries: any row with at least one CA/exam value > 0
            $terminalEntered = Broadsheets::where('subjectclass_id', $item->subjectclass_id)
                ->where('staff_id', $item->teacher_id)
                ->where('term_id', $termId)
                ->where(function ($q) {
                    $q->where('ca1', '>', 0)
                      ->orWhere('ca2', '>', 0)
                      ->orWhere('ca3', '>', 0)
                      ->orWhere('exam', '>', 0);
                })
                ->count();

            $terminalTotal = Broadsheets::where('subjectclass_id', $item->subjectclass_id)
                ->where('staff_id', $item->teacher_id)
                ->where('term_id', $termId)
                ->count();

            $item->terminal_entries_count   = $terminalEntered;
            $item->terminal_partial_count   = max(0, $terminalTotal - $terminalEntered);
            $item->terminal_started_count   = $terminalTotal;
            $item->has_terminal_scores      = $terminalEntered > 0;
            $item->entry_percentage         = $studentCount > 0
                ? round(($terminalEntered / $studentCount) * 100, 1)
                : 0;

            $item->entry_status = $this->resolveEntryStatus($terminalEntered, $terminalTotal, $studentCount);

            // Mock entries
            $mockEntered = BroadsheetsMock::where('subjectclass_id', $item->subjectclass_id)
                ->where('staff_id', $item->teacher_id)
                ->where('term_id', $termId)
                ->where('exam', '>', 0)
                ->count();

            $mockTotal = BroadsheetsMock::where('subjectclass_id', $item->subjectclass_id)
                ->where('staff_id', $item->teacher_id)
                ->where('term_id', $termId)
                ->count();

            $item->mock_entries_count = $mockEntered;
            $item->has_mock_scores    = $mockEntered > 0;
            $item->mock_percentage    = $studentCount > 0
                ? round(($mockEntered / $studentCount) * 100, 1)
                : 0;
            $item->mock_status = $this->resolveEntryStatus($mockEntered, $mockTotal, $studentCount);

            return $item;
        });
    }

    protected function resolveEntryStatus($fullyEntered, $startedCount, $studentCount)
    {
        if ($studentCount > 0 && $fullyEntered >= $studentCount) return 'complete';
        if ($startedCount > 0) return 'partial';
        return 'not_started';
    }

    // =========================================================================
    // SCORESHEET VIEW — TERMINAL
    // =========================================================================

    public function showScoresheet($subjectclassId, $teacherId, $termId, $sessionId, $type = 'terminal')
    {
        session([
            'admin_score_entry_subjectclass_id' => $subjectclassId,
            'admin_score_entry_teacher_id'      => $teacherId,
            'admin_score_entry_term_id'         => $termId,
            'admin_score_entry_session_id'      => $sessionId,
            'admin_score_entry_type'            => $type,
            'schoolclass_id'                    => null,
            'subjectclass_id'                   => $subjectclassId,
            'staff_id'                          => $teacherId,
            'term_id'                           => $termId,
            'session_id'                        => $sessionId,
        ]);

        if ($type === 'mock') {
            return $this->showMockScoresheet($subjectclassId, $teacherId, $termId, $sessionId);
        }

        $subjectClass = Subjectclass::with(['subject', 'schoolclass.arm', 'schoolclass.classcategory'])
            ->findOrFail($subjectclassId);

        $teacher     = User::findOrFail($teacherId);
        $term        = Schoolterm::findOrFail($termId);
        $session     = Schoolsession::findOrFail($sessionId);
        $schoolclass = $subjectClass->schoolclass;

        session(['schoolclass_id' => $schoolclass->id ?? null]);

        $broadsheets = $this->getBroadsheets($teacherId, $termId, $sessionId, $schoolclass->id, $subjectclassId);

        if ($broadsheets->isNotEmpty()) {
            $this->updateClassMetrics($subjectclassId, $teacherId, $termId, $sessionId);
            $this->updateSubjectPositions($subjectclassId, $teacherId, $termId, $sessionId);
            $this->updateClassPositions($schoolclass->id, $termId, $sessionId);
            $broadsheets = $this->getBroadsheets($teacherId, $termId, $sessionId, $schoolclass->id, $subjectclassId);
        }

        $pagetitle = sprintf(
            'Admin: %s – %s (%s) | %s %s | %s %s',
            $teacher->name,
            $subjectClass->subject->subject,
            $subjectClass->subject->subject_code,
            $schoolclass->schoolclass,
            $schoolclass->arm->arm ?? '',
            $term->term,
            $session->session
        );

        $is_senior = $schoolclass && $schoolclass->classcategory
            ? $schoolclass->classcategory->is_senior
            : false;

        $globalLock = ScoresheetLock::where([
            'subjectclass_id' => $subjectclassId,
            'term_id'         => $termId,
            'session_id'      => $sessionId,
            'is_active'       => true,
        ])->with('lockedBy')->first();

        $lockedCount           = $broadsheets->where('is_locked', true)->count();
        $teacherEditingEnabled = $subjectClass->teacher_editing_enabled;

        return view('admin.score-entry.scoresheet', compact(
            'broadsheets', 'pagetitle', 'is_senior',
            'subjectclassId', 'teacherId', 'termId', 'sessionId',
            'teacher', 'subjectClass', 'term', 'session', 'schoolclass',
            'globalLock', 'lockedCount', 'teacherEditingEnabled'
        ));
    }

    // =========================================================================
    // SCORESHEET VIEW — MOCK
    // =========================================================================

    public function showMockScoresheet($subjectclassId, $teacherId, $termId, $sessionId)
    {
        $subjectClass = Subjectclass::with(['subject', 'schoolclass.arm', 'schoolclass.classcategory'])
            ->findOrFail($subjectclassId);

        $teacher     = User::findOrFail($teacherId);
        $term        = Schoolterm::findOrFail($termId);
        $session     = Schoolsession::findOrFail($sessionId);
        $schoolclass = $subjectClass->schoolclass;

        session(['schoolclass_id' => $schoolclass->id ?? null]);

        $broadsheets = $this->getMockBroadsheets($teacherId, $termId, $sessionId, $schoolclass->id, $subjectclassId);

        if ($broadsheets->isNotEmpty()) {
            $this->updateMockClassMetrics($subjectclassId, $teacherId, $termId, $sessionId);
            $this->updateMockSubjectPositions($subjectclassId, $teacherId, $termId, $sessionId);
            $broadsheets = $this->getMockBroadsheets($teacherId, $termId, $sessionId, $schoolclass->id, $subjectclassId);
        }

        $pagetitle = sprintf(
            'Admin Mock: %s – %s (%s) | %s %s | %s %s',
            $teacher->name,
            $subjectClass->subject->subject,
            $subjectClass->subject->subject_code,
            $schoolclass->schoolclass,
            $schoolclass->arm->arm ?? '',
            $term->term,
            $session->session
        );

        $is_senior = $schoolclass && $schoolclass->classcategory
            ? $schoolclass->classcategory->is_senior
            : false;

        return view('admin.score-entry.mock-scoresheet', compact(
            'broadsheets', 'pagetitle', 'is_senior',
            'subjectclassId', 'teacherId', 'termId', 'sessionId',
            'teacher', 'subjectClass', 'term', 'session', 'schoolclass'
        ));
    }

    // =========================================================================
    // SINGLE UPDATE — CA1/CA2/CA3/EXAM (with lock check)
    // =========================================================================

    public function singleUpdate(Request $request)
    {
        try {
            $broadsheetId = $request->input('broadsheet_id');

            $lockCheck = $this->checkTeacherCanEdit($broadsheetId);
            if (!$lockCheck['allowed']) {
                return response()->json([
                    'success' => false,
                    'message' => $lockCheck['message'],
                    'locked'  => true,
                ], 423);
            }

            $validated = $request->validate([
                'broadsheet_id' => 'required|exists:broadsheets,id',
                'ca1'           => 'nullable|numeric|min:0|max:100',
                'ca2'           => 'nullable|numeric|min:0|max:100',
                'ca3'           => 'nullable|numeric|min:0|max:100',
                'exam'          => 'nullable|numeric|min:0|max:100',
            ]);

            $broadsheet = Broadsheets::findOrFail($broadsheetId);
            $termId     = $broadsheet->term_id;
            $record     = BroadsheetRecord::find($broadsheet->broadsheet_record_id);

            if (!$record) {
                return response()->json(['success' => false, 'message' => 'Broadsheet record missing.'], 404);
            }

            $ca1  = (float) ($validated['ca1']  ?? 0);
            $ca2  = (float) ($validated['ca2']  ?? 0);
            $ca3  = (float) ($validated['ca3']  ?? 0);
            $exam = (float) ($validated['exam'] ?? 0);

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
                'last_modified_by' => auth()->id(),
                'last_modified_at' => now(),
                'entry_source'     => $broadsheet->entry_source ?: 'admin',
            ]);

            $this->updateClassMetrics($broadsheet->subjectclass_id, $broadsheet->staff_id, $termId, $record->session_id);
            $this->updateSubjectPositions($broadsheet->subjectclass_id, $broadsheet->staff_id, $termId, $record->session_id);
            $this->updateClassPositions($record->schoolclass_id, $termId, $record->session_id);

            $fresh = Broadsheets::find($broadsheetId);

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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Admin singleUpdate error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // MOCK SINGLE UPDATE
    // =========================================================================

    public function mockSingleUpdate(Request $request)
    {
        try {
            $validated = $request->validate([
                'broadsheet_id' => 'required|exists:broadsheetmock,id',
                'exam'          => 'required|numeric|min:0|max:100',
            ]);

            $broadsheetId = $validated['broadsheet_id'];
            $examScore    = (float) $validated['exam'];

            $broadsheet = BroadsheetsMock::findOrFail($broadsheetId);
            $mockRecord = BroadsheetRecordMock::find($broadsheet->broadsheet_records_mock_id);

            $schoolclassId = $mockRecord?->schoolclass_id ?? 0;
            $sessionId     = $mockRecord?->session_id ?? session('admin_score_entry_session_id');
            $schoolclass   = Schoolclass::with('classcategory')->find($schoolclassId);

            $examScore = max(0, min($examScore, 100));
            $total     = round($examScore, 2);

            $grade = $schoolclass && $schoolclass->classcategory
                ? $schoolclass->classcategory->calculateGrade($total)
                : $this->getDefaultGrade($total);
            $remark = $this->getRemark($grade);

            $broadsheet->update([
                'exam'   => $examScore,
                'total'  => $total,
                'grade'  => $grade,
                'remark' => $remark,
            ]);

            $this->updateMockClassMetrics($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $sessionId);
            $this->updateMockSubjectPositions($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $sessionId);

            $broadsheet->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Mock score updated successfully!',
                'data'    => [
                    'total'    => $broadsheet->total,
                    'grade'    => $broadsheet->grade,
                    'remark'   => $broadsheet->remark,
                    'position' => $broadsheet->subject_position_class,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Admin mockSingleUpdate error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to save: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // BULK UPDATE — CA-based
    // =========================================================================

    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'scores'            => 'required|array',
            'scores.*.id'       => 'required|exists:broadsheets,id',
            'scores.*.ca1'      => 'nullable|numeric|min:0|max:100',
            'scores.*.ca2'      => 'nullable|numeric|min:0|max:100',
            'scores.*.ca3'      => 'nullable|numeric|min:0|max:100',
            'scores.*.exam'     => 'nullable|numeric|min:0|max:100',
            'term_id'           => 'required|exists:schoolterm,id',
            'session_id'        => 'required|exists:schoolsession,id',
            'subjectclass_id'   => 'required|exists:subjectclass,id',
            'staff_id'          => 'required|exists:users,id',
            'schoolclass_id'    => 'required|exists:schoolclass,id',
        ]);

        $scores    = $validated['scores'];
        $lockedIds = [];

        foreach ($scores as $sd) {
            $lc = $this->checkTeacherCanEdit($sd['id']);
            if (!$lc['allowed']) $lockedIds[] = $sd['id'];
        }

        if (!empty($lockedIds)) {
            return response()->json([
                'success'    => false,
                'message'    => count($lockedIds) . ' scoresheet(s) are locked.',
                'locked_ids' => $lockedIds,
            ], 423);
        }

        $term_id         = $validated['term_id'];
        $session_id      = $validated['session_id'];
        $subjectclass_id = $validated['subjectclass_id'];
        $staff_id        = $validated['staff_id'];
        $schoolclass_id  = $validated['schoolclass_id'];

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
                $total     = round(($caAverage + $exam) / 2, 1);

                $record = BroadsheetRecord::find($broadsheet->broadsheet_record_id);
                if (!$record) continue;

                $bf  = $this->getPreviousTermCum($record->student_id, $record->subject_id, $term_id, $session_id);
                $cum = $term_id == 1 ? $total : round(($bf + $total) / 2, 2);

                $grade = $schoolclass->classcategory
                    ? $schoolclass->classcategory->calculateGrade($cum)
                    : $this->getDefaultGrade($cum);

                $broadsheet->update([
                    'ca1'              => $ca1,
                    'ca2'              => $ca2,
                    'ca3'              => $ca3,
                    'exam'             => $exam,
                    'total'            => $total,
                    'bf'               => $bf,
                    'cum'              => $cum,
                    'grade'            => $grade,
                    'remark'           => $this->getRemark($grade),
                    'last_modified_by' => auth()->id(),
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
        ]);
    }

    // =========================================================================
    // MOCK BULK UPDATE
    // =========================================================================

    public function mockBulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'scores'           => 'required|array',
            'scores.*.id'      => 'required|exists:broadsheetmock,id',
            'scores.*.exam'    => 'nullable|numeric|min:0|max:100',
            'term_id'          => 'required|exists:schoolterm,id',
            'session_id'       => 'required|exists:schoolsession,id',
            'subjectclass_id'  => 'required|exists:subjectclass,id',
            'staff_id'         => 'required|exists:users,id',
            'schoolclass_id'   => 'required|exists:schoolclass,id',
        ]);

        $scores          = $validated['scores'];
        $term_id         = $validated['term_id'];
        $session_id      = $validated['session_id'];
        $subjectclass_id = $validated['subjectclass_id'];
        $staff_id        = $validated['staff_id'];
        $schoolclass_id  = $validated['schoolclass_id'];
        $schoolclass     = Schoolclass::with('classcategory')->find($schoolclass_id);
        $updatedCount    = 0;

        DB::transaction(function () use ($scores, $session_id, $schoolclass, &$updatedCount) {
            foreach ($scores as $scoreData) {
                $broadsheet = BroadsheetsMock::find($scoreData['id']);
                if (!$broadsheet) continue;

                $examScore = max(0, min((float) ($scoreData['exam'] ?? 0), 100));
                $total     = round($examScore, 2);

                $grade = $schoolclass && $schoolclass->classcategory
                    ? $schoolclass->classcategory->calculateGrade($total)
                    : $this->getDefaultGrade($total);

                $broadsheet->update([
                    'exam'   => $examScore,
                    'total'  => $total,
                    'grade'  => $grade,
                    'remark' => $this->getRemark($grade),
                ]);

                $updatedCount++;
            }
        });

        $this->updateMockClassMetrics($subjectclass_id, $staff_id, $term_id, $session_id);
        $this->updateMockSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id);

        $updatedBroadsheets = $this->getMockBroadsheets($staff_id, $term_id, $session_id, $schoolclass_id, $subjectclass_id);

        return response()->json([
            'success' => true,
            'message' => "{$updatedCount} mock score(s) updated!",
            'data'    => ['broadsheets' => $updatedBroadsheets->values()->toArray()],
        ]);
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function destroy(Request $request)
    {
        $id   = $request->input('id');
        $type = $request->input('type', 'terminal');

        $lockCheck = $this->checkTeacherCanEdit($id, $type === 'mock');
        if (!$lockCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $lockCheck['message'],
                'locked'  => true,
            ], 423);
        }

        if ($type === 'mock') {
            $broadsheet      = BroadsheetsMock::findOrFail($id);
            $subjectclassid  = $broadsheet->subjectclass_id;
            $staffid         = $broadsheet->staff_id;
            $termid          = $broadsheet->term_id;
            $mockRecord      = BroadsheetRecordMock::find($broadsheet->broadsheet_records_mock_id);
            $broadsheet->delete();
            if ($mockRecord) {
                $this->updateMockClassMetrics($subjectclassid, $staffid, $termid, $mockRecord->session_id);
                $this->updateMockSubjectPositions($subjectclassid, $staffid, $termid, $mockRecord->session_id);
            }
        } else {
            $broadsheet       = Broadsheets::findOrFail($id);
            $subjectclassid   = $broadsheet->subjectclass_id;
            $staffid          = $broadsheet->staff_id;
            $termid           = $broadsheet->term_id;
            $broadsheetRecord = BroadsheetRecord::find($broadsheet->broadsheet_record_id);

            $broadsheet->delete();

            if ($broadsheetRecord) {
                $this->updateClassMetrics($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
                $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
                $this->updateClassPositions($broadsheetRecord->schoolclass_id, $termid, $broadsheetRecord->session_id);
            }
        }

        return response()->json(['success' => true, 'message' => 'Score deleted successfully!']);
    }

    // =========================================================================
    // RESULTS — AJAX refresh
    // =========================================================================

    public function results(Request $request)
    {
        try {
            $subjectclass_id = session('admin_score_entry_subjectclass_id') ?? session('subjectclass_id');
            $schoolclass_id  = session('schoolclass_id') ?? $request->get('schoolclass_id');
            $term_id         = session('admin_score_entry_term_id') ?? session('term_id');
            $session_id      = session('admin_score_entry_session_id') ?? session('session_id');
            $type            = session('admin_score_entry_type', 'terminal');

            if (!$subjectclass_id || !$term_id || !$session_id) {
                return response()->json(['success' => false, 'message' => 'Missing session data', 'scores' => []], 400);
            }

            if ($type === 'mock') {
                $staff_id    = session('admin_score_entry_teacher_id');
                $broadsheets = $this->getMockBroadsheets($staff_id, $term_id, $session_id, $schoolclass_id, $subjectclass_id);
                return response()->json([
                    'success' => true,
                    'scores'  => $broadsheets->values()->toArray(),
                    'type'    => 'mock',
                ]);
            }

            $staff_id    = session('admin_score_entry_teacher_id');
            $broadsheets = $this->getBroadsheets($staff_id, $term_id, $session_id, $schoolclass_id, $subjectclass_id);

            return response()->json([
                'success' => true,
                'scores'  => $broadsheets->values()->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Admin results error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal server error.'], 500);
        }
    }

    // =========================================================================
    // EXPORT — Excel
    // =========================================================================

    public function export(Request $request)
    {
        $schoolclassId  = $request->input('schoolclass_id',  session('schoolclass_id'));
        $subjectclassId = $request->input('subjectclass_id', session('admin_score_entry_subjectclass_id'));
        $termId         = $request->input('term_id',         session('admin_score_entry_term_id'));
        $sessionId      = $request->input('session_id',      session('admin_score_entry_session_id'));
        $staffId        = $request->input('staff_id',        session('admin_score_entry_teacher_id'));

        $subjectClass = Subjectclass::with('subject')->find($subjectclassId);
        $schoolclass  = Schoolclass::find($schoolclassId);
        $term         = Schoolterm::find($termId);
        $session      = Schoolsession::find($sessionId);

        $subjectName = preg_replace('/[^a-zA-Z0-9-]/', '_', $subjectClass?->subject?->subject ?? 'subject');
        $className   = preg_replace('/[^a-zA-Z0-9-]/', '_', $schoolclass?->schoolclass ?? 'class');
        $termName    = preg_replace('/[^a-zA-Z0-9-]/', '_', $term?->term ?? 'term');
        $sessionName = preg_replace('/[^a-zA-Z0-9-]/', '_', $session?->session ?? 'session');

        $filename = "admin_{$subjectName}_{$className}_{$termName}_{$sessionName}_scoresheet.xlsx";

        $export = new AdminRecordsheetExport(
            (int) $schoolclassId,
            (int) $subjectclassId,
            (int) $termId,
            (int) $sessionId,
            (int) $staffId
        );

        return Excel::download($export, $filename);
    }

    // =========================================================================
    // IMPORT — single scoresheet
    // =========================================================================

    public function import(Request $request)
    {
        try {
            $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

            $importData = [
                'subjectclass_id' => $request->input('subjectclass_id', session('admin_score_entry_subjectclass_id')),
                'staff_id'        => $request->input('staff_id',        session('admin_score_entry_teacher_id')),
                'term_id'         => $request->input('term_id',         session('admin_score_entry_term_id')),
                'session_id'      => $request->input('session_id',      session('admin_score_entry_session_id')),
                'schoolclass_id'  => $request->input('schoolclass_id',  session('schoolclass_id')),
            ];

            if (empty($importData['subjectclass_id']) || empty($importData['staff_id'])) {
                return response()->json(['success' => false, 'message' => 'Missing session data. Please open the scoresheet first.'], 422);
            }

            $importer = new AdminScoresheetImport($importData);
            Excel::import($importer, $request->file('file'));

            $this->updateClassMetrics(
                (int) $importData['subjectclass_id'],
                (int) $importData['staff_id'],
                (int) $importData['term_id'],
                (int) $importData['session_id']
            );
            $this->updateSubjectPositions(
                (int) $importData['subjectclass_id'],
                (int) $importData['staff_id'],
                (int) $importData['term_id'],
                (int) $importData['session_id']
            );
            $this->updateClassPositions(
                (int) $importData['schoolclass_id'],
                (int) $importData['term_id'],
                (int) $importData['session_id']
            );

            return response()->json([
                'success'     => true,
                'message'     => 'Scores imported successfully!',
                'broadsheets' => $importer->getUpdatedBroadsheets(),
                'failures'    => $importer->getFailures(),
            ]);
        } catch (\Exception $e) {
            Log::error('Admin import failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Import failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PDF DOWNLOADS — DomPDF
    // =========================================================================

    public function downloadMarksSheet(Request $request)
    {
        try {
            $subjectclassid = $request->input('subjectclass_id', session('admin_score_entry_subjectclass_id'));
            $staffid        = $request->input('staff_id',        session('admin_score_entry_teacher_id'));
            $termid         = $request->input('term_id',         session('admin_score_entry_term_id'));
            $sessionid      = $request->input('session_id',      session('admin_score_entry_session_id'));
            $schoolclassid  = $request->input('schoolclass_id',  session('schoolclass_id'));
            $type           = $request->input('type',            session('admin_score_entry_type', 'terminal'));

            if ($type === 'mock') {
                return $this->downloadMockMarksSheet($subjectclassid, $staffid, $termid, $sessionid, $schoolclassid);
            }

            if (!$subjectclassid || !$staffid || !$termid || !$sessionid || !$schoolclassid) {
                return response()->json(['success' => false, 'message' => 'Missing session data.'], 400);
            }

            $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);
            if ($broadsheets->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No students found.'], 404);
            }

            $teacher     = User::find($staffid);
            $teacherName = $teacher ? $teacher->name : '';
            $school      = SchoolInformation::first();

            $pdf = Pdf::loadView('subjectscoresheet.marksheet', [
                'broadsheets' => $broadsheets,
                'classInfo'   => $broadsheets->first(),
                'school'      => $school,
                'teacherName' => $teacherName,
                'isAdminView' => true,
            ])->setPaper('a4', 'landscape');

            return $pdf->download('admin-marks-sheet-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            Log::error('Admin marks sheet error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function downloadScoresPdf(Request $request)
    {
        try {
            $subjectclassid = $request->input('subjectclass_id', session('admin_score_entry_subjectclass_id'));
            $staffid        = $request->input('staff_id',        session('admin_score_entry_teacher_id'));
            $termid         = $request->input('term_id',         session('admin_score_entry_term_id'));
            $sessionid      = $request->input('session_id',      session('admin_score_entry_session_id'));
            $schoolclassid  = $request->input('schoolclass_id',  session('schoolclass_id'));

            if (!$subjectclassid || !$staffid || !$termid || !$sessionid || !$schoolclassid) {
                return response()->json(['success' => false, 'message' => 'Missing session data.'], 400);
            }

            $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);
            if ($broadsheets->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No students found.'], 404);
            }

            $teacher     = User::find($staffid);
            $teacherName = $teacher ? $teacher->name : '';
            $school      = SchoolInformation::first();

            $pdf = Pdf::loadView('subjectscoresheet.scores-pdf', [
                'broadsheets' => $broadsheets,
                'classInfo'   => $broadsheets->first(),
                'school'      => $school,
                'teacherName' => $teacherName,
                'isAdminView' => true,
            ])->setPaper('a4', 'landscape');

            $subject  = preg_replace('/[^a-zA-Z0-9-]/', '_', $broadsheets->first()->subject ?? 'subject');
            $class    = preg_replace('/[^a-zA-Z0-9-]/', '_', $broadsheets->first()->schoolclass ?? 'class');
            $termName = preg_replace('/[^a-zA-Z0-9-]/', '_', $broadsheets->first()->term ?? 'term');

            return $pdf->download("admin-scores-{$subject}-{$class}-{$termName}-" . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            Log::error('Admin scores PDF error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    protected function downloadMockMarksSheet($subjectclassid, $staffid, $termid, $sessionid, $schoolclassid)
    {
        $broadsheets = $this->getMockBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);
        if ($broadsheets->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No mock scores found.'], 404);
        }

        $teacher     = User::find($staffid);
        $teacherName = $teacher ? $teacher->name : '';
        $school      = SchoolInformation::first();

        $pdf = Pdf::loadView('subjectscoresheet.mock-marksheet', [
            'broadsheets' => $broadsheets,
            'classInfo'   => $broadsheets->first(),
            'school'      => $school,
            'teacherName' => $teacherName,
            'isAdminView' => true,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('admin-mock-marks-sheet-' . date('Y-m-d') . '.pdf');
    }

    // =========================================================================
    // LOCK MANAGEMENT
    // =========================================================================

    public function lockManagement()
    {
        $pagetitle = "Scoresheet Lock Management";
        return view('admin.score-entry.lock-management', compact('pagetitle'));
    }

    public function getScoresheetsList(Request $request)
    {
        try {
            $query = DB::table('subjectclass')
                ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->join('users', 'users.id', '=', 'subjectteacher.staffid')
                ->join('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->join('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->select([
                    'subjectclass.id as subjectclass_id',
                    'users.id as teacher_id',
                    'users.name as teacher_name',
                    'subject.subject as subject_name',
                    'subject.subject_code',
                    'schoolclass.id as schoolclass_id',
                    DB::raw("CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, '')) as class_name"),
                    'subjectteacher.termid',
                    'subjectteacher.sessionid',
                    'schoolterm.term as term_name',
                    'schoolsession.session as session_name',
                    'subjectclass.teacher_editing_enabled',

                    DB::raw("(SELECT sl.is_active FROM scoresheet_locks sl
                        WHERE sl.subjectclass_id = subjectclass.id
                          AND sl.term_id    = subjectteacher.termid
                          AND sl.session_id = subjectteacher.sessionid
                          AND sl.is_active  = 1 LIMIT 1) as global_lock_active"),

                    DB::raw("(SELECT sl.reason FROM scoresheet_locks sl
                        WHERE sl.subjectclass_id = subjectclass.id
                          AND sl.term_id    = subjectteacher.termid
                          AND sl.session_id = subjectteacher.sessionid
                          AND sl.is_active  = 1 LIMIT 1) as global_lock_reason"),

                    DB::raw("(SELECT u2.name FROM scoresheet_locks sl
                        LEFT JOIN users u2 ON u2.id = sl.locked_by
                        WHERE sl.subjectclass_id = subjectclass.id
                          AND sl.term_id    = subjectteacher.termid
                          AND sl.session_id = subjectteacher.sessionid
                          AND sl.is_active  = 1 LIMIT 1) as global_lock_by"),

                    DB::raw("(SELECT COUNT(*) FROM broadsheets b
                        WHERE b.subjectclass_id = subjectclass.id AND b.is_locked = 1) as individually_locked_count"),

                    DB::raw("(SELECT COUNT(*) FROM broadsheets b
                        WHERE b.subjectclass_id = subjectclass.id) as total_students"),
                ]);

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('users.name', 'LIKE', "%{$search}%")
                      ->orWhere('subject.subject', 'LIKE', "%{$search}%")
                      ->orWhere('subject.subject_code', 'LIKE', "%{$search}%");
                });
            }

            if ($request->filled('term_id'))    $query->where('subjectteacher.termid',    $request->term_id);
            if ($request->filled('session_id')) $query->where('subjectteacher.sessionid', $request->session_id);
            if ($request->filled('class_id'))   $query->where('schoolclass.id',           $request->class_id);

            if ($request->filled('status')) {
                switch ($request->status) {
                    case 'open':
                        $query->where('subjectclass.teacher_editing_enabled', 1)
                              ->whereRaw("(SELECT COUNT(*) FROM scoresheet_locks sl
                                  WHERE sl.subjectclass_id = subjectclass.id
                                    AND sl.term_id    = subjectteacher.termid
                                    AND sl.session_id = subjectteacher.sessionid
                                    AND sl.is_active  = 1) = 0")
                              ->whereRaw("(SELECT COUNT(*) FROM broadsheets b
                                  WHERE b.subjectclass_id = subjectclass.id AND b.is_locked = 1) = 0");
                        break;
                    case 'individual':
                        $query->whereRaw("(SELECT COUNT(*) FROM broadsheets b
                                  WHERE b.subjectclass_id = subjectclass.id AND b.is_locked = 1) > 0")
                              ->whereRaw("(SELECT COUNT(*) FROM scoresheet_locks sl
                                  WHERE sl.subjectclass_id = subjectclass.id
                                    AND sl.term_id    = subjectteacher.termid
                                    AND sl.session_id = subjectteacher.sessionid
                                    AND sl.is_active  = 1) = 0");
                        break;
                    case 'global':
                        $query->whereRaw("(SELECT COUNT(*) FROM scoresheet_locks sl
                                  WHERE sl.subjectclass_id = subjectclass.id
                                    AND sl.term_id    = subjectteacher.termid
                                    AND sl.session_id = subjectteacher.sessionid
                                    AND sl.is_active  = 1) > 0");
                        break;
                    case 'disabled':
                        $query->where('subjectclass.teacher_editing_enabled', 0);
                        break;
                }
            }

            $results = $query
                ->groupBy(
                    'subjectclass.id', 'users.id', 'users.name',
                    'subject.subject', 'subject.subject_code',
                    'schoolclass.id', 'schoolclass.schoolclass',
                    'schoolarm.arm',
                    'subjectteacher.termid', 'subjectteacher.sessionid',
                    'schoolterm.term', 'schoolsession.session',
                    'subjectclass.teacher_editing_enabled'
                )
                ->orderBy('users.name')
                ->orderBy('subject.subject')
                ->get();

            return response()->json([
                'success' => true,
                'data'    => $results,
                'filters' => [
                    'terms'    => Schoolterm::select('id', 'term')->get(),
                    'sessions' => Schoolsession::select('id', 'session')->orderBy('id', 'desc')->get(),
                    'classes'  => DB::table('schoolclass')
                        ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                        ->select('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm')
                        ->get(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Get scoresheets list error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function lockScoresheet(Request $request)
    {
        $request->validate([
            'broadsheet_id' => 'required|exists:broadsheets,id',
            'reason'        => 'nullable|string|max:500',
        ]);

        try {
            $broadsheet = Broadsheets::findOrFail($request->broadsheet_id);

            if ($broadsheet->is_locked) {
                return response()->json(['success' => false, 'message' => 'Already locked.'], 422);
            }

            $broadsheet->update([
                'is_locked'   => true,
                'locked_by'   => auth()->id(),
                'locked_at'   => now(),
                'lock_reason' => $request->reason ?: 'Locked by administrator',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Scoresheet locked successfully.',
                'data'    => ['is_locked' => true],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to lock scoresheet.'], 500);
        }
    }

    public function unlockScoresheet(Request $request)
    {
        $request->validate(['broadsheet_id' => 'required|exists:broadsheets,id']);

        try {
            $broadsheet = Broadsheets::findOrFail($request->broadsheet_id);

            $globalLock = ScoresheetLock::where([
                'subjectclass_id' => $broadsheet->subjectclass_id,
                'term_id'         => $broadsheet->term_id,
                'session_id'      => $broadsheet->session_id,
                'is_active'       => true,
            ])->first();

            if ($globalLock) {
                return response()->json(['success' => false, 'message' => 'Cannot unlock: Subject is under global lock.'], 422);
            }

            $broadsheet->update([
                'is_locked'           => false,
                'locked_by'           => null,
                'locked_at'           => null,
                'lock_reason'         => null,
                'scheduled_unlock_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Scoresheet unlocked successfully.',
                'data'    => ['is_locked' => false],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to unlock scoresheet.'], 500);
        }
    }

    public function lockScoresheetWithSchedule(Request $request)
    {
        $request->validate([
            'broadsheet_id'       => 'required|exists:broadsheets,id',
            'reason'              => 'nullable|string|max:500',
            'scheduled_unlock_at' => 'required|date|after:now',
        ]);

        $broadsheet = Broadsheets::findOrFail($request->broadsheet_id);

        if ($broadsheet->is_locked) {
            return response()->json(['success' => false, 'message' => 'Already locked.'], 422);
        }

        $broadsheet->update([
            'is_locked'           => true,
            'locked_by'           => auth()->id(),
            'locked_at'           => now(),
            'lock_reason'         => $request->reason ?: 'Locked by administrator',
            'scheduled_unlock_at' => $request->scheduled_unlock_at,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Scoresheet locked with scheduled unlock at ' . $request->scheduled_unlock_at,
            'data'    => [
                'is_locked'           => true,
                'scheduled_unlock_at' => $request->scheduled_unlock_at,
            ],
        ]);
    }

    // =========================================================================
    // BULK LOCK MANAGEMENT
    // =========================================================================

    public function bulkLockManagement(Request $request)
    {
        $request->validate([
            'action'             => 'required|in:lock_individual,unlock_individual,lock_global,unlock_global,disable_editing,enable_editing',
            'subjectclass_ids'   => 'required|array',
            'subjectclass_ids.*' => 'exists:subjectclass,id',
            'reason'             => 'nullable|string|max:500',
            'term_id'            => 'required_if:action,lock_global,unlock_global|nullable|exists:schoolterm,id',
            'session_id'         => 'required_if:action,lock_global,unlock_global|nullable|exists:schoolsession,id',
        ]);

        $action          = $request->action;
        $subjectclassIds = $request->subjectclass_ids;
        $reason          = $request->reason;
        $userId          = auth()->id();

        DB::beginTransaction();
        try {
            $results       = [];
            $lockedCount   = 0;
            $unlockedCount = 0;

            foreach ($subjectclassIds as $subjectclassId) {
                $subjectClass = Subjectclass::with('subject')->find($subjectclassId);
                if (!$subjectClass) continue;

                $subjectName = $subjectClass->subject->subject ?? "Subject#{$subjectclassId}";

                switch ($action) {
                    case 'lock_individual':
                        $count = Broadsheets::where('subjectclass_id', $subjectclassId)
                            ->where('is_locked', false)
                            ->update([
                                'is_locked'   => true,
                                'locked_by'   => $userId,
                                'locked_at'   => now(),
                                'lock_reason' => $reason ?: 'Locked by admin',
                            ]);
                        $lockedCount += $count;
                        $results[] = "Locked {$count} scoresheets for {$subjectName}";
                        break;

                    case 'unlock_individual':
                        $count = Broadsheets::where('subjectclass_id', $subjectclassId)
                            ->where('is_locked', true)
                            ->update([
                                'is_locked'           => false,
                                'locked_by'           => null,
                                'locked_at'           => null,
                                'lock_reason'         => null,
                                'scheduled_unlock_at' => null,
                            ]);
                        $unlockedCount += $count;
                        $results[] = "Unlocked {$count} scoresheets for {$subjectName}";
                        break;

                    case 'lock_global':
                        ScoresheetLock::updateOrCreate(
                            [
                                'subjectclass_id' => $subjectclassId,
                                'term_id'         => $request->term_id,
                                'session_id'      => $request->session_id,
                            ],
                            [
                                'is_active' => true,
                                'locked_by' => $userId,
                                'locked_at' => now(),
                                'reason'    => $reason ?: 'Global lock applied by admin',
                            ]
                        );
                        $count = Broadsheets::where('subjectclass_id', $subjectclassId)
                            ->update([
                                'is_locked'   => true,
                                'locked_by'   => $userId,
                                'locked_at'   => now(),
                                'lock_reason' => $reason ?: 'Global lock applied',
                            ]);
                        $lockedCount += $count;
                        $results[] = "Global lock applied to {$subjectName} - Locked {$count} scoresheets";
                        break;

                    case 'unlock_global':
                        ScoresheetLock::where([
                            'subjectclass_id' => $subjectclassId,
                            'term_id'         => $request->term_id,
                            'session_id'      => $request->session_id,
                        ])->update(['is_active' => false]);
                        $results[] = "Global lock removed from {$subjectName}";
                        break;

                    case 'disable_editing':
                        $subjectClass->update(['teacher_editing_enabled' => false]);
                        $count = Broadsheets::where('subjectclass_id', $subjectclassId)
                            ->update([
                                'is_locked'   => true,
                                'locked_by'   => $userId,
                                'locked_at'   => now(),
                                'lock_reason' => $reason ?: 'Teacher editing disabled by admin',
                            ]);
                        $lockedCount += $count;
                        $results[] = "Teacher editing disabled for {$subjectName} - Locked {$count} scoresheets";
                        break;

                    case 'enable_editing':
                        $subjectClass->update(['teacher_editing_enabled' => true]);
                        $results[] = "Teacher editing enabled for {$subjectName}";
                        break;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => implode("\n", $results),
                'data'    => [
                    'results'        => $results,
                    'locked_count'   => $lockedCount,
                    'unlocked_count' => $unlockedCount,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk lock management error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function disableTeacherEditing(Request $request)
    {
        $request->validate([
            'subjectclass_ids'   => 'required|array',
            'subjectclass_ids.*' => 'exists:subjectclass,id',
            'reason'             => 'nullable|string|max:500',
        ]);

        return $this->bulkLockManagement(new Request([
            'action'          => 'disable_editing',
            'subjectclass_ids'=> $request->subjectclass_ids,
            'reason'          => $request->reason,
        ]));
    }

    public function enableTeacherEditing(Request $request)
    {
        $request->validate([
            'subjectclass_ids'   => 'required|array',
            'subjectclass_ids.*' => 'exists:subjectclass,id',
        ]);

        return $this->bulkLockManagement(new Request([
            'action'          => 'enable_editing',
            'subjectclass_ids'=> $request->subjectclass_ids,
        ]));
    }

    public function getLockStatus(Request $request)
    {
        $request->validate([
            'subjectclass_id' => 'required|exists:subjectclass,id',
            'term_id'         => 'required|exists:schoolterm,id',
            'session_id'      => 'required|exists:schoolsession,id',
        ]);

        $subjectclass = Subjectclass::findOrFail($request->subjectclass_id);

        $globalLock = ScoresheetLock::where([
            'subjectclass_id' => $request->subjectclass_id,
            'term_id'         => $request->term_id,
            'session_id'      => $request->session_id,
            'is_active'       => true,
        ])->first();

        $lockedCount = Broadsheets::where([
            'subjectclass_id' => $request->subjectclass_id,
            'term_id'         => $request->term_id,
            'is_locked'       => true,
        ])->whereHas('broadsheetRecord', fn($q) => $q->where('session_id', $request->session_id))->count();

        $totalCount = Broadsheets::where([
            'subjectclass_id' => $request->subjectclass_id,
            'term_id'         => $request->term_id,
        ])->whereHas('broadsheetRecord', fn($q) => $q->where('session_id', $request->session_id))->count();

        return response()->json([
            'success' => true,
            'data'    => [
                'teacher_editing_enabled' => $subjectclass->teacher_editing_enabled,
                'global_lock_exists'      => !is_null($globalLock),
                'global_lock_info'        => $globalLock ? [
                    'locked_by'           => optional($globalLock->lockedBy)->name,
                    'locked_at'           => optional($globalLock->locked_at)->format('Y-m-d H:i:s'),
                    'reason'              => $globalLock->reason,
                    'scheduled_unlock_at' => $globalLock->scheduled_unlock_at,
                ] : null,
                'locked_count'    => $lockedCount,
                'total_count'     => $totalCount,
                'lock_percentage' => $totalCount > 0 ? round(($lockedCount / $totalCount) * 100) : 0,
            ],
        ]);
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
            ]);
        } catch (\Exception $e) {
            Log::error('Admin updateAllArmPositions error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // GRADE PREVIEW
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
    // QUERY HELPERS
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
                if ($subjectClassId) $join->where('subjectclass.id', $subjectClassId);
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

        if ($schoolClassId) $query->where('schoolclass.id', $schoolClassId);

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
            'studentpicture.picture',

            // ✅ CA fields
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
            'broadsheets.entered_at',
            'broadsheets.last_modified_at',
            'broadsheets.entry_source',
        ]);

        // Recalculate total/bf/cum/grade per the project-1 formula
        foreach ($results as $broadsheet) {
            $ca1  = $broadsheet->ca1 ?? 0;
            $ca2  = $broadsheet->ca2 ?? 0;
            $ca3  = $broadsheet->ca3 ?? 0;
            $exam = $broadsheet->exam ?? 0;

            $caAverage = ($ca1 + $ca2 + $ca3) / 3;
            $newTotal  = round(($caAverage + $exam) / 2, 1);

            $newBf  = $this->getPreviousTermCum($broadsheet->student_id, $broadsheet->subject_id, $termId, $sessionId);
            $newCum = $termId == 1 ? $newTotal : round(($newBf + $newTotal) / 2, 2);

            $schoolclass = Schoolclass::with('classcategory')->find($broadsheet->schoolclass_id);
            $newGrade = $schoolclass && $schoolclass->classcategory
                ? $schoolclass->classcategory->calculateGrade($newCum)
                : $this->getDefaultGrade($newCum);
            $newRemark = $this->getRemark($newGrade);

            if (abs(($broadsheet->total ?? 0) - $newTotal) > 0.01 ||
                abs(($broadsheet->bf    ?? 0) - $newBf)    > 0.01 ||
                abs(($broadsheet->cum   ?? 0) - $newCum)   > 0.01 ||
                $broadsheet->grade !== $newGrade ||
                $broadsheet->remark !== $newRemark) {

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
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'broadsheetmock.subjectclass_id')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheetmock.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records_mock.session_id')
            ->where('broadsheet_records_mock.session_id', $sessionId)
            ->orderBy('studentRegistration.lastname');

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
            'broadsheetmock.cmin',
            'broadsheetmock.cmax',
            'broadsheetmock.avg',
        ]);

        foreach ($results as $broadsheet) {
            $exam     = $broadsheet->exam ?? 0;
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
    // CLASS METRICS + POSITIONS
    // =========================================================================

    protected function updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid)
    {
        $subjectClass = DB::table('subjectclass')->where('id', $subjectclassid)->first(['subjectteacherid']);
        if (!$subjectClass) return;

        $subjectTeacher = DB::table('subjectteacher')->where('id', $subjectClass->subjectteacherid)->first(['subjectid']);
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
            ->update(['cmin' => $classMin, 'cmax' => $classMax, 'avg' => $classAvg]);
    }

    protected function updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id)
    {
        Log::info('[Admin updateSubjectPositions] START', compact('subjectclass_id', 'term_id', 'session_id'));

        $subjectClass = DB::table('subjectclass')
            ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->where('subjectclass.id', $subjectclass_id)
            ->first(['subjectclass.schoolclassid', 'subjectteacher.subjectid']);

        if (!$subjectClass) return;

        $subjectId     = $subjectClass->subjectid;
        $schoolclassId = $subjectClass->schoolclassid;

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

        if ($allStudents->isEmpty()) {
            Log::warning('[Admin updateSubjectPositions] No students to rank');
            return;
        }

        $this->denseRank($allStudents, 'cum',   'subject_position_class');
        $this->denseRank($allStudents, 'total', 'subject_position_class_total');

        foreach ($allStudents->groupBy('schoolclass_id') as $armClassId => $studentsInArm) {
            $this->denseRank($studentsInArm, 'total', 'arm_position');
            $this->denseRank($studentsInArm, 'cum',   'arm_position_cum');
        }

        Log::info('[Admin updateSubjectPositions] DONE', ['students' => $allStudents->count()]);
    }

    protected function denseRank($rows, string $sortKey, string $column)
    {
        $sorted = $rows->sortByDesc(fn($row) => (float) ($row->$sortKey ?? 0))->values();

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
            ])
            ->first();

        $classMin = $metrics->class_min ?? 0;
        $classMax = $metrics->class_max ?? 0;
        $classAvg = $metrics->student_count > 0 ? round((float) $metrics->total_sum / $metrics->student_count, 1) : 0;

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
        $broadsheets = BroadsheetsMock::query()
            ->where('broadsheetmock.subjectclass_id', $subjectclass_id)
            ->where('broadsheetmock.staff_id', $staff_id)
            ->where('broadsheetmock.term_id', $term_id)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->where('broadsheet_records_mock.session_id', $session_id)
            ->orderByDesc('broadsheetmock.total')
            ->orderBy('broadsheetmock.id')
            ->get(['broadsheetmock.id', 'broadsheetmock.total', 'broadsheetmock.subject_position_class']);

        if ($broadsheets->isEmpty()) return;

        $rank = 0; $lastTotal = null; $lastPosition = 0;

        foreach ($broadsheets as $b) {
            $rank++;
            if ($lastTotal === null || $b->total != $lastTotal) {
                $lastPosition = $rank;
                $lastTotal    = $b->total;
            }
            if ($b->subject_position_class != $lastPosition) {
                BroadsheetsMock::where('id', $b->id)->update(['subject_position_class' => $lastPosition]);
            }
        }
    }

    // =========================================================================
    // GRADE HELPERS
    // =========================================================================

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
     * (it's the direct output of the CA/exam averaging in singleUpdate,
     * bulkUpdate, and getBroadsheets' recalculation loop). 0 is already
     * this app's convention for "not really set" (see the cum != 0
     * eligibility checks in ClassPositionService), so a previous-term cum
     * of exactly 0 is treated as unset and total is used instead.
     */
    protected function getPreviousTermCum($studentId, $subjectId, $termId, $sessionId)
    {
        if ($termId == 1) return 0;

        $prev = DB::table('broadsheets')
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->where('broadsheet_records.student_id', $studentId)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->where('broadsheet_records.session_id', $sessionId)
            ->where('broadsheets.term_id', $termId - 1)
            ->select(['broadsheets.cum', 'broadsheets.total'])
            ->first();

        if (!$prev) {
            return 0;
        }

        $value = ($prev->cum !== null && (float) $prev->cum != 0)
            ? $prev->cum
            : $prev->total;

        return $value !== null ? round((float) $value, 2) : 0;
    }
}