<?php

namespace App\Console\Commands;

use App\Models\PromotionSetting;
use App\Models\Studentclass;
use App\Services\PromotionEvaluator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditSeniorCumulativePromotion extends Command
{
    protected $signature = 'promotion:audit-senior-cumulative';
    protected $description = 'Dry-run: show which students\' promotion status would change if senior count_conditions became cumulative (>=) instead of exact-match. Makes no writes.';

    public function handle(PromotionEvaluator $evaluator)
    {
        // 1. Find active senior settings that actually use count_conditions
        $affectedSettings = PromotionSetting::where('is_active', true)
            ->whereHas('schoolclass.classcategories', fn ($q) => $q->where('is_senior', true))
            ->get()
            ->filter(function ($setting) {
                foreach ($setting->promotion_rules ?? [] as $rule) {
                    if (!empty($rule['compulsory_section']['count_conditions'] ?? [])
                        || !empty($rule['other_section']['count_conditions'] ?? [])) {
                        return true;
                    }
                }
                return false;
            });

        if ($affectedSettings->isEmpty()) {
            $this->info('No active senior settings use count_conditions. Nothing affected.');
            return;
        }

        $this->info("Found {$affectedSettings->count()} affected senior setting(s):");
        foreach ($affectedSettings as $s) {
            $this->line("  - [{$s->id}] class {$s->schoolclass_id} \"{$s->rule_set_name}\"");
        }
        $this->newLine();

        $changes = [];

        foreach ($affectedSettings as $setting) {
            $students = Studentclass::where('schoolclassid', $setting->schoolclass_id)
                ->when($setting->session_id, fn ($q) => $q->where('sessionid', $setting->session_id))
                ->when($setting->term_id,    fn ($q) => $q->where('termid', $setting->term_id))
                ->get();

            foreach ($students as $sc) {
                $scores = DB::table('broadsheets')
                    ->join('broadsheet_records', 'broadsheet_records.id', '=', 'broadsheets.broadsheet_record_id')
                    ->join('subject', 'subject.id', '=', 'broadsheet_records.subject_id')
                    ->where('broadsheet_records.student_id', $sc->studentId)
                    ->where('broadsheets.term_id', $sc->termid)
                    ->where('broadsheet_records.session_id', $sc->sessionid)
                    ->where('broadsheet_records.schoolclass_id', $sc->schoolclassid)
                    ->select('subject.id as subject_id', 'subject.subject as subject_name',
                             'broadsheets.total', 'broadsheets.grade')
                    ->get();

                if ($scores->isEmpty()) continue;

                $obtained = $scores->sum(fn ($s) => is_numeric($s->total) ? (float) $s->total : 0);
                $count    = $scores->filter(fn ($s) => is_numeric($s->total))->count();
                $overallAverage = $count > 0 ? round(($obtained / ($count * 100)) * 100, 1) : null;

                // Current (exact-match) result
                $before = $evaluator->evaluate(
                    studentId: $sc->studentId,
                    schoolclassid: $sc->schoolclassid,
                    termid: $sc->termid,
                    sessionid: $sc->sessionid,
                    scores: $scores,
                    overallAverage: $overallAverage
                );

                // Simulated cumulative result
                $after = $this->evaluateWithCumulativeSeniorCounts(
                    $evaluator, $sc->studentId, $sc->schoolclassid, $sc->termid, $sc->sessionid, $scores, $overallAverage
                );

                if ($before['status'] !== $after['status']) {
                    $changes[] = [
                        'student_id' => $sc->studentId,
                        'setting_id' => $setting->id,
                        'class_id'   => $sc->schoolclassid,
                        'before'     => $before['status'] ?? 'null',
                        'after'      => $after['status'] ?? 'null',
                        'average'    => $overallAverage,
                    ];
                }
            }
        }

        if (empty($changes)) {
            $this->info('No students would change status. Safe to ship as a pure bugfix.');
            return;
        }

        $this->warn(count($changes) . ' student(s) would change status:');
        $this->table(
            ['Student ID', 'Setting', 'Class', 'Before', 'After', 'Avg'],
            $changes
        );
    }

    /**
     * Re-runs evaluation with a temporary reflection-based override so senior
     * count_conditions are compared cumulatively (>=) instead of exact-match,
     * without modifying PromotionEvaluator itself yet.
     */
    private function evaluateWithCumulativeSeniorCounts(
        PromotionEvaluator $evaluator,
        int $studentId, int $schoolclassid, int $termid, int $sessionid,
        $scores, ?float $overallAverage
    ): array {
        // Monkey-patch via a subclass would be cleaner long-term, but for a
        // one-off audit we just re-implement the matching decision inline
        // using the same rule set, swapping only the senior count logic.
        $ref = new \ReflectionClass($evaluator);

        $getClassCategory = $ref->getMethod('getClassCategory');
        $getClassCategory->setAccessible(true);
        $classCategory = $getClassCategory->invoke($evaluator, $schoolclassid);
        $isSenior = $classCategory && !empty($classCategory->is_senior);

        if (!$isSenior) {
            return $evaluator->evaluate(
                studentId: $studentId, schoolclassid: $schoolclassid, termid: $termid,
                sessionid: $sessionid, scores: $scores, overallAverage: $overallAverage
            );
        }

        // For senior classes, temporarily relax the grade order lookup so
        // exact match becomes >= rank match. We do this by patching the
        // seniorGradeOrder-based comparison is not exposed cleanly, so
        // instead we just re-run evaluate() as-is (exact match, "before")
        // and separately compute what WOULD match under cumulative logic
        // by inspecting the rule directly. This keeps the audit read-only
        // and avoids touching production code.
        return $this->simulateCumulative($evaluator, $studentId, $schoolclassid, $termid, $sessionid, $scores, $overallAverage);
    }

    private function simulateCumulative(
        PromotionEvaluator $evaluator,
        int $studentId, int $schoolclassid, int $termid, int $sessionid,
        $scores, ?float $overallAverage
    ): array {
        $seniorOrder = ['F9'=>0,'E8'=>1,'D7'=>2,'C6'=>3,'C5'=>4,'C4'=>5,'B3'=>6,'B2'=>7,'A1'=>8];

        $scoreMap = collect($scores)->keyBy(fn ($s) => $s->subject_id);

        $ref = new \ReflectionClass($evaluator);
        $findBestSettings = $ref->getMethod('findBestSettings');
        $findBestSettings->setAccessible(true);
        $settings = $findBestSettings->invoke($evaluator, $schoolclassid, $sessionid, $termid);

        if (!$settings || empty($settings->promotion_rules)) {
            return $evaluator->awaitingResult($overallAverage, 'no settings', $schoolclassid);
        }

        $getCompulsoryIds = $ref->getMethod('getCompulsoryIds');
        $getCompulsoryIds->setAccessible(true);
        $compulsoryIds = $getCompulsoryIds->invoke($evaluator, $schoolclassid, $termid, $sessionid);

        foreach ($settings->promotion_rules as $rule) {
            $ok = true;

            foreach (array_merge(
                $rule['compulsory_section']['count_conditions'] ?? [],
                $rule['other_section']['count_conditions'] ?? []
            ) as $cond) {
                $grade    = strtoupper(trim($cond['grade'] ?? ''));
                $required = (int) ($cond['count'] ?? 0);
                $operator = $cond['operator'] ?? '>=';
                $requiredRank = $seniorOrder[$grade] ?? 0;

                $actual = $scoreMap->filter(function ($s) use ($seniorOrder, $requiredRank) {
                    $rank = $seniorOrder[strtoupper(trim($s->grade ?? ''))] ?? -1;
                    return $rank >= $requiredRank;
                })->count();

                $pass = match ($operator) {
                    '>=' => $actual >= $required, '<=' => $actual <= $required,
                    '='  => $actual === $required, '>'  => $actual > $required,
                    '<'  => $actual < $required,   default => false,
                };
                if (!$pass) { $ok = false; break; }
            }

            if ($ok) {
                return ['status' => $rule['status_label'] ?? 'promoted'];
            }
        }

        return ['status' => 'repeated'];
    }
}