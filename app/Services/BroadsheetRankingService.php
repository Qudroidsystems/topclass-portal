<?php

namespace App\Services;

use App\Models\BroadsheetRankingSetting;

/**
 * UNOFFICIAL best-student ranking for TopClass broadsheets.
 *
 * Works only on the assembled $studentRows from BroadsheetController
 * (fixed CA1/CA2/CA3/Exam structure). Never writes to the database and
 * never touches the official position columns.
 *
 * Returns:
 *   overall     → top N across every row passed in (all arms / all selected classes)
 *   by_arm      → top N inside each arm (grouped by row['class_label'] ?? row['arm'])
 *   by_subject  → top N per subject across every row passed in
 *   rank_map    → [student_id => rank] for the optional Rank column
 *   excluded    → [student_id => reason] for anyone filtered out by eligibility rules
 */
class BroadsheetRankingService
{
    private const FAIL_GRADES = ['F', 'F9'];
    private const DISTINCTION_GRADES = ['A', 'A1'];

    public function rank(
        array $rows,
        BroadsheetRankingSetting $settings,
        ?string $override = null,
        bool $isSenior = false,
        array $compulsorySubjectIds = [],
        array $subjectsMap = [],
        string $basis = 'cum'
    ): array {
        $measures = BroadsheetRankingSetting::MEASURES;
        $measure  = ($override && isset($measures[$override]))
            ? $override
            : (isset($measures[$settings->primary_measure]) ? $settings->primary_measure : 'cum_ave');

        $tiebreakers = array_values(array_unique(array_filter(
            (array) ($settings->tiebreakers ?? []),
            fn ($m) => $m && $m !== $measure && isset($measures[$m])
        )));

        $keys     = array_merge([$measure], $tiebreakers);
        $scoreKey = $basis === 'total' ? 'total' : 'cum';
        $coreIds  = array_map('intval', (array) ($settings->core_subject_ids ?? []));
        $topN     = max(1, (int) ($settings->top_n ?? 3));
        $subTopN  = max(1, (int) ($settings->subject_top_n ?? 3));
        $scope    = $settings->scope ?: 'both';

        // ── Eligibility + measure values ────────────────────────────
        $eligible = [];
        $excluded = [];
        foreach ($rows as $row) {
            $sid    = (int) $row['id'];
            $reason = $this->ineligibleReason($row, $settings, $compulsorySubjectIds, $scoreKey);
            if ($reason !== null) {
                $excluded[$sid] = $reason;
                continue;
            }

            $vals = [];
            foreach ($keys as $k) {
                $vals[] = $this->measureValue($row, $k, $coreIds, $scoreKey);
            }
            if ($vals[0] === null) {
                $excluded[$sid] = 'No value for "' . $measures[$measure] . '"';
                continue;
            }

            $eligible[] = ['row' => $row, 'vals' => $vals];
        }

        // ── Overall ─────────────────────────────────────────────────
        $ranked  = $this->competitionRank($eligible);
        $rankMap = [];
        foreach ($ranked as $item) {
            $rankMap[(int) $item['row']['id']] = $item['rank'];
        }

        // ── Per arm ─────────────────────────────────────────────────
        $byArm = [];
        if (in_array($scope, ['arm', 'both'], true)) {
            $groups = [];
            foreach ($eligible as $item) {
                $groups[$this->groupLabel($item['row'])][] = $item;
            }
            ksort($groups, SORT_NATURAL);
            foreach ($groups as $label => $items) {
                $byArm[$label] = $this->top($this->competitionRank($items), $topN);
            }
        }

        // ── Per subject (no eligibility filter: best in the subject is best in the subject) ──
        $bySubject = $this->rankSubjects($rows, $subjectsMap, $scoreKey, $subTopN);

        return [
            'measure'          => $measure,
            'measure_label'    => $measures[$measure],
            'tiebreakers'      => array_map(fn ($k) => $measures[$k], $tiebreakers),
            'basis'            => $scoreKey,
            'scope'            => $scope,
            'top_n'            => $topN,
            'subject_top_n'    => $subTopN,
            'overall'          => $this->top($ranked, $topN),
            'by_arm'           => $byArm,
            'by_subject'       => $bySubject,
            'rank_map'         => $rankMap,
            'eligible_count'   => count($eligible),
            'excluded'         => $excluded,
            'show_rank_column' => (bool) $settings->show_rank_column,
            'section'          => $settings->section,
        ];
    }

    // =========================================================================
    // Subjects
    // =========================================================================

    private function rankSubjects(array $rows, array $subjectsMap, string $scoreKey, int $topN): array
    {
        $subjectIds = array_keys($subjectsMap);
        if (empty($subjectIds)) {
            foreach ($rows as $row) {
                $subjectIds = array_merge($subjectIds, array_keys($row['subjects'] ?? []));
            }
            $subjectIds = array_unique($subjectIds);
        }

        $out = [];
        foreach ($subjectIds as $subId) {
            $items = [];
            foreach ($rows as $row) {
                $sd = $row['subjects'][$subId] ?? null;
                if (!$sd || !$this->isSat($sd)) {
                    continue;
                }
                $items[] = [
                    'row'   => $row,
                    // score on chosen basis, then term total as tie-breaker
                    'vals'  => [(float) ($sd[$scoreKey] ?? 0), (float) ($sd['total'] ?? 0)],
                    'grade' => $sd['grade'] ?? null,
                ];
            }
            if (empty($items)) {
                continue;
            }

            $out[(int) $subId] = [
                'subject' => $subjectsMap[$subId]['subject_name'] ?? ('Subject #' . $subId),
                'count'   => count($items),
                'top'     => $this->top($this->competitionRank($items), $topN),
            ];
        }

        uasort($out, fn ($a, $b) => strcasecmp($a['subject'], $b['subject']));
        return $out;
    }

    // =========================================================================
    // Measures & eligibility
    // =========================================================================

    private function measureValue(array $row, string $key, array $coreIds, string $scoreKey): ?float
    {
        $sat = $this->satScores($row, $scoreKey);
        if (empty($sat) && $key !== 'num_subjects') {
            return null;
        }

        switch ($key) {
            case 'cum_ave':      return (float) ($row['cum_ave'] ?? 0);
            case 'term_ave':     return (float) ($row['term_ave'] ?? 0);
            case 'total_cum':    return (float) ($row['total_cum'] ?? 0);
            case 'total_term':   return (float) ($row['total_term'] ?? 0);
            case 'num_subjects': return (float) count($sat);
            case 'lowest_score': return (float) min($sat);

            case 'distinctions':
                $n = 0;
                foreach ($row['subjects'] ?? [] as $sd) {
                    if ($this->isSat($sd) && in_array($sd['grade'] ?? '', self::DISTINCTION_GRADES, true)) {
                        $n++;
                    }
                }
                return (float) $n;

            case 'core_ave':
                $core = array_intersect_key($sat, array_flip($coreIds));
                return empty($core) ? null : round(array_sum($core) / count($core), 2);
        }

        return null;
    }

    private function ineligibleReason(array $row, BroadsheetRankingSetting $s, array $compulsory, string $scoreKey): ?string
    {
        $sat = $this->satScores($row, $scoreKey);
        $n   = count($sat);

        if ($n === 0) {
            return 'No scores recorded';
        }

        $min = (int) ($s->min_subjects ?? 0);
        if ($min > 0 && $n < $min) {
            return "Only {$n} subject(s) scored (minimum {$min})";
        }

        if ($s->min_average !== null && $s->min_average !== '') {
            $avg = $scoreKey === 'total' ? (float) ($row['term_ave'] ?? 0) : (float) ($row['cum_ave'] ?? 0);
            if ($avg < (float) $s->min_average) {
                return 'Average ' . number_format($avg, 1) . ' is below ' . number_format((float) $s->min_average, 1);
            }
        }

        if ($s->exclude_failed) {
            foreach ($row['subjects'] ?? [] as $sd) {
                if (!$this->isSat($sd)) continue;
                $score = (float) ($sd[$scoreKey] ?? 0);
                if ($score < 40 || in_array($sd['grade'] ?? '', self::FAIL_GRADES, true)) {
                    return 'Has a failed subject';
                }
            }
        }

        if ($s->require_all_compulsory && !empty($compulsory)) {
            $missing = array_diff(array_map('intval', $compulsory), array_keys($sat));
            if (!empty($missing)) {
                return count($missing) . ' compulsory subject(s) not scored';
            }
        }

        return null;
    }

    /** [subject_id => score on basis] for subjects the student actually sat. */
    private function satScores(array $row, string $scoreKey): array
    {
        $out = [];
        foreach ($row['subjects'] ?? [] as $subId => $sd) {
            if ($this->isSat($sd)) {
                $out[(int) $subId] = (float) ($sd[$scoreKey] ?? 0);
            }
        }
        return $out;
    }

    private function isSat(array $sd): bool
    {
        return (float) ($sd['total'] ?? 0) > 0 || (float) ($sd['cum'] ?? 0) > 0;
    }

    // =========================================================================
    // Ranking helpers
    // =========================================================================

    /** Competition rank (1, 2, 2, 4) on vals[] descending, ties on every key share a place. */
    private function competitionRank(array $items): array
    {
        usort($items, function ($a, $b) {
            foreach ($a['vals'] as $i => $v) {
                $cmp = ($b['vals'][$i] ?? -INF) <=> ($v ?? -INF);
                if ($cmp !== 0) return $cmp;
            }
            return strcasecmp($this->fullName($a['row']), $this->fullName($b['row']));
        });

        $prevSig = null;
        $rank    = 0;
        foreach ($items as $i => &$item) {
            $sig = json_encode(array_map(fn ($v) => $v === null ? null : round($v, 4), $item['vals']));
            if ($sig !== $prevSig) {
                $rank    = $i + 1;
                $prevSig = $sig;
            }
            $item['rank'] = $rank;
        }
        unset($item);

        return $items;
    }

    /** Everyone ranked within top N (ties included, so "Top 3" can show 4 names). */
    private function top(array $ranked, int $n): array
    {
        $out = [];
        foreach ($ranked as $item) {
            if ($item['rank'] > $n) break;
            $row   = $item['row'];
            $out[] = [
                'id'          => (int) $row['id'],
                'name'        => $this->fullName($row),
                'admissionno' => $row['admissionno'] ?? '',
                'arm'         => $this->groupLabel($row),
                'picture'     => $row['picture'] ?? null,
                'rank'        => $item['rank'],
                'value'       => round((float) $item['vals'][0], 2),
                'extra'       => array_map(fn ($v) => $v === null ? null : round($v, 2), array_slice($item['vals'], 1)),
                'grade'       => $item['grade'] ?? null,
            ];
        }
        return $out;
    }

    private function groupLabel(array $row): string
    {
        $label = trim((string) ($row['class_label'] ?? ''));
        if ($label === '') {
            $label = trim((string) ($row['arm'] ?? ''));
        }
        return $label !== '' ? $label : '—';
    }

    private function fullName(array $row): string
    {
        return trim(strtoupper($row['lastname'] ?? '') . ', ' . ($row['firstname'] ?? ''), ', ');
    }
}
