<?php

namespace App\Services;

use App\Models\CompulsorySubjectClass;
use App\Models\PromotionSetting;
use App\Models\PromotionStatus;
use App\Models\Schoolterm;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionEvaluator
{
    // ── Status constants ────────────────────────────────────────────────────
    // These are the only four real promotion outcomes an admin can configure
    // via a rule's status_label. "Awaiting" is NOT one of them — it was
    // removed as a formal status because it isn't a promotion decision, it's
    // the absence of one. See awaitingResult() below: it now returns
    // 'status' => null instead of a fifth pseudo-status, and the 'reason'
    // field explains why no decision could be made.
    public const STATUS_PROMOTED      = 'promoted';
    public const STATUS_TRIAL         = 'trial';
    public const STATUS_SEE_PRINCIPAL = 'see_principal';
    public const STATUS_REPEATED      = 'repeated';

    /**
     * Senior grade ladder (WAEC style) — higher = better.
     */
    private static array $seniorGradeOrder = [
        'F9' => 0, 'E8' => 1, 'D7' => 2,
        'C6' => 3, 'C5' => 4, 'C4' => 5,
        'B3' => 6, 'B2' => 7, 'A1' => 8,
    ];

    /**
     * Junior grade ladder (A–F) — higher = better.
     */
    private static array $juniorGradeOrder = [
        'F' => 0, 'D' => 1, 'C' => 2, 'B' => 3, 'A' => 4,
    ];

    /**
     * Map senior exact grades → junior grouped equivalents.
     */
    private static array $gradeConversionMap = [
        'A1' => 'A',
        'B2' => 'B', 'B3' => 'B',
        'C4' => 'C', 'C5' => 'C', 'C6' => 'C',
        'D7' => 'D',
        'E8' => 'F', 'F9' => 'F',
    ];

    // =========================================================================
    // PUBLIC ENTRY POINT
    // =========================================================================

    public function evaluate(
        int    $studentId,
        int    $schoolclassid,
        int    $termid,
        int    $sessionid,
               $scores,
        ?float $overallAverage = null
    ): array {
        Log::info('[PromotionEvaluator] START', [
            'student_id'      => $studentId,
            'class_id'        => $schoolclassid,
            'session_id'      => $sessionid,
            'term_id'         => $termid,
            'overall_average' => $overallAverage,
            'scores_count'    => is_countable($scores) ? count($scores) : 0,
        ]);

        // ── Step 1: is this a promotional term at all? ──────────────────────
        $term          = Schoolterm::find($termid);
        $isPromotional = $term && $term->is_promotional;

        if (!$isPromotional) {
            return $this->awaitingResult($overallAverage, 'Non-promotional term');
        }

        // ── Step 2: class grading scale (senior A1–F9 vs junior A–F) ────────
        $classCategory     = $this->getClassCategory($schoolclassid);
        $usesSeniorGrading = $classCategory && !empty($classCategory->is_senior);

        // ── Step 3: does any active setting exist for this class? ───────────
        $anySettingsExist = PromotionSetting::where('schoolclass_id', $schoolclassid)
            ->where('is_active', true)
            ->exists();

        if (!$anySettingsExist) {
            return $this->awaitingResult($overallAverage, 'No promotion settings configured');
        }

        // ── Step 4: find the best setting for this session + term ───────────
        $settings = $this->findBestSettings($schoolclassid, $sessionid, $termid);

        if (!$settings) {
            return $this->awaitingResult($overallAverage, 'No matching setting for session/term');
        }

        $rules = $settings->promotion_rules ?? [];

        if (empty($rules)) {
            return $this->awaitingResult($overallAverage, 'Settings have no rules');
        }

        // ── Step 5: prepare everything the rule engine needs ────────────────
        $scoreMap      = $this->buildScoreMap($scores);
        $compulsoryIds = $this->getCompulsoryIds($schoolclassid, $termid, $sessionid);
        $ruleLogic     = $settings->rule_logic ?? 'grade_count';

        if (empty($compulsoryIds)) {
            Log::warning('[PromotionEvaluator] No compulsory subjects configured', [
                'schoolclass_id' => $schoolclassid,
                'term_id'        => $termid,
                'session_id'     => $sessionid,
            ]);
        }

        // ── Step 6: match rules (grade_count / both) ────────────────────────
        $matchedRule      = null;
        $matchedStatus    = null;
        $matchedRuleName  = null;
        $matchedRuleIndex = null;

        if (in_array($ruleLogic, ['grade_count', 'both'], true)) {
            foreach ($rules as $idx => $rule) {
                $ruleName = $rule['rule_name'] ?? null;

                if (!$ruleName) {
                    continue;
                }

                if ($this->ruleMatches($rule, $scoreMap, $compulsoryIds, $usesSeniorGrading, $overallAverage)) {
                    $matchedRule      = $rule;
                    $matchedStatus    = $rule['status_label'] ?? self::STATUS_PROMOTED;
                    $matchedRuleName  = $ruleName;
                    $matchedRuleIndex = $idx;

                    Log::info('[PromotionEvaluator] Rule MATCHED', [
                        'rule_name' => $matchedRuleName,
                        'status'    => $matchedStatus,
                        'index'     => $idx,
                    ]);
                    break;
                }
            }
        }

        // ── Step 7: evaluate average-only branch ────────────────────────────
        $requiredAverage = $this->resolveRequiredAverage($settings, $schoolclassid);

        // $averageConditionMet is tri-state: true | false | null.
        // null means "not applicable" (no required average configured, or
        // no overall average computable for this student).
        [$averageConditionMet, $averageStatus] = $this->evaluateAverage(
            $ruleLogic,
            $requiredAverage,
            $overallAverage
        );

        // ── Step 8: resolve final status ────────────────────────────────────
        // $finalStatus is now nullable: null means "no decision could be
        // made" (not a fifth status) — see resolveFinalStatus() below.
        $finalStatus = $this->resolveFinalStatus(
            $ruleLogic,
            $matchedStatus,
            $averageConditionMet,
            $averageStatus,
            $isPromotional
        );

        // ── Step 9: build compulsory subject detail for the drawer ──────────
        [$failedCompulsory, $compulsoryDetail, $passedCount, $totalCount]
            = $this->buildCompulsoryDetail(
                $schoolclassid,
                $termid,
                $sessionid,
                $scoreMap,
                $usesSeniorGrading
            );

        // ── Step 10: applied-rule summary for the UI ────────────────────────
        $appliedRuleSummary = null;
        if ($matchedRule !== null && $matchedRuleName !== null) {
            $appliedRuleSummary = [
                'name'        => $matchedRuleName,
                'description' => $this->describeRule($matchedRule, $usesSeniorGrading),
                'index'       => $matchedRuleIndex + 1,
            ];
        }

        return [
            'status'                    => $finalStatus,
            'status_label'              => $this->mapStatusLabel($finalStatus, $settings),
            'is_promotional_term'       => $isPromotional,
            'failed_compulsory'         => $failedCompulsory,
            'compulsory_subject_detail' => $compulsoryDetail,
            'average_failed'            => $averageConditionMet === false,
            'average_applicable'        => $averageConditionMet !== null,
            'required_average'          => $requiredAverage,
            'actual_average'            => $overallAverage,
            'compulsory_count'          => $totalCount,
            'passed_compulsory'         => $passedCount,
            'matched_labels'            => [],
            'applied_rule'              => $appliedRuleSummary,
            'settings_id'               => $settings->id,
            'rule_logic'                => $ruleLogic,
            'settings'                  => [
                'rule_logic'             => $ruleLogic,
                'promotion_pass_average' => $requiredAverage,
            ],
        ];
    }

    /**
     * Return a "no decision" result — used whenever evaluation can't run at
     * all (non-promotional term, no settings, no matching setting, no
     * rules). 'status' is null, NOT a formal status value: it is not one of
     * the four configurable promotion outcomes. The blade layer already
     * treats a missing/null status as "awaiting" for display purposes via
     * `$rec['status'] ?? 'awaiting'` — no template changes were needed for
     * this removal. 'status_label' stays as human-readable text since it's
     * just informational, not part of the status enum.
     */
    public function awaitingResult(?float $overallAverage, string $reason = ''): array
    {
        return [
            'status'                    => null,
            'status_label'              => 'Awaiting Decision',
            'is_promotional_term'       => false,
            'failed_compulsory'         => [],
            'compulsory_subject_detail' => [],
            'average_failed'            => false,
            'average_applicable'        => false,
            'required_average'          => null,
            'actual_average'            => $overallAverage,
            'compulsory_count'          => 0,
            'passed_compulsory'         => 0,
            'matched_labels'            => [],
            'applied_rule'              => null,
            'settings_id'               => null,
            'rule_logic'                => null,
            'settings'                  => null,
            'reason'                    => $reason,
        ];
    }

    /**
     * Persist the evaluator result onto PromotionStatus.
     */
    public function persistResult(
        int   $studentId,
        int   $schoolclassid,
        int   $sessionid,
        int   $termid,
        array $result
    ): PromotionStatus {
        return PromotionStatus::updateOrCreate(
            [
                'studentId'     => $studentId,
                'schoolclassid' => $schoolclassid,
                'sessionid'     => $sessionid,
                'termid'        => $termid,
            ],
            [
                // $result['status'] may be null when no decision was made;
                // strtoupper(null) is deprecated in PHP 8.1+, so guard it.
                'promotionStatus'        => $result['status'] !== null ? strtoupper($result['status']) : null,
                'classstatus'            => 'CURRENT',
                'rule_applied'           => $result['applied_rule']['name'] ?? null,
                'overall_average'        => $result['actual_average']   ?? null,
                'promotion_pass_average' => $result['required_average'] ?? null,
                'evaluated_at'           => now(),
            ]
        );
    }

    // =========================================================================
    // PRIVATE — SETTINGS RESOLUTION
    // =========================================================================

    private function getClassCategory(int $schoolclassid): ?object
    {
        return DB::table('schoolclass_classcategory')
            ->join(
                'classcategories',
                'classcategories.id',
                '=',
                'schoolclass_classcategory.classcategory_id'
            )
            ->where('schoolclass_classcategory.schoolclass_id', $schoolclassid)
            ->select(
                'classcategories.id',
                'classcategories.category',
                'classcategories.is_senior',
                'classcategories.promotion_pass_average'
            )
            ->first();
    }

    private function findBestSettings(int $schoolclassid, int $sessionid, int $termid): ?PromotionSetting
    {
        $allSettings = PromotionSetting::where('schoolclass_id', $schoolclassid)
            ->where('is_active', true)
            ->get();

        if ($allSettings->isEmpty()) {
            return null;
        }

        $scored = [];

        foreach ($allSettings as $setting) {
            $score     = 0;
            $matchType = '';

            if ($setting->session_id == $sessionid && $setting->term_id == $termid) {
                $score = 100; $matchType = 'exact_session_term';
            } elseif ($setting->session_id == $sessionid && $setting->term_id === null) {
                $score = 90;  $matchType = 'session_only';
            } elseif ($setting->session_id === null && $setting->term_id == $termid) {
                $score = 80;  $matchType = 'term_only';
            } elseif ($setting->session_id === null && $setting->term_id === null) {
                $score = 70;  $matchType = 'global';
            } elseif ($setting->session_id !== null
                      && $setting->session_id != $sessionid
                      && $setting->term_id === null) {
                $score = 60;  $matchType = 'different_session_fallback';
            }

            if ($score > 0) {
                $scored[] = [
                    'setting'    => $setting,
                    'score'      => $score,
                    'match_type' => $matchType,
                    'priority'   => $setting->priority ?? 999,
                ];
            }
        }

        if (empty($scored)) {
            return null;
        }

        usort($scored, function ($a, $b) {
            if ($a['score'] !== $b['score']) {
                return $b['score'] - $a['score'];
            }
            return $a['priority'] - $b['priority'];
        });

        Log::info('[PromotionEvaluator] Selected setting', [
            'setting_id'    => $scored[0]['setting']->id,
            'rule_set_name' => $scored[0]['setting']->rule_set_name,
            'match_type'    => $scored[0]['match_type'],
            'score'         => $scored[0]['score'],
        ]);

        return $scored[0]['setting'];
    }

    private function resolveRequiredAverage(PromotionSetting $settings, int $schoolclassid): ?float
    {
        if ($settings->promotion_pass_average !== null && $settings->promotion_pass_average !== '') {
            return (float) $settings->promotion_pass_average;
        }

        $val = DB::table('schoolclass_classcategory')
            ->join('classcategories', 'classcategories.id', '=', 'schoolclass_classcategory.classcategory_id')
            ->where('schoolclass_classcategory.schoolclass_id', $schoolclassid)
            ->value('classcategories.promotion_pass_average');

        return $val !== null ? (float) $val : null;
    }

    // =========================================================================
    // PRIVATE — RULE MATCHING
    // =========================================================================

    private function ruleMatches(
        array      $rule,
        Collection $scoreMap,
        array      $compulsoryIds,
        bool       $isSenior,
        ?float     $overallAverage = null
    ): bool {
        $grouping = $rule['grade_grouping'] ?? 'grouped';

        // On senior classes, "grouped" mode is meaningless
        if ($isSenior && $grouping === 'grouped') {
            $grouping = 'exact';
        }

        $gradeConditionsMet = true;

        // ── Section 1: per-subject minimum grade ────────────────────────────
        foreach ($rule['compulsory_section']['subjects'] ?? [] as $subjectRule) {
            $minGrade = $subjectRule['min_grade'] ?? null;
            if (!$minGrade) {
                continue;
            }

            $subjectId    = $subjectRule['subject_id'] ?? null;
            $scoreEntry   = $subjectId ? $scoreMap->get($subjectId) : null;
            $studentGrade = $this->gradeFromEntry($scoreEntry);

            $normalizedStudent = $this->normalizeGrade($studentGrade, $isSenior);
            $normalizedMin     = $this->normalizeGrade($minGrade,     $isSenior);

            if ($this->gradeFails($normalizedStudent, $normalizedMin, $isSenior, $grouping)) {
                $gradeConditionsMet = false;
                break;
            }
        }

        // ── Section 2a: compulsory-scope count conditions ───────────────────
        if ($gradeConditionsMet) {
            $gradeConditionsMet = $this->evaluateCountConditions(
                $rule['compulsory_section']['count_conditions'] ?? [],
                $scoreMap,
                $compulsoryIds,
                $grouping,
                $isSenior
            );
        }

        // ── Section 2b: other / all-scope count conditions ──────────────────
        if ($gradeConditionsMet) {
            $gradeConditionsMet = $this->evaluateCountConditions(
                $rule['other_section']['count_conditions'] ?? [],
                $scoreMap,
                $compulsoryIds,
                $grouping,
                $isSenior
            );
        }

        // ── Section 3: per-rule average condition ───────────────────────────
        $avgCond = $rule['average_condition'] ?? null;

        if (!empty($avgCond['enabled'])) {
            $minAvg = isset($avgCond['min_average']) && $avgCond['min_average'] !== ''
                ? (float) $avgCond['min_average']
                : null;

            $avgConditionMet = ($minAvg !== null && $overallAverage !== null)
                && ($overallAverage >= $minAvg);

            $logic = strtoupper($avgCond['logic'] ?? 'AND');

            return $logic === 'OR'
                ? ($gradeConditionsMet || $avgConditionMet)
                : ($gradeConditionsMet && $avgConditionMet);
        }

        return $gradeConditionsMet;
    }

    private function evaluateCountConditions(
        array      $conditions,
        Collection $scoreMap,
        array      $compulsoryIds,
        string     $grouping,
        bool       $isSenior
    ): bool {
        if (empty($conditions)) {
            return true;
        }

        foreach ($conditions as $cond) {
            $grade    = strtoupper(trim($cond['grade']    ?? ''));
            $operator = $cond['operator']                ?? '>=';
            $required = (int) ($cond['count']            ?? 0);
            $scope    = $cond['scope']                   ?? 'all';

            if (!$grade) {
                continue;
            }

            $scopedScores = $this->filterByScope($scoreMap, $scope, $compulsoryIds);
            $actual       = $this->countMatchingGrade($scopedScores, $grade, $grouping, $isSenior);

            if (!$this->compareCount($actual, $operator, $required)) {
                return false;
            }
        }

        return true;
    }

    private function filterByScope(Collection $scoreMap, string $scope, array $compulsoryIds): Collection
    {
        $compLookup = array_map('strval', $compulsoryIds);

        return $scoreMap->filter(function ($score, $subjectId) use ($scope, $compLookup) {
            $isComp = in_array((string) $subjectId, $compLookup, true);
            return match ($scope) {
                'compulsory_only' => $isComp,
                'other_only'      => !$isComp,
                default           => true,
            };
        });
    }

    private function countMatchingGrade(
        Collection $scopedScores,
        string     $grade,
        string     $grouping,
        bool       $isSenior
    ): int {
        $required = strtoupper(trim($grade));
        $count    = 0;

        foreach ($scopedScores as $score) {
            $studentGrade = $this->gradeFromEntry($score);
            if (!$studentGrade) {
                continue;
            }

            $normalized = strtoupper(trim($this->normalizeGrade($studentGrade, $isSenior) ?? ''));
            if (!$normalized) {
                continue;
            }

            if ($isSenior) {
                if ($grouping === 'grouped') {
                    $studentGroup  = self::$gradeConversionMap[$normalized] ?? $normalized;
                    $requiredGroup = self::$gradeConversionMap[$required]   ?? $required;
                    if ($studentGroup === $requiredGroup) {
                        $count++;
                    }
                } else {
                    if ($normalized === $required) {
                        $count++;
                    }
                }
            } else {
                $studentRank  = self::$juniorGradeOrder[$normalized] ?? -1;
                $requiredRank = self::$juniorGradeOrder[$required]   ?? 0;
                if ($studentRank >= $requiredRank) {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function compareCount(int $actual, string $operator, int $required): bool
    {
        return match ($operator) {
            '>='    => $actual >= $required,
            '<='    => $actual <= $required,
            '='     => $actual === $required,
            '>'     => $actual >  $required,
            '<'     => $actual <  $required,
            default => false,
        };
    }

    private function gradeFails(
        ?string $studentGrade,
        ?string $minGrade,
        bool    $isSenior,
        string  $grouping = 'exact'
    ): bool {
        if ($studentGrade === null) {
            return true;
        }

        $sg = strtoupper(trim($studentGrade));

        if (empty($minGrade)) {
            return in_array($sg, $isSenior ? ['F9'] : ['F'], true);
        }

        $mg = strtoupper(trim($minGrade));

        if ($isSenior) {
            return (self::$seniorGradeOrder[$sg] ?? -1) < (self::$seniorGradeOrder[$mg] ?? 0);
        }

        return (self::$juniorGradeOrder[$sg] ?? -1) < (self::$juniorGradeOrder[$mg] ?? 0);
    }

    private function normalizeGrade(?string $grade, bool $isSenior): ?string
    {
        if ($grade === null) {
            return null;
        }
        $grade = strtoupper(trim($grade));

        if (!$isSenior && isset(self::$gradeConversionMap[$grade])) {
            return self::$gradeConversionMap[$grade];
        }
        return $grade;
    }

    // =========================================================================
    // PRIVATE — COMPULSORY SUBJECT DETAIL
    // =========================================================================

    private function buildCompulsoryDetail(
        int        $schoolclassid,
        int        $termid,
        int        $sessionid,
        Collection $scoreMap,
        bool       $isSenior
    ): array {
        $compulsoryRules = CompulsorySubjectClass::where('schoolclassid', $schoolclassid)
            ->where(function ($q) use ($termid, $sessionid) {
                $q->where(function ($q2) use ($termid, $sessionid) {
                    $q2->where('termid', $termid)->where('sessionid', $sessionid);
                })->orWhere(function ($q2) use ($sessionid) {
                    $q2->whereNull('termid')->where('sessionid', $sessionid);
                })->orWhere(function ($q2) {
                    $q2->whereNull('termid')->whereNull('sessionid');
                });
            })
            ->get(['subjectId', 'min_grade']);

        $failed = [];
        $detail = [];
        $passed = 0;
        $total  = $compulsoryRules->count();

        foreach ($compulsoryRules as $rule) {
            $subjectId    = $rule->subjectId;
            $scoreEntry   = $scoreMap->get($subjectId);
            $studentGrade = $this->gradeFromEntry($scoreEntry);
            $subjectName  = is_object($scoreEntry)
                ? ($scoreEntry->subject_name ?? null)
                : null;
            $minGrade = $rule->min_grade;

            $normalizedStudent = $this->normalizeGrade($studentGrade, $isSenior);
            $normalizedMin     = $this->normalizeGrade($minGrade,     $isSenior);

            $didPass = false;
            if ($scoreEntry && $normalizedStudent) {
                if ($isSenior) {
                    $didPass = (self::$seniorGradeOrder[$normalizedStudent] ?? -1)
                             >= (self::$seniorGradeOrder[$normalizedMin]     ?? 0);
                } else {
                    $didPass = (self::$juniorGradeOrder[$normalizedStudent] ?? -1)
                             >= (self::$juniorGradeOrder[$normalizedMin]     ?? 0);
                }
            }

            $entry = [
                'subject_id'           => $subjectId,
                'subject'              => $subjectName,
                'grade'                => $studentGrade,
                'normalized_grade'     => $normalizedStudent,
                'min_grade'            => $minGrade,
                'normalized_min_grade' => $normalizedMin,
                'passed'               => $didPass,
                'not_sat'              => !$scoreEntry,
            ];

            $detail[] = $entry;

            if ($didPass) {
                $passed++;
            } else {
                $failed[] = $entry;
            }
        }

        return [$failed, $detail, $passed, $total];
    }

    private function getCompulsoryIds(int $schoolclassid, int $termid, int $sessionid): array
    {
        return CompulsorySubjectClass::where('schoolclassid', $schoolclassid)
            ->where(function ($q) use ($termid, $sessionid) {
                $q->where(function ($q2) use ($termid, $sessionid) {
                    $q2->where('termid', $termid)->where('sessionid', $sessionid);
                })->orWhere(function ($q2) use ($sessionid) {
                    $q2->whereNull('termid')->where('sessionid', $sessionid);
                })->orWhere(function ($q2) {
                    $q2->whereNull('termid')->whereNull('sessionid');
                });
            })
            ->pluck('subjectId')
            ->map(fn ($id) => (string) $id)
            ->toArray();
    }

    // =========================================================================
    // PRIVATE — MISC HELPERS
    // =========================================================================

    private function buildScoreMap($scores): Collection
    {
        return collect($scores)->keyBy(function ($s) {
            return is_object($s) ? ($s->subject_id ?? null) : ($s['subject_id'] ?? null);
        });
    }

    private function gradeFromEntry($entry): ?string
    {
        if (!$entry) return null;
        return is_object($entry)
            ? ($entry->grade ?? null)
            : ($entry['grade'] ?? null);
    }

    /**
     * Tri-state return: [true, null] met / [false, self::STATUS_REPEATED]
     * not met / [null, null] not applicable (not configured, not computable,
     * or not used by this rule_logic mode). Callers MUST treat null as
     * "not applicable", never as "passed" — see resolveFinalStatus().
     */
    private function evaluateAverage(
        string $ruleLogic,
        ?float $requiredAverage,
        ?float $overallAverage
    ): array {
        if (!in_array($ruleLogic, ['average_only', 'both'], true)) {
            return [null, null];
        }
        if ($requiredAverage === null || $overallAverage === null) {
            return [null, null];
        }
        $met = $overallAverage >= $requiredAverage;
        return [$met, $met ? self::STATUS_PROMOTED : self::STATUS_REPEATED];
    }

    /**
     * Returns one of the four real status constants. null is reserved
     * EXCLUSIVELY for "not promotional term" (a case evaluate() already
     * short-circuits before this method is ever called, so in practice this
     * method always returns a real status when it runs at all).
     *
     * Once rules are actually being evaluated (a matching active setting
     * with rules exists, for a promotional term), the student ALWAYS gets
     * a real decision — there is no ambiguous "still deciding" state once
     * evaluation has genuinely started. This mirrors the rule your own
     * settings page documents: "If no rule matches → Advice to Repeat."
     *
     * - average_only: defers to the average comparison; if the average
     *   couldn't be computed (not configured / no score data), defaults to
     *   Repeat rather than leaving the outcome undetermined.
     * - grade_count: defers to the matched rule; if nothing matched,
     *   defaults to Repeat.
     * - both: a matched rule's status wins outright unless the average was
     *   evaluated AND failed — in which case only an otherwise-Promoted
     *   outcome gets downgraded to Trial. A non-Promoted matched status
     *   (Trial / See Principal / Repeat) is never upgraded by a failed
     *   average. When nothing matched, falls back to the average alone,
     *   defaulting to Repeat if the average wasn't applicable either.
     */
    private function resolveFinalStatus(
        string  $ruleLogic,
        ?string $matchedStatus,
        ?bool   $averageConditionMet,
        ?string $averageStatus,
        bool    $isPromotional = true
    ): ?string {
        if (!$isPromotional) {
            return null;
        }

        switch ($ruleLogic) {
            case 'average_only':
                return $averageStatus ?? self::STATUS_REPEATED;

            case 'grade_count':
                return $matchedStatus ?? self::STATUS_REPEATED;

            case 'both':
                if ($matchedStatus !== null) {
                    if ($averageConditionMet === null || $averageConditionMet === true) {
                        return $matchedStatus;
                    }
                    // Average explicitly failed. Only downgrade a
                    // would-be Promotion to Trial; never upgrade any
                    // other configured outcome.
                    return $matchedStatus === self::STATUS_PROMOTED
                        ? self::STATUS_TRIAL
                        : $matchedStatus;
                }

                // No rule matched — fall back to the average check alone.
                // Both "average failed" and "average not applicable"
                // default to Repeat: with no matched rule and no positive
                // average confirmation, there is nothing to promote on.
                return $averageConditionMet === true
                    ? self::STATUS_PROMOTED
                    : self::STATUS_REPEATED;

            default:
                return $matchedStatus ?? self::STATUS_REPEATED;
        }
    }

    private function mapStatusLabel(?string $status, PromotionSetting $settings): string
    {
        return match ($status) {
            self::STATUS_PROMOTED      => $settings->promoted_label      ?? 'Promoted',
            self::STATUS_TRIAL         => $settings->trial_label         ?? 'Promoted on Trial',
            self::STATUS_SEE_PRINCIPAL => $settings->see_principal_label ?? 'Advised to See Principal',
            self::STATUS_REPEATED      => $settings->repeat_label        ?? 'Advice to Repeat',
            default                    => 'Awaiting Decision',
        };
    }

    private function describeRule(array $rule, bool $isSenior): string
    {
        $parts = [];

        $withMin = array_filter(
            $rule['compulsory_section']['subjects'] ?? [],
            fn ($s) => !empty($s['min_grade'])
        );
        if ($withMin) {
            $parts[] = count($withMin) . ' compulsory subject min-grade requirement(s)';
        }

        foreach ($rule['compulsory_section']['count_conditions'] ?? [] as $c) {
            $scope   = match ($c['scope'] ?? 'all') {
                'compulsory_only' => 'compulsory subj',
                'other_only'      => 'other subj',
                default           => 'all subj',
            };
            $parts[] = "{$c['operator']} {$c['count']} {$c['grade']} in {$scope}";
        }

        foreach ($rule['other_section']['count_conditions'] ?? [] as $c) {
            $scope   = match ($c['scope'] ?? 'all') {
                'compulsory_only' => 'compulsory subj',
                'other_only'      => 'other subj',
                default           => 'all subj',
            };
            $parts[] = "{$c['operator']} {$c['count']} {$c['grade']} in {$scope}";
        }

        $avgCond = $rule['average_condition'] ?? [];
        if (!empty($avgCond['enabled'])) {
            $parts[] = 'avg ' . ($avgCond['logic'] ?? 'AND')
                     . ' ≥' . ($avgCond['min_average'] ?? '?') . '%';
        }

        return implode('; ', $parts) ?: 'No conditions';
    }

    // =========================================================================
    // PUBLIC — badge helpers (used by Blade views)
    // =========================================================================

    public function getStatusBadgeClass(?string $status): string
    {
        return match ($status) {
            self::STATUS_PROMOTED      => 'bg-success',
            self::STATUS_TRIAL         => 'bg-warning',
            self::STATUS_SEE_PRINCIPAL => 'bg-info',
            self::STATUS_REPEATED      => 'bg-danger',
            default                    => 'bg-secondary',
        };
    }

    public function getStatusIcon(?string $status): string
    {
        return match ($status) {
            self::STATUS_PROMOTED      => 'ri-checkbox-circle-line',
            self::STATUS_TRIAL         => 'ri-time-line',
            self::STATUS_SEE_PRINCIPAL => 'ri-eye-line',
            self::STATUS_REPEATED      => 'ri-repeat-line',
            default                    => 'ri-question-line',
        };
    }
}