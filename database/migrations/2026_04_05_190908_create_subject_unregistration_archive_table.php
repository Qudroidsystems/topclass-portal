<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subject_unregistration_archive', function (Blueprint $table) {
            $table->id();

            // Student & subject identifiers
            $table->unsignedBigInteger('studentid');
            $table->unsignedBigInteger('subjectclassid');
            $table->unsignedBigInteger('staffid');
            $table->unsignedBigInteger('termid');
            $table->unsignedBigInteger('sessionid');
            $table->unsignedBigInteger('subjectid');
            $table->unsignedBigInteger('schoolclassid');

            // Snapshot of the broadsheet record ID that was deleted
            // (for audit/display — the actual row is gone after hard delete)
            $table->unsignedBigInteger('broadsheet_record_id')->nullable();

            // Who performed the unregistration
            $table->unsignedBigInteger('unregistered_by')->nullable();

            // Human-readable name given by staff at unregistration time
            $table->string('snapshot_name', 191)->nullable();
            $table->index('snapshot_name', 'archive_snapshot_name');

            // Optional longer description / notes
            $table->text('snapshot_notes')->nullable();

            // Status: 'archived' | 'restored' | 'permanently_deleted'
            $table->string('status', 30)->default('archived');
            $table->index('status');

            // When it was unregistered and when it was actioned (restored / deleted)
            $table->timestamp('unregistered_at')->useCurrent();
            $table->timestamp('actioned_at')->nullable();

            $table->timestamps();

            // Composite indexes for fast lookups
            $table->index(['subjectclassid', 'termid', 'sessionid'], 'archive_subject_term_session');
            $table->index(['studentid', 'sessionid', 'termid'],      'archive_student_session_term');

            // Foreign keys — loose (no cascade) because referenced rows may already
            // be hard-deleted by the time we query the archive
            $table->foreign('studentid')
                  ->references('id')->on('studentRegistration')
                  ->onDelete('cascade');

            $table->foreign('subjectclassid')
                  ->references('id')->on('subjectclass')
                  ->onDelete('cascade');

            $table->foreign('staffid')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->foreign('termid')
                  ->references('id')->on('schoolterm')
                  ->onDelete('cascade');

            $table->foreign('sessionid')
                  ->references('id')->on('schoolsession')
                  ->onDelete('cascade');

            $table->foreign('unregistered_by')
                  ->references('id')->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_unregistration_archive');
    }
};
