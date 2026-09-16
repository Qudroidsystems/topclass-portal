<?php
// app/Http/Controllers/MySubjectVettingsController.php

namespace App\Http\Controllers;

use App\Models\Broadsheets;
use App\Models\BroadsheetRecord;
use App\Models\PromotionStatus;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Studentclass;
use App\Models\SubjectVetting;
use App\Models\Subjectclass;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MySubjectVettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View my-subject-vettings',   ['only' => ['index', 'classBroadsheet']]);
        $this->middleware('permission:Update my-subject-vettings', ['only' => ['update', 'updateVettedStatus']]);
    }

    // =========================================================================
    // INDEX — list vetting assignments
    // =========================================================================

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
                    'subjectteacher.id as subtid',
                    'subject.id as subjectid',
                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',
                    'teacher_user.name as teachername',
                    'schoolterm.id as termid',
                    'schoolterm.term as termname',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as sessionname',
                    'subject_vettings.status',
                    'subject_vettings.updated_at',
                ])
                ->orderBy('schoolterm.term')
                ->orderBy('schoolsession.session')
                ->orderBy('subject.subject')
                ->get();

            $statusCounts = SubjectVetting::where('subject_vettings.userid', Auth::id())
                ->groupBy('status')
                ->selectRaw('status, COUNT(*) as count')
                ->pluck('count', 'status')
                ->toArray();

            $statusCounts = array_merge([
                'pending'   => 0,
                'completed' => 0,
                'rejected'  => 0,
            ], $statusCounts);

            $terms    = Schoolterm::get(['id', 'term'])->sortBy('term');
            $sessions = Schoolsession::get(['id', 'session'])->sortBy('session');

            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success'        => true,
                    'subjectvettings' => $subjectvettings,
                    'statusCounts'    => $statusCounts,
                ], 200);
            }

            return view('mysubjectvettings.index', compact(
                'subjectvettings', 'terms', 'sessions', 'pagetitle', 'statusCounts'
            ));

        } catch (\Exception $e) {
            Log::error('Error loading my subject vetting index: ' . $e->getMessage());

            if ($request->header('X-Requested-With') === 'XMLHttpRequest') {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load subject vetting data: ' . $e->getMessage(),
                ], 500);
            }

            return view('mysubjectvettings.index', [
                'subjectvettings' => collect([]),
                'terms'           => collect([]),
                'sessions'        => collect([]),
                'pagetitle'       => 'My Subject Vetting Assignments',
                'statusCounts'    => ['pending' => 0, 'completed' => 0, 'rejected' => 0],
            ])->with('danger', 'Failed to load subject vetting data: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // CLASS BROADSHEET — view for vetting a specific subject/class/term/session
    // =========================================================================

    public function classBroadsheet($schoolclassid, $subjectclassid, $staffid, $termid, $sessionid)
    {
        Log::info('classBroadsheet parameters:', compact('staffid', 'termid', 'sessionid', 'schoolclassid', 'subjectclassid'));

        $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

        if ($broadsheets->isEmpty()) {
            Log::warning('No broadsheets found for classBroadsheet', compact('staffid', 'termid', 'sessionid', 'schoolclassid', 'subjectclassid'));
            $pagetitle = "Class Broadsheet";
        } else {
            // Update metrics and positions
            $this->updateClassMetrics($subjectclassid, $staffid, $termid, $sessionid);
            $this->updateSubjectPositions($subjectclassid, $staffid, $termid, $sessionid);
            $this->updateClassPositions($schoolclassid, $termid, $sessionid);

            // Refresh
            $broadsheets = $this->getBroadsheets($staffid, $termid, $sessionid, $schoolclassid, $subjectclassid);

            $first = $broadsheets->first();
            $pagetitle = sprintf(
                'Class Broadsheet for %s (%s) - %s %s - %s %s',
                $first->subject,
                $first->subject_code,
                $first->schoolclass,
                $first->arm,
                $first->term,
                $first->session
            );
        }

        $schoolclass   = Schoolclass::with('classcategory')->find($schoolclassid);
        $schoolterm    = Schoolterm::where('id', $termid)->value('term')    ?? 'N/A';
        $schoolsession = Schoolsession::where('id', $sessionid)->value('session') ?? 'N/A';

        // Defaults for empty state
        $isSenior = $schoolclass && $schoolclass->classcategory
            ? (bool) $schoolclass->classcategory->is_senior
            : false;

        return view('mysubjectvettings.classbroadsheet', compact(
            'broadsheets',
            'schoolclass',
            'schoolterm',
            'schoolsession',
            'schoolclassid',
            'sessionid',
            'termid',
            'pagetitle',
            'isSenior'
        ));
    }

    // =========================================================================
    // QUERY: getBroadsheets
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
            ->where('broadsheet_records.session_id', $sessionId);

        if ($schoolClassId) {
            $query->where('schoolclass.id', $schoolClassId);
        }

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

            // ✅ Fixed CA columns
            'broadsheets.ca1',
            'broadsheets.ca2',
            'broadsheets.ca3',
            'broadsheets.exam',

            'broadsheets.total',
            'broadsheets.bf',
            'broadsheets.cum',
            'broadsheets.grade',
            'broadsheets.subject_position_class as position',
            'broadsheets.remark',
            'broadsheets.vettedstatus',
            'broadsheets.cmin',
            'broadsheets.cmax',
            'broadsheets.avg',
        ])->sortBy('lastname');

        return $results;
    }

    // =========================================================================
    // UPDATE VETTED STATUS (single row toggle)
    // =========================================================================

    public function updateVettedStatus(Request $request)
    {
        $request->validate([
            'broadsheet_id' => 'required|exists:broadsheets,id',
            'vettedstatus'  => 'required|in:0,1',
        ]);

        try {
            $broadsheet = Broadsheets::findOrFail($request->broadsheet_id);

            $broadsheet->vettedstatus = $request->vettedstatus;
            $broadsheet->vettedby     = Auth::id();
            $broadsheet->save();

            Log::info('Vetted status updated', [
                'broadsheet_id' => $broadsheet->id,
                'vettedstatus'  => $broadsheet->vettedstatus,
                'vettedby'      => $broadsheet->vettedby,
            ]);

            $allVetted = $this->checkAllBroadsheetsVetted(
                $broadsheet->term_id,
                $broadsheet->subjectclass_id,
                Auth::id()
            );

            $subjectVetting = SubjectVetting::where('userid', Auth::id())
                ->where('termid', $broadsheet->term_id)
                ->where('subjectclassid', $broadsheet->subjectclass_id)
                ->first();

            if ($subjectVetting) {
                $newStatus = $allVetted ? 'completed' : 'pending';
                if ($subjectVetting->status !== $newStatus) {
                    $subjectVetting->status = $newStatus;
                    $subjectVetting->save();

                    Log::info('SubjectVetting status updated', [
                        'subjectvetting_id' => $subjectVetting->id,
                        'new_status'        => $newStatus,
                    ]);
                }
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Failed to update vetted status', [
                'broadsheet_id' => $request->broadsheet_id,
                'error'         => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update vetted status: ' . $e->getMessage(),
            ], 500);
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

        return $totalBroadsheets > 0 && $totalBroadsheets === $vettedBroadsheets;
    }

    // =========================================================================
    // UPDATE — bulk update vetted status for multiple rows
    // =========================================================================

    public function update(Request $request, $id)
    {
        try {
            $vettedstatus = $request->input('vettedstatus', $request->input('status'));

            // Support both single-toggle and bulk-list payloads
            if ($request->has('broadsheet_ids')) {
                $ids = $request->input('broadsheet_ids', []);
                Broadsheets::whereIn('id', $ids)->update([
                    'vettedstatus' => $vettedstatus,
                    'vettedby'     => Auth::id(),
                ]);
            } else {
                $broadsheet = Broadsheets::findOrFail($id);
                $broadsheet->update([
                    'vettedstatus' => $vettedstatus,
                    'vettedby'     => Auth::id(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Vetted status updated.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Update failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // CLASS METRICS — min / max / avg by CUM
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
            ->update([
                'cmin' => $classMin,
                'cmax' => $classMax,
                'avg'  => $classAvg,
            ]);
    }

    // =========================================================================
    // SUBJECT POSITIONS
    // =========================================================================

    protected function updateSubjectPositions($subjectclass_id, $staff_id, $term_id, $session_id)
    {
        $broadsheets = Broadsheets::where('subjectclass_id', $subjectclass_id)
            ->where('staff_id', $staff_id)
            ->where('term_id', $term_id)
            ->where('broadsheet_records.session_id', $session_id)
            ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
            ->orderByDesc('broadsheets.cum')
            ->orderBy('broadsheets.id')
            ->get();

        if ($broadsheets->isEmpty()) return;

        $rank = 0;
        $lastCum = null;
        $lastPosition = 0;

        foreach ($broadsheets as $broadsheet) {
            $rank++;
            if ($lastCum !== null && $broadsheet->cum == $lastCum) {
                // tied
            } else {
                $lastPosition = $rank;
                $lastCum = $broadsheet->cum;
            }
            if ($broadsheet->subject_position_class != $lastPosition) {
                $broadsheet->subject_position_class = $lastPosition;
                $broadsheet->save();
            }
        }
    }

    // =========================================================================
    // CLASS POSITIONS
    // =========================================================================

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
                $rank      = $rows;
            }
            $position = match ($rank) {
                1 => 'st', 2 => 'nd', 3 => 'rd', default => 'th',
            };
            PromotionStatus::where('id', $row->id)->update(['position' => $rank . $position]);
        }
    }

    // =========================================================================
    // FALLBACK GRADE / REMARK
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
}