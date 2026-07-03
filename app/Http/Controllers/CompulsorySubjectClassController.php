<?php

namespace App\Http\Controllers;

use App\Models\CompulsorySubjectClass;
use App\Models\Schoolclass;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompulsorySubjectClassController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View compulsory-subject|Create compulsory-subject|Update compulsory-subject|Delete compulsory-subject', ['only' => ['index', 'store']]);
        $this->middleware('permission:Create compulsory-subject', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update compulsory-subject', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete compulsory-subject', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $pagetitle = "Compulsory Subject Class Management";

        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get(['schoolclass.id as id', 'schoolclass.schoolclass as schoolclass', 'schoolarm.arm as arm'])
            ->sortBy('schoolclass');

        $subjects = Subject::get(['id', 'subject', 'subject_code'])
            ->sortBy('subject');

        $compulsorysubjectclasses = CompulsorySubjectClass::leftJoin('schoolclass', 'compulsory_subject_classes.schoolclassid', '=', 'schoolclass.id')
            ->leftJoin('subject', 'compulsory_subject_classes.subjectId', '=', 'subject.id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select([
                'compulsory_subject_classes.id as cscid',
                'schoolclass.id as schoolclassid',
                'schoolclass.schoolclass as sclass',
                'schoolarm.arm as schoolarm',
                'subject.id as subjectid',
                'subject.subject as subjectname',
                'subject.subject_code as subjectcode',
                'compulsory_subject_classes.updated_at'
            ])
            ->orderBy('sclass')
            ->get();

        return view('compulsorysubjectclass.index')
            ->with('compulsorysubjectclasses', $compulsorysubjectclasses)
            ->with('schoolclasses', $schoolclasses)
            ->with('subjects', $subjects)
            ->with('pagetitle', $pagetitle);
    }

    public function create()
    {
        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get(['schoolclass.id as id', 'schoolclass.schoolclass as schoolclass', 'schoolarm.arm as arm'])
            ->sortBy('schoolclass');

        $subjects = Subject::get(['id', 'subject', 'subject_code'])
            ->sortBy('subject');

        return view('compulsorysubjectclass.create')
            ->with('schoolclasses', $schoolclasses)
            ->with('subjects', $subjects);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schoolclassid' => 'required|exists:schoolclass,id',
            'subjectId.*' => 'required|exists:subject,id',
        ], [
            'schoolclassid.required' => 'Please select a class!',
            'schoolclassid.exists' => 'Selected class does not exist!',
            'subjectId.*.required' => 'Please select at least one subject!',
            'subjectId.*.exists' => 'Selected subject does not exist!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $schoolClassId = $request->input('schoolclassid');
        $subjectIds = $request->input('subjectId', []);

        if (empty($subjectIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Please select at least one subject.'
            ], 422);
        }

        $createdRecords = [];
        foreach ($subjectIds as $subjectId) {
            $exists = CompulsorySubjectClass::where('schoolclassid', $schoolClassId)
                ->where('subjectId', $subjectId)
                ->exists();

            if ($exists) {
                continue;
            }

            $compulsorysubjectclass = CompulsorySubjectClass::create([
                'schoolclassid' => $schoolClassId,
                'subjectId' => $subjectId,
            ]);

            $createdRecords[] = $compulsorysubjectclass;
        }

        if (empty($createdRecords)) {
            return response()->json([
                'success' => false,
                'message' => 'All selected subjects are already assigned to this class.'
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Compulsory Subject Class(es) added successfully.',
            'data' => $createdRecords
        ], 201);
    }

    public function edit($id)
    {
        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get(['schoolclass.id as id', 'schoolclass.schoolclass as schoolclass', 'schoolarm.arm as arm'])
            ->sortBy('schoolclass');

        $subjects = Subject::get(['id', 'subject', 'subject_code'])
            ->sortBy('subject');

        $compulsorysubjectclass = CompulsorySubjectClass::where('compulsory_subject_classes.id', $id)
            ->leftJoin('schoolclass', 'compulsory_subject_classes.schoolclassid', '=', 'schoolclass.id')
            ->leftJoin('subject', 'compulsory_subject_classes.subjectId', '=', 'subject.id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->first([
                'compulsory_subject_classes.id as cscid',
                'schoolclass.id as schoolclassid',
                'schoolclass.schoolclass as sclass',
                'schoolarm.arm as schoolarm',
                'subject.id as subjectid',
                'subject.subject as subjectname',
                'subject.subject_code as subjectcode',
                'compulsory_subject_classes.updated_at'
            ]);

        if (!$compulsorysubjectclass) {
            return redirect()->route('compulsorysubjectclass.index')->with('danger', 'Compulsory Subject Class not found.');
        }

        return view('compulsorysubjectclass.edit')
            ->with('compulsorysubjectclasses', collect([$compulsorysubjectclass]))
            ->with('schoolclasses', $schoolclasses)
            ->with('subjects', $subjects);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'schoolclassid' => 'required|exists:schoolclass,id',
            'subjectId' => 'required|exists:subject,id',
        ], [
            'schoolclassid.required' => 'Please select a class!',
            'schoolclassid.exists' => 'Selected class does not exist!',
            'subjectId.required' => 'Please select a subject!',
            'subjectId.exists' => 'Selected subject does not exist!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $schoolClassId = $request->input('schoolclassid');
        $subjectId = $request->input('subjectId');

        $exists = CompulsorySubjectClass::where('schoolclassid', $schoolClassId)
            ->where('subjectId', $subjectId)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This subject is already assigned to this class.'
            ], 422);
        }

        $compulsorysubjectclass = CompulsorySubjectClass::updateOrCreate(
            ['id' => $id],
            [
                'schoolclassid' => $schoolClassId,
                'subjectId' => $subjectId,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Compulsory Subject Class updated successfully.',
            'data' => $compulsorysubjectclass
        ], 200);
    }

    public function destroy($id)
    {
        $compulsorysubjectclass = CompulsorySubjectClass::find($id);
        if (!$compulsorysubjectclass) {
            return response()->json([
                'success' => false,
                'message' => 'Compulsory Subject Class not found.'
            ], 404);
        }

        $compulsorysubjectclass->delete();

        return response()->json([
            'success' => true,
            'message' => 'Compulsory Subject Class deleted successfully.'
        ], 200);
    }
}