<?php

namespace App\Http\Controllers;

use App\Models\CompulsorySubjectClass;
use App\Models\PromotionRuleTemplate;
use App\Models\PromotionSetting;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Subjectclass;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PromotionSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View promotion',   ['only' => [
            'index', 'getClassPromotionData', 'subjectsByClass', 'compulsoryByClass',
        ]]);
        $this->middleware('permission:Update promotion', ['only' => [
            'store', 'update', 'destroy', 'toggleActive',
        ]]);
    }

    // =========================================================================
    // INDEX — renders the settings page
    // =========================================================================

    public function index(): View
    {
        $pagetitle = 'Promotion Settings';

        $settings = PromotionSetting::with(['schoolclass', 'session', 'term', 'template'])
            ->orderBy('schoolclass_id')
            ->orderBy('priority')
            ->get();

        $schoolclasses = DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name')
            ->orderBy('schoolclass.schoolclass')
            ->get();

        $sessions  = Schoolsession::orderByDesc('session')->get();
        $terms     = Schoolterm::orderBy('term')->get();
        $templates = PromotionRuleTemplate::where('is_active', true)->orderBy('name')->get();

        return view('promotions.settings', compact(
            'settings', 'schoolclasses', 'sessions', 'terms', 'templates', 'pagetitle'
        ));
    }

    // =========================================================================
    // CLASS PROMOTION DATA — AJAX endpoint for the modal
    // =========================================================================

    public function getClassPromotionData(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'classid'   => 'required|integer|exists:schoolclass,id',
                'termid'    => 'nullable|integer',
                'sessionid' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid parameters: ' . $validator->errors()->first(),
                ], 422);
            }

            $classId   = (int) $request->query('classid');
            $termId    = $request->query('termid')    ? (int) $request->query('termid')    : null;
            $sessionId = $request->query('sessionid') ? (int) $request->query('sessionid') : null;

            $class = Schoolclass::find($classId);
            if (!$class) {
                return response()->json(['success' => false, 'message' => 'Class not found.'], 404);
            }

            // ── Class category (senior/junior + pass average) ───────────────
            $category = DB::table('schoolclass_classcategory')
                ->join('classcategories', 'classcategories.id', '=', 'schoolclass_classcategory.classcategory_id')
                ->where('schoolclass_classcategory.schoolclass_id', $classId)
                ->select(
                    'classcategories.id',
                    'classcategories.category',
                    'classcategories.is_senior',
                    'classcategories.promotion_pass_average'
                )
                ->first();

            $isSenior = $category && !empty($category->is_senior);

            $gradeScale = $isSenior
                ? ['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9']
                : ['A', 'B', 'C', 'D', 'F'];

            // ── Compulsory subjects (with term/session scoping) ─────────────
            $compulsoryQuery = CompulsorySubjectClass::where('schoolclassid', $classId)
                ->where(function ($q) use ($termId, $sessionId) {
                    $q->where(function ($q2) use ($termId, $sessionId) {
                        if ($termId && $sessionId) {
                            $q2->where('termid', $termId)->where('sessionid', $sessionId);
                        } elseif ($sessionId) {
                            $q2->whereNull('termid')->where('sessionid', $sessionId);
                        } else {
                            $q2->whereNull('termid')->whereNull('sessionid');
                        }
                    })->orWhere(function ($q2) {
                        // Global fallback (both null)
                        $q2->whereNull('termid')->whereNull('sessionid');
                    });
                })
                ->with('subject')
                ->get();

            $compulsorySubjects = $compulsoryQuery->map(function ($cs) {
                return [
                    'id'                => $cs->subjectId,
                    'subject'           => $cs->subject?->subject ?? 'Unknown',
                    'subject_code'      => $cs->subject?->subject_code ?? '',
                    'default_min_grade' => $cs->min_grade ?? '',
                ];
            })->values()->toArray();

            $compulsoryIds = array_column($compulsorySubjects, 'id');

            // ── All subjects registered for this class ──────────────────────
            $allSubjectsQuery = Subjectclass::where('subjectclass.schoolclassid', $classId);

            if ($sessionId) {
                $allSubjectsQuery->where(function ($q) use ($sessionId) {
                    $q->where('subjectclass.sessionid', $sessionId)
                      ->orWhereNull('subjectclass.sessionid');
                });
            }

            $allSubjects = $allSubjectsQuery
                ->join('subject', 'subject.id', '=', 'subjectclass.subjectid')
                ->select('subject.id', 'subject.subject', 'subject.subject_code')
                ->distinct()
                ->get();

            $totalSubjects   = $allSubjects->count();
            $compulsoryCount = count($compulsoryIds);
            $otherCount      = max(0, $totalSubjects - $compulsoryCount);

            $passAverage = null;
            if ($category && $category->promotion_pass_average !== null) {
                $passAverage = (float) $category->promotion_pass_average;
            }

            return response()->json([
                'success'             => true,
                'is_senior'           => $isSenior,
                'grade_scale'         => $gradeScale,
                'total_subjects'      => $totalSubjects,
                'compulsory_count'    => $compulsoryCount,
                'other_count'         => $otherCount,
                'pass_average'        => $passAverage,
                'compulsory_subjects' => $compulsorySubjects,
                'all_subjects'        => $allSubjects->map(fn ($s) => [
                    'id'           => $s->id,
                    'subject'      => $s->subject,
                    'subject_code' => $s->subject_code,
                ])->values()->toArray(),
            ]);

        } catch (Exception $e) {
            Log::error('getClassPromotionData failed', [
                'classid'   => $request->query('classid'),
                'termid'    => $request->query('termid'),
                'sessionid' => $request->query('sessionid'),
                'error'     => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'schoolclass_id'         => 'required|integer|exists:schoolclass,id',
            'session_id'             => 'nullable|integer',
            'term_id'                => 'nullable|integer',
            'promoted_label'         => 'nullable|string|max:255',
            'trial_label'            => 'nullable|string|max:255',
            'see_principal_label'    => 'nullable|string|max:255',
            'repeat_label'           => 'nullable|string|max:255',
            'rule_logic'             => 'required|in:grade_count,average_only,both',
            'promotion_pass_average' => 'nullable|numeric|min:0|max:100',
            'promotion_rules'        => 'required|json',
            'is_active'              => 'nullable',
            'template_id'            => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $rules = json_decode($request->input('promotion_rules'), true);
            if (!is_array($rules)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid rules format.',
                ], 422);
            }

            $sessionId = $request->input('session_id') ?: null;
            $termId    = $request->input('term_id')    ?: null;

            $setting = PromotionSetting::updateOrCreate(
                [
                    'schoolclass_id' => $request->input('schoolclass_id'),
                    'session_id'     => $sessionId,
                    'term_id'        => $termId,
                ],
                [
                    'rule_set_name'          => 'Custom Rules',
                    'priority'               => 999,
                    'promoted_label'         => $request->input('promoted_label')      ?: 'PROMOTED',
                    'trial_label'            => $request->input('trial_label')         ?: 'PROMOTED ON TRIAL',
                    'see_principal_label'    => $request->input('see_principal_label') ?: 'PARENTS TO SEE PRINCIPAL',
                    'repeat_label'           => $request->input('repeat_label')        ?: 'ADVISED TO REPEAT/PARENTS TO SEE PRINCIPAL',
                    'rule_logic'             => $request->input('rule_logic', 'grade_count'),
                    'promotion_pass_average' => $request->input('promotion_pass_average') !== '' && $request->input('promotion_pass_average') !== null
                                                    ? (float) $request->input('promotion_pass_average')
                                                    : null,
                    'promotion_rules'        => $rules,
                    'is_active'              => filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
                    'is_default'             => false,
                    'template_id'            => $request->input('template_id') ?: null,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Promotion settings saved successfully.',
                'data'    => $setting,
            ]);

        } catch (Exception $e) {
            Log::error('PromotionSetting store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error saving settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'schoolclass_id'         => 'required|integer|exists:schoolclass,id',
            'session_id'             => 'nullable|integer',
            'term_id'                => 'nullable|integer',
            'rule_logic'             => 'required|in:grade_count,average_only,both',
            'promotion_pass_average' => 'nullable|numeric|min:0|max:100',
            'promotion_rules'        => 'required|json',
            'is_active'              => 'nullable',
            'template_id'            => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $validator->errors()->first(),
                'errors'  => $validator->errors(),
            ], 422);
        }

        try {
            $setting = PromotionSetting::findOrFail($id);

            $rules = json_decode($request->input('promotion_rules'), true);
            if (!is_array($rules)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid rules format.',
                ], 422);
            }

            $sessionId = $request->input('session_id') ?: null;
            $termId    = $request->input('term_id')    ?: null;

            $setting->update([
                'schoolclass_id'         => $request->input('schoolclass_id'),
                'session_id'             => $sessionId,
                'term_id'                => $termId,
                'promoted_label'         => $request->input('promoted_label')      ?: $setting->promoted_label,
                'trial_label'            => $request->input('trial_label')         ?: $setting->trial_label,
                'see_principal_label'    => $request->input('see_principal_label') ?: $setting->see_principal_label,
                'repeat_label'           => $request->input('repeat_label')        ?: $setting->repeat_label,
                'rule_logic'             => $request->input('rule_logic', $setting->rule_logic),
                'promotion_pass_average' => $request->input('promotion_pass_average') !== '' && $request->input('promotion_pass_average') !== null
                                                ? (float) $request->input('promotion_pass_average')
                                                : null,
                'promotion_rules'        => $rules,
                'is_active'              => filter_var($request->input('is_active', $setting->is_active), FILTER_VALIDATE_BOOLEAN),
                'template_id'            => $request->input('template_id') ?: null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Promotion settings updated successfully.',
                'data'    => $setting->fresh(),
            ]);

        } catch (Exception $e) {
            Log::error('PromotionSetting update failed', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    public function destroy($id): JsonResponse
    {
        try {
            $setting = PromotionSetting::findOrFail($id);
            $setting->delete();

            return response()->json([
                'success' => true,
                'message' => 'Promotion setting deleted successfully.',
            ]);

        } catch (Exception $e) {
            Log::error('PromotionSetting destroy failed', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error deleting setting: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // TOGGLE ACTIVE
    // =========================================================================

    public function toggleActive(Request $request, $id): JsonResponse
    {
        try {
            $setting = PromotionSetting::findOrFail($id);
            $setting->is_active = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
            $setting->save();

            return response()->json([
                'success'   => true,
                'message'   => 'Status updated successfully.',
                'is_active' => $setting->is_active,
            ]);

        } catch (Exception $e) {
            Log::error('PromotionSetting toggleActive failed', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }

    // =========================================================================
    // SUBJECTS BY CLASS
    // =========================================================================

    public function subjectsByClass(Request $request): JsonResponse
    {
        try {
            $classId = (int) $request->query('classid');
            if (!$classId) {
                return response()->json(['success' => false, 'message' => 'classid is required.'], 422);
            }

            $subjects = Subjectclass::where('subjectclass.schoolclassid', $classId)
                ->join('subject', 'subject.id', '=', 'subjectclass.subjectid')
                ->select('subject.id', 'subject.subject', 'subject.subject_code')
                ->distinct()
                ->orderBy('subject.subject')
                ->get();

            return response()->json(['success' => true, 'data' => $subjects]);

        } catch (Exception $e) {
            Log::error('subjectsByClass failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // COMPULSORY BY CLASS
    // =========================================================================

    public function compulsoryByClass(Request $request): JsonResponse
    {
        try {
            $classId = (int) $request->query('classid');
            if (!$classId) {
                return response()->json(['success' => false, 'message' => 'classid is required.'], 422);
            }

            $compulsory = CompulsorySubjectClass::where('schoolclassid', $classId)
                ->with('subject')
                ->get()
                ->map(fn ($cs) => [
                    'id'           => $cs->subjectId,
                    'subject'      => $cs->subject?->subject ?? 'Unknown',
                    'subject_code' => $cs->subject?->subject_code ?? '',
                    'min_grade'    => $cs->min_grade,
                ]);

            return response()->json(['success' => true, 'data' => $compulsory]);

        } catch (Exception $e) {
            Log::error('compulsoryByClass failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}