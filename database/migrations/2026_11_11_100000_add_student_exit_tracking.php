<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets the school record that a student has left (Left / Transferred /
 * Graduated / Expelled) instead of only Active / Inactive, with when and why,
 * and keeps a history so a leaver can be reactivated with their class
 * enrolments restored.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Was ENUM('Active','Inactive'); the leaver statuses need more values.
        Schema::table('studentRegistration', function (Blueprint $table) {
            $table->string('student_status', 30)->default('Active')->change();
        });

        Schema::table('studentRegistration', function (Blueprint $table) {
            if (!Schema::hasColumn('studentRegistration', 'exit_date')) {
                $table->date('exit_date')->nullable();
                $table->text('exit_reason')->nullable();
                $table->string('exit_destination')->nullable();     // school transferred to, etc.
                $table->unsignedBigInteger('exit_session_id')->nullable(); // first session/term the
                $table->unsignedBigInteger('exit_term_id')->nullable();    // student is no longer in
                $table->unsignedBigInteger('exit_class_id')->nullable();   // last class attended
                $table->unsignedBigInteger('exit_recorded_by')->nullable();
            }
        });

        if (!Schema::hasTable('student_status_history')) {
            Schema::create('student_status_history', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('student_id')->index();
                $table->string('from_status', 30)->nullable();
                $table->string('to_status', 30);
                $table->unsignedBigInteger('effective_session_id')->nullable();
                $table->unsignedBigInteger('effective_term_id')->nullable();
                $table->unsignedBigInteger('last_class_id')->nullable();
                $table->date('exit_date')->nullable();
                $table->text('reason')->nullable();
                $table->string('destination')->nullable();
                // Class enrolments removed when the student left, so a
                // reactivation can put them back exactly as they were.
                $table->json('archived_enrolments')->nullable();
                $table->unsignedBigInteger('changed_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_status_history');

        Schema::table('studentRegistration', function (Blueprint $table) {
            $table->dropColumn([
                'exit_date', 'exit_reason', 'exit_destination', 'exit_session_id',
                'exit_term_id', 'exit_class_id', 'exit_recorded_by',
            ]);
        });
    }
};
