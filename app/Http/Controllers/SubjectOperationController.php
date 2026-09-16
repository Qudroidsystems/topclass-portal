<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Student;
use App\Models\Schoolterm;
use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\Subjectclass;
use Illuminate\Http\Request;
use App\Models\Schoolsession;
use App\Models\Studentpicture;
use App\Models\SubjectTeacher;
use App\Models\BroadsheetsMock;
use App\Models\BroadsheetRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\BroadsheetRecordMock;
use App\Models\StudentSubjectRecord;
use App\Models\ArchiveScoreSnapshot;
use App\Models\SubjectRegistrationStatus;
use App\Models\SubjectUnregistrationArchive;
use Illuminate\Validation\ValidationException;

/**
 * SubjectOperationController (Project 1 — static score columns)
 *
 * Feature parity with the project 2 controller (snapshot archive, restore,
 * registered-classes overview, per-student subject counts) but WITHOUT the
 * dynamic Assessment / BroadsheetAssessmentScore / GPA-CGPA machinery.
 *
 * Scores in this project live as plain columns on `broadsheets`:
 *     ca1, ca2, ca3, exam, total, bf, cum, grade, remark
 *
 * Snapshot storage strategy
 * -------------------------
 * `archive_score_snapshots` is re-used as a generic key/value store:
 *   numeric columns -> score_type = TYPE_ASSESSMENT
 *                      assessment_id   = synthetic 1-based column index
 *                      assessment_name = uppercase column name  (CA1, EXAM …)
 *                      score           = the numeric value
 *   text columns    -> score_type = TYPE_SUB_ASSESSMENT
 *                      assessment_name     = uppercase column name (GRADE)
 *                      sub_assessment_name = the text value       ('A1')
 *                      score               = 0
 *
 * Restore maps the column name back and issues a targeted UPDATE, then hands
 * off to MyScoreSheetController to recompute class metrics and positions.
 */
class SubjectOperationController extends Controller
{
    /** Numeric score columns on `broadsheets`, in display order. */
    private const NUMERIC_SCORE_COLUMNS = ['ca1', 'ca2', 'ca3', 'exam', 'total', 'bf', 'cum'];

    /** Text columns on `broadsheets` captured alongside the numbers. */
    private const TEXT_SCORE_COLUMNS = ['grade', 'remark'];

    public function __construct()
    {
        $this->middleware(
            'permission:View subject-operation|Create subject-operation|Update subject-operation|Delete subject-operation',
            ['only' => [
                'index', 'subjectinfo', 'getSubjectTeachers', 'getSchoolInformation',
                'registeredClasses', 'getRegisteredClasses', 'getStudentSubjectCounts',
                'getArchivedRegistrations', 'getSnapshotDetail',
            ]]
        );

        $this->middleware(
            'permission:Create subject-operation',
            ['only' => ['store', 'batchRegister', 'restoreRegistration']]
        );

        $this->middleware(
            'permission:Delete subject-operation',
            ['only' => ['destroy', 'permanentlyDeleteArchive', 'permanentlyDeleteArchiveBatch']]
        );
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request): \Illuminate\View\View|\Illuminate\Http\Response
    {
        $pagetitle = "Subject Operation Management";

        $schoolclass = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id as id', 'schoolarm.arm as schoolarm', 'schoolclass.schoolclass as schoolclass'])
            ->orderBy('schoolclass.schoolclass')
            ->get();

        $schoolterms    = Schoolterm::all();
        $schoolsessions = Schoolsession::all();

        $staffs = User::whereHas('roles', function ($q) {
            $q->where('name', '!=', 'Student');
        })->get(['users.id as userid', 'users.name as name', 'users.avatar as avatar']);

        $students        = null;
        $subjectTeachers = null;

        if ($request->filled(['class_id', 'session_id']) &&
            $request->input('class_id') !== 'ALL' &&
            $request->input('session_id') !== 'ALL') {

            $subjectTeachers = SubjectTeacher::leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->leftJoin('subjectclass', 'subjectclass.subjectteacherid', '=', 'subjectteacher.id')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->where('subjectteacher.sessionid', $request->input('session_id'))
                ->where('subjectclass.schoolclassid', $request->input('class_id'))
                ->select([
                    'subjectteacher.id as id',
                    'subjectclass.id as subjectclassid',
                    'users.id as userid',
                    'users.name as staffname',
                    'users.avatar as avatar',
                    'subject.id as subjectid',
                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',
                    'schoolterm.id as termid',
                    'schoolterm.term as termname',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as sessionname',
                    'schoolclass.schoolclass as class_name',
                    'schoolarm.arm as arm_name',
                    'subjectteacher.updated_at',
                ])
                ->orderBy('subject.subject')
                ->get();

            $query = Student::leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
                ->leftJoin('studentclass', 'studentclass.studentid', '=', 'studentRegistration.id')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'studentclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm');

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('studentRegistration.admissionno', 'like', "%{$search}%")
                        ->orWhere('studentRegistration.firstname', 'like', "%{$search}%")
                        ->orWhere('studentRegistration.lastname', 'like', "%{$search}%");
                });
            }

            if (($gender = $request->input('gender')) && $gender !== 'ALL') {
                $query->where('studentRegistration.gender', $gender);
            }

            if (($admissionNo = $request->input('admissionno')) && $admissionNo !== 'ALL') {
                $query->where('studentRegistration.admissionno', $admissionNo);
            }

            $query->where('studentclass.schoolclassid', $request->input('class_id'))
                ->where('studentclass.sessionid', $request->input('session_id'))
                ->orderBy('studentRegistration.lastname')
                ->orderBy('studentRegistration.firstname');

            $students = $query->select([
                'studentRegistration.id as id',
                'studentRegistration.admissionno as admissionno',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.othername',
                'studentRegistration.gender',
                'studentRegistration.updated_at',
                'studentpicture.picture',
                'studentclass.studentid as studentid',
                'studentclass.schoolclassid as schoolclassid',
                'studentclass.sessionid',
                'schoolclass.schoolclass as class_name',
                'schoolarm.arm as arm_name',
            ])->paginate(100)->appends($request->query());
        }

        return view('subjectoperation.index', compact(
            'students', 'subjectTeachers', 'pagetitle', 'schoolclass', 'schoolterms', 'schoolsessions'
        ));
    }

    // =========================================================================
    // SUBJECT INFO — term now honoured instead of being hard-coded
    // =========================================================================

    public function subjectinfo(Request $request, $id, $schoolclassid, $termid, $sessionid): \Illuminate\View\View|\Illuminate\Http\JsonResponse
    {
        try {
            $pagetitle   = "Subject Operation Management";
            $studentdata = Student::where('id', $id)->get();

            if ($studentdata->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Student not found'], 404);
            }

            $studentpic = Studentpicture::where('studentid', $id)
                ->select(['studentid', 'picture as avatar'])
                ->get();

            $subjectclass = Subjectclass::query()
                ->where('subjectclass.schoolclassid', $schoolclassid)
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->where('schoolterm.id', $termid)
                ->where('schoolsession.id', $sessionid)
                ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('staffbioinfo', 'staffbioinfo.userid', '=', 'users.id')
                ->leftJoin('staffpicture', 'staffpicture.staffid', '=', 'users.id')
                ->groupBy([
                    'subject.id', 'users.id', 'staffbioinfo.title', 'users.name',
                    'staffpicture.picture', 'subject.subject', 'subject.subject_code',
                    'subjectclass.id', 'schoolterm.term', 'schoolterm.id',
                    'schoolsession.session', 'schoolsession.id',
                ])
                ->select([
                    'subject.id as subjectid', 'staffbioinfo.title', 'users.name',
                    'staffpicture.picture as picture', 'subject.subject',
                    'users.id as staffid', 'subject.subject_code as subjectcode',
                    'subjectclass.id as subjectclassid', 'schoolterm.term',
                    'schoolterm.id as termid', 'schoolsession.session',
                    'schoolsession.id as sessionid',
                ])
                ->orderBy('subject.subject')
                ->get();

            // One query for every registration this student holds this term/session
            $registeredKeys = SubjectRegistrationStatus::where('studentid', $id)
                ->where('termid', $termid)
                ->where('sessionid', $sessionid)
                ->get(['subjectclassid', 'staffid', 'broadsheetid'])
                ->keyBy(fn ($r) => $r->subjectclassid . '_' . $r->staffid);

            $subjectRegistrations = [];
            foreach ($subjectclass as $sc) {
                $hit = $registeredKeys->get($sc->subjectclassid . '_' . $sc->staffid);

                $subjectRegistrations[$sc->subjectid][$sc->staffid] = [
                    'subjectclassid' => $sc->subjectclassid,
                    'status' => $hit
                        ? ['status' => 'Registered',     'broadsheetid' => $hit->broadsheetid]
                        : ['status' => 'Not Registered', 'broadsheetid' => null],
                ];
            }

            $totalreg = Subjectclass::where('subjectclass.schoolclassid', $schoolclassid)
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->where('schoolterm.id', $termid)
                ->where('schoolsession.id', $sessionid)
                ->distinct('subjectteacher.subjectid')
                ->count('subjectteacher.subjectid');

            $regcount   = $registeredKeys->count();
            $noregcount = max($totalreg - $regcount, 0);

            $classname = Schoolclass::where('schoolclass.id', $schoolclassid)
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->select(['schoolclass.id', 'schoolclass.schoolclass as schoolclass', 'schoolarm.arm as arm'])
                ->get();

            $terms = Schoolterm::all();

            return view('subjectoperation.subjectinfo', compact(
                'studentpic', 'classname', 'subjectclass', 'subjectRegistrations',
                'studentdata', 'id', 'schoolclassid', 'termid', 'sessionid',
                'totalreg', 'regcount', 'noregcount', 'pagetitle', 'terms'
            ));

        } catch (\Exception $error) {
            Log::error('Error fetching subject info', [
                'student_id'    => $id,
                'schoolclassid' => $schoolclassid,
                'error'         => $error->getMessage(),
                'trace'         => $error->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch subject information: ' . $error->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // SUBJECT TEACHERS (AJAX)
    // =========================================================================

    public function getSubjectTeachers(Request $request): JsonResponse
    {
        if (!$request->ajax()) {
            return response()->json(['error' => 'Invalid request'], 400);
        }

        $classId   = $request->input('class_id');
        $termId    = $request->input('term_id');
        $sessionId = $request->input('session_id');

        if (!$classId || !$termId || !$sessionId ||
            $classId === 'ALL' || $termId === 'ALL' || $sessionId === 'ALL') {
            return response()->json(['error' => 'Missing required parameters'], 400);
        }

        $subjectTeachers = SubjectTeacher::leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
            ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->leftJoin('subjectclass', 'subjectclass.subjectteacherid', '=', 'subjectteacher.id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->where('subjectteacher.termid', $termId)
            ->where('subjectteacher.sessionid', $sessionId)
            ->where('subjectclass.schoolclassid', $classId)
            ->select([
                'subjectteacher.id as id',
                'subjectclass.id as subjectclassid',
                'users.id as userid',
                'users.name as staffname',
                'users.avatar as avatar',
                'subject.id as subjectid',
                'subject.subject as subjectname',
                'subject.subject_code as subjectcode',
                'schoolterm.id as termid',
                'schoolterm.term as termname',
                'schoolsession.id as sessionid',
                'schoolsession.session as sessionname',
                'schoolclass.schoolclass as class_name',
                'schoolarm.arm as arm_name',
            ])
            ->orderBy('subject.subject')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $subjectTeachers,
            'count'   => $subjectTeachers->count(),
        ]);
    }

    // =========================================================================
    // SCHOOL INFORMATION (for the print header)
    // =========================================================================

    public function getSchoolInformation(): JsonResponse
    {
        try {
            if (!class_exists(\App\Models\SchoolInformation::class)) {
                return response()->json(['success' => false, 'message' => 'School information is not configured.'], 404);
            }

            $schoolInfo = \App\Models\SchoolInformation::getActiveSchool();

            if (!$schoolInfo) {
                return response()->json(['success' => false, 'message' => 'School information not found'], 404);
            }

            return response()->json(['success' => true, 'data' => $schoolInfo]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // REGISTERED CLASSES — per term, per subject, with real student counts
    // =========================================================================

    public function registeredClasses(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'class_id'   => ['required', 'integer', 'exists:schoolclass,id'],
                'session_id' => ['required', 'integer', 'exists:schoolsession,id'],
                'term_id'    => ['nullable', 'integer', 'exists:schoolterm,id'],
            ]);

            $classInfo = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->where('schoolclass.id', $validated['class_id'])
                ->select([
                    'schoolclass.id as class_id',
                    'schoolclass.schoolclass as class_name',
                    'schoolarm.arm as arm_name',
                ])
                ->first();

            if (!$classInfo) {
                return response()->json(['success' => false, 'message' => 'Class not found.'], 404);
            }

            $sessionInfo = Schoolsession::find($validated['session_id']);

            $terms = !empty($validated['term_id'])
                ? Schoolterm::where('id', $validated['term_id'])->get()
                : Schoolterm::all();

            $processedData = [];

            foreach ($terms as $term) {
                $subjectRecords = Subjectclass::query()
                    ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                    ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                    ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                    ->where('subjectclass.schoolclassid', $validated['class_id'])
                    ->where('subjectteacher.sessionid', $validated['session_id'])
                    ->where('subjectteacher.termid', $term->id)
                    ->whereNotNull('subject.id')
                    ->select([
                        'subjectclass.id as subjectclass_id',
                        'subject.id as subject_id',
                        'subject.subject as subject_name',
                        'subject.subject_code as subject_code',
                        'users.id as teacher_id',
                        'users.name as teacher_name',
                    ])
                    ->orderBy('subject.subject', 'asc')
                    ->get();

                if ($subjectRecords->isEmpty()) {
                    continue;
                }

                $subjectsWithTeachers  = [];
                $subjectclassToSubject = [];

                foreach ($subjectRecords as $record) {
                    $subjectclassToSubject[$record->subjectclass_id] = $record->subject_id;
                    $key = $record->subject_id;

                    if (!isset($subjectsWithTeachers[$key])) {
                        $subjectsWithTeachers[$key] = [
                            'id'              => $record->subject_id,
                            'name'            => $record->subject_name,
                            'code'            => $record->subject_code,
                            'subjectclass_id' => $record->subjectclass_id,
                            'teachers'        => [],
                            'student_count'   => 0,
                        ];
                    }

                    if ($record->teacher_id && $record->teacher_name) {
                        $already = false;
                        foreach ($subjectsWithTeachers[$key]['teachers'] as $t) {
                            if ($t['id'] == $record->teacher_id) { $already = true; break; }
                        }
                        if (!$already) {
                            $subjectsWithTeachers[$key]['teachers'][] = [
                                'id'   => $record->teacher_id,
                                'name' => $record->teacher_name,
                            ];
                        }
                    }
                }

                $subjectclassIds = $subjectRecords->pluck('subjectclass_id')->unique()->toArray();

                $studentCountsRaw = SubjectRegistrationStatus::query()
                    ->whereIn('subjectclassid', $subjectclassIds)
                    ->where('sessionid', $validated['session_id'])
                    ->where('termid', $term->id)
                    ->select(['subjectclassid', DB::raw('COUNT(DISTINCT studentid) as student_count')])
                    ->groupBy('subjectclassid')
                    ->get();

                $subjectStudentCounts = [];
                foreach ($studentCountsRaw as $row) {
                    $subjectId = $subjectclassToSubject[$row->subjectclassid] ?? null;
                    if (!$subjectId) continue;
                    $subjectStudentCounts[$subjectId] = max(
                        $subjectStudentCounts[$subjectId] ?? 0,
                        (int) $row->student_count
                    );
                }

                foreach ($subjectsWithTeachers as $key => $subject) {
                    $subjectsWithTeachers[$key]['student_count'] = $subjectStudentCounts[$subject['id']] ?? 0;
                }

                $subjectsWithTeachers = array_values($subjectsWithTeachers);

                $totalStudents = SubjectRegistrationStatus::query()
                    ->whereIn('subjectclassid', $subjectclassIds)
                    ->where('sessionid', $validated['session_id'])
                    ->where('termid', $term->id)
                    ->distinct('studentid')
                    ->count('studentid');

                $processedData[] = [
                    'class_id'          => $classInfo->class_id,
                    'class_name'        => $classInfo->class_name,
                    'arm_name'          => $classInfo->arm_name ?? '',
                    'session_name'      => $sessionInfo->session ?? 'N/A',
                    'term_name'         => $term->term,
                    'term_id'           => $term->id,
                    'student_count'     => $totalStudents,
                    'subject_count'     => count($subjectsWithTeachers),
                    'subjects_teachers' => $subjectsWithTeachers,
                ];
            }

            if (empty($processedData)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No subjects found for this class and session. Assign subjects and teachers first.',
                ], 200);
            }

            return response()->json(['success' => true, 'data' => $processedData]);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid parameters.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error fetching registered classes', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PER-STUDENT SUBJECT COUNT — drives the PDF breakdown table
    // =========================================================================

    public function getStudentSubjectCounts(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'class_id'   => ['required', 'integer', 'exists:schoolclass,id'],
                'session_id' => ['required', 'integer', 'exists:schoolsession,id'],
                'term_id'    => ['nullable', 'integer', 'exists:schoolterm,id'],
            ]);

            $subjectclassQuery = Subjectclass::query()
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->where('subjectclass.schoolclassid', $validated['class_id'])
                ->where('subjectteacher.sessionid', $validated['session_id']);

            if (!empty($validated['term_id'])) {
                $subjectclassQuery->where('subjectteacher.termid', $validated['term_id']);
            }

            $subjectclassIds = $subjectclassQuery->pluck('subjectclass.id')->unique()->toArray();

            if (empty($subjectclassIds)) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $srsQuery = SubjectRegistrationStatus::query()
                ->whereIn('subjectclassid', $subjectclassIds)
                ->where('sessionid', $validated['session_id']);

            if (!empty($validated['term_id'])) {
                $srsQuery->where('termid', $validated['term_id']);
            }

            $perStudent = $srsQuery
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'subject_registration_status.studentid')
                ->select([
                    'subject_registration_status.studentid',
                    'studentRegistration.admissionno',
                    DB::raw("CONCAT(COALESCE(studentRegistration.lastname,''), ' ', COALESCE(studentRegistration.firstname,'')) as student_name"),
                    'studentRegistration.gender',
                    DB::raw('COUNT(DISTINCT subject_registration_status.subjectclassid) as subject_count'),
                ])
                ->groupBy([
                    'subject_registration_status.studentid',
                    'studentRegistration.admissionno',
                    'studentRegistration.lastname',
                    'studentRegistration.firstname',
                    'studentRegistration.gender',
                ])
                ->orderBy('studentRegistration.lastname')
                ->get();

            return response()->json(['success' => true, 'data' => $perStudent]);

        } catch (\Exception $e) {
            Log::error('Error fetching per-student subject counts', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // GET REGISTERED CLASSES (legacy aggregate endpoint)
    // =========================================================================

    public function getRegisteredClasses(Request $request): JsonResponse
    {
        try {
            $classId   = $request->input('class_id');
            $sessionId = $request->input('session_id');
            $termId    = $request->input('term_id');

            $query = Subjectclass::query()
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('student_subject_register_record', 'student_subject_register_record.subjectclassid', '=', 'subjectclass.id')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->whereNotNull('student_subject_register_record.studentId');

            if ($classId   && $classId   !== 'ALL') $query->where('subjectclass.schoolclassid', $classId);
            if ($sessionId && $sessionId !== 'ALL') $query->where('subjectteacher.sessionid', $sessionId);
            if ($termId    && $termId    !== 'ALL') $query->where('subjectteacher.termid', $termId);

            $registeredClasses = $query->select([
                'schoolclass.id as class_id',
                'schoolclass.schoolclass as class_name',
                'schoolarm.arm as arm_name',
                'schoolsession.session as session_name',
                'schoolterm.term as term_name',
                DB::raw('COUNT(DISTINCT student_subject_register_record.studentId) as student_count'),
                DB::raw('COUNT(DISTINCT subject.id) as subject_count'),
                DB::raw('GROUP_CONCAT(DISTINCT subject.subject ORDER BY subject.subject SEPARATOR ", ") as subjects'),
                DB::raw('GROUP_CONCAT(DISTINCT users.name ORDER BY users.name SEPARATOR ", ") as teachers'),
            ])
            ->groupBy([
                'schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm',
                'schoolsession.session', 'schoolterm.term',
            ])
            ->get();

            return response()->json(['success' => true, 'data' => $registeredClasses], 200);

        } catch (\Exception $e) {
            Log::error("Error fetching registered classes: {$e->getMessage()}");
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // STORE (single subject, tiered by volume)
    // =========================================================================

    public function store(Request $request): array
    {
        $validated = $request->validate([
            'studentid'      => ['required', 'array'],
            'studentid.*'    => ['required', 'exists:studentRegistration,id'],
            'subjectclassid' => ['required', 'exists:subjectclass,id'],
            'staffid'        => ['required', 'exists:users,id'],
            'termid'         => ['required', 'exists:schoolterm,id'],
            'sessionid'      => ['required', 'exists:schoolsession,id'],
        ]);

        $count = count($validated['studentid']);

        $result = $count <= 50
            ? $this->processIndividually($validated)
            : ($count <= 500 ? $this->processBatch($validated) : $this->processLargeDataset($validated));

        if (($result['success_count'] ?? 0) > 0) {
            $this->recalculateScoreMetrics(
                (int) $validated['subjectclassid'],
                (int) $validated['staffid'],
                (int) $validated['termid'],
                (int) $validated['sessionid']
            );
        }

        return $result;
    }

    // =========================================================================
    // BATCH REGISTER (many subjects at once)
    // =========================================================================

    public function batchRegister(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'studentids'                      => ['required', 'array'],
            'studentids.*'                    => ['required', 'exists:studentRegistration,id'],
            'subjectclasses'                  => ['required', 'array'],
            'subjectclasses.*.subjectclassid' => ['required', 'exists:subjectclass,id'],
            'subjectclasses.*.staffid'        => ['required', 'exists:users,id'],
            'subjectclasses.*.termid'         => ['required', 'exists:schoolterm,id'],
            'sessionid'                       => ['required', 'exists:schoolsession,id'],
        ]);

        $results      = [];
        $errors       = [];
        $successCount = 0;
        $touched      = [];

        try {
            DB::beginTransaction();

            foreach ($validated['subjectclasses'] as $subject) {
                $response = $this->processIndividually([
                    'studentid'      => $validated['studentids'],
                    'subjectclassid' => $subject['subjectclassid'],
                    'staffid'        => $subject['staffid'],
                    'termid'         => $subject['termid'],
                    'sessionid'      => $validated['sessionid'],
                ]);

                if ($response['success']) {
                    $successCount += $response['success_count'];
                    $touched[] = [
                        'subjectclassid' => (int) $subject['subjectclassid'],
                        'staffid'        => (int) $subject['staffid'],
                        'termid'         => (int) $subject['termid'],
                        'sessionid'      => (int) $validated['sessionid'],
                    ];
                } else {
                    $errors[] = [
                        'subjectclassid' => $subject['subjectclassid'],
                        'termid'         => $subject['termid'],
                        'message'        => $response['message'] ?? 'Error',
                        'details'        => $response['errors'] ?? [],
                    ];
                }

                $results[] = $response;
            }

            DB::commit();

            // Positions and class metrics are recomputed outside the transaction
            foreach ($touched as $t) {
                $this->recalculateScoreMetrics($t['subjectclassid'], $t['staffid'], $t['termid'], $t['sessionid']);
            }

            return response()->json([
                'success'       => empty($errors),
                'message'       => 'Batch registration completed.',
                'results'       => $results,
                'error_details' => $errors,
                'success_count' => $successCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch registration failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Batch registration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY — snapshot first, then remove the registration data
    // =========================================================================

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'studentids'                      => ['required', 'array'],
            'studentids.*'                    => ['required', 'exists:studentRegistration,id'],
            'subjectclasses'                  => ['required', 'array'],
            'subjectclasses.*.subjectclassid' => ['required', 'exists:subjectclass,id'],
            'subjectclasses.*.staffid'        => ['required', 'exists:users,id'],
            'subjectclasses.*.termid'         => ['required', 'exists:schoolterm,id'],
            'sessionid'                       => ['required', 'exists:schoolsession,id'],
            'snapshot_name'                   => ['nullable', 'string', 'max:191'],
            'snapshot_notes'                  => ['nullable', 'string', 'max:1000'],
        ]);

        if (empty($validated['snapshot_name'])) {
            $validated['snapshot_name'] = 'Unregistration — ' . now()->format('d M Y H:i');
        }

        $results              = [];
        $errors               = [];
        $unregisteredStudents = [];
        $skippedCount         = 0;
        $touched              = [];
        $unregisteredById     = Auth::id();

        try {
            DB::beginTransaction();

            foreach ($validated['subjectclasses'] as $subject) {
                $subjectclassid = (int) $subject['subjectclassid'];
                $staffid        = (int) $subject['staffid'];
                $termid         = (int) $subject['termid'];
                $sessionid      = (int) $validated['sessionid'];

                $subjectclass  = Subjectclass::findOrFail($subjectclassid);
                $subjectId     = $subjectclass->subjectid;
                $schoolclassId = $subjectclass->schoolclassid;

                $existingRegistrations = SubjectRegistrationStatus::where([
                    'subjectclassid' => $subjectclassid,
                    'termid'         => $termid,
                    'sessionid'      => $sessionid,
                    'staffid'        => $staffid,
                ])->whereIn('studentid', $validated['studentids'])
                    ->get()
                    ->keyBy('studentid');

                $studentsToProcess = array_values(array_intersect(
                    $validated['studentids'],
                    $existingRegistrations->keys()->toArray()
                ));

                $skippedCount += count(array_diff($validated['studentids'], $studentsToProcess));

                if (empty($studentsToProcess)) {
                    $errors[] = [
                        'subjectclassid' => $subjectclassid,
                        'termid'         => $termid,
                        'message'        => 'No students are registered for this subject.',
                    ];
                    continue;
                }

                $unregisteredStudents = array_unique(array_merge($unregisteredStudents, $studentsToProcess));
                $broadsheetRecordIds  = $existingRegistrations->pluck('broadsheetid')->filter()->toArray();

                // ── 1. Archive rows are written before anything is deleted ────
                $now         = now();
                $archiveRows = [];
                foreach ($studentsToProcess as $studentId) {
                    $reg = $existingRegistrations->get($studentId);
                    $archiveRows[] = [
                        'studentid'            => $studentId,
                        'subjectclassid'       => $subjectclassid,
                        'staffid'              => $staffid,
                        'termid'               => $termid,
                        'sessionid'            => $sessionid,
                        'subjectid'            => $subjectId,
                        'schoolclassid'        => $schoolclassId,
                        'broadsheet_record_id' => $reg?->broadsheetid,
                        'unregistered_by'      => $unregisteredById,
                        'snapshot_name'        => $validated['snapshot_name'],
                        'snapshot_notes'       => $validated['snapshot_notes'] ?? null,
                        'status'               => SubjectUnregistrationArchive::STATUS_ARCHIVED,
                        'unregistered_at'      => $now,
                        'created_at'           => $now,
                        'updated_at'           => $now,
                    ];
                }
                SubjectUnregistrationArchive::insertOrIgnore($archiveRows);

                // ── 2. Reload to get archive IDs ──────────────────────────────
                $createdArchives = SubjectUnregistrationArchive::whereIn('studentid', $studentsToProcess)
                    ->where('subjectclassid', $subjectclassid)
                    ->where('termid', $termid)
                    ->where('sessionid', $sessionid)
                    ->where('staffid', $staffid)
                    ->where('status', SubjectUnregistrationArchive::STATUS_ARCHIVED)
                    ->get()
                    ->keyBy('studentid');

                // ── 3. Capture the static score columns ───────────────────────
                $this->captureScoreSnapshots(
                    $createdArchives, $broadsheetRecordIds, $subjectclassid,
                    $subjectId, $schoolclassId, $sessionid, $termid, $staffid, $now
                );

                // ── 4. Mock rows for this term ────────────────────────────────
                $mockRecordIds = BroadsheetRecordMock::whereIn('student_id', $studentsToProcess)
                    ->where('subject_id', $subjectId)
                    ->where('schoolclass_id', $schoolclassId)
                    ->where('session_id', $sessionid)
                    ->pluck('id');

                if ($mockRecordIds->isNotEmpty()) {
                    BroadsheetsMock::whereIn('broadsheet_records_mock_id', $mockRecordIds)
                        ->where('subjectclass_id', $subjectclassid)
                        ->where('term_id', $termid)
                        ->where('staff_id', $staffid)
                        ->delete();
                }

                // ── 5. Broadsheets for this term ──────────────────────────────
                Broadsheets::whereIn('broadsheet_record_id', $broadsheetRecordIds)
                    ->where('term_id', $termid)
                    ->where('subjectclass_id', $subjectclassid)
                    ->delete();

                // ── 6. Drop parent records only when no term remains ──────────
                $orphanedRecordIds = collect($broadsheetRecordIds)
                    ->filter(fn ($recordId) => Broadsheets::where('broadsheet_record_id', $recordId)->doesntExist())
                    ->toArray();

                if (!empty($orphanedRecordIds)) {
                    BroadsheetRecord::whereIn('id', $orphanedRecordIds)->delete();
                }

                if ($mockRecordIds->isNotEmpty()) {
                    $orphanedMockIds = BroadsheetRecordMock::whereIn('id', $mockRecordIds)
                        ->get()
                        ->filter(fn ($m) => BroadsheetsMock::where('broadsheet_records_mock_id', $m->id)->doesntExist())
                        ->pluck('id')
                        ->toArray();

                    if (!empty($orphanedMockIds)) {
                        BroadsheetRecordMock::whereIn('id', $orphanedMockIds)->delete();
                    }
                }

                // ── 7. Registration rows ──────────────────────────────────────
                StudentSubjectRecord::whereIn('studentId', $studentsToProcess)
                    ->where('subjectclassid', $subjectclassid)
                    ->where('staffid', $staffid)
                    ->where('session', $sessionid)
                    ->delete();

                SubjectRegistrationStatus::whereIn('studentid', $studentsToProcess)
                    ->where('subjectclassid', $subjectclassid)
                    ->where('termid', $termid)
                    ->where('sessionid', $sessionid)
                    ->where('staffid', $staffid)
                    ->delete();

                $touched[] = [
                    'subjectclassid' => $subjectclassid,
                    'staffid'        => $staffid,
                    'termid'         => $termid,
                    'sessionid'      => $sessionid,
                    'schoolclassid'  => $schoolclassId,
                ];

                Log::info('Unregistered subjects', [
                    'subjectclassid' => $subjectclassid,
                    'termid'         => $termid,
                    'sessionid'      => $sessionid,
                    'student_count'  => count($studentsToProcess),
                    'snapshot_name'  => $validated['snapshot_name'],
                ]);

                $results[] = [
                    'subjectclassid'        => $subjectclassid,
                    'termid'                => $termid,
                    'message'               => 'Successfully unregistered ' . count($studentsToProcess) . ' students',
                    'students_unregistered' => $studentsToProcess,
                ];
            }

            $successCount = count($unregisteredStudents);

            if ($successCount === 0 && !empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'success'       => false,
                    'message'       => 'No students were unregistered.',
                    'error_details' => $errors,
                    'success_count' => 0,
                    'skipped_count' => $skippedCount,
                ], 422);
            }

            DB::commit();

            // Remaining students need fresh positions and class metrics
            foreach ($touched as $t) {
                $this->recalculateScoreMetrics(
                    $t['subjectclassid'], $t['staffid'], $t['termid'], $t['sessionid'], $t['schoolclassid']
                );
            }

            return response()->json([
                'success'       => empty($errors),
                'message'       => "Successfully unregistered {$successCount} student(s) from " . count($validated['subjectclasses']) . " subject(s).",
                'results'       => $results,
                'error_details' => $errors,
                'success_count' => $successCount,
                'skipped_count' => $skippedCount,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Batch unregistration failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Batch unregistration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // ARCHIVED REGISTRATIONS (snapshot cards)
    // =========================================================================

    public function getArchivedRegistrations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'class_id'   => ['required', 'integer', 'exists:schoolclass,id'],
            'session_id' => ['required', 'integer', 'exists:schoolsession,id'],
            'term_id'    => ['nullable', 'integer', 'exists:schoolterm,id'],
            'per_page'   => ['nullable', 'integer', 'in:20,50,100,150'],
        ]);

        try {
            $perPage = $request->input('per_page', 50);

            $query = SubjectUnregistrationArchive::query()
                ->where('subject_unregistration_archive.status', SubjectUnregistrationArchive::STATUS_ARCHIVED)
                ->where('subject_unregistration_archive.sessionid', $validated['session_id'])
                ->where('subject_unregistration_archive.schoolclassid', $validated['class_id'])
                ->leftJoin('subject', 'subject.id', '=', 'subject_unregistration_archive.subjectid')
                ->leftJoin('users as staff', 'staff.id', '=', 'subject_unregistration_archive.staffid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subject_unregistration_archive.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subject_unregistration_archive.sessionid')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'subject_unregistration_archive.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('users as actor', 'actor.id', '=', 'subject_unregistration_archive.unregistered_by')
                ->select([
                    DB::raw('MIN(subject_unregistration_archive.id) as archive_id'),
                    'subject_unregistration_archive.snapshot_name',
                    'subject_unregistration_archive.snapshot_notes',
                    'subject_unregistration_archive.subjectclassid',
                    'subject_unregistration_archive.termid',
                    'subject_unregistration_archive.sessionid',
                    'subject_unregistration_archive.subjectid',
                    'subject_unregistration_archive.schoolclassid',
                    'subject_unregistration_archive.staffid',
                    DB::raw('COUNT(DISTINCT subject_unregistration_archive.studentid) as student_count'),
                    DB::raw('MIN(subject_unregistration_archive.unregistered_at) as unregistered_at'),
                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',
                    'staff.name as staffname',
                    'schoolterm.term as termname',
                    'schoolsession.session as sessionname',
                    'schoolclass.schoolclass as class_name',
                    'schoolarm.arm as arm_name',
                    'actor.name as unregistered_by_name',
                ])
                ->groupBy([
                    'subject_unregistration_archive.snapshot_name',
                    'subject_unregistration_archive.snapshot_notes',
                    'subject_unregistration_archive.subjectclassid',
                    'subject_unregistration_archive.termid',
                    'subject_unregistration_archive.sessionid',
                    'subject_unregistration_archive.subjectid',
                    'subject_unregistration_archive.schoolclassid',
                    'subject_unregistration_archive.staffid',
                    'subject.subject',
                    'subject.subject_code',
                    'staff.name',
                    'schoolterm.term',
                    'schoolsession.session',
                    'schoolclass.schoolclass',
                    'schoolarm.arm',
                    'actor.name',
                ]);

            if (!empty($validated['term_id'])) {
                $query->where('subject_unregistration_archive.termid', $validated['term_id']);
            }

            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('subject_unregistration_archive.snapshot_name', 'like', "%{$search}%")
                      ->orWhere('subject.subject', 'like', "%{$search}%");
                });
            }

            $archived = $query->orderBy('unregistered_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data'    => $archived->items(),
                'meta'    => [
                    'current_page' => $archived->currentPage(),
                    'last_page'    => $archived->lastPage(),
                    'total'        => $archived->total(),
                    'per_page'     => $archived->perPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching archived registrations', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // SNAPSHOT DETAIL
    // =========================================================================

    public function getSnapshotDetail(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'snapshot_name'  => ['required', 'string'],
            'subjectclassid' => ['required', 'integer', 'exists:subjectclass,id'],
            'termid'         => ['required', 'integer', 'exists:schoolterm,id'],
            'sessionid'      => ['required', 'integer', 'exists:schoolsession,id'],
            'staffid'        => ['required', 'integer', 'exists:users,id'],
        ]);

        try {
            $archives = SubjectUnregistrationArchive::where([
                'snapshot_name'  => $validated['snapshot_name'],
                'subjectclassid' => $validated['subjectclassid'],
                'termid'         => $validated['termid'],
                'sessionid'      => $validated['sessionid'],
                'staffid'        => $validated['staffid'],
                'status'         => SubjectUnregistrationArchive::STATUS_ARCHIVED,
            ])
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'subject_unregistration_archive.studentid')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->select([
                'subject_unregistration_archive.id as archive_id',
                'subject_unregistration_archive.studentid',
                'subject_unregistration_archive.snapshot_name',
                'subject_unregistration_archive.snapshot_notes',
                'subject_unregistration_archive.unregistered_at',
                'studentRegistration.admissionno',
                'studentRegistration.firstname',
                'studentRegistration.lastname',
                'studentRegistration.othername',
                'studentRegistration.gender',
                'studentpicture.picture',
            ])
            ->orderBy('studentRegistration.lastname')
            ->get();

            if ($archives->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Snapshot not found or already actioned.'], 404);
            }

            $archiveIds = $archives->pluck('archive_id');

            $allSnapshots = ArchiveScoreSnapshot::whereIn('archive_id', $archiveIds)
                ->orderBy('student_id')
                ->orderBy('assessment_id')
                ->get();

            $grouped = $allSnapshots->groupBy('archive_id');

            // Headers come from EVERY snapshot row, not just the first student's —
            // a student with no captured scores no longer blanks out the table.
            $assessmentHeaders = $allSnapshots
                ->where('score_type', ArchiveScoreSnapshot::TYPE_ASSESSMENT)
                ->unique('assessment_id')
                ->sortBy('assessment_id')
                ->map(fn ($s) => [
                    'assessment_id'   => $s->assessment_id,
                    'assessment_name' => $s->assessment_name,
                ])
                ->values();

            $rows = $archives->map(function ($row) use ($grouped) {
                $scores = $grouped->get($row->archive_id, collect());

                return [
                    'archive_id'      => $row->archive_id,
                    'studentid'       => $row->studentid,
                    'admissionno'     => $row->admissionno,
                    'firstname'       => $row->firstname,
                    'lastname'        => $row->lastname,
                    'othername'       => $row->othername,
                    'gender'          => $row->gender,
                    'picture'         => $row->picture,
                    'snapshot_name'   => $row->snapshot_name,
                    'snapshot_notes'  => $row->snapshot_notes,
                    'unregistered_at' => $row->unregistered_at,
                    // numeric columns (CA1 … CUM)
                    'assessment_scores' => $scores
                        ->where('score_type', ArchiveScoreSnapshot::TYPE_ASSESSMENT)
                        ->map(fn ($s) => [
                            'assessment_id'   => $s->assessment_id,
                            'assessment_name' => $s->assessment_name,
                            'score'           => $s->score,
                        ])->values()->toArray(),
                    // text columns (GRADE, REMARK)
                    'text_scores' => $scores
                        ->where('score_type', ArchiveScoreSnapshot::TYPE_SUB_ASSESSMENT)
                        ->map(fn ($s) => [
                            'name'  => $s->assessment_name,
                            'value' => $s->sub_assessment_name,
                        ])->values()->toArray(),
                ];
            });

            return response()->json([
                'success'            => true,
                'rows'               => $rows,
                'assessment_headers' => $assessmentHeaders,
                'snapshot_name'      => $archives->first()->snapshot_name,
                'snapshot_notes'     => $archives->first()->snapshot_notes,
                'total_students'     => $archives->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching snapshot detail', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // RESTORE — re-register, put scores back, recompute positions
    // =========================================================================

    public function restoreRegistration(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'archive_ids'   => ['required', 'array'],
            'archive_ids.*' => ['required', 'integer', 'exists:subject_unregistration_archive,id'],
        ]);

        try {
            DB::beginTransaction();

            $archives = SubjectUnregistrationArchive::whereIn('id', $validated['archive_ids'])
                ->where('status', SubjectUnregistrationArchive::STATUS_ARCHIVED)
                ->get();

            if ($archives->isEmpty()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'No valid archived records found. They may have already been restored or deleted.',
                ], 422);
            }

            $groups        = $archives->groupBy(fn ($r) => $r->subjectclassid . '_' . $r->termid . '_' . $r->sessionid . '_' . $r->staffid);
            $totalRestored = 0;
            $errors        = [];
            $touched       = [];

            foreach ($groups as $groupArchives) {
                $first      = $groupArchives->first();
                $studentIds = $groupArchives->pluck('studentid')->unique()->toArray();

                $result = $this->processIndividually([
                    'studentid'      => $studentIds,
                    'subjectclassid' => $first->subjectclassid,
                    'staffid'        => $first->staffid,
                    'termid'         => $first->termid,
                    'sessionid'      => $first->sessionid,
                ]);

                if ($result['success'] || ($result['skipped_count'] ?? 0) > 0) {
                    $this->restoreScoresFromSnapshot($groupArchives, $first);

                    SubjectUnregistrationArchive::whereIn('id', $groupArchives->pluck('id')->toArray())
                        ->update([
                            'status'      => SubjectUnregistrationArchive::STATUS_RESTORED,
                            'actioned_at' => now(),
                            'updated_at'  => now(),
                        ]);

                    $totalRestored += $result['success_count'] ?? 0;

                    $touched[$first->subjectclassid . '_' . $first->termid . '_' . $first->sessionid . '_' . $first->staffid] = [
                        'subjectclassid' => (int) $first->subjectclassid,
                        'staffid'        => (int) $first->staffid,
                        'termid'         => (int) $first->termid,
                        'sessionid'      => (int) $first->sessionid,
                        'schoolclassid'  => (int) $first->schoolclassid,
                    ];
                } else {
                    $errors[] = [
                        'subjectclassid' => $first->subjectclassid,
                        'termid'         => $first->termid,
                        'message'        => $result['message'] ?? 'Unknown error',
                    ];
                }
            }

            DB::commit();

            // Restored scores change every remaining student's ranking
            foreach ($touched as $t) {
                $this->recalculateScoreMetrics(
                    $t['subjectclassid'], $t['staffid'], $t['termid'], $t['sessionid'], $t['schoolclassid']
                );
            }

            return response()->json([
                'success'        => empty($errors),
                'message'        => "Successfully restored {$totalRestored} registration(s).",
                'total_restored' => $totalRestored,
                'errors'         => $errors,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Restore registration failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // PERMANENT DELETE
    // =========================================================================

    public function permanentlyDeleteArchive(Request $request, int $archiveId): JsonResponse
    {
        try {
            $archive = SubjectUnregistrationArchive::where('id', $archiveId)
                ->where('status', SubjectUnregistrationArchive::STATUS_ARCHIVED)
                ->firstOrFail();

            // Score snapshots go with it via the CASCADE foreign key
            $archive->delete();

            return response()->json(['success' => true, 'message' => 'Archive record permanently deleted.']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Record not found or already actioned.'], 404);
        } catch (\Exception $e) {
            Log::error('Permanent delete failed', ['archive_id' => $archiveId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function permanentlyDeleteArchiveBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'archive_ids'   => ['required', 'array'],
            'archive_ids.*' => ['required', 'integer'],
        ]);

        try {
            $deleted = SubjectUnregistrationArchive::whereIn('id', $validated['archive_ids'])
                ->where('status', SubjectUnregistrationArchive::STATUS_ARCHIVED)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => "{$deleted} archive record(s) permanently deleted.",
                'deleted' => $deleted,
            ]);

        } catch (\Exception $e) {
            Log::error('Batch permanent delete failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // SNAPSHOT HELPERS
    // =========================================================================

    /** All captured columns in display order; index+1 becomes assessment_id. */
    private function scoreColumnOrder(): array
    {
        return array_merge(self::NUMERIC_SCORE_COLUMNS, self::TEXT_SCORE_COLUMNS);
    }

    private function captureScoreSnapshots(
        $createdArchives,
        array $broadsheetRecordIds,
        int $subjectclassid,
        int $subjectId,
        int $schoolclassId,
        int $sessionid,
        int $termid,
        int $staffid,
        $now
    ): void {
        try {
            if ($createdArchives->isEmpty() || empty($broadsheetRecordIds)) {
                return;
            }

            $recordToStudent = SubjectRegistrationStatus::whereIn('broadsheetid', $broadsheetRecordIds)
                ->where('subjectclassid', $subjectclassid)
                ->where('termid', $termid)
                ->where('sessionid', $sessionid)
                ->pluck('studentid', 'broadsheetid');

            $broadsheets = Broadsheets::whereIn('broadsheet_record_id', $broadsheetRecordIds)
                ->where('term_id', $termid)
                ->where('subjectclass_id', $subjectclassid)
                ->get()
                ->keyBy('broadsheet_record_id');

            if ($broadsheets->isEmpty()) {
                return;
            }

            $columnOrder = $this->scoreColumnOrder();
            $snapshots   = [];

            foreach ($broadsheetRecordIds as $broadsheetRecordId) {
                $broadsheet = $broadsheets->get($broadsheetRecordId);
                if (!$broadsheet) continue;

                $studentId = $recordToStudent->get($broadsheetRecordId);
                if (!$studentId) continue;

                $archive = $createdArchives->get($studentId);
                if (!$archive) continue;

                $base = [
                    'archive_id'      => $archive->id,
                    'broadsheet_id'   => $broadsheet->id,
                    'student_id'      => $studentId,
                    'subject_id'      => $subjectId,
                    'schoolclass_id'  => $schoolclassId,
                    'session_id'      => $sessionid,
                    'term_id'         => $termid,
                    'subjectclass_id' => $subjectclassid,
                    'staff_id'        => $staffid,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];

                foreach (self::NUMERIC_SCORE_COLUMNS as $col) {
                    $value = $broadsheet->$col;
                    if ($value === null) continue;

                    $snapshots[] = $base + [
                        'assessment_id'       => array_search($col, $columnOrder) + 1,
                        'assessment_name'     => strtoupper($col),
                        'sub_assessment_id'   => null,
                        'sub_assessment_name' => null,
                        'score'               => is_numeric($value) ? $value : 0,
                        'score_type'          => ArchiveScoreSnapshot::TYPE_ASSESSMENT,
                    ];
                }

                foreach (self::TEXT_SCORE_COLUMNS as $col) {
                    $value = $broadsheet->$col;
                    if ($value === null || $value === '') continue;

                    $index = array_search($col, $columnOrder) + 1;

                    $snapshots[] = $base + [
                        'assessment_id'       => $index,
                        'assessment_name'     => strtoupper($col),
                        'sub_assessment_id'   => $index,
                        'sub_assessment_name' => (string) $value,
                        'score'               => 0,
                        'score_type'          => ArchiveScoreSnapshot::TYPE_SUB_ASSESSMENT,
                    ];
                }
            }

            foreach (array_chunk($snapshots, 500) as $chunk) {
                ArchiveScoreSnapshot::insertOrIgnore($chunk);
            }

            Log::info('Score snapshots captured', [
                'archive_ids'    => $createdArchives->pluck('id')->toArray(),
                'snapshot_count' => count($snapshots),
            ]);

        } catch (\Exception $e) {
            Log::error('captureScoreSnapshots failed', ['error' => $e->getMessage()]);
        }
    }

    private function restoreScoresFromSnapshot($groupArchives, $first): void
    {
        try {
            $archiveIds = $groupArchives->pluck('id')->toArray();
            $snapshots  = ArchiveScoreSnapshot::whereIn('archive_id', $archiveIds)->get();

            if ($snapshots->isEmpty()) return;

            $studentIds = $groupArchives->pluck('studentid')->toArray();

            $registrations = SubjectRegistrationStatus::whereIn('studentid', $studentIds)
                ->where('subjectclassid', $first->subjectclassid)
                ->where('termid', $first->termid)
                ->where('sessionid', $first->sessionid)
                ->where('staffid', $first->staffid)
                ->pluck('broadsheetid', 'studentid');

            $broadsheets = Broadsheets::whereIn('broadsheet_record_id', $registrations->values()->toArray())
                ->where('term_id', $first->termid)
                ->where('subjectclass_id', $first->subjectclassid)
                ->pluck('id', 'broadsheet_record_id');

            $archiveToBroadsheetId = [];
            foreach ($groupArchives as $archive) {
                $bsRecordId = $registrations->get($archive->studentid);
                if (!$bsRecordId) continue;
                $bsId = $broadsheets->get($bsRecordId);
                if (!$bsId) continue;
                $archiveToBroadsheetId[$archive->id] = $bsId;
            }

            $allowed = $this->scoreColumnOrder();

            foreach ($snapshots->groupBy('archive_id') as $archiveId => $colSnapshots) {
                $broadsheetId = $archiveToBroadsheetId[$archiveId] ?? null;
                if (!$broadsheetId) continue;

                $updates = [];
                foreach ($colSnapshots as $snap) {
                    $col = strtolower((string) $snap->assessment_name);
                    if (!in_array($col, $allowed, true)) continue;

                    $updates[$col] = $snap->score_type === ArchiveScoreSnapshot::TYPE_SUB_ASSESSMENT
                        ? $snap->sub_assessment_name
                        : $snap->score;
                }

                if (!empty($updates)) {
                    $updates['updated_at'] = now();
                    Broadsheets::where('id', $broadsheetId)->update($updates);
                }
            }

            Log::info('Scores restored from snapshot', [
                'archive_ids'    => $archiveIds,
                'snapshot_count' => $snapshots->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('restoreScoresFromSnapshot failed', ['error' => $e->getMessage()]);
        }
    }

    // =========================================================================
    // POSITION / METRIC RECALCULATION
    // =========================================================================

    /**
     * Hands recalculation back to MyScoreSheetController so that cmin/cmax/avg
     * and subject_position_class stay consistent with the teacher scoresheet.
     * Always called AFTER the surrounding transaction has committed.
     */
    private function recalculateScoreMetrics(
        int $subjectclassid,
        int $staffid,
        int $termid,
        int $sessionid,
        ?int $schoolclassid = null
    ): void {
        try {
            $controller = app(MyScoreSheetController::class);

            foreach (['updateClassMetrics', 'updateSubjectPositions'] as $methodName) {
                if (!method_exists($controller, $methodName)) continue;
                $method = new \ReflectionMethod($controller, $methodName);
                $method->setAccessible(true);
                $method->invoke($controller, $subjectclassid, $staffid, $termid, $sessionid);
            }

            if ($schoolclassid && method_exists($controller, 'updateClassPositions')) {
                $method = new \ReflectionMethod($controller, 'updateClassPositions');
                $method->setAccessible(true);
                $method->invoke($controller, $schoolclassid, $termid, $sessionid);
            }

            Log::info('Score metrics recalculated', compact('subjectclassid', 'staffid', 'termid', 'sessionid', 'schoolclassid'));

        } catch (\Throwable $e) {
            Log::error('recalculateScoreMetrics failed', [
                'error'          => $e->getMessage(),
                'subjectclassid' => $subjectclassid,
                'termid'         => $termid,
                'sessionid'      => $sessionid,
            ]);
        }
    }

    // =========================================================================
    // REGISTRATION PROCESSORS
    // =========================================================================

    private function processIndividually(array $validated): array
    {
        $results      = [];
        $successCount = 0;
        $errors       = [];

        try {
            DB::beginTransaction();

            $subjectclass  = Subjectclass::findOrFail($validated['subjectclassid']);
            $subjectId     = $subjectclass->subjectid;
            $schoolclassId = $subjectclass->schoolclassid;

            $existingRegistrations = SubjectRegistrationStatus::where([
                'subjectclassid' => $validated['subjectclassid'],
                'termid'         => $validated['termid'],
                'sessionid'      => $validated['sessionid'],
            ])->whereIn('studentid', $validated['studentid'])
                ->pluck('studentid')
                ->toArray();

            $studentsToProcess = array_diff($validated['studentid'], $existingRegistrations);
            $skippedCount      = count($existingRegistrations);

            foreach ($existingRegistrations as $id) {
                $errors[] = "Student ID {$id} is already registered";
            }

            if (empty($studentsToProcess)) {
                DB::rollBack();
                return [
                    'success'       => false,
                    'message'       => 'All students are already registered for this subject.',
                    'errors'        => $errors,
                    'skipped_count' => $skippedCount,
                    'success_count' => 0,
                ];
            }

            foreach ($studentsToProcess as $studentId) {
                try {
                    $record = BroadsheetRecord::firstOrCreate([
                        'student_id'     => $studentId,
                        'subject_id'     => $subjectId,
                        'schoolclass_id' => $schoolclassId,
                        'session_id'     => $validated['sessionid'],
                    ]);

                    $recordmock = BroadsheetRecordMock::firstOrCreate([
                        'student_id'     => $studentId,
                        'subject_id'     => $subjectId,
                        'schoolclass_id' => $schoolclassId,
                        'session_id'     => $validated['sessionid'],
                    ]);

                    $this->createDependentRecords($record->id, $recordmock->id, $studentId, $validated);
                    $successCount++;
                    $results[] = "Successfully registered student ID {$studentId}";

                } catch (\Exception $e) {
                    Log::error("Error processing student {$studentId}", ['error' => $e->getMessage()]);
                    $errors[] = "Failed to register student ID {$studentId}: " . $e->getMessage();
                }
            }

            if ($successCount > 0) {
                DB::commit();
                return [
                    'success'       => true,
                    'message'       => "{$successCount} students registered successfully",
                    'method'        => 'individual',
                    'results'       => $results,
                    'errors'        => $errors,
                    'success_count' => $successCount,
                    'skipped_count' => $skippedCount,
                ];
            }

            DB::rollBack();
            return [
                'success'       => false,
                'message'       => 'No students were registered.',
                'errors'        => $errors,
                'skipped_count' => $skippedCount,
                'success_count' => 0,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Individual processing error', ['error' => $e->getMessage()]);
            return [
                'success'       => false,
                'message'       => 'Processing failed: ' . $e->getMessage(),
                'errors'        => [$e->getMessage()],
                'success_count' => 0,
            ];
        }
    }

    private function processBatch(array $validated): array
    {
        try {
            DB::beginTransaction();

            $subjectclass  = Subjectclass::findOrFail($validated['subjectclassid']);
            $subjectId     = $subjectclass->subjectid;
            $schoolclassId = $subjectclass->schoolclassid;
            $now           = now();

            $existing = SubjectRegistrationStatus::where([
                'subjectclassid' => $validated['subjectclassid'],
                'termid'         => $validated['termid'],
                'sessionid'      => $validated['sessionid'],
            ])->whereIn('studentid', $validated['studentid'])->pluck('studentid')->toArray();

            $toProcess    = array_diff($validated['studentid'], $existing);
            $skippedCount = count($existing);

            if (empty($toProcess)) {
                DB::rollBack();
                return ['success' => false, 'message' => 'All students are already registered.', 'skipped_count' => $skippedCount, 'success_count' => 0];
            }

            $bsRecords = $bsMockRecords = [];
            foreach ($toProcess as $sid) {
                $row = [
                    'student_id'     => $sid,
                    'subject_id'     => $subjectId,
                    'schoolclass_id' => $schoolclassId,
                    'session_id'     => $validated['sessionid'],
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
                $bsRecords[]     = $row;
                $bsMockRecords[] = $row;
            }

            BroadsheetRecord::insertOrIgnore($bsRecords);
            BroadsheetRecordMock::insertOrIgnore($bsMockRecords);

            $createdRecords     = BroadsheetRecord::where(['subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid']])->whereIn('student_id', $toProcess)->get()->keyBy('student_id');
            $createdRecordsMock = BroadsheetRecordMock::where(['subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid']])->whereIn('student_id', $toProcess)->get()->keyBy('student_id');

            $this->bulkCreateDependentRecords($createdRecords, $createdRecordsMock, $toProcess, $validated, $now);

            DB::commit();

            return [
                'success'       => true,
                'message'       => count($toProcess) . ' students registered',
                'method'        => 'batch',
                'success_count' => count($toProcess),
                'skipped_count' => $skippedCount,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Batch processing failed: ' . $e->getMessage(), 'errors' => [$e->getMessage()], 'success_count' => 0];
        }
    }

    private function processLargeDataset(array $validated): array
    {
        try {
            DB::beginTransaction();

            $subjectclass   = Subjectclass::findOrFail($validated['subjectclassid']);
            $subjectId      = $subjectclass->subjectid;
            $schoolclassId  = $subjectclass->schoolclassid;
            $totalProcessed = 0;
            $totalSkipped   = 0;

            foreach (array_chunk($validated['studentid'], 200) as $chunk) {
                $existing = SubjectRegistrationStatus::where([
                    'subjectclassid' => $validated['subjectclassid'],
                    'termid'         => $validated['termid'],
                    'sessionid'      => $validated['sessionid'],
                ])->whereIn('studentid', $chunk)->pluck('studentid')->toArray();

                $toProcess     = array_diff($chunk, $existing);
                $totalSkipped += count($existing);

                if (!empty($toProcess)) {
                    $this->processChunk($toProcess, $validated, $subjectId, $schoolclassId);
                    $totalProcessed += count($toProcess);
                }
            }

            DB::commit();

            return [
                'success'       => true,
                'message'       => "{$totalProcessed} students registered",
                'method'        => 'large_dataset',
                'success_count' => $totalProcessed,
                'skipped_count' => $totalSkipped,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return ['success' => false, 'message' => 'Large dataset processing failed: ' . $e->getMessage(), 'errors' => [$e->getMessage()], 'success_count' => 0];
        }
    }

    private function processChunk(array $students, array $validated, int $subjectId, int $schoolclassId): void
    {
        $now = now();
        $bsRecords = $bsMockRecords = [];

        foreach ($students as $sid) {
            $row = [
                'student_id'     => $sid,
                'subject_id'     => $subjectId,
                'schoolclass_id' => $schoolclassId,
                'session_id'     => $validated['sessionid'],
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
            $bsRecords[]     = $row;
            $bsMockRecords[] = $row;
        }

        BroadsheetRecord::insertOrIgnore($bsRecords);
        BroadsheetRecordMock::insertOrIgnore($bsMockRecords);

        $createdRecords     = BroadsheetRecord::where(['subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid']])->whereIn('student_id', $students)->get()->keyBy('student_id');
        $createdRecordsMock = BroadsheetRecordMock::where(['subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid']])->whereIn('student_id', $students)->get()->keyBy('student_id');

        $this->bulkCreateDependentRecords($createdRecords, $createdRecordsMock, $students, $validated, $now);
    }

    private function createDependentRecords(int $recordId, int $recordMockId, int $studentId, array $validated): void
    {
        Broadsheets::firstOrCreate(
            ['broadsheet_record_id' => $recordId, 'term_id' => $validated['termid'], 'subjectclass_id' => $validated['subjectclassid']],
            ['staff_id' => $validated['staffid']]
        );

        BroadsheetsMock::firstOrCreate(
            ['broadsheet_records_mock_id' => $recordMockId, 'term_id' => $validated['termid'], 'subjectclass_id' => $validated['subjectclassid']],
            ['staff_id' => $validated['staffid']]
        );

        SubjectRegistrationStatus::firstOrCreate(
            ['studentid' => $studentId, 'subjectclassid' => $validated['subjectclassid'], 'termid' => $validated['termid'], 'sessionid' => $validated['sessionid'], 'staffid' => $validated['staffid']],
            ['broadsheetid' => $recordId, 'Status' => 1]
        );

        StudentSubjectRecord::firstOrCreate([
            'studentId'      => $studentId,
            'subjectclassid' => $validated['subjectclassid'],
            'staffid'        => $validated['staffid'],
            'session'        => $validated['sessionid'],
        ]);
    }

    private function bulkCreateDependentRecords($createdRecords, $createdRecordsMock, array $students, array $validated, $now): void
    {
        $broadsheets = $broadsheetsMock = $subjectRegs = $studentSubjectRecs = [];

        foreach ($students as $sid) {
            $r  = $createdRecords->get($sid);
            $rm = $createdRecordsMock->get($sid);
            if (!$r || !$rm) continue;

            $broadsheets[]        = ['broadsheet_record_id' => $r->id, 'term_id' => $validated['termid'], 'subjectclass_id' => $validated['subjectclassid'], 'staff_id' => $validated['staffid'], 'created_at' => $now, 'updated_at' => $now];
            $broadsheetsMock[]    = ['broadsheet_records_mock_id' => $rm->id, 'term_id' => $validated['termid'], 'subjectclass_id' => $validated['subjectclassid'], 'staff_id' => $validated['staffid'], 'created_at' => $now, 'updated_at' => $now];
            $subjectRegs[]        = ['studentid' => $sid, 'subjectclassid' => $validated['subjectclassid'], 'staffid' => $validated['staffid'], 'termid' => $validated['termid'], 'sessionid' => $validated['sessionid'], 'broadsheetid' => $r->id, 'Status' => 1, 'created_at' => $now, 'updated_at' => $now];
            $studentSubjectRecs[] = ['studentId' => $sid, 'subjectclassid' => $validated['subjectclassid'], 'staffid' => $validated['staffid'], 'session' => $validated['sessionid'], 'created_at' => $now, 'updated_at' => $now];
        }

        if (!empty($broadsheets))        Broadsheets::insertOrIgnore($broadsheets);
        if (!empty($broadsheetsMock))    BroadsheetsMock::insertOrIgnore($broadsheetsMock);
        if (!empty($subjectRegs))        SubjectRegistrationStatus::insertOrIgnore($subjectRegs);
        if (!empty($studentSubjectRecs)) StudentSubjectRecord::insertOrIgnore($studentSubjectRecs);
    }
}