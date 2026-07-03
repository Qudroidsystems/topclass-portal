<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Studentpersonalityprofile;

class MergeStudentPersonalityProfilesSafely extends Command
{
    protected $signature = 'profiles:merge-duplicates-safely';
    protected $description = 'Safely merge duplicate Studentpersonalityprofile records (same student + class + session + term)';

    public function handle()
    {
        $this->info('Starting SAFE merge of duplicate Studentpersonalityprofile records...');

        $duplicateGroups = Studentpersonalityprofile::select('studentid', 'schoolclassid', 'sessionid', 'termid')
            ->groupBy('studentid', 'schoolclassid', 'sessionid', 'termid')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $totalGroups = $duplicateGroups->count();
        $this->info("Found {$totalGroups} groups with duplicates.");

        if ($totalGroups === 0) {
            $this->info('No duplicates to merge.');
            return 0;
        }

        $merged = 0;
        $deleted = 0;

        foreach ($duplicateGroups as $group) {
            $records = Studentpersonalityprofile::where('studentid', $group->studentid)
                ->where('schoolclassid', $group->schoolclassid)
                ->where('sessionid', $group->sessionid)
                ->where('termid', $group->termid)
                ->orderByDesc('updated_at')
                ->get();

            $keeper = $records->shift(); // Latest record
            $toDelete = $records;

            $changes = false;

            // Merge principal's comment if missing
            if (empty($keeper->principalscomment)) {
                foreach ($toDelete as $old) {
                    if (!empty($old->principalscomment)) {
                        $keeper->principalscomment = $old->principalscomment;
                        $changes = true;
                        break;
                    }
                }
            }

            // Safety net: merge any other missing fields
            $fields = ['classteachercomment', 'guidancescomment', 'remark_on_other_activities', 'no_of_times_school_absent', 'signature'];
            foreach ($fields as $field) {
                if (empty($keeper->$field)) {
                    foreach ($toDelete as $old) {
                        if (!empty($old->$field)) {
                            $keeper->$field = $old->$field;
                            $changes = true;
                            break;
                        }
                    }
                }
            }

            if ($changes) {
                $keeper->save();
                $this->line("Merged comments for student ID {$group->studentid} (Class {$group->schoolclassid}, Session {$group->sessionid}, Term {$group->termid})");
            }

            $deletedIds = $toDelete->pluck('id')->toArray();
            Studentpersonalityprofile::whereIn('id', $deletedIds)->delete();
            $deleted += count($deletedIds);
            $merged++;
        }

        $this->newLine();
        $this->info("Safe merge complete!");
        $this->info("Processed {$merged} duplicate groups");
        $this->info("Deleted {$deleted} old records");

        $this->warn("Now run the unique constraint migration to prevent future duplicates.");

        return 0;
    }
}