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
            'index', 'studentresult',
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

    /**
     * Convert score value to numeric, handling 'ABS' and 0 differently
     * - 'ABS' or null = don't include in average calculation (return null)
     * - 0 = include in average calculation (return 0)
     * (Restored from the former ViewStudentReportController.)
     */
    protected function getNumericScore($score)
    {
        if ($score === null || $score === '') {
            return null;
        }

        if (is_string($score) && strtoupper(trim($score)) === 'ABS') {
            return null;
        }

        return (float) $score;
    }

    /**
     * Format score for display — shows 'ABS' if absent, '0' if zero,
     * the number otherwise. (Restored from the former ViewStudentReportController.)
     */
    protected function formatScore($score, $originalValue = null)
    {
        $checkValue = $originalValue ?? $score;

        if ($checkValue === null || $checkValue === '') {
            return '-';
        }

        if (is_string($checkValue) && strtoupper(trim($checkValue)) === 'ABS') {
            return 'ABS';
        }

        if (is_numeric($score)) {
            if ($score != (int) $score) {
                return round($score, 1);
            }
            return (int) $score;
        }

        if (is_numeric($checkValue)) {
            return (int) $checkValue;
        }

        return '-';
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
            // classcategories() is a belongsTo (see App\Models\Schoolclass) — it
            // resolves to a single Classcategory model or null, never a Collection.
            $isSenior    = $schoolclass && $schoolclass->classcategories
                ? (bool) ($schoolclass->classcategories->is_senior ?? false)
                : false;

            // ── Fetch broadsheets ───────────────────────────────────────────
            // NOTE: matches the former/working query shape — no subjectRegistrationStatus
            // whereExists eligibility filter. That filter was dropping every subject row
            // for students whose registration data didn't line up exactly, producing a
            // blank "no result printed" PDF. Reverted per request to restore the simpler,
            // known-working score fetch.
            $scores = Broadsheets::where('broadsheet_records.student_id', $id)
                ->where('broadsheets.term_id',               $termid)
                ->where('broadsheet_records.session_id',     $sessionid)
                ->where('broadsheet_records.schoolclass_id', $schoolclassid)
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

            // ── Prefetch previous-term totals for BF fallback ───────────────
            // One query for all subjects of this student instead of one per
            // subject inside the loop below. Only needed for Term 2 / Term 3.
            $previousTotals = collect();
            if ((int) $termid > 1 && $scores->isNotEmpty()) {
                $subjectIds = $scores->pluck('subject_id')->filter()->unique()->values()->toArray();

                if (!empty($subjectIds)) {
                    $previousTotals = DB::table('broadsheets')
                        ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                        ->where('broadsheet_records.student_id', $id)
                        ->where('broadsheet_records.session_id', $sessionid)
                        ->whereIn('broadsheet_records.subject_id', $subjectIds)
                        ->where('broadsheets.term_id', $termid - 1)
                        ->pluck('broadsheets.total', 'broadsheet_records.subject_id');
                }
            }

            // ── Format positions ────────────────────────────────────────────
            foreach ($scores as $score) {
                $score->position_formatted         = ($score->position      && $score->position      > 0) ? $this->formatOrdinal($score->position)      : '-';
                $score->position_total_formatted   = ($score->position_total && $score->position_total > 0) ? $this->formatOrdinal($score->position_total) : '-';
                $score->arm_position_formatted     = ($score->arm_position  && $score->arm_position  > 0) ? $this->formatOrdinal($score->arm_position)  : '-';
                $score->arm_position_cum_formatted = ($score->arm_position_cum && $score->arm_position_cum > 0) ? $this->formatOrdinal($score->arm_position_cum) : '-';
            }

            // ── a–k column breakdown (restored from the former controller) ───
            // Column a/b/c = CA1/CA2/CA3 (ABS-aware). Column d = avg of the CAs
            // supplied. Column e = Exam. Column f = (d+e)/2. Column g = B/F
            // (Term 2/3 only). Column h/Cum = Term 1: f itself; Term 2/3:
            // (f+g)/2. Grade is always recalculated from that final cum value,
            // never trusted from the stored broadsheets.grade column.
            //
            // BF FALLBACK (Sep 2026): if the stored bf is 0/null for Term 2/3,
            // fall back to the previous term's TOTAL for the same student /
            // subject / session. Mirrors the rule already applied in
            // MyScoreSheetController and AdminScoreEntryController — see the
            // getPreviousTermCum() fix note in either of those files. total is
            // the field this system reliably keeps in sync; cum (and therefore
            // bf) is often 0/unpopulated.
            foreach ($scores as $score) {
                $originalCa1  = $score->ca1;
                $originalCa2  = $score->ca2;
                $originalCa3  = $score->ca3;
                $originalExam = $score->exam;
                $originalBf   = $score->bf;

                $ca1  = $this->getNumericScore($originalCa1);
                $ca2  = $this->getNumericScore($originalCa2);
                $ca3  = $this->getNumericScore($originalCa3);
                $exam = $this->getNumericScore($originalExam);
                $bf   = $this->getNumericScore($originalBf);

                // ── BF fallback ────────────────────────────────────────────
                if ((int) $termid > 1 && ($bf === null || (float) $bf == 0)) {
                    $prevTotal = $previousTotals[$score->subject_id] ?? null;

                    if ($prevTotal !== null && (float) $prevTotal != 0) {
                        $bf = round((float) $prevTotal, 2);
                    }
                }

                $caValues = [];
                if ($ca1 !== null) $caValues[] = $ca1;
                if ($ca2 !== null) $caValues[] = $ca2;
                if ($ca3 !== null) $caValues[] = $ca3;

                $columnD = count($caValues) > 0
                    ? round(array_sum($caValues) / count($caValues), 1)
                    : null;

                $validComponents = [];
                if ($columnD !== null) $validComponents[] = $columnD;
                if ($exam !== null)    $validComponents[] = $exam;

                $columnF = count($validComponents) > 0
                    ? round(array_sum($validComponents) / count($validComponents), 1)
                    : null;

                if ((int) $termid === 1) {
                    $columnH = $columnF;
                } else {
                    $finalComponents = [];
                    if ($columnF !== null) $finalComponents[] = $columnF;
                    if ($bf !== null)      $finalComponents[] = $bf;

                    $columnH = count($finalComponents) > 0
                        ? round(array_sum($finalComponents) / count($finalComponents), 1)
                        : null;
                }

                $score->ca1_display  = $this->formatScore($ca1, $originalCa1);
                $score->ca2_display  = $this->formatScore($ca2, $originalCa2);
                $score->ca3_display  = $this->formatScore($ca3, $originalCa3);
                $score->ca_average   = $this->formatScore($columnD);
                $score->exam_display = $this->formatScore($exam, $originalExam);
                $score->f_score      = $this->formatScore($columnF);
                // bf_display now shows the value actually used in the cum math
                // (the derived previous-term total when the stored bf was 0/null),
                // not the raw stored bf. This keeps the displayed column and the
                // displayed cum consistent with each other.
                $score->bf_display   = $this->formatScore($bf);
                $score->cum_score    = $this->formatScore($columnH);
                $score->cum_numeric  = $columnH;

                if ($score->cum_numeric !== null && is_numeric($score->cum_numeric)) {
                    $score->grade  = $isSenior
                        ? $this->calculateSeniorGrade($score->cum_numeric)
                        : $this->calculateJuniorGrade($score->cum_numeric);
                    $score->remark = $this->getRemark($score->grade);
                } else {
                    $score->grade  = '-';
                    $score->remark = '-';
                }
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
            // Count across ALL arms of this class (same cohort ClassPositionService
            // ranks positions against), not just this one arm/schoolclassid — matches
            // the class-wide student count the former report showed.
            $classmateClassIds = Schoolclass::where('schoolclass', $schoolclass?->schoolclass ?? '')
                ->pluck('id')
                ->toArray();
            $numberOfStudents = Studentclass::whereIn('schoolclassid', $classmateClassIds)
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
        $maleCount   = 0;
        $femaleCount = 0;

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

                // Gender breakdown — counted against the same filtered query
                // (pre-pagination, cloned before ->select()/->paginate() ran
                // above) so the totals reflect the WHOLE filtered result set,
                // not just the current page of up to 100 rows.
                try {
                    $genderCounts = (clone $query)
                        ->selectRaw('studentRegistration.gender as gender, COUNT(DISTINCT studentRegistration.id) as cnt')
                        ->groupBy('studentRegistration.gender')
                        ->pluck('cnt', 'gender');

                    foreach ($genderCounts as $genderValue => $cnt) {
                        if (strtolower((string) $genderValue) === 'male') {
                            $maleCount = (int) $cnt;
                        } elseif (strtolower((string) $genderValue) === 'female') {
                            $femaleCount = (int) $cnt;
                        }
                    }

                    Log::info('[studentreports.index] Gender counts', [
                        'male'   => $maleCount,
                        'female' => $femaleCount,
                    ]);
                } catch (\Throwable $e) {
                    Log::error('[studentreports.index] Gender count query FAILED', [
                        'message' => $e->getMessage(),
                    ]);
                }
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
                    'maleCount'    => $maleCount,
                    'femaleCount'  => $femaleCount,
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

        return view('studentreports.index', compact('allstudents', 'schoolsessions', 'schoolclasses', 'pagetitle', 'maleCount', 'femaleCount'));
    }

    public function registeredClasses(Request $request)
    {
        $classId   = $request->query('class_id');
        $sessionId = $request->query('session_id');

        if (!$classId || !$sessionId || $classId === 'ALL' || $sessionId === 'ALL') {
            return response()->json([
                'success' => false,
                'message' => 'Please select a valid class and session.'
            ], 400);
        }

        $classes = Studentclass::query()
            ->join('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->join('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
            ->where('schoolclass.id', $classId)
            ->where('schoolsession.id', $sessionId)
            ->groupBy('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm', 'schoolsession.session')
            ->selectRaw('
                schoolclass.schoolclass as class_name,
                schoolarm.arm as name_arm,
                schoolsession.session as session_name,
                COUNT(DISTINCT studentclass.studentId) as student_count
            ')
            ->get();

        return response()->json(['success' => true, 'data' => $classes]);
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