<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $archiveTable = 'subject_unregistration_archive';
    private string $snapshotsTable = 'archive_score_snapshots';

    public function up(): void
    {
        // ── 1. Add snapshot name/notes to archive table (if missing) ──────────
        if (!Schema::hasColumn($this->archiveTable, 'snapshot_name')) {
            Schema::table($this->archiveTable, function (Blueprint $table) {
                $table->string('snapshot_name', 191)->nullable()->after('unregistered_by');
            });
        }

        if (!Schema::hasColumn($this->archiveTable, 'snapshot_notes')) {
            Schema::table($this->archiveTable, function (Blueprint $table) {
                $table->text('snapshot_notes')->nullable()->after('snapshot_name');
            });
        }

        if (!$this->indexExists($this->archiveTable, 'archive_snapshot_name')) {
            Schema::table($this->archiveTable, function (Blueprint $table) {
                $table->index('snapshot_name', 'archive_snapshot_name');
            });
        }

        // ── 2. Create archive_score_snapshots (if missing) ────────────────────
        if (!Schema::hasTable($this->snapshotsTable)) {
            Schema::create($this->snapshotsTable, function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('archive_id');
                $table->unsignedBigInteger('broadsheet_id');

                $table->unsignedBigInteger('student_id');
                $table->unsignedBigInteger('subject_id');
                $table->unsignedBigInteger('schoolclass_id');
                $table->unsignedBigInteger('session_id');
                $table->unsignedBigInteger('term_id');
                $table->unsignedBigInteger('subjectclass_id');
                $table->unsignedBigInteger('staff_id');

                $table->unsignedBigInteger('assessment_id');
                $table->string('assessment_name', 100)->nullable();
                $table->unsignedBigInteger('sub_assessment_id')->nullable();
                $table->string('sub_assessment_name', 100)->nullable();

                $table->decimal('score', 8, 2)->default(0.00);

                $table->string('score_type', 20)->default('assessment');

                $table->timestamps();

                $table->index('archive_id', 'snap_archive_id');
                $table->index(['student_id', 'session_id', 'term_id'], 'snap_student_session_term');
            });
        } else {
            // Table exists — make sure columns are there too
            $this->ensureSnapshotColumns();
        }

        // ── 3. Add FK on archive_id (if missing) ──────────────────────────────
        if (!$this->foreignKeyExistsOnColumn($this->snapshotsTable, 'archive_id')) {
            Schema::table($this->snapshotsTable, function (Blueprint $table) {
                $table->foreign('archive_id', 'snap_archive_id_foreign')
                      ->references('id')
                      ->on('subject_unregistration_archive')
                      ->onDelete('cascade');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists($this->snapshotsTable);

        if (Schema::hasColumn($this->archiveTable, 'snapshot_name')
            && $this->indexExists($this->archiveTable, 'archive_snapshot_name')) {
            Schema::table($this->archiveTable, function (Blueprint $table) {
                $table->dropIndex('archive_snapshot_name');
            });
        }

        $drop = [];
        if (Schema::hasColumn($this->archiveTable, 'snapshot_name'))  $drop[] = 'snapshot_name';
        if (Schema::hasColumn($this->archiveTable, 'snapshot_notes')) $drop[] = 'snapshot_notes';

        if ($drop) {
            Schema::table($this->archiveTable, function (Blueprint $table) use ($drop) {
                $table->dropColumn($drop);
            });
        }
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function ensureSnapshotColumns(): void
    {
        $columns = [
            'broadsheet_id' => fn (Blueprint $t) => $t->unsignedBigInteger('broadsheet_id'),
            'student_id' => fn (Blueprint $t) => $t->unsignedBigInteger('student_id'),
            'subject_id' => fn (Blueprint $t) => $t->unsignedBigInteger('subject_id'),
            'schoolclass_id' => fn (Blueprint $t) => $t->unsignedBigInteger('schoolclass_id'),
            'session_id' => fn (Blueprint $t) => $t->unsignedBigInteger('session_id'),
            'term_id' => fn (Blueprint $t) => $t->unsignedBigInteger('term_id'),
            'subjectclass_id' => fn (Blueprint $t) => $t->unsignedBigInteger('subjectclass_id'),
            'staff_id' => fn (Blueprint $t) => $t->unsignedBigInteger('staff_id'),
            'assessment_id' => fn (Blueprint $t) => $t->unsignedBigInteger('assessment_id'),
            'assessment_name' => fn (Blueprint $t) => $t->string('assessment_name', 100)->nullable(),
            'sub_assessment_id' => fn (Blueprint $t) => $t->unsignedBigInteger('sub_assessment_id')->nullable(),
            'sub_assessment_name' => fn (Blueprint $t) => $t->string('sub_assessment_name', 100)->nullable(),
            'score' => fn (Blueprint $t) => $t->decimal('score', 8, 2)->default(0.00),
            'score_type' => fn (Blueprint $t) => $t->string('score_type', 20)->default('assessment'),
            'created_at' => fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
        ];

        foreach ($columns as $name => $adder) {
            if (!Schema::hasColumn($this->snapshotsTable, $name)) {
                Schema::table($this->snapshotsTable, function (Blueprint $table) use ($adder) {
                    $adder($table);
                });
            }
        }

        if (!$this->indexExists($this->snapshotsTable, 'snap_archive_id')) {
            Schema::table($this->snapshotsTable, function (Blueprint $table) {
                $table->index('archive_id', 'snap_archive_id');
            });
        }
        if (!$this->indexExists($this->snapshotsTable, 'snap_student_session_term')) {
            Schema::table($this->snapshotsTable, function (Blueprint $table) {
                $table->index(['student_id', 'session_id', 'term_id'], 'snap_student_session_term');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();
        return DB::selectOne(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$db, $table, $indexName]
        ) !== null;
    }

    private function foreignKeyExistsOnColumn(string $table, string $column): bool
    {
        $db = DB::getDatabaseName();
        return DB::selectOne(
            'SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
               AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME IS NOT NULL LIMIT 1',
            [$db, $table, $column]
        ) !== null;
    }
};
