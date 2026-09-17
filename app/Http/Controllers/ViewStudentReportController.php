<?php
// app/Http/Controllers/ViewStudentReportController.php

namespace App\Http\Controllers;

use App\Models\AttendanceSummary;
use App\Models\Broadsheets;
use App\Models\BroadsheetsMock;
use App\Models\CompulsorySubjectClass;
use App\Models\PromotionStatus;
use App\Models\Schoolclass;
use App\Models\SchoolInformation;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Student;
use App\Models\Studentclass;
use App\Models\Studentpersonalityprofile;
use App\Services\ClassPositionService;
use App\Services\PromotionEvaluator;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ViewStudentReportController extends Controller
{
    protected ClassPositionService $positionService;
    protected PromotionEvaluator  $promotionEvaluator;

    public function __construct(
        ClassPositionService $positionService,
        PromotionEvaluator   $promotionEvaluator
    ) {
        $this->positionService    = $positionService;
        $this->promotionEvaluator = $promotionEvaluator;

        $this->middleware('permission:View student-report', ['only' => [
            'index', 'studentresult', 'studentmockresult',
            'exportStudentResultPdf', 'exportClassResultsPdf', 'drawerData',
        ]]);
        $this->middleware('permission:Create student-report', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update student-report', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete student-report', ['only' => ['destroy']]);
    }

    // =========================================================================
    // FORMAT HELPERS
    // =========================================================================

    protected function formatOrdinal($number)
    {
        if (!is_numeric($number) || $number <= 0) return '-';

        $lastDigit     = $number % 10;
        $lastTwoDigits = $number % 100;

        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 13) return $number . 'th';

        return $number . match ($lastDigit) {
            1       => 'st',
            2       => 'nd',
            3       => 'rd',
            default => 'th',
        };
    }

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

    protected function calculateGrade($score)
    {
        return $this->calculateSeniorGrade($score);
    }

    protected function getGradePoint($score)
    {
        if ($score === null || $score == 0) return 0.0;
        if ($score >= 75) return 5.0;
        if ($score >= 70) return 4.5;
        if ($score >= 65) return 4.0;
        if ($score >= 60) return 3.5;
        if ($score >= 55) return 3.0;
        if ($score >= 50) return 2.5;
        if ($score >= 45) return 2.0;
        if ($score >= 40) return 1.0;
        return 0.0;
    }

    protected function getRemark($grade)
    {
        return match ($grade) {
            'A1', 'A'              => 'Excellent',
            'B2', 'B3', 'B'        => 'Very Good',
            'C4', 'C5', 'C6', 'C'  => 'Good',
            'D7', 'D'              => 'Pass',
            'E8'                   => 'Pass',
            'F9', 'F'              => 'Fail',
            default                => 'Unknown',
        };
    }

    protected function getGpaGrade($gpa)
    {
        if ($gpa >= 4.5) return 'A1';
        if ($gpa >= 4.0) return 'B2';
        if ($gpa >= 3.5) return 'B3';
        if ($gpa >= 3.0) return 'C4';
        if ($gpa >= 2.5) return 'C5';
        if ($gpa >= 2.0) return 'C6';
        if ($gpa >= 1.5) return 'D7';
        if ($gpa >= 1.0) return 'E8';
        return 'F9';
    }

    // =========================================================================
    // GPA / CGPA
    // =========================================================================

    protected function computeOverallGPAAndCGPAForStudent($studentId, $schoolclass, $termId, $sessionId)
    {
        $classIds = Schoolclass::where('schoolclass', $schoolclass->schoolclass)->pluck('id')->toArray();

        $currentTermBroadsheets = Broadsheets::where('broadsheets.term_id', $termId)
            ->whereHas('broadsheetRecord', function ($q) use ($studentId, $sessionId) {
                $q->where('student_id', $studentId)->where('session_id', $sessionId);
            })
            ->whereExists(function ($query) use ($studentId, $termId, $sessionId, $classIds) {
                $query->select(DB::raw(1))
                    ->from('subjectRegistrationStatus')
                    ->join('subjectclass', 'subjectclass.id', '=', 'subjectRegistrationStatus.subjectclassid')
                    ->join('broadsheet_records as br_inner', 'br_inner.subject_id', '=', 'subjectclass.subjectid')
                    ->whereColumn('br_inner.id', 'broadsheets.broadsheet_record_id')
                    ->whereIn('subjectclass.schoolclassid', $classIds)
                    ->where('subjectRegistrationStatus.studentid', $studentId)
                    ->where('subjectRegistrationStatus.termid', $termId)
                    ->where('subjectRegistrationStatus.sessionid', $sessionId);
            })
            ->get(['broadsheets.cum']);

        $termGradePoints    = $currentTermBroadsheets->map(fn ($b) => $this->getGradePoint(round($b->cum ?? 0)));
        $gpa                = $termGradePoints->avg() ?? 0.0;
        $num_subjects       = $currentTermBroadsheets->count();
        $total_grade_points = $termGradePoints->sum();

        $termGPAs = [];
        for ($t = 1; $t <= $termId; $t++) {
            $termBroadsheets = Broadsheets::where('broadsheets.term_id', $t)
                ->whereHas('broadsheetRecord', function ($q) use ($studentId, $sessionId) {
                    $q->where('student_id', $studentId)->where('session_id', $sessionId);
                })
                ->whereExists(function ($query) use ($studentId, $t, $sessionId, $classIds) {
                    $query->select(DB::raw(1))
                        ->from('subjectRegistrationStatus')
                        ->join('subjectclass', 'subjectclass.id', '=', 'subjectRegistrationStatus.subjectclassid')
                        ->join('broadsheet_records as br_inner', 'br_inner.subject_id', '=', 'subjectclass.subjectid')
                        ->whereColumn('br_inner.id', 'broadsheets.broadsheet_record_id')
                        ->whereIn('subjectclass.schoolclassid', $classIds)
                        ->where('subjectRegistrationStatus.studentid', $studentId)
                        ->where('subjectRegistrationStatus.termid', $t)
                        ->where('subjectRegistrationStatus.sessionid', $sessionId);
                })
                ->get(['broadsheets.cum']);

            if ($termBroadsheets->isNotEmpty()) {
                $gp   = $termBroadsheets->map(fn ($b) => $this->getGradePoint(round($b->cum ?? 0)));
                $tGPA = $gp->avg() ?? 0.0;
                if ($tGPA > 0) $termGPAs[] = $tGPA;
            }
        }

        $cgpa     = !empty($termGPAs) ? collect($termGPAs)->avg() : 0.0;
        $gpaGrade = $this->getGpaGrade($gpa);

        return [
            'gpa'                => round($gpa, 2),
            'cgpa'               => round($cgpa, 2),
            'gpa_grade'          => $gpaGrade,
            'num_subjects'       => $num_subjects,
            'total_grade_points' => round($total_grade_points, 1),
            'calculated_gpa'     => $num_subjects > 0 ? round($total_grade_points / $num_subjects, 2) : 0.0,
        ];
    }

    // =========================================================================
    // CLASS POSITIONS
    // =========================================================================

    protected function calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid)
    {
        return $this->positionService->recalculate($schoolclassid, $sessionid, $termid);
    }

    // =========================================================================
    // ATTENDANCE SUMMARY
    // =========================================================================

    protected function getAttendanceSummary($studentId, $schoolclassId, $termId, $sessionId): array
    {
        try {
            $record = AttendanceSummary::where('student_id', $studentId)
                ->where('schoolclass_id', $schoolclassId)
                ->where('term_id', $termId)
                ->where('session_id', $sessionId)
                ->first();

            if ($record) {
                return [
                    'total_school_days'     => $record->total_school_days     ?? 0,
                    'days_present'          => $record->days_present          ?? 0,
                    'days_absent'           => $record->days_absent           ?? 0,
                    'days_sick_leave'       => $record->days_sick_leave       ?? 0,
                    'days_excused'          => $record->days_excused          ?? 0,
                    'days_late'             => $record->days_late             ?? 0,
                    'attendance_percentage' => $record->attendance_percentage ?? 0.0,
                    'found'                 => true,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error fetching attendance summary', [
                'student_id' => $studentId,
                'error'      => $e->getMessage(),
            ]);
        }

        return [
            'total_school_days'     => 0,
            'days_present'          => 0,
            'days_absent'           => 0,
            'days_sick_leave'       => 0,
            'days_excused'          => 0,
            'days_late'             => 0,
            'attendance_percentage' => 0.0,
            'found'                 => false,
        ];
    }

    // =========================================================================
    // GET STUDENT RESULT DATA
    // =========================================================================

    private function getStudentResultData($id, $schoolclassid, $sessionid, $termid)
    {
        try {
            if (!is_numeric($id) || !is_numeric($schoolclassid) || !is_numeric($sessionid) || !is_numeric($termid)) {
                Log::error('Invalid parameters in getStudentResultData', compact('id', 'schoolclassid', 'sessionid', 'termid'));
                return [];
            }

            // ── Student record ──────────────────────────────────────────────
            $students = Student::where('studentRegistration.id', $id)
                ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->select([
                    'studentRegistration.id           as id',
                    'studentRegistration.admissionNo  as admissionNo',
                    'studentRegistration.firstname    as fname',
                    'studentRegistration.lastname     as lastname',
                    'studentRegistration.othername    as othername',
                    'studentRegistration.dateofbirth  as dateofbirth',
                    'studentRegistration.gender       as gender',
                    'studentRegistration.home_address2 as present_address',
                    'studentRegistration.home_address2 as permanent_address',
                    'studentRegistration.updated_at   as updated_at',
                    'studentpicture.picture           as picture',
                ])
                ->orderBy('studentRegistration.lastname', 'asc')
                ->get();

            if ($students->isEmpty()) $students = collect([]);

            // ── Class + senior/junior detection ─────────────────────────────
            $schoolclass = Schoolclass::with(['arms', 'classcategories'])->find($schoolclassid);
            $isSenior    = $schoolclass && $schoolclass->classcategories->isNotEmpty()
                ? (bool) ($schoolclass->classcategories->first()->is_senior ?? false)
                : false;

            // ── Fetch broadsheets ───────────────────────────────────────────
            $scores = Broadsheets::where('broadsheet_records.student_id', $id)
                ->where('broadsheets.term_id',               $termid)
                ->where('broadsheet_records.session_id',     $sessionid)
                ->where('broadsheet_records.schoolclass_id', $schoolclassid)
                ->whereExists(function ($query) use ($id, $termid, $sessionid, $schoolclassid) {
                    $query->select(DB::raw(1))
                        ->from('subjectRegistrationStatus')
                        ->join('subjectclass as sjc_reg', 'sjc_reg.id', '=', 'subjectRegistrationStatus.subjectclassid')
                        ->join('subjectteacher as st_reg', 'st_reg.id', '=', 'sjc_reg.subjectteacherid')
                        ->whereColumn('st_reg.subjectid', 'broadsheet_records.subject_id')
                        ->where('subjectRegistrationStatus.studentid', $id)
                        ->where('subjectRegistrationStatus.termid',    $termid)
                        ->where('subjectRegistrationStatus.sessionid', $sessionid)
                        ->where('sjc_reg.schoolclassid', $schoolclassid);
                })
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->join('subject',            'subject.id',             '=', 'broadsheet_records.subject_id')
                ->orderBy('subject.subject')
                ->select([
                    'subject.id           as subject_id',
                    'subject.subject      as subject_name',
                    'subject.subject_code as subject_code',
                    'broadsheets.ca1',
                    'broadsheets.ca2',
                    'broadsheets.ca3',
                    'broadsheets.exam',
                    'broadsheets.total',
                    'broadsheets.bf',
                    'broadsheets.cum',
                    'broadsheets.grade',
                    'broadsheets.remark',
                    'broadsheets.subject_position_class       as position',
                    'broadsheets.subject_position_class_total as position_total',
                    'broadsheets.arm_position                 as arm_position',
                    'broadsheets.arm_position_cum             as arm_position_cum',
                    'broadsheets.avg                          as class_average',
                    'broadsheets.id                           as broadsheet_id',
                    'broadsheets.vettedstatus',
                ])->get();

            // ── Format positions ────────────────────────────────────────────
            foreach ($scores as $score) {
                $score->position_formatted         = ($score->position      && $score->position      > 0) ? $this->formatOrdinal($score->position)      : '-';
                $score->position_total_formatted   = ($score->position_total && $score->position_total > 0) ? $this->formatOrdinal($score->position_total) : '-';
                $score->arm_position_formatted     = ($score->arm_position  && $score->arm_position  > 0) ? $this->formatOrdinal($score->arm_position)  : '-';
                $score->arm_position_cum_formatted = ($score->arm_position_cum && $score->arm_position_cum > 0) ? $this->formatOrdinal($score->arm_position_cum) : '-';
            }

            // ── Totals summary ──────────────────────────────────────────────
            $totalObtained   = 0;
            $totalObtainable = 0;

            foreach ($scores as $score) {
                if ($score->total !== null && is_numeric($score->total)) {
                    $totalObtained += (float) $score->total;
                }
                $totalObtainable += 100;
            }

            $totalPercentage = $totalObtainable > 0
                ? round(($totalObtained / $totalObtainable) * 100, 1)
                : 0;

            $totalsSummary = [
                'obtained'   => round($totalObtained, 1),
                'obtainable' => $totalObtainable,
                'percentage' => $totalPercentage,
            ];

            // ── GPA / CGPA ──────────────────────────────────────────────────
            $gpaData = [];
            if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
                try {
                    $gpaData = $this->computeOverallGPAAndCGPAForStudent($id, $schoolclass, $termid, $sessionid);
                } catch (\Exception $e) {
                    Log::error('GPA calc error', ['student_id' => $id, 'error' => $e->getMessage()]);
                    $gpaData = [
                        'gpa' => 0.0, 'cgpa' => 0.0, 'gpa_grade' => 'F9',
                        'num_subjects' => 0, 'total_grade_points' => 0, 'calculated_gpa' => 0.0,
                    ];
                }
            }

            // ── Compulsory subjects ─────────────────────────────────────────
            $compulsorySubjects = [];
            try {
                $compulsorySubjects = CompulsorySubjectClass::where('schoolclassid', $schoolclassid)
                    ->pluck('subjectId')
                    ->toArray();
            } catch (\Exception $e) {
                Log::error('Compulsory subjects error', ['error' => $e->getMessage()]);
            }

            foreach ($scores as $score) {
                $score->is_compulsory = in_array($score->subject_id, $compulsorySubjects);
            }

            // ── PROMOTION EVALUATION ────────────────────────────────────────
            $promotionResult = $this->promotionEvaluator->evaluate(
                studentId:      $id,
                schoolclassid:  $schoolclassid,
                termid:         $termid,
                sessionid:      $sessionid,
                scores:         $scores,
                overallAverage: $totalsSummary['percentage']
            );

            // ── Persist to PromotionStatus ──────────────────────────────────
            try {
                $existing = PromotionStatus::where('studentId',     $id)
                    ->where('schoolclassid', $schoolclassid)
                    ->where('sessionid',     $sessionid)
                    ->where('termid',        $termid)
                    ->first();

                $canPersist = !$existing || $existing->rule_applied !== null;

                if ($canPersist && $promotionResult['status'] !== 'awaiting') {
                    $this->promotionEvaluator->persistResult(
                        $id, $schoolclassid, $sessionid, $termid, $promotionResult
                    );
                }
            } catch (\Exception $e) {
                Log::error('PromotionStatus persist error', ['student_id' => $id, 'error' => $e->getMessage()]);
            }

            // ── Personality profile ─────────────────────────────────────────
            try {
                $studentpp = Studentpersonalityprofile::where('studentpersonalityprofiles.studentid',    $id)
                    ->where('studentpersonalityprofiles.termid',        $termid)
                    ->where('studentpersonalityprofiles.sessionid',     $sessionid)
                    ->where('studentpersonalityprofiles.schoolclassid', $schoolclassid)
                    ->join('schoolsession', 'schoolsession.id', '=', 'studentpersonalityprofiles.sessionid')
                    ->join('schoolterm',    'schoolterm.id',    '=', 'studentpersonalityprofiles.termid')
                    ->join('schoolclass',   'schoolclass.id',   '=', 'studentpersonalityprofiles.schoolclassid')
                    ->select(
                        'studentpersonalityprofiles.*',
                        'schoolsession.session as session',
                        'schoolterm.term       as term',
                        'schoolclass.schoolclass as schoolclass'
                    )
                    ->get();

                if ($studentpp->isEmpty()) $studentpp = collect();
            } catch (\Exception $e) {
                Log::error('Personality profile error', ['student_id' => $id, 'error' => $e->getMessage()]);
                $studentpp = collect();
            }

            // ── Meta ────────────────────────────────────────────────────────
            $schoolsession    = Schoolsession::where('id', $sessionid)->first();
            $schoolterm       = Schoolterm::where('id', $termid)->first();
            $numberOfStudents = Studentclass::where('schoolclassid', $schoolclassid)
                ->where('sessionid', $sessionid)
                ->count();

            $schoolInfo = SchoolInformation::first();
            if (!$schoolInfo) {
                $schoolInfo = new \stdClass();
                $schoolInfo->id                    = 0;
                $schoolInfo->school_name           = 'School Name Not Found';
                $schoolInfo->school_logo           = null;
                $schoolInfo->school_stamp          = null;
                $schoolInfo->school_motto          = 'Motto Not Found';
                $schoolInfo->school_address        = 'Address Not Found';
                $schoolInfo->school_phone          = 'Phone Not Found';
                $schoolInfo->date_school_opened    = null;
                $schoolInfo->date_next_term_begins = null;
            } else {
                $schoolInfo->school_stamp = $schoolInfo->school_stamp ?? null;
            }

            // ── Attendance ──────────────────────────────────────────────────
            $attendanceSummary = $this->getAttendanceSummary($id, $schoolclassid, $termid, $sessionid);

            return [
                'students'             => $students,
                'studentpp'            => $studentpp,
                'scores'               => $scores,
                'studentid'            => $id,
                'schoolclassid'        => $schoolclassid,
                'sessionid'            => $sessionid,
                'termid'               => $termid,
                'schoolclass'          => $schoolclass,
                'schoolterm'           => $schoolterm,
                'schoolsession'        => $schoolsession,
                'numberOfStudents'     => $numberOfStudents,
                'schoolInfo'           => $schoolInfo,
                'promotionStatusValue' => $promotionResult['status']       ?? null,
                'promotion_label'      => $promotionResult['status_label'] ?? null,
                'compulsorySubjects'   => $compulsorySubjects,
                'gpa_data'             => $gpaData,
                'totals_summary'       => $totalsSummary,
                'attendance_summary'   => $attendanceSummary,
                'promotion_result'     => $promotionResult,
            ];

        } catch (Exception $e) {
            Log::error('getStudentResultData error', [
                'student_id' => $id,
                'error'      => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);
            return [];
        }
    }

    // =========================================================================
    // VIEWS
    // =========================================================================

    public function studentresult($id, $schoolclassid, $sessionid, $termid)
    {
        $pagetitle = 'Student Personality Profile';

        $metricsCalculated = $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);
        if (!$metricsCalculated) {
            return back()->with('error', 'Failed to calculate class metrics. Please try again.');
        }

        $data = $this->getStudentResultData($id, $schoolclassid, $sessionid, $termid);

        return view('studentreports.studentresult')->with($data)->with('pagetitle', $pagetitle);
    }

    public function studentmockresult($id, $schoolclassid, $sessionid, $termid)
    {
        $pagetitle = 'Student Mock Result';

        $metricsCalculated = $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);
        if (!$metricsCalculated) {
            return back()->with('error', 'Failed to calculate class metrics. Please try again.');
        }

        $data = $this->getStudentResultData($id, $schoolclassid, $sessionid, $termid);

        return view('studentreports.studentmockresult')->with($data)->with('pagetitle', $pagetitle);
    }

    public function classBroadsheet($schoolclassid, $sessionid, $termid): View
    {
        $class     = Schoolclass::findOrFail($schoolclassid);
        $session   = Schoolsession::findOrFail($sessionid);
        $pagetitle = "Broadsheet for {$class->schoolclass} - {$session->session} - Term {$termid}";

        return view('studentreports.broadsheet', [
            'class'     => $class,
            'session'   => $session,
            'term'      => $termid,
            'pagetitle' => $pagetitle,
        ]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

      // =========================================================================
    // INDEX — with debug logging
    // =========================================================================

    public function index(Request $request): View|JsonResponse
    {
        // ═══════════════════════════════════════════════════════════════════
        // DEBUG: Log every incoming request with full context
        // ═══════════════════════════════════════════════════════════════════
        Log::info('[studentreports.index] Request received', [
            'is_ajax'         => $request->ajax(),
            'wants_json'      => $request->wantsJson(),
            'method'          => $request->method(),
            'url'             => $request->fullUrl(),
            'ip'              => $request->ip(),
            'user_id'         => optional($request->user())->id,
            'user_email'      => optional($request->user())->email,
            'user_roles'      => $request->user() ? $request->user()->getRoleNames()->toArray() : [],
            'user_perms'      => $request->user() ? $request->user()->getAllPermissions()->pluck('name')->toArray() : [],
            'can_view_report' => $request->user() ? $request->user()->can('View student-report') : false,
            'all_input'       => $request->all(),
            'schoolclassid'   => $request->input('schoolclassid'),
            'sessionid'       => $request->input('sessionid'),
            'termid'          => $request->input('termid'),
            'search'          => $request->input('search'),
            'has_csrf_header' => $request->hasHeader('X-CSRF-TOKEN'),
            'csrf_header'     => $request->header('X-CSRF-TOKEN'),
            'x_requested_with' => $request->header('X-Requested-With'),
            'session_id'      => $request->session()->getId(),
            'session_token'   => $request->session()->token(),
        ]);

        $pagetitle   = 'Student Terminal Report Management';
        $allstudents = new LengthAwarePaginator([], 0, 10);

        // ═══════════════════════════════════════════════════════════════════
        // DEBUG: Branch — do we have valid filter parameters?
        // ═══════════════════════════════════════════════════════════════════
        $hasSchoolClass = $request->filled('schoolclassid');
        $hasSession     = $request->filled('sessionid');
        $isAllClass     = $request->input('schoolclassid') === 'ALL';
        $isAllSession   = $request->input('sessionid')     === 'ALL';

        Log::info('[studentreports.index] Filter param check', [
            'has_schoolclassid' => $hasSchoolClass,
            'has_sessionid'     => $hasSession,
            'schoolclassid_val' => $request->input('schoolclassid'),
            'sessionid_val'     => $request->input('sessionid'),
            'is_all_class'      => $isAllClass,
            'is_all_session'    => $isAllSession,
            'will_query'        => $hasSchoolClass && $hasSession && !$isAllClass && !$isAllSession,
        ]);

        if ($hasSchoolClass && $hasSession && !$isAllClass && !$isAllSession) {
            try {
                Log::info('[studentreports.index] Building student query', [
                    'schoolclassid' => $request->input('schoolclassid'),
                    'sessionid'     => $request->input('sessionid'),
                ]);

                $query = Studentclass::query()
                    ->where('schoolclassid', $request->input('schoolclassid'))
                    ->where('sessionid',     $request->input('sessionid'))
                    ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
                    ->leftJoin('studentpicture',      'studentpicture.studentid', '=', 'studentRegistration.id')
                    ->leftJoin('schoolclass',         'schoolclass.id',           '=', 'studentclass.schoolclassid')
                    ->leftJoin('schoolarm',           'schoolarm.id',             '=', 'schoolclass.arm')
                    ->leftJoin('schoolsession',       'schoolsession.id',         '=', 'studentclass.sessionid');
                    // NOTE: no ->where('schoolsession.status', ...) — column doesn't exist

                if ($search = $request->input('search')) {
                    Log::info('[studentreports.index] Adding search filter', ['search' => $search]);

                    $query->where(function ($q) use ($search) {
                        $q->where('studentRegistration.admissionNo', 'like', "%{$search}%")
                          ->orWhere('studentRegistration.firstname',  'like', "%{$search}%")
                          ->orWhere('studentRegistration.lastname',   'like', "%{$search}%")
                          ->orWhere('studentRegistration.othername',  'like', "%{$search}%");
                    });
                }

                Log::debug('[studentreports.index] SQL before pagination', [
                    'sql'      => $query->toSql(),
                    'bindings' => $query->getBindings(),
                ]);

                $allstudents = $query->select([
                    'studentRegistration.admissionNo as admissionno',
                    'studentRegistration.firstname   as firstname',
                    'studentRegistration.lastname    as lastname',
                    'studentRegistration.othername   as othername',
                    'studentRegistration.gender      as gender',
                    'studentRegistration.id          as stid',
                    'studentpicture.picture          as picture',
                    'studentclass.schoolclassid      as schoolclassID',
                    'studentclass.sessionid          as sessionid',
                    'schoolclass.schoolclass         as schoolclass',
                    'schoolarm.arm                   as schoolarm',
                    'schoolsession.session           as session',
                ])->latest('studentclass.created_at')->paginate(100);

                Log::info('[studentreports.index] Query succeeded', [
                    'total_students'  => $allstudents->total(),
                    'per_page'        => $allstudents->perPage(),
                    'current_page'    => $allstudents->currentPage(),
                    'count_on_page'   => $allstudents->count(),
                    'first_student'   => $allstudents->first() ? [
                        'id'          => $allstudents->first()->stid,
                        'admissionno' => $allstudents->first()->admissionno,
                        'name'        => $allstudents->first()->firstname . ' ' . $allstudents->first()->lastname,
                    ] : null,
                ]);
            } catch (\Throwable $e) {
                Log::error('[studentreports.index] Query FAILED', [
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                    'trace'   => $e->getTraceAsString(),
                ]);

                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Query error: ' . $e->getMessage(),
                        'file'    => basename($e->getFile()) . ':' . $e->getLine(),
                    ], 500);
                }

                return back()->with('error', 'Failed to load students: ' . $e->getMessage());
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // DEBUG: Loading sessions + classes
        // ═══════════════════════════════════════════════════════════════════
        try {
            $schoolsessions = Schoolsession::get();
            Log::info('[studentreports.index] Loaded sessions', [
                'count'    => $schoolsessions->count(),
                'sessions' => $schoolsessions->pluck('session', 'id')->toArray(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[studentreports.index] Failed to load sessions', [
                'message' => $e->getMessage(),
            ]);
            $schoolsessions = collect();
        }

        try {
            $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->get(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm']);
            Log::info('[studentreports.index] Loaded classes', [
                'count'   => $schoolclasses->count(),
                'classes' => $schoolclasses->pluck('schoolclass', 'id')->toArray(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[studentreports.index] Failed to load classes', [
                'message' => $e->getMessage(),
            ]);
            $schoolclasses = collect();
        }

        // ═══════════════════════════════════════════════════════════════════
        // DEBUG: AJAX branch — build JSON response
        // ═══════════════════════════════════════════════════════════════════
        if ($request->ajax()) {
            try {
                Log::info('[studentreports.index] AJAX — rendering partial', [
                    'total' => $allstudents->total(),
                ]);

                $tableBody = view('studentreports.partials.student_rows', compact('allstudents'))->render();

                Log::info('[studentreports.index] Partial rendered', [
                    'html_length' => strlen($tableBody),
                    'html_preview' => substr($tableBody, 0, 200),
                ]);

                $pagination = $allstudents->links('pagination::bootstrap-5')->render();

                Log::info('[studentreports.index] Pagination rendered', [
                    'html_length' => strlen($pagination),
                ]);

                return response()->json([
                    'tableBody'    => $tableBody,
                    'pagination'   => $pagination,
                    'studentCount' => $allstudents->total(),
                ]);
            } catch (\Throwable $e) {
                Log::error('[studentreports.index] AJAX render FAILED', [
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                    'trace'   => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Render error: ' . $e->getMessage(),
                    'file'    => basename($e->getFile()) . ':' . $e->getLine(),
                ], 500);
            }
        }

        // ═══════════════════════════════════════════════════════════════════
        // DEBUG: Standard HTML page render
        // ═══════════════════════════════════════════════════════════════════
        Log::info('[studentreports.index] Rendering full HTML view');

        return view('studentreports.index', compact('allstudents', 'schoolsessions', 'schoolclasses', 'pagetitle'));
    }
    // =========================================================================
    // DRAWER DATA
    // =========================================================================

    public function drawerData($studentId, $schoolclassId, $sessionId, $termId)
    {
        try {
            if (!is_numeric($studentId) || !is_numeric($schoolclassId) || !is_numeric($sessionId) || !is_numeric($termId)) {
                return response()->json(['success' => false, 'message' => 'Invalid parameters'], 400);
            }

            $resultData = $this->getStudentResultData($studentId, $schoolclassId, $sessionId, $termId);

            if (empty($resultData)) {
                return response()->json(['success' => false, 'message' => 'No data found'], 404);
            }

            $profile = null;
            try {
                $profile = Studentpersonalityprofile::where('studentid',    $studentId)
                    ->where('termid',        $termId)
                    ->where('sessionid',     $sessionId)
                    ->where('schoolclassid', $schoolclassId)
                    ->first();
            } catch (\Exception $e) {
                Log::error('drawerData profile error', ['error' => $e->getMessage()]);
            }

            $student       = $resultData['students']->first();
            $schoolclass   = $resultData['schoolclass'];
            $schoolterm    = $resultData['schoolterm'];
            $schoolsession = $resultData['schoolsession'];

            $fullName = trim(
                strtoupper($student->lastname ?? '') . ' ' .
                ($student->fname ?? '') . ' ' .
                ($student->othername ?? '')
            );

            $scores = ($resultData['scores'] ?? collect())->map(function ($score) {
                return [
                    'subject_name'      => $score->subject_name,
                    'subject_code'      => $score->subject_code,
                    'ca1'               => $score->ca1  !== null ? (float) $score->ca1  : null,
                    'ca2'               => $score->ca2  !== null ? (float) $score->ca2  : null,
                    'ca3'               => $score->ca3  !== null ? (float) $score->ca3  : null,
                    'exam'              => $score->exam !== null ? (float) $score->exam : null,
                    'total'             => $score->total !== null ? (float) $score->total : null,
                    'bf'                => $score->bf    !== null ? (float) $score->bf    : null,
                    'cum'               => $score->cum    !== null ? (float) $score->cum    : null,
                    'grade'             => $score->grade,
                    'remark'            => $score->remark,
                    'position'          => $score->position_formatted         ?? '-',
                    'position_total'    => $score->position_total_formatted   ?? '-',
                    'arm_position'      => $score->arm_position_formatted     ?? '-',
                    'arm_position_cum'  => $score->arm_position_cum_formatted ?? '-',
                    'class_average'     => $score->class_average !== null ? (float) $score->class_average : null,
                    'is_compulsory'     => $score->is_compulsory ?? false,
                    'vettedstatus'      => $score->vettedstatus,
                ];
            })->values()->toArray();

            $pictureUrl = null;
            if ($student && $student->picture) {
                $pictureUrl = asset('storage/student_avatars/' . basename($student->picture));
            }

            $attendance = $resultData['attendance_summary'] ?? [];
            if ($schoolterm) $attendance['term_name'] = $schoolterm->term ?? null;

            return response()->json([
                'success'          => true,
                'student_name'     => $fullName,
                'admissionno'      => $student->admissionNo ?? '—',
                'gender'           => $student->gender      ?? '—',
                'schoolclass'      => trim(($schoolclass->schoolclass ?? '') . ' ' . ($schoolclass->arms->arm ?? '')),
                'term'             => $schoolterm->term       ?? '—',
                'session'          => $schoolsession->session ?? '—',
                'studentid'        => $studentId,
                'schoolclassid'    => $schoolclassId,
                'termid'           => $termId,
                'sessionid'        => $sessionId,
                'picture_url'      => $pictureUrl,
                'scores'           => $scores,
                'mock_scores'      => $this->fetchMockScoresForDrawer($studentId, $schoolclassId, $sessionId, $termId),
                'profile'          => $profile ? $profile->toArray() : null,
                'attendance'       => $attendance,
                'gpa_data'         => $resultData['gpa_data']         ?? [],
                'totals_summary'   => $resultData['totals_summary']   ?? [],
                'promotion_result' => $resultData['promotion_result'] ?? [],
            ]);

        } catch (\Exception $e) {
            Log::error('drawerData error', ['student_id' => $studentId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // MOCK SCORES
    // =========================================================================

    private function fetchMockScoresForDrawer($studentId, $schoolclassId, $sessionId, $termId): array
    {
        try {
            $rows = BroadsheetsMock::where('broadsheet_records_mock.student_id', $studentId)
                ->where('broadsheetmock.term_id', $termId)
                ->where('broadsheet_records_mock.session_id', $sessionId)
                ->where('broadsheet_records_mock.schoolclass_id', $schoolclassId)
                ->join('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
                ->join('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
                ->orderBy('subject.subject')
                ->select([
                    'subject.subject      as subject_name',
                    'subject.subject_code as subject_code',
                    'broadsheetmock.exam',
                    'broadsheetmock.total',
                    'broadsheetmock.grade',
                    'broadsheetmock.remark',
                    'broadsheetmock.subject_position_class as position',
                    'broadsheetmock.avg as class_average',
                    'broadsheetmock.cmin',
                    'broadsheetmock.cmax',
                ])
                ->get();

            return $rows->map(fn ($r) => [
                'subject_name'  => $r->subject_name,
                'subject_code'  => $r->subject_code,
                'exam'          => $r->exam  !== null ? (float) $r->exam  : null,
                'total'         => $r->total !== null ? (float) $r->total : null,
                'grade'         => $r->grade,
                'remark'        => $r->remark,
                'position'      => $r->position,
                'class_average' => $r->class_average !== null ? (float) $r->class_average : null,
                'cmin'          => $r->cmin !== null ? (float) $r->cmin : null,
                'cmax'          => $r->cmax !== null ? (float) $r->cmax : null,
            ])->values()->toArray();

        } catch (\Exception $e) {
            Log::error('fetchMockScoresForDrawer error', ['student_id' => $studentId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    // =========================================================================
    // PDF EXPORTS
    // =========================================================================

    public function exportStudentResultPdf($id, $schoolclassid, $sessionid, $termid)
    {
        try {
            ini_set('max_execution_time', 600);
            ini_set('memory_limit', '1024M');

            $metricsCalculated = $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);
            if (!$metricsCalculated) {
                return back()->with('error', 'Failed to calculate class metrics. Please try again.');
            }

            $data = $this->getStudentResultData($id, $schoolclassid, $sessionid, $termid);

            if (empty($data) || empty($data['students']) || $data['students']->isEmpty()) {
                return back()->with('error', 'No student data found.');
            }

            $this->fixImagePaths([$data]);

            $student     = $data['students']->first();
            $studentName = $student ? $student->fname . '_' . $student->lastname : 'Student';
            $filename    = 'Terminal_Report_' . $studentName . '_'
                . ($data['schoolsession']->session ?? '') . '_Term_' . $data['termid'] . '.pdf';

            $pdf = Pdf::loadView('studentreports.studentresult_pdf', ['data' => $data])
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'dpi'                     => 150,
                    'defaultFont'             => 'DejaVu Sans',
                    'isRemoteEnabled'         => true,
                    'isHtml5ParserEnabled'    => true,
                    'isFontSubsettingEnabled' => true,
                    'isPhpEnabled'            => false,
                    'chroot'                  => [public_path(), storage_path()],
                    'fontCache'               => storage_path('fonts/'),
                    'logOutputFile'           => storage_path('logs/dompdf.log'),
                ]);

            return $pdf->download($filename);

        } catch (Exception $e) {
            Log::error('Single Student PDF Export Error', [
                'student_id' => $id,
                'error'      => $e->getMessage(),
            ]);
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    public function exportClassResultsPdf(Request $request)
    {
        try {
            ini_set('max_execution_time', 300);
            ini_set('memory_limit', '512M');

            $schoolclassid   = $request->input('schoolclassid');
            $sessionid       = $request->input('sessionid');
            $termid          = $request->input('termid', 3);
            $studentIds      = $request->input('studentIds', []);
            $selectedColumns = $request->input('selectedColumns', []);
            $gradeBasis      = $request->input('grade_basis', 'total');
            if (!in_array($gradeBasis, ['total', 'cum'], true)) {
                $gradeBasis = 'total';
            }

            if (!$schoolclassid || !$sessionid || !$termid) {
                return response()->json(['success' => false, 'message' => 'Missing required parameters'], 400);
            }

            $metricsCalculated = $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);
            if (!$metricsCalculated) {
                return response()->json(['success' => false, 'message' => 'Failed to calculate class metrics.'], 500);
            }

            $allStudentData = [];
            $failedCount    = 0;

            foreach ($studentIds as $studentId) {
                $studentData = $this->getStudentResultData($studentId, $schoolclassid, $sessionid, $termid);

                if (!empty($studentData) && !empty($studentData['students']) && $studentData['students']->isNotEmpty()) {
                    $studentData['selected_columns'] = $selectedColumns;
                    $allStudentData[]                = $studentData;
                } else {
                    $failedCount++;
                    Log::warning('Skipped student due to empty data', ['student_id' => $studentId]);
                }
            }

            if (empty($allStudentData)) {
                return response()->json(['success' => false, 'message' => 'Failed to process student data.'], 500);
            }

            $this->fixImagePaths($allStudentData);

            $schoolclass   = Schoolclass::where('id', $schoolclassid)->with(['arms', 'classcategories'])->first(['id', 'schoolclass', 'arm']);
            $schoolsession = Schoolsession::where('id', $sessionid)->value('session') ?? 'N/A';
            $term          = $this->getTermName($termid);
            $className     = $schoolclass
                ? ($schoolclass->schoolclass . ($schoolclass->arms ? $schoolclass->arms->arm : ''))
                : 'Class';

            $filename = 'Class_Results_'
                . preg_replace('/[^A-Za-z0-9_-]/', '_', $className) . '_'
                . preg_replace('/[^A-Za-z0-9_-]/', '_', $schoolsession) . '_'
                . $term . '.pdf';

            $viewName = 'studentreports.class_results_pdf';
            if (!view()->exists($viewName)) {
                return response()->json(['success' => false, 'message' => 'PDF template not found'], 500);
            }

            $viewData = [
                'allStudentData' => $allStudentData,
                'metadata' => [
                    'class_name'       => $className,
                    'session'          => $schoolsession,
                    'term'             => $term,
                    'generation_date'  => now()->format('Y-m-d H:i:s'),
                    'student_count'    => count($allStudentData),
                    'selected_columns' => $selectedColumns,
                    'grade_basis'      => $gradeBasis,
                ],
            ];

            $pdf = Pdf::loadView($viewName, $viewData)
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'dpi'                     => 96,
                    'defaultFont'             => 'DejaVu Sans',
                    'isRemoteEnabled'         => true,
                    'isHtml5ParserEnabled'    => true,
                    'isFontSubsettingEnabled' => true,
                    'isPhpEnabled'            => false,
                    'chroot'                  => [public_path(), storage_path()],
                    'tempDir'                 => storage_path('app/temp/'),
                    'fontCache'               => storage_path('fonts/'),
                    'logOutputFile'           => storage_path('logs/dompdf.log'),
                ]);

            $pdfContent = $pdf->output();

            if (empty($pdfContent)) {
                return response()->json(['success' => false, 'message' => 'Generated PDF content is empty'], 500);
            }

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));

        } catch (Exception $e) {
            Log::error('Class PDF Export Error', [
                'error_message' => $e->getMessage(),
                'error_line'    => $e->getLine(),
            ]);
            return response()->json([
                'success'    => false,
                'message'    => 'Failed to generate PDF: ' . $e->getMessage(),
                'error_type' => get_class($e),
            ], 500);
        }
    }

    // =========================================================================
    // IMAGE HELPERS
    // =========================================================================

    private function fixImagePaths(&$studentData)
    {
        $defaultStudentImage = public_path('storage/student_avatars/unnamed.jpg');
        $defaultSchoolLogo   = public_path('storage/school_logos/default.jpg');

        foreach ($studentData as &$student) {
            $picturePath = $student['students'] && $student['students']->isNotEmpty()
                ? $this->getAbsoluteImagePath($student['students']->first()->picture, true)
                : null;

            $student['student_image_base64'] = ($picturePath && file_exists($picturePath))
                ? $this->imageToBase64($picturePath)
                : $this->imageToBase64($defaultStudentImage);

            if (isset($student['schoolInfo']) && !empty($student['schoolInfo']->school_logo)) {
                $logoPath = $this->getAbsoluteImagePath($student['schoolInfo']->school_logo, false);
                $student['school_logo_base64'] = ($logoPath && file_exists($logoPath) && filesize($logoPath) > 100)
                    ? $this->imageToBase64($logoPath)
                    : $this->imageToBase64($defaultSchoolLogo);
            } else {
                $student['school_logo_base64'] = $this->imageToBase64($defaultSchoolLogo);
            }

            if (isset($student['schoolInfo']) && !empty($student['schoolInfo']->school_stamp)) {
                $stampPath = $this->getAbsoluteImagePath($student['schoolInfo']->school_stamp, false);
                $student['school_stamp_base64'] = ($stampPath && file_exists($stampPath) && filesize($stampPath) > 100)
                    ? $this->imageToBase64($stampPath)
                    : null;
            } else {
                $student['school_stamp_base64'] = null;
            }
        }
    }

    private function getAbsoluteImagePath($path, $isStudent = false)
    {
        if (empty($path)) return null;
        if (str_starts_with($path, public_path()) || str_starts_with($path, storage_path())) {
            return file_exists($path) ? $path : null;
        }
        if (str_starts_with($path, 'data:image')) return null;

        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        $path = preg_replace('/^(http:\/\/|https:\/\/|\/\/)[^\/]+/', '', $path);
        $path = ltrim($path, DIRECTORY_SEPARATOR);

        $possiblePaths = $isStudent ? [
            public_path('storage/student_avatars/' . $path),
            storage_path('app/public/student_avatars/' . $path),
            public_path('storage/' . $path),
            storage_path('app/public/' . $path),
            public_path($path),
        ] : [
            storage_path('app/public/' . $path),
            public_path('storage/' . $path),
            storage_path('app/public/school_logos/' . basename($path)),
            public_path('storage/school_logos/' . basename($path)),
            public_path($path),
        ];

        foreach (array_unique($possiblePaths) as $fullPath) {
            if (file_exists($fullPath)) return $fullPath;
        }
        return null;
    }

    private function imageToBase64($imagePath)
    {
        if (str_starts_with((string) $imagePath, 'data:image')) return $imagePath;

        if (!$imagePath || !file_exists($imagePath)) {
            return 'data:image/svg+xml;base64,' . base64_encode(
                '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100">
                <rect width="100" height="100" fill="#f0f0f0"/>
                <circle cx="50" cy="40" r="15" fill="#ddd"/>
                <rect x="35" y="60" width="30" height="25" fill="#ddd" rx="2"/>
                </svg>'
            );
        }

        try {
            $imageData = file_get_contents($imagePath);
            if (empty($imageData)) throw new \Exception('Empty image file');
            $ext      = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
            $mimeType = mime_content_type($imagePath) ?: (
                ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
                 'gif' => 'image/gif',  'webp' => 'image/webp'][$ext] ?? 'image/jpeg'
            );
            return "data:{$mimeType};base64," . base64_encode($imageData);
        } catch (\Exception $e) {
            Log::error('imageToBase64 failed', ['path' => $imagePath, 'error' => $e->getMessage()]);
            return 'data:image/svg+xml;base64,' . base64_encode(
                '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100" fill="#f8f9fa"/></svg>'
            );
        }
    }

    private function getTermName($termid)
    {
        return [1 => 'First Term', 2 => 'Second Term', 3 => 'Third Term'][$termid] ?? 'Unknown Term';
    }

    // =========================================================================
    // COLUMN OPTIONS (for the export modal)
    // =========================================================================

    public function columnOptions(Request $request): JsonResponse
    {
        try {
            $columns = [
                'student_info' => [
                    'sn'           => ['label' => 'S/N',          'default' => true],
                    'admission_no' => ['label' => 'Admission No', 'default' => false],
                    'name'         => ['label' => 'Subject Name', 'default' => true],
                ],
                'assessments' => [
                    'ca1'  => ['label' => 'CA1',  'default' => true],
                    'ca2'  => ['label' => 'CA2',  'default' => true],
                    'ca3'  => ['label' => 'CA3',  'default' => true],
                    'exam' => ['label' => 'Exam', 'default' => true],
                ],
                'scores' => [
                    'total'            => ['label' => 'Total',             'default' => true],
                    'bf'               => ['label' => 'BF (Brought Fwd)',  'default' => true],
                    'cum'              => ['label' => 'Cumulative',        'default' => true],
                    'cum_ave'          => ['label' => 'Cum Average',       'default' => false],
                    'grade'            => ['label' => 'Grade',             'default' => true],
                    'arm_position'     => ['label' => 'Arm Pos (Total)',   'default' => true],
                    'arm_position_cum' => ['label' => 'Arm Pos (Cum)',     'default' => true],
                    'position_total'   => ['label' => 'Class Pos (Total)', 'default' => true],
                    'position'         => ['label' => 'Class Pos (Cum)',   'default' => true],
                    'class_average'    => ['label' => 'Subject Average',   'default' => true],
                ],
                'gpa_metrics' => [
                    'gpa'  => ['label' => 'GPA',  'default' => false],
                    'cgpa' => ['label' => 'CGPA', 'default' => false],
                ],
                'other' => [
                    'compulsory_flag' => ['label' => 'Compulsory Flag', 'default' => false],
                    'remark'          => ['label' => 'Remark',          'default' => false],
                ],
            ];

            return response()->json(['success' => true, 'columns' => $columns]);
        } catch (\Throwable $e) {
            Log::error('columnOptions failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to load column options.'], 500);
        }
    }
}