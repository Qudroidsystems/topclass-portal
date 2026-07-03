<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Studentpersonalityprofile;

class MergeStudentPersonalityProfiles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'profiles:merge-duplicates';

    /**
     * The console command description.
     */
    protected $description = 'Safely merge duplicate Studentpersonalityprofile records (one per student + class + session + term)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting safe merge of duplicate Studentpersonalityprofile records...');

        // Find groups with duplicates
        $duplicateGroups = Studentpersonalityprofile::select('studentid', 'schoolclassid', 'sessionid', 'termid')
            ->groupBy('studentid', 'schoolclassid', 'sessionid', 'termid')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $totalGroups = $duplicateGroups->count();
        $this->info("Found {$totalGroups} groups with duplicate profiles.");

        if ($totalGroups === 0) {
            $this->info('No duplicates found. Your data is already clean!');
            return 0;
        }

        $merged = 0;
        $deleted = 0;

        foreach ($duplicateGroups as $group) {
            // Fetch all records in the duplicate group, ordered by latest update
            $records = Studentpersonalityprofile::where('studentid', $group->studentid)
                ->where('schoolclassid', $group->schoolclassid)
                ->where('sessionid', $group->sessionid)
                ->where('termid', $group->termid)
                ->orderByDesc('updated_at')
                ->get();

            // Keep the most recently updated record
            $keeper = $records->shift(); // Remove and return the first (latest)
            $toDelete = $records;

            $changesMade = false;

            // Fields to merge: if keeper is empty/null, fill from duplicates
            $fieldsToMerge = [
                'classteachercomment',
                'guidancescomment',
                'principalscomment',
                'remark_on_other_activities',
                'no_of_times_school_absent',
                'signature',
                'staffid',
                // Add more fields here if needed (e.g., punctuality, neatness, etc.)
            ];

            foreach ($fieldsToMerge as $field) {
                if (empty($keeper->$field) || is_null($keeper->$field)) {
                    foreach ($toDelete as $record) {
                        if (!empty($record->$field) || !is_null($record->$field)) {
                            $keeper->$field = $record->$field;
                            $changesMade = true;
                            break; // Take the first available
                        }
                    }
                }
            }

            // Save the keeper if we added any missing data
            if ($changesMade) {
                $keeper->save();
                $this->line("  → Merged missing data into record ID {$keeper->id} for student {$group->studentid}");
            }

            // Delete the duplicate records
            $deletedIds = $toDelete->pluck('id')->toArray();
            Studentpersonalityprofile::whereIn('id', $deletedIds)->delete();
            $deleted += count($deletedIds);
            $merged++;
        }

        $this->newLine();
        $this->info('Merge completed successfully!');
        $this->info("Groups processed: {$merged}");
        $this->info("Duplicate records deleted: {$deleted}");

        $this->newLine();
        $this->warn('You can now safely add the unique constraint with a migration.');
        $this->line('Next: Create and run the unique constraint migration.');

        return 0;
    }
}