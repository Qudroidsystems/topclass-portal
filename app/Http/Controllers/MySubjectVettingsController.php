<?php

namespace App\Http\Controllers;

use App\Models\SubjectVetting;
use App\Models\Schoolclass;
use App\Models\Subjectclass;
use App\Models\SubjectTeacher;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\User;
use App\Models\Studentclass;
use App\Models\Subject;
use App\Models\Broadsheets;
use App\Models\Assessment;
use App\Models\BroadsheetAssessmentScore;
use App\Models\BroadsheetSubAssessmentScore;
use App\Models\PromotionStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MySubjectVettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View my-subject-vettings', ['only' => ['index', 'showVettingScores']]);
        $this->middleware('permission:Update my-subject-vettings', ['only' => ['update']]);
    }

    public function index(Request $request)
    {
        try {
            $pagetitle = "My Subject Vetting Assignments";

            $subjectvettings = SubjectVetting::where('subject_vettings.userid', Auth::id())
                ->leftJoin('subjectclass', 'subject_vettings.subjectclassid', '=', 'subjectclass.id')
                ->leftJoin('schoolclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('users as teacher_user', 'subjectteacher.staffid', '=', 'teacher_user.id')
                ->leftJoin('schoolterm', 'subject_vettings.termid', '=', 'schoolterm.id')
                ->leftJoin('schoolsession', 'subject_vettings.sessionid', '=', 'schoolsession.id')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->select([
                    'subject_vettings.id as svid',
                    'subject_vettings.userid as vetting_userid',
                    'subjectclass.id as subjectclassid',
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
                    'subject_vettings.status',
                    'subject_vettings.updated_at'
                ])
                ->orderBy('termname')
                ->orderBy('sessionname')
                ->orderBy('subjectname')
                ->get();

            $statusCounts = SubjectVetting::where('subject_vettings.userid', Auth::id())
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
                    'subjectvettings' => $subjectvettings,
                    'statusCounts' => $statusCounts
                ], 200);
            }

            return view('mysubjectvettings.index')
                ->with('subjectvettings', $subjectvettings)
                ->with('subjectclasses', $subjectclasses)
                ->with('terms', $terms)
                ->with('sessions', $sessions)
                ->with('pagetitle', $pagetitle)
                ->with('statusCounts', $statusCounts);
        } catch (\Exception $e) {
            Log::error('Error loading my subject vetting index: ' . $e->getMessage());
            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load subject vetting data: ' . $e->getMessage()
                ], 500);
            }
            return view('mysubjectvettings.index')
                ->with('subjectvettings', collect([]))
                ->with('subjectclasses', collect([]))
                ->with('terms', collect([]))
                ->with('sessions', collect([]))
                ->with('pagetitle', 'My Subject Vetting Assignments')
                ->with('statusCounts', ['pending' => 0, 'completed' => 0, 'rejected' => 0])
                ->with('danger', 'Failed to load subject vetting data: ' . $e->getMessage());
        }
    }

    public function classBroadsheet($schoolclassid, $subjectclassid, $staffid, $termid, $sessionid)
    {
        Log::info('classBroadsheet parameters:', compact('staffid', 'termid', 'sessionid', 'schoolclassid', 'subjectclassid'));

        // Fetch assessments for this class category
        $schoolclass = Schoolclass::with('classcategories')->find($schoolclassid);
        $assessments = collect();

        if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with('subAssessments')
                ->orderBy('id')
                ->get();
        }

        // Fetch broadsheets with assessment scores
        $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

        if ($broadsheets->isEmpty()) {
            Log::warning('No broadsheets found for classBroadsheet', compact('staffid', 'termid', 'sessionid', 'schoolclassid', 'subjectclassid'));
        } else {
            // Update metrics and positions
            $this->updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid);
            $this->computeDynamicTotals($broadsheets, $assessments, $schoolclass, $termid, $sessionid);
            $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $sessionid);
            $this->updateClassPositions($schoolclassid, $termid, $sessionid);

            // Refresh broadsheets
            $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

            $pagetitle = sprintf(
                'Class Broadsheet for %s (%s) - %s %s - %s %s',
                $broadsheets->first()->subject,
                $broadsheets->first()->subject_code,
                $broadsheets->first()->schoolclass,
                $broadsheets->first()->arm,
                $broadsheets->first()->term,
                $broadsheets->first()->session
            );
        }

        $pagetitle = $pagetitle ?? "Class Broadsheet";
        $schoolterm = Schoolterm::where('id', $termid)->value('term') ?? 'N/A';
        $schoolsession = Schoolsession::where('id', $sessionid)->value('session') ?? 'N/A';

        return view('mysubjectvettings.classbroadsheet')
            ->with('broadsheets', $broadsheets)
            ->with('schoolclass', $schoolclass)
            ->with('assessments', $assessments)
            ->with('schoolterm', $schoolterm)
            ->with('schoolsession', $schoolsession)
            ->with('schoolclassid', $schoolclassid)
            ->with('sessionid', $sessionid)
            ->with('termid', $termid)
            ->with('pagetitle', $pagetitle);
    }

    protected function getBroadsheets($staffId, $termId, $sessionId, $schoolClassId = null, $subjectClassId = null)
    {
        $query = Broadsheets::query()
            ->where('broadsheets.staff_id', $staffId)
            ->where('broadsheets.term_id', $termId)
            ->with(['assessmentScores', 'subAssessmentScores'])
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
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
            ->where('broadsheet_records.session_id', $sessionId);

        if ($schoolClassId) {
            $query->where('schoolclass.id', $schoolClassId);
        }

        // Log the raw SQL query for debugging
        $sql = $query->toSql();
        $bindings = $query->getBindings();
        Log::debug('getBroadsheets: Raw SQL query', [
            'sql' => $sql,
            'bindings' => $bindings,
        ]);

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
            'broadsheets.total',
            'broadsheets.bf',
            'broadsheets.cum',
            'broadsheets.grade',
            'broadsheets.subject_position_class as position',
            'broadsheets.remark',
            'broadsheets.vettedstatus',
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

        return $results;
    }

    /**
     * Compute dynamic totals, cums, grades, and remarks based on assessment scores.
     */
    private function computeDynamicTotals($broadsheets, $assessments, $schoolclass, $termId, $sessionId)
    {
        foreach ($broadsheets as $broadsheet) {
            $assessmentScores = $broadsheet->assessmentScores ?? collect();
            $totalRaw = 0;

            foreach ($assessments as $assessment) {
                $scoreObj = $assessmentScores->where('assessment_id', $assessment->id)->first();
                $assessmentScore = $scoreObj ? $scoreObj->score : 0;
                $totalRaw += $assessmentScore;
            }

            // Get BF (brought forward from previous term)
            $newBf = $this->getPreviousTermCum(
                $broadsheet->student_id,
                $broadsheet->subject_id,
                $termId,
                $sessionId
            );

            // Calculate cum as (total + bf) / 2, or just total for term 1
            $newCum = $termId == 1 ? round($totalRaw, 2) : round(($totalRaw + $newBf) / 2, 2);

            $newGrade = $schoolclass && $schoolclass->classcategories->isNotEmpty()
                ? $schoolclass->classcategories->first()->calculateGrade($newCum)
                : $this->getDefaultGrade($newCum);

            $newRemark = $this->getRemark($newGrade);

            $significantChange = abs($broadsheet->total - $totalRaw) > 0.01 ||
                                abs($broadsheet->bf - $newBf) > 0.01 ||
                                abs($broadsheet->cum - $newCum) > 0.01 ||
                                $broadsheet->grade !== $newGrade ||
                                $broadsheet->remark !== $newRemark;

            if ($significantChange) {
                Log::info("computeDynamicTotals: Updating broadsheet {$broadsheet->id} due to significant changes", [
                    'old_values' => [
                        'total' => $broadsheet->total,
                        'bf' => $broadsheet->bf,
                        'cum' => $broadsheet->cum,
                        'grade' => $broadsheet->grade,
                        'remark' => $broadsheet->remark,
                    ],
                    'new_values' => [
                        'total' => $totalRaw,
                        'bf' => $newBf,
                        'cum' => $newCum,
                        'grade' => $newGrade,
                        'remark' => $newRemark,
                    ],
                ]);

                $broadsheet->total = $totalRaw;
                $broadsheet->bf = $newBf;
                $broadsheet->cum = $newCum;
                $broadsheet->grade = $newGrade;
                $broadsheet->remark = $newRemark;
                $broadsheet->save();
            }
        }
    }

    public function updateVettedStatus(Request $request)
    {
        $request->validate([
            'broadsheet_id' => 'required|exists:broadsheets,id',
            'vettedstatus' => 'required|in:0,1',
        ]);

        try {
            // Find the broadsheet
            $broadsheet = Broadsheets::findOrFail($request->broadsheet_id);

            // Update vetted status and vettedby
            $broadsheet->vettedstatus = $request->vettedstatus;
            $broadsheet->vettedby = Auth::id();
            $broadsheet->save();

            Log::info('Vetted status updated', [
                'broadsheet_id' => $broadsheet->id,
                'vettedstatus' => $broadsheet->vettedstatus,
                'vettedby' => $broadsheet->vettedby,
            ]);

            // Check if all broadsheets for the term_id and subjectclass_id are vetted (vettedstatus = 1)
            $allVetted = $this->checkAllBroadsheetsVetted(
                $broadsheet->term_id,
                $broadsheet->subjectclass_id,
                Auth::id()
            );

            if ($allVetted) {
                // Update the corresponding SubjectVetting status to 'completed'
                $subjectVetting = SubjectVetting::where('userid', Auth::id())
                    ->where('termid', $broadsheet->term_id)
                    ->where('subjectclassid', $broadsheet->subjectclass_id)
                    ->first();

                if ($subjectVetting && $subjectVetting->status !== 'completed') {
                    $subjectVetting->status = 'completed';
                    $subjectVetting->save();

                    Log::info('SubjectVetting status updated to completed', [
                        'subjectvetting_id' => $subjectVetting->id,
                        'userid' => $subjectVetting->userid,
                        'termid' => $subjectVetting->termid,
                        'subjectclassid' => $subjectVetting->subjectclassid,
                        'status' => $subjectVetting->status,
                    ]);
                }
            } else {
                // Ensure the status is 'pending' if not all broadsheets are vetted
                $subjectVetting = SubjectVetting::where('userid', Auth::id())
                    ->where('termid', $broadsheet->term_id)
                    ->where('subjectclassid', $broadsheet->subjectclass_id)
                    ->first();

                if ($subjectVetting && $subjectVetting->status !== 'pending') {
                    $subjectVetting->status = 'pending';
                    $subjectVetting->save();

                    Log::info('SubjectVetting status reverted to pending', [
                        'subjectvetting_id' => $subjectVetting->id,
                        'userid' => $subjectVetting->userid,
                        'termid' => $subjectVetting->termid,
                        'subjectclassid' => $subjectVetting->subjectclassid,
                        'status' => $subjectVetting->status,
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
        $totalBroadsheets = Broadsheets::where('term_id', $termId)
            ->where('subjectclass_id', $subjectClassId)
            ->count();

        $vettedBroadsheets = Broadsheets::where('term_id', $termId)
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

            $schoolclass = Schoolclass::with('classcategories')->find($schoolclass_id);
            $assessments = collect();

            if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
                $categoryIds = $schoolclass->classcategories->pluck('id');
                $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                    ->with('subAssessments')
                    ->orderBy('id')->get();
            }

            $broadsheets = Broadsheets::where([
                'subjectclass_id' => $subjectclass_id,
                'term_id' => $term_id,
            ])
                ->with(['assessmentScores', 'subAssessmentScores'])
                ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
                ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
                ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                ->where('broadsheet_records.session_id', $session_id)
                ->get([
                    'broadsheets.id',
                    'studentRegistration.admissionNO as admissionno',
                    'studentRegistration.firstname as fname',
                    'studentRegistration.lastname as lname',
                    'broadsheets.total',
                    'broadsheets.bf',
                    'broadsheets.cum',
                    'broadsheets.grade',
                    'broadsheets.subject_position_class as position',
                    'broadsheets.term_id',
                    'broadsheets.vettedstatus',
                ]);

            // Compute dynamic data for response
            $scoresData = $broadsheets->map(function ($broadsheet) use ($assessments) {
                $assessmentData = [];
                foreach ($assessments as $assessment) {
                    $scoreObj = $broadsheet->assessmentScores->where('assessment_id', $assessment->id)->first();
                    $assessmentData[$assessment->id] = [
                        'name' => $assessment->name,
                        'max_score' => $assessment->max_score,
                        'score' => $scoreObj ? $scoreObj->score : 0,
                    ];
                }

                return [
                    'id' => $broadsheet->id,
                    'admissionno' => $broadsheet->admissionno,
                    'fname' => $broadsheet->fname,
                    'lname' => $broadsheet->lname,
                    'assessments' => $assessmentData,
                    'total' => $broadsheet->total,
                    'bf' => $broadsheet->bf,
                    'cum' => $broadsheet->cum,
                    'grade' => $broadsheet->grade,
                    'position' => $broadsheet->position,
                    'vettedstatus' => $broadsheet->vettedstatus,
                ];
            });

            return response()->json([
                'success' => true,
                'assessments' => $assessments,
                'scores' => $scoresData,
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
        // Fetch the subjectclass to get the subject_id
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

        // Calculate class metrics (min, max, avg) for the subject using cum instead of total
        $metrics = Broadsheets::where('broadsheets.subjectclass_id', $subjectclassid)
            ->where('broadsheets.staff_id', $staffid)
            ->where('broadsheets.term_id', $termid)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->select([
                DB::raw('MIN(broadsheets.cum) as class_min'),
                DB::raw('MAX(broadsheets.cum) as class_max'),
                DB::raw('SUM(broadsheets.cum) as cum_sum'),
                DB::raw('COUNT(broadsheets.id) as student_count')
            ])
            ->first();

        $classMin = $metrics->class_min ?? 0;
        $classMax = $metrics->class_max ?? 0;
        $classAvg = $metrics->student_count > 0 ? round($metrics->cum_sum / $metrics->student_count, 1) : 0;

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
            'cum_sum' => $metrics->cum_sum,
        ]);

        // Update all relevant broadsheet records with the calculated metrics
        Broadsheets::where('subjectclass_id', $subjectclassid)
            ->where('staff_id', $staffid)
            ->where('term_id', $termid)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->where('broadsheet_records.session_id', $sessionid)
            ->where('broadsheet_records.subject_id', $subjectId)
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

        $broadsheets = Broadsheets::where('subjectclass_id', $subjectclass_id)
            ->where('staff_id', $staff_id)
            ->where('term_id', $term_id)
            ->where('broadsheet_records.session_id', $session_id)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->orderByDesc('broadsheets.cum')
            ->orderBy('broadsheets.id')
            ->get();

        if ($broadsheets->isEmpty()) {
            Log::warning('No broadsheets found for position update', compact('subjectclass_id', 'staff_id', 'term_id', 'session_id'));
            return;
        }

        $rank = 0;
        $lastCum = null;
        $lastPosition = 0;

        foreach ($broadsheets as $broadsheet) {
            $rank++;
            if ($lastCum !== null && $broadsheet->cum == $lastCum) {
                // Tied rank
            } else {
                $lastPosition = $rank;
                $lastCum = $broadsheet->cum;
            }

            if ($broadsheet->subject_position_class != $lastPosition) {
                $broadsheet->subject_position_class = $lastPosition;
                $broadsheet->save();

                Log::info('Updated position', [
                    'broadsheet_id' => $broadsheet->id,
                    'student_id' => $broadsheet->student_id,
                    'admissionno' => $broadsheet->admissionno,
                    'cum' => $broadsheet->cum,
                    'subject_position_class' => $lastPosition,
                ]);
            }
        }

        Log::info('Subject positions updated', ['total_records' => $broadsheets->count()]);
    }

    protected function updateClassPositions($schoolclassid, $termid, $sessionid)
    {
        $rank = 0;
        $lastScore = null;
        $rows = 0;

        $pos = PromotionStatus::where('schoolclassid', $schoolclassid)
            ->where('termid', $termid)
            ->where('sessionid', $sessionid)
            ->orderBy('subjectstotalscores', 'DESC')
            ->get();

        foreach ($pos as $row) {
            $rows++;
            if ($lastScore !== $row->subjectstotalscores) {
                $lastScore = $row->subjectstotalscores;
                $rank = $rows;
            }
            $position = match ($rank) {
                1 => 'st',
                2 => 'nd',
                3 => 'rd',
                default => 'th',
            };
            $rankPos = $rank . $position;

            PromotionStatus::where('id', $row->id)
                ->update(['position' => $rankPos]);
        }
    }

    public function edit($id)
    {
        $broadsheet = Broadsheets::where('broadsheets.id', $id)
            ->with(['assessmentScores', 'subAssessmentScores'])
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->leftJoin('studentRegistration', 'studentRegistration.id', '=', 'broadsheet_records.student_id')
            ->leftJoin('studentpicture', 'studentpicture.studentid', '=', 'studentRegistration.id')
            ->leftJoin('subjectclass', 'subjectclass.id', '=', 'broadsheets.subjectclass_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'broadsheet_records.schoolclass_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'broadsheets.term_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'broadsheet_records.session_id')
            ->first([
                'broadsheets.id as bid',
                'studentRegistration.admissionNO as admissionno',
                'studentRegistration.title',
                'studentRegistration.firstname as fname',
                'studentRegistration.lastname as lname',
                'studentpicture.picture',
                'broadsheets.total',
                'broadsheets.bf',
                'broadsheets.cum',
                'broadsheets.grade',
                'schoolterm.term',
                'schoolsession.session',
                'subject.subject',
                'subject.subject_code',
                'schoolclass.schoolclass',
                'schoolarm.arm',
                'broadsheets.subject_position_class as position',
                'broadsheets.remark',
                'broadsheet_records.student_id',
                'broadsheets.staff_id',
                'broadsheets.term_id',
                'broadsheet_records.session_id as sessionid',
                'schoolclass.id as schoolclass_id',
            ]);

        if (!$broadsheet) {
            return view('error', [
                'id' => $id,
                'title' => 'Not Found',
                'message' => 'Score not found.',
            ]);
        }

        $schoolclass = Schoolclass::with('classcategories')->find($broadsheet->schoolclass_id);
        $assessments = collect();

        if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with('subAssessments')
                ->orderBy('id')->get();
        }

        $pagetitle = sprintf(
            'Edit Score for %s %s - %s (%s)',
            $broadsheet->fname,
            $broadsheet->lname,
            $broadsheet->subject,
            $id
        );

        return view('mysubjectvettings.edit', compact('broadsheet', 'pagetitle', 'assessments'));
    }

    public function update(Request $request, $id)
    {
        $broadsheet = Broadsheets::findOrFail($id);
        $termId = $broadsheet->term_id;
        $broadsheetRecord = BroadsheetRecord::find($broadsheet->broadSheet_record_id);

        if (!$broadsheetRecord) {
            return redirect()->back()->with('error', 'Broadsheet record not found.');
        }

        $schoolclass = Schoolclass::with('classcategories')->find($broadsheetRecord->schoolclass_id);
        $assessments = collect();

        if ($schoolclass && $schoolclass->classcategories->isNotEmpty()) {
            $categoryIds = $schoolclass->classcategories->pluck('id');
            $assessments = Assessment::whereIn('classcategory_id', $categoryIds)
                ->with('subAssessments')
                ->get();
        }

        // Validate dynamic assessments
        $validationRules = [];
        foreach ($assessments as $assessment) {
            $field = 'assessment_' . $assessment->id;
            $validationRules[$field] = 'nullable|numeric|min:0|max:' . $assessment->max_score;
        }
        $request->validate($validationRules);

        // Update assessment scores
        foreach ($assessments as $assessment) {
            $field = 'assessment_' . $assessment->id;
            $score = $request->input($field, 0);

            BroadsheetAssessmentScore::updateOrCreate(
                [
                    'broadsheet_id' => $id,
                    'assessment_id' => $assessment->id,
                ],
                ['score' => $score]
            );
        }

        // Recompute total, cum, grade, remark
        $broadsheet->load('assessmentScores');
        $this->computeDynamicTotals(collect([$broadsheet]), $assessments, $schoolclass, $termId, $broadsheetRecord->session_id);

        $this->updateClassMetrics($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $broadsheetRecord->session_id);
        $this->updateSubjectPositions($broadsheet->subjectclass_id, $broadsheet->staff_id, $broadsheet->term_id, $broadsheetRecord->session_id);
        $this->updateClassPositions($broadsheetRecord->schoolclass_id, $broadsheet->term_id, $broadsheetRecord->session_id);

        return redirect()->action(
            [self::class, 'classBroadsheet'],
            [
                'schoolclassid' => $broadsheetRecord->schoolclass_id,
                'subjectclassid' => $broadsheet->subjectclass_id,
                'staffid' => $broadsheet->staff_id,
                'termid' => $termId,
                'sessionid' => $broadsheetRecord->session_id,
            ]
        )->with('success', 'Score updated successfully!');
    }

    public function destroy(Request $request)
    {
        $id = $request->input('id');
        $broadsheet = Broadsheets::findOrFail($id);
        $subjectclassid = $broadsheet->subjectclass_id;
        $staffid = $broadsheet->staff_id;
        $termid = $broadsheet->term_id;

        $broadsheetRecord = DB::table('broadsheet_records')
            ->where('id', $broadsheet->broadSheet_record_id)
            ->first();

        // Delete assessment scores
        BroadsheetAssessmentScore::where('broadsheet_id', $id)->delete();
        BroadsheetSubAssessmentScore::where('broadsheet_id', $id)->delete();

        $broadsheet->delete();

        if ($broadsheetRecord) {
            $this->updateClassMetrics($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
            $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $broadsheetRecord->session_id);
            $this->updateClassPositions($broadsheetRecord->schoolclass_id, $termid, $broadsheetRecord->session_id);
        }

        return response()->json([
            'success' => true,
            'message' => 'Score deleted successfully!',
        ]);
    }

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
     * Fallback grading logic when class category is not available
     */
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

    protected function getPreviousTermCum($studentId, $subjectId, $termId, $sessionId)
    {
        if ($termId == 1) {
            Log::debug('getPreviousTermCum: Term 1, bf set to 0', [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
            ]);
            return 0;
        }

        $previousTerm = Broadsheets::where('broadsheet_records.student_id', $studentId)
            ->where('broadsheet_records.subject_id', $subjectId)
            ->where('broadsheets.term_id', $termId - 1)
            ->where('broadsheet_records.session_id', $sessionId)
            ->leftJoin('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadSheet_record_id')
            ->value('broadsheets.cum');

        if (is_null($previousTerm)) {
            Log::warning('getPreviousTermCum: No previous term cum found', [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'term_id' => $termId - 1,
                'session_id' => $sessionId,
            ]);
            return 0;
        }

        $cum = round($previousTerm, 2);
        Log::debug('getPreviousTermCum: Fetched previous cum', [
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'term_id' => $termId - 1,
            'cum' => $cum,
        ]);

        return $cum;
    }
}
