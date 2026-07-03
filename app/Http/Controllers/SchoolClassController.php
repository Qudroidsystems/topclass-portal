<?php

namespace App\Http\Controllers;

use App\Models\Classcategory;
use App\Models\Schoolclass;
use App\Models\Schoolarm;
use App\Models\ClassTeacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SchoolClassController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View school-class|Create school-class|Update school-class|Delete school-class', ['only' => ['index']]);
        $this->middleware('permission:Create school-class', ['only' => ['store']]);
        $this->middleware('permission:Update school-class', ['only' => ['update']]);
        $this->middleware('permission:Delete school-class', ['only' => ['destroy', 'deleteschoolclass']]);
    }

    public function index(Request $request)
    {
        Log::info('Index School Class Request:', $request->all());
        $pagetitle = "School Class Management";

        $query = Schoolclass::query()
            ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select(
                'schoolclass.id',
                'schoolclass.schoolclass',
                'schoolarm.arm as arm_name',
                'schoolclass.arm as arm_id',
                'classcategories.category as classcategory',
                'classcategories.id as classcategoryid',
                'schoolclass.updated_at'
            );

        if ($request->has('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('schoolclass.schoolclass', 'like', '%' . $search . '%')
                  ->orWhere('schoolarm.arm', 'like', '%' . $search . '%')
                  ->orWhere('classcategories.category', 'like', '%' . $search . '%');
            });
        }

        $all_classes = $query->orderBy('schoolclass.schoolclass')->paginate(100);
        $arms = Schoolarm::all();
        $classcategories = Classcategory::all();

        if ($request->ajax()) {
            return response()->json([
                'html' => view('schoolclass.index', compact('all_classes', 'arms', 'classcategories', 'pagetitle'))->render(),
                'count' => $all_classes->count(),
                'total' => $all_classes->total(),
            ]);
        }

        return view('schoolclass.index')
            ->with('all_classes', $all_classes)
            ->with('arms', $arms)
            ->with('classcategories', $classcategories)
            ->with('pagetitle', $pagetitle);
    }

    public function store(Request $request)
    {
        Log::info('Store School Class Request:', $request->all());

        $validator = Validator::make($request->all(), [
            'schoolclass' => 'required|string|max:255',
            'arm_id' => 'required|array|min:1',
            'arm_id.*' => 'exists:schoolarm,id',
            'classcategoryid' => 'required|exists:classcategories,id',
        ], [
            'schoolclass.required' => 'Please enter a school class name.',
            'arm_id.required' => 'Please select at least one arm.',
            'arm_id.*.exists' => 'One or more selected arms do not exist.',
            'classcategoryid.required' => 'Please select a category.',
            'classcategoryid.exists' => 'Selected category does not exist.',
        ]);

        $validator->after(function ($validator) use ($request) {
            $armIds = $request->arm_id ?? [];
            $category = Classcategory::find($request->classcategoryid);
            $categoryName = $category ? $category->category : 'Unknown';
            foreach ($armIds as $armId) {
                $exists = Schoolclass::where('schoolclass', $request->schoolclass)
                    ->where('arm', $armId)
                    ->where('classcategoryid', $request->classcategoryid)
                    ->exists();
                if ($exists) {
                    $arm = Schoolarm::find($armId);
                    $armName = $arm ? $arm->arm : 'Unknown';
                    $validator->errors()->add(
                        'schoolclass',
                        "The combination of class '{$request->schoolclass}', arm '{$armName}', and category '{$categoryName}' already exists."
                    );
                }
            }
        });

        if ($validator->fails()) {
            Log::error('Validation failed for store school class:', ['errors' => $validator->errors()->all(), 'input' => $request->all()]);
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $createdRecords = [];
            foreach ($request->arm_id as $armId) {
                $schoolclass = new Schoolclass();
                $schoolclass->schoolclass = $request->schoolclass;
                $schoolclass->arm = $armId;
                $schoolclass->classcategoryid = $request->classcategoryid;
                $schoolclass->description = $request->description ?? 'Null';
                $schoolclass->save();

                $arm = Schoolarm::find($armId);
                $category = Classcategory::find($schoolclass->classcategoryid);

                $createdRecords[] = [
                    'id' => $schoolclass->id,
                    'schoolclass' => $schoolclass->schoolclass,
                    'arm_id' => $schoolclass->arm,
                    'arm_name' => $arm ? $arm->arm : 'Unknown',
                    'classcategoryid' => $schoolclass->classcategoryid,
                    'classcategory' => $category ? $category->category : 'Unknown',
                    'description' => $schoolclass->description,
                    'updated_at' => $schoolclass->updated_at->toISOString(),
                    'created_at' => $schoolclass->created_at->toISOString()
                ];
            }

            Log::info('School classes stored successfully:', $createdRecords);

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'School class(es) added successfully!',
                    'schoolclasses' => $createdRecords
                ], 200);
            }

            return redirect()->back()->with('success', 'School class(es) registered successfully!');
        } catch (\Exception $e) {
            Log::error('Error storing school class:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Error storing school class',
                    'error' => $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error storing school class');
        }
    }

    public function update(Request $request, $id)
    {
        Log::info('Update School Class Request:', ['id' => $id, 'data' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'schoolclass' => 'required|string|max:255',
            'arm_id' => 'required|array|size:1',
            'arm_id.*' => 'exists:schoolarm,id',
            'classcategoryid' => 'required|exists:classcategories,id',
        ], [
            'schoolclass.required' => 'Please enter a school class name.',
            'arm_id.required' => 'Please select one arm.',
            'arm_id.size' => 'Exactly one arm must be selected.',
            'arm_id.*.exists' => 'The selected arm does not exist.',
            'classcategoryid.required' => 'Please select a category.',
            'classcategoryid.exists' => 'Selected category does not exist.',
        ]);

        $validator->after(function ($validator) use ($request, $id) {
            $armId = $request->arm_id[0] ?? null;
            if ($armId) {
                $exists = Schoolclass::where('schoolclass', $request->schoolclass)
                    ->where('arm', $armId)
                    ->where('classcategoryid', $request->classcategoryid)
                    ->where('id', '!=', $id)
                    ->exists();
                if ($exists) {
                    $arm = Schoolarm::find($armId);
                    $armName = $arm ? $arm->arm : 'Unknown';
                    $category = Classcategory::find($request->classcategoryid);
                    $categoryName = $category ? $category->category : 'Unknown';
                    $validator->errors()->add(
                        'schoolclass',
                        "The combination of class '{$request->schoolclass}', arm '{$armName}', and category '{$categoryName}' already exists."
                    );
                }
            }
        });

        if ($validator->fails()) {
            Log::error('Validation failed for update school class:', ['errors' => $validator->errors()->all(), 'input' => $request->all()]);
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $schoolclass = Schoolclass::findOrFail($id);
            $schoolclass->schoolclass = $request->schoolclass;
            $schoolclass->arm = $request->arm_id[0];
            $schoolclass->classcategoryid = $request->classcategoryid;
            $schoolclass->description = $request->description ?? 'Null';
            $schoolclass->save();

            $arm = Schoolarm::find($schoolclass->arm);
            $category = Classcategory::find($schoolclass->classcategoryid);

            $updatedRecord = [
                'id' => $schoolclass->id,
                'schoolclass' => $schoolclass->schoolclass,
                'arm_id' => $schoolclass->arm,
                'arm_name' => $arm ? $arm->arm : 'Unknown',
                'classcategoryid' => $schoolclass->classcategoryid,
                'classcategory' => $category ? $category->category : 'Unknown',
                'description' => $schoolclass->description,
                'updated_at' => $schoolclass->updated_at->toISOString(),
                'created_at' => $schoolclass->created_at->toISOString()
            ];

            Log::info('School class updated successfully:', $updatedRecord);

            if ($request->ajax()) {
                return response()->json([
                    'message' => 'School class updated successfully!',
                    'schoolclass' => $updatedRecord
                ], 200);
            }

            return redirect()->route('schoolclass.index')->with('success', 'School class updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating school class:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);
            if ($request->ajax()) {
                return response()->json([
                    'message' => 'Error updating school class',
                    'error' => $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Error updating school class');
        }
    }

    public function destroy($id)
    {
        Log::info('Delete School Class Request:', ['id' => $id]);
        \DB::enableQueryLog();

        try {
            $schoolclass = Schoolclass::findOrFail($id);
            Classteacher::where('schoolclassid', $id)->delete();
            $schoolclass->delete();

            Log::info('Query Log:', \DB::getQueryLog());
            return response()->json(['message' => 'School class deleted successfully!']);
        } catch (\Exception $e) {
            Log::error('Delete Error:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Error deleting school class', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteschoolclass(Request $request)
    {
        Log::info('Delete School Class AJAX Request:', ['schoolclassid' => $request->schoolclassid]);

        $schoolclass = Schoolclass::find($request->schoolclassid);
        if ($schoolclass) {
            \DB::table('classteacher')->where('schoolclassid', $request->schoolclassid)->delete();
            \DB::table('schoolclass')->where('id', $request->schoolclassid)->delete();
            return response()->json([
                'success' => true,
                'message' => 'School class has been removed'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'School class not found'
        ], 404);
    }

    public function getArms($id)
    {
        Log::info('Fetching arms for schoolclass', ['id' => $id]);
        try {
            $schoolClass = Schoolclass::findOrFail($id);
            $armIds = [$schoolClass->arm];
            return response()->json(['success' => true, 'armIds' => $armIds], 200);
        } catch (\Exception $e) {
            Log::error('Get arms error', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to fetch arms'], 500);
        }
    }
}