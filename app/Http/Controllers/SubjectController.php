<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Subject;

class SubjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View subjects|Create subjects|Update subjects|Delete subjects', ['only' => ['index', 'store']]);
        $this->middleware('permission:Create subjects', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update subjects', ['only' => ['edit', 'update', 'updatesubject']]);
        $this->middleware('permission:Delete subjects', ['only' => ['destroy', 'deletesubject']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $pagetitle = "Subject Management";
        $subjects = Subject::paginate(100); // Paginate with 10 items per page

        if ($request->ajax()) {
            return response()->json([
                'html' => view('subject.partials.table', compact('subjects'))->render(),
                'pagination' => view('subject.partials.pagination', compact('subjects'))->render(),
                'count' => $subjects->count(),
                'total' => $subjects->total(),
            ]);
        }

        return view('subject.index', compact('subjects', 'pagetitle'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('subject.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|unique:subject,subject',
            'subject_code' => 'required|min:3|unique:subject,subject_code',
            'remark' => 'required',
        ], [
            'subject.required' => 'Please enter a subject name!',
            'subject.unique' => 'This subject name is already taken!',
            'subject_code.required' => 'Please enter a subject code!',
            'subject_code.min' => 'Subject code must be at least 4 characters!',
            'subject_code.unique' => 'This subject code is already taken!',
            'remark.required' => 'Please enter a remark!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $subject = Subject::create([
            'subject' => $request->input('subject'),
            'subject_code' => $request->input('subject_code'),
            'remark' => $request->input('remark'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subject added successfully.',
            'data' => $subject
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $subject = Subject::find($id);
        if (!$subject) {
            return redirect()->route('subject.index')->with('danger', 'Subject not found.');
        }

        return view('subject.edit')->with('subject', $subject);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|unique:subject,subject,' . $id,
            'subject_code' => 'required|min:3|unique:subject,subject_code,' . $id,
            'remark' => 'required',
        ], [
            'subject.required' => 'Please enter a subject name!',
            'subject.unique' => 'This subject name is already taken!',
            'subject_code.required' => 'Please enter a subject code!',
            'subject_code.min' => 'Subject code must be at least 4 characters!',
            'subject_code.unique' => 'This subject code is already taken!',
            'remark.required' => 'Please enter a remark!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $subject = Subject::find($id);
        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found.'
            ], 404);
        }

        $subject->update([
            'subject' => $request->input('subject'),
            'subject_code' => $request->input('subject_code'),
            'remark' => $request->input('remark'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subject updated successfully.',
            'data' => $subject
        ], 200);
    }

    /**
     * Legacy update method for non-AJAX requests.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updatesubject(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subject' => 'required|unique:subject,subject,' . $request->id,
            'subject_code' => 'required|min:4|unique:subject,subject_code,' . $request->id,
            'remark' => 'required',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $subject = Subject::find($request->id);
        if (!$subject) {
            return redirect()->back()->with('danger', 'Subject not found.');
        }

        $subject->update([
            'subject' => $request->input('subject'),
            'subject_code' => $request->input('subject_code'),
            'remark' => $request->input('remark'),
        ]);

        return redirect()->back()->with('success', 'Subject updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $subject = Subject::find($id);
        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found.'
            ], 404);
        }

        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subject deleted successfully.'
        ], 200);
    }

    /**
     * Handle AJAX delete request for subject.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deletesubject(Request $request)
    {
        $subject = Subject::find($request->subjectid);
        if (!$subject) {
            return response()->json([
                'success' => false,
                'message' => 'Subject not found.'
            ], 404);
        }

        $subject->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subject has been removed.'
        ], 200);
    }
}