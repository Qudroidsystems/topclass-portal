<?php

namespace App\Http\Controllers;

use App\Models\Schoolterm;
use App\Models\Schoolclass;
use Illuminate\Http\Request;
use App\Models\Schoolsession;
use App\Models\SchoolBillModel;
use Illuminate\Support\Facades\DB;
use App\Models\SchoolBillTermSession;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Yajra\DataTables\Facades\DataTables;

class SchoolBillTermSessionController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View school-bill-for-term-session|Create school-bill-for-term-session|Update school-bill-for-term-session|Delete school-bill-for-term-session', ['only' => ['index', 'data', 'stats', 'show', 'getRelated']]);
        $this->middleware('permission:Create school-bill-for-term-session', ['only' => ['create', 'store']]);
        $this->middleware('permission:Update school-bill-for-term-session', ['only' => ['edit', 'update']]);
        $this->middleware('permission:Delete school-bill-for-term-session', ['only' => ['destroy', 'bulkDestroy']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index()
    {
        $pagetitle      = 'School Bill Term Session Management';
        $terms          = Schoolterm::all();
        $schoolsessions = Schoolsession::all();

        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get([
                'schoolclass.id as id',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm as arm_name',
            ])
            ->map(function ($c) {
                $c->label = trim($c->schoolclass . ($c->arm_name ? ' ' . $c->arm_name : ''));
                return $c;
            })
            ->sortBy('schoolclass');

        $schoolbills = SchoolBillModel::whereIn('statusId', [1, 2])->get();

        return view('schoolbilltermsession.index', compact(
            'pagetitle', 'schoolbills', 'schoolclasses', 'terms', 'schoolsessions'
        ));
    }

    // =========================================================================
    // DATATABLE
    // =========================================================================

    public function data(Request $request)
    {
        $assignments = SchoolBillTermSession::query()
            ->leftJoin('school_bill', 'school_bill.id', '=', 'school_bill_class_term_session.bill_id')
            ->leftJoin('schoolclass', 'schoolclass.id', '=', 'school_bill_class_term_session.class_id')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->leftJoin('schoolterm', 'schoolterm.id', '=', 'school_bill_class_term_session.termid_id')
            ->leftJoin('schoolsession', 'schoolsession.id', '=', 'school_bill_class_term_session.session_id')
            ->leftJoin('users', 'users.id', '=', 'school_bill_class_term_session.created_by')
            ->select([
                'school_bill_class_term_session.id as id',
                'school_bill_class_term_session.bill_id',
                'school_bill_class_term_session.class_id',
                'school_bill_class_term_session.termid_id',
                'school_bill_class_term_session.session_id',
                'school_bill_class_term_session.student_status_id',
                'school_bill.statusId as bill_status_id',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm as arm_name',
                'schoolterm.term as schoolterm',
                'schoolsession.session as schoolsession',
                'users.name as createdBy',
                'school_bill.title as schoolbill',
                'school_bill.bill_amount as bill_amount',
                'school_bill_class_term_session.updated_at as updated_at',
            ]);

        // Newest first: the current term of the current session, then the rest of the
        // current session, then every other session (latest session / term first).
        $currentSessionId = (int) (Schoolsession::where('status', 'Current')->value('id') ?? 0);
        $currentTermId    = (int) (optional(Schoolterm::current())->id ?? 0);

        $assignments
            ->orderByRaw(
                'CASE WHEN CAST(school_bill_class_term_session.session_id AS UNSIGNED) = ? '
                . 'AND CAST(school_bill_class_term_session.termid_id AS UNSIGNED) = ? THEN 0 '
                . 'WHEN CAST(school_bill_class_term_session.session_id AS UNSIGNED) = ? THEN 1 ELSE 2 END',
                [$currentSessionId, $currentTermId, $currentSessionId]
            )
            ->orderByRaw('CAST(school_bill_class_term_session.session_id AS UNSIGNED) DESC')
            ->orderByRaw('CAST(school_bill_class_term_session.termid_id AS UNSIGNED) DESC')
            ->orderBy('school_bill_class_term_session.id', 'desc');

        return DataTables::of($assignments)
            // Ordering is fixed above; ignore the browser's column ordering.
            ->order(function () {
            })
            // Searching: 'createdBy' etc. are aliases/joined columns, so point the search at the
            // real columns (otherwise it looks for school_bill_class_term_session.createdBy).
            ->filterColumn('createdBy', function ($q, $kw) {
                $q->whereRaw('LOWER(users.name) LIKE ?', ['%' . mb_strtolower($kw) . '%']);
            })
            ->filterColumn('formatted_bill', function ($q, $kw) {
                $q->whereRaw('LOWER(school_bill.title) LIKE ?', ['%' . mb_strtolower($kw) . '%']);
            })
            ->filterColumn('formatted_class', function ($q, $kw) {
                $q->whereRaw("LOWER(CONCAT(schoolclass.schoolclass, ' ', COALESCE(schoolarm.arm, ''))) LIKE ?", ['%' . mb_strtolower($kw) . '%']);
            })
            ->filterColumn('formatted_term_session', function ($q, $kw) {
                $q->whereRaw("LOWER(CONCAT(schoolterm.term, ' ', schoolsession.session)) LIKE ?", ['%' . mb_strtolower($kw) . '%']);
            })
            ->filterColumn('formatted_audience', function ($q, $kw) {
                $k = mb_strtolower($kw);
                $q->where(function ($w) use ($k) {
                    if (str_contains($k, 'old')) {
                        $w->orWhereRaw('COALESCE(school_bill_class_term_session.student_status_id, school_bill.statusId) = 1');
                    }
                    if (str_contains($k, 'new')) {
                        $w->orWhereRaw('COALESCE(school_bill_class_term_session.student_status_id, school_bill.statusId) = 2');
                    }
                    if (str_contains($k, 'both') || str_contains($k, '&')) {
                        $w->orWhereRaw("COALESCE(school_bill_class_term_session.student_status_id, school_bill.statusId, 0) IN (0, '')");
                    }
                    if (!str_contains($k, 'old') && !str_contains($k, 'new') && !str_contains($k, 'both') && !str_contains($k, '&')) {
                        $w->orWhereRaw('1 = 0');
                    }
                });
            })
            ->addIndexColumn()
            ->addColumn('formatted_class', function ($row) {
                return trim($row->schoolclass . ($row->arm_name ? ' ' . $row->arm_name : ''));
            })
            ->addColumn('formatted_term_session', function ($row) {
                return '<span class="ts-badge ts-badge-term">'
                    . e($row->schoolterm)
                    . '</span>'
                    . '<span class="ts-badge ts-badge-session ms-1">'
                    . e($row->schoolsession)
                    . '</span>';
            })
            ->addColumn('formatted_audience', function ($row) {
                // Assignment setting wins; legacy rows (NULL) fall back to the bill's own type.
                $effective = $row->student_status_id ?? $row->bill_status_id;
                $label     = SchoolBillTermSession::audienceLabel($effective) ?? 'Old & new students';
                $cls       = match ((int) $effective) {
                    1       => 'bg-info-subtle text-info',
                    2       => 'bg-success-subtle text-success',
                    default => 'bg-secondary-subtle text-secondary',
                };
                return '<span class="badge ' . $cls . '">' . e($label) . '</span>';
            })
            ->addColumn('formatted_bill', function ($row) {
                return '<div class="fw-semibold">' . e($row->schoolbill) . '</div>'
                    . '<div class="text-muted small">&#8358;&nbsp;' . number_format($row->bill_amount, 2) . '</div>';
            })
            ->addColumn('formatted_date', function ($row) {
                return $row->updated_at
                    ? '<span class="text-muted small">'
                        . \Carbon\Carbon::parse($row->updated_at)->format('d M Y')
                        . '<br><span style="font-size:10px">'
                        . \Carbon\Carbon::parse($row->updated_at)->format('H:i')
                        . '</span></span>'
                    : 'N/A';
            })
            ->addColumn('action', function ($row) {
                $classLabel = trim($row->schoolclass . ($row->arm_name ? ' ' . $row->arm_name : ''));
                $buttons = '<div class="btn-group btn-group-sm">';
                if (auth()->user()->can('Update school-bill-for-term-session')) {
                    $buttons .= '<button class="btn btn-primary edit-assignment" title="Edit"
                        data-id="'         . $row->id         . '"
                        data-bill_id="'    . $row->bill_id    . '"
                        data-class_id="'   . $row->class_id   . '"
                        data-termid_id="'  . $row->termid_id  . '"
                        data-session_id="' . $row->session_id . '"
                        data-student_status_id="' . ($row->student_status_id ?? $row->bill_status_id ?? 0) . '">
                        <i class="ri-pencil-line"></i>
                    </button>';
                }
                if (auth()->user()->can('Delete school-bill-for-term-session')) {
                    $buttons .= '<button class="btn btn-danger delete-assignment" title="Delete"
                        data-id="'    . $row->id       . '"
                        data-title="' . e($row->schoolbill . ' — ' . $classLabel) . '">
                        <i class="ri-delete-bin-line"></i>
                    </button>';
                }
                $buttons .= '</div>';
                return $buttons;
            })
            ->rawColumns(['formatted_term_session', 'formatted_audience', 'formatted_bill', 'formatted_date', 'action'])
            ->make(true);
    }

    // =========================================================================
    // STATS
    // =========================================================================

    public function stats()
    {
        $assignments = SchoolBillTermSession::query()
            ->leftJoin('school_bill', 'school_bill.id', '=', 'school_bill_class_term_session.bill_id')
            ->select([
                'school_bill_class_term_session.id',
                'school_bill_class_term_session.session_id',
                'school_bill.bill_amount',
            ])
            ->get();

        $uniqueSessions = SchoolBillTermSession::distinct('session_id')->count('session_id');
        $uniqueBills    = SchoolBillTermSession::distinct('bill_id')->count('bill_id');

        return response()->json([
            'stats' => [
                'total'           => $assignments->count(),
                'unique_bills'    => $uniqueBills,
                'unique_sessions' => $uniqueSessions,
                'total_amount'    => $assignments->sum('bill_amount'),
            ]
        ]);
    }

    // =========================================================================
    // STORE — with duplicate pre-check + graceful handling of DB unique violations
    // =========================================================================

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bill_id'      => 'required|exists:school_bill,id',
            'class_id'     => 'required|array|min:1',
            'class_id.*'   => 'exists:schoolclass,id',
            'termid_id'    => 'required|array|min:1',
            'termid_id.*'  => 'exists:schoolterm,id',
            'session_id'   => 'required|exists:schoolsession,id',
            'student_status_id' => 'nullable|in:0,1,2',
        ], [
            'student_status_id.in' => 'Student type must be Both, Old or New.',
            'bill_id.required'    => 'Please select a school bill.',
            'bill_id.exists'      => 'Selected bill does not exist.',
            'class_id.required'   => 'Please select at least one class.',
            'class_id.min'        => 'Please select at least one class.',
            'termid_id.required'  => 'Please select at least one term.',
            'termid_id.min'       => 'Please select at least one term.',
            'session_id.required' => 'Please select a session.',
            'session_id.exists'   => 'Selected session does not exist.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $bill_id    = $request->input('bill_id');
        $class_ids  = array_values(array_filter((array) $request->input('class_id', [])));
        $term_ids   = array_values(array_filter((array) $request->input('termid_id', [])));
        $session_id = $request->input('session_id');
        // 0 = both, 1 = old, 2 = new
        $status_id  = $request->filled('student_status_id') ? (int) $request->input('student_status_id') : 0;

        // Pre-check: find any existing combinations that would overlap and describe them.
        // A "both" assignment overlaps every existing one; an old/new one only overlaps
        // the same type, a "both" one, or a legacy (unset) one.
        $existing = SchoolBillTermSession::where('bill_id', $bill_id)
            ->whereIn('class_id', $class_ids)
            ->whereIn('termid_id', $term_ids)
            ->where('session_id', $session_id)
            ->when($status_id !== 0, function ($q) use ($status_id) {
                $q->where(function ($w) use ($status_id) {
                    $w->whereNull('student_status_id')
                      ->orWhere('student_status_id', 0)
                      ->orWhere('student_status_id', $status_id);
                });
            })
            ->get(['class_id', 'termid_id']);

        if ($existing->isNotEmpty()) {
            $existingLabels = $existing->map(function ($row) {
                $class = Schoolclass::with('armRelation')->find($row->class_id);
                $term  = Schoolterm::find($row->termid_id);
                $label = trim(($class->schoolclass ?? '?') . ' ' . ($class->armRelation->arm ?? ''));
                return $label . ' / ' . ($term->term ?? '?');
            })->unique()->values()->implode(', ');

            return response()->json([
                'success' => false,
                'message' => 'These combinations already exist: ' . $existingLabels,
            ], 422);
        }

        DB::beginTransaction();
        try {
            $created = [];
            foreach ($term_ids as $term_id) {
                foreach ($class_ids as $class_id) {
                    $created[] = SchoolBillTermSession::create([
                        'bill_id'    => $bill_id,
                        'class_id'   => $class_id,
                        'termid_id'  => $term_id,
                        'session_id' => $session_id,
                        'student_status_id' => $status_id,
                        'created_by' => auth()->id(),
                    ]);
                }
            }
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($created) . ' assignment(s) created successfully.',
                'data'    => $created,
            ], 201);

        } catch (QueryException $e) {
            DB::rollBack();

            // 1062 = duplicate entry (unique key violation)
            if (($e->errorInfo[1] ?? null) === 1062) {
                return response()->json([
                    'success' => false,
                    'message' => 'One or more of these combinations already exist. Please refresh and try again.',
                ], 422);
            }

            Log::error('SchoolBillTermSession store QueryException', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage(),
            ], 500);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('SchoolBillTermSession store failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // SHOW / EDIT
    // =========================================================================

    public function show($id)
    {
        $assignment = SchoolBillTermSession::find($id);
        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Assignment not found.'], 404);
        }
        return response()->json(['success' => true, 'data' => $assignment]);
    }

    public function edit($id)
    {
        return $this->show($id);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, $id)
    {
        $assignment = SchoolBillTermSession::find($id);
        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Assignment not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'bill_id'    => 'required|exists:school_bill,id',
            'class_id'   => 'required|exists:schoolclass,id',
            'termid_id'  => 'required|exists:schoolterm,id',
            'session_id' => 'required|exists:schoolsession,id',
            'student_status_id' => 'nullable|in:0,1,2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $status_id = $request->filled('student_status_id')
            ? (int) $request->input('student_status_id')
            : (int) ($assignment->student_status_id ?? 0);

        $duplicate = SchoolBillTermSession::where('bill_id', $request->bill_id)
            ->where('class_id', $request->class_id)
            ->where('termid_id', $request->termid_id)
            ->where('session_id', $request->session_id)
            ->where('id', '!=', $id)
            ->when($status_id !== 0, function ($q) use ($status_id) {
                $q->where(function ($w) use ($status_id) {
                    $w->whereNull('student_status_id')
                      ->orWhere('student_status_id', 0)
                      ->orWhere('student_status_id', $status_id);
                });
            })
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'This combination already exists.',
            ], 422);
        }

        $assignment->update([
            'bill_id'    => $request->bill_id,
            'class_id'   => $request->class_id,
            'termid_id'  => $request->termid_id,
            'session_id' => $request->session_id,
            'student_status_id' => $status_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignment updated successfully.',
            'data'    => $assignment,
        ]);
    }

    // =========================================================================
    // DESTROY (single) — hard delete
    // =========================================================================

    public function destroy($id)
    {
        try {
            $assignment = SchoolBillTermSession::find($id);
            if (!$assignment) {
                return response()->json(['success' => false, 'message' => 'Assignment not found.'], 404);
            }

            $assignment->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Assignment deleted successfully.',
            ]);

        } catch (\Throwable $e) {
            Log::error('SchoolBillTermSession destroy failed', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Could not delete: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // BULK DESTROY — hard delete
    // =========================================================================

    public function bulkDestroy(Request $request)
    {
        try {
            $ids = $request->input('ids');

            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                $ids = is_array($decoded) ? $decoded : array_map('trim', explode(',', $ids));
            }
            if (!is_array($ids)) {
                $ids = [];
            }
            $ids = array_values(array_filter($ids, fn($v) => $v !== null && $v !== ''));

            if (empty($ids)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No assignments selected.',
                ], 400);
            }

            $existingIds = SchoolBillTermSession::whereIn('id', $ids)->pluck('id')->toArray();
            $invalidIds  = array_diff($ids, $existingIds);
            if (!empty($invalidIds)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected assignments do not exist: ' . implode(', ', $invalidIds),
                ], 400);
            }

            DB::beginTransaction();
            $deleted = SchoolBillTermSession::whereIn('id', $ids)->forceDelete();
            DB::commit();

            return response()->json([
                'success'       => true,
                'message'       => $deleted . ' assignment(s) deleted successfully.',
                'deleted_count' => $deleted,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('SchoolBillTermSession bulkDestroy failed', [
                'ids'   => $request->input('ids'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Could not delete: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // GET RELATED
    // =========================================================================

    public function getRelated($id)
    {
        $assignment = SchoolBillTermSession::find($id);
        if (!$assignment) {
            return response()->json(['success' => false, 'message' => 'Assignment not found.'], 404);
        }

        $related = SchoolBillTermSession::where('bill_id', $assignment->bill_id)
            ->where('session_id', $assignment->session_id)
            ->select('class_id', 'termid_id')
            ->get();

        return response()->json([
            'success'    => true,
            'bill_id'    => $assignment->bill_id,
            'class_ids'  => $related->pluck('class_id')->unique()->values(),
            'term_ids'   => $related->pluck('termid_id')->unique()->values(),
            'session_id' => $assignment->session_id,
        ]);
    }
}