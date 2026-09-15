<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Subject;
use Illuminate\View\View;
use App\Models\Schoolterm;
use App\Models\Schoolclass;
use App\Models\Studentclass;
use Illuminate\Http\Request;
use App\Models\Schoolsession;
use App\Models\StudentPicture;
use App\Models\BroadsheetsMock;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\SchoolInformation;
use Illuminate\Http\JsonResponse;
use App\Models\StudentRegistration;
use Illuminate\Support\Facades\Log;
use App\Models\BroadsheetRecordsMock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use App\Models\Studentpersonalityprofile;
use Illuminate\Validation\ValidationException;
use Illuminate\Pagination\LengthAwarePaginator;

class ViewStudentMockReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View student-mock-report', ['only' => [
            'index', 'registeredClasses', 'classBroadsheet', 'studentmockresult',
            'exportStudentMockResultPdf', 'exportClassMockResultsPdf', 'calculateGradePreview'
        ]]);
    }

    /**
     * Format a number as an ordinal string (e.g., 1st, 2nd, 3rd, 4th).
     *
     * @param int $number
     * @return string
     */
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
     * Calculate junior grade based on score.
     *
     * @param float $score
     * @return string
     */
    protected function calculateJuniorGrade($score)
    {
        if ($score >= 70 && $score <= 100) {
            return 'A';
        } elseif ($score >= 60) {
            return 'B';
        } elseif ($score >= 50) {
            return 'C';
        } elseif ($score >= 40) {
            return 'D';
        }
        return 'F';
    }

    /**
     * Get default grade based on score.
     *
     * @param float $score
     * @return string
     */
    protected function getDefaultGrade($score)
    {
        return $this->calculateJuniorGrade($score);
    }

    /**
     * Get remark based on grade.
     *
     * @param string $grade
     * @return string
     */
    protected function getRemark($grade)
    {
        $remarks = [
            'A' => 'Excellent',
            'B' => 'Very Good',
            'C' => 'Good',
            'D' => 'Pass',
            'F' => 'Fail',
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

    /**
     * Calculate subject positions and class averages for the entire class (all arms) for each subject in mock results.
     *
     * @param int $schoolclassid
     * @param int $sessionid
     * @param int $termid
     * @return void
     */
    protected function calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid)
    {
        $cacheKey = "mock_class_metrics_{$schoolclassid}_{$sessionid}_{$termid}";
        if (Cache::has($cacheKey)) {
            Log::info('Using cached mock class metrics', [
                'cache_key' => $cacheKey,
                'schoolclassid' => $schoolclassid,
                'sessionid' => $sessionid,
                'termid' => $termid,
            ]);
            return;
        }

        $schoolclass = Schoolclass::with('classcategory')->where('id', $schoolclassid)->first(['id', 'schoolclass', 'classcategoryid']);
        if (!$schoolclass) {
            Log::warning('Schoolclass not found', [
                'schoolclassid' => $schoolclassid,
                'sessionid' => $sessionid,
                'termid' => $termid,
            ]);
            return;
        }
        $className = $schoolclass->schoolclass;
        $isSenior = $schoolclass->classcategory ? $schoolclass->classcategory->is_senior : false;

        $classIds = Schoolclass::where('schoolclass', $className)
            ->pluck('id')
            ->toArray();

        if (empty($classIds)) {
            Log::warning('No schoolclass IDs found for class name', [
                'class_name' => $className,
                'schoolclassid' => $schoolclassid,
                'sessionid' => $sessionid,
                'termid' => $termid,
            ]);
            return;
        }

        $students = Studentclass::whereIn('schoolclassid', $classIds)
            ->where('sessionid', $sessionid)
            ->pluck('studentId')
            ->toArray();

        if (empty($students)) {
            Log::warning('No students found for class', [
                'class_name' => $className,
                'schoolclassids' => $classIds,
                'sessionid' => $sessionid,
                'termid' => $termid,
            ]);
            return;
        }

        $broadsheets = BroadsheetsMock::whereIn('broadsheet_records_mock.student_id', $students)
            ->where('broadsheetmock.term_id', $termid)
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->whereIn('broadsheet_records_mock.schoolclass_id', $classIds)
            ->join('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->join('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
            ->join('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records_mock.student_id')
            ->select([
                'broadsheetmock.id',
                'broadsheet_records_mock.student_id',
                'broadsheet_records_mock.subject_id',
                'subject.subject as subject_name',
                'studentRegistration.admissionNo as admission_no',
                'broadsheetmock.total',
                'broadsheetmock.subject_position_class',
                'broadsheetmock.avg',
                'broadsheetmock.grade',
                'broadsheetmock.remark',
            ])
            ->get();

        if ($broadsheets->isEmpty()) {
            Log::warning('No broadsheet mock records found for class', [
                'class_name' => $className,
                'schoolclassids' => $classIds,
                'sessionid' => $sessionid,
                'termid' => $termid,
            ]);
            return;
        }

        $subjectGroups = $broadsheets->groupBy('subject_id');

        foreach ($subjectGroups as $subjectId => $subjectRecords) {
            $subjectName = $subjectRecords->first()->subject_name;
            $validRecords = $subjectRecords->filter(function ($record) {
                return $record->total != 0;
            });
            $totalScores = $validRecords->sum('total');
            $studentCount = $validRecords->count();
            $classAvg = $studentCount > 0 ? round($totalScores / $studentCount, 1) : 0;

            $sortedRecords = $validRecords->sortByDesc('total')->values();
            $rank = 0;
            $lastTotal = null;
            $lastPosition = 0;
            $positionMap = [];

            foreach ($sortedRecords as $record) {
                $rank++;
                if ($lastTotal !== null && $record->total == $lastTotal) {
                    $positionMap[$record->id] = $lastPosition;
                } else {
                    $lastPosition = $rank;
                    $lastTotal = $record->total;
                    $positionMap[$record->id] = $lastPosition;
                }
            }

            foreach ($subjectRecords as $record) {
                $newPosition = $record->total == 0 ? '-' : ($positionMap[$record->id] ?? null);
                if ($newPosition !== '-') {
                    $newPosition = $this->formatOrdinal($newPosition);
                }

                $grade = $record->total == 0 ? '-' : (
                    $isSenior && $schoolclass->classcategory
                        ? $schoolclass->classcategory->calculateGrade($record->total)
                        : $this->calculateJuniorGrade($record->total)
                );
                $remark = $this->getRemark($grade);

                if (
                    $record->avg != $classAvg ||
                    $record->subject_position_class != $newPosition ||
                    $record->grade != $grade ||
                    $record->remark != $remark
                ) {
                    BroadsheetsMock::where('id', $record->id)->update([
                        'avg' => $classAvg,
                        'subject_position_class' => $newPosition,
                        'grade' => $grade,
                        'remark' => $remark,
                    ]);

                    Log::info('Updated broadsheet mock metrics', [
                        'broadsheet_id' => $record->id,
                        'student_id' => $record->student_id,
                        'admission_no' => $record->admission_no,
                        'subject_id' => $subjectId,
                        'subject_name' => $subjectName,
                        'class_avg' => $classAvg,
                        'subject_position_class' => $newPosition,
                        'grade' => $grade,
                        'remark' => $remark,
                        'class_name' => $className,
                        'total' => $record->total,
                    ]);
                }
            }

            Log::info('Calculated metrics for subject (mock)', [
                'subject_id' => $subjectId,
                'subject_name' => $subjectName,
                'class_name' => $className,
                'schoolclassids' => $classIds,
                'sessionid' => $sessionid,
                'termid' => $termid,
                'class_avg' => $classAvg,
                'student_count' => $studentCount,
                'total_scores' => $totalScores,
            ]);
        }

        Cache::put($cacheKey, true, now()->addHours(1));

        Log::info('Completed class metrics calculation (mock)', [
            'class_name' => $className,
            'schoolclassids' => $classIds,
            'sessionid' => $sessionid,
            'termid' => $termid,
            'total_subjects' => $subjectGroups->count(),
            'total_students' => count($students),
        ]);
    }

    /**
     * Fetch student mock result data for a specific student, class, session, and term.
     *
     * @param int $id
     * @param int $schoolclassid
     * @param int $sessionid
     * @param int $termid
     * @return array
     */
    private function getStudentMockResultData($id, $schoolclassid, $sessionid, $termid)
    {
        try {
            if (!is_numeric($id) || !is_numeric($schoolclassid) || !is_numeric($sessionid) || !is_numeric($termid)) {
                Log::error('Invalid parameters in getStudentMockResultData', [
                    'student_id' => $id,
                    'schoolclassid' => $schoolclassid,
                    'sessionid' => $sessionid,
                    'termid' => $termid,
                ]);
                return [];
            }

            // Get student basic info
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
                ])->get();

            if ($students->isEmpty()) {
                Log::warning('No active student found for ID', [
                    'student_id' => $id,
                ]);
                return [];
            }

            // Calculate class metrics first
            $this->calculateClassPositionsAndAverages($schoolclassid, $sessionid, $termid);

            // Get mock scores
            $mockScores = BroadsheetsMock::where('broadsheet_records_mock.student_id', $id)
                ->where('broadsheetmock.term_id', $termid)
                ->where('broadsheet_records_mock.session_id', $sessionid)
                ->where('broadsheet_records_mock.schoolclass_id', $schoolclassid)
                ->join('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
                ->join('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
                ->orderBy('subject.subject')
                ->select([
                    'subject.id as subject_id',
                    'subject.subject as subject_name',
                    'subject.subject_code',
                    'broadsheetmock.exam',
                    'broadsheetmock.total',
                    'broadsheetmock.grade',
                    'broadsheetmock.remark',
                    'broadsheetmock.subject_position_class as position',
                    'broadsheetmock.avg as class_average',
                ])->get();

            // Log for debugging
            Log::info('Mock scores retrieved', [
                'student_id' => $id,
                'scores_count' => $mockScores->count(),
            ]);

            // Get personality profile
            $studentpp = Studentpersonalityprofile::where('studentid', $id)
                ->where('schoolclassid', $schoolclassid)
                ->where('sessionid', $sessionid)
                ->where('termid', $termid)
                ->first();

            // Get school class info
            $schoolclass = Schoolclass::with('armRelation')->find($schoolclassid);
            if (!$schoolclass) {
                $schoolclass = (object)[
                    'schoolclass' => 'N/A',
                    'armRelation' => (object)['arm' => 'N/A'],
                    'classcategoryid' => null
                ];
            }

            $schoolterm = Schoolterm::where('id', $termid)->value('term') ?? 'N/A';
            $schoolsession = Schoolsession::where('id', $sessionid)->value('session') ?? 'N/A';

            $numberOfStudents = Studentclass::whereIn('schoolclassid',
                Schoolclass::where('schoolclass', $schoolclass->schoolclass ?? 'N/A')->pluck('id'))
                ->where('sessionid', $sessionid)
                ->count();

            $schoolInfo = SchoolInformation::getActiveSchool() ?? (object)[
                'school_name' => 'TOPCLASS COLLEGE',
                'school_motto' => 'Developing the total child',
                'school_address' => '39, Okegbala Street, Ondo.',
                'school_website' => 'https://topclasscollege.ng',
                'school_phone' => '+234806 770 6684',
                'school_email' => 'info@topclasscollege.ng',
                'date_school_opened' => null,
                'no_of_times_school_opened' => null,
                'date_next_term_begins' => null,
                'getLogoUrlAttribute' => function() {
                    return public_path('storage/school_logos/default.jpg');
                }
            ];

            return [
                'students' => $students,
                'studentpp' => collect([$studentpp]),
                'mockScores' => $mockScores,
                'studentid' => $id,
                'schoolclassid' => $schoolclassid,
                'sessionid' => $sessionid,
                'termid' => $termid,
                'schoolclass' => $schoolclass,
                'schoolterm' => $schoolterm,
                'schoolsession' => $schoolsession,
                'numberOfStudents' => $numberOfStudents,
                'schoolInfo' => $schoolInfo
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching student mock result data', [
                'student_id' => $id,
                'schoolclassid' => $schoolclassid,
                'sessionid' => $sessionid,
                'termid' => $termid,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return [];
        }
    }

    /**
     * Display the student list with filtering options.
     *
     * @param Request $request
     * @return View|JsonResponse
     */
    public function index(Request $request): View|JsonResponse
    {
        $pagetitle = "Student Mock Report Management";
        $current = "Current";

        $allstudents = new LengthAwarePaginator([], 0, 10);

        if ($request->filled('schoolclassid') && $request->filled('sessionid') && $request->input('schoolclassid') !== 'ALL' && $request->input('sessionid') !== 'ALL') {
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
        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get(['schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm']);
        $schoolterms = Schoolterm::all(['id', 'term']);

        if ($request->ajax()) {
            return response()->json([
                'tableBody' => view('studentmockreports.partials.student_rows', compact('allstudents'))->render(),
                'pagination' => $allstudents->links('pagination::bootstrap-5')->render(),
                'studentCount' => $allstudents->total(),
            ]);
        }

        return view('studentmockreports.index', compact('allstudents', 'schoolsessions', 'schoolclasses', 'schoolterms', 'pagetitle'));
    }

    /**
     * Fetch registered classes for a session.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function registeredClasses(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'class_id' => 'required|numeric|exists:schoolclass,id',
                'session_id' => 'required|numeric|exists:schoolsession,id',
            ]);

            $classId = $request->query('class_id');
            $sessionId = $request->query('session_id');

            $classes = Studentclass::query()
                ->join('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->join('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
                ->where('schoolclass.id', $classId)
                ->where('schoolsession.id', $sessionId)
                ->where('schoolsession.status', 'Current')
                ->groupBy('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm', 'schoolsession.session')
                ->selectRaw('
                    schoolclass.schoolclass as class_name,
                    schoolarm.arm as arm_name,
                    schoolsession.session as session_name,
                    COUNT(DISTINCT studentclass.studentId) as student_count
                ')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $classes
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error fetching registered classes', [
                'class_id' => $request->query('class_id'),
                'session_id' => $request->query('session_id'),
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch registered classes.'
            ], 500);
        }
    }

    /**
     * Display class broadsheet.
     *
     * @param Request $request
     * @param int $schoolclassid
     * @param int $sessionid
     * @param int $termid
     * @return View
     */
    public function classBroadsheet(Request $request, $schoolclassid, $sessionid, $termid): View
    {
        try {
            $request->validate([
                'schoolclassid' => 'required|numeric|exists:schoolclass,id',
                'sessionid' => 'required|numeric|exists:schoolsession,id',
                'termid' => 'required|numeric|exists:schoolterm,id',
            ]);

            $class = Schoolclass::findOrFail($schoolclassid);
            $session = Schoolsession::findOrFail($sessionid);
            $term = Schoolterm::where('id', $termid)->value('term') ?? 'Unknown Term';
            $pagetitle = "Mock Broadsheet for {$class->schoolclass} - {$session->session} - {$term}";

            $data = [
                'class' => $class,
                'session' => $session,
                'term' => $term,
                'pagetitle' => $pagetitle
            ];

            return view('studentreports.broadsheet_mock', $data);
        } catch (ValidationException $e) {
            Log::error('Validation failed for class broadsheet', [
                'schoolclassid' => $schoolclassid,
                'sessionid' => $sessionid,
                'termid' => $termid,
                'errors' => $e->errors(),
            ]);
            return abort(422, 'Validation failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Error displaying class broadsheet', [
                'schoolclassid' => $schoolclassid,
                'sessionid' => $sessionid,
                'termid' => $termid,
                'error' => $e->getMessage(),
            ]);
            return abort(500, 'Failed to display broadsheet.');
        }
    }

    /**
     * Export a single student's mock result as a PDF.
     *
     * @param Request $request
     * @param int $id
     * @param int $schoolclassid
     * @param int $sessionid
     * @param int $termid
     * @return \Illuminate\Http\Response
     */
    public function exportStudentMockResultPdf(Request $request, $id, $schoolclassid, $sessionid, $termid)
    {
        try {
            $request->validate([
                'id' => 'required|numeric|exists:studentRegistration,id',
                'schoolclassid' => 'required|numeric|exists:schoolclass,id',
                'sessionid' => 'required|numeric|exists:schoolsession,id',
                'termid' => 'required|numeric|exists:schoolterm,id',
            ]);

            ini_set('max_execution_time', 600);
            ini_set('memory_limit', '1024M');

            Log::info('Generating single student mock PDF', [
                'student_id' => $id,
                'schoolclassid' => $schoolclassid,
                'sessionid' => $sessionid,
                'termid' => $termid,
            ]);

            $data = $this->getStudentMockResultData($id, $schoolclassid, $sessionid, $termid);

            if (empty($data) || empty($data['students']) || $data['students']->isEmpty()) {
                Log::error('No valid student data for mock PDF generation', [
                    'student_id' => $id,
                    'schoolclassid' => $schoolclassid,
                    'sessionid' => $sessionid,
                    'termid' => $termid,
                ]);
                return back()->with('error', 'No student data found for the provided parameters.');
            }

            $this->fixImagePaths([$data]);

            $student = $data['students']->first();
            $studentName = $student ? $student->fname . '_' . $student->lastname : 'Student';
            $filename = 'Mock_Terminal_Report_' . $studentName . '_' . $data['schoolsession'] . '_Term_' . $data['termid'] . '.pdf';

            $pdf = Pdf::loadView('studentreports.studentmockresult_pdf', ['data' => $data])
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'dpi' => 150,
                    'defaultFont' => 'DejaVu Sans',
                    'isRemoteEnabled' => false,
                    'isHtml5ParserEnabled' => true,
                    'isFontSubsettingEnabled' => true,
                    'isPhpEnabled' => false,
                    'chroot' => [public_path(), storage_path('app/public')],
                    'fontCache' => storage_path('fonts/'),
                    'logOutputFile' => storage_path('logs/dompdf.log'),
                    'debugCss' => config('app.debug', false),
                    'debugLayout' => config('app.debug', false),
                    'debugKeepTemp' => config('app.debug', false),
                ]);

            return $pdf->download($filename);
        } catch (ValidationException $e) {
            Log::error('Validation failed for single student mock PDF', [
                'student_id' => $id,
                'schoolclassid' => $schoolclassid,
                'sessionid' => $sessionid,
                'termid' => $termid,
                'errors' => $e->errors(),
            ]);
            return back()->with('error', 'Validation failed: ' . $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Single Student Mock PDF Export Error', [
                'student_id' => $id,
                'schoolclassid' => $schoolclassid,
                'sessionid' => $sessionid,
                'termid' => $termid,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return back()->with('error', 'Failed to generate mock PDF: ' . $e->getMessage());
        }
    }

    /**
     * Export the entire class's mock results as a PDF.
     *
     * @param Request $request
     * @return JsonResponse|\Illuminate\Http\Response
     */
    public function exportClassMockResultsPdf(Request $request)
    {
        try {
            ini_set('max_execution_time', 1200);
            ini_set('memory_limit', '2048M');

            $request->validate([
                'schoolclassid' => 'required|numeric|exists:schoolclass,id',
                'sessionid' => 'required|numeric|exists:schoolsession,id',
                'termid' => 'required|numeric|exists:schoolterm,id',
                'studentIds' => 'nullable|array',
                'studentIds.*' => 'numeric|exists:studentRegistration,id',
                'response_method' => 'nullable|in:base64,inline,download,chunked,save_and_redirect',
            ]);

            $schoolclassid = $request->input('schoolclassid');
            $sessionid = $request->input('sessionid');
            $termid = $request->input('termid');
            $studentIds = $request->input('studentIds', []);

            Log::info('Starting class mock results PDF generation', [
                'schoolclassid' => $schoolclassid,
                'sessionid' => $sessionid,
                'termid' => $termid,
                'studentIds' => $studentIds,
            ]);

            $query = Studentclass::where('schoolclassid', $schoolclassid)
                ->where('sessionid', $sessionid)
                ->join('studentRegistration', 'studentRegistration.id', '=', 'studentclass.studentId')
                ->join('schoolsession', 'schoolsession.id', '=', 'studentclass.sessionid')
                ->select('studentRegistration.id', 'studentRegistration.firstname', 'studentRegistration.lastname')
                ->orderBy('studentRegistration.lastname', 'asc')
                ->orderBy('studentRegistration.firstname', 'asc');

            if (!empty($studentIds)) {
                $query->whereIn('studentRegistration.id', $studentIds);
            }

            $students = $query->get();

            if ($students->isEmpty()) {
                Log::warning('No students found for class', [
                    'schoolclassid' => $schoolclassid,
                    'sessionid' => $sessionid
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'No students found for the selected class and session.'
                ], 404);
            }

            Log::info('Processing students for mock PDF', ['student_count' => $students->count()]);

            $allStudentData = [];
            $processedStudents = 0;
            $skippedStudents = 0;

            foreach ($students as $student) {
                try {
                    $studentData = $this->getStudentMockResultData($student->id, $schoolclassid, $sessionid, $termid);
                    // Validate that we have at least student data (scores can be empty)
                    if (!empty($studentData) && !empty($studentData['students']) && $studentData['students']->isNotEmpty()) {
                        $allStudentData[] = $studentData;
                        $processedStudents++;
                        Log::info('Successfully processed student mock data', [
                            'student_id' => $student->id,
                            'student_name' => $student->firstname . ' ' . $student->lastname,
                            'scores_count' => $studentData['mockScores']->count()
                        ]);
                    } else {
                        $skippedStudents++;
                        Log::warning('Skipping student due to invalid mock data', [
                            'student_id' => $student->id,
                            'student_name' => $student->firstname . ' ' . $student->lastname,
                        ]);
                    }
                } catch (\Exception $e) {
                    $skippedStudents++;
                    Log::error('Error processing student mock data', [
                        'student_id' => $student->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (empty($allStudentData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid student mock data found for PDF generation. Please ensure students have mock scores entered.'
                ], 404);
            }

            Log::info('Student mock data collection completed', [
                'processed' => $processedStudents,
                'skipped' => $skippedStudents,
                'total' => $students->count()
            ]);

            $this->fixImagePaths($allStudentData);

            $schoolclass = Schoolclass::where('id', $schoolclassid)->with('armRelation')->first(['schoolclass', 'arm']);
            $schoolsession = Schoolsession::where('id', $sessionid)->value('session') ?? 'N/A';
            $term = Schoolterm::where('id', $termid)->value('term') ?? 'Unknown Term';
            $className = $schoolclass ? ($schoolclass->schoolclass . ($schoolclass->armRelation ? $schoolclass->armRelation->arm : '')) : 'Class';
            $filename = 'Class_Mock_Results_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $className) . '_' .
                        preg_replace('/[^A-Za-z0-9_-]/', '_', $schoolsession) . '_' . $term . '.pdf';

            Log::info('Preparing mock PDF data', [
                'filename' => $filename,
                'class_name' => $className,
                'session' => $schoolsession,
                'term' => $term
            ]);

            $viewName = 'studentmockreports.class_mock_results_pdf';
            if (!view()->exists($viewName)) {
                Log::error('Mock PDF view not found', ['view' => $viewName]);
                return response()->json([
                    'success' => false,
                    'message' => 'PDF template view not found: ' . $viewName
                ], 500);
            }

            $viewData = [
                'allStudentData' => $allStudentData,
                'metadata' => [
                    'class_name' => $className,
                    'session' => $schoolsession,
                    'term' => $term,
                    'generation_date' => now()->format('Y-m-d H:i:s'),
                    'student_count' => count($allStudentData)
                ]
            ];

            $this->ensureDirectoriesExist();

            $pdf = Pdf::loadView($viewName, $viewData)
                ->setPaper('A4', 'portrait')
                ->setOptions([
                    'dpi' => 96,
                    'defaultFont' => 'DejaVu Sans',
                    'isRemoteEnabled' => true,
                    'isHtml5ParserEnabled' => true,
                    'isFontSubsettingEnabled' => true,
                    'isPhpEnabled' => false,
                    'chroot' => [public_path(), storage_path()],
                    'tempDir' => storage_path('app/temp/'),
                    'fontCache' => storage_path('fonts/'),
                    'logOutputFile' => storage_path('logs/dompdf.log'),
                    'isJavascriptEnabled' => false,
                    'enable_css_float' => true,
                    'debugLayout' => false,
                    'debugCss' => false,
                    'debugKeepTemp' => false,
                ]);

            $pdfContent = $pdf->output();

            if (empty($pdfContent) || !str_starts_with($pdfContent, '%PDF')) {
                Log::error('Invalid or empty mock PDF content', [
                    'content_start' => substr($pdfContent, 0, 100)
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or empty mock PDF content generated',
                    'error_code' => 'INVALID_PDF_CONTENT'
                ], 500);
            }

            $responseMethod = $request->input('response_method', 'base64');

            switch ($responseMethod) {
                case 'save_and_redirect':
                    return $this->saveAndRedirectResponse($pdfContent, $filename);
                case 'base64':
                    return $this->base64Response($pdfContent, $filename);
                case 'chunked':
                    return $this->chunkedResponse($pdfContent, $filename);
                case 'download':
                    return $this->downloadResponse($pdfContent, $filename);
                case 'inline':
                    return $this->inlineResponse($pdfContent, $filename);
                default:
                    return $this->base64Response($pdfContent, $filename);
            }
        } catch (ValidationException $e) {
            Log::error('Validation failed for class mock PDF', [
                'schoolclassid' => $request->input('schoolclassid'),
                'sessionid' => $request->input('sessionid'),
                'termid' => $request->input('termid'),
                'errors' => $e->errors(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Class Mock PDF Export Error', [
                'schoolclassid' => $request->input('schoolclassid') ?? 'N/A',
                'sessionid' => $request->input('sessionid') ?? 'N/A',
                'termid' => $request->input('termid') ?? 'N/A',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate mock PDF: ' . $e->getMessage(),
                'error_code' => 'PDF_EXPORT_FAILED'
            ], 500);
        }
    }

    /**
     * Calculate grade preview based on total score.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calculateGradePreview(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'schoolclass_id' => 'required|numeric|exists:schoolclass,id',
                'total' => 'required|numeric|min:0|max:100',
            ]);

            $schoolclass = Schoolclass::with('classcategory')->findOrFail($request->schoolclass_id);
            $grade = $schoolclass->classcategory
                ? $schoolclass->classcategory->calculateGrade($request->total)
                : $this->getDefaultGrade($request->total);
            $remark = $this->getRemark($grade);

            return response()->json([
                'success' => true,
                'grade' => $grade,
                'remark' => $remark
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage(),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error calculating grade preview', [
                'schoolclass_id' => $request->schoolclass_id,
                'total' => $request->total,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate grade preview.'
            ], 500);
        }
    }

    /**
     * Send inline PDF response.
     *
     * @param string $pdfContent
     * @param string $filename
     * @return \Illuminate\Http\Response|JsonResponse
     */
    private function inlineResponse($pdfContent, $filename)
    {
        Log::info('Sending inline mock PDF response', ['size' => strlen($pdfContent)]);

        try {
            while (ob_get_level()) {
                ob_end_clean();
            }

            if (headers_sent($headerFile, $headerLine)) {
                Log::error('Headers already sent', [
                    'file' => $headerFile,
                    'line' => $headerLine
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Headers already sent. Cannot deliver mock PDF directly.',
                    'error_code' => 'HEADERS_ALREADY_SENT'
                ], 500);
            }

            return response($pdfContent, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $filename . '"')
                ->header('Content-Length', strlen($pdfContent))
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
        } catch (\Exception $e) {
            Log::error('Inline mock response failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send inline mock response: ' . $e->getMessage(),
                'error_code' => 'INLINE_RESPONSE_FAILED'
            ], 500);
        }
    }

    /**
     * Send download PDF response.
     *
     * @param string $pdfContent
     * @param string $filename
     * @return \Illuminate\Http\Response|JsonResponse
     */
    private function downloadResponse($pdfContent, $filename)
    {
        Log::info('Sending download mock PDF response', ['size' => strlen($pdfContent)]);

        try {
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
                'Content-Length' => strlen($pdfContent),
            ]);
        } catch (\Exception $e) {
            Log::error('Download mock response failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send download mock response: ' . $e->getMessage(),
                'error_code' => 'DOWNLOAD_RESPONSE_FAILED'
            ], 500);
        }
    }

    /**
     * Save PDF and return a redirect URL.
     *
     * @param string $pdfContent
     * @param string $filename
     * @return JsonResponse
     */
    private function saveAndRedirectResponse($pdfContent, $filename)
    {
        Log::info('Saving mock PDF and returning URL');

        try {
            $publicPath = public_path('temp_pdfs');
            if (!file_exists($publicPath)) {
                mkdir($publicPath, 0755, true);
            }

            $filePath = $publicPath . '/' . $filename;
            file_put_contents($filePath, $pdfContent);

            $publicUrl = url('temp_pdfs/' . $filename);

            Log::info('Mock PDF saved successfully', [
                'file_path' => $filePath,
                'public_url' => $publicUrl,
                'file_size' => filesize($filePath)
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Mock PDF generated successfully',
                'pdf_url' => $publicUrl,
                'filename' => $filename,
                'size' => strlen($pdfContent)
            ]);
        } catch (\Exception $e) {
            Log::error('Save and redirect mock failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to save mock PDF: ' . $e->getMessage(),
                'error_code' => 'SAVE_RESPONSE_FAILED'
            ], 500);
        }
    }

    /**
     * Send base64-encoded PDF response.
     *
     * @param string $pdfContent
     * @param string $filename
     * @return JsonResponse
     */
    private function base64Response($pdfContent, $filename)
    {
        Log::info('Sending base64 mock PDF response');

        try {
            return response()->json([
                'success' => true,
                'pdf_base64' => base64_encode($pdfContent),
                'filename' => $filename,
                'size' => strlen($pdfContent),
                'message' => 'Mock PDF generated successfully as base64'
            ]);
        } catch (\Exception $e) {
            Log::error('Base64 mock response failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create base64 mock response: ' . $e->getMessage(),
                'error_code' => 'BASE64_RESPONSE_FAILED'
            ], 500);
        }
    }

    /**
     * Send chunked PDF response.
     *
     * @param string $pdfContent
     * @param string $filename
     * @return \Illuminate\Http\Response|JsonResponse
     */
    private function chunkedResponse($pdfContent, $filename)
    {
        Log::info('Sending chunked mock PDF response', ['size' => strlen($pdfContent)]);

        try {
            return response()->stream(function() use ($pdfContent) {
                $chunkSize = 8192;
                $length = strlen($pdfContent);
                $offset = 0;

                while ($offset < $length) {
                    echo substr($pdfContent, $offset, $chunkSize);
                    $offset += $chunkSize;
                    if (ob_get_level()) {
                        ob_flush();
                    }
                    flush();
                }
            }, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Content-Length' => strlen($pdfContent),
                'Transfer-Encoding' => 'chunked',
            ]);
        } catch (\Exception $e) {
            Log::error('Chunked mock response failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to send chunked mock response: ' . $e->getMessage(),
                'error_code' => 'CHUNKED_RESPONSE_FAILED'
            ], 500);
        }
    }

    /**
     * Fix image paths for PDF rendering.
     *
     * @param array &$studentData
     * @return void
     */
    private function fixImagePaths(&$studentData)
    {
        foreach ($studentData as &$student) {
            if (isset($student['students']) && $student['students']->isNotEmpty() && $student['students']->first()->picture) {
                $student['student_image_path'] = $this->sanitizeImagePath($student['students']->first()->picture);
                Log::info('Student image path set', [
                    'student_id' => $student['students']->first()->id,
                    'path' => $student['student_image_path'],
                    'exists' => file_exists($student['student_image_path'])
                ]);
            } else {
                $student['student_image_path'] = public_path('storage/student_avatars/unnamed.jpg');
                Log::info('Using default student image', ['path' => $student['student_image_path']]);
            }

            if (isset($student['schoolInfo'])) {
                $logoPath = $student['schoolInfo']->getLogoUrlAttribute();
                $student['school_logo_path'] = $this->sanitizeImagePath($logoPath);
                Log::info('School logo path set', [
                    'path' => $student['school_logo_path'],
                    'exists' => file_exists($student['school_logo_path'])
                ]);
            } else {
                $student['school_logo_path'] = public_path('storage/school_logos/default.jpg');
                Log::info('Using default school logo', ['path' => $student['school_logo_path']]);
            }
        }
    }

    /**
     * Sanitize image path for PDF rendering.
     *
     * @param string $path
     * @return string|null
     */
    private function sanitizeImagePath($path)
    {
        if (empty($path)) {
            Log::warning('Empty image path provided');
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

        if (file_exists($fullPath)) {
            Log::info('Sanitized image path', ['original' => $path, 'sanitized' => $fullPath]);
            return $fullPath;
        }

        Log::warning('Image file does not exist', ['path' => $fullPath]);
        return null;
    }

    /**
     * Ensure required directories exist for PDF generation.
     *
     * @return void
     */
    private function ensureDirectoriesExist()
    {
        $directories = [
            storage_path('app/temp'),
            storage_path('fonts'),
            storage_path('logs'),
            public_path('temp_pdfs')
        ];

        foreach ($directories as $dir) {
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
                Log::info('Created directory', ['path' => $dir]);
            }
        }
    }

    /**
     * Display the student's mock result for a specific class, session, and term.
     *
     * @param Request $request
     * @param int $id
     * @param int $schoolclassid
     * @param int $sessionid
     * @param int $termid
     * @return View
     */
    public function studentmockresult(Request $request, $id, $schoolclassid, $sessionid, $termid): View
    {
        try {
            $request->validate([
                'id' => 'required|numeric|exists:studentRegistration,id',
                'schoolclassid' => 'required|numeric|exists:schoolclass,id',
                'sessionid' => 'required|numeric|exists:schoolsession,id',
                'termid' => 'required|numeric|exists:schoolterm,id',
            ]);

            $pagetitle = "Student Mock Result";
            $data = $this->getStudentMockResultData($id, $schoolclassid, $sessionid, $termid);

            if (empty($data) || empty($data['students']) || $data['students']->isEmpty()) {
                Log::warning('No valid student data for mock result display', [
                    'student_id' => $id,
                    'schoolclassid' => $schoolclassid,
                    'sessionid' => $sessionid,
                    'termid' => $termid,
                ]);
                return view('studentreports.studentmockresult', ['pagetitle' => $pagetitle, 'error' => 'No student data found.']);
            }

            return view('studentreports.studentmockresult')->with($data)->with('pagetitle', $pagetitle);
        } catch (ValidationException $e) {
            Log::error('Validation failed for student mock result', [
                'student_id' => $id,
                'schoolclassid' => $schoolclassid,
                'sessionid' => $sessionid,
                'termid' => $termid,
                'errors' => $e->errors(),
            ]);
            return view('studentreports.studentmockresult', ['pagetitle' => 'Student Mock Result', 'error' => 'Validation failed: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Error displaying student mock result', [
                'student_id' => $id,
                'schoolclassid' => $schoolclassid,
                'sessionid' => $sessionid,
                'termid' => $termid,
                'error' => $e->getMessage(),
            ]);
            return view('studentreports.studentmockresult', ['pagetitle' => 'Student Mock Result', 'error' => 'Failed to display result.']);
        }
    }
}
