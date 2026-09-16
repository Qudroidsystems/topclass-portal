<?php
// app/Services/PromotionEvaluator.php

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
    public const STATUS_PROMOTED      = 'promoted';
    public const STATUS_TRIAL         = 'trial';
    public const STATUS_SEE_PRINCIPAL = 'see_principal';
    public const STATUS_REPEATED      = 'repeated';
    public const STATUS_AWAITING      = 'awaiting';

    /**
     * Senior grade ladder (WAEC style) — higher = better.
     * Used for exact-grade comparisons and min-grade checks.
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
     * Used when a rule says `grouping = grouped` on a senior class.
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

    /**
     * Evaluate a single student's promotion outcome.
     *
     * @param  int        $studentId
     * @param  int        $schoolclassid
     * @param  int        $termid
     * @param  int        $sessionid
     * @param  Collection $scores          Fixed-CA scores (from ViewStudentReportController::getStudentResultData)
     *                                     Each item MUST expose:
     *                                       ->subject_id
     *                                       ->subject_name
     *                                       ->subject_code
     *                                       ->total
     *                                       ->cum
     *                                       ->grade
     * @param  float|null $overallAverage  Pre-computed overall average (0–100). Optional — will be computed from scores if null.
     */
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
            Log::warning('[PromotionEvaluator] No compulsory subjects configured — rules scoped to compulsory_only cannot match', [
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
                    Log::warning('[PromotionEvaluator] Rule missing rule_name', [
                        'rule_index' => $idx,
                    ]);
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

        [$averageConditionMet, $averageStatus] = $this->evaluateAverage(
            $ruleLogic, $requiredAverage, $overallAverage
        );

        // ── Step 8: resolve final status ────────────────────────────────────
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
                $schoolclassid, $termid, $sessionid, $scoreMap, $usesSeniorGrading
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
            'status'                     => $finalStatus,
            'status_label'               => $this->mapStatusLabel($finalStatus, $settings),
            'is_promotional_term'        => $isPromotional,
            'failed_compulsory'          => $failedCompulsory,
            'compulsory_subject_detail'  => $compulsoryDetail,
            'average_failed'             => !$averageConditionMet,
            'required_average'           => $requiredAverage,
            'actual_average'             => $overallAverage,
            'compulsory_count'           => $totalCount,
            'passed_compulsory'          => $passedCount,
            'matched_labels'             => [],
            'applied_rule'               => $appliedRuleSummary,
            'settings_id'                => $settings->id,
            'rule_logic'                 => $ruleLogic,
            'settings'                   => [
                'rule_logic'             => $ruleLogic,
                'promotion_pass_average' => $requiredAverage,
            ],
        ];
    }

    /**
     * Return an "awaiting" result — used when a class/session/term has
     * no applicable promotion settings or a non-promotional term.
     */
    public function awaitingResult(?float $overallAverage, string $reason = ''): array
    {
        return [
            'status'                    => self::STATUS_AWAITING,
            'status_label'              => 'Awaiting Decision',
            'is_promotional_term'       => false,
            'failed_compulsory'         => [],
            'compulsory_subject_detail' => [],
            'average_failed'            => false,
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
     * Persist the evaluator result onto the PromotionStatus row
     * (upsert by student/class/session/term). Used by controllers
     * after evaluation.
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
                'promotionStatus'        => strtoupper($result['status']),
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
                'classcategories.id', '=', 'schoolclass_classcategory.classcategory_id'
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

    /**
     * Score each matching setting by specificity and return the highest-scoring
     * one. Exact session+term > session-only > term-only > global > fallback.
     */
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
            } else {
                $score = 0;   $matchType = 'mismatch';
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

    /**
     * Determine whether a single rule matches this student's scores.
     *
     * A rule has up to three independent sections:
     *   1. compulsory_section.subjects         → per-subject min grade
     *   2. compulsory_section.count_conditions → grade-count thresholds (compulsory scope)
     *      + other_section.count_conditions    → grade-count thresholds (other / all scope)
     *   3. average_condition                   → optional min overall average
     *
     * Sections 1 + 2 are combined as AND (all must pass).
     * Section 3, when enabled, is combined against that result using its
     * own `logic` field: AND (default) or OR.
     */
    private function ruleMatches(
        array      $rule,
        Collection $scoreMap,
        array      $compulsoryIds,
        bool       $isSenior,
        ?float     $overallAverage = null
    ): bool {
        $grouping = $rule['grade_grouping'] ?? 'grouped';

        // On senior classes, "grouped" mode is meaningless (A/B/C/D/F don't exist).
        // Force exact so the grade ladders line up.
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

            Log::info('[PromotionEvaluator] Rule average-condition', [
                'rule_name'          => $rule['rule_name'] ?? null,
                'min_average'        => $minAvg,
                'overall_average'    => $overallAverage,
                'logic'              => $logic,
                'grade_conditions'   => $gradeConditionsMet,
                'avg_condition_met'  => $avgConditionMet,
            ]);

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

            if ($scope === 'compulsory_only'
                && empty($compulsoryIds)
                && in_array($operator, ['>=', '>'], true)
                && $required > 0) {
                Log::warning('[PromotionEvaluator] Unreachable condition: compulsory_only with no compulsory subjects', [
                    'condition' => $cond,
                ]);
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

    /**
     * Count how many subjects in the scoped set meet the required grade.
     * Senior + grouped  → collapse exact grades to their group letter before comparing.
     * Senior + exact    → direct string equality.
     * Junior            → rank-based ">=" comparison.
     */
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
            return true; // not sat = fail
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

        // Only map when the class is JUNIOR — because the map collapses
        // senior codes (A1/B2) to junior letters (A/B) which is what
        // junior rules compare against.
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

    private function evaluateAverage(
        string $ruleLogic,
        ?float $requiredAverage,
        ?float $overallAverage
    ): array {
        if (!in_array($ruleLogic, ['average_only', 'both'], true)) {
            return [true, null];
        }
        if ($requiredAverage === null || $overallAverage === null) {
            return [true, null];
        }
        $met = $overallAverage >= $requiredAverage;
        return [$met, $met ? self::STATUS_PROMOTED : self::STATUS_REPEATED];
    }

    private function resolveFinalStatus(
        string  $ruleLogic,
        ?string $matchedStatus,
        bool    $averageConditionMet,
        ?string $averageStatus,
        bool    $isPromotional = true
    ): string {
        if (!$isPromotional) {
            return self::STATUS_AWAITING;
        }

        switch ($ruleLogic) {
            case 'average_only':
                return $averageStatus ?? self::STATUS_AWAITING;

            case 'grade_count':
                return $matchedStatus ?? self::STATUS_REPEATED;

            case 'both':
                if ($matchedStatus !== null && $averageConditionMet)  return $matchedStatus;
                if ($matchedStatus !== null && !$averageConditionMet) return self::STATUS_TRIAL;
                if ($matchedStatus === null && $averageConditionMet)  return self::STATUS_PROMOTED;
                return self::STATUS_REPEATED;

            default:
                return $matchedStatus ?? self::STATUS_REPEATED;
        }
    }

    private function mapStatusLabel(string $status, PromotionSetting $settings): string
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

    public function getStatusBadgeClass(string $status): string
    {
        return match ($status) {
            self::STATUS_PROMOTED      => 'bg-success',
            self::STATUS_TRIAL         => 'bg-warning',
            self::STATUS_SEE_PRINCIPAL => 'bg-info',
            self::STATUS_REPEATED      => 'bg-danger',
            default                    => 'bg-secondary',
        };
    }

    public function getStatusIcon(string $status): string
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