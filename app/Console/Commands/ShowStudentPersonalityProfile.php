<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Studentpersonalityprofile;

class ShowStudentPersonalityProfile extends Command
{
    protected $signature = 'profiles:show {studentid : The student ID to show profiles for}';
    protected $description = 'Show all Studentpersonalityprofile records for a specific student ID (read-only)';

    public function handle()
    {
        $studentId = $this->argument('studentid');

        // Fetch student details
        $student = DB::table('studentRegistration')
            ->where('id', $studentId)
            ->select('id', 'admissionNo', 'lastname', 'firstname', 'othername')
            ->first();

        if (!$student) {
            $this->error("Student with ID {$studentId} not found in studentRegistration table.");
            return 1;
        }

        $fullName = trim("{$student->lastname} {$student->firstname} {$student->othername}");

        $this->info("Student ID: {$student->id}");
        $this->info("Admission No: {$student->admissionNo}");
        $this->info("Full Name: {$fullName}");
        $this->newLine();

        // Fetch all personality profiles for this student
        $profiles = Studentpersonalityprofile::where('studentid', $studentId)
            ->orderByDesc('updated_at')
            ->get(['id', 'schoolclassid', 'sessionid', 'termid', 'staffid', 'updated_at', 'created_at',
                'classteachercomment', 'guidancescomment', 'principalscomment', 'remark_on_other_activities']);

        if ($profiles->isEmpty()) {
            $this->info('No personality profile records found for this student.');
            return 0;
        }

        $this->info("Found {$profiles->count()} profile record(s):");
        $this->newLine();

        foreach ($profiles as $index => $profile) {
            $this->line(($index + 1) . ". Record ID: {$profile->id}");
            $this->line("   Class ID: {$profile->schoolclassid} | Session: {$profile->sessionid} | Term: {$profile->termid}");
            $this->line("   Staff ID: {$profile->staffid}");
            $this->line("   Created: {$profile->created_at}");
            $this->line("   Updated: {$profile->updated_at}");
            $this->newLine();

            $this->line("   Class Teacher Comment:");
            $this->line("   " . ($profile->classteachercomment ?: '(empty)'));
            $this->newLine();

            $this->line("   Guidance Counselor Comment:");
            $this->line("   " . ($profile->guidancescomment ?: '(empty)'));
            $this->newLine();

            $this->line("   Principal's Comment:");
            $this->line("   " . ($profile->principalscomment ?: '(empty)'));
            $this->newLine();

            $this->line("   Remark on Other Activities:");
            $this->line("   " . ($profile->remark_on_other_activities ?: '(empty)'));
            $this->newLine(2);
        }

        $this->warn('This was a read-only operation — nothing was changed.');

        return 0;
    }
}