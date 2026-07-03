<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SchoolBillModel;
use Illuminate\Support\Facades\Validator;

class SchoolBillController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View school-bills|Create school-bills|Update school-bills|Delete school-bills', ['only' => ['index', 'store']]);
        $this->middleware('permission:Create school-bills', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update school-bills', ['only' => ['edit', 'update', 'updatebill']]);
        $this->middleware('permission:Delete school-bills', ['only' => ['destroy', 'deletebill']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pagetitle = "School Bill Management";

        $schoolbills = SchoolBillModel::leftJoin('student_status', 'student_status.id', '=', 'school_bill.statusId')
            ->whereIn('student_status.id', [1, 2])
            ->select([
                'school_bill.id as id',
                'school_bill.title as title',
                'school_bill.description as description',
                'school_bill.bill_amount as bill_amount',
                'student_status.id as statusId',
                'school_bill.updated_at as updated_at'
            ])
            ->paginate(1000); // Paginate with 10 records per page

        return view('schoolbill.index')
            ->with('schoolbills', $schoolbills)
            ->with('pagetitle', $pagetitle);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('schoolbill.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|min:1|unique:school_bill,title',
            'bill_amount' => 'required|numeric|min:1',
            'description' => 'required',
            'statusId' => 'required|in:1,2',
        ], [
            'title.required' => 'Please enter a bill title!',
            'title.unique' => 'This bill title already exists!',
            'bill_amount.required' => 'Please enter a bill amount!',
            'bill_amount.numeric' => 'Bill amount must be a number!',
            'bill_amount.min' => 'Bill amount must be at least 1!',
            'description.required' => 'Please enter a description!',
            'statusId.required' => 'Please select a student status!',
            'statusId.in' => 'Invalid student status selected!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $plainNumberString = str_replace(['₦', ','], '', $request->bill_amount);
        $number = floatval($plainNumberString);

        $sbill = SchoolBillModel::create([
            'title' => $request->title,
            'bill_amount' => $number,
            'description' => $request->description,
            'statusId' => $request->statusId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'School Bill created successfully!',
            'data' => $sbill
        ], 201);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $bill = SchoolBillModel::find($id);
        if (!$bill) {
            return redirect()->route('schoolbill.index')->with('danger', 'School Bill not found.');
        }

        return view('schoolbill.edit', compact('bill'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $sbill = SchoolBillModel::find($id);
        if (!$sbill) {
            return response()->json([
                'success' => false,
                'message' => 'School Bill not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|min:1|unique:school_bill,title,' . $id,
            'bill_amount' => 'required|numeric|min:1',
            'description' => 'required',
            'statusId' => 'required|in:1,2',
        ], [
            'title.required' => 'Please enter a bill title!',
            'title.unique' => 'This bill title already exists!',
            'bill_amount.required' => 'Please enter a bill amount!',
            'bill_amount.numeric' => 'Bill amount must be a number!',
            'bill_amount.min' => 'Bill amount must be at least 1!',
            'description.required' => 'Please enter a description!',
            'statusId.required' => 'Please select a student status!',
            'statusId.in' => 'Invalid student status selected!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $plainNumberString = str_replace(['₦', ','], '', $request->bill_amount);
        $number = floatval($plainNumberString);

        $sbill->update([
            'title' => $request->title,
            'bill_amount' => $number,
            'description' => $request->description,
            'statusId' => $request->statusId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'School Bill updated successfully!',
            'data' => $sbill
        ], 200);
    }

    /**
     * Custom update method for AJAX.
     */
    public function updatebill(Request $request)
    {
        return $this->update($request, $request->id);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $sbill = SchoolBillModel::find($id);
        if (!$sbill) {
            return response()->json([
                'success' => false,
                'message' => 'School Bill not found.'
            ], 404);
        }

        $sbill->delete();

        return response()->json([
            'success' => true,
            'message' => 'School Bill deleted successfully.'
        ], 200);
    }

    /**
     * Custom delete method for AJAX.
     */
    public function deletebill(Request $request)
    {
        return $this->destroy($request->billid);
    }
}