<?php

namespace App\Http\Controllers;

use App\Models\Principalscomment;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class PrincipalsCommentController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View principals-comment|Create principals-comment|Update principals-comment|Delete principals-comment', ['only' => ['index', 'data', 'stats']]);
        $this->middleware('permission:Create principals-comment', ['only' => ['store']]);
        $this->middleware('permission:Update principals-comment', ['only' => ['update']]);
        $this->middleware('permission:Delete principals-comment', ['only' => ['destroy']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "Principals Comment Management";

        // School classes with ARM NAME (avoid `as arm` alias collision)
        $schoolclasses = Schoolclass::leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->get([
                'schoolclass.id as id',
                'schoolclass.schoolclass as schoolclass',
                'schoolarm.arm as arm_name',
            ])
            ->map(function ($c) {
                $c->label = trim($c->schoolclass . ($c->arm_name ? ' (' . $c->arm_name . ')' : ''));
                return $c;
            })
            ->sortBy('schoolclass');

        // Exclude students from staff dropdown
        $staff = User::whereHas('roles', function ($q) {
            $q->where('name', '!=', 'Student');
        })->orderBy('name')->get(['users.id', 'users.name', 'users.avatar']);

        $sessions = Schoolsession::orderByDesc('session')->get(['id', 'session', 'status']);
        $terms    = Schoolterm::orderBy('id')->get(['id', 'term']);

        return view('principalscomment.index')
            ->with(compact('schoolclasses', 'staff', 'sessions', 'terms', 'pagetitle'));
    }

    // =========================================================================
    // DATATABLE
    // =========================================================================

    public function data(Request $request)
    {
        try {
            $query = Principalscomment::query()
                ->leftJoin('users', 'users.id', '=', 'principalscomments.staffId')
                ->leftJoin('schoolclass', 'schoolclass.id', '=', 'principalscomments.schoolclassid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'principalscomments.sessionid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'principalscomments.termid')
                ->select([
                    'principalscomments.id as pcid',
                    'principalscomments.staffId as staffid',
                    'principalscomments.schoolclassid',
                    'users.name as staffname',
                    'users.avatar as picture',
                    'schoolclass.schoolclass as sclass',
                    'schoolarm.arm as arm_name',
                    'schoolsession.session as session_name',
                    'schoolterm.term as term_name',
                    'principalscomments.updated_at',
                ]);

            return DataTables::of($query)
                ->addIndexColumn()

                ->filterColumn('staffname', function ($query, $keyword) {
                    $query->where('users.name', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('sclass', function ($query, $keyword) {
                    $query->where('schoolclass.schoolclass', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('arm_name', function ($query, $keyword) {
                    $query->where('schoolarm.arm', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('session_name', function ($query, $keyword) {
                    $query->where('schoolsession.session', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('term_name', function ($query, $keyword) {
                    $query->where('schoolterm.term', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('formatted_date', function ($query, $keyword) {
                    $query->whereRaw("DATE(principalscomments.updated_at) LIKE ?", ["%{$keyword}%"]);
                })

                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->pcid . '">';
                })

                ->addColumn('staff_info', function ($row) {
                    $name = $this->cleanUtf8String($row->staffname ?? 'Unknown');
                    $initial = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
                    $defaultUrl = asset('storage/staff_avatars/unnamed.jpg');

                    $avatarUrl = null;
                    if (!empty($row->picture) && !in_array(trim($row->picture), ['unnamed.jpg', 'unnamed.png', ''], true)) {
                        if (\Storage::exists('public/staff_avatars/' . $row->picture)) {
                            $avatarUrl = asset('storage/staff_avatars/' . $row->picture);
                        }
                    }

                    $avatarHtml = $avatarUrl
                        ? '<img src="' . e($avatarUrl) . '" alt="' . e($name) . '" class="staff-image" onerror="this.onerror=null;this.src=\'' . e($defaultUrl) . '\'">'
                        : '<div class="avatar-initials">' . e($initial) . '</div>';

                    return '<div class="d-flex align-items-center gap-2">'
                        . $avatarHtml
                        . '<span class="fw-semibold">' . e($name) . '</span>'
                        . '</div>';
                })

                ->addColumn('class_info', function ($row) {
                    return '<span class="pc-badge pc-badge-class">' . e($this->cleanUtf8String($row->sclass ?? '—')) . '</span>';
                })

                ->addColumn('arm_info', function ($row) {
                    return $row->arm_name
                        ? e($this->cleanUtf8String($row->arm_name))
                        : '<span class="text-muted">—</span>';
                })

                ->addColumn('session_info', function ($row) {
                    return '<span class="pc-badge pc-badge-session">' . e($this->cleanUtf8String($row->session_name ?? '—')) . '</span>';
                })

                ->addColumn('term_info', function ($row) {
                    return '<span class="pc-badge pc-badge-term">' . e($this->cleanUtf8String($row->term_name ?? '—')) . '</span>';
                })

                ->addColumn('formatted_date', function ($row) {
                    if (!$row->updated_at) return '<span class="text-muted small">—</span>';
                    return '<small class="text-muted">'
                        . \Carbon\Carbon::parse($row->updated_at)->format('d M Y')
                        . '</small>';
                })

                ->addColumn('action', function ($row) {
                    $buttons = '<div class="d-flex gap-1">';

                    if (auth()->user() && auth()->user()->can('Update principals-comment')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-secondary edit-btn" title="Edit" '
                            . 'data-id="%s" data-staffid="%s" data-schoolclassid="%s">'
                            . '<i class="ph-pencil"></i></button>',
                            $row->pcid,
                            $row->staffid,
                            $row->schoolclassid
                        );
                    }

                    if (auth()->user() && auth()->user()->can('Delete principals-comment')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-danger delete-btn" title="Delete" '
                            . 'data-id="%s"><i class="ph-trash"></i></button>',
                            $row->pcid
                        );
                    }

                    return $buttons . '</div>';
                })

                ->rawColumns(['checkbox', 'staff_info', 'class_info', 'arm_info', 'session_info', 'term_info', 'formatted_date', 'action'])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('PrincipalsComment DataTable error', [
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
                    'total'           => Principalscomment::count(),
                    'unique_staff'    => Principalscomment::distinct('staffId')->count('staffId'),
                    'unique_classes'  => Principalscomment::distinct('schoolclassid')->count('schoolclassid'),
                    'unique_sessions' => Principalscomment::distinct('sessionid')->count('sessionid'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'stats' => ['total' => 0, 'unique_staff' => 0, 'unique_classes' => 0, 'unique_sessions' => 0],
            ]);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'staffId'         => 'required|exists:users,id',
            'sessionid'       => 'required|exists:schoolsession,id',
            'termid'          => 'required|exists:schoolterm,id',
            'schoolclassid'   => 'required|array|min:1',
            'schoolclassid.*' => 'exists:schoolclass,id',
        ], [
            'staffId.required'         => 'Please select a staff member!',
            'sessionid.required'       => 'Please select a session!',
            'termid.required'          => 'Please select a term!',
            'schoolclassid.required'   => 'Please select at least one class!',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $staffId        = $request->input('staffId');
        $sessionId      = $request->input('sessionid');
        $termId         = $request->input('termid');
        $schoolClassIds = $request->input('schoolclassid', []);

        $createdRecords = [];
        $skipped        = 0;

        foreach ($schoolClassIds as $schoolClassId) {
            $exists = Principalscomment::where('staffId', $staffId)
                ->where('schoolclassid', $schoolClassId)
                ->where('sessionid', $sessionId)
                ->where('termid', $termId)
                ->exists();

            if ($exists) { $skipped++; continue; }

            $createdRecords[] = Principalscomment::create([
                'staffId'       => $staffId,
                'schoolclassid' => $schoolClassId,
                'sessionid'     => $sessionId,
                'termid'        => $termId,
            ]);
        }

        if (empty($createdRecords)) {
            return response()->json([
                'success' => false,
                'message' => 'This staff is already assigned to all selected classes for the chosen session and term.',
            ], 422);
        }

        $msg = count($createdRecords) . ' assignment(s) added successfully!';
        if ($skipped > 0) $msg .= ' ' . $skipped . ' duplicate(s) skipped.';

        return response()->json([
            'success' => true,
            'message' => $msg,
        ], 201);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'staffId'       => 'required|exists:users,id',
            'schoolclassid' => 'required|exists:schoolclass,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        $record = Principalscomment::findOrFail($id);

        $exists = Principalscomment::where('staffId', $request->staffId)
            ->where('schoolclassid', $request->schoolclassid)
            ->where('sessionid', $record->sessionid)
            ->where('termid', $record->termid)
            ->where('id', '!=', $id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'This staff is already assigned to this class in this session/term.',
            ], 422);
        }

        $record->update([
            'staffId'       => $request->staffId,
            'schoolclassid' => $request->schoolclassid,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Assignment updated successfully.',
        ]);
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function destroy($id)
    {
        try {
            $record = Principalscomment::findOrFail($id);
            $record->delete();

            return response()->json([
                'success' => true,
                'message' => 'Assignment deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Principalscomment destroy error', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete assignment. Please try again.',
            ], 500);
        }
    }

    private function cleanUtf8String($string)
    {
        if (empty($string)) return '';
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
    }
}
