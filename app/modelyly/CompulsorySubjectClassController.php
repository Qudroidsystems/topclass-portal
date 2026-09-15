<?php

namespace App\Http\Controllers;

use App\Models\CompulsorySubjectClass;
use App\Models\Classcategory;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Subject;
use App\Models\Subjectclass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

class CompulsorySubjectClassController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View compulsory-subject|Create compulsory-subject|Update compulsory-subject|Delete compulsory-subject', ['only' => ['index', 'data', 'stats', 'subjectsByClass']]);
        $this->middleware('permission:Create compulsory-subject', ['only' => ['store']]);
        $this->middleware('permission:Update compulsory-subject', ['only' => ['update', 'updatePassAverage']]);
        $this->middleware('permission:Delete compulsory-subject', ['only' => ['destroy', 'bulkDestroy', 'deleteMultiple']]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(Request $request)
    {
        $pagetitle = "Compulsory Subject Class Management";

        try {
            // ── School classes with ARM NAME (not the FK id) ────────────────
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

            $terms    = Schoolterm::orderBy('term')->get();
            $sessions = Schoolsession::orderBy('session')->get();

            $classPassAverages = collect();
            if (Schema::hasTable('schoolclass_classcategory')) {
                $classPassAverages = DB::table('schoolclass_classcategory')
                    ->select('schoolclass_id as classid', 'promotion_pass_average')
                    ->get()
                    ->keyBy('classid');
            }

            return view('compulsorysubjectclass.index')
                ->with('schoolclasses', $schoolclasses)
                ->with('terms', $terms)
                ->with('sessions', $sessions)
                ->with('classPassAverages', $classPassAverages)
                ->with('pagetitle', $pagetitle);

        } catch (\Exception $e) {
            Log::error('CompulsorySubjectClass Index Error:', ['error' => $e->getMessage()]);
            return back()->with('danger', 'Error loading compulsory subjects: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // DATATABLE
    // =========================================================================

    public function data(Request $request)
    {
        try {
            $query = CompulsorySubjectClass::leftJoin('schoolclass', 'compulsory_subject_classes.schoolclassid', '=', 'schoolclass.id')
                ->leftJoin('subject', 'compulsory_subject_classes.subjectId', '=', 'subject.id')
                ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
                ->leftJoin('schoolterm', 'schoolterm.id', '=', 'compulsory_subject_classes.termid')
                ->leftJoin('schoolsession', 'schoolsession.id', '=', 'compulsory_subject_classes.sessionid')
                ->select([
                    'compulsory_subject_classes.id as id',
                    'schoolclass.id as schoolclassid',
                    'schoolclass.schoolclass as schoolclass',
                    'schoolarm.arm as arm_name',
                    'subject.id as subjectid',
                    'subject.subject as subjectname',
                    'subject.subject_code as subjectcode',
                    'schoolterm.id as termid',
                    'schoolterm.term as termname',
                    'schoolsession.id as sessionid',
                    'schoolsession.session as sessionname',
                    'compulsory_subject_classes.min_grade',
                    'compulsory_subject_classes.created_at',
                    'compulsory_subject_classes.updated_at',
                ]);

            return DataTables::of($query)
                ->addIndexColumn()

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
                    $query->whereRaw("DATE(compulsory_subject_classes.updated_at) LIKE ?", ["%{$keyword}%"]);
                })

                ->addColumn('checkbox', function ($row) {
                    return '<input type="checkbox" class="form-check-input row-checkbox" value="' . $row->id . '">';
                })

                ->addColumn('subject_info', function ($row) {
                    return '<div>'
                        . '<span class="fw-semibold text-dark">' . e($this->cleanUtf8String($row->subjectname ?? '')) . '</span>'
                        . '<small class="text-muted d-block">' . e($row->subjectcode ?? 'N/A') . '</small>'
                        . '</div>';
                })

                ->addColumn('class_info', function ($row) {
                    $class = $this->cleanUtf8String($row->schoolclass ?? '');
                    $arm   = $this->cleanUtf8String($row->arm_name ?? '');
                    return '<div>'
                        . '<span class="fw-semibold">' . e($class) . '</span>'
                        . ($arm ? '<small class="text-muted d-block">Arm: ' . e($arm) . '</small>' : '')
                        . '</div>';
                })

                ->addColumn('term_info', function ($row) {
                    if ($row->termname) {
                        return '<span class="cs-badge cs-badge-term">' . e($this->cleanUtf8String($row->termname)) . '</span>';
                    }
                    return '<span class="cs-badge cs-badge-all-terms">All Terms</span>';
                })

                ->addColumn('session_info', function ($row) {
                    if ($row->sessionname) {
                        return '<span class="cs-badge cs-badge-session">' . e($this->cleanUtf8String($row->sessionname)) . '</span>';
                    }
                    return '<span class="text-muted small">Any Session</span>';
                })

                ->addColumn('min_grade_info', function ($row) {
                    if ($row->min_grade) {
                        return '<span class="cs-badge cs-badge-grade">' . e($row->min_grade) . '</span>';
                    }
                    return '<span class="text-muted small">—</span>';
                })

                ->addColumn('pass_avg_info', function ($row) {
                    if (!Schema::hasTable('schoolclass_classcategory')) {
                        return '<span class="text-muted small">—</span>';
                    }
                    $passAvg = DB::table('schoolclass_classcategory')
                        ->where('schoolclass_id', $row->schoolclassid)
                        ->value('promotion_pass_average');
                    if ($passAvg !== null && $passAvg !== '') {
                        return '<span class="cs-badge cs-badge-pass-avg">' . number_format((float)$passAvg, 1) . '%</span>';
                    }
                    return '<span class="text-muted small">Not set</span>';
                })

                ->addColumn('formatted_date', function ($row) {
                    if (!$row->updated_at) return '<span class="text-muted small">—</span>';
                    return '<small class="text-muted">'
                        . \Carbon\Carbon::parse($row->updated_at)->format('d M Y')
                        . '</small>';
                })

                ->addColumn('action', function ($row) {
                    $buttons = '<div class="d-flex gap-1">';

                    if (auth()->user() && auth()->user()->can('Update compulsory-subject')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-secondary edit-cs-btn" title="Edit" '
                            . 'data-id="%s" data-subject-id="%s" data-class-id="%s" '
                            . 'data-term-id="%s" data-session-id="%s" data-min-grade="%s">'
                            . '<i class="ph-pencil"></i></button>',
                            $row->id,
                            $row->subjectid,
                            $row->schoolclassid,
                            $row->termid ?? '',
                            $row->sessionid ?? '',
                            $row->min_grade ?? ''
                        );
                    }

                    if (auth()->user() && auth()->user()->can('Delete compulsory-subject')) {
                        $buttons .= sprintf(
                            '<button class="btn btn-sm btn-outline-danger delete-cs-btn" title="Delete" '
                            . 'data-id="%s" data-name="%s" data-class="%s">'
                            . '<i class="ph-trash"></i></button>',
                            $row->id,
                            e($this->cleanUtf8String($row->subjectname ?? 'Unknown Subject')),
                            e($this->cleanUtf8String($row->schoolclass ?? 'Unknown Class'))
                        );
                    }

                    return $buttons . '</div>';
                })

                ->rawColumns(['checkbox', 'subject_info', 'class_info', 'term_info', 'session_info', 'min_grade_info', 'pass_avg_info', 'formatted_date', 'action'])
                ->make(true);

        } catch (\Exception $e) {
            Log::error('CompulsorySubjectClass DataTable error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
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
                    'total'              => CompulsorySubjectClass::count(),
                    'total_classes'      => Schoolclass::count(),
                    'total_sessions'     => Schoolsession::count(),
                    'classes_with_rules' => CompulsorySubjectClass::distinct('schoolclassid')->count('schoolclassid'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'stats' => ['total' => 0, 'total_classes' => 0, 'total_sessions' => 0, 'classes_with_rules' => 0],
            ]);
        }
    }

    // =========================================================================
    // SUBJECTS BY CLASS
    // =========================================================================

    public function subjectsByClass(Request $request)
    {
        $classId   = $request->query('classid');
        $termId    = $request->query('termid');
        $sessionId = $request->query('sessionid');

        if (!$classId) {
            return response()->json(['success' => false, 'message' => 'Class is required.'], 422);
        }

        $schoolclass = Schoolclass::find($classId);
        if (!$schoolclass) {
            return response()->json(['success' => false, 'message' => 'Class not found.'], 404);
        }

        $category = null;
        if (Schema::hasTable('schoolclass_classcategory')) {
            $pivotCategory = $schoolclass->classcategories()->first();
            if ($pivotCategory) $category = $pivotCategory;
        }
        if (!$category) {
            $category = $schoolclass->classcategory;
        }

        $gradeScale = [];
        if ($category) {
            $gradeScale = $category->is_senior
                ? ['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9']
                : ['A', 'B', 'C', 'D', 'F'];
        }

        $passAverage = null;
        if (Schema::hasTable('schoolclass_classcategory')) {
            $passAverage = DB::table('schoolclass_classcategory')
                ->where('schoolclass_id', $classId)
                ->value('promotion_pass_average');
        }

        $query = Subjectclass::with(['subject'])
            ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->join('users', 'users.id', '=', 'subjectteacher.staffid')
            ->where('subjectclass.schoolclassid', $classId)
            ->select([
                'subjectclass.id as id',
                'subjectclass.subjectid',
                'subjectteacher.staffid',
                'subjectteacher.termid',
                'subjectteacher.sessionid',
                'users.name as teacher_name',
            ]);

        if ($termId)    $query->where('subjectteacher.termid', $termId);
        if ($sessionId) $query->where('subjectteacher.sessionid', $sessionId);

        $subjectclasses = $query->get();

        $alreadyAssigned = CompulsorySubjectClass::where('schoolclassid', $classId)
            ->when($termId,    fn($q) => $q->where('termid', $termId))
            ->when($sessionId, fn($q) => $q->where('sessionid', $sessionId))
            ->get(['subjectId as subjectid', 'min_grade']);

        $assignedMap = $alreadyAssigned->keyBy('subjectid');

        $subjects = $subjectclasses->map(function ($sc) use ($assignedMap) {
            $subjectId = $sc->subjectid;
            $assigned  = $assignedMap->has($subjectId);
            $minGrade  = $assigned ? ($assignedMap[$subjectId]->min_grade ?? null) : null;

            return [
                'id'           => $subjectId,
                'subject'      => $sc->subject?->subject,
                'subject_code' => $sc->subject?->subject_code,
                'teacher'      => $sc->teacher_name ?? 'N/A',
                'assigned'     => $assigned,
                'min_grade'    => $minGrade,
            ];
        })->unique('id')->values();

        return response()->json([
            'success'      => true,
            'subjects'     => $subjects,
            'grade_scale'  => $gradeScale,
            'pass_average' => $passAverage,
            'category'     => $category
                ? ['name' => $category->category, 'is_senior' => $category->is_senior, 'id' => $category->id]
                : null,
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'schoolclassid' => 'required|exists:schoolclass,id',
            'subjectId'     => 'required|array|min:1',
            'subjectId.*'   => 'exists:subject,id',
            'termid'        => 'nullable|exists:schoolterm,id',
            'sessionid'     => 'nullable|exists:schoolsession,id',
            'min_grades'    => 'nullable|array',
            'min_grades.*'  => 'nullable|string|max:10',
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
            $schoolClassId = $request->input('schoolclassid');
            $subjectIds    = $request->input('subjectId', []);
            $termId        = $request->input('termid') ?: null;
            $sessionId     = $request->input('sessionid') ?: null;
            $minGrades     = $request->input('min_grades', []);

            $created = [];
            $skipped = [];

            foreach ($subjectIds as $subjectId) {
                $exists = CompulsorySubjectClass::where('schoolclassid', $schoolClassId)
                    ->where('subjectId', $subjectId)
                    ->where('termid', $termId)
                    ->where('sessionid', $sessionId)
                    ->exists();

                if ($exists) {
                    $skipped[] = $subjectId;
                    continue;
                }

                $created[] = CompulsorySubjectClass::create([
                    'schoolclassid' => $schoolClassId,
                    'subjectId'     => $subjectId,
                    'termid'        => $termId,
                    'sessionid'     => $sessionId,
                    'min_grade'     => $minGrades[$subjectId] ?? null,
                ]);
            }

            DB::commit();

            if (empty($created)) {
                return response()->json([
                    'success' => false,
                    'message' => 'All selected subjects are already assigned to this class for the selected term/session.',
                ], 422);
            }

            $msg = count($created) . ' compulsory subject(s) added successfully.';
            if (!empty($skipped)) $msg .= ' ' . count($skipped) . ' duplicate(s) skipped.';

            return response()->json([
                'success' => true,
                'message' => $msg,
                'data'    => $created,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating compulsory subject:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to create compulsory subject: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'schoolclassid' => 'required|exists:schoolclass,id',
            'subjectId'     => 'required|exists:subject,id',
            'termid'        => 'nullable|exists:schoolterm,id',
            'sessionid'     => 'nullable|exists:schoolsession,id',
            'min_grade'     => 'nullable|string|max:10',
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
            $schoolClassId = $request->input('schoolclassid');
            $subjectId     = $request->input('subjectId');
            $termId        = $request->input('termid') ?: null;
            $sessionId     = $request->input('sessionid') ?: null;
            $minGrade      = $request->input('min_grade') ?: null;

            $duplicate = CompulsorySubjectClass::where('schoolclassid', $schoolClassId)
                ->where('subjectId', $subjectId)
                ->where('termid', $termId)
                ->where('sessionid', $sessionId)
                ->where('id', '!=', $id)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'This subject is already assigned to this class for the selected term/session.',
                ], 422);
            }

            $record = CompulsorySubjectClass::find($id);
            if (!$record) {
                return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
            }

            $record->update([
                'schoolclassid' => $schoolClassId,
                'subjectId'     => $subjectId,
                'termid'        => $termId,
                'sessionid'     => $sessionId,
                'min_grade'     => $minGrade,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Updated successfully.',
                'data'    => $record,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating compulsory subject:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update compulsory subject: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE PASS AVERAGE
    // =========================================================================

    public function updatePassAverage(Request $request)
    {
        if (!Schema::hasTable('schoolclass_classcategory')) {
            return response()->json([
                'success' => false,
                'message' => 'Pivot table schoolclass_classcategory does not exist. Run migrations first.',
            ], 500);
        }

        $validator = Validator::make($request->all(), [
            'schoolclassid'          => 'required|exists:schoolclass,id',
            'promotion_pass_average' => 'nullable|numeric|min:0|max:100',
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
            $schoolClassId = $request->input('schoolclassid');
            $passAverage   = $request->input('promotion_pass_average');
            $passAverageValue = ($passAverage !== null && $passAverage !== '') ? (float) $passAverage : null;

            $pivotExists = DB::table('schoolclass_classcategory')
                ->where('schoolclass_id', $schoolClassId)
                ->exists();

            if (!$pivotExists) {
                $class = Schoolclass::find($schoolClassId);
                $className = strtoupper($class->schoolclass ?? '');
                $isJunior = preg_match('/^(JSS|J\.S\.S\.|JUNIOR)/', $className);

                $categoryName = $isJunior ? 'Junior Secondary School' : 'Senior Secondary School';
                $category = Classcategory::firstOrCreate(
                    ['category' => $categoryName],
                    ['is_senior' => !$isJunior]
                );

                DB::table('schoolclass_classcategory')->insert([
                    'schoolclass_id'         => $schoolClassId,
                    'classcategory_id'       => $category->id,
                    'promotion_pass_average' => $passAverageValue,
                    'created_at'             => now(),
                    'updated_at'             => now(),
                ]);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => "Class automatically linked to '{$categoryName}' category. Promotion pass average updated to " . ($passAverageValue ? number_format($passAverageValue, 1) . '%' : 'disabled'),
                    'saved_value' => $passAverageValue,
                ]);
            }

            DB::table('schoolclass_classcategory')
                ->where('schoolclass_id', $schoolClassId)
                ->update([
                    'promotion_pass_average' => $passAverageValue,
                    'updated_at'             => now(),
                ]);

            DB::commit();

            $display = ($passAverageValue !== null)
                ? number_format($passAverageValue, 1) . '%'
                : 'None (threshold disabled)';

            return response()->json([
                'success' => true,
                'message' => "Promotion pass average updated to {$display}.",
                'saved_value' => $passAverageValue,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating pass average:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update pass average: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function destroy($id)
    {
        try {
            $record = CompulsorySubjectClass::find($id);
            if (!$record) {
                return response()->json(['success' => false, 'message' => 'Record not found.'], 404);
            }
            $record->delete();

            return response()->json([
                'success' => true,
                'message' => 'Compulsory subject removed successfully.',
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error deleting compulsory subject:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete compulsory subject: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // BULK DESTROY
    // =========================================================================

    public function deleteMultiple(Request $request)
    {
        try {
            $ids = $request->input('ids', []);
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                $ids = is_array($decoded) ? $decoded : array_map('trim', explode(',', $ids));
            }
            if (!is_array($ids)) $ids = [];
            $ids = array_values(array_filter($ids));

            if (empty($ids)) {
                return response()->json(['success' => false, 'message' => 'No records selected.'], 400);
            }

            $existingIds = CompulsorySubjectClass::whereIn('id', $ids)->pluck('id')->toArray();
            $invalidIds  = array_diff($ids, $existingIds);
            if (!empty($invalidIds)) {
                return response()->json(['success' => false, 'message' => 'Some selected records do not exist.'], 400);
            }

            DB::beginTransaction();
            $deleted = CompulsorySubjectClass::whereIn('id', $ids)->delete();
            DB::commit();

            return response()->json([
                'success'       => true,
                'message'       => $deleted . ' record(s) deleted successfully.',
                'deleted_count' => $deleted,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk delete failed:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error deleting records: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function bulkDestroy(Request $request)
    {
        return $this->deleteMultiple($request);
    }

    private function cleanUtf8String($string)
    {
        if (empty($string)) return '';
        $string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $string);
    }
}
