<?php

namespace App\Http\Controllers;

use App\Models\Schoolterm;
use App\Models\Schoolclass;
use Illuminate\Http\Request;
use App\Models\Schoolsession;
use App\Models\SchoolBillModel;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolBillTermSession;
use Illuminate\Support\Facades\Validator;

class SchoolBillTermSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View school-bill-for-term-session|Create school-bill-for-term-session|Update school-bill-for-term-session|Delete school-bill-for-term-session', ['only' => ['index', 'store']]);
        $this->middleware('permission:Create school-bill-for-term-session', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update school-bill-for-term-session', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete school-bill-for-term-session', ['only' => ['destroy', 'deleteschoolbilltermsession']]);
    }

    public function index()
    {
        $pagetitle = "School Bill Term Session Management";

        $terms = Schoolterm::all();
        $sessions = Schoolsession::all();
        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id as id', 'schoolclass.schoolclass as schoolclass', 'schoolarm.arm as arm'])
            ->orderBy('schoolclass')
            ->get();
        $schoolbills = SchoolBillModel::all();

        $schoolbillclasstermsessions = SchoolBillTermSession::leftJoin('school_bill', 'school_bill.id', '=', 'school_bill_class_term_session.bill_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'school_bill_class_term_session.class_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'school_bill_class_term_session.termid_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'school_bill_class_term_session.session_id')
            ->leftJoin('users', 'users.id', '=', 'school_bill_class_term_session.createdBy')
            ->select([
                'school_bill_class_term_session.id as id',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm as schoolarm',
                'schoolterm.term as schoolterm',
                'schoolsession.session as schoolsession',
                'users.name as createdBy',
                'school_bill.title as schoolbill',
                'school_bill_class_term_session.updated_at as updated_at'
            ])
            ->paginate(100);

        return view('schoolbilltermsession.index')
            ->with('schoolbills', $schoolbills)
            ->with('schoolclasses', $schoolclasses)
            ->with('terms', $terms)
            ->with('schoolsessions', $sessions)
            ->with('schoolbillclasstermsessions', $schoolbillclasstermsessions)
            ->with('pagetitle', $pagetitle);
    }

    public function create()
    {
        return view('schoolbilltermsession.create');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bill_id' => 'required|exists:school_bill,id',
            'class_id' => 'required|array|min:1',
            'class_id.*' => 'exists:schoolclass,id',
            'termid_id' => 'required|array|min:1',
            'termid_id.*' => 'exists:schoolterm,id',
            'session_id' => 'required|exists:schoolsession,id',
        ], [
            'bill_id.required' => 'Please select a school bill!',
            'bill_id.exists' => 'Selected school bill does not exist!',
            'class_id.required' => 'Please select at least one class!',
            'class_id.array' => 'Classes must be an array!',
            'class_id.min' => 'Please select at least one class!',
            'class_id.*.exists' => 'One or more selected classes do not exist!',
            'termid_id.required' => 'Please select at least one term!',
            'termid_id.array' => 'Terms must be an array!',
            'termid_id.min' => 'Please select at least one term!',
            'termid_id.*.exists' => 'One or more selected terms do not exist!',
            'session_id.required' => 'Please select a session!',
            'session_id.exists' => 'Selected session does not exist!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $bill_id = $request->input('bill_id');
        $class_ids = $request->input('class_id');
        $term_ids = $request->input('termid_id');
        $session_id = $request->input('session_id');

        // Check for existing combinations
        $existing = SchoolBillTermSession::where('bill_id', $bill_id)
            ->whereIn('class_id', $class_ids)
            ->whereIn('termid_id', $term_ids)
            ->where('session_id', $session_id)
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'One or more combinations of bill, class, term, and session already exist!'
            ], 422);
        }

        $createdRecords = [];
        foreach ($term_ids as $term_id) {
            foreach ($class_ids as $class_id) {
                $record = SchoolBillTermSession::create([
                    'bill_id' => $bill_id,
                    'class_id' => $class_id,
                    'termid_id' => $term_id,
                    'session_id' => $session_id,
                    'createdBy' => auth()->id()
                ]);
                $createdRecords[] = $record;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'School Bill Term Session(s) created successfully!',
            'data' => $createdRecords
        ], 201);
    }

    public function edit(string $id)
    {
        $schoolbillclasstermsessions = SchoolBillTermSession::where('school_bill_class_term_session.id', $id)
            ->leftJoin('school_bill', 'school_bill.id', '=', 'school_bill_class_term_session.bill_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'school_bill_class_term_session.class_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'school_bill_class_term_session.termid_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'school_bill_class_term_session.session_id')
            ->leftJoin('users', 'users.id', '=', 'school_bill_class_term_session.createdBy')
            ->select([
                'school_bill_class_term_session.id as id',
                'school_bill_class_term_session.bill_id as bill_id',
                'school_bill_class_term_session.class_id as class_id',
                'school_bill_class_term_session.termid_id as termid_id',
                'school_bill_class_term_session.session_id as session_id',
                'schoolclass.schoolclass as schoolclass',
                'schoolclass.id as schoolclassid',
                'schoolarm.arm as schoolarm',
                'schoolterm.term as schoolterm',
                'schoolterm.id as schooltermid',
                'schoolsession.id as schoolsessionid',
                'schoolsession.session as schoolsession',
                'users.name as createdBy',
                'school_bill.title as schoolbill',
                'school_bill.id as schoolbill_id',
                'school_bill_class_term_session.updated_at as updated_at'
            ])
            ->first();

        if (!$schoolbillclasstermsessions) {
            return redirect()->route('schoolbilltermsession.index')->with('danger', 'School Bill Term Session not found.');
        }

        $terms = Schoolterm::all();
        $sessions = Schoolsession::all();
        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(['schoolclass.id as id', 'schoolclass.schoolclass as schoolclass', 'schoolarm.arm as arm'])
            ->orderBy('schoolclass')
            ->get();
        $schoolbills = SchoolBillModel::all();

        return view('schoolbilltermsession.edit')
            ->with('schoolbills', $schoolbills)
            ->with('sclasses', $schoolclasses)
            ->with('schoolterms', $terms)
            ->with('schoolsessions', $sessions)
            ->with('schoolbillclasstermsessions', $schoolbillclasstermsessions);
    }

    public function update(Request $request, string $id)
    {
        $schoolbillclasstermsessions = SchoolBillTermSession::find($id);
        if (!$schoolbillclasstermsessions) {
            return response()->json([
                'success' => false,
                'message' => 'School Bill Term Session not found.'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'bill_id' => 'required|exists:school_bill,id',
            'class_id' => 'required|array|min:1',
            'class_id.*' => 'exists:schoolclass,id',
            'termid_id' => 'required|array|min:1',
            'termid_id.*' => 'exists:schoolterm,id',
            'session_id' => 'required|exists:schoolsession,id',
        ], [
            'bill_id.required' => 'Please select a school bill!',
            'bill_id.exists' => 'Selected school bill does not exist!',
            'class_id.required' => 'Please select at least one class!',
            'class_id.array' => 'Classes must be an array!',
            'class_id.min' => 'Please select at least one class!',
            'class_id.*.exists' => 'One or more selected classes do not exist!',
            'termid_id.required' => 'Please select at least one term!',
            'termid_id.array' => 'Terms must be an array!',
            'termid_id.min' => 'Please select at least one term!',
            'termid_id.*.exists' => 'One or more selected terms do not exist!',
            'session_id.required' => 'Please select a session!',
            'session_id.exists' => 'Selected session does not exist!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $bill_id = $request->input('bill_id');
        $class_ids = $request->input('class_id');
        $term_ids = $request->input('termid_id');
        $session_id = $request->input('session_id');

        // Check for existing combinations, excluding the current record
        $existing = SchoolBillTermSession::where('bill_id', $bill_id)
            ->whereIn('class_id', $class_ids)
            ->whereIn('termid_id', $term_ids)
            ->where('session_id', $session_id)
            ->where('id', '!=', $id)
            ->exists();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'One or more combinations of bill, class, term, and session already exist!'
            ], 422);
        }

        // Delete existing records for this bill_id and session_id to avoid duplicates
        SchoolBillTermSession::where('bill_id', $schoolbillclasstermsessions->bill_id)
            ->where('session_id', $schoolbillclasstermsessions->session_id)
            ->where('class_id', $schoolbillclasstermsessions->class_id)
            ->where('termid_id', $schoolbillclasstermsessions->termid_id)
            ->delete();

        $updatedRecords = [];
        foreach ($term_ids as $term_id) {
            foreach ($class_ids as $class_id) {
                $record = SchoolBillTermSession::create([
                    'bill_id' => $bill_id,
                    'class_id' => $class_id,
                    'termid_id' => $term_id,
                    'session_id' => $session_id,
                    'createdBy' => auth()->id()
                ]);
                $updatedRecords[] = $record;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'School Bill Term Session updated successfully!',
            'data' => $updatedRecords
        ], 200);
    }

    public function destroy(string $id)
    {
        $schoolbillclasstermsessions = SchoolBillTermSession::find($id);
        if (!$schoolbillclasstermsessions) {
            return response()->json([
                'success' => false,
                'message' => 'School Bill Term Session not found.'
            ], 404);
        }

        $schoolbillclasstermsessions->delete();

        return response()->json([
            'success' => true,
            'message' => 'School Bill Term Session deleted successfully.'
        ], 200);
    }



    public function getRelated($id)
    {
        \Log::info('getRelated called', ['id' => $id]);
    
        $schoolbillclasstermsessions = SchoolBillTermSession::where('id', $id)->first();
        if (!$schoolbillclasstermsessions) {
            \Log::error('School Bill Term Session not found', ['id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'School Bill Term Session not found.'
            ], 404);
        }
    
        $relatedRecords = SchoolBillTermSession::where('bill_id', $schoolbillclasstermsessions->bill_id)
            ->where('session_id', $schoolbillclasstermsessions->session_id)
            ->select('class_id', 'termid_id')
            ->get();
    
        \Log::info('Related records fetched', [
            'bill_id' => $schoolbillclasstermsessions->bill_id,
            'session_id' => $schoolbillclasstermsessions->session_id,
            'count' => $relatedRecords->count()
        ]);
    
        $classIds = $relatedRecords->pluck('class_id')->unique()->toArray();
        $termIds = $relatedRecords->pluck('termid_id')->unique()->toArray();
    
        \Log::info('Class and Term IDs', [
            'classIds' => $classIds,
            'termIds' => $termIds
        ]);
    
        return response()->json([
            'success' => true,
            'bill_id' => $schoolbillclasstermsessions->bill_id,
            'class_ids' => $classIds ?: [],
            'term_ids' => $termIds ?: [],
            'session_id' => $schoolbillclasstermsessions->session_id
        ], 200);
    }

    public function deleteschoolbilltermsession(Request $request)
    {
        return $this->destroy($request->schoolbilltermsessionid);
    }
}