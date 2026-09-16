<?php
// app/Http/Controllers/PromotionSettingController.php

namespace App\Http\Controllers;

use App\Models\Classcategory;
use App\Models\CompulsorySubjectClass;
use App\Models\PromotionSetting;
use App\Models\Schoolclass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Subject;
use App\Models\SubjectTeacher;
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
        $this->middleware('permission:View promotion');
        $this->middleware('permission:Update promotion')->except(['index', 'classPromotionData']);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    public function index(): View
    {
        $pagetitle = 'Promotion Settings';

        $settings = PromotionSetting::with(['schoolclass', 'session', 'term', 'template'])
            ->orderBy('priority')
            ->orderBy('schoolclass_id')
            ->get();

        $schoolclasses = DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name')
            ->orderBy('schoolclass.schoolclass')
            ->get();

        $sessions = Schoolsession::orderByDesc('session')->get();
        $terms    = Schoolterm::orderBy('term')->get();
        $templates = \App\Models\PromotionRuleTemplate::orderBy('name')->get(['id', 'name', 'grade_scale']);

        return view('promotions.settings', compact(
            'settings', 'schoolclasses', 'sessions', 'terms', 'templates', 'pagetitle'
        ));
    }

    // =========================================================================
    // CLASS PROMOTION DATA (AJAX helper for the modal)
    // =========================================================================

    public function classPromotionData(Request $request): JsonResponse
    {
        $classId   = $request->query('classid');
        $termId    = $request->query('termid');
        $sessionId = $request->query('sessionid');

        if (!$classId) {
            return response()->json(['success' => false, 'message' => 'Missing class id'], 400);
        }

        $schoolclass = Schoolclass::with('classcategory')->find($classId);
        if (!$schoolclass) {
            return response()->json(['success' => false, 'message' => 'Class not found'], 404);
        }

        $isSenior   = $schoolclass->classcategory ? (bool) $schoolclass->classcategory->is_senior : false;
        $passAvg    = $schoolclass->classcategory->promotion_pass_average ?? null;
        $gradeScale = $isSenior
            ? ['A1','B2','B3','C4','C5','C6','D7','E8','F9']
            : ['A','B','C','D','F'];

        // Total subjects for this class
        $totalSubjects = DB::table('subjectclass')
            ->join('subjectteacher', 'subjectteacher.id', '=', 'subjectclass.subjectteacherid')
            ->where('subjectclass.schoolclassid', $classId)
            ->distinct('subjectteacher.subjectid')
            ->count('subjectteacher.subjectid');

        // Compulsory subjects for this class/term/session
        $compulsoryQuery = CompulsorySubjectClass::where('schoolclassid', $classId)
            ->when($termId && $termId !== '', function ($q) use ($termId, $sessionId) {
                $q->where(function ($q2) use ($termId, $sessionId) {
                    $q2->where('termid', $termId)->where('sessionid', $sessionId);
                })->orWhere(function ($q2) use ($sessionId) {
                    $q2->whereNull('termid')->where('sessionid', $sessionId);
                })->orWhere(function ($q2) {
                    $q2->whereNull('termid')->whereNull('sessionid');
                });
            }, function ($q) use ($sessionId) {
                $q->where(function ($q2) use ($sessionId) {
                    $q2->where('sessionid', $sessionId)->orWhereNull('sessionid');
                });
            })
            ->with('subject')
            ->get();

        $compulsory = $compulsoryQuery->map(fn ($cs) => [
            'id'                => $cs->subjectId,
            'subject'           => $cs->subject?->subject ?? 'Unknown',
            'subject_code'      => $cs->subject?->subject_code ?? '',
            'default_min_grade' => $cs->min_grade ?? '',
        ]);

        return response()->json([
            'success'             => true,
            'is_senior'           => $isSenior,
            'grade_scale'         => $gradeScale,
            'pass_average'        => $passAvg,
            'total_subjects'      => $totalSubjects,
            'compulsory_count'    => $compulsory->count(),
            'other_count'         => max(0, $totalSubjects - $compulsory->count()),
            'compulsory_subjects' => $compulsory,
        ]);
    }

    // =========================================================================
    // STORE
    // =========================================================================

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'schoolclass_id'         => 'required|exists:schoolclass,id',
            'session_id'             => 'nullable|exists:schoolsession,id',
            'term_id'                => 'nullable|exists:schoolterm,id',
            'promoted_label'         => 'required|string|max:100',
            'trial_label'            => 'required|string|max:100',
            'see_principal_label'    => 'required|string|max:100',
            'repeat_label'           => 'required|string|max:100',
            'rule_logic'             => 'required|in:grade_count,average_only,both',
            'promotion_pass_average' => 'nullable|numeric|min:0|max:100',
            'promotion_rules'        => 'required|json',
            'is_active'              => 'nullable|boolean',
            'template_id'            => 'nullable|exists:promotion_rule_templates,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $rules = json_decode($request->promotion_rules, true);
            if (!is_array($rules)) {
                return response()->json(['success' => false, 'message' => 'Invalid rules JSON'], 422);
            }

            $setting = PromotionSetting::create([
                'schoolclass_id'         => $request->schoolclass_id,
                'session_id'             => $request->session_id ?: null,
                'term_id'                => $request->term_id    ?: null,
                'rule_set_name'          => 'Rule set ' . now()->format('Y-m-d H:i'),
                'priority'               => 999,
                'promoted_label'         => $request->promoted_label,
                'trial_label'            => $request->trial_label,
                'see_principal_label'    => $request->see_principal_label,
                'repeat_label'           => $request->repeat_label,
                'rule_logic'             => $request->rule_logic,
                'promotion_pass_average' => $request->promotion_pass_average === '' ? null : $request->promotion_pass_average,
                'promotion_rules'        => $rules,
                'is_active'              => filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
                'template_id'            => $request->template_id ?: null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Promotion settings saved successfully.',
                'data'    => $setting,
            ]);

        } catch (\Exception $e) {
            Log::error('PromotionSetting store failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    public function update(Request $request, $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'schoolclass_id'         => 'required|exists:schoolclass,id',
            'session_id'             => 'nullable|exists:schoolsession,id',
            'term_id'                => 'nullable|exists:schoolterm,id',
            'promoted_label'         => 'required|string|max:100',
            'trial_label'            => 'required|string|max:100',
            'see_principal_label'    => 'required|string|max:100',
            'repeat_label'           => 'required|string|max:100',
            'rule_logic'             => 'required|in:grade_count,average_only,both',
            'promotion_pass_average' => 'nullable|numeric|min:0|max:100',
            'promotion_rules'        => 'required|json',
            'is_active'              => 'nullable|boolean',
            'template_id'            => 'nullable|exists:promotion_rule_templates,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        try {
            $setting = PromotionSetting::findOrFail($id);
            $rules   = json_decode($request->promotion_rules, true);
            if (!is_array($rules)) {
                return response()->json(['success' => false, 'message' => 'Invalid rules JSON'], 422);
            }

            $setting->update([
                'schoolclass_id'         => $request->schoolclass_id,
                'session_id'             => $request->session_id ?: null,
                'term_id'                => $request->term_id    ?: null,
                'promoted_label'         => $request->promoted_label,
                'trial_label'            => $request->trial_label,
                'see_principal_label'    => $request->see_principal_label,
                'repeat_label'           => $request->repeat_label,
                'rule_logic'             => $request->rule_logic,
                'promotion_pass_average' => $request->promotion_pass_average === '' ? null : $request->promotion_pass_average,
                'promotion_rules'        => $rules,
                'is_active'              => filter_var($request->input('is_active', $setting->is_active), FILTER_VALIDATE_BOOLEAN),
                'template_id'            => $request->template_id ?: null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Promotion settings updated successfully.',
                'data'    => $setting,
            ]);

        } catch (\Exception $e) {
            Log::error('PromotionSetting update failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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

            return response()->json(['success' => true, 'message' => 'Promotion settings deleted successfully.']);

        } catch (\Exception $e) {
            Log::error('PromotionSetting destroy failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // TOGGLE ACTIVE
    // =========================================================================

    public function toggleActive(Request $request, $id): JsonResponse
    {
        try {
            $setting = PromotionSetting::findOrFail($id);
            $isActive = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
            $setting->is_active = $isActive;
            $setting->save();

            return response()->json([
                'success'   => true,
                'message'   => 'Status updated successfully.',
                'is_active' => $setting->is_active,
            ]);

        } catch (\Exception $e) {
            Log::error('PromotionSetting toggle active failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}