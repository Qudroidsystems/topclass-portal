<?php

namespace App\Http\Controllers;

use App\Models\Broadsheets;
use App\Models\BroadsheetsMock;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Subjectclass;
use App\Models\SubjectRegistrationStatus;
use App\Models\SubjectTeacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class SubjectClassController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View subject-class|Create subject-class|Update subject-class|Delete subject-class', ['only' => ['index', 'data', 'stats']]);
        $this->middleware('permission:Create subject-class', ['only' => ['store']]);
        $this->middleware('permission:Update subject-class', ['only' => ['update']]);
        $this->middleware('permission:Delete subject-class', ['only' => ['destroy', 'deletesubjectclass', 'deleteMultiple']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "Subject Class Management";

        try {
            // ── School classes with the ARM NAME (not the FK id) ────────────
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

            // ── Subject teachers list for the add modal ─────────────────────
            $subjectteacher = SubjectTeacher::leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->get([
                    'subjectteacher.id as id',
                    'subjectteacher.staffid as subtid',
                    'subjectteacher.subjectid as subid',
                    'subject.id as subjectid',
                    'subject.subject as subject',
                    'subject.subject_code as subjectcode',
                    'users.name as teachername',
                    'schoolterm.id as termid',
                    'schoolterm.term as termname',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as sessionname',
                ])
                ->sortBy('subject');

            // ── Staff list for the edit modal dropdown ──────────────────────
            $allStaff = User::whereHas('roles', function ($q) {
                $q->where('name', '!=', 'Student');
            })->orderBy('name')->get(['users.id', 'users.name']);

            return view('subjectclass.index')
                ->with('schoolclasses', $schoolclasses)
                ->with('subjectteacher', $subjectteacher)
                ->with('allStaff', $allStaff)
                ->with('pagetitle', $pagetitle);

        } catch (\Exception $e) {
            Log::error('SubjectClass Index Error', ['error' => $e->getMessage()]);
            return back()->with('danger', 'Error loading subject classes: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATATABLE
    // =========================================================================

    public function data(Request $request)
    {
        try {
            $query = Subjectclass::leftJoin('schoolclass', 'subjectclass.schoolclassid', '=', 'schoolclass.id')
                ->leftJoin('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
                ->leftJoin('subject', 'subject.id', '=', 'subjectteacher.subjectid')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'subjectteacher.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'subjectteacher.sessionid')
                ->leftJoin('users', 'users.id', '=', 'subjectteacher.staffid')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->select([
                    'subjectclass.id as id',
                    'schoolclass.id as schoolclassid',
                    'schoolclass.schoolclass as schoolclass',
                    'schoolarm.arm as arm',
                    'subjectteacher.id as subjectteacherid',
                    'subjectteacher.staffid as staffid',
                    'subjectteacher.subjectid as subjectid',
                    'subject.id as subject_id',
                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',
                    'users.name as teachername',
                    'users.avatar as picture',
                    'schoolterm.id as termid',
                    'schoolterm.term as termname',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as sessionname',
                    'subjectclass.updated_at',
                ]);

            return DataTables::of($query)
                ->addIndexColumn()

                ->filterColumn('teachername', function ($query, $keyword) {
                    $query->where('users.name', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('subjectname', function ($query, $keyword) {
                    $query->where('subject.subject', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('subjectcode', function ($query, $keyword) {
                    $query->where('subject.subject_code', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('schoolclass', function ($query, $keyword) {
                    $query->where('schoolclass.schoolclass', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('termname', function ($query, $keyword) {
                    $query->where('schoolterm.term', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('sessionname', function ($query, $keyword) {
                    $query->where('schoolsession.session', 'LIKE', "%{$keyword}%");
                })
                ->filterColumn('formatted_date', function ($query, $keyword) {
                    $query->whereRaw("DATE(subjectclass.updated_at) LIKE ?", ["%{$keyword}%"]);
                })

                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '">';
                })

                ->addColumn('teacher_info', function ($row) {
                    $name    = $this->cleanUtf8String($row->teachername ?? 'Unknown');
                    $initial = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
                    return '<div class="d-flex align-items-center gap-2">'
                        . '<div class="avatar-initials">' . e($initial) . '</div>'
                        . '<span class="fw-semibold text-dark">' . e($name) . '</span>'
                        . '</div>';
                })

                ->addColumn('subject_info', function ($row) {
                    return '<div>'
                        . '<span class="fw-semibold">' . e($this->cleanUtf8String($row->subjectname ?? '')) . '</span>'
                        . '<br><small class="text-muted">' . e($row->subjectcode ?? 'N/A') . '</small>'
                        . '</div>';
                })

                ->addColumn('class_info', function ($row) {
                    $arm = $row->arm ? ' (' . $row->arm . ')' : '';
                    return '<span class="sc-badge sc-badge-class">'
                        . e($this->cleanUtf8String($row->schoolclass ?? '')) . e($arm)
                        . '</span>';
                })

                ->addColumn('term_info', function ($row) {
                    if (empty($row->termname)) return '<span class="text-muted">—</span>';
                    $cls = match(true) {
                        str_contains($row->termname, 'First')  => 'sc-badge-term-first',
                        str_contains($row->termname, 'Second') => 'sc-badge-term-second',
                        str_contains($row->termname, 'Third')  => 'sc-badge-term-third',
                        default => 'sc-badge-term-other'
                    };
                    return '<span class="sc-badge ' . $cls . '">' . e($row->termname) . '</span>';
                })

                ->addColumn('session_info', function ($row) {
                    if (empty($row->sessionname)) return '<span class="text-muted">—</span>';
                    return '<span class="sc-badge sc-badge-session">' . e($row->sessionname) . '</span>';
                })

                ->addColumn('registration_count', function ($row) {
                    $c = SubjectRegistrationStatus::where('subjectclassid', $row->id)->count();
                    if ($c > 0) {
                        return '<span class="badge bg-info text-white">' . $c . ' student(s)</span>';
                    }
                    return '<span class="text-muted">—</span>';
                })

                ->addColumn('formatted_date', function ($row) {
                    if (!$row->updated_at) return '<span class="text-muted small">—</span>';
                    return '<small class="text-muted">'
                        . \Carbon\Carbon::parse($row->updated_at)->format('d M Y')
                        . '</small>';
                })

                ->addColumn('action', function ($row) {
                    $buttons = '<div class="d-flex gap-1">';

                    if (auth()->user() && auth()->user()->can('Update subject-class')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-secondary edit-sc-btn" title="Change Teacher" '
                            . 'data-id="%s" data-staffid="%s" data-teachername="%s" '
                            . 'data-subjectname="%s" data-subjectcode="%s" '
                            . 'data-termname="%s" data-sessionname="%s" data-classname="%s">'
                            . '<i class="ph-pencil"></i></button>',
                            $row->id,
                            $row->staffid,
                            e($this->cleanUtf8String($row->teachername ?? '')),
                            e($this->cleanUtf8String($row->subjectname ?? '')),
                            e($row->subjectcode ?? ''),
                            e($row->termname ?? ''),
                            e($row->sessionname ?? ''),
                            e(trim(($row->schoolclass ?? '') . ' ' . ($row->arm ?? '')))
                        );
                    }

                    if (auth()->user() && auth()->user()->can('Delete subject-class')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-danger delete-sc-btn" title="Delete" '
                            . 'data-id="%s" data-subject="%s" data-teacher="%s">'
                            . '<i class="ph-trash"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->subjectname ?? 'Unknown Subject')),
                            e($this->cleanUtf8String($row->teachername ?? 'Unknown Teacher'))
                        );
                    }

                    return $buttons . '</div>';
                })

                ->rawColumns(['checkbox', 'teacher_info', 'subject_info', 'class_info', 'term_info', 'session_info', 'registration_count', 'formatted_date', 'action'])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('SubjectClass DataTable error', [
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
                    'total'           => Subjectclass::count(),
                    'unique_teachers' => Subjectclass::distinct('subjectteacherid')->count('subjectteacherid'),
                    'unique_classes'  => Subjectclass::distinct('schoolclassid')->count('schoolclassid'),
                    'unique_subjects' => Subjectclass::distinct('subjectid')->count('subjectid'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'stats' => ['total' => 0, 'unique_teachers' => 0, 'unique_classes' => 0, 'unique_subjects' => 0],
            ]);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schoolclassid'      => 'required|exists:schoolclass,id',
            'subjectteacherid'   => 'required|array|min:1',
            'subjectteacherid.*' => 'exists:subjectteacher,id',
        ], [
            'schoolclassid.required'    => 'Please select a class.',
            'subjectteacherid.required' => 'Please select at least one subject teacher.',
            'subjectteacherid.*.exists' => 'One or more selected subject teachers do not exist.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $schoolClassId     = $request->input('schoolclassid');
            $subjectTeacherIds = $request->input('subjectteacherid', []);
            $createdRecords    = [];
            $subjectTeachers   = SubjectTeacher::whereIn('id', $subjectTeacherIds)->get();

            foreach ($subjectTeacherIds as $subjectTeacherId) {
                $subjectTeacher = $subjectTeachers->firstWhere('id', $subjectTeacherId);
                if (!$subjectTeacher) continue;

                $exists = Subjectclass::where('schoolclassid', $schoolClassId)
                    ->where('subjectteacherid', $subjectTeacherId)
                    ->exists();
                if ($exists) continue;

                $createdRecords[] = Subjectclass::create([
                    'schoolclassid'    => $schoolClassId,
                    'subjectteacherid' => $subjectTeacherId,
                    'subjectid'        => $subjectTeacher->subjectid,
                ]);
            }

            if (empty($createdRecords)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'All selected subject teachers are already assigned to this class.',
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($createdRecords) . ' Subject Class(es) added successfully.',
                'data'    => $createdRecords,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SubjectClass store error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // UPDATE — change teacher only
    // =========================================================================

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'new_staffid' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        DB::beginTransaction();
        try {
            $newStaffId   = $request->input('new_staffid');
            $subjectclass = Subjectclass::find($id);
            if (!$subjectclass) {
                return response()->json(['success' => false, 'message' => 'Subject Class not found.'], 404);
            }

            $currentST = SubjectTeacher::find($subjectclass->subjectteacherid);
            if (!$currentST) {
                return response()->json(['success' => false, 'message' => 'Linked Subject Teacher row is missing.'], 422);
            }

            $newST = SubjectTeacher::firstOrCreate(
                [
                    'staffid'   => $newStaffId,
                    'subjectid' => $currentST->subjectid,
                    'termid'    => $currentST->termid,
                    'sessionid' => $currentST->sessionid,
                ]
            );

            $subjectclass->subjectteacherid = $newST->id;
            $subjectclass->save();

            // Cascade to related records
            Broadsheets::where('subjectclass_id', $subjectclass->id)->update(['staff_id' => $newStaffId]);
            BroadsheetsMock::where('subjectclass_id', $subjectclass->id)->update(['staff_id' => $newStaffId]);
            SubjectRegistrationStatus::where('subjectclassid', $subjectclass->id)->update(['staffid' => $newStaffId]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Subject class teacher changed successfully.',
                'data'    => $subjectclass,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SubjectClass update error', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // ASSIGNMENTS
    // =========================================================================

    public function assignments($subjectClassId)
    {
        try {
            $sc = Subjectclass::find($subjectClassId);
            if (!$sc) {
                return response()->json(['success' => false, 'message' => 'Subject Class not found.'], 404);
            }
            return response()->json([
                'success' => true,
                'data'    => [
                    'schoolclassid'    => $sc->schoolclassid,
                    'subjectteacherid' => [$sc->subjectteacherid],
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch assignments.'], 500);
        }
    }

    public function assignmentsBySubjectTeacher($subjectTeacherId)
    {
        try {
            $assignments = Subjectclass::where('subjectteacherid', $subjectTeacherId)
                ->select('schoolclassid')
                ->get();
            return response()->json(['success' => true, 'data' => $assignments], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch assignments.'], 500);
        }
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            $subjectclass = Subjectclass::find($id);
            if (!$subjectclass) {
                return response()->json(['success' => false, 'message' => 'Subject Class not found.'], 404);
            }

            $registered = SubjectRegistrationStatus::where('subjectclassid', $id)->count();
            if ($registered > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "This subject class has {$registered} student(s) registered. Unregister them first.",
                ], 422);
            }

            $scoreRows = Broadsheets::where('subjectclass_id', $id)->count();
            if ($scoreRows > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subject class already has score records and cannot be deleted.',
                ], 422);
            }

            $subjectclass->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Subject Class deleted successfully.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SubjectClass destroy error', ['error' => $e->getMessage(), 'id' => $id]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function deletesubjectclass(Request $request)
    {
        return $this->destroy($request->subjectclassid);
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
                return response()->json(['success' => false, 'message' => 'No assignments selected.'], 400);
            }

            $blockedReg = SubjectRegistrationStatus::whereIn('subjectclassid', $ids)->count();
            if ($blockedReg > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Some selected subject classes have student registrations ({$blockedReg} total). Unregister them first.",
                ], 422);
            }

            $blockedScore = Broadsheets::whereIn('subjectclass_id', $ids)->count();
            if ($blockedScore > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected subject classes already have score records and cannot be deleted.',
                ], 422);
            }

            DB::beginTransaction();
            $deleted = Subjectclass::whereIn('id', $ids)->delete();
            DB::commit();

            return response()->json([
                'success'       => true,
                'message'       => $deleted . ' assignment(s) deleted successfully.',
                'deleted_count' => $deleted,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('SubjectClass bulk delete error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function cleanUtf8String($string)
    {
        if (empty($string)) return '';
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
    }
}
