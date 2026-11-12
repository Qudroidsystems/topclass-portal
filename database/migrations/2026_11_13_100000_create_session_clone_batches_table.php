<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Snapshots for the "Clone Session / Term Setup" feature.
 *
 * Every clone run creates one batch. Every record the run inserts is listed in
 * session_clone_batch_items together with a JSON copy of the row, so the run
 * can be reviewed, downloaded and reverted later.
 *
 * Plain ids only (no FK constraints to legacy tables) so it drops cleanly onto
 * production.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('session_clone_batches')) {
            Schema::create('session_clone_batches', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('user_id')->nullable()->index();
                $t->string('mode', 20);                              // term | session | session_all
                $t->unsignedBigInteger('source_session_id')->index();
                $t->string('source_term', 10);                       // term id or "all"
                $t->unsignedBigInteger('target_session_id')->index();
                $t->string('target_term', 10)->nullable();           // null when "all"
                $t->json('modules');
                $t->json('totals')->nullable();                      // {source, create, skip}
                $t->json('module_totals')->nullable();               // per-module counts
                $t->string('status', 30)->default('completed');      // completed | partially_reverted | reverted
                $t->timestamp('reverted_at')->nullable();
                $t->unsignedBigInteger('reverted_by')->nullable();
                $t->json('revert_summary')->nullable();
                $t->timestamps();
            });
        }

        if (!Schema::hasTable('session_clone_batch_items')) {
            Schema::create('session_clone_batch_items', function (Blueprint $t) {
                $t->id();
                $t->unsignedBigInteger('batch_id')->index();
                $t->string('module', 40);
                $t->string('table_name', 80);
                $t->unsignedBigInteger('record_id');
                $t->string('label')->nullable();                     // human-readable description
                $t->json('snapshot');                                // copy of the inserted row
                $t->timestamp('reverted_at')->nullable();
                $t->string('kept_reason')->nullable();               // why it was not reverted
                $t->timestamps();

                $t->index(['table_name', 'record_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('session_clone_batch_items');
        Schema::dropIfExists('session_clone_batches');
    }
};
