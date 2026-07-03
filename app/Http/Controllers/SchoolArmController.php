<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schoolarm;
use Illuminate\Support\Facades\Log;

class SchoolArmController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View school-arm|Create school-arm|Update school-arm|Delete school-arm', ['only' => ['index']]);
        $this->middleware('permission:Create school-arm', ['only' => ['store']]);
        $this->middleware('permission:Update school-arm', ['only' => ['update', 'updatearm']]);
        $this->middleware('permission:Delete school-arm', ['only' => ['destroy', 'deletearm']]);
    }

    public function index(Request $request)
    {
        Log::info('Index School Arm Request:', $request->all());
        $pagetitle = "School Arm Management";
        $query = Schoolarm::query();

        if ($request->has('search')) {
            $query->where('arm', 'like', '%' . $request->query('search') . '%')
                  ->orWhere('description', 'like', '%' . $request->query('search') . '%');
        }

        $data = Schoolarm::latest()->paginate(5);
        $all_arms = $query->orderBy('arm')->paginate(30);

        if ($request->ajax()) {
            return response()->json(['arms' => $all_arms->items()]);
        }

        return view('arm.index')->with('all_arms', $all_arms)->with('data', $data)->with('pagetitle', $pagetitle);
    }

    public function store(Request $request)
    {
        Log::info('Store School Arm Request:', $request->all());
        $request->validate([
            'arm' => 'required|string|max:255|unique:schoolarm,arm',
            'description' => 'required|string'
        ]);

        $checkArm = Schoolarm::where('arm', $request->input('arm'))->exists();
        if ($checkArm) {
            Log::warning('School arm already taken:', ['arm' => $request->input('arm')]);
            return response()->json(['success' => false, 'message' => 'School arm is already taken'], 422);
        }

        $arm = Schoolarm::create([
            'arm' => $request->input('arm'),
            'description' => $request->input('description')
        ]);
        Log::info('School Arm Created:', $arm->toArray());

        return response()->json(['success' => true, 'message' => 'School arm has been created successfully']);
    }

    public function update(Request $request, $id)
    {
        Log::info('Update School Arm Request:', ['id' => $id, 'data' => $request->all()]);
        $request->validate([
            'arm' => "required|string|max:255|unique:schoolarm,arm,$id",
            'description' => 'required|string'
        ]);

        $checkArm = Schoolarm::where('arm', $request->input('arm'))->where('id', '!=', $id)->exists();
        if ($checkArm) {
            Log::warning('School arm already taken:', ['arm' => $request->input('arm')]);
            return response()->json(['success' => false, 'message' => 'School arm is already taken'], 422);
        }

        $arm = Schoolarm::findOrFail($id);
        $arm->update([
            'arm' => $request->input('arm'),
            'description' => $request->input('description')
        ]);
        Log::info('School Arm Updated:', $arm->toArray());

        return response()->json(['success' => true, 'message' => 'School arm has been updated successfully']);
    }

    public function destroy($id)
    {
        Log::info('Delete School Arm Request:', ['id' => $id]);
        $arm = Schoolarm::findOrFail($id);
        $arm->delete();
        Log::info('School Arm Deleted:', ['id' => $id]);

        return response()->json(['success' => true, 'message' => 'School arm has been deleted successfully']);
    }

    public function deletearm(Request $request)
    {
        Log::info('Delete School Arm AJAX Request:', $request->all());
        $request->validate(['armid' => 'required|exists:schoolarm,id']);
        $arm = Schoolarm::findOrFail($request->armid);
        $arm->delete();
        Log::info('School Arm Deleted via AJAX:', ['id' => $request->armid]);

        return response()->json(['success' => true, 'message' => 'School arm has been deleted successfully']);
    }

    public function updatearm(Request $request)
    {
        Log::info('Update School Arm AJAX Request:', $request->all());
        $request->validate([
            'id' => 'required|exists:schoolarm,id',
            'arm' => "required|string|max:255|unique:schoolarm,arm,{$request->id}",
            'description' => 'required|string'
        ]);

        $checkArm = Schoolarm::where('arm', $request->input('arm'))->where('id', '!=', $request->id)->exists();
        if ($checkArm) {
            Log::warning('School arm already taken:', ['arm' => $request->input('arm')]);
            return response()->json(['success' => false, 'message' => 'School arm is already taken'], 422);
        }

        $arm = Schoolarm::findOrFail($request->id);
        $arm->update([
            'arm' => $request->input('arm'),
            'description' => $request->input('description')
        ]);
        Log::info('School Arm Updated via AJAX:', $arm->toArray());

        return response()->json(['success' => true, 'message' => 'School arm has been updated successfully']);
    }
}