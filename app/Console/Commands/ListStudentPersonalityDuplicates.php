<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Studentpersonalityprofile;

class ListStudentPersonalityDuplicates extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'profiles:list-duplicates';

    /**
     * The console command description.
     */
    protected $description = 'List all duplicate Studentpersonalityprofile records (same student + class + session + term) without deleting anything';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Scanning for duplicate Studentpersonalityprofile records...');

        // Find groups with more than one record
        $duplicateGroups = Studentpersonalityprofile::select(
                'studentid',
                'schoolclassid',
                'sessionid',
                'termid',
                \DB::raw('COUNT(*) as duplicate_count')
            )
            ->groupBy('studentid', 'schoolclassid', 'sessionid', 'termid')
            ->having('duplicate_count', '>', 1)
            ->orderBy('duplicate_count', 'desc')
            ->get();

        $totalGroups = $duplicateGroups->count();
        $totalDuplicateRecords = $duplicateGroups->sum('duplicate_count');

        if ($totalGroups === 0) {
            $this->info('No duplicates found! Your data is clean.');
            return 0;
        }

        $this->warn("Found {$totalGroups} groups with duplicates (total of {$totalDuplicateRecords} records involved).");

        $this->newLine();
        $this->info('Listing duplicate groups:');

        foreach ($duplicateGroups as $index => $group) {
            $this->line(($index + 1) . ". Student ID: {$group->studentid} | Class ID: {$group->schoolclassid} | Session: {$group->sessionid} | Term: {$group->termid} | Copies: {$group->duplicate_count}");

            // Show details of each record in the group
            $records = Studentpersonalityprofile::where('studentid', $group->studentid)
                ->where('schoolclassid', $group->schoolclassid)
                ->where('sessionid', $group->sessionid)
                ->where('termid', $group->termid)
                ->orderByDesc('updated_at')
                ->get(['id', 'staffid', 'updated_at', 'classteachercomment', 'guidancescomment', 'principalscomment']);

            foreach ($records as $record) {
                $teacherComment = $record->classteachercomment ? substr($record->classteachercomment, 0, 50) . '...' : '(empty)';
                $guidanceComment = $record->guidancescomment ? substr($record->guidancescomment, 0, 50) . '...' : '(empty)';
                $principalComment = $record->principalscomment ? substr($record->principalscomment, 0, 50) . '...' : '(empty)';

                $this->line("   → Record ID: {$record->id} | Staff ID: {$record->staffid} | Updated: {$record->updated_at}");
                $this->line("     Teacher: {$teacherComment}");
                $this->line("     Guidance: {$guidanceComment}");
                $this->line("     Principal: {$principalComment}");
                $this->newLine();
            }
        }

        $this->newLine();
        $this->warn('This command only displayed duplicates — nothing was changed or deleted.');
        $this->info('If the duplicates look safe to merge, you can later run the merge command.');

        return 0;
    }
}