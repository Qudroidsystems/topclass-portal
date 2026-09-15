<?php

namespace App\Http\Controllers;

use App\Models\MockSubjectVetting;
use App\Models\Schoolclass;
use App\Models\Subjectclass;
use App\Models\SubjectTeacher;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\User;
use App\Models\Studentclass;
use App\Models\Subject;
use App\Models\BroadsheetsMock;
use App\Models\BroadsheetRecordMock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MyMockSubjectVettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View my-mock-subject-vettings', ['only' => ['index', 'showVettingScores']]);
        $this->middleware('permission:Update my-mock-subject-vettings', ['only' => ['update']]);
    }

    public function index(Request $request)
    {
        try {
            $pagetitle = "My Mock Subject Vetting Assignments";

            $mocksubjectvettings = MockSubjectVetting::where('mock_subject_vettings.userid', Auth::id())
                ->leftJoin('subjectclass', 'mock_subject_vettings.subjectclassId', '=', 'subjectclass.id')
                ->leftJoin('schoolclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('users as teacher_user', 'subjectteacher.staffid', '=', 'teacher_user.id')
                ->leftJoin('schoolterm', 'mock_subject_vettings.termid', '=', 'schoolterm.id')
                ->leftJoin('schoolsession', 'mock_subject_vettings.sessionid', '=', 'schoolsession.id')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->select([
                    'mock_subject_vettings.id as svid',
                    'mock_subject_vettings.userid as vetting_userid',
                    'subjectclass.id as subjectclassId',
                    'schoolclass.id as schoolclassid',
                    'schoolclass.schoolclass as sclass',
                    'schoolarm.arm as schoolarm',
                    'subjectteacher.staffid as staffid',
                    'subject.id as subjectid',
                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',
                    'teacher_user.name as teachername',
                    'schoolterm.id as termid',
                    'schoolterm.term as termname',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as sessionname',
                    'mock_subject_vettings.status',
                    'mock_subject_vettings.updated_at'
                ])
                ->orderBy('termname')
                ->orderBy('sessionname')
                ->orderBy('subjectname')
                ->get();

            $statusCounts = MockSubjectVetting::where('mock_subject_vettings.userid', Auth::id())
                ->groupBy('status')
                ->selectRaw('status, COUNT(*) as count')
                ->pluck('count', 'status')
                ->toArray();

            $statusCounts = array_merge([
                'pending' => 0,
                'completed' => 0,
                'rejected' => 0
            ], $statusCounts);

            $terms = Schoolterm::get(['id', 'term'])->sortBy('term');
            $sessions = Schoolsession::get(['id', 'session'])->sortBy('session');
            $subjectclasses = Subjectclass::leftJoin('schoolclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->get([
                    'subjectclass.id as scid',
                    'schoolclass.id as schoolclassid',
                    'schoolclass.schoolclass as sclass',
                    'schoolarm.arm as schoolarm',
                    'subjectteacher.staffid as subtid',
                    'subject.id as subjectid',
                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',
                    'users.name as teachername'
                ])
                ->sortBy('sclass');

            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => true,
                    'mocksubjectvettings' => $mocksubjectvettings,
                    'statusCounts' => $statusCounts
                ], 200);
            }

            return view('mymocksubjectvettings.index')
                ->with('mocksubjectvettings', $mocksubjectvettings)
                ->with('subjectclasses', $subjectclasses)
                ->with('terms', $terms)
                ->with('sessions', $sessions)
                ->with('pagetitle', $pagetitle)
                ->with('statusCounts', $statusCounts);
        } catch (\Exception $e) {
            Log::error('Error loading my mock subject vetting index: ' . $e->getMessage());
            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load mock subject vetting data: ' . $e->getMessage()
                ], 500);
            }
            return view('mymocksubjectvettings.index')
                ->with('mocksubjectvettings', collect([]))
                ->with('subjectclasses', collect([]))
                ->with('terms', collect([]))
                ->with('sessions', collect([]))
                ->with('pagetitle', 'My Mock Subject Vetting Assignments')
                ->with('statusCounts', ['pending' => 0, 'completed' => 0, 'rejected' => 0])
                ->with('danger', 'Failed to load mock subject vetting data: ' . $e->getMessage());
        }
    }

    public function classBroadsheet($schoolclassid, $subjectclassid, $staffid, $termid, $sessionid)
    {
        Log::info('classBroadsheet parameters:', compact('staffid', 'termid', 'sessionid', 'schoolclassid', 'subjectclassid'));

        $pagetitle = "Mock Class Broadsheet";

        $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

        if ($broadsheets->isEmpty()) {
            Log::warning('No broadsheets found for classBroadsheet', compact('staffid', 'termid', 'sessionid', 'schoolclassid', 'subjectclassid'));
        } else {
            $pagetitle = sprintf(
                'Mock Class Broadsheet for %s (%s) - %s %s - %s %s',
                $broadsheets->first()->subject,
                $broadsheets->first()->subject_code,
                $broadsheets->first()->schoolclass,
                $broadsheets->first()->arm,
                $broadsheets->first()->term,
                $broadsheets->first()->session
            );
        }

        $schoolclass = Schoolclass::where('schoolclass.id', $schoolclassid)
            ->leftJoin('schoolarm', 'schoolclass.arm', '=', 'schoolarm.id')
            ->first(['schoolclass.schoolclass', 'schoolclass.arm as arm_id', 'schoolarm.arm']);

        $schoolterm = Schoolterm::where('id', $termid)->value('term') ?? 'N/A';
        $schoolsession = Schoolsession::where('id', $sessionid)->value('session') ?? 'N/A';

        return view('mymocksubjectvettings.classbroadsheet')
            ->with('broadsheets', $broadsheets)
            ->with('schoolclass', $schoolclass)
            ->with('schoolterm', $schoolterm)
            ->with('schoolsession', $schoolsession)
            ->with('schoolclassid', $schoolclassid)
            ->with('sessionid', $sessionid)
            ->with('termid', $termid)
            ->with('pagetitle', $pagetitle);
    }

    protected function getBroadsheets($staffId, $termId, $sessionId, $schoolClassId = null, $subjectClassId = null)
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
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheetmock.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records_mock.session_id')
            ->where('broadsheet_records_mock.session_id', $sessionId);

        if ($schoolClassId) {
            $query->where('schoolclass.id', $schoolClassId);
        }

        $sql = $query->toSql();
        $bindings = $query->getBindings();
        Log::debug('getBroadsheets: Raw SQL query', [
            'sql' => $sql,
            'bindings' => $bindings,
        ]);

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
            'classcategories.examscore as examscore',
            'studentpicture.picture',
            'broadsheetmock.exam',
            'broadsheetmock.total',
            'broadsheetmock.grade',
            'broadsheetmock.subject_position_class as position',
            'broadsheetmock.remark',
            'broadsheetmock.vettedstatus',
        ])->sortBy('lastname');

        Log::debug('getBroadsheets: Retrieved broadsheets', [
            'staff_id' => $staffId,
            'term_id' => $termId,
            'session_id' => $sessionId,
            'schoolclass_id' => $schoolClassId,
            'subjectclass_id' => $subjectClassId,
            'result_count' => $results->count(),
            'students' => $results->map(function ($item) {
                return [
                    'admissionno' => $item->admissionno,
                    'student_id' => $item->student_id,
                    'subject' => $item->subject,
                    'subject_id' => $item->subject_id,
                    'subjectclass_id' => $item->subjectclid,
                    'position' => $item->position,
                    'vettedstatus' => $item->vettedstatus,
                ];
            })->toArray(),
            'subjects' => $results->pluck('subject')->unique()->values()->toArray(),
        ]);

        foreach ($results as $broadsheet) {
            $exam = $broadsheet->exam ?? 0;
            $newTotal = round($exam, 1);

            $schoolclass = Schoolclass::with('classcategory')->find($broadsheet->schoolclass_id);
            $newGrade = $schoolclass && $schoolclass->classcategory
                ? $schoolclass->classcategory->calculateGrade($newTotal)
                : $this->getDefaultGrade($newTotal);

            $newRemark = $this->getRemark($newGrade);

            $significantChange = abs($broadsheet->total - $newTotal) > 0.01 ||
                                $broadsheet->grade !== $newGrade ||
                                $broadsheet->remark !== $newRemark;

            if ($significantChange) {
                Log::info("getBroadsheets: Updating broadsheet {$broadsheet->id} due to significant changes", [
                    'schoolclass_id' => $broadsheet->schoolclass_id,
                    'subjectclass_id' => $subjectClassId,
                    'student_id' => $broadsheet->student_id,
                    'admissionno' => $broadsheet->admissionno,
                    'subject_id' => $broadsheet->subject_id,
                    'subject' => $broadsheet->subject,
                    'old_values' => [
                        'total' => $broadsheet->total,
                        'grade' => $broadsheet->grade,
                        'remark' => $broadsheet->remark,
                        'position' => $broadsheet->position,
                        'vettedstatus' => $broadsheet->vettedstatus,
                    ],
                    'new_values' => [
                        'total' => $newTotal,
                        'grade' => $newGrade,
                        'remark' => $newRemark,
                        'position' => $broadsheet->position,
                        'vettedstatus' => $broadsheet->vettedstatus,
                    ],
                ]);

                $broadsheet->total = $newTotal;
                $broadsheet->grade = $newGrade;
                $broadsheet->remark = $newRemark;
                $broadsheet->save();
            }
        }

        return $results;
    }

    public function updateVettedStatus(Request $request)
    {
        $request->validate([
            'broadsheet_id' => 'required|exists:broadsheetmock,id',
            'vettedstatus' => 'required|in:0,1',
        ]);

        try {
            $broadsheet = BroadsheetsMock::findOrFail($request->broadsheet_id);
            
            $broadsheet->vettedstatus = $request->vettedstatus;
            $broadsheet->vettedby = Auth::id();
            $broadsheet->save();

            Log::info('Vetted status updated', [
                'broadsheet_id' => $broadsheet->id,
                'vettedstatus' => $broadsheet->vettedstatus,
                'vettedby' => $broadsheet->vettedby,
            ]);

            $allVetted = $this->checkAllBroadsheetsVetted(
                $broadsheet->term_id,
                $broadsheet->subjectclass_id,
                Auth::id()
            );

            if ($allVetted) {
                $mockSubjectVetting = MockSubjectVetting::where('userid', Auth::id())
                    ->where('termid', $broadsheet->term_id)
                    ->where('subjectclassId', $broadsheet->subjectclass_id)
                    ->first();

                if ($mockSubjectVetting && $mockSubjectVetting->status !== 'completed') {
                    $mockSubjectVetting->status = 'completed';
                    $mockSubjectVetting->save();

                    Log::info('MockSubjectVetting status updated to completed', [
                        'mocksubjectvetting_id' => $mockSubjectVetting->id,
                        'userid' => $mockSubjectVetting->userid,
                        'termid' => $mockSubjectVetting->termid,
                        'subjectclassId' => $mockSubjectVetting->subjectclassId,
                        'status' => $mockSubjectVetting->status,
                    ]);
                }
            } else {
                $mockSubjectVetting = MockSubjectVetting::where('userid', Auth::id())
                    ->where('termid', $broadsheet->term_id)
                    ->where('subjectclassId', $broadsheet->subjectclass_id)
                    ->first();

                if ($mockSubjectVetting && $mockSubjectVetting->status !== 'pending') {
                    $mockSubjectVetting->status = 'pending';
                    $mockSubjectVetting->save();

                    Log::info('MockSubjectVetting status reverted to pending', [
                        'mocksubjectvetting_id' => $mockSubjectVetting->id,
                        'userid' => $mockSubjectVetting->userid,
                        'termid' => $mockSubjectVetting->termid,
                        'subjectclassId' => $mockSubjectVetting->subjectclassId,
                        'status' => $mockSubjectVetting->status,
                    ]);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Failed to update vetted status', [
                'broadsheet_id' => $request->broadsheet_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['success' => false, 'message' => 'Failed to update vetted status: ' . $e->getMessage()], 500);
        }
    }

    protected function checkAllBroadsheetsVetted($termId, $subjectClassId, $userId)
    {
        $totalBroadsheets = BroadsheetsMock::where('term_id', $termId)
            ->where('subjectclass_id', $subjectClassId)
            ->count();

        $vettedBroadsheets = BroadsheetsMock::where('term_id', $termId)
            ->where('subjectclass_id', $subjectClassId)
            ->where('vettedstatus', 1)
            ->where('vettedby', $userId)
            ->count();

        Log::debug('Checking if all broadsheets are vetted', [
            'term_id' => $termId,
            'subjectclass_id' => $subjectClassId,
            'user_id' => $userId,
            'total_broadsheets' => $totalBroadsheets,
            'vetted_broadsheets' => $vettedBroadsheets,
        ]);

        return $totalBroadsheets > 0 && $totalBroadsheets === $vettedBroadsheets;
    }

    public function results()
    {
        try {
            $subjectclass_id = session('subjectclass_id');
            $schoolclass_id = session('schoolclass_id');
            $term_id = session('term_id');
            $session_id = session('session_id');

            if (!$subjectclass_id || !$schoolclass_id || !$term_id || !$session_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Missing required session data',
                    'scores' => [],
                ], 400);
            }

            $broadsheets = BroadsheetsMock::where([
                'subjectclass_id' => $subjectclass_id,
                'term_id' => $term_id,
            ])
                ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records_mock.student_id')
                ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records_mock.subject_id')
                ->where('broadsheet_records_mock.session_id', $session_id)
                ->get([
                    'broadsheetmock.id',
                    'studentRegistration.admissionNO as admissionno',
                    'studentRegistration.firstname as fname',
                    'studentRegistration.lastname as lname',
                    'broadsheetmock.exam',
                    'broadsheetmock.total',
                    'broadsheetmock.grade',
                    'broadsheetmock.subject_position_class as position',
                    'broadsheetmock.term_id',
                ]);

            return response()->json([
                'success' => true,
                'scores' => $broadsheets->toArray(),
            ]);
        } catch (\Exception $e) {
            Log::error('Error in results endpoint: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error: ' . $e->getMessage(),
            ], 500);
        }
    }

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
                DB::raw('SUM(broadsheetmock.total) as total_sum')
            ])
            ->first();

        $classMin = $metrics->class_min ?? 0;
        $classMax = $metrics->class_max ?? 0;
        $classAvg = $metrics->student_count > 0 ? round($metrics->class_avg, 1) : 0;

        Log::info('Calculated class metrics', [
            'subjectclass_id' => $subjectclassid,
            'staff_id' => $staffid,
            'term_id' => $termid,
            'session_id' => $sessionid,
            'subject_id' => $subjectId,
            'class_min' => $classMin,
            'class_max' => $classMax,
            'class_avg' => $classAvg,
            'student_count' => $metrics->student_count,
            'total_sum' => $metrics->total_sum,
        ]);

        BroadsheetsMock::where('subjectclass_id', $subjectclassid)
            ->where('staff_id', $staffid)
            ->where('term_id', $termid)
            ->leftJoin('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->where('broadsheet_records_mock.session_id', $sessionid)
            ->where('broadsheet_records_mock.subject_id', $subjectId)
            ->update([
                'cmin' => $classMin,
                'cmax' => $classMax,
                'avg' => $classAvg,
            ]);

        Log::info('Updated class metrics for broadsheets', [
            'subjectclass_id' => $subjectclassid,
            'staff_id' => $staffid,
            'term_id' => $termid,
            'session_id' => $sessionid,
            'subject_id' => $subjectId,
        ]);
    }

    protected function updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id)
    {
        Log::info('updateSubjectPositions called', compact('subjectclass_id', 'staff_id', 'term_id', 'session_id'));
        $broadsheets = BroadsheetsMock::where('subjectclass_id', $subjectclass_id)
            ->where('staff_id', $staff_id)
            ->where('term_id', $term_id)
            ->where('broadsheet_records_mock.session_id', $session_id)
            ->join('broadsheet_records_mock', 'broadsheet_records_mock.id', '=', 'broadsheetmock.broadsheet_records_mock_id')
            ->orderByDesc('broadsheetmock.total')
            ->orderBy('broadsheetmock.id')
            ->get();

        if ($broadsheets->isEmpty()) {
            Log::warning('No broadsheets found for position update', compact('subjectclass_id', 'staff_id', 'term_id', 'session_id'));
            return;
        }

        $rank = 0;
        $lastTotal = null;
        $lastPosition = 0;

        foreach ($broadsheets as $broadsheet) {
            $rank++;
            if ($lastTotal !== null && $broadsheet->total == $lastTotal) {
                // Tied rank
            } else {
                $lastPosition = $rank;
                $lastTotal = $broadsheet->total;
            }
            if ($broadsheet->subject_position_class != $lastPosition) {
                $broadsheet->subject_position_class = $lastPosition;
                $broadsheet->save();
                Log::info('Updated position', [
                    'broadsheet_id' => $broadsheet->id,
                    'student_id' => $broadsheet->student_id,
                    'admissionno' => $broadsheet->admissionno,
                    'total' => $broadsheet->total,
                    'subject_position_class' => $lastPosition,
                ]);
            }
        }

        Log::info('Subject positions updated', ['total_records' => $broadsheets->count()]);
    }

    protected function getDefaultGrade($score)
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
}