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
    public const STATUS_PROMOTED      = 'promoted';
    public const STATUS_TRIAL         = 'trial';
    public const STATUS_SEE_PRINCIPAL = 'see_principal';
    public const STATUS_REPEATED      = 'repeated';

    private static array $seniorGradeOrder = [
        'F9' => 0, 'E8' => 1, 'D7' => 2,
        'C6' => 3, 'C5' => 4, 'C4' => 5,
        'B3' => 6, 'B2' => 7, 'A1' => 8,
    ];

    private static array $juniorGradeOrder = [
        'F' => 0, 'D' => 1, 'C' => 2, 'B' => 3, 'A' => 4,
    ];

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

        $term          = Schoolterm::find($termid);
        $isPromotional = $term && $term->is_promotional;

        if (!$isPromotional) {
            return $this->awaitingResult($overallAverage, 'Non-promotional term', $schoolclassid);
        }

        $classCategory     = $this->getClassCategory($schoolclassid);
        $usesSeniorGrading = $classCategory && !empty($classCategory->is_senior);

        $anySettingsExist = PromotionSetting::where('schoolclass_id', $schoolclassid)
            ->where('is_active', true)
            ->exists();

        if (!$anySettingsExist) {
            return $this->awaitingResult($overallAverage, 'No promotion settings configured', $schoolclassid);
        }

        $settings = $this->findBestSettings($schoolclassid, $sessionid, $termid);

        if (!$settings) {
            return $this->awaitingResult($overallAverage, 'No matching setting for session/term', $schoolclassid);
        }

        $rules = $settings->promotion_rules ?? [];

        if (empty($rules)) {
            return $this->awaitingResult($overallAverage, 'Settings have no rules', $schoolclassid);
        }

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
                    // FIX: normalize immediately on read. Settings are saved
                    // with status_label = 'repeat' (see
                    // PromotionSettingController::parseRules' $validStatuses),
                    // but this class's own constant is STATUS_REPEATED =
                    // 'repeated'. Without normalizing here, 'repeat' never
                    // equals STATUS_REPEATED anywhere downstream (finalStatus,
                    // mapStatusLabel, badge class/icon, persisted status),
                    // and mapStatusLabel's match() falls through to its
                    // default 'Awaiting Decision' even though a rule matched
                    // correctly and is shown in applied_rule.name.
                    $matchedStatus    = $this->normalizeStatus($rule['status_label'] ?? self::STATUS_PROMOTED);
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

        $requiredAverage = $this->resolveRequiredAverage($settings, $schoolclassid);

        [$averageConditionMet, $averageStatus] = $this->evaluateAverage(
            $ruleLogic,
            $requiredAverage,
            $overallAverage
        );

        $finalStatus = $this->resolveFinalStatus(
            $ruleLogic,
            $matchedStatus,
            $averageConditionMet,
            $averageStatus,
            $isPromotional
        );

        [$failedCompulsory, $compulsoryDetail, $passedCount, $totalCount]
            = $this->buildCompulsoryDetail(
                $schoolclassid,
                $termid,
                $sessionid,
                $scoreMap,
                $usesSeniorGrading
            );

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
     * Normalizes rule status_label values that don't exactly match this
     * class's STATUS_* constants. Currently only 'repeat' (the value saved
     * by PromotionSettingController::parseRules' $validStatuses) needs
     * mapping to STATUS_REPEATED = 'repeated'. Add further aliases here if
     * other saved values are ever found to diverge from the constants.
     */
    private function normalizeStatus(?string $status): ?string
    {
        if ($status === null) {
            return null;
        }

        return match ($status) {
            'repeat' => self::STATUS_REPEATED,
            default  => $status,
        };
    }

    /**
     * Fallback result used whenever full rule evaluation can't run at all
     * (non-promotional term, no settings, no matching setting, no rules).
     * Never returns a null status: falls back to comparing the average
     * against the class category's default pass average when available,
     * otherwise defaults to Repeat — there is no "Awaiting Decision" state.
     */
    public function awaitingResult(?float $overallAverage, string $reason = '', ?int $schoolclassid = null): array
    {
        [$fallbackStatus, $requiredAverage] = $this->computeFallbackStatus($schoolclassid, $overallAverage);

        return [
            'status'                    => $fallbackStatus,
            'status_label'              => $this->fallbackStatusLabel($fallbackStatus),
            'is_promotional_term'       => false,
            'failed_compulsory'         => [],
            'compulsory_subject_detail' => [],
            'average_failed'            => $fallbackStatus === self::STATUS_REPEATED,
            'average_applicable'        => $requiredAverage !== null && $overallAverage !== null,
            'required_average'          => $requiredAverage,
            'actual_average'            => $overallAverage,
            'compulsory_count'          => 0,
            'passed_compulsory'         => 0,
            'matched_labels'            => [],
            'applied_rule'              => null,
            'settings_id'               => null,
            'rule_logic'                => null,
            'settings'                  => null,
            'reason'                    => $reason,
            'is_estimated'              => true,
        ];
    }

    /**
     * Falls back to the class category's default pass average when rule
     * evaluation can't run. Defaults to Repeat when no average is
     * configured or computable — never leaves the outcome undetermined.
     */
    private function computeFallbackStatus(?int $schoolclassid, ?float $overallAverage): array
    {
        $requiredAverage = null;

        if ($schoolclassid !== null) {
            $val = DB::table('schoolclass_classcategory')
                ->join('classcategories', 'classcategories.id', '=', 'schoolclass_classcategory.classcategory_id')
                ->where('schoolclass_classcategory.schoolclass_id', $schoolclassid)
                ->value('classcategories.promotion_pass_average');
            $requiredAverage = $val !== null ? (float) $val : null;
        }

        if ($requiredAverage !== null && $overallAverage !== null) {
            return [
                $overallAverage >= $requiredAverage ? self::STATUS_PROMOTED : self::STATUS_REPEATED,
                $requiredAverage,
            ];
        }

        return [self::STATUS_REPEATED, $requiredAverage];
    }

    private function fallbackStatusLabel(string $status): string
    {
        return $status === self::STATUS_PROMOTED ? 'Promoted' : 'Advice to Repeat';
    }

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

        $gradeConditionsMet = true;

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

        if ($gradeConditionsMet) {
            $gradeConditionsMet = $this->evaluateCountConditions(
                $rule['compulsory_section']['count_conditions'] ?? [],
                $scoreMap,
                $compulsoryIds,
                $grouping,
                $isSenior
            );
        }

        if ($gradeConditionsMet) {
            $gradeConditionsMet = $this->evaluateCountConditions(
                $rule['other_section']['count_conditions'] ?? [],
                $scoreMap,
                $compulsoryIds,
                $grouping,
                $isSenior
            );
        }

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
                $studentRank  = self::$seniorGradeOrder[$normalized] ?? -1;
                $requiredRank = self::$seniorGradeOrder[$required]   ?? 0;
                if ($studentRank >= $requiredRank) {
                    $count++;
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
                    return $matchedStatus === self::STATUS_PROMOTED
                        ? self::STATUS_TRIAL
                        : $matchedStatus;
                }

                return $averageConditionMet === true
                    ? self::STATUS_PROMOTED
                    : self::STATUS_REPEATED;

            default:
                return $matchedStatus ?? self::STATUS_REPEATED;
        }
    }

    private function mapStatusLabel(?string $status, PromotionSetting $settings): string
    {
        // FIX: normalize here too, as a second line of defense — in case
        // this method is ever called directly with a raw un-normalized
        // rule status_label from somewhere other than evaluate().
        $status = $this->normalizeStatus($status);

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
        $status = $this->normalizeStatus($status);

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
        $status = $this->normalizeStatus($status);

        return match ($status) {
            self::STATUS_PROMOTED      => 'ri-checkbox-circle-line',
            self::STATUS_TRIAL         => 'ri-time-line',
            self::STATUS_SEE_PRINCIPAL => 'ri-eye-line',
            self::STATUS_REPEATED      => 'ri-repeat-line',
            default                    => 'ri-question-line',
        };
    }
}