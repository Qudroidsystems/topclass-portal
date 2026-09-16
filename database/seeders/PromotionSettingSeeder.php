<?php

namespace Database\Seeders;

use App\Models\PromotionSetting;
use App\Models\Schoolclass;
use Illuminate\Database\Seeder;

class PromotionSettingSeeder extends Seeder
{
    /**
     * Seed a default promotion_setting row for every schoolclass,
     * recreating the hardcoded senior / junior promotion criteria.
     *
     * Safe to run multiple times — uses updateOrCreate so it won't duplicate.
     */
    public function run(): void
    {
        $classes = Schoolclass::with('classcategory')->get();

        if ($classes->isEmpty()) {
            $this->command->warn('No school classes found. Nothing to seed.');
            return;
        }

        $created = 0;
        $updated = 0;

        foreach ($classes as $class) {
            $category = $class->classcategory;

            if (!$category) {
                $this->command->warn("Skipping class #{$class->id} ({$class->schoolclass}) — no class category assigned.");
                continue;
            }

            $isSenior = (bool) $category->is_senior;

            $rules = $isSenior
                ? $this->seniorRules()
                : $this->juniorRules();

            $existing = PromotionSetting::where('schoolclass_id', $class->id)
                ->whereNull('session_id')
                ->whereNull('term_id')
                ->first();

            PromotionSetting::updateOrCreate(
                [
                    'schoolclass_id' => $class->id,
                    'session_id'     => null,
                    'term_id'        => null,
                ],
                [
                    'rule_set_name'          => 'Default ' . ($isSenior ? 'Senior' : 'Junior') . ' Criteria',
                    'priority'               => 999,
                    'promoted_label'         => 'PROMOTED',
                    'trial_label'            => 'PROMOTED ON TRIAL',
                    'see_principal_label'    => 'PARENTS TO SEE PRINCIPAL',
                    'repeat_label'           => 'ADVISED TO REPEAT/PARENTS TO SEE PRINCIPAL',
                    'rule_logic'             => 'grade_count',
                    'promotion_pass_average' => null,
                    'promotion_rules'        => $rules,
                    'is_active'              => true,
                    'is_default'             => true,
                    'template_id'            => null,
                ]
            );

            $existing ? $updated++ : $created++;
        }

        $this->command->info("PromotionSettingSeeder: {$created} created, {$updated} updated.");
    }

    // =========================================================================
    // SENIOR RULES (A1–F9 scale)
    // =========================================================================

    private function seniorRules(): array
    {
        return [
            // ── Rule 1: Promoted ─────────────────────────────────────────────
            [
                'rule_name'      => 'Senior — Full Promotion',
                'status_label'   => 'promoted',
                'priority'       => 1,
                'grade_grouping' => 'exact',
                'compulsory_section' => [
                    'subjects'         => [],
                    'count_conditions' => [
                        [
                            'grade'    => 'C6',
                            'operator' => '>=',
                            'count'    => 5,
                            'scope'    => 'compulsory_only',
                        ],
                    ],
                ],
                'other_section' => [
                    'count_conditions' => [
                        [
                            'grade'    => 'C6',
                            'operator' => '>=',
                            'count'    => 5,
                            'scope'    => 'all',
                        ],
                    ],
                ],
                'average_condition' => ['enabled' => false],
            ],

            // ── Rule 2: Promoted on Trial ────────────────────────────────────
            [
                'rule_name'      => 'Senior — Trial Promotion',
                'status_label'   => 'trial',
                'priority'       => 2,
                'grade_grouping' => 'exact',
                'compulsory_section' => [
                    'subjects'         => [],
                    'count_conditions' => [
                        [
                            'grade'    => 'C6',
                            'operator' => '>=',
                            'count'    => 4,
                            'scope'    => 'compulsory_only',
                        ],
                    ],
                ],
                'other_section' => [
                    'count_conditions' => [
                        [
                            'grade'    => 'C6',
                            'operator' => '>=',
                            'count'    => 4,
                            'scope'    => 'all',
                        ],
                    ],
                ],
                'average_condition' => ['enabled' => false],
            ],

            // ── Rule 3: See Principal (2 compulsory failed) ─────────────────
            [
                'rule_name'      => 'Senior — Two Compulsory Fails',
                'status_label'   => 'see_principal',
                'priority'       => 3,
                'grade_grouping' => 'exact',
                'compulsory_section' => [
                    'subjects'         => [],
                    'count_conditions' => [
                        [
                            'grade'    => 'C6',
                            'operator' => '>=',
                            'count'    => 4,
                            'scope'    => 'all',
                        ],
                    ],
                ],
                'other_section' => [
                    'count_conditions' => [],
                ],
                'average_condition' => ['enabled' => false],
            ],

            // ── Rule 4: Catch-all — Repeat ──────────────────────────────────
            [
                'rule_name'      => 'Senior — Catch-all Repeat',
                'status_label'   => 'repeat',
                'priority'       => 99,
                'grade_grouping' => 'exact',
                'compulsory_section' => ['subjects' => [], 'count_conditions' => []],
                'other_section'      => ['count_conditions' => []],
                'average_condition'  => ['enabled' => false],
            ],
        ];
    }

    // =========================================================================
    // JUNIOR RULES (A–F scale)
    // =========================================================================

    private function juniorRules(): array
    {
        return [
            // ── Rule 1: Promoted ─────────────────────────────────────────────
            [
                'rule_name'      => 'Junior — Full Promotion',
                'status_label'   => 'promoted',
                'priority'       => 1,
                'grade_grouping' => 'exact',
                'compulsory_section' => [
                    'subjects'         => [],
                    'count_conditions' => [
                        [
                            'grade'    => 'C',
                            'operator' => '>=',
                            'count'    => 5,
                            'scope'    => 'compulsory_only',
                        ],
                    ],
                ],
                'other_section' => [
                    'count_conditions' => [
                        [
                            'grade'    => 'C',
                            'operator' => '>=',
                            'count'    => 5,
                            'scope'    => 'all',
                        ],
                    ],
                ],
                'average_condition' => ['enabled' => false],
            ],

            // ── Rule 2: Promoted on Trial ────────────────────────────────────
            [
                'rule_name'      => 'Junior — Trial Promotion',
                'status_label'   => 'trial',
                'priority'       => 2,
                'grade_grouping' => 'exact',
                'compulsory_section' => [
                    'subjects'         => [],
                    'count_conditions' => [
                        [
                            'grade'    => 'C',
                            'operator' => '>=',
                            'count'    => 1,
                            'scope'    => 'compulsory_only',
                        ],
                    ],
                ],
                'other_section' => [
                    'count_conditions' => [
                        [
                            'grade'    => 'C',
                            'operator' => '>=',
                            'count'    => 4,
                            'scope'    => 'all',
                        ],
                    ],
                ],
                'average_condition' => ['enabled' => false],
            ],

            // ── Rule 3: See Principal ────────────────────────────────────────
            [
                'rule_name'      => 'Junior — Credits Without Compulsory',
                'status_label'   => 'see_principal',
                'priority'       => 3,
                'grade_grouping' => 'exact',
                'compulsory_section' => [
                    'subjects'         => [],
                    'count_conditions' => [
                        [
                            'grade'    => 'C',
                            'operator' => '=',
                            'count'    => 0,
                            'scope'    => 'compulsory_only',
                        ],
                    ],
                ],
                'other_section' => [
                    'count_conditions' => [
                        [
                            'grade'    => 'C',
                            'operator' => '>=',
                            'count'    => 4,
                            'scope'    => 'all',
                        ],
                    ],
                ],
                'average_condition' => ['enabled' => false],
            ],

            // ── Rule 4: Catch-all — Repeat ──────────────────────────────────
            [
                'rule_name'      => 'Junior — Catch-all Repeat',
                'status_label'   => 'repeat',
                'priority'       => 99,
                'grade_grouping' => 'exact',
                'compulsory_section' => ['subjects' => [], 'count_conditions' => []],
                'other_section'      => ['count_conditions' => []],
                'average_condition'  => ['enabled' => false],
            ],
        ];
    }
}