<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $table = 'subject_unregistration_archive';

    public function up(): void
    {
        // 1. Create the table if it doesn't exist (with all columns + indexes,
        //    but no foreign keys yet — FKs added separately so we can check them)
        if (!Schema::hasTable($this->table)) {
            Schema::create($this->table, function (Blueprint $table) {
                $table->id();

                $table->unsignedBigInteger('studentid');
                $table->unsignedBigInteger('subjectclassid');
                $table->unsignedBigInteger('staffid');
                $table->unsignedBigInteger('termid');
                $table->unsignedBigInteger('sessionid');
                $table->unsignedBigInteger('subjectid');
                $table->unsignedBigInteger('schoolclassid');

                $table->unsignedBigInteger('broadsheet_record_id')->nullable();
                $table->unsignedBigInteger('unregistered_by')->nullable();

                $table->string('status', 30)->default('archived');

                $table->timestamp('unregistered_at')->useCurrent();
                $table->timestamp('actioned_at')->nullable();

                $table->timestamps();

                $table->index(['subjectclassid', 'termid', 'sessionid'], 'archive_subject_term_session');
                $table->index(['studentid', 'sessionid', 'termid'], 'archive_student_session_term');
                $table->index('status', 'archive_status_index');
            });
        }

        // 2. Add any missing columns (in case the table pre-existed but is incomplete)
        $this->addMissingColumns();

        // 3. Add any missing indexes
        $this->addMissingIndexes();

        // 4. Add any missing foreign keys
        $this->addMissingForeignKeys();
    }

    public function down(): void
    {
        Schema::dropIfExists($this->table);
    }

    // ---------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------

    private function addMissingColumns(): void
    {
        // column name => closure that adds it
        $columns = [
            'studentid' => fn (Blueprint $t) => $t->unsignedBigInteger('studentid'),
            'subjectclassid' => fn (Blueprint $t) => $t->unsignedBigInteger('subjectclassid'),
            'staffid' => fn (Blueprint $t) => $t->unsignedBigInteger('staffid'),
            'termid' => fn (Blueprint $t) => $t->unsignedBigInteger('termid'),
            'sessionid' => fn (Blueprint $t) => $t->unsignedBigInteger('sessionid'),
            'subjectid' => fn (Blueprint $t) => $t->unsignedBigInteger('subjectid'),
            'schoolclassid' => fn (Blueprint $t) => $t->unsignedBigInteger('schoolclassid'),
            'broadsheet_record_id' => fn (Blueprint $t) => $t->unsignedBigInteger('broadsheet_record_id')->nullable(),
            'unregistered_by' => fn (Blueprint $t) => $t->unsignedBigInteger('unregistered_by')->nullable(),
            'status' => fn (Blueprint $t) => $t->string('status', 30)->default('archived'),
            'unregistered_at' => fn (Blueprint $t) => $t->timestamp('unregistered_at')->useCurrent(),
            'actioned_at' => fn (Blueprint $t) => $t->timestamp('actioned_at')->nullable(),
            'created_at' => fn (Blueprint $t) => $t->timestamp('created_at')->nullable(),
            'updated_at' => fn (Blueprint $t) => $t->timestamp('updated_at')->nullable(),
        ];

        foreach ($columns as $name => $adder) {
            if (!Schema::hasColumn($this->table, $name)) {
                Schema::table($this->table, function (Blueprint $table) use ($adder) {
                    $adder($table);
                });
            }
        }
    }

    private function addMissingIndexes(): void
    {
        // index name => [columns, closure]
        $indexes = [
            'archive_subject_term_session' => [
                ['subjectclassid', 'termid', 'sessionid'],
                fn (Blueprint $t) => $t->index(['subjectclassid', 'termid', 'sessionid'], 'archive_subject_term_session'),
            ],
            'archive_student_session_term' => [
                ['studentid', 'sessionid', 'termid'],
                fn (Blueprint $t) => $t->index(['studentid', 'sessionid', 'termid'], 'archive_student_session_term'),
            ],
            'archive_status_index' => [
                ['status'],
                fn (Blueprint $t) => $t->index('status', 'archive_status_index'),
            ],
        ];

        foreach ($indexes as $name => [$cols, $adder]) {
            if (!$this->indexExists($this->table, $name)) {
                Schema::table($this->table, function (Blueprint $table) use ($adder) {
                    $adder($table);
                });
            }
        }
    }

    private function addMissingForeignKeys(): void
    {
        // fk name => [column, refTable, refColumn, onDelete]
        $fks = [
            'archive_studentid_foreign'   => ['studentid',     'studentRegistration', 'id', 'cascade'],
            'archive_subjectclassid_foreign' => ['subjectclassid', 'subjectclass',       'id', 'cascade'],
            'archive_staffid_foreign'     => ['staffid',       'users',               'id', 'cascade'],
            'archive_termid_foreign'      => ['termid',        'schoolterm',          'id', 'cascade'],
            'archive_sessionid_foreign'   => ['sessionid',     'schoolsession',       'id', 'cascade'],
            'archive_unregistered_by_foreign' => ['unregistered_by', 'users',          'id', 'set null'],
        ];

        foreach ($fks as $name => [$col, $refTable, $refCol, $onDelete]) {
            if (!$this->foreignKeyExists($this->table, $name)) {
                Schema::table($this->table, function (Blueprint $table) use ($col, $refTable, $refCol, $onDelete, $name) {
                    $table->foreign($col, $name)
                          ->references($refCol)
                          ->on($refTable)
                          ->onDelete($onDelete);
                });
            }
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?
             LIMIT 1',
            [$db, $table, $indexName]
        );
        return $row !== null;
    }

    private function foreignKeyExists(string $table, string $fkName): bool
    {
        $db = DB::getDatabaseName();
        $row = DB::selectOne(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'
             LIMIT 1",
            [$db, $table, $fkName]
        );
        return $row !== null;
    }
};
