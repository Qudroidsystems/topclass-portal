<?php

namespace App\Http\Controllers;

use App\Models\Classcategory;
use App\Models\Schoolarm;
use App\Models\Schoolclass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SchoolClassController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View school-class|Create school-class|Update school-class|Delete school-class', ['only' => ['index', 'data', 'stats', 'show']]);
        $this->middleware('permission:Create school-class', ['only' => ['store']]);
        $this->middleware('permission:Update school-class', ['only' => ['update']]);
        $this->middleware('permission:Delete school-class', ['only' => ['destroy', 'deleteMultiple']]);
    }

    // =========================================================================
    // INDEX — passes $arms and $classcategories as Collections
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "School Class Management";

        try {
            $arms            = Schoolarm::orderBy('arm')->get();
            $classcategories = Classcategory::orderBy('category')->get();

            return view('schoolclass.index', [
                'pagetitle'       => $pagetitle,
                'arms'            => $arms,
                'classcategories' => $classcategories,
            ]);

        } catch (\Exception $e) {
            Log::error('SchoolClass index error', ['error' => $e->getMessage()]);
            return back()->with('danger', 'Error loading school classes: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATATABLE
    // =========================================================================

    public function data(Request $request)
    {
        try {
            $classes = Schoolclass::query()
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('classcategories', 'classcategories.id', '=', 'schoolclass.classcategoryid')
                ->select([
                    'schoolclass.id',
                    'schoolclass.schoolclass',
                    'schoolclass.arm as arm_id',
                    'schoolarm.arm as arm_name',
                    'schoolclass.classcategoryid',
                    'classcategories.category as classcategory_name',
                    'schoolclass.description',
                    'schoolclass.created_at',
                    'schoolclass.updated_at',
                ]);

            return DataTables::of($classes)
                ->addIndexColumn()

                ->addColumn('class_info', function ($row) {
                    return '<div>'
                        . '<span class="fw-semibold text-dark">' . e($this->cleanUtf8String($row->schoolclass ?? '')) . '</span>'
                        . '<small class="text-muted d-block">ID: ' . $row->id . '</small>'
                        . '</div>';
                })

                ->addColumn('arm_info', function ($row) {
                    $armName = $this->cleanUtf8String($row->arm_name ?? 'N/A');
                    return '<span class="sc-badge sc-badge-arm">' . e($armName) . '</span>'
                        . '<small class="text-muted d-block">Arm ID: ' . ($row->arm_id ?? 'N/A') . '</small>';
                })

                ->addColumn('categories_info', function ($row) {
                    if (empty($row->classcategory_name)) {
                        return '<span class="text-muted">No category</span>';
                    }
                    return '<span class="sc-badge sc-badge-category">'
                        . e($this->cleanUtf8String($row->classcategory_name))
                        . '</span>'
                        . '<small class="text-muted d-block">Category ID: ' . ($row->classcategoryid ?? 'N/A') . '</small>';
                })

                ->addColumn('formatted_date', function ($row) {
                    if (!$row->updated_at) return '<span class="text-muted small">—</span>';
                    return '<small class="text-muted">'
                        . \Carbon\Carbon::parse($row->updated_at)->format('d M Y')
                        . '</small>';
                })

                ->addColumn('action', function ($row) {
                    $buttons = '<div class="d-flex gap-1">';

                    if (auth()->user() && auth()->user()->can('Update school-class')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-secondary edit-class-btn" title="Edit" '
                            . 'data-id="%s" data-schoolclass="%s" data-arm-id="%s" data-category-id="%s">'
                            . '<i class="ph-pencil"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->schoolclass ?? '')),
                            $row->arm_id,
                            $row->classcategoryid
                        );
                    }

                    if (auth()->user() && auth()->user()->can('Delete school-class')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-danger delete-class-btn" title="Delete" '
                            . 'data-id="%s" data-name="%s"><i class="ph-trash"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->schoolclass ?? 'Unknown'))
                        );
                    }

                    return $buttons . '</div>';
                })

                ->rawColumns(['class_info', 'arm_info', 'categories_info', 'formatted_date', 'action'])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('SchoolClass DataTable error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // STATS
    // =========================================================================

    public function stats()
    {
        try {
            return response()->json([
                'stats' => [
                    'total'            => Schoolclass::count(),
                    'total_arms'       => Schoolarm::count(),
                    'total_categories' => Classcategory::count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'stats' => ['total' => 0, 'total_arms' => 0, 'total_categories' => 0],
            ]);
        }
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    public function show($id)
    {
        try {
            $class = Schoolclass::findOrFail($id);
            return response()->json([
                'success' => true,
                'data'    => [
                    'id'              => $class->id,
                    'schoolclass'     => $class->schoolclass,
                    'arm_id'          => $class->arm,
                    'classcategoryid' => $class->classcategoryid,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Class not found'], 404);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schoolclass'     => 'required|string|max:255',
            'arm_id'          => 'required|array|min:1',
            'arm_id.*'        => 'exists:schoolarm,id',
            'classcategoryid' => 'required',
        ], [
            'schoolclass.required'     => 'Please enter a school class name.',
            'arm_id.required'          => 'Please select at least one arm.',
            'arm_id.*.exists'          => 'One or more selected arms do not exist.',
            'classcategoryid.required' => 'Please select a category.',
        ]);

        $categoryIds = $request->input('classcategoryid');
        if (!is_array($categoryIds)) {
            $categoryIds = [$categoryIds];
        }
        $categoryIds = array_values(array_filter($categoryIds));

        $existingCats = Classcategory::whereIn('id', $categoryIds)->pluck('id')->toArray();
        if (count($existingCats) !== count($categoryIds)) {
            return response()->json([
                'success' => false,
                'message' => 'One or more selected categories do not exist.',
            ], 422);
        }

        $validator->after(function ($validator) use ($request, $categoryIds) {
            foreach ($request->arm_id as $armId) {
                foreach ($categoryIds as $catId) {
                    $exists = Schoolclass::where('schoolclass', $request->schoolclass)
                        ->where('arm', $armId)
                        ->where('classcategoryid', $catId)
                        ->exists();
                    if ($exists) {
                        $arm = Schoolarm::find($armId);
                        $cat = Classcategory::find($catId);
                        $validator->errors()->add(
                            'schoolclass',
                            "The class '{$request->schoolclass}', arm '"
                            . ($arm->arm ?? '?') . "', category '"
                            . ($cat->category ?? '?') . "' already exists."
                        );
                    }
                }
            }
        });

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $created    = [];
            $pivotReady = Schema::hasTable('schoolclass_classcategory');

            foreach ($request->arm_id as $armId) {
                foreach ($categoryIds as $catId) {
                    $class = new Schoolclass();
                    $class->schoolclass     = $request->schoolclass;
                    $class->arm             = $armId;
                    $class->classcategoryid = $catId;
                    $class->description     = $request->description ?? null;
                    $class->save();

                    if ($pivotReady) {
                        DB::table('schoolclass_classcategory')->updateOrInsert(
                            [
                                'schoolclass_id'   => $class->id,
                                'classcategory_id' => $catId,
                            ],
                            [
                                'promotion_pass_average' => null,
                                'created_at'             => now(),
                                'updated_at'             => now(),
                            ]
                        );
                    }

                    $arm = Schoolarm::find($armId);
                    $cat = Classcategory::find($catId);

                    $created[] = [
                        'id'                 => $class->id,
                        'schoolclass'        => $class->schoolclass,
                        'arm_id'             => $class->arm,
                        'arm_name'           => $arm->arm ?? 'Unknown',
                        'classcategoryid'    => $class->classcategoryid,
                        'classcategory_name' => $cat->category ?? 'Unknown',
                    ];
                }
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($created) . ' school class(es) added successfully!',
                'data'    => $created,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SchoolClass store error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error storing school class: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'schoolclass'     => 'required|string|max:255',
            'arm_id'          => 'required|exists:schoolarm,id',
            'classcategoryid' => 'required',
        ]);

        $categoryIds = $request->input('classcategoryid');
        if (!is_array($categoryIds)) {
            $categoryIds = [$categoryIds];
        }
        $categoryIds  = array_values(array_filter($categoryIds));
        $primaryCatId = $categoryIds[0] ?? null;

        if ($validator->fails() || !$primaryCatId) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first() ?: 'Please select a category.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $exists = Schoolclass::where('schoolclass', $request->schoolclass)
            ->where('arm', $request->arm_id)
            ->where('classcategoryid', $primaryCatId)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            $arm = Schoolarm::find($request->arm_id);
            $cat = Classcategory::find($primaryCatId);
            return response()->json([
                'success' => false,
                'message' => "The class '{$request->schoolclass}', arm '"
                    . ($arm->arm ?? '?') . "', category '"
                    . ($cat->category ?? '?') . "' already exists.",
            ], 422);
        }

        DB::beginTransaction();
        try {
            $class = Schoolclass::findOrFail($id);
            $class->schoolclass     = $request->schoolclass;
            $class->arm             = $request->arm_id;
            $class->classcategoryid = $primaryCatId;
            $class->description     = $request->description ?? null;
            $class->save();

            if (Schema::hasTable('schoolclass_classcategory')) {
                DB::table('schoolclass_classcategory')->where('schoolclass_id', $class->id)->delete();
                DB::table('schoolclass_classcategory')->insert([
                    'schoolclass_id'         => $class->id,
                    'classcategory_id'       => $primaryCatId,
                    'promotion_pass_average' => null,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'School class updated successfully!',
                'data'    => [
                    'id'              => $class->id,
                    'schoolclass'     => $class->schoolclass,
                    'arm_id'          => $class->arm,
                    'classcategoryid' => $class->classcategoryid,
                ],
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SchoolClass update error', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating school class: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $class = Schoolclass::find($id);
            if (!$class) {
                return response()->json(['success' => false, 'message' => 'Class not found.'], 404);
            }

            if (Schema::hasTable('schoolclass_classcategory')) {
                DB::table('schoolclass_classcategory')->where('schoolclass_id', $id)->delete();
            }
            if (Schema::hasTable('classteacher')) {
                DB::table('classteacher')->where('schoolclassid', $id)->delete();
            }

            $class->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'School class deleted successfully!'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SchoolClass destroy error', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Error deleting school class: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // BULK DESTROY
    // =========================================================================

    public function deleteMultiple(Request $request)
    {
        try {
            $ids = $request->input('ids');
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                $ids = is_array($decoded) ? $decoded : array_map('trim', explode(',', $ids));
            }
            if (!is_array($ids)) $ids = [];
            $ids = array_values(array_filter($ids));

            if (empty($ids)) {
                return response()->json(['success' => false, 'message' => 'No classes selected.'], 400);
            }

            $existingIds = Schoolclass::whereIn('id', $ids)->pluck('id')->toArray();
            $invalidIds  = array_diff($ids, $existingIds);
            if (!empty($invalidIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected classes do not exist.',
                ], 400);
            }

            DB::beginTransaction();

            if (Schema::hasTable('schoolclass_classcategory')) {
                DB::table('schoolclass_classcategory')->whereIn('schoolclass_id', $ids)->delete();
            }
            if (Schema::hasTable('classteacher')) {
                DB::table('classteacher')->whereIn('schoolclassid', $ids)->delete();
            }

            $deleted = Schoolclass::whereIn('id', $ids)->delete();
            DB::commit();

            return response()->json([
                'success'       => true,
                'message'       => $deleted . ' class(es) deleted successfully.',
                'deleted_count' => $deleted,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SchoolClass bulk delete error', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error deleting classes: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // LEGACY ALIASES
    // =========================================================================

    public function deleteschoolclass(Request $request)
    {
        $request->merge(['ids' => [$request->input('schoolclassid')]]);
        return $this->deleteMultiple($request);
    }

    public function getArms($id)
    {
        try {
            $class = Schoolclass::findOrFail($id);
            return response()->json(['success' => true, 'armIds' => [$class->arm]], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch arms'], 500);
        }
    }

    // =========================================================================
    // HELPER
    // =========================================================================

    private function cleanUtf8String($string)
    {
        if (empty($string)) return '';
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
    }
}
