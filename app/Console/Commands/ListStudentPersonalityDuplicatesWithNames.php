<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Studentpersonalityprofile;

class ListStudentPersonalityDuplicatesWithNames extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'profiles:list-duplicates-with-names';

    /**
     * The console command description.
     */
    protected $description = 'List duplicate Studentpersonalityprofile records with student names and admission numbers (read-only, no changes)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Scanning for duplicate Studentpersonalityprofile records...');

        // Find duplicate groups using raw query for efficiency
        $duplicateGroups = DB::table('studentpersonalityprofiles as spp')
            ->join('studentRegistration as sr', 'sr.id', '=', 'spp.studentid')
            ->select(
                'spp.studentid',
                'sr.admissionNo',
                DB::raw("CONCAT(sr.lastname, ' ', sr.firstname, ' ', COALESCE(sr.othername, '')) as full_name"),
                'spp.schoolclassid',
                'spp.sessionid',
                'spp.termid',
                DB::raw('COUNT(*) as duplicate_count')
            )
            ->groupBy('spp.studentid', 'sr.admissionNo', 'full_name', 'spp.schoolclassid', 'spp.sessionid', 'spp.termid')
            ->having('duplicate_count', '>', 1)
            ->orderBy('duplicate_count', 'desc')
            ->get();

        $totalGroups = $duplicateGroups->count();

        if ($totalGroups === 0) {
            $this->info('No duplicates found! Your data is clean.');
            return 0;
        }

        $this->warn("Found {$totalGroups} students with duplicate profiles.");

        $this->newLine();
        $this->info('Listing duplicate groups:');

        foreach ($duplicateGroups as $index => $group) {
            $this->line(($index + 1) . ". Student ID: {$group->studentid} | Adm No: {$group->admissionNo} | Name: {$group->full_name}");
            $this->line("   Class ID: {$group->schoolclassid} | Session: {$group->sessionid} | Term: {$group->termid} | Copies: {$group->duplicate_count}");

            // Fetch detailed records for this group
            $records = Studentpersonalityprofile::where('studentid', $group->studentid)
                ->where('schoolclassid', $group->schoolclassid)
                ->where('sessionid', $group->sessionid)
                ->where('termid', $group->termid)
                ->orderByDesc('updated_at')
                ->get(['id', 'staffid', 'updated_at', 'classteachercomment', 'guidancescomment', 'principalscomment']);

            foreach ($records as $record) {
                $teacherPreview = $record->classteachercomment ? substr($record->classteachercomment, 0, 60) . (strlen($record->classteachercomment) > 60 ? '...' : '') : '(empty)';
                $guidancePreview = $record->guidancescomment ? substr($record->guidancescomment, 0, 60) . (strlen($record->guidancescomment) > 60 ? '...' : '') : '(empty)';
                $principalPreview = $record->principalscomment ? substr($record->principalscomment, 0, 60) . (strlen($record->principalscomment) > 60 ? '...' : '') : '(empty)';

                $this->line("   → Record ID: {$record->id} | Staff ID: {$record->staffid} | Updated: {$record->updated_at}");
                $this->line("     Teacher Comment: {$teacherPreview}");
                $this->line("     Guidance Comment: {$guidancePreview}");
                $this->line("     Principal Comment: {$principalPreview}");
                $this->newLine();
            }
        }

        $this->newLine();
        $this->warn('This command only displayed information — NO data was changed or deleted.');
        $this->info('Review the list above. If it looks correct, we can proceed to a safe merge.');

        return 0;
    }
}