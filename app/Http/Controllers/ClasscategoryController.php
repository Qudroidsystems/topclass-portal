<?php

namespace App\Http\Controllers;

use App\Models\Classcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class ClasscategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View class-category|Create class-category|Update class-category|Delete class-category', ['only' => ['index', 'data', 'stats']]);
        $this->middleware('permission:Create class-category', ['only' => ['store']]);
        $this->middleware('permission:Update class-category', ['only' => ['update', 'updateclasscategory']]);
        $this->middleware('permission:Delete class-category', ['only' => ['destroy', 'deleteclasscategory', 'bulkDestroy']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "Class Category Management";

        try {
            return view('classcategories.index')->with('pagetitle', $pagetitle);
        } catch (\Exception $e) {
            Log::error('Class Category Index Error:', ['error' => $e->getMessage()]);
            return back()->with('danger', 'Error loading class categories: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATATABLE — AJAX (server-side)
    // =========================================================================

    public function data(Request $request)
    {
        try {
            $categories = Classcategory::select('classcategories.*');

            return DataTables::of($categories)
                ->addIndexColumn()

                // ── Category Name ─────────────────────────────────────────
                ->addColumn('category_info', function ($row) {
                    return '<div>'
                        . '<span class="fw-semibold text-dark">' . e($this->cleanUtf8String($row->category ?? '')) . '</span>'
                        . '<small class="text-muted d-block">ID: ' . $row->id . '</small>'
                        . '</div>';
                })

                // ── CA1 / CA2 / CA3 / Exam summary ────────────────────────
                ->addColumn('scores_info', function ($row) {
                    return '<div class="small lh-sm">'
                        . '<span class="me-2">CA1: <strong>' . number_format((float) $row->ca1score, 1) . '</strong></span>'
                        . '<span class="me-2">CA2: <strong>' . number_format((float) $row->ca2score, 1) . '</strong></span>'
                        . '<span class="me-2">CA3: <strong>' . number_format((float) $row->ca3score, 1) . '</strong></span>'
                        . '<span>Exam: <strong>' . number_format((float) $row->examscore, 1) . '</strong></span>'
                        . '</div>';
                })

                // ── Grade Type ────────────────────────────────────────────
                ->addColumn('grade_type', function ($row) {
                    return $row->is_senior
                        ? '<span class="cc-badge cc-badge-senior">Senior (A1-F9)</span>'
                        : '<span class="cc-badge cc-badge-junior">Junior (A-F)</span>';
                })

                // ── Total Max ─────────────────────────────────────────────
                ->addColumn('total_max', function ($row) {
                    $total = (float) $row->ca1score
                           + (float) $row->ca2score
                           + (float) $row->ca3score
                           + (float) $row->examscore;
                    return '<span class="badge bg-primary">' . number_format($total, 1) . '</span>';
                })

                // ── Last Updated ──────────────────────────────────────────
                ->addColumn('formatted_date', function ($row) {
                    if (!$row->updated_at) {
                        return '<span class="text-muted small">—</span>';
                    }
                    return '<small class="text-muted">'
                        . \Carbon\Carbon::parse($row->updated_at)->format('d M Y')
                        . '</small>';
                })

                // ── Actions ───────────────────────────────────────────────
                ->addColumn('action', function ($row) {
                    $buttons = '<div class="d-flex gap-1">';

                    if (auth()->user() && auth()->user()->can('Update class-category')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-secondary edit-category-btn" title="Edit" '
                            . 'data-id="%s" data-category="%s" data-is_senior="%s" '
                            . 'data-ca1score="%s" data-ca2score="%s" data-ca3score="%s" data-examscore="%s">'
                            . '<i class="ph-pencil"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->category ?? '')),
                            $row->is_senior ? 1 : 0,
                            (float) $row->ca1score,
                            (float) $row->ca2score,
                            (float) $row->ca3score,
                            (float) $row->examscore
                        );
                    }

                    if (auth()->user() && auth()->user()->can('Delete class-category')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-danger delete-category-btn" title="Delete" '
                            . 'data-id="%s" data-name="%s"><i class="ph-trash"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->category ?? 'Unknown Category'))
                        );
                    }

                    return $buttons . '</div>';
                })

                ->rawColumns(['category_info', 'scores_info', 'grade_type', 'total_max', 'formatted_date', 'action'])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('Class Category DataTable error:', [
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
                    'total' => Classcategory::count(),
                    'senior' => Classcategory::where('is_senior', true)->count(),
                    'junior' => Classcategory::where('is_senior', false)->count(),
                    // In Project 1's model, "with CA config" = ca1score > 0
                    'with_assessment' => Classcategory::where('ca1score', '>', 0)->count(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Class Category stats error: ' . $e->getMessage());
            return response()->json([
                'stats' => ['total' => 0, 'senior' => 0, 'junior' => 0, 'with_assessment' => 0],
            ]);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        Log::info('Store Class Category Request:', $request->all());

        $validator = Validator::make($request->all(), [
            'category'  => 'required|string|max:255|unique:classcategories,category',
            'ca1score'  => 'required|numeric|min:0',
            'ca2score'  => 'required|numeric|min:0',
            'ca3score'  => 'required|numeric|min:0',
            'examscore' => 'required|numeric|min:0',
            'is_senior' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $category = Classcategory::create([
                'category'  => $request->input('category'),
                'ca1score'  => $request->input('ca1score'),
                'ca2score'  => $request->input('ca2score'),
                'ca3score'  => $request->input('ca3score'),
                'examscore' => $request->input('examscore'),
                'is_senior' => (bool) $request->input('is_senior'),
            ]);

            DB::commit();
            Log::info('Class Category Created:', $category->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Class category created successfully.',
                'data'    => $category,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating class category:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create class category: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE (uses ID from route for REST-style calls)
    // =========================================================================

    public function update(Request $request, $id)
    {
        Log::info('Update Class Category Request:', ['id' => $id, 'data' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'category'  => "required|string|max:255|unique:classcategories,category,{$id}",
            'ca1score'  => 'required|numeric|min:0',
            'ca2score'  => 'required|numeric|min:0',
            'ca3score'  => 'required|numeric|min:0',
            'examscore' => 'required|numeric|min:0',
            'is_senior' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $category = Classcategory::findOrFail($id);
            $category->update([
                'category'  => $request->input('category'),
                'ca1score'  => $request->input('ca1score'),
                'ca2score'  => $request->input('ca2score'),
                'ca3score'  => $request->input('ca3score'),
                'examscore' => $request->input('examscore'),
                'is_senior' => (bool) $request->input('is_senior'),
            ]);

            DB::commit();
            Log::info('Class Category Updated:', $category->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Class category updated successfully.',
                'data'    => $category,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating class category:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update class category: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE — AJAX  (id in the payload, not the route)
    // =========================================================================

    public function updateclasscategory(Request $request)
    {
        Log::info('Update Class Category AJAX Request:', $request->all());

        $validator = Validator::make($request->all(), [
            'id'        => 'required|exists:classcategories,id',
            'category'  => "required|string|max:255|unique:classcategories,category,{$request->id}",
            'ca1score'  => 'required|numeric|min:0',
            'ca2score'  => 'required|numeric|min:0',
            'ca3score'  => 'required|numeric|min:0',
            'examscore' => 'required|numeric|min:0',
            'is_senior' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $category = Classcategory::findOrFail($request->id);
            $category->update([
                'category'  => $request->input('category'),
                'ca1score'  => $request->input('ca1score'),
                'ca2score'  => $request->input('ca2score'),
                'ca3score'  => $request->input('ca3score'),
                'examscore' => $request->input('examscore'),
                'is_senior' => (bool) $request->input('is_senior'),
            ]);

            DB::commit();
            Log::info('Class Category Updated via AJAX:', $category->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Class category updated successfully.',
                'data'    => $category,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating class category via AJAX:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update class category: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY — single (REST-style)
    // =========================================================================

    public function destroy($id)
    {
        try {
            $category = Classcategory::findOrFail($id);

            // Safety: don't delete if any schoolclass references this category
            $inUse = DB::table('schoolclass')->where('classcategoryid', $id)->exists();
            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'This category is being used by one or more school classes and cannot be deleted.',
                ], 422);
            }

            $category->delete();
            Log::info('Class Category Deleted:', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Class category deleted successfully.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting class category:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete class category: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY — AJAX (payload has classcategoryid)
    // =========================================================================

    public function deleteclasscategory(Request $request)
    {
        Log::info('Delete Class Category AJAX Request:', $request->all());

        $validator = Validator::make($request->all(), [
            'classcategoryid' => 'required|exists:classcategories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $id = $request->input('classcategoryid');

            $inUse = DB::table('schoolclass')->where('classcategoryid', $id)->exists();
            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'This category is being used by one or more school classes and cannot be deleted.',
                ], 422);
            }

            $category = Classcategory::findOrFail($id);
            $category->delete();

            Log::info('Class Category Deleted via AJAX:', ['id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Class category deleted successfully.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting class category via AJAX:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete class category: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // BULK DESTROY
    // =========================================================================

    public function bulkDestroy(Request $request)
    {
        try {
            // Accept ids as array, JSON string, or comma-separated string
            $ids = $request->input('ids');

            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                $ids = is_array($decoded) ? $decoded : array_map('trim', explode(',', $ids));
            }
            if (!is_array($ids)) {
                $ids = [];
            }
            $ids = array_values(array_filter($ids));

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No categories selected.',
                ], 400);
            }

            // Validate all exist
            $existingIds = Classcategory::whereIn('id', $ids)->pluck('id')->toArray();
            $invalidIds  = array_diff($ids, $existingIds);
            if (!empty($invalidIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected categories do not exist.',
                ], 400);
            }

            // Refuse if any are referenced by a schoolclass
            $inUse = DB::table('schoolclass')->whereIn('classcategoryid', $ids)->exists();
            if ($inUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some categories are being used by school classes and cannot be deleted.',
                ], 422);
            }

            DB::beginTransaction();
            $deleted = Classcategory::whereIn('id', $ids)->delete();
            DB::commit();

            Log::info('Class Category bulk delete:', ['deleted' => $deleted, 'ids' => $ids]);

            return response()->json([
                'success'       => true,
                'message'       => $deleted . ' category(ies) deleted successfully.',
                'deleted_count' => $deleted,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk delete failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error deleting categories: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function cleanUtf8String($string)
    {
        if (empty($string)) {
            return '';
        }
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
        return $string;
    }
}
