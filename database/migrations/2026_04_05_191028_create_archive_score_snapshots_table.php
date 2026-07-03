<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per student × assessment captured at the moment of unregistration.
        // All identifiers are denormalised so the snapshot remains self-contained
        // even after the source broadsheet rows have been hard-deleted.
        Schema::create('archive_score_snapshots', function (Blueprint $table) {
            $table->id();

            // Links back to the archive record (many scores per archive entry)
            $table->unsignedBigInteger('archive_id');

            // The broadsheet.id that existed at snapshot time (audit only —
            // the broadsheet row is deleted during unregistration)
            $table->unsignedBigInteger('broadsheet_id');

            // Denormalised context (student / subject / class / term / session)
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('schoolclass_id');
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('term_id');
            $table->unsignedBigInteger('subjectclass_id');
            $table->unsignedBigInteger('staff_id');

            // Assessment / sub-assessment details (denormalised names)
            $table->unsignedBigInteger('assessment_id');
            $table->string('assessment_name', 100)->nullable();

            $table->unsignedBigInteger('sub_assessment_id')->nullable();
            $table->string('sub_assessment_name', 100)->nullable();

            // The actual score captured at snapshot time
            $table->decimal('score', 8, 2)->default(0.00);

            // 'assessment' or 'sub_assessment'
            $table->string('score_type', 20)->default('assessment');

            $table->timestamps();

            // Indexes
            $table->index('archive_id', 'snap_archive_id');
            $table->index(['student_id', 'session_id', 'term_id'], 'snap_student_session_term');

            // CASCADE: when the archive row is hard-deleted, scores go with it
            $table->foreign('archive_id')
                  ->references('id')
                  ->on('subject_unregistration_archive')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_score_snapshots');
    }
};
