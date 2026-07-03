<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Studentpersonalityprofile;

class RegeneratePrincipalsComments extends Command
{
    protected $signature = 'principals:regenerate-comments';
    protected $description = 'Regenerate all existing principal comments to use student name and proper pronouns (He/She, his/her)';

    public function handle()
    {
        $this->info('Starting regeneration of principal comments...');

        // Fetch all profiles with a principal comment
        $profiles = Studentpersonalityprofile::whereNotNull('principalscomment')
            ->where('principalscomment', '!=', '')
            ->with('student') // Assuming you have a relationship 'student' -> belongsTo(StudentRegistration::class, 'studentid')
            ->select('id', 'studentid', 'schoolclassid', 'sessionid', 'termid', 'principalscomment')
            ->chunk(100, function ($profiles) {
                $updated = 0;

                foreach ($profiles as $profile) {
                    $student = $profile->student;

                    if (!$student) {
                        $this->warn("Student not found for profile ID {$profile->id}, skipping...");
                        continue;
                    }

                    // Get student's first name and gender
                    $firstName = $student->firstname ?? 'Student';
                    $gender = strtoupper($student->gender ?? 'MALE'); // Default to MALE if missing

                    $pronoun = $gender === 'MALE' ? 'He' : 'She';
                    $possessive = $gender === 'MALE' ? 'his' : 'her';

                    // Here you can reuse the same logic as in your controller
                    // For simplicity, we'll just replace patterns like "BEST should work harder..." with pronoun version
                    $oldComment = $profile->principalscomment;
                    $newComment = $oldComment;

                    // Pattern 1: Replace lines like "\n\nBEST should work harder..." with pronoun version
                    $newComment = preg_replace_callback(
                        '/\n\n[A-Z]+ should work harder in .+ to improve\./i',
                        function ($matches) use ($pronoun, $possessive) {
                            $line = $matches[0];
                            // Extract the subject part
                            if (preg_match('/in (.+) to improve\./i', $line, $subjectMatch)) {
                                $subjects = $subjectMatch[1];
                                return "\n\n$pronoun should work harder in $subjects to improve $possessive performance.";
                            }
                            return $line;
                        },
                        $newComment
                    );

                    // Pattern 2: If the main comment uses "You" -> replace with name
                    $newComment = preg_replace_callback(
                        '/^You\s+/m',
                        function () use ($firstName) {
                            return "$firstName, you ";
                        },
                        $newComment
                    );

                    // Optional: Ensure main comment uses name at the start
                    if (!str_contains($newComment, $firstName)) {
                        // If comment doesn't already have the name, prepend it where appropriate
                        $newComment = preg_replace(
                            '/^(Excellent|A very good|Good|Average|You can do better|You need|Wake up)/i',
                            "$firstName, $1",
                            $newComment,
                            1
                        );
                    }

                    if ($newComment !== $oldComment) {
                        $profile->principalscomment = $newComment;
                        $profile->save();
                        $updated++;
                        $this->line("Updated comment for student ID {$student->id} ({$firstName})");
                    }
                }

                $this->info("Batch processed: $updated comments updated.");
            });

        $this->newLine();
        $this->info('Principal comments regeneration completed successfully!');
        $this->warn('All existing comments have been updated to use student name and proper pronouns.');

        return 0;
    }
}