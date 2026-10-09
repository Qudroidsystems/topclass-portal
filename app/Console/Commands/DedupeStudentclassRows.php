<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Removes exact duplicate studentclass rows: more than one row for the same
 * student + class + session + term. The oldest row (lowest id) is kept.
 *
 * Note: one row per TERM is normal (a promoted student gets terms 1, 2 and 3
 * of the new session), so those are not touched. Dry run by default.
 */
class DedupeStudentclassRows extends Command
{
    protected $signature = 'studentclass:dedupe {--force : Actually delete the duplicate rows}';
    protected $description = 'List (or with --force delete) duplicate studentclass rows for the same student/class/session/term, keeping the oldest.';

    public function handle()
    {
        $groups = DB::table('studentclass')
            ->select('studentId', 'schoolclassid', 'sessionid', 'termid', DB::raw('MIN(id) as keep_id'), DB::raw('COUNT(*) as cnt'))
            ->groupBy('studentId', 'schoolclassid', 'sessionid', 'termid')
            ->having('cnt', '>', 1)
            ->get();

        if ($groups->isEmpty()) {
            $this->info('No duplicate studentclass rows found.');
            return self::SUCCESS;
        }

        $extra = $groups->sum(fn ($g) => $g->cnt - 1);
        $this->table(
            ['studentId', 'schoolclassid', 'sessionid', 'termid', 'rows', 'keeping id'],
            $groups->map(fn ($g) => [$g->studentId, $g->schoolclassid, $g->sessionid, $g->termid, $g->cnt, $g->keep_id])
        );
        $this->info("{$groups->count()} duplicated enrolment(s), {$extra} extra row(s).");

        if (!$this->option('force')) {
            $this->warn('Dry run: nothing deleted. Back up the database, then re-run with --force to delete the extra rows.');
            return self::SUCCESS;
        }

        $deleted = 0;
        DB::transaction(function () use ($groups, &$deleted) {
            foreach ($groups as $g) {
                $deleted += DB::table('studentclass')
                    ->where('studentId', $g->studentId)
                    ->where('schoolclassid', $g->schoolclassid)
                    ->where('sessionid', $g->sessionid)
                    ->where('termid', $g->termid)
                    ->where('id', '!=', $g->keep_id)
                    ->delete();
            }
        });

        $this->info("Deleted {$deleted} duplicate row(s).");
        return self::SUCCESS;
    }
}
