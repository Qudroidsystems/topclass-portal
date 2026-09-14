<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('principalscomments', function (Blueprint $table) {
            // Change existing columns to proper unsignedBigInteger (fix previous string issue)
            $table->unsignedBigInteger('staffId')->nullable()->change();
            $table->unsignedBigInteger('schoolclassid')->nullable()->change();
        });

        // Add new columns only if they don't exist
        Schema::table('principalscomments', function (Blueprint $table) {
            if (!Schema::hasColumn('principalscomments', 'sessionid')) {
                $table->unsignedBigInteger('sessionid')->nullable()->after('schoolclassid');
            }
            if (!Schema::hasColumn('principalscomments', 'termid')) {
                $table->unsignedBigInteger('termid')->nullable()->after('sessionid');
            }
        });

        // Add foreign keys only if they don't exist
        Schema::table('principalscomments', function (Blueprint $table) {
            if (!$this->hasForeignKey('principalscomments', 'principalscomments_staffid_foreign')) {
                $table->foreign('staffId')->references('id')->on('users')->onDelete('cascade');
            }
            if (!$this->hasForeignKey('principalscomments', 'principalscomments_schoolclassid_foreign')) {
                $table->foreign('schoolclassid')->references('id')->on('schoolclass')->onDelete('cascade');
            }
            if (!$this->hasForeignKey('principalscomments', 'principalscomments_sessionid_foreign')) {
                $table->foreign('sessionid')->references('id')->on('schoolsession')->onDelete('cascade');
            }
            if (!$this->hasForeignKey('principalscomments', 'principalscomments_termid_foreign')) {
                $table->foreign('termid')->references('id')->on('schoolterm')->onDelete('cascade');
            }
        });

        // Add unique constraint only if it doesn't exist
        if (!$this->hasIndex('principalscomments', 'principalscomments_staffid_schoolclassid_sessionid_termid_unique')) {
            Schema::table('principalscomments', function (Blueprint $table) {
                $table->unique(['staffId', 'schoolclassid', 'sessionid', 'termid'], 'principalscomments_staffid_schoolclassid_sessionid_termid_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('principalscomments', function (Blueprint $table) {
            // Drop unique if exists
            if ($this->hasIndex('principalscomments', 'principalscomments_staffid_schoolclassid_sessionid_termid_unique')) {
                $table->dropUnique('principalscomments_staffid_schoolclassid_sessionid_termid_unique');
            }
        });

        Schema::table('principalscomments', function (Blueprint $table) {
            // Drop foreign keys if they exist
            if ($this->hasForeignKey('principalscomments', 'principalscomments_staffid_foreign')) {
                $table->dropForeign('principalscomments_staffid_foreign');
            }
            if ($this->hasForeignKey('principalscomments', 'principalscomments_schoolclassid_foreign')) {
                $table->dropForeign('principalscomments_schoolclassid_foreign');
            }
            if ($this->hasForeignKey('principalscomments', 'principalscomments_sessionid_foreign')) {
                $table->dropForeign('principalscomments_sessionid_foreign');
            }
            if ($this->hasForeignKey('principalscomments', 'principalscomments_termid_foreign')) {
                $table->dropForeign('principalscomments_termid_foreign');
            }
        });

        Schema::table('principalscomments', function (Blueprint $table) {
            if (Schema::hasColumn('principalscomments', 'sessionid')) {
                $table->dropColumn('sessionid');
            }
            if (Schema::hasColumn('principalscomments', 'termid')) {
                $table->dropColumn('termid');
            }

            $table->string('staffId')->nullable()->change();
            $table->string('schoolclassid')->nullable()->change();
        });
    }

    /**
     * Check if a foreign key exists on a table.
     */
    private function hasForeignKey(string $table, string $foreignKeyName): bool
    {
        $conn = Schema::getConnection();
        $dbName = $conn->getDatabaseName();

        $result = $conn->selectOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
            [$dbName, $table, $foreignKeyName]
        );

        return $result !== null;
    }

    /**
     * Check if an index exists on a table.
     */
    private function hasIndex(string $table, string $indexName): bool
    {
        $conn = Schema::getConnection();
        $dbName = $conn->getDatabaseName();

        $result = $conn->selectOne(
            "SELECT INDEX_NAME FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$dbName, $table, $indexName]
        );

        return $result !== null;
    }
};
