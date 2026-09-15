<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Schoolarm;
use Illuminate\View\View;
use App\Models\Schoolterm;
use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\Studentclass;
use Illuminate\Http\Request;
use App\Models\Classcategory;
use App\Models\Schoolsession;
use Illuminate\Http\Response;
use App\Models\PromotionStatus;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\SchoolInformation;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\CompulsorySubjectClass;
use App\Models\Studentpersonalityprofile;
use Illuminate\Pagination\LengthAwarePaginator;

class ViewStudentReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View student-report', ['only' => [
            'index', 'show', 'registeredClasses', 'classBroadsheet',
            'studentresult', 'studentmockresult', 'exportStudentResultPdf', 'exportClassResultsPdf'
        ]]);
        $this->middleware('permission:Create student-report', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update student-report', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete student-report', ['only' => ['destroy']]);
    }

    /**
     * Convert score value to numeric, handling 'ABS' and 0 differently
     * - 'ABS' or null = don't include in average calculation (return null)
     * - 0 = include in average calculation (return 0)
     */
    protected function getNumericScore($score)
    {
        if ($score === null || $score === '') {
            return null;
        }

        // Check if it's 'ABS' (case insensitive)
        if (is_string($score) && strtoupper(trim($score)) === 'ABS') {
            return null;  // ABS = exclude from calculation
        }

        // Convert to float - 0 will remain 0 (included in calculation)
        return (float)$score;
    }

    /**
     * Format score for display
     * - Shows 'ABS' if absent
     * - Shows '0' if zero
     * - Shows the number otherwise
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
            // Round to 1 decimal place for calculated values
            if ($score != (int)$score) {
                return round($score, 1);
            }
            return (int)$score;
        }

        if (is_numeric($checkValue)) {
            return (int)$checkValue;
        }

        return '-';
    }

    protected function formatOrdinal($number)
    {
        if (!is_numeric($number) || $number <= 0) {
            return '-';
        }

        $lastDigit = $number % 10;
        $lastTwoDigits = $number % 100;

        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 13) {
            return $number . 'th';
        }

        return $number . match ($lastDigit) {
            1 => 'st',
            2 => 'nd',
            3 => 'rd',
            default => 'th',
        };
    }

    /**
     * Calculate grade based on cumulative score.
     * This ensures grade is always calculated from the actual numeric value.
     */
    protected function calculateGrade($cumulativeScore, $isSenior = false, $classcategory = null)
    {
        if ($cumulativeScore === null || !is_numeric($cumulativeScore)) {
            return '-';
        }

        // Use class category grade calculation if available and senior
        if ($isSenior && $classcategory && method_exists($classcategory, 'calculateGrade')) {
            return $classcategory->calculateGrade($cumulativeScore);
        }

        // Junior grade calculation
        if ($cumulativeScore >= 70) {
            return 'A';
        } elseif ($cumulativeScore >= 60) {
            return 'B';
        } elseif ($cumulativeScore >= 50) {
            return 'C';
        } elseif ($cumulativeScore >= 40) {
            return 'D';
        } else {
            return 'F';
        }
    }

    protected function getDefaultGrade($score)
    {
        return $this->calculateGrade($score, false);
    }

    protected function getRemark($grade)
    {
        $remarks = [
            'A'  => 'Excellent',
            'B'  => 'Very Good',
            'C'  => 'Good',
            'D'  => 'Pass',
            'F'  => 'Fail',
            'A1' => 'Excellent',
            'B2' => 'Very Good',
            'B3' => 'Good',
            'C4' => 'Credit',
            'C5' => 'Credit',
            'C6' => 'Credit',
            'D7' => 'Pass',
            'E8' => 'Pass',
            'F9' => 'Fail',
        ];
        return $remarks[$grade] ?? 'Unknown';
    }

    protected function calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid)
    {
        $cacheKey = "class_metrics_{$schoolclassid}_{$sessionid}_{$termid}";

        // Force cache invalidation to ensure fresh calculations
        Cache::forget($cacheKey);

        $schoolclass = Schoolclass::with('classcategory')->where('id', $schoolclassid)->first(['id', 'schoolclass', 'classcategoryid']);
        if (!$schoolclass) {
            Log::warning('Schoolclass not found', [
                'schoolclassid' => $schoolclassid,
                'sessionid'     => $sessionid,
                'termid'        => $termid,
            ]);
            return false;
        }
        $className = $schoolclass->schoolclass;
        $isSenior  = $schoolclass->classcategory ? $schoolclass->classcategory->is_senior : false;

        $classIds = Schoolclass::where('schoolclass', $className)->pluck('id')->toArray();
        if (empty($classIds)) {
            Log::warning('No schoolclass IDs found for class name', [
                'class_name'   => $className,
                'schoolclassid' => $schoolclassid,
                'sessionid'     => $sessionid,
                'termid'        => $termid,
            ]);
            return false;
        }

        $students = Studentclass::whereIn('schoolclassid', $classIds)
            ->where('sessionid', $sessionid)
            ->pluck('studentId')
            ->toArray();

        if (empty($students)) {
            Log::warning('No students found for class', [
                'class_name'    => $className,
                'schoolclassids' => $classIds,
                'sessionid'      => $sessionid,
                'termid'         => $termid,
            ]);
            return false;
        }

        $success = DB::transaction(function () use ($schoolclassid, $sessionid, $termid, $className, $classIds, $students, $isSenior, $schoolclass, &$subjectGroups) {
            $broadsheets = Broadsheets::whereIn('broadsheet_records.student_id', $students)
                ->where('broadsheets.term_id', $termid)
                ->where('broadsheet_records.session_id', $sessionid)
                ->whereIn('broadsheet_records.schoolclass_id', $classIds)
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                ->join('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
                ->select([
                    'broadsheets.id',
                    'broadsheet_records.student_id',
                    'broadsheet_records.subject_id',
                    'subject.subject as subject_name',
                    'studentRegistration.admissionNo as admission_no',
                    'broadsheets.total',
                    'broadsheets.cum',
                    'broadsheets.subject_position_class',
                    'broadsheets.avg',
                    'broadsheets.grade',
                    'broadsheets.remark',
                ])
                ->get();

            if ($broadsheets->isEmpty()) {
                Log::warning('No broadsheet records found for class', [
                    'class_name'    => $className,
                    'schoolclassids' => $classIds,
                    'sessionid'      => $sessionid,
                    'termid'         => $termid,
                ]);
                return false;
            }

            $subjectGroups = $broadsheets->groupBy('subject_id');

            foreach ($subjectGroups as $subjectId => $subjectRecords) {
                $subjectName  = $subjectRecords->first()->subject_name;
                $validRecords = $subjectRecords->filter(function ($record) {
                    return $record->cum != 0;
                });
                $totalScores  = $validRecords->sum('total');
                $studentCount = $validRecords->count();
                $classAvg     = $studentCount > 0 ? round($totalScores / $studentCount, 1) : 0;

                $sortedRecords = $validRecords->sortByDesc('total')->values();

                $rank         = 0;
                $lastTotal    = null;
                $lastPosition = 0;
                $positionMap  = [];

                foreach ($sortedRecords as $record) {
                    $rank++;
                    if ($lastTotal !== null && $record->total == $lastTotal) {
                        $positionMap[$record->id] = $lastPosition;
                    } else {
                        $lastPosition             = $rank;
                        $lastTotal                = $record->total;
                        $positionMap[$record->id] = $lastPosition;
                    }
                }

                foreach ($subjectRecords as $record) {
                    $newPosition = $record->cum == 0 ? '-' : ($positionMap[$record->id] ?? null);
                    if ($newPosition !== '-') {
                        $newPosition = $this->formatOrdinal($newPosition);
                    }

                    // Recalculate grade based on actual cum value
                    $grade  = $record->cum == 0 ? '-' : $this->calculateGrade($record->cum, $isSenior, $schoolclass->classcategory);
                    $remark = $this->getRemark($grade);

                    if (
                        $record->avg != $classAvg ||
                        $record->subject_position_class != $newPosition ||
                        $record->grade != $grade ||
                        $record->remark != $remark
                    ) {
                        Broadsheets::where('id', $record->id)->update([
                            'avg'                    => $classAvg,
                            'subject_position_class' => $newPosition,
                            'grade'                  => $grade,
                            'remark'                 => $remark,
                        ]);

                        Log::info('Updated broadsheet metrics', [
                            'broadsheet_id'          => $record->id,
                            'student_id'             => $record->student_id,
                            'admission_no'           => $record->admission_no,
                            'subject_id'             => $subjectId,
                            'subject_name'           => $subjectName,
                            'class_avg'              => $classAvg,
                            'subject_position_class' => $newPosition,
                            'grade'                  => $grade,
                            'remark'                 => $remark,
                            'class_name'             => $className,
                            'cum'                    => $record->cum,
                        ]);
                    }
                }

                Log::info('Calculated metrics for subject', [
                    'subject_id'    => $subjectId,
                    'subject_name'  => $subjectName,
                    'class_name'    => $className,
                    'schoolclassids' => $classIds,
                    'sessionid'      => $sessionid,
                    'termid'         => $termid,
                    'class_avg'      => $classAvg,
                    'student_count'  => $studentCount,
                    'total_scores'   => $totalScores,
                ]);
            }

            return true;
        });

        if ($success) {
            Cache::put($cacheKey, true, now()->addHours(1));
            Log::info('Completed class metrics calculation', [
                'class_name'     => $className,
                'schoolclassids' => $classIds,
                'sessionid'      => $sessionid,
                'termid'         => $termid,
                'total_subjects' => $subjectGroups->count(),
                'total_students' => count($students),
            ]);
        } else {
            Log::error('Failed to calculate class metrics', [
                'class_name'   => $className,
                'schoolclassid' => $schoolclassid,
                'sessionid'     => $sessionid,
                'termid'        => $termid,
            ]);
        }

        return $success;
    }

    private function getStudentResultData($id, $schoolclassid, $sessionid, $termid)
    {
        try {
            if (!is_numeric($id) || !is_numeric($schoolclassid) || !is_numeric($sessionid) || !is_numeric($termid)) {
                Log::error('Invalid parameters in getStudentResultData', [
                    'student_id'   => $id,
                    'schoolclassid' => $schoolclassid,
                    'sessionid'     => $sessionid,
                    'termid'        => $termid,
                ]);
                return [];
            }

            // Initialize variables
            $promotionStatusValue = null;

            $students = Student::where('studentRegistration.id', $id)
                ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->select([
                    'studentRegistration.id as id',
                    'studentRegistration.admissionNo as admissionNo',
                    'studentRegistration.firstname as fname',
                    'studentRegistration.home_address2 as homeaddress',
                    'studentRegistration.lastname as lastname',
                    'studentRegistration.othername as othername',
                    'studentRegistration.dateofbirth as dateofbirth',
                    'studentRegistration.gender as gender',
                    'studentRegistration.updated_at as updated_at',
                    'studentpicture.picture as picture'
                ])
                ->orderBy('studentRegistration.lastname', 'asc')
                ->get();

            if ($students->isEmpty()) {
                Log::warning('No active student found for ID', ['student_id' => $id]);
                $students = collect([]);
            }

            // Fetch scores
            $scores = Broadsheets::where('broadsheet_records.student_id', $id)
                ->where('broadsheets.term_id', $termid)
                ->where('broadsheet_records.session_id', $sessionid)
                ->where('broadsheet_records.schoolclass_id', $schoolclassid)
                ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                ->orderBy('subject.subject')
                ->select([
                    'subject.id as subject_id',
                    'subject.subject as subject_name',
                    'subject.subject_code',
                    'broadsheets.ca1',
                    'broadsheets.ca2',
                    'broadsheets.ca3',
                    'broadsheets.exam',
                    'broadsheets.bf',
                    'broadsheets.total',
                    'broadsheets.cum',
                    'broadsheets.grade',
                    'broadsheets.remark',
                    'broadsheets.subject_position_class as position',
                    'broadsheets.avg as class_average',
                ])->get();

            // Get schoolclass info for grade calculation
            $schoolclass = Schoolclass::with('classcategory')->find($schoolclassid);
            $isSenior    = $schoolclass && $schoolclass->classcategory ? $schoolclass->classcategory->is_senior : false;

            // ==============================================
            // CALCULATE SCORES WITH PROPER 0 vs ABS HANDLING
            // ==============================================
            foreach ($scores as $score) {
                // Get original values
                $originalCa1  = $score->ca1;
                $originalCa2  = $score->ca2;
                $originalCa3  = $score->ca3;
                $originalExam = $score->exam;
                $originalBf   = $score->bf;

                // Get numeric values (ABS becomes null, 0 remains 0)
                $ca1  = $this->getNumericScore($originalCa1);
                $ca2  = $this->getNumericScore($originalCa2);
                $ca3  = $this->getNumericScore($originalCa3);
                $exam = $this->getNumericScore($originalExam);
                $bf   = $this->getNumericScore($originalBf);

                // --------------------------------------------------
                // Column d: Average of available CAs (including zeros)
                // --------------------------------------------------
                $caValues = [];
                if ($ca1 !== null) $caValues[] = $ca1;
                if ($ca2 !== null) $caValues[] = $ca2;
                if ($ca3 !== null) $caValues[] = $ca3;

                $columnD = count($caValues) > 0
                    ? round(array_sum($caValues) / count($caValues), 1)
                    : null;

                // --------------------------------------------------
                // Column f: (d + e) / 2  — average of CA-avg and Exam
                // --------------------------------------------------
                $validComponents = [];
                if ($columnD !== null) $validComponents[] = $columnD;
                if ($exam !== null)    $validComponents[] = $exam;

                $columnF = count($validComponents) > 0
                    ? round(array_sum($validComponents) / count($validComponents), 1)
                    : null;

                // --------------------------------------------------
                // Column h (Cum):
                //   TERM 1  → f IS the final score (no bf column).
                //              f also becomes the bf carried into Term 2.
                //   TERM 2/3 → (f + bf) / 2
                // --------------------------------------------------
                if ((int)$termid === 1) {
                    // First Term: cumulative score = f (no bf involved)
                    $columnH = $columnF;
                } else {
                    // Second / Third Term: average of f and the carried-forward bf
                    $finalComponents = [];
                    if ($columnF !== null) $finalComponents[] = $columnF;
                    if ($bf !== null)      $finalComponents[] = $bf;

                    $columnH = count($finalComponents) > 0
                        ? round(array_sum($finalComponents) / count($finalComponents), 1)
                        : null;
                }

                // --------------------------------------------------
                // Store display values
                // --------------------------------------------------
                $score->ca1_display  = $this->formatScore($ca1, $originalCa1);   // Column a
                $score->ca2_display  = $this->formatScore($ca2, $originalCa2);   // Column b
                $score->ca3_display  = $this->formatScore($ca3, $originalCa3);   // Column c
                $score->ca_average   = $this->formatScore($columnD);             // Column d
                $score->exam_display = $this->formatScore($exam, $originalExam); // Column e
                $score->f_score      = $this->formatScore($columnF);             // Column f
                $score->bf_display   = $this->formatScore($bf, $originalBf);     // Column g (Term 2/3 only)
                $score->cum_score    = $this->formatScore($columnH);             // Column h / final cum
                $score->cum_numeric  = $columnH; // Numeric value used for grade calculation

                // --------------------------------------------------
                // GRADE: always recalculate from the actual numeric cum
                // For Term 1: cum_numeric = f = (d+e)/2
                // For Term 2/3: cum_numeric = (f + bf) / 2
                // --------------------------------------------------
                if ($score->cum_numeric !== null && is_numeric($score->cum_numeric)) {
                    $score->grade  = $this->calculateGrade(
                        $score->cum_numeric,
                        $isSenior,
                        $schoolclass ? $schoolclass->classcategory : null
                    );
                    $score->remark = $this->getRemark($score->grade);
                } else {
                    $score->grade  = '-';
                    $score->remark = '-';
                }

                // Store raw numeric values for template calculations
                $score->ca1_numeric   = $ca1;
                $score->ca2_numeric   = $ca2;
                $score->ca3_numeric   = $ca3;
                $score->exam_numeric  = $exam;
                $score->bf_numeric    = $bf;
                $score->col_d_numeric = $columnD;
                $score->col_f_numeric = $columnF;
                $score->col_h_numeric = $columnH;
            }

            Log::info('Fetched broadsheet data with calculations', [
                'student_id'   => $id,
                'termid'       => $termid,
                'scores_count' => $scores->count(),
            ]);

            // Fetch the LATEST personality profile
            $studentpp = Studentpersonalityprofile::where('studentid', $id)
                ->where('schoolclassid', $schoolclassid)
                ->where('sessionid', $sessionid)
                ->where('termid', $termid)
                ->first();

            $promotionStatus = PromotionStatus::where('studentId', $id)
                ->where('schoolclassid', $schoolclassid)
                ->where('sessionid', $sessionid)
                ->where('termid', $termid)
                ->first();

            $schoolclass   = Schoolclass::with('armRelation')->find($schoolclassid, ['id', 'schoolclass', 'arm', 'classcategoryid']) ?? (object)[
                'schoolclass' => 'N/A',
                'armRelation' => (object)['arm' => 'N/A'],
                'classcategoryid' => null
            ];
            $schoolterm    = Schoolterm::where('id', $termid)->value('term') ?? 'N/A';
            $schoolsession = Schoolsession::where('id', $sessionid)->value('session') ?? 'N/A';

            $numberOfStudents = Studentclass::whereIn('schoolclassid',
                Schoolclass::where('schoolclass', $schoolclass->schoolclass ?? 'N/A')->pluck('id'))
                ->where('sessionid', $sessionid)
                ->count();

            $schoolInfo = SchoolInformation::getActiveSchool() ?? (object)[
                'school_name'    => 'TOPCLASS COLLEGE',
                'school_motto'   => 'Developing the total child',
                'school_address' => '39, Okegbala Street, Ondo.',
                'school_website' => 'https://topclasscollege.ng',
                'getLogoUrlAttribute' => function () {
                    return 'school_logos/default.jpg';
                }
            ];

            // ------------------------------------------------------------------
            // Term 3 only: promotion logic
            // ------------------------------------------------------------------
            if ((int)$termid === 3) {
                $classCategory = Classcategory::find($schoolclass->classcategoryid, ['is_senior']);
                $isSenior      = $classCategory ? $classCategory->is_senior : false;

                $compulsorySubjects = CompulsorySubjectClass::where('schoolclassid', $schoolclassid)
                    ->join('subject', 'compulsory_subject_classes.subjectId', '=', 'subject.id')
                    ->select(['compulsory_subject_classes.subjectId', 'subject.subject as subject_name'])
                    ->get();

                $compulsorySubjectLog    = [];
                $compulsoryCreditCount   = 0;
                $creditCount             = 0;
                $failCount               = 0;
                $missingCompulsorySubjects = [];

                $creditGrades = $isSenior ? ['A1', 'B2', 'B3', 'C4', 'C5', 'C6'] : ['A', 'B', 'C'];
                $failGrades   = $isSenior ? ['F9', 'E8'] : ['F'];
                $dGrade       = $isSenior ? 'D7' : 'D';
                $aGrades      = $isSenior ? ['A1'] : ['A'];
                $bGrades      = $isSenior ? ['B2', 'B3'] : ['B'];
                $cGrades      = $isSenior ? ['C4', 'C5', 'C6'] : ['C'];

                $compulsorySubjectIds = $compulsorySubjects->pluck('subjectId')->toArray();
                foreach ($compulsorySubjects as $compulsorySubject) {
                    $subjectId   = $compulsorySubject->subjectId;
                    $subjectName = $compulsorySubject->subject_name;
                    $score       = $scores->firstWhere('subject_id', $subjectId);

                    $grade = $score ? $score->grade : '-';

                    $compulsorySubjectLog[] = [
                        'subject_id'   => $subjectId,
                        'subject_name' => $subjectName,
                        'grade'        => $grade,
                    ];
                    if ($score && in_array($grade, $creditGrades)) {
                        $compulsoryCreditCount++;
                    } elseif (!$score) {
                        $missingCompulsorySubjects[] = $subjectName;
                    }
                }

                foreach ($scores as $score) {
                    $grade = $score->grade ?? '-';
                    if (in_array($grade, $creditGrades)) {
                        $creditCount++;
                    } elseif (in_array($grade, $failGrades)) {
                        $failCount++;
                    }
                }

                // Determine performance comment based on grades
                $performanceComment = '';
                if ($scores->count() > 0) {
                    $aCount = $scores->filter(fn($s) => in_array($s->grade, $aGrades))->count();
                    $bCount = $scores->filter(fn($s) => in_array($s->grade, $bGrades))->count();
                    $cCount = $scores->filter(fn($s) => in_array($s->grade, $cGrades))->count();
                    $dCount = $scores->filter(fn($s) => $s->grade === $dGrade)->count();
                    $fCount = $scores->filter(fn($s) => in_array($s->grade, $failGrades))->count();

                    $totalGrades = $scores->count();
                    if ($aCount === $totalGrades) {
                        $performanceComment = 'Excellent results';
                    } elseif ($aCount > 0 && $bCount > 0 && $cCount === 0 && $dCount === 0 && $fCount === 0) {
                        $performanceComment = 'Very Good results';
                    } elseif (($aCount + $bCount + $cCount) >= $totalGrades / 2) {
                        $performanceComment = 'Good results';
                    } elseif (($bCount + $cCount) >= $totalGrades / 2) {
                        $performanceComment = 'Average results';
                    } else {
                        $performanceComment = 'Below Average results';
                    }
                } else {
                    $performanceComment = 'No grades available';
                }

                // Promotion logic
                $principalComment    = '';
                $promotionStatusValue = '';

                if ($isSenior) {
                    $criticalCompulsorySubjects = $compulsorySubjects->filter(function ($subject) {
                        return in_array(strtolower($subject->subject_name), ['english language', 'mathematics']);
                    })->pluck('subjectId')->toArray();

                    $criticalCompulsoryCreditCount = 0;
                    $failedCriticalSubjects        = [];
                    foreach ($criticalCompulsorySubjects as $subjectId) {
                        $score       = $scores->firstWhere('subject_id', $subjectId);
                        $subjectName = $compulsorySubjects->firstWhere('subjectId', $subjectId)->subject_name ?? 'Unknown';
                        $grade       = $score ? $score->grade : null;
                        if ($score && in_array($grade, $creditGrades)) {
                            $criticalCompulsoryCreditCount++;
                        } elseif ($score && !in_array($grade, $creditGrades)) {
                            $failedCriticalSubjects[] = $subjectName;
                        }
                    }

                    $compulsoryFailCount = $compulsorySubjects->count() - $compulsoryCreditCount;

                    if (!empty($missingCompulsorySubjects)) {
                        $principalComment     = "$performanceComment. Missing grades for compulsory subjects. Parents to see the Principal.";
                        $promotionStatusValue = 'PARENTS TO SEE PRINCIPAL';
                    } elseif ($compulsoryCreditCount === 5 && $creditCount >= 5) {
                        $principalComment     = "$performanceComment. Promoted to the next class.";
                        $promotionStatusValue = 'PROMOTED';
                    } elseif ($creditCount >= 4 && $compulsoryFailCount <= 1) {
                        $principalComment     = "$performanceComment. Needs improvement in some subjects. Promoted on trial.";
                        $promotionStatusValue = 'PROMOTED ON TRIAL';
                    } elseif ($compulsoryFailCount === 2) {
                        $principalComment     = "$performanceComment. Failed two compulsory subjects. Parents to see the Principal.";
                        $promotionStatusValue = 'PARENTS TO SEE PRINCIPAL';
                    } elseif ($compulsoryFailCount > 2 || $creditCount < 4) {
                        $principalComment     = "$performanceComment. Advised to repeat the class. Parents to see the Principal.";
                        $promotionStatusValue = 'ADVISED TO REPEAT/PARENTS TO SEE PRINCIPAL';
                    } else {
                        $principalComment     = "$performanceComment. Inconsistent performance or insufficient compulsory subject credits. Parents to see the Principal.";
                        $promotionStatusValue = 'PARENTS TO SEE PRINCIPAL';
                    }
                } else {
                    // Junior category
                    $allDs = $scores->count() > 0 && $scores->every(fn($s) => $s->grade === $dGrade);
                    $mixOfDsAndFs = $scores->count() > 0 && $scores->every(
                        fn($s) => $s->grade === $dGrade || in_array($s->grade, $failGrades)
                    );
                    $failedNonCompulsory = $scores->filter(function ($score) use ($compulsorySubjectIds, $failGrades) {
                        return !in_array($score->subject_id, $compulsorySubjectIds) && in_array($score->grade, $failGrades);
                    })->count() === ($scores->count() - count($compulsorySubjectIds));

                    if (!empty($missingCompulsorySubjects)) {
                        $principalComment     = "$performanceComment. Missing grades for compulsory subjects: " . implode(', ', $missingCompulsorySubjects) . '. Parents to see the Principal.';
                        $promotionStatusValue = 'PARENTS TO SEE PRINCIPAL';
                    } elseif ($compulsorySubjects->count() > 0 && $compulsoryCreditCount === $compulsorySubjects->count() && $creditCount >= 5) {
                        $principalComment     = "$performanceComment. Promoted to the next class.";
                        $promotionStatusValue = 'PROMOTED';
                    } elseif ($creditCount >= 4 && $compulsoryCreditCount > 0) {
                        $principalComment     = "$performanceComment. But needs improvement in some compulsory subjects. Promoted on trial.";
                        $promotionStatusValue = 'PROMOTED ON TRIAL';
                    } elseif ($creditCount >= 4 && $compulsoryCreditCount == 0) {
                        $principalComment     = "$performanceComment. Achieved credits but none in compulsory subjects. Parents to see the Principal.";
                        $promotionStatusValue = 'PARENTS TO SEE PRINCIPAL';
                    } elseif ($failCount === count($scores) && count($scores) > 0) {
                        $principalComment     = "$performanceComment. Poor performance across all subjects. Advised to repeat the class. Parents to see the Principal.";
                        $promotionStatusValue = 'ADVISED TO REPEAT/PARENTS TO SEE PRINCIPAL';
                    } elseif ($allDs || $mixOfDsAndFs) {
                        $principalComment     = "$performanceComment. Poor performance with D or F grades. Advised to repeat the class. Parents to see the Principal.";
                        $promotionStatusValue = 'ADVISED TO REPEAT/PARENTS TO SEE PRINCIPAL';
                    } elseif ($compulsoryCreditCount === $compulsorySubjects->count() && $failedNonCompulsory && $scores->count() > count($compulsorySubjectIds)) {
                        $principalComment     = "$performanceComment. Passed compulsory subjects but failed all other subjects. Parents to see the Principal.";
                        $promotionStatusValue = 'PARENTS TO SEE PRINCIPAL';
                    } elseif ($creditCount < 4 && $compulsoryCreditCount < $compulsorySubjects->count()) {
                        $principalComment     = "$performanceComment. Less than 4 credits and failed compulsory subjects. Advised to repeat the class. Parents to see the Principal.";
                        $promotionStatusValue = 'ADVISED TO REPEAT/PARENTS TO SEE PRINCIPAL';
                    } else {
                        $principalComment     = "$performanceComment. Inconsistent performance or incomplete grades. Parents to see the Principal for further discussion.";
                        $promotionStatusValue = 'ADVISED TO REPEAT/PARENTS TO SEE PRINCIPAL';
                    }
                }

                Log::info("Promotion Decision for Student ID: {$id}", [
                    'principal_comment'  => $principalComment,
                    'promotion_status'   => $promotionStatusValue,
                    'performance_comment' => $performanceComment,
                ]);

                Studentpersonalityprofile::updateOrCreate(
                    [
                        'studentid'    => $id,
                        'schoolclassid' => $schoolclassid,
                        'sessionid'    => $sessionid,
                        'termid'       => $termid,
                    ],
                    ['principalscomment' => $principalComment]
                );

                PromotionStatus::updateOrCreate(
                    [
                        'studentId'    => $id,
                        'schoolclassid' => $schoolclassid,
                        'sessionid'    => $sessionid,
                        'termid'       => $termid,
                    ],
                    [
                        'promotionStatus' => $promotionStatusValue,
                        'position'        => null,
                        'classstatus'     => 'CURRENT',
                    ]
                );
            }

            return [
                'students'            => $students,
                'studentpp'           => collect([$studentpp]),
                'scores'              => $scores,
                'studentid'           => $id,
                'schoolclassid'       => $schoolclassid,
                'sessionid'           => $sessionid,
                'termid'              => $termid,
                'schoolclass'         => $schoolclass,
                'schoolterm'          => $schoolterm,
                'schoolsession'       => $schoolsession,
                'numberOfStudents'    => $numberOfStudents,
                'schoolInfo'          => $schoolInfo,
                'promotionStatusValue' => $promotionStatusValue,
            ];

        } catch (Exception $e) {
            Log::error('Error fetching student result data', [
                'student_id' => $id,
                'error'      => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);
            return [];
        }
    }

    public function calculateGradePreview(Request $request)
    {
        $request->validate([
            'schoolclass_id' => 'required|exists:schoolclass,id',
            'cum'            => 'required|numeric|min:0|max:100',
        ]);

        $schoolclass = Schoolclass::with('classcategory')->findOrFail($request->schoolclass_id);
        $isSenior    = $schoolclass->classcategory ? $schoolclass->classcategory->is_senior : false;
        $grade       = $this->calculateGrade($request->cum, $isSenior, $schoolclass->classcategory);

        return response()->json(['grade' => $grade]);
    }

    public function studentresult($id, $schoolclassid, $sessionid, $termid)
    {
        $pagetitle = "Student Personality Profile";

        $metricsCalculated = $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);
        if (!$metricsCalculated) {
            Log::error('Failed to calculate class metrics for student result', [
                'student_id'   => $id,
                'schoolclassid' => $schoolclassid,
                'sessionid'     => $sessionid,
                'termid'        => $termid,
            ]);
            return back()->with('error', 'Failed to calculate class metrics. Please try again.');
        }

        $data = $this->getStudentResultData($id, $schoolclassid, $sessionid, $termid);

        return view('studentreports.studentresult')->with($data)->with('pagetitle', $pagetitle);
    }

    public function exportStudentResultPdf($id, $schoolclassid, $sessionid, $termid)
    {
        try {
            ini_set('max_execution_time', 600);
            ini_set('memory_limit', '1024M');

            Log::info('Generating single student PDF', [
                'student_id'   => $id,
                'schoolclassid' => $schoolclassid,
                'sessionid'     => $sessionid,
                'termid'        => $termid,
            ]);

            $metricsCalculated = $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);
            if (!$metricsCalculated) {
                Log::error('Failed to calculate class metrics for PDF generation', [
                    'student_id'   => $id,
                    'schoolclassid' => $schoolclassid,
                    'sessionid'     => $sessionid,
                    'termid'        => $termid,
                ]);
                return back()->with('error', 'Failed to calculate class metrics. Please try again.');
            }

            $data = $this->getStudentResultData($id, $schoolclassid, $sessionid, $termid);

            if (empty($data) || empty($data['students']) || $data['students']->isEmpty()) {
                Log::error('No valid student data for PDF generation', ['student_id' => $id]);
                return back()->with('error', 'No student data found for the provided parameters.');
            }

            $student     = $data['students']->first();
            $studentName = $student ? $student->fname . '_' . $student->lastname : 'Student';
            $filename    = 'Terminal_Report_' . $studentName . '_' . $data['schoolsession'] . '_Term_' . $data['termid'] . '.pdf';

            $this->fixImagePaths([$data]);

            $pdf = Pdf::loadView('studentreports.studentresult_pdf', ['data' => $data])
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'dpi'                    => 150,
                    'defaultFont'            => 'DejaVu Sans',
                    'isRemoteEnabled'        => true,
                    'isHtml5ParserEnabled'   => true,
                    'isFontSubsettingEnabled' => true,
                    'isPhpEnabled'           => false,
                    'chroot'                 => [public_path(), storage_path()],
                    'fontCache'              => storage_path('fonts/'),
                    'logOutputFile'          => storage_path('logs/dompdf.log'),
                    'debugCss'               => config('app.debug', false),
                    'debugLayout'            => config('app.debug', false),
                ]);

            return $pdf->download($filename);

        } catch (Exception $e) {
            Log::error('Single Student PDF Export Error', [
                'student_id' => $id,
                'error'      => $e->getMessage(),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    public function exportClassResultsPdf(Request $request)
    {
        try {
            ini_set('max_execution_time', 1200);
            ini_set('memory_limit', '2048M');

            $schoolclassid  = $request->input('schoolclassid');
            $sessionid      = $request->input('sessionid');
            $termid         = $request->input('termid', 3);
            $studentIds     = $request->input('studentIds', []);

            Log::info('Starting class results PDF generation', [
                'schoolclassid' => $schoolclassid,
                'sessionid'     => $sessionid,
                'termid'        => $termid,
                'studentIds'    => $studentIds,
            ]);

            if (!is_numeric($schoolclassid) || !is_numeric($sessionid) || !is_numeric($termid)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parameters provided. All IDs must be numeric.'
                ], 400);
            }

            if (!Schoolclass::find($schoolclassid) || !Schoolsession::find($sessionid) || !Schoolterm::find($termid)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid class, session, or term ID.'
                ], 400);
            }

            $metricsCalculated = $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);
            if (!$metricsCalculated) {
                Log::error('Failed to calculate class metrics before generating PDF', [
                    'schoolclassid' => $schoolclassid,
                    'sessionid'     => $sessionid,
                    'termid'        => $termid,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to calculate class metrics. Please try again.'
                ], 500);
            }

            $query = Studentclass::where('schoolclassid', $schoolclassid)
                ->where('sessionid', $sessionid)
                ->join('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
                ->join('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
                ->select('studentRegistration.id', 'studentRegistration.firstname', 'studentRegistration.lastname')
                ->orderBy('studentRegistration.lastname', 'asc');

            if (!empty($studentIds)) {
                $query->whereIn('studentRegistration.id', $studentIds);
            }

            $students = $query->get();

            if ($students->isEmpty()) {
                Log::warning('No students found for class or selected students', [
                    'schoolclassid' => $schoolclassid,
                    'sessionid'     => $sessionid,
                    'studentIds'    => $studentIds,
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No students found for the selected class, session, or selected students.'
                ], 404);
            }

            Log::info('Processing students for PDF', ['student_count' => $students->count()]);

            $allStudentData    = [];
            $processedStudents = 0;
            $skippedStudents   = 0;

            DB::transaction(function () use ($students, $schoolclassid, $sessionid, $termid, &$allStudentData, &$processedStudents, &$skippedStudents) {
                foreach ($students as $student) {
                    try {
                        $studentData = $this->getStudentResultData($student->id, $schoolclassid, $sessionid, $termid);
                        if ($this->validateStudentData($studentData)) {
                            $allStudentData[] = $studentData;
                            $processedStudents++;
                            Log::info('Processed student data', [
                                'student_id'   => $student->id,
                                'student_name' => $student->firstname . ' ' . $student->lastname,
                            ]);
                        } else {
                            $skippedStudents++;
                            Log::warning('Skipping student due to invalid/missing data', ['student_id' => $student->id]);
                        }
                    } catch (Exception $e) {
                        $skippedStudents++;
                        Log::error('Error processing student data', [
                            'student_id' => $student->id,
                            'error'      => $e->getMessage(),
                        ]);
                    }
                }
            });

            if (empty($allStudentData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid student data found for PDF generation.'
                ], 404);
            }

            Log::info('Student data collection completed', [
                'processed' => $processedStudents,
                'skipped'   => $skippedStudents,
                'total'     => $students->count(),
            ]);

            $this->fixImagePaths($allStudentData);

            $schoolclass   = Schoolclass::where('id', $schoolclassid)->with('armRelation')->first(['schoolclass', 'arm']);
            $schoolsession = Schoolsession::where('id', $sessionid)->value('session') ?? 'N/A';
            $term          = $this->getTermName($termid);
            $className     = $schoolclass
                ? ($schoolclass->schoolclass . ($schoolclass->armRelation ? $schoolclass->armRelation->arm : ''))
                : 'Class';
            $filename = 'Class_Results_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $className) . '_' .
                        preg_replace('/[^A-Za-z0-9_-]/', '_', $schoolsession) . '_' . $term . '.pdf';

            $viewName = 'studentreports.class_results_pdf';
            if (!view()->exists($viewName)) {
                Log::error('PDF view not found', ['view' => $viewName]);
                return response()->json([
                    'success' => false,
                    'message' => 'PDF template view not found: ' . $viewName,
                ], 500);
            }

            $viewData = [
                'allStudentData' => $allStudentData,
                'metadata'       => [
                    'class_name'      => $className,
                    'session'         => $schoolsession,
                    'term'            => $term,
                    'generation_date' => now()->format('Y-m-d H:i:s'),
                    'student_count'   => count($allStudentData),
                ],
            ];

            $this->ensureDirectoriesExist();

            $pdf = Pdf::loadView($viewName, $viewData)
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'dpi'                    => 96,
                    'defaultFont'            => 'DejaVu Sans',
                    'isRemoteEnabled'        => true,
                    'isHtml5ParserEnabled'   => true,
                    'isFontSubsettingEnabled' => true,
                    'isPhpEnabled'           => false,
                    'chroot'                 => [public_path(), storage_path()],
                    'tempDir'                => storage_path('app/temp/'),
                    'fontCache'              => storage_path('fonts/'),
                    'logOutputFile'          => storage_path('logs/dompdf.log'),
                ]);

            $pdfContent = $pdf->output();

            if (empty($pdfContent)) {
                Log::error('PDF content is empty');
                return response()->json([
                    'success' => false,
                    'message' => 'Generated PDF content is empty',
                ], 500);
            }

            $responseMethod = $request->input('response_method', 'base64');

            switch ($responseMethod) {
                case 'save_and_redirect':
                    return $this->saveAndRedirectResponse($pdfContent, $filename);
                case 'base64':
                    return $this->base64Response($pdfContent, $filename);
                case 'download':
                    return $this->downloadResponse($pdfContent, $filename);
                case 'inline':
                    return $this->inlineResponse($pdfContent, $filename);
                default:
                    return $this->base64Response($pdfContent, $filename);
            }

        } catch (Exception $e) {
            Log::error('Class PDF Export Error', [
                'schoolclassid' => $schoolclassid ?? 'N/A',
                'sessionid'     => $sessionid ?? 'N/A',
                'termid'        => $termid ?? 'N/A',
                'error'         => $e->getMessage(),
                'file'          => $e->getFile(),
                'line'          => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function inlineResponse($pdfContent, $filename)
    {
        try {
            while (ob_get_level()) {
                ob_end_clean();
            }
            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent));
        } catch (Exception $e) {
            Log::error('Inline response failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to send inline response: ' . $e->getMessage()], 500);
        }
    }

    private function downloadResponse($pdfContent, $filename)
    {
        try {
            return response($pdfContent, 200, [
                'Content-Type'        => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length'      => strlen($pdfContent),
            ]);
        } catch (Exception $e) {
            Log::error('Download response failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to send download response: ' . $e->getMessage()], 500);
        }
    }

    private function saveAndRedirectResponse($pdfContent, $filename)
    {
        try {
            $publicPath = public_path('temp_pdfs');
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0755, true);
            }
            $filePath  = $publicPath . '/' . $filename;
            file_put_contents($filePath, $pdfContent);
            $publicUrl = url('temp_pdfs/' . $filename);

            return response()->json([
                'success'  => true,
                'message'  => 'PDF generated successfully',
                'pdf_url'  => $publicUrl,
                'filename' => $filename,
                'size'     => strlen($pdfContent)
            ]);
        } catch (Exception $e) {
            Log::error('Save and redirect failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to save PDF: ' . $e->getMessage()], 500);
        }
    }

    private function base64Response($pdfContent, $filename)
    {
        try {
            return response()->json([
                'success'    => true,
                'pdf_base64' => base64_encode($pdfContent),
                'filename'   => $filename,
                'size'       => strlen($pdfContent),
                'message'    => 'PDF generated successfully as base64'
            ]);
        } catch (Exception $e) {
            Log::error('Base64 response failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed to create base64 response: ' . $e->getMessage()], 500);
        }
    }

    private function fixImagePaths(&$studentData)
    {
        foreach ($studentData as &$student) {
            if (isset($student['students']) && $student['students']->isNotEmpty() && $student['students']->first()->picture) {
                $student['student_image_path'] = $this->sanitizeImagePath($student['students']->first()->picture);
            } else {
                $student['student_image_path'] = public_path('storage/student_avatars/unnamed.jpg');
            }

            if (isset($student['schoolInfo'])) {
                $logoPath = $student['schoolInfo']->getLogoUrlAttribute();
                $student['school_logo_path'] = $this->sanitizeImagePath($logoPath);
            } else {
                $student['school_logo_path'] = public_path('storage/school_logos/default.jpg');
            }
        }
    }

    private function sanitizeImagePath($path)
    {
        if (empty($path)) {
            return null;
        }

        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
        $path = preg_replace('/^(http:\/\/|https:\/\/|\/\/)[^\/]+/', '', $path);
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        if (!preg_match('/^(storage|school_logos|student_avatars)/', $path)) {
            $path = 'storage/' . $path;
        }

        $fullPath = public_path($path);
        $fullPath = realpath($fullPath) ?: $fullPath;

        return file_exists($fullPath) ? $fullPath : null;
    }

    private function ensureDirectoriesExist()
    {
        $directories = [
            storage_path('app/temp'),
            storage_path('fonts'),
            storage_path('logs'),
            public_path('temp_pdfs'),
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }

    private function getTermName($termid)
    {
        $terms = [
            1 => 'First_Term',
            2 => 'Second_Term',
            3 => 'Third_Term',
        ];
        return $terms[$termid] ?? 'Unknown_Term';
    }

    private function validateStudentData($studentData): bool
    {
        if (empty($studentData)) {
            return false;
        }
        if (empty($studentData['students']) || !$studentData['students']) {
            return false;
        }
        if (!isset($studentData['scores'])) {
            return false;
        }
        return true;
    }

    public function index(Request $request): View|JsonResponse
    {
        $pagetitle  = "Student Terminal Report Management";
        $allstudents = new LengthAwarePaginator([], 0, 10);

        if ($request->filled('schoolclassid') && $request->filled('sessionid') &&
            $request->input('schoolclassid') !== 'ALL' && $request->input('sessionid') !== 'ALL'
        ) {
            $query = Studentclass::query()
                ->where('schoolclassid', $request->input('schoolclassid'))
                ->where('sessionid', $request->input('sessionid'))
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
                ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid');

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('studentRegistration.admissionNo', 'like', "%{$search}%")
                      ->orWhere('studentRegistration.firstname', 'like', "%{$search}%")
                      ->orWhere('studentRegistration.lastname', 'like', "%{$search}%")
                      ->orWhere('studentRegistration.othername', 'like', "%{$search}%");
                });
            }

            $allstudents = $query->select([
                'studentRegistration.admissionNo as admissionno',
                'studentRegistration.firstname as firstname',
                'studentRegistration.lastname as lastname',
                'studentRegistration.othername as othername',
                'studentRegistration.gender as gender',
                'studentRegistration.id as stid',
                'studentpicture.picture as picture',
                'studentclass.schoolclassid as schoolclassID',
                'studentclass.sessionid as sessionid',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm as schoolarm',
                'schoolsession.session as session',
            ])->latest('studentclass.created_at')->paginate(100);
        }

        $schoolsessions = Schoolsession::get();
        $schoolclasses  = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm']);

        if ($request->ajax()) {
            return response()->json([
                'tableBody'    => view('studentreports.partials.student_rows', compact('allstudents'))->render(),
                'pagination'   => $allstudents->links('pagination::bootstrap-5')->render(),
                'studentCount' => $allstudents->total(),
            ]);
        }

        return view('studentreports.index', compact('allstudents', 'schoolsessions', 'schoolclasses', 'pagetitle'));
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

    public function classBroadsheet($schoolclassid, $sessionid, $termid): View
    {
        $class     = Schoolclass::findOrFail($schoolclassid);
        $session   = Schoolsession::findOrFail($sessionid);
        $term      = $termid;
        $pagetitle = "Broadsheet for {$class->schoolclass} - {$session->session} - Term {$term}";

        return view('studentreports.broadsheet', compact('class', 'session', 'term', 'pagetitle'));
    }

    public function studentmockresult($id, $schoolclassid, $sessionid, $termid)
    {
        $pagetitle = "Student Mock Result";

        $metricsCalculated = $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);
        if (!$metricsCalculated) {
            Log::error('Failed to calculate class metrics for mock result', [
                'student_id'   => $id,
                'schoolclassid' => $schoolclassid,
                'sessionid'     => $sessionid,
                'termid'        => $termid,
            ]);
            return back()->with('error', 'Failed to calculate class metrics. Please try again.');
        }

        $data = $this->getStudentResultData($id, $schoolclassid, $sessionid, $termid);

        return view('studentreports.studentmockresult')->with($data)->with('pagetitle', $pagetitle);
    }
}
