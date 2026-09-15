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
use App\Models\BroadsheetRecordMock;
use App\Models\StudentSubjectRecord;
use App\Models\ArchiveScoreSnapshot;
use App\Models\SubjectRegistrationStatus;
use App\Models\SubjectUnregistrationArchive;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class SubjectOperationController extends Controller
{
    public function __construct()
    {
        $this->middleware(
            'permission:View subject-operation|Create subject-operation|Update subject-operation|Delete subject-operation',
            ['only' => ['index', 'subjectinfo', 'getRegisteredClasses', 'getArchivedRegistrations', 'getSnapshotDetail']]
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

            if ($gender = $request->input('gender')) {
                if ($gender !== 'ALL') {
                    $query->where('studentRegistration.gender', $gender);
                }
            }

            if ($admissionNo = $request->input('admissionno')) {
                if ($admissionNo !== 'ALL') {
                    $query->where('studentRegistration.admissionno', $admissionNo);
                }
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
    // SUBJECT INFO
    // =========================================================================

    public function subjectinfo(Request $request, $id, $schoolclassid, $termid, $sessionid): \Illuminate\View\View|\Illuminate\Http\JsonResponse
    {
        $current = "Current";

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
                ->where('schoolterm.id', 2)
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
                ->get();

            $subjectRegistrations = [];
            foreach ($subjectclass as $sc) {
                $subjectRegistrations[$sc->subjectid][$sc->staffid] = [
                    'subjectclassid' => $sc->subjectclassid,
                    'status' => StudentSubjectRecord::where([
                        'studentId'      => $id,
                        'subjectclassid' => $sc->subjectclassid,
                        'staffid'        => $sc->staffid,
                        'session'        => $sessionid,
                    ])->exists()
                        ? [
                            'status'       => 'Registered',
                            'broadsheetid' => SubjectRegistrationStatus::where([
                                'studentid'      => $id,
                                'subjectclassid' => $sc->subjectclassid,
                                'staffid'        => $sc->staffid,
                            ])->value('broadsheetid'),
                          ]
                        : ['status' => 'Not Registered', 'broadsheetid' => null],
                ];
            }

            $totalreg = Subjectclass::where('subjectclass.schoolclassid', $schoolclassid)
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->where('schoolterm.id', 2)
                ->where('schoolsession.id', $sessionid)
                ->distinct('subjectteacher.subjectid')
                ->count('subjectteacher.subjectid');

            $regcount = StudentSubjectRecord::where('student_subject_register_record.studentId', $id)
                ->leftJoin('subjectclass', 'subjectclass.id', '=', 'student_subject_register_record.subjectclassid')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'student_subject_register_record.session')
                ->where('schoolterm.id', 2)
                ->where('schoolsession.status', $current)
                ->count();

            $noregcount = $totalreg - $regcount;

            $classname = Schoolclass::where('schoolclass.id', $schoolclassid)
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->select(['schoolclass.id', 'schoolclass.schoolclass as schoolclass', 'schoolarm.arm as arm'])
                ->get();

            $terms = Schoolterm::all();

            return view('subjectoperation.subjectinfo', compact(
                'studentpic', 'classname', 'subjectclass', 'subjectRegistrations',
                'studentdata', 'id', 'termid', 'sessionid', 'totalreg',
                'regcount', 'noregcount', 'pagetitle', 'terms'
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
    // SUBJECT TEACHERS AJAX
    // =========================================================================

    public function getSubjectTeachers(Request $request)
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
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $subjectTeachers,
            'count'   => $subjectTeachers->count(),
        ]);
    }

    // =========================================================================
    // STORE (REGISTER — single subject)
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

        if ($count <= 50) {
            return $this->processIndividually($validated);
        } elseif ($count <= 500) {
            return $this->processBatch($validated);
        } else {
            return $this->processLargeDataset($validated);
        }
    }

    // =========================================================================
    // BATCH REGISTER (multiple subjects at once)
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

            return response()->json([
                'success'       => empty($errors),
                'message'       => 'Batch registration completed.',
                'results'       => $results,
                'error_details' => $errors,
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
    // DESTROY — saves snapshot + hard-deletes registration data
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
            // Supplied by the "Name this snapshot" modal — nullable so direct API
            // calls without a name don't fail; a sensible default is applied below.
            'snapshot_name'  => ['nullable', 'string', 'max:191'],
            'snapshot_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if (empty($validated['snapshot_name'])) {
            $validated['snapshot_name'] = 'Unregistration — ' . now()->format('d M Y H:i');
        }

        $results              = [];
        $errors               = [];
        $unregisteredStudents = [];
        $skippedCount         = 0;
        $unregisteredById     = Auth::id();

        try {
            DB::beginTransaction();

            foreach ($validated['subjectclasses'] as $subject) {
                $subjectclassid = $subject['subjectclassid'];
                $staffid        = $subject['staffid'];
                $termid         = $subject['termid'];
                $sessionid      = $validated['sessionid'];

                $subjectclass  = Subjectclass::findOrFail($subjectclassid);
                $subjectId     = $subjectclass->subjectid;
                $schoolclassId = $subjectclass->schoolclassid;

                // ── Find registered students ──────────────────────────────────
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

                // ── Broadsheet record IDs ─────────────────────────────────────
                $broadsheetRecordIds = $existingRegistrations->pluck('broadsheetid')->filter()->toArray();

                // ── Step 1: Write archive rows BEFORE deleting anything ────────
                $now         = now();
                $archiveRows = [];
                foreach ($studentsToProcess as $studentId) {
                    $reg           = $existingRegistrations->get($studentId);
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

                // ── Step 2: Reload archive rows to get their IDs ──────────────
                $createdArchives = SubjectUnregistrationArchive::whereIn('studentid', $studentsToProcess)
                    ->where('subjectclassid', $subjectclassid)
                    ->where('termid', $termid)
                    ->where('sessionid', $sessionid)
                    ->where('staffid', $staffid)
                    ->where('status', SubjectUnregistrationArchive::STATUS_ARCHIVED)
                    ->get()
                    ->keyBy('studentid');

                // ── Step 3: Capture score snapshots ───────────────────────────
                $this->captureScoreSnapshots(
                    $createdArchives,
                    $studentsToProcess,
                    $broadsheetRecordIds,
                    $subjectclassid,
                    $subjectId,
                    $schoolclassId,
                    $sessionid,
                    $termid,
                    $staffid,
                    $now
                );

                // ── Step 4: Get broadsheet.id list (term-specific) ────────────
                $broadsheetSheetIds = Broadsheets::whereIn('broadsheet_record_id', $broadsheetRecordIds)
                    ->where('term_id', $termid)
                    ->where('subjectclass_id', $subjectclassid)
                    ->pluck('id');

                // ── Step 5: Get mock record IDs ───────────────────────────────
                $mockRecordIds = BroadsheetRecordMock::whereIn('student_id', $studentsToProcess)
                    ->where('subject_id', $subjectId)
                    ->where('schoolclass_id', $schoolclassId)
                    ->where('session_id', $sessionid)
                    ->pluck('id');

                // ── Step 6: Delete BroadsheetsMock for this term ──────────────
                if ($mockRecordIds->isNotEmpty()) {
                    BroadsheetsMock::whereIn('broadsheet_records_mock_id', $mockRecordIds)
                        ->where('subjectclass_id', $subjectclassid)
                        ->where('term_id', $termid)
                        ->where('staff_id', $staffid)
                        ->delete();
                }

                // ── Step 7: Delete Broadsheets for this term ──────────────────
                Broadsheets::whereIn('broadsheet_record_id', $broadsheetRecordIds)
                    ->where('term_id', $termid)
                    ->where('subjectclass_id', $subjectclassid)
                    ->delete();

                // ── Step 8: Delete BroadsheetRecord only if no other term ──────
                $orphanedRecordIds = collect($broadsheetRecordIds)->filter(function ($recordId) {
                    return Broadsheets::where('broadsheet_record_id', $recordId)->doesntExist();
                })->toArray();

                if (!empty($orphanedRecordIds)) {
                    BroadsheetRecord::whereIn('id', $orphanedRecordIds)->delete();
                }

                // ── Step 9: Delete BroadsheetRecordMock only if no other term ──
                if ($mockRecordIds->isNotEmpty()) {
                    $orphanedMockIds = BroadsheetRecordMock::whereIn('id', $mockRecordIds)
                        ->get()
                        ->filter(fn($m) => BroadsheetsMock::where('broadsheet_records_mock_id', $m->id)->doesntExist())
                        ->pluck('id')
                        ->toArray();

                    if (!empty($orphanedMockIds)) {
                        BroadsheetRecordMock::whereIn('id', $orphanedMockIds)->delete();
                    }
                }

                // ── Step 10: Delete StudentSubjectRecord ──────────────────────
                StudentSubjectRecord::whereIn('studentId', $studentsToProcess)
                    ->where('subjectclassid', $subjectclassid)
                    ->where('staffid', $staffid)
                    ->where('session', $sessionid)
                    ->delete();

                // ── Step 11: Delete SubjectRegistrationStatus ─────────────────
                SubjectRegistrationStatus::whereIn('studentid', $studentsToProcess)
                    ->where('subjectclassid', $subjectclassid)
                    ->where('termid', $termid)
                    ->where('sessionid', $sessionid)
                    ->where('staffid', $staffid)
                    ->delete();

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
    // CAPTURE SCORE SNAPSHOTS  (private — called from destroy)
    // =========================================================================

    /**
     * This project stores scores directly as columns on the broadsheets table
     * (ca1, ca2, ca3, exam, total, bf, cum, grade …) rather than in a separate
     * assessment-scores pivot.  We capture every numeric/grade column as a
     * single 'assessment' snapshot row per broadsheet, keyed by column name.
     */
    private function captureScoreSnapshots(
        $createdArchives,
        array $studentsToProcess,
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

            // Map broadsheet_record_id → student_id
            $recordToStudent = SubjectRegistrationStatus::whereIn('broadsheetid', $broadsheetRecordIds)
                ->where('subjectclassid', $subjectclassid)
                ->where('termid', $termid)
                ->where('sessionid', $sessionid)
                ->pluck('studentid', 'broadsheetid');

            // Broadsheet rows for this term/subjectclass
            $broadsheets = Broadsheets::whereIn('broadsheet_record_id', $broadsheetRecordIds)
                ->where('term_id', $termid)
                ->where('subjectclass_id', $subjectclassid)
                ->get()
                ->keyBy('broadsheet_record_id');

            if ($broadsheets->isEmpty()) {
                return;
            }

            // Columns that carry score data in this project's broadsheets table
            $scoreColumns = ['ca1', 'ca2', 'ca3', 'exam', 'total', 'bf', 'cum', 'grade'];

            $snapshots = [];

            foreach ($broadsheetRecordIds as $broadsheetRecordId) {
                $broadsheet = $broadsheets->get($broadsheetRecordId);
                if (!$broadsheet) continue;

                $studentId = $recordToStudent->get($broadsheetRecordId);
                if (!$studentId) continue;

                $archive = $createdArchives->get($studentId);
                if (!$archive) continue;

                // Store one snapshot row per score column
                foreach ($scoreColumns as $col) {
                    $value = $broadsheet->$col ?? null;
                    if ($value === null) continue;

                    $snapshots[] = [
                        'archive_id'          => $archive->id,
                        'broadsheet_id'       => $broadsheet->id,
                        'student_id'          => $studentId,
                        'subject_id'          => $subjectId,
                        'schoolclass_id'      => $schoolclassId,
                        'session_id'          => $sessionid,
                        'term_id'             => $termid,
                        'subjectclass_id'     => $subjectclassid,
                        'staff_id'            => $staffid,
                        // Re-use assessment_id as a synthetic column index (0-based)
                        // and assessment_name as the column name string
                        'assessment_id'       => array_search($col, $scoreColumns) + 1,
                        'assessment_name'     => strtoupper($col),
                        'sub_assessment_id'   => null,
                        'sub_assessment_name' => null,
                        'score'               => is_numeric($value) ? $value : 0,
                        'score_type'          => ArchiveScoreSnapshot::TYPE_ASSESSMENT,
                        'created_at'          => $now,
                        'updated_at'          => $now,
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

    // =========================================================================
    // GET ARCHIVED REGISTRATIONS  (snapshot card list)
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

            $query->orderBy('unregistered_at', 'desc');

            $archived = $query->paginate($perPage);

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
    // GET SNAPSHOT DETAIL
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

            $scoreSnapshots = ArchiveScoreSnapshot::whereIn('archive_id', $archiveIds)
                ->orderBy('student_id')
                ->orderBy('assessment_id')
                ->get()
                ->groupBy('archive_id');

            $rows = $archives->map(function ($row) use ($scoreSnapshots) {
                $scores = $scoreSnapshots->get($row->archive_id, collect());
                return [
                    'archive_id'            => $row->archive_id,
                    'studentid'             => $row->studentid,
                    'admissionno'           => $row->admissionno,
                    'firstname'             => $row->firstname,
                    'lastname'              => $row->lastname,
                    'othername'             => $row->othername,
                    'gender'                => $row->gender,
                    'picture'               => $row->picture,
                    'snapshot_name'         => $row->snapshot_name,
                    'snapshot_notes'        => $row->snapshot_notes,
                    'unregistered_at'       => $row->unregistered_at,
                    'assessment_scores'     => $scores->where('score_type', ArchiveScoreSnapshot::TYPE_ASSESSMENT)->values()->toArray(),
                    'sub_assessment_scores' => $scores->where('score_type', ArchiveScoreSnapshot::TYPE_SUB_ASSESSMENT)->values()->toArray(),
                ];
            });

            // Build column headers from the first student's score rows
            $assessmentHeaders = collect();
            $firstScores = $scoreSnapshots->first()?->where('score_type', ArchiveScoreSnapshot::TYPE_ASSESSMENT)
                ->sortBy('assessment_id') ?? collect();
            foreach ($firstScores as $score) {
                $assessmentHeaders->push([
                    'assessment_id'   => $score->assessment_id,
                    'assessment_name' => $score->assessment_name,
                ]);
            }

            return response()->json([
                'success'            => true,
                'rows'               => $rows,
                'assessment_headers' => $assessmentHeaders->values(),
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
    // RESTORE
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
                return response()->json([
                    'success' => false,
                    'message' => 'No valid archived records found. They may have already been restored or permanently deleted.',
                ], 422);
            }

            $groups        = $archives->groupBy(fn($r) => $r->subjectclassid . '_' . $r->termid . '_' . $r->sessionid . '_' . $r->staffid);
            $totalRestored = 0;
            $errors        = [];

            foreach ($groups as $groupArchives) {
                $first      = $groupArchives->first();
                $studentIds = $groupArchives->pluck('studentid')->unique()->toArray();

                // Re-register the students
                $result = $this->processIndividually([
                    'studentid'      => $studentIds,
                    'subjectclassid' => $first->subjectclassid,
                    'staffid'        => $first->staffid,
                    'termid'         => $first->termid,
                    'sessionid'      => $first->sessionid,
                ]);

                if ($result['success'] || ($result['skipped_count'] ?? 0) > 0) {
                    // Put scores back
                    $this->restoreScoresFromSnapshot($groupArchives, $first);

                    SubjectUnregistrationArchive::whereIn('id', $groupArchives->pluck('id')->toArray())
                        ->update([
                            'status'      => SubjectUnregistrationArchive::STATUS_RESTORED,
                            'actioned_at' => now(),
                            'updated_at'  => now(),
                        ]);

                    $totalRestored += $result['success_count'] ?? 0;
                } else {
                    $errors[] = [
                        'subjectclassid' => $first->subjectclassid,
                        'termid'         => $first->termid,
                        'message'        => $result['message'] ?? 'Unknown error',
                    ];
                }
            }

            DB::commit();

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
    // RESTORE SCORES FROM SNAPSHOT  (private)
    // =========================================================================

    /**
     * After processIndividually() has re-created the broadsheet rows, overwrite
     * the default 0.00 / null values with the scores saved in the snapshot.
     *
     * Because this project stores scores as direct columns (ca1, ca2 …) we map
     * assessment_name → column name and do a targeted UPDATE per broadsheet row.
     */
    private function restoreScoresFromSnapshot($groupArchives, $first): void
    {
        try {
            $archiveIds = $groupArchives->pluck('id')->toArray();
            $snapshots  = ArchiveScoreSnapshot::whereIn('archive_id', $archiveIds)
                ->where('score_type', ArchiveScoreSnapshot::TYPE_ASSESSMENT)
                ->get();

            if ($snapshots->isEmpty()) return;

            // Re-fetch the freshly created SubjectRegistrationStatus rows
            $studentIds    = $groupArchives->pluck('studentid')->toArray();
            $registrations = SubjectRegistrationStatus::whereIn('studentid', $studentIds)
                ->where('subjectclassid', $first->subjectclassid)
                ->where('termid', $first->termid)
                ->where('sessionid', $first->sessionid)
                ->where('staffid', $first->staffid)
                ->pluck('broadsheetid', 'studentid');  // [studentid => broadsheet_record_id]

            // Map broadsheet_record_id → broadsheet.id
            $broadsheetRecordIds = $registrations->values()->toArray();
            $broadsheets         = Broadsheets::whereIn('broadsheet_record_id', $broadsheetRecordIds)
                ->where('term_id', $first->termid)
                ->where('subjectclass_id', $first->subjectclassid)
                ->pluck('id', 'broadsheet_record_id');  // [broadsheet_record_id => broadsheet.id]

            // Build archive_id → broadsheet.id
            $archiveToBroadsheetId = [];
            foreach ($groupArchives as $archive) {
                $bsRecordId = $registrations->get($archive->studentid);
                if (!$bsRecordId) continue;
                $bsId = $broadsheets->get($bsRecordId);
                if (!$bsId) continue;
                $archiveToBroadsheetId[$archive->id] = $bsId;
            }

            // Group snapshots by archive_id and apply column updates
            $grouped = $snapshots->groupBy('archive_id');
            foreach ($grouped as $archiveId => $colSnapshots) {
                $broadsheetId = $archiveToBroadsheetId[$archiveId] ?? null;
                if (!$broadsheetId) continue;

                $updates = [];
                foreach ($colSnapshots as $snap) {
                    // assessment_name was stored as the uppercase column name (CA1, EXAM …)
                    $col = strtolower($snap->assessment_name);
                    $updates[$col] = $snap->score;
                }

                if (!empty($updates)) {
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
    // PERMANENTLY DELETE — single
    // =========================================================================

    public function permanentlyDeleteArchive(Request $request, int $archiveId): JsonResponse
    {
        try {
            $archive = SubjectUnregistrationArchive::where('id', $archiveId)
                ->where('status', SubjectUnregistrationArchive::STATUS_ARCHIVED)
                ->firstOrFail();

            // Score snapshots removed via CASCADE foreign key
            $archive->delete();

            return response()->json(['success' => true, 'message' => 'Archive record permanently deleted.']);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Record not found or already actioned.'], 404);
        } catch (\Exception $e) {
            Log::error('Permanent delete failed', ['archive_id' => $archiveId, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // PERMANENTLY DELETE — batch
    // =========================================================================

    public function permanentlyDeleteArchiveBatch(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'archive_ids'   => ['required', 'array'],
            'archive_ids.*' => ['required', 'integer'],
        ]);

        try {
            // Score snapshots removed via CASCADE foreign key
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
    // REGISTERED CLASSES
    // =========================================================================

    public function registeredClasses(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'class_id'   => ['required', 'integer', 'exists:schoolclass,id'],
                'session_id' => ['required', 'integer', 'exists:schoolsession,id'],
                'term_id'    => ['nullable', 'integer', 'exists:schoolterm,id'],
            ]);

            DB::statement('SET SESSION group_concat_max_len = 1000000');

            $query = SubjectRegistrationStatus::query()
                ->join('subjectclass', 'subjectclass.id', '=', 'subject_registration_status.subjectclassid')
                ->join('schoolclass', 'schoolclass.id', '=', 'subjectclass.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->join('schoolsession', 'schoolsession.id', '=', 'subject_registration_status.sessionid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subject_registration_status.termid')
                ->leftJoin('broadsheet', 'broadsheet.id', '=', 'subject_registration_status.broadsheetid')
                ->leftJoin('subject', 'subject.id', '=', 'broadsheet.subjectid')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('staffpicture', 'staffpicture.staffid', '=', 'users.id')
                ->where('subjectclass.schoolclassid', $validated['class_id'])
                ->where('subject_registration_status.sessionid', $validated['session_id'])
                ->when($validated['term_id'], fn($q, $t) => $q->where('subject_registration_status.termid', $t))
                ->groupBy([
                    'schoolclass.id', 'schoolarm.id', 'schoolsession.id', 'schoolterm.id',
                    'schoolclass.schoolclass', 'schoolarm.arm', 'schoolsession.session', 'schoolterm.term',
                ])
                ->select([
                    'schoolclass.id as class_id',
                    'schoolclass.schoolclass as class_name',
                    DB::raw('COALESCE(schoolarm.arm, "None") as arm_name'),
                    DB::raw('COALESCE(schoolsession.session, "Unknown") as session_name'),
                    DB::raw('COALESCE(schoolterm.term, "Unknown") as term_name'),
                    DB::raw('COUNT(DISTINCT subject_registration_status.studentid) as student_count'),
                    DB::raw('COUNT(DISTINCT subject_registration_status.subjectclassid) as subject_count'),
                    DB::raw('COALESCE(GROUP_CONCAT(DISTINCT subject.subject ORDER BY subject.subject SEPARATOR ", "), "None") as subjects'),
                    DB::raw('COALESCE(GROUP_CONCAT(DISTINCT CONCAT(users.id, "|||", users.name, "|||", COALESCE(staffpicture.picture, "")) ORDER BY users.name SEPARATOR ";;;"), "") as teachers_data'),
                ]);

            $classes = $query->get();

            $processedData = [];
            foreach ($classes as $class) {
                $teachersData = [];
                if ($class->teachers_data) {
                    foreach (explode(';;;', $class->teachers_data) as $entry) {
                        if ($entry) {
                            $parts = explode('|||', $entry);
                            if (count($parts) >= 2) {
                                $teachersData[] = ['id' => $parts[0], 'name' => $parts[1], 'picture' => $parts[2] ?? null];
                            }
                        }
                    }
                }
                $processedData[] = [
                    'class_id'      => $class->class_id,
                    'class_name'    => $class->class_name,
                    'arm_name'      => $class->arm_name,
                    'session_name'  => $class->session_name,
                    'term_name'     => $class->term_name,
                    'student_count' => $class->student_count,
                    'subject_count' => $class->subject_count,
                    'subjects'      => $class->subjects,
                    'teachers'      => $teachersData,
                ];
            }

            return response()->json(['success' => true, 'data' => $processedData]);

        } catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Invalid parameters.', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Error fetching registered classes', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

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

            if ($classId && $classId !== 'ALL')   $query->where('subjectclass.schoolclassid', $classId);
            if ($sessionId && $sessionId !== 'ALL') $query->where('subjectteacher.sessionid', $sessionId);
            if ($termId && $termId !== 'ALL')     $query->where('subjectteacher.termid', $termId);

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
    // PRIVATE PROCESSING HELPERS
    // =========================================================================

    private function processIndividually(array $validated): array
    {
        $results      = [];
        $successCount = 0;
        $errors       = [];
        $skippedCount = 0;

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
            return ['success' => false, 'message' => 'No students were registered.', 'errors' => $errors, 'skipped_count' => $skippedCount, 'success_count' => 0];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Individual processing error', ['error' => $e->getMessage()]);
            return ['success' => false, 'message' => 'Processing failed: ' . $e->getMessage(), 'errors' => [$e->getMessage()], 'success_count' => 0];
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
                $bsRecords[]     = ['student_id' => $sid, 'subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid'], 'created_at' => $now, 'updated_at' => $now];
                $bsMockRecords[] = ['student_id' => $sid, 'subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid'], 'created_at' => $now, 'updated_at' => $now];
            }
            BroadsheetRecord::insertOrIgnore($bsRecords);
            BroadsheetRecordMock::insertOrIgnore($bsMockRecords);

            $createdRecords     = BroadsheetRecord::where(['subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid']])->whereIn('student_id', $toProcess)->get()->keyBy('student_id');
            $createdRecordsMock = BroadsheetRecordMock::where(['subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid']])->whereIn('student_id', $toProcess)->get()->keyBy('student_id');

            $this->bulkCreateDependentRecords($createdRecords, $createdRecordsMock, $toProcess, $validated, $now);

            DB::commit();
            return ['success' => true, 'message' => count($toProcess) . ' students registered', 'method' => 'batch', 'success_count' => count($toProcess), 'skipped_count' => $skippedCount];

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
                $existing  = SubjectRegistrationStatus::where(['subjectclassid' => $validated['subjectclassid'], 'termid' => $validated['termid'], 'sessionid' => $validated['sessionid']])->whereIn('studentid', $chunk)->pluck('studentid')->toArray();
                $toProcess = array_diff($chunk, $existing);
                $totalSkipped += count($existing);
                if (!empty($toProcess)) {
                    $this->processChunk($toProcess, $validated, $subjectId, $schoolclassId);
                    $totalProcessed += count($toProcess);
                }
            }

            DB::commit();
            return ['success' => true, 'message' => "{$totalProcessed} students registered", 'method' => 'large_dataset', 'success_count' => $totalProcessed, 'skipped_count' => $totalSkipped];

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
            $bsRecords[]     = ['student_id' => $sid, 'subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid'], 'created_at' => $now, 'updated_at' => $now];
            $bsMockRecords[] = ['student_id' => $sid, 'subject_id' => $subjectId, 'schoolclass_id' => $schoolclassId, 'session_id' => $validated['sessionid'], 'created_at' => $now, 'updated_at' => $now];
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

            $broadsheets[]      = ['broadsheet_record_id' => $r->id, 'term_id' => $validated['termid'], 'subjectclass_id' => $validated['subjectclassid'], 'staff_id' => $validated['staffid'], 'created_at' => $now, 'updated_at' => $now];
            $broadsheetsMock[]  = ['broadsheet_records_mock_id' => $rm->id, 'term_id' => $validated['termid'], 'subjectclass_id' => $validated['subjectclassid'], 'staff_id' => $validated['staffid'], 'created_at' => $now, 'updated_at' => $now];
            $subjectRegs[]      = ['studentid' => $sid, 'subjectclassid' => $validated['subjectclassid'], 'staffid' => $validated['staffid'], 'termid' => $validated['termid'], 'sessionid' => $validated['sessionid'], 'broadsheetid' => $r->id, 'Status' => 1, 'created_at' => $now, 'updated_at' => $now];
            $studentSubjectRecs[] = ['studentId' => $sid, 'subjectclassid' => $validated['subjectclassid'], 'staffid' => $validated['staffid'], 'session' => $validated['sessionid'], 'created_at' => $now, 'updated_at' => $now];
        }

        if (!empty($broadsheets))       Broadsheets::insertOrIgnore($broadsheets);
        if (!empty($broadsheetsMock))   BroadsheetsMock::insertOrIgnore($broadsheetsMock);
        if (!empty($subjectRegs))       SubjectRegistrationStatus::insertOrIgnore($subjectRegs);
        if (!empty($studentSubjectRecs)) StudentSubjectRecord::insertOrIgnore($studentSubjectRecs);
    }
}
