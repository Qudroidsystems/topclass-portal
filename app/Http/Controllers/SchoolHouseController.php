<?php

namespace App\Http\Controllers;

use App\Models\Schoolhouse;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SchoolHouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View schoolhouse|Create schoolhouse|Update schoolhouse|Delete schoolhouse', ['only' => ['index', 'data', 'stats']]);
        $this->middleware('permission:Create schoolhouse', ['only' => ['store']]);
        $this->middleware('permission:Update schoolhouse', ['only' => ['update', 'updatehouse']]);
        $this->middleware('permission:Delete schoolhouse', ['only' => ['destroy', 'deletehouse', 'deleteMultiple']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "School House Management";

        try {
            $schoolterm    = Schoolterm::orderBy('term')->get();
            $schoolsession = Schoolsession::orderBy('session')->get();

            $staff = User::whereHas('roles', function ($q) {
                $q->where('name', '!=', 'Student');
            })->get(['users.id as userid', 'users.name as name']);

            return view('schoolhouse.index')
                ->with('schoolterm', $schoolterm)
                ->with('schoolsession', $schoolsession)
                ->with('staff', $staff)
                ->with('pagetitle', $pagetitle);

        } catch (\Exception $e) {
            Log::error('SchoolHouse index error', ['error' => $e->getMessage()]);
            return back()->with('danger', 'Error loading school houses: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATATABLE
    // =========================================================================

    public function data(Request $request)
    {
        try {
            $query = Schoolhouse::leftJoin('users', 'users.id', '=', 'schoolhouses.housemasterid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'schoolhouses.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'schoolhouses.sessionid')
                ->select([
                    'schoolhouses.id as id',
                    'schoolhouses.house as house',
                    'schoolhouses.housecolour as housecolour',
                    'schoolhouses.housemasterid as housemasterid',
                    'schoolhouses.termid as termid',
                    'schoolhouses.sessionid as sessionid',
                    'users.name as housemaster_name',
                    'schoolterm.term as term_name',
                    'schoolsession.session as session_name',
                    'schoolhouses.updated_at as updated_at',
                ]);

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '">';
                })

                ->addColumn('house_info', function ($row) {
                    return '<div>'
                        . '<span class="fw-semibold text-dark">' . e($this->cleanUtf8String($row->house ?? '')) . '</span>'
                        . '<small class="text-muted d-block">ID: ' . $row->id . '</small>'
                        . '</div>';
                })

                ->addColumn('colour_info', function ($row) {
                    $colour = $row->housecolour ?: '#cccccc';
                    $swatch = preg_match('/^#[0-9A-Fa-f]{3,6}$/', $colour)
                        ? '<span style="display:inline-block;width:14px;height:14px;border-radius:50%;background:' . e($colour) . ';border:1px solid #ccc;margin-right:6px;vertical-align:middle;"></span>'
                        : '';
                    return $swatch
                        . '<span class="sh-badge sh-badge-colour" style="background-color:' . e($colour) . ';color:#fff;">'
                        . e($this->cleanUtf8String($colour))
                        . '</span>';
                })

                ->addColumn('master_info', function ($row) {
                    return $row->housemaster_name
                        ? '<span class="fw-semibold">' . e($this->cleanUtf8String($row->housemaster_name)) . '</span>'
                        : '<span class="text-muted">N/A</span>';
                })

                ->addColumn('term_info', function ($row) {
                    return $row->term_name
                        ? '<span class="sh-badge sh-badge-term">' . e($this->cleanUtf8String($row->term_name)) . '</span>'
                        : '<span class="text-muted small">—</span>';
                })

                ->addColumn('session_info', function ($row) {
                    return $row->session_name
                        ? '<span class="sh-badge sh-badge-session">' . e($this->cleanUtf8String($row->session_name)) . '</span>'
                        : '<span class="text-muted small">—</span>';
                })

                ->addColumn('formatted_date', function ($row) {
                    if (!$row->updated_at) return '<span class="text-muted small">—</span>';
                    return '<small class="text-muted">'
                        . \Carbon\Carbon::parse($row->updated_at)->format('d M Y')
                        . '</small>';
                })

                ->addColumn('action', function ($row) {
                    $buttons = '<div class="d-flex gap-1">';

                    if (auth()->user() && auth()->user()->can('Update schoolhouse')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-secondary edit-house-btn" title="Edit" '
                            . 'data-id="%s" data-house="%s" data-housecolour="%s" '
                            . 'data-housemasterid="%s" data-termid="%s" data-sessionid="%s">'
                            . '<i class="ph-pencil"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->house ?? '')),
                            e($this->cleanUtf8String($row->housecolour ?? '')),
                            $row->housemasterid ?? '',
                            $row->termid ?? '',
                            $row->sessionid ?? ''
                        );
                    }

                    if (auth()->user() && auth()->user()->can('Delete schoolhouse')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-danger delete-house-btn" title="Delete" '
                            . 'data-id="%s" data-name="%s"><i class="ph-trash"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->house ?? 'Unknown House'))
                        );
                    }

                    return $buttons . '</div>';
                })

                ->rawColumns(['checkbox', 'house_info', 'colour_info', 'master_info', 'term_info', 'session_info', 'formatted_date', 'action'])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('SchoolHouse DataTable error', [
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
                    'total'           => Schoolhouse::count(),
                    'unique_masters'  => Schoolhouse::distinct('housemasterid')->count('housemasterid'),
                    'unique_terms'    => Schoolhouse::distinct('termid')->count('termid'),
                    'unique_sessions' => Schoolhouse::distinct('sessionid')->count('sessionid'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('SchoolHouse stats error', ['error' => $e->getMessage()]);
            return response()->json([
                'stats' => ['total' => 0, 'unique_masters' => 0, 'unique_terms' => 0, 'unique_sessions' => 0],
            ]);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'house'         => 'required|string|max:255',
            'housecolour'   => [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^#[0-9A-Fa-f]{6}$|^#[0-9A-Fa-f]{3}$|^[a-zA-Z]+$|^rgb\(\d{1,3},\s*\d{1,3},\s*\d{1,3}\)$/', $value)) {
                        $fail('The house colour must be a valid CSS color (name, hex, or RGB).');
                    }
                },
            ],
            'housemasterid' => 'required|exists:users,id',
            'termid'        => 'required|exists:schoolterm,id',
            'sessionid'     => 'required|exists:schoolsession,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $duplicate = Schoolhouse::where('house', $request->house)
            ->where('housemasterid', $request->housemasterid)
            ->where('housecolour', $request->housecolour)
            ->where('termid', $request->termid)
            ->where('sessionid', $request->sessionid)
            ->exists();

        if ($duplicate) {
            return response()->json(['success' => false, 'message' => 'Record already exists'], 422);
        }

        try {
            $house = Schoolhouse::create($request->only(['house', 'housecolour', 'housemasterid', 'termid', 'sessionid']));

            return response()->json([
                'success' => true,
                'message' => 'School house created successfully',
                'data'    => $house,
            ], 201);

        } catch (\Exception $e) {
            Log::error('SchoolHouse store error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // UPDATE — route style
    // =========================================================================

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'house'         => 'required|string|max:255',
            'housecolour'   => [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^#[0-9A-Fa-f]{6}$|^#[0-9A-Fa-f]{3}$|^[a-zA-Z]+$|^rgb\(\d{1,3},\s*\d{1,3},\s*\d{1,3}\)$/', $value)) {
                        $fail('The house colour must be a valid CSS color (name, hex, or RGB).');
                    }
                },
            ],
            'housemasterid' => 'required|exists:users,id',
            'termid'        => 'required|exists:schoolterm,id',
            'sessionid'     => 'required|exists:schoolsession,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $duplicate = Schoolhouse::where('house', $request->house)
            ->where('housemasterid', $request->housemasterid)
            ->where('housecolour', $request->housecolour)
            ->where('termid', $request->termid)
            ->where('sessionid', $request->sessionid)
            ->where('id', '!=', $id)
            ->exists();

        if ($duplicate) {
            return response()->json(['success' => false, 'message' => 'Record already exists'], 422);
        }

        try {
            $row = Schoolhouse::findOrFail($id);
            $row->update($request->only(['house', 'housecolour', 'housemasterid', 'termid', 'sessionid']));
            return response()->json(['success' => true, 'message' => 'School house updated successfully', 'data' => $row]);
        } catch (\Exception $e) {
            Log::error('SchoolHouse update error', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // UPDATE — AJAX
    // =========================================================================

    public function updatehouse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'            => 'required|exists:schoolhouses,id',
            'house'         => 'required|string|max:255',
            'housecolour'   => [
                'required', 'string', 'max:255',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^#[0-9A-Fa-f]{6}$|^#[0-9A-Fa-f]{3}$|^[a-zA-Z]+$|^rgb\(\d{1,3},\s*\d{1,3},\s*\d{1,3}\)$/', $value)) {
                        $fail('The house colour must be a valid CSS color (name, hex, or RGB).');
                    }
                },
            ],
            'housemasterid' => 'required|exists:users,id',
            'termid'        => 'required|exists:schoolterm,id',
            'sessionid'     => 'required|exists:schoolsession,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        $duplicate = Schoolhouse::where('house', $request->house)
            ->where('housemasterid', $request->housemasterid)
            ->where('housecolour', $request->housecolour)
            ->where('termid', $request->termid)
            ->where('sessionid', $request->sessionid)
            ->where('id', '!=', $request->id)
            ->exists();

        if ($duplicate) {
            return response()->json(['success' => false, 'message' => 'Record already exists'], 422);
        }

        try {
            $row = Schoolhouse::findOrFail($request->id);
            $row->update($request->only(['house', 'housecolour', 'housemasterid', 'termid', 'sessionid']));
            return response()->json(['success' => true, 'message' => 'School house updated successfully', 'data' => $row]);
        } catch (\Exception $e) {
            Log::error('SchoolHouse updatehouse error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function destroy($id)
    {
        try {
            $row = Schoolhouse::findOrFail($id);
            $row->delete();
            return response()->json(['success' => true, 'message' => 'School house deleted successfully']);
        } catch (\Exception $e) {
            Log::error('SchoolHouse destroy error', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // DELETE HOUSE (AJAX)
    // =========================================================================

    public function deletehouse(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'houseid' => 'required|exists:schoolhouses,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $row = Schoolhouse::findOrFail($request->houseid);
            $row->delete();
            return response()->json(['success' => true, 'message' => 'School house deleted successfully']);
        } catch (\Exception $e) {
            Log::error('SchoolHouse deletehouse error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
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
                return response()->json(['success' => false, 'message' => 'No houses selected.'], 400);
            }

            $existing = Schoolhouse::whereIn('id', $ids)->pluck('id')->toArray();
            $invalid  = array_diff($ids, $existing);
            if (!empty($invalid)) {
                return response()->json(['success' => false, 'message' => 'Some selected houses do not exist.'], 400);
            }

            DB::beginTransaction();
            $deleted = Schoolhouse::whereIn('id', $ids)->delete();
            DB::commit();

            return response()->json([
                'success'       => true,
                'message'       => $deleted . ' house(s) deleted successfully.',
                'deleted_count' => $deleted,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SchoolHouse bulk delete error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    private function cleanUtf8String($string)
    {
        if (empty($string)) return '';
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
    }
}
