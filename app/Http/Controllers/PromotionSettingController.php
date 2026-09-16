<?php

namespace App\Http\Controllers;

use App\Models\PromotionSetting;
use App\Models\PromotionRuleTemplate;
use App\Models\CompulsorySubjectClass;
use App\Models\Schoolsession;
use App\Models\Schoolterm;
use App\Models\Schoolclass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PromotionSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:View promotion|Update promotion');
    }

    // ══════════════════════════════════════════════════════════════════════
    // INDEX
    // ══════════════════════════════════════════════════════════════════════

    public function index()
    {
        $pagetitle = 'Promotion Settings';

        $settings = PromotionSetting::with(['schoolclass.arm', 'session', 'term'])
            ->orderBy('schoolclass_id')
            ->orderBy('priority', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $schoolclasses = DB::table('schoolclass')
            ->leftJoin('schoolarm', 'schoolarm.id', '=', 'schoolclass.arm')
            ->select('schoolclass.id', 'schoolclass.schoolclass', 'schoolarm.arm as arm_name')
            ->get();

        $sessions = Schoolsession::orderBy('session', 'desc')->get();
        $terms    = Schoolterm::orderBy('term')->get();

        $templates = [];
        if (Schema::hasTable('promotion_rule_templates')) {
            $templates = PromotionRuleTemplate::select('id', 'name', 'grade_scale')
                ->orderBy('name')
                ->get();
        }

        return view('promotions.settings', compact(
            'settings', 'schoolclasses', 'sessions', 'terms', 'templates', 'pagetitle'
        ));
    }

    // ══════════════════════════════════════════════════════════════════════
    // CLASS PROMOTION DATA (AJAX — used by modal)
    // ══════════════════════════════════════════════════════════════════════

    public function getClassPromotionData(Request $request)
    {
        try {
            $classId   = $request->query('classid');
            $termId    = $request->query('termid');
            $sessionId = $request->query('sessionid');

            if (!$classId) {
                return response()->json(['success' => false, 'message' => 'Class required'], 422);
            }

            // Class category (senior/junior + pass average)
            $classCategory = $this->getClassCategory($classId);
            $isSenior = false;

            if ($classCategory) {
                $isSenior = (bool) $classCategory->is_senior;
            } else {
                // Fallback: infer from class name
                $class = Schoolclass::find($classId);
                $name  = strtoupper(trim($class->schoolclass ?? ''));
                $isSenior = str_contains($name, 'SSS')
                         || str_contains($name, 'S.S.S')
                         || str_contains($name, 'SENIOR');
            }

            $gradeScale = $isSenior
                ? ['A1', 'B2', 'B3', 'C4', 'C5', 'C6', 'D7', 'E8', 'F9']
                : ['A', 'B', 'C', 'D', 'F'];

            $passAverage = $classCategory ? $classCategory->promotion_pass_average : null;

            // ── All subjects for this class ────────────────────────────────
            // subjectclass has NO sessionid column; term/session scoping
            // lives on subjectteacher (st.termid, st.sessionid).
            $subjectParams = [$classId];
            $subjectSql    = "SELECT DISTINCT s.id, s.subject, s.subject_code
                              FROM subjectclass sc
                              INNER JOIN subjectteacher st ON st.id = sc.subjectteacherid
                              INNER JOIN subject s ON s.id = sc.subjectid
                              WHERE sc.schoolclassid = ?";

            if ($termId && $termId !== '' && $termId !== 'null') {
                $subjectSql .= " AND st.termid = ?";
                $subjectParams[] = $termId;
            }
            if ($sessionId && $sessionId !== '' && $sessionId !== 'null') {
                $subjectSql .= " AND st.sessionid = ?";
                $subjectParams[] = $sessionId;
            }

            $allSubjects = DB::select($subjectSql, $subjectParams);

            $compulsorySubjects = $this->getCompulsorySubjects($classId, $termId, $sessionId);
            $compIds = array_column($compulsorySubjects, 'id');
            $otherSubjects = array_filter(
                $allSubjects,
                fn ($s) => !in_array((string) $s->id, array_map('strval', $compIds))
            );

            $recommendedGrouping = $isSenior ? 'exact' : 'grouped';

            return response()->json([
                'success'              => true,
                'pass_average'         => $passAverage,
                'grade_scale'          => $gradeScale,
                'is_senior'            => $isSenior,
                'recommended_grouping' => $recommendedGrouping,
                'compulsory_subjects'  => $compulsorySubjects,
                'compulsory_count'     => count($compulsorySubjects),
                'other_count'          => count($otherSubjects),
                'total_subjects'       => count($allSubjects),
            ]);

        } catch (\Exception $e) {
            Log::error('Get Class Promotion Data Error: ' . $e->getMessage(), [
                'class_id'   => $request->query('classid'),
                'term_id'    => $request->query('termid'),
                'session_id' => $request->query('sessionid'),
                'file'       => $e->getFile(),
                'line'       => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // STORE
    // ══════════════════════════════════════════════════════════════════════

    public function store(Request $request)
    {
        Log::info('STORE PROMOTION SETTINGS', [
            'schoolclass_id'         => $request->schoolclass_id,
            'session_id'             => $request->session_id,
            'term_id'                => $request->term_id,
            'rule_logic'             => $request->rule_logic,
            'promotion_pass_average' => $request->promotion_pass_average,
        ]);

        $v = $this->validateRequest($request);
        if ($v->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $v->errors(),
                'message' => 'Validation failed: ' . $v->errors()->first(),
            ], 422);
        }

        try {
            [$sessionId, $termId] = $this->cleanIds($request);

            $classCategory = $this->getClassCategory($request->schoolclass_id);
            $isSenior = $classCategory ? (bool) $classCategory->is_senior : false;

            $rules = $this->parseRules($request, $isSenior);
            if ($rules instanceof \Illuminate\Http\JsonResponse) return $rules;

            if (empty($rules)) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one promotion rule is required.',
                ], 422);
            }

            $ruleLogic = $request->rule_logic ?? 'grade_count';

            $averageOnlyWarning = null;
            if ($ruleLogic === 'average_only' && $this->rulesHaveSubstantiveConditions($rules)) {
                $averageOnlyWarning = 'Evaluation Mode is "Minimum Average Only" — the rules below will NOT be evaluated. Only the Global Minimum Average decides the outcome.';
            }

            $isActive  = filter_var($request->input('is_active', true), FILTER_VALIDATE_BOOLEAN);
            $isDefault = filter_var($request->input('is_default', false), FILTER_VALIDATE_BOOLEAN);

            $ruleSetName = $request->rule_set_name;
            if (empty($ruleSetName)) {
                $existingCount = PromotionSetting::where('schoolclass_id', $request->schoolclass_id)
                    ->where('session_id', $sessionId)
                    ->where('term_id', $termId)
                    ->count();
                $ruleSetName = "Rule Set " . ($existingCount + 1);
            }

            $maxPriority = PromotionSetting::where('schoolclass_id', $request->schoolclass_id)
                ->where('session_id', $sessionId)
                ->where('term_id', $termId)
                ->max('priority');
            $priority = ($maxPriority !== null) ? $maxPriority + 1 : 1;

            if ($isDefault) {
                PromotionSetting::where('schoolclass_id', $request->schoolclass_id)
                    ->where('session_id', $sessionId)
                    ->where('term_id', $termId)
                    ->update(['is_default' => false]);
            }

            // Preserve 0 as a valid average
            $avgValue = null;
            if ($request->has('promotion_pass_average')
                && $request->promotion_pass_average !== ''
                && $request->promotion_pass_average !== null) {
                $avgValue = (float) $request->promotion_pass_average;
            }

            $setting = PromotionSetting::create([
                'schoolclass_id'         => $request->schoolclass_id,
                'session_id'             => $sessionId,
                'term_id'                => $termId,
                'rule_set_name'          => $ruleSetName,
                'priority'               => $priority,
                'promoted_label'         => $request->promoted_label      ?? 'Promoted',
                'trial_label'            => $request->trial_label         ?? 'Promoted on Trial',
                'see_principal_label'    => $request->see_principal_label ?? 'Advised to See Principal',
                'repeat_label'           => $request->repeat_label        ?? 'Advice to Repeat',
                'rule_logic'             => $ruleLogic,
                'promotion_pass_average' => $avgValue,
                'is_active'              => $isActive,
                'is_default'             => $isDefault,
                'template_id'            => $request->template_id ?: null,
            ]);

            $setting->promotion_rules = $rules;
            $setting->save();

            $response = [
                'success' => true,
                'message' => "Rule set '{$ruleSetName}' created with " . count($rules) . ' rules.',
                'data'    => $setting,
            ];
            if ($averageOnlyWarning) $response['warning'] = $averageOnlyWarning;

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Store Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error saving settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // UPDATE
    // ══════════════════════════════════════════════════════════════════════

    public function update(Request $request, $id)
    {
        Log::info('UPDATE PROMOTION SETTINGS', [
            'id'                     => $id,
            'schoolclass_id'         => $request->schoolclass_id,
            'session_id'             => $request->session_id,
            'term_id'                => $request->term_id,
            'rule_logic'             => $request->rule_logic,
            'promotion_pass_average' => $request->promotion_pass_average,
        ]);

        $v = $this->validateRequest($request);
        if ($v->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $v->errors(),
                'message' => 'Validation failed: ' . $v->errors()->first(),
            ], 422);
        }

        try {
            $setting = PromotionSetting::findOrFail($id);
            [$sessionId, $termId] = $this->cleanIds($request);

            $classCategory = $this->getClassCategory($request->schoolclass_id ?? $setting->schoolclass_id);
            $isSenior = $classCategory ? (bool) $classCategory->is_senior : false;

            $rules = $this->parseRules($request, $isSenior);
            if ($rules instanceof \Illuminate\Http\JsonResponse) return $rules;

            if (empty($rules)) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one promotion rule is required.',
                ], 422);
            }

            $ruleLogic = $request->rule_logic ?? 'grade_count';

            $averageOnlyWarning = null;
            if ($ruleLogic === 'average_only' && $this->rulesHaveSubstantiveConditions($rules)) {
                $averageOnlyWarning = 'Evaluation Mode is "Minimum Average Only" — the rules below will NOT be evaluated. Only the Global Minimum Average decides the outcome.';
            }

            $isActive  = filter_var($request->input('is_active', $setting->is_active), FILTER_VALIDATE_BOOLEAN);
            $isDefault = filter_var($request->input('is_default', $setting->is_default), FILTER_VALIDATE_BOOLEAN);

            if ($isDefault && !$setting->is_default) {
                PromotionSetting::where('schoolclass_id', $setting->schoolclass_id)
                    ->where('session_id', $sessionId)
                    ->where('term_id', $termId)
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }

            // Preserve existing average, or update — 0 is valid
            $avgValue = $setting->promotion_pass_average;
            if ($request->has('promotion_pass_average')) {
                if ($request->promotion_pass_average !== '' && $request->promotion_pass_average !== null) {
                    $avgValue = (float) $request->promotion_pass_average;
                } else {
                    $avgValue = null;
                }
            }

            $setting->update([
                'schoolclass_id'         => $request->schoolclass_id ?? $setting->schoolclass_id,
                'session_id'             => $sessionId,
                'term_id'                => $termId,
                'rule_set_name'          => $request->rule_set_name ?? $setting->rule_set_name,
                'promoted_label'         => $request->promoted_label      ?? 'Promoted',
                'trial_label'            => $request->trial_label         ?? 'Promoted on Trial',
                'see_principal_label'    => $request->see_principal_label ?? 'Advised to See Principal',
                'repeat_label'           => $request->repeat_label        ?? 'Advice to Repeat',
                'rule_logic'             => $ruleLogic,
                'promotion_pass_average' => $avgValue,
                'is_active'              => $isActive,
                'is_default'             => $isDefault,
                'template_id'            => $request->template_id ?: null,
            ]);

            $setting->promotion_rules = $rules;
            $setting->save();

            $response = [
                'success' => true,
                'message' => "Rule set '{$setting->rule_set_name}' updated with " . count($rules) . ' rules.',
                'data'    => $setting,
            ];
            if ($averageOnlyWarning) $response['warning'] = $averageOnlyWarning;

            return response()->json($response);

        } catch (\Exception $e) {
            Log::error('Update Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'Error updating settings: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // DESTROY
    // ══════════════════════════════════════════════════════════════════════

    public function destroy($id)
    {
        try {
            $setting = PromotionSetting::find($id);
            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promotion setting not found.',
                ], 404);
            }

            $ruleSetName = $setting->rule_set_name;
            $setting->delete();

            return response()->json([
                'success' => true,
                'message' => "Rule set '{$ruleSetName}' deleted successfully.",
            ]);
        } catch (\Exception $e) {
            Log::error('Delete Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // TOGGLE ACTIVE
    // ══════════════════════════════════════════════════════════════════════

    public function toggleActive(Request $request, $id)
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
        } catch (\Exception $e) {
            Log::error('Toggle Active Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update status: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // AUXILIARY API
    // ══════════════════════════════════════════════════════════════════════

    public function subjectsByClass(Request $request)
    {
        try {
            $classId   = $request->query('classid');
            $termId    = $request->query('termid');
            $sessionId = $request->query('sessionid');

            if (!$classId) {
                return response()->json(['success' => false, 'message' => 'Class required'], 422);
            }

            $sql = "SELECT DISTINCT s.id, s.subject, s.subject_code
                    FROM subjectclass sc
                    INNER JOIN subjectteacher st ON st.id = sc.subjectteacherid
                    INNER JOIN subject s ON s.id = sc.subjectid
                    WHERE sc.schoolclassid = ?";
            $params = [$classId];

            if ($termId && $termId !== 'null' && $termId !== '') {
                $sql .= " AND st.termid = ?";
                $params[] = $termId;
            }
            if ($sessionId && $sessionId !== 'null' && $sessionId !== '') {
                $sql .= " AND st.sessionid = ?";
                $params[] = $sessionId;
            }

            $results = DB::select($sql, $params);

            $subjects = array_map(fn ($row) => [
                'id'           => (string) $row->id,
                'subject'      => $row->subject,
                'subject_code' => $row->subject_code,
            ], $results);

            return response()->json([
                'success'  => true,
                'subjects' => $subjects,
                'total'    => count($subjects),
            ]);

        } catch (\Exception $e) {
            Log::error('Subjects by Class Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading subjects: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function compulsoryByClass(Request $request)
    {
        try {
            $classId   = $request->query('classid');
            $termId    = $request->query('termid');
            $sessionId = $request->query('sessionid');

            if (!$classId) {
                return response()->json(['success' => false, 'message' => 'Class required'], 422);
            }

            $compulsorySubjects = $this->getCompulsorySubjects($classId, $termId, $sessionId);

            return response()->json([
                'success'  => true,
                'subjects' => $compulsorySubjects,
                'total'    => count($compulsorySubjects),
            ]);

        } catch (\Exception $e) {
            Log::error('Compulsory Subjects Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error loading compulsory subjects: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════════

    private function rulesHaveSubstantiveConditions(array $rules): bool
    {
        foreach ($rules as $rule) {
            $hasCompSubjGrades = !empty(array_filter(
                $rule['compulsory_section']['subjects'] ?? [],
                fn ($s) => !empty($s['min_grade'])
            ));
            $hasCompConds  = !empty($rule['compulsory_section']['count_conditions'] ?? []);
            $hasOtherConds = !empty($rule['other_section']['count_conditions'] ?? []);
            $hasAvgCond    = !empty($rule['average_condition']['enabled'] ?? false);

            if ($hasCompSubjGrades || $hasCompConds || $hasOtherConds || $hasAvgCond) {
                return true;
            }
        }
        return false;
    }

    private function getClassCategory($classId): ?object
    {
        return DB::table('schoolclass_classcategory')
            ->join('classcategories', 'classcategories.id', '=', 'schoolclass_classcategory.classcategory_id')
            ->where('schoolclass_classcategory.schoolclass_id', $classId)
            ->select(
                'classcategories.id',
                'classcategories.category',
                'classcategories.is_senior',
                'classcategories.promotion_pass_average'
            )
            ->first();
    }

    private function validateRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'schoolclass_id'         => 'required|exists:schoolclass,id',
            'session_id'             => 'nullable|exists:schoolsession,id',
            'term_id'                => 'nullable|exists:schoolterm,id',
            'rule_set_name'          => 'nullable|string|max:255',
            'promoted_label'         => 'nullable|string|max:100',
            'trial_label'            => 'nullable|string|max:100',
            'see_principal_label'    => 'nullable|string|max:100',
            'repeat_label'           => 'nullable|string|max:100',
            'rule_logic'             => 'nullable|in:grade_count,average_only,both,subject_only',
            'promotion_pass_average' => 'nullable|numeric|min:0|max:100',
            'is_active'              => 'nullable|boolean',
            'is_default'             => 'nullable|boolean',
            'template_id'            => 'nullable|exists:promotion_rule_templates,id',
            'promotion_rules'        => 'nullable|json',
        ]);
    }

    private function cleanIds(Request $request): array
    {
        $s = $request->session_id ?: null;
        $t = $request->term_id    ?: null;
        if (in_array($s, ['null', ''])) $s = null;
        if (in_array($t, ['null', ''])) $t = null;
        return [$s, $t];
    }

    private function parseRules(Request $request, bool $isSenior = false)
    {
        if (!$request->filled('promotion_rules')) return [];

        $rules = json_decode($request->promotion_rules, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON in promotion rules: ' . json_last_error_msg(),
            ], 422);
        }

        $validStatuses = ['promoted', 'trial', 'see_principal', 'repeat'];

        foreach ($rules as $i => $rule) {
            $n = $i + 1;

            if (empty($rule['rule_name'])) {
                return response()->json(['success' => false, 'message' => "Rule {$n}: name required."], 422);
            }
            if (!in_array($rule['status_label'] ?? '', $validStatuses)) {
                return response()->json(['success' => false, 'message' => "Rule {$n}: invalid status."], 422);
            }

            // Auto-correct grade grouping based on class type
            $currentGrouping = $rule['grade_grouping'] ?? 'grouped';
            if ($isSenior && $currentGrouping === 'grouped') {
                $rule['grade_grouping'] = 'exact';
            } elseif (!$isSenior && $currentGrouping === 'exact') {
                $rule['grade_grouping'] = 'grouped';
            }

            if (!isset($rule['compulsory_section'])) {
                $rule['compulsory_section'] = ['subjects' => [], 'count_conditions' => []];
            }
            if (!isset($rule['compulsory_section']['subjects'])) {
                $rule['compulsory_section']['subjects'] = [];
            }

            // Filter out subjects without valid subject_id
            $validSubjects = [];
            foreach ($rule['compulsory_section']['subjects'] as $subject) {
                if (isset($subject['subject_id']) && !empty($subject['subject_id'])) {
                    $validSubjects[] = $subject;
                }
            }
            $rule['compulsory_section']['subjects'] = $validSubjects;

            if (!isset($rule['other_section'])) {
                $rule['other_section'] = ['count_conditions' => []];
            }
            if (!isset($rule['other_section']['count_conditions'])) {
                $rule['other_section']['count_conditions'] = [];
            }

            if (!isset($rule['average_condition'])) {
                $rule['average_condition'] = ['enabled' => false, 'min_average' => 50, 'logic' => 'AND'];
            }

            $rules[$i] = $rule;
        }

        return $rules;
    }

    private function getCompulsorySubjects($classId, $termId = null, $sessionId = null): array
    {
        $query = CompulsorySubjectClass::where('schoolclassid', $classId)
            ->where(function ($q) use ($termId, $sessionId) {
                $q->where(function ($q2) use ($termId, $sessionId) {
                    if ($termId)    $q2->where('termid', $termId);
                    if ($sessionId) $q2->where('sessionid', $sessionId);
                })->orWhere(function ($q2) {
                    $q2->whereNull('termid')->whereNull('sessionid');
                });
            })
            ->with('subject');

        return $query->get()->map(fn ($cs) => [
            'id'                => (string) $cs->subjectId,
            'subject'           => $cs->subject?->subject ?? 'N/A',
            'subject_code'      => $cs->subject?->subject_code ?? '',
            'default_min_grade' => $cs->min_grade ?? '',
        ])->unique('id')->values()->toArray();
    }
}