<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The old unique key (bill, class, term, session) ignored who the assignment is for,
 * so once a bill was assigned to a class for "Old students" it could never also be
 * assigned for "New students" (MySQL rejected it as a duplicate). It also still
 * counted soft-deleted rows. Replace it with a key that includes student_status_id,
 * and clear out soft-deleted rows that would otherwise block re-assignment.
 */
return new class extends Migration
{
    private string $table = 'school_bill_class_term_session';

    private function indexNames(): array
    {
        return array_unique(array_column(DB::select("SHOW INDEX FROM {$this->table}"), 'Key_name'));
    }

    public function up(): void
    {
        if (!Schema::hasTable($this->table)) return;

        if (!Schema::hasColumn($this->table, 'student_status_id')) {
            DB::statement("ALTER TABLE {$this->table} ADD student_status_id TINYINT UNSIGNED NULL AFTER session_id");
        }

        // Soft-deleted assignments are dead data; they only get in the way of new ones.
        if (Schema::hasColumn($this->table, 'deleted_at')) {
            DB::table($this->table)->whereNotNull('deleted_at')->delete();
        }

        $names = $this->indexNames();

        // Make sure the bill_id foreign key keeps an index of its own before dropping.
        if (!in_array('idx_sbcts_bill', $names)) {
            DB::statement("ALTER TABLE {$this->table} ADD INDEX idx_sbcts_bill (bill_id)");
        }

        if (in_array('uk_sbcts_unique', $names)) {
            DB::statement("ALTER TABLE {$this->table} DROP INDEX uk_sbcts_unique");
        }

        if (!in_array('uk_sbcts_unique_aud', $this->indexNames())) {
            try {
                DB::statement("ALTER TABLE {$this->table} ADD UNIQUE uk_sbcts_unique_aud (bill_id, class_id, termid_id, session_id, student_status_id)");
            } catch (\Throwable $e) {
                // Existing duplicate rows: leave without a unique key; the app checks duplicates itself.
                error_log('uk_sbcts_unique_aud not added: ' . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        if (in_array('uk_sbcts_unique_aud', $this->indexNames())) {
            DB::statement("ALTER TABLE {$this->table} DROP INDEX uk_sbcts_unique_aud");
        }
    }
};
