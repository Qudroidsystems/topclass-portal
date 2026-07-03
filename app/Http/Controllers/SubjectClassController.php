<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Broadsheet;
use App\Models\Schoolterm;
use App\Models\Broadsheets;
use App\Models\Schoolclass;
use App\Models\Subjectclass;
use Illuminate\Http\Request;
use App\Models\Schoolsession;
use App\Models\SubjectTeacher;
use App\Models\BroadsheetsMock;
use App\Http\Controllers\Controller;
use App\Models\SubjectRegistrationStatus;
use Illuminate\Support\Facades\Validator;

class SubjectClassController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View subject-class|Create subject-class|Update subject-class|Delete subject-class', ['only' => ['index', 'store']]);
        $this->middleware('permission:Create subject-class', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update subject-class', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete subject-class', ['only' => ['destroy', 'deletesubjectclass']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Subject Class Management";

        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get(['schoolclass.id as id', 'schoolclass.schoolclass as schoolclass', 'schoolarm.arm as arm'])
            ->sortBy('schoolclass');

        $subjectteacher = SubjectTeacher::leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->get([
                'subjectteacher.id as id',
                'subjectteacher.staffid as subtid',
                'subjectteacher.subjectid as subid',
                'subject.id as subjectid',
                'subject.subject as subject',
                'subject.subject_code as subjectcode',
                'users.name as teachername',
                'schoolterm.id as termid',
                'schoolterm.term as termname',
                'schoolsession.id as sessionid',
                'schoolsession.session as sessionname'
            ])
            ->sortBy('subject');

        $subjectclasses = Subjectclass::leftJoin('schoolclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select([
                'subjectclass.id as scid',
                'schoolclass.id as schoolclassid',
                'schoolclass.schoolclass as sclass',
                'schoolarm.arm as schoolarm',
                'subjectteacher.id as subteacherid',
                'subjectteacher.staffid as subtid',
                'subjectteacher.subjectid as subid',
                'subject.id as subjectid',
                'subject.subject as subjectname',
                'subject.subject_code as subjectcode',
                'users.name as teachername',
                'users.avatar as picture',
                'schoolterm.id as termid',
                'schoolterm.term as termname',
                'schoolsession.id as sessionid',
                'schoolsession.session as sessionname',
                'subjectclass.updated_at'
            ])
            ->orderBy('sclass')
            ->get();

        return view('subjectclass.index')
            ->with('subjectclasses', $subjectclasses)
            ->with('schoolclasses', $schoolclasses)
            ->with('subjectteacher', $subjectteacher)
            ->with('pagetitle', $pagetitle);
    }

    public function create()
    {
        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get(['schoolclass.id as id', 'schoolclass.schoolclass as schoolclass', 'schoolarm.arm as arm'])
            ->sortBy('schoolclass');

        $subjectteachers = SubjectTeacher::leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->get([
                'subjectteacher.id as id',
                'subjectteacher.staffid as subtid',
                'subjectteacher.subjectid as subid',
                'subject.id as subjectid',
                'subject.subject as subject',
                'subject.subject_code as subjectcode',
                'users.name as teachername',
                'schoolterm.id as termid',
                'schoolterm.term as termname',
                'schoolsession.id as sessionid',
                'schoolsession.session as sessionname'
            ])
            ->sortBy('subject');

        return view('subjectclass.create')
            ->with('schoolclasses', $schoolclasses)
            ->with('subjectteacher', $subjectteachers);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schoolclassid' => 'required|exists:schoolclass,id',
            'subjectteacherid.*' => 'required|exists:subjectteacher,id',
        ], [
            'schoolclassid.required' => 'Please select a class!',
            'schoolclassid.exists' => 'Selected class does not exist!',
            'subjectteacherid.*.required' => 'Please select at least one subject teacher!',
            'subjectteacherid.*.exists' => 'Selected subject teacher does not exist!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $schoolClassId = $request->input('schoolclassid');
        $subjectTeacherIds = $request->input('subjectteacherid', []);

        if (empty($subjectTeacherIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Please select at least one subject teacher.'
            ], 422);
        }

        $createdRecords = [];
        $subjectTeachers = SubjectTeacher::whereIn('id', $subjectTeacherIds)->get();

        foreach ($subjectTeacherIds as $subjectTeacherId) {
            $subjectTeacher = $subjectTeachers->firstWhere('id', $subjectTeacherId);
            if (!$subjectTeacher) {
                continue;
            }

            $exists = Subjectclass::where('schoolclassid', $schoolClassId)
                ->where('subjectteacherid', $subjectTeacherId)
                ->exists();

            if ($exists) {
                continue;
            }

            $subjectclass = Subjectclass::create([
                'schoolclassid' => $schoolClassId,
                'subjectteacherid' => $subjectTeacherId,
                'subjectid' => $subjectTeacher->subjectid,
            ]);

            $createdRecords[] = $subjectclass;
        }

        if (empty($createdRecords)) {
            return response()->json([
                'success' => false,
                'message' => 'All selected subject teachers are already assigned to this class.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Subject Class(es) added successfully.',
            'data' => $createdRecords
        ], 201);
    }

    public function edit($id)
    {
        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get(['schoolclass.id as id', 'schoolclass.schoolclass as schoolclass', 'schoolarm.arm as arm'])
            ->sortBy('schoolclass');

        $subjectteachers = SubjectTeacher::leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->get([
                'subjectteacher.id as id',
                'subjectteacher.staffid as subtid',
                'subjectteacher.subjectid as subid',
                'subject.id as subjectid',
                'subject.subject as subject',
                'subject.subject_code as subjectcode',
                'users.name as teachername',
                'schoolterm.id as termid',
                'schoolterm.term as termname',
                'schoolsession.id as sessionid',
                'schoolsession.session as sessionname'
            ])
            ->sortBy('subject');

        $subjectclasses = Subjectclass::where('subjectclass.id', $id)
            ->leftJoin('schoolclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
            ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
            ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->first([
                'subjectclass.id as scid',
                'schoolclass.id as schoolclassid',
                'schoolclass.schoolclass as sclass',
                'schoolarm.arm as schoolarm',
                'subjectteacher.id as subteacherid',
                'subjectteacher.staffid as subtid',
                'subjectteacher.subjectid as subid',
                'subject.id as subjectid',
                'subject.subject as subjectname',
                'subject.subject_code as subjectcode',
                'users.name as teachername',
                'users.avatar as picture',
                'schoolterm.id as termid',
                'schoolterm.term as termname',
                'schoolsession.id as sessionid',
                'schoolsession.session as sessionname',
                'subjectclass.updated_at'
            ]);

        if (!$subjectclasses) {
            return redirect()->route('subjectclass.index')->with('danger', 'Subject Class not found.');
        }

        return view('subjectclass.edit')
            ->with('subjectclasses', collect([$subjectclasses]))
            ->with('schoolclasses', $schoolclasses)
            ->with('subjectteachers', $subjectteachers);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'schoolclassid' => 'required|exists:schoolclass,id',
            'subjectteacherid' => 'required|exists:subjectteacher,id',
        ], [
            'schoolclassid.required' => 'Please select a class!',
            'schoolclassid.exists' => 'Selected class does not exist!',
            'subjectteacherid.required' => 'Please select a subject teacher!',
            'subjectteacherid.exists' => 'Selected subject teacher does not exist!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $schoolClassId = $request->input('schoolclassid');
        $subjectTeacherId = $request->input('subjectteacherid');

        $subjectTeacher = SubjectTeacher::find($subjectTeacherId);
        if (!$subjectTeacher) {
            return response()->json([
                'success' => false,
                'message' => 'Subject teacher not found.'
            ], 404);
        }

        $exists = Subjectclass::where('schoolclassid', $schoolClassId)
            ->where('subjectteacherid', $subjectTeacherId)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This subject teacher is already assigned to this class.'
            ], 422);
        }

        $subjectclass = Subjectclass::updateOrCreate(
            ['id' => $id],
            [
                'schoolclassid' => $schoolClassId,
                'subjectteacherid' => $subjectTeacherId,
                'subjectid' => $subjectTeacher->subjectid,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Subject Class updated successfully.',
            'data' => $subjectclass
        ], 200);
    }

    public function assignments($subjectClassId)
    {
        try {
            $subjectclass = Subjectclass::where('id', $subjectClassId)
                ->select('schoolclassid', 'subjectteacherid')
                ->first();
    
            if (!$subjectclass) {
                return response()->json([
                    'success' => false,
                    'message' => 'Subject Class not found.'
                ], 404);
            }
    
            return response()->json([
                'success' => true,
                'data' => [
                    'schoolclassid' => $subjectclass->schoolclassid,
                    'subjectteacherid' => [$subjectclass->subjectteacherid],
                ]
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching assignments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assignments'
            ], 500);
        }
    }

    public function assignmentsBySubjectTeacher($subjectTeacherId)
    {
        try {
            $assignments = Subjectclass::where('subjectteacherid', $subjectTeacherId)
                ->select('schoolclassid')
                ->get();
            return response()->json([
                'success' => true,
                'data' => $assignments
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Error fetching assignments by subject teacher: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch assignments'
            ], 500);
        }
    }

    public function destroy($id)
    {
        $subjectclass = Subjectclass::find($id);
        if (!$subjectclass) {
            return response()->json([
                'success' => false,
                'message' => 'Subject Class not found.'
            ], 404);
        }

        // Delete from Broadsheets (regular)
        Broadsheets::where('subjectclass_id', $id)->delete();
        
        // Delete from BroadsheetsMock (mock/exam)
        BroadsheetsMock::where('subjectclass_id', $id)->delete();
        
        // Delete from SubjectRegistrationStatus
        SubjectRegistrationStatus::where('subjectclassid', $id)->delete();
        
        $subjectclass->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subject Class deleted successfully.'
        ], 200);
    }

    public function deletesubjectclass(Request $request)
    {
        $subjectclass = Subjectclass::find($request->subjectclassid);
        if (!$subjectclass) {
            return response()->json([
                'success' => false,
                'message' => 'Subject Class not found.'
            ], 404);
        }

        // Delete from Broadsheets (regular)
        Broadsheets::where('subjectclass_id', $request->subjectclassid)->delete();
        
        // Delete from BroadsheetsMock (mock/exam)
        BroadsheetsMock::where('subjectclass_id', $request->subjectclassid)->delete();
        
        // Delete from SubjectRegistrationStatus
        SubjectRegistrationStatus::where('subjectclassid', $request->subjectclassid)->delete();
        
        $subjectclass->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subject Class has been removed.'
        ], 200);
    }
}