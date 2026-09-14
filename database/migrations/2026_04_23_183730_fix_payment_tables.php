<?php
// database/migrations/2026_04_23_183730_fix_payment_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // ============================================
        // PART 1: Add missing columns to student_bill_payment
        // ============================================
        $this->addMissingColumns('student_bill_payment', [
            'total_paid'        => fn (Blueprint $t) => $t->decimal('total_paid', 15, 2)->default(0)->after('payment_method'),
            'total_balance'     => fn (Blueprint $t) => $t->decimal('total_balance', 15, 2)->default(0)->after('total_paid'),
            'last_payment_date' => fn (Blueprint $t) => $t->timestamp('last_payment_date')->nullable()->after('total_balance'),
            'payment_status'    => fn (Blueprint $t) => $t->enum('payment_status', ['pending','partial','completed','failed','refunded'])->default('pending')->after('status'),
            'session_token'     => fn (Blueprint $t) => $t->string('session_token', 100)->nullable()->after('payment_status'),
            'deleted_at'        => fn (Blueprint $t) => $t->softDeletes()->after('updated_at'),
        ]);

        // ============================================
        // PART 2: Fix column types
        // ============================================
        $this->fixColumnTypes('student_bill_payment', ['student_id','school_bill_id','class_id','termid_id','session_id','generated_by']);

        // ============================================
        // PART 3: Add Foreign Keys (each checked + safe)
        // ============================================
        $this->safeAddForeignKey('student_bill_payment', 'student_id',    'fk_sbp_student',   'studentRegistration', 'id', 'cascade');
        $this->safeAddForeignKey('student_bill_payment', 'school_bill_id','fk_sbp_bill',      'school_bill',         'id', 'cascade');
        $this->safeAddForeignKey('student_bill_payment', 'class_id',      'fk_sbp_class',     'schoolclass',         'id', null);
        $this->safeAddForeignKey('student_bill_payment', 'termid_id',     'fk_sbp_term',      'schoolterm',          'id', null);
        $this->safeAddForeignKey('student_bill_payment', 'session_id',    'fk_sbp_session',   'schoolsession',       'id', null);
        $this->safeAddForeignKey('student_bill_payment', 'generated_by',  'fk_sbp_generated', 'users',               'id', null);

        // ============================================
        // PART 4: Add Indexes
        // ============================================
        $this->safeAddIndex('student_bill_payment', ['student_id','termid_id','session_id'], 'idx_sbp_student_term_session');
        $this->safeAddIndex('student_bill_payment', ['payment_status'],                      'idx_sbp_status');

        // ============================================
        // PART 5: Fix student_bill_payment_record table
        // ============================================
        $this->addMissingColumns('student_bill_payment_record', [
            'last_payment'          => fn (Blueprint $t) => $t->decimal('last_payment', 15, 2)->default(0)->after('amount_paid'),
            'is_reversal'           => fn (Blueprint $t) => $t->boolean('is_reversal')->default(false)->after('complete_payment'),
            'reversal_reason'       => fn (Blueprint $t) => $t->text('reversal_reason')->nullable()->after('is_reversal'),
            'invoiceNo'             => fn (Blueprint $t) => $t->string('invoiceNo', 100)->nullable()->after('reversal_reason'),
            'transaction_reference' => fn (Blueprint $t) => $t->string('transaction_reference', 100)->nullable()->after('invoiceNo'),
            'deleted_at'            => fn (Blueprint $t) => $t->softDeletes()->after('updated_at'),
        ]);

        // Fix column type
        try {
            DB::statement('ALTER TABLE student_bill_payment_record MODIFY student_bill_payment_id BIGINT UNSIGNED NOT NULL');
        } catch (\Throwable $e) {
            Log::warning('[payment-tables] Could not modify student_bill_payment_record.student_bill_payment_id: ' . $e->getMessage());
        }

        $this->safeAddForeignKey('student_bill_payment_record', 'student_bill_payment_id', 'fk_sbpr_payment', 'student_bill_payment', 'id', 'cascade');

        // ============================================
        // PART 6: Fix student_bill_payment_book table
        // ============================================
        $this->addMissingColumns('student_bill_payment_book', [
            'original_amount'      => fn (Blueprint $t) => $t->decimal('original_amount', 15, 2)->default(0)->after('school_bill_id'),
            'scholarship_deduction'=> fn (Blueprint $t) => $t->decimal('scholarship_deduction', 15, 2)->default(0)->after('original_amount'),
            'discount_deduction'   => fn (Blueprint $t) => $t->decimal('discount_deduction', 15, 2)->default(0)->after('scholarship_deduction'),
            'adjusted_amount'      => fn (Blueprint $t) => $t->decimal('adjusted_amount', 15, 2)->default(0)->after('discount_deduction'),
        ]);

        // Change column types (only if columns exist)
        foreach (['amount_paid', 'amount_owed'] as $col) {
            if (Schema::hasColumn('student_bill_payment_book', $col)) {
                try {
                    Schema::table('student_bill_payment_book', function (Blueprint $t) use ($col) {
                        $t->decimal($col, 15, 2)->default(0)->change();
                    });
                } catch (\Throwable $e) {
                    Log::warning("[payment-tables] Could not change type of student_bill_payment_book.{$col}: " . $e->getMessage());
                }
            }
        }

        // ============================================
        // PART 7: Fix student_bill_invoice table
        // ============================================
        $this->fixColumnTypes('student_bill_invoice', ['student_id','school_bill_id','class_id','termid_id','session_id','generated_by']);

        $this->addMissingColumns('student_bill_invoice', [
            'pdf_path'   => fn (Blueprint $t) => $t->string('pdf_path')->nullable()->after('payment_method'),
            'amount'     => fn (Blueprint $t) => $t->decimal('amount', 15, 2)->default(0)->after('pdf_path'),
            'deleted_at' => fn (Blueprint $t) => $t->softDeletes()->after('updated_at'),
        ]);
    }

    // =====================================================================
    // HELPERS
    // =====================================================================

    /**
     * Add a set of columns to a table, skipping any that already exist.
     * Each column is added in its own Schema::table() call so one failure
     * doesn't abort the others.
     */
    private function addMissingColumns(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            Log::warning("[payment-tables] Table {$table} does not exist — skipping column additions.");
            return;
        }

        foreach ($columns as $name => $adder) {
            if (Schema::hasColumn($table, $name)) {
                continue;
            }
            try {
                Schema::table($table, function (Blueprint $t) use ($adder) {
                    $adder($t);
                });
            } catch (\Throwable $e) {
                Log::warning("[payment-tables] Could not add {$table}.{$name}: " . $e->getMessage());
            }
        }
    }

    /**
     * Add a foreign key ONLY if:
     *   - the table exists
     *   - the column exists
     *   - no FK already exists on that column
     *   - the parent table & column exist
     *   - no orphan rows would violate the FK
     * Otherwise, skip and log.
     */
    private function safeAddForeignKey(
        string $table,
        string $column,
        string $fkName,
        string $refTable,
        string $refColumn,
        ?string $onDelete = null
    ): void {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
            return;
        }
        if (!Schema::hasTable($refTable) || !Schema::hasColumn($refTable, $refColumn)) {
            Log::warning("[payment-tables] Skipping FK {$fkName}: {$refTable}.{$refColumn} does not exist.");
            return;
        }

        // Already has a FK on this column (any name)?
        if ($this->foreignKeyExistsOnColumn($table, $column)) {
            return;
        }

        // Orphans would block the FK.
        $orphanCount = $this->countOrphans($table, $column, $refTable, $refColumn);
        if ($orphanCount > 0) {
            Log::warning("[payment-tables] Skipping FK {$fkName} on {$table}.{$column}: {$orphanCount} orphan row(s) reference missing {$refTable}.{$refColumn}.");
            return;
        }

        try {
            Schema::table($table, function (Blueprint $t) use ($column, $fkName, $refTable, $refColumn, $onDelete) {
                $fk = $t->foreign($column, $fkName)->references($refColumn)->on($refTable);
                if ($onDelete !== null) {
                    $fk->onDelete($onDelete);
                }
            });
        } catch (\Throwable $e) {
            Log::warning("[payment-tables] Failed adding FK {$fkName} on {$table}.{$column}: " . $e->getMessage());
        }
    }

    /**
     * Add an index if no index with that name already exists.
     */
    private function safeAddIndex(string $table, array $columns, string $indexName): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }
        foreach ($columns as $col) {
            if (!Schema::hasColumn($table, $col)) {
                return;
            }
        }
        if ($this->indexExists($table, $indexName)) {
            return;
        }
        try {
            Schema::table($table, function (Blueprint $t) use ($columns, $indexName) {
                $t->index($columns, $indexName);
            });
        } catch (\Throwable $e) {
            Log::warning("[payment-tables] Failed adding index {$indexName} on {$table}: " . $e->getMessage());
        }
    }

    /**
     * Modify column types — only if the column exists and the current type
     * is not already bigint/integer-ish.
     */
    private function fixColumnTypes(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }
        foreach ($columns as $column) {
            if (!Schema::hasColumn($table, $column)) {
                continue;
            }
            try {
                $type = DB::getSchemaBuilder()->getColumnType($table, $column);
                if (!in_array($type, ['bigint', 'integer', 'bigint unsigned', 'int', 'int unsigned'], true)) {
                    DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT UNSIGNED NULL");
                }
            } catch (\Throwable $e) {
                Log::warning("[payment-tables] Could not convert {$table}.{$column} to BIGINT UNSIGNED: " . $e->getMessage());
            }
        }
    }

    // =====================================================================
    // DB INTROSPECTION
    // =====================================================================

    private function foreignKeyExistsOnColumn(string $table, string $column): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND COLUMN_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL
             LIMIT 1',
            [$table, $column]
        ) !== null;
    }

    private function indexExists(string $table, string $indexName): bool
    {
        return DB::selectOne(
            'SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
               AND INDEX_NAME = ?
             LIMIT 1',
            [$table, $indexName]
        ) !== null;
    }

    /**
     * Count rows in $table whose $column points to a non-existent row in
     * $refTable.$refColumn. NULLs are ignored (they don't violate FKs).
     */
    private function countOrphans(string $table, string $column, string $refTable, string $refColumn): int
    {
        $row = DB::selectOne(
            "SELECT COUNT(*) AS c
               FROM `{$table}` t
               LEFT JOIN `{$refTable}` r ON r.`{$refColumn}` = t.`{$column}`
              WHERE t.`{$column}` IS NOT NULL
                AND r.`{$refColumn}` IS NULL",
            []
        );
        return (int) ($row->c ?? 0);
    }

    // =====================================================================
    // DOWN
    // =====================================================================

    public function down(): void
    {
        // Drop FKs (safe — swallow "doesn't exist" errors)
        foreach ([
            ['student_bill_payment', 'fk_sbp_student'],
            ['student_bill_payment', 'fk_sbp_bill'],
            ['student_bill_payment', 'fk_sbp_class'],
            ['student_bill_payment', 'fk_sbp_term'],
            ['student_bill_payment', 'fk_sbp_session'],
            ['student_bill_payment', 'fk_sbp_generated'],
            ['student_bill_payment_record', 'fk_sbpr_payment'],
        ] as [$table, $fk]) {
            if (Schema::hasTable($table)) {
                try {
                    Schema::table($table, fn (Blueprint $t) => $t->dropForeign($fk));
                } catch (\Throwable $e) { /* ignore */ }
            }
        }

        // Drop indexes
        foreach ([
            ['student_bill_payment', 'idx_sbp_student_term_session'],
            ['student_bill_payment', 'idx_sbp_status'],
        ] as [$table, $idx]) {
            if (Schema::hasTable($table) && $this->indexExists($table, $idx)) {
                try {
                    Schema::table($table, fn (Blueprint $t) => $t->dropIndex($idx));
                } catch (\Throwable $e) { /* ignore */ }
            }
        }

        // Drop columns — guarded
        $this->dropColumnsIfExist('student_bill_payment', [
            'total_paid', 'total_balance', 'last_payment_date',
            'payment_status', 'session_token', 'deleted_at',
        ]);
        $this->dropColumnsIfExist('student_bill_payment_record', [
            'last_payment', 'is_reversal', 'reversal_reason',
            'invoiceNo', 'transaction_reference', 'deleted_at',
        ]);
        $this->dropColumnsIfExist('student_bill_payment_book', [
            'original_amount', 'scholarship_deduction',
            'discount_deduction', 'adjusted_amount',
        ]);
        $this->dropColumnsIfExist('student_bill_invoice', [
            'pdf_path', 'amount', 'deleted_at',
        ]);
    }

    private function dropColumnsIfExist(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }
        $existing = array_filter($columns, fn ($c) => Schema::hasColumn($table, $c));
        if (!$existing) {
            return;
        }
        try {
            Schema::table($table, function (Blueprint $t) use ($existing) {
                $t->dropColumn(array_values($existing));
            });
        } catch (\Throwable $e) {
            Log::warning("[payment-tables] Could not drop columns on {$table}: " . $e->getMessage());
        }
    }
};
