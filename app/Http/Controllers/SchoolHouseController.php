<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schoolhouse;
use App\Models\Schoolterm;
use App\Models\Schoolsession;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class SchoolHouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View schoolhouse|Create schoolhouse|Update schoolhouse|Delete schoolhouse', ['only' => ['index']]);
        $this->middleware('permission:Create schoolhouse', ['only' => ['store']]);
        $this->middleware('permission:Update schoolhouse', ['only' => ['update', 'updatehouse']]);
        $this->middleware('permission:Delete schoolhouse', ['only' => ['destroy', 'deletehouse']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $pagetitle = "School House Management";
        $query = Schoolhouse::query()
            ->leftJoin('users', 'users.id', '=', 'schoolhouses.housemasterid')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'schoolhouses.termid')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'schoolhouses.sessionid')
            ->select([
                'schoolhouses.id as id',
                'users.id as userid',
                'users.name as housemaster',
                'schoolhouses.house',
                'schoolhouses.housecolour',
                'schoolterm.term as term',
                'schoolsession.session as session',
                'schoolhouses.updated_at as updated_at'
            ]);

        if ($request->has('search')) {
            $query->where('schoolhouses.house', 'like', '%' . $request->query('search') . '%')
                  ->orWhere('schoolhouses.housecolour', 'like', '%' . $request->query('search') . '%');
        }

        $schoolhouses = $query->paginate(10);
        $schoolterm = Schoolterm::all();
        $schoolsession = Schoolsession::all();
        $staff = User::whereHas('roles', function ($q) {
            $q->where('name', '!=', 'Student');
        })->get(['users.id as userid', 'users.name as name']);

        if ($request->ajax()) {
            return response()->json(['schoolhouses' => $schoolhouses->items()]);
        }

        return view('schoolhouse.index')
            ->with('schoolhouses', $schoolhouses)
            ->with('schoolterm', $schoolterm)
            ->with('schoolsession', $schoolsession)
            ->with('staff', $staff)
            ->with('pagetitle', $pagetitle);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'house' => 'required|string|max:255',
            'housecolour' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^#[0-9A-Fa-f]{6}$|^[a-zA-Z]+$|^rgb\(\d{1,3},\s*\d{1,3},\s*\d{1,3}\)$/', $value)) {
                        $fail('The house colour must be a valid CSS color (name, hex, or RGB).');
                    }
                }
            ],
            'housemasterid' => 'required|exists:users,id',
            'termid' => 'required|exists:schoolterm,id',
            'sessionid' => 'required|exists:schoolsession,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $schoolhouse = Schoolhouse::where('house', $request->house)
            ->where('housemasterid', $request->housemasterid)
            ->where('housecolour', $request->housecolour)
            ->where('termid', $request->termid)
            ->where('sessionid', $request->sessionid)
            ->exists();

        if ($schoolhouse) {
            return response()->json(['success' => false, 'message' => 'Record already exists'], 422);
        }

        Schoolhouse::create($request->only(['house', 'housecolour', 'housemasterid', 'termid', 'sessionid']));
        return response()->json(['success' => true, 'message' => 'School house created successfully']);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'house' => 'required|string|max:255',
            'housecolour' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^#[0-9A-Fa-f]{6}$|^[a-zA-Z]+$|^rgb\(\d{1,3},\s*\d{1,3},\s*\d{1,3}\)$/', $value)) {
                        $fail('The house colour must be a valid CSS color (name, hex, or RGB).');
                    }
                }
            ],
            'housemasterid' => 'required|exists:users,id',
            'termid' => 'required|exists:schoolterm,id',
            'sessionid' => 'required|exists:schoolsession,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $schoolhouse = Schoolhouse::where('house', $request->house)
            ->where('housemasterid', $request->housemasterid)
            ->where('housecolour', $request->housecolour)
            ->where('termid', $request->termid)
            ->where('sessionid', $request->sessionid)
            ->where('id', '!=', $id)
            ->exists();

        if ($schoolhouse) {
            return response()->json(['success' => false, 'message' => 'Record already exists'], 422);
        }

        $schoolhouse = Schoolhouse::findOrFail($id);
        $schoolhouse->update($request->only(['house', 'housecolour', 'housemasterid', 'termid', 'sessionid']));
        return response()->json(['success' => true, 'message' => 'School house updated successfully']);
    }

    /**
     * Update school house via AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function updatehouse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:schoolhouse,id',
            'house' => 'required|string|max:255',
            'housecolour' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^#[0-9A-Fa-f]{6}$|^[a-zA-Z]+$|^rgb\(\d{1,3},\s*\d{1,3},\s*\d{1,3}\)$/', $value)) {
                        $fail('The house colour must be a valid CSS color (name, hex, or RGB).');
                    }
                }
            ],
            'housemasterid' => 'required|exists:users,id',
            'termid' => 'required|exists:schoolterm,id',
            'sessionid' => 'required|exists:schoolsession,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $schoolhouse = Schoolhouse::where('house', $request->house)
            ->where('housemasterid', $request->housemasterid)
            ->where('housecolour', $request->housecolour)
            ->where('termid', $request->termid)
            ->where('sessionid', $request->sessionid)
            ->where('id', '!=', $request->id)
            ->exists();

        if ($schoolhouse) {
            return response()->json(['success' => false, 'message' => 'Record already exists'], 422);
        }

        $schoolhouse = Schoolhouse::findOrFail($request->id);
        $schoolhouse->update($request->only(['house', 'housecolour', 'housemasterid', 'termid', 'sessionid']));
        return response()->json(['success' => true, 'message' => 'School house updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $schoolhouse = Schoolhouse::findOrFail($id);
        $schoolhouse->delete();
        return response()->json(['success' => true, 'message' => 'School house deleted successfully']);
    }

    /**
     * Delete school house via AJAX.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function deletehouse(Request $request)
    {
        $request->validate(['houseid' => 'required|exists:schoolhouses,id']);
        $schoolhouse = Schoolhouse::findOrFail($request->houseid);
        $schoolhouse->delete();
        return response()->json(['success' => true, 'message' => 'School house deleted successfully']);
    }
}