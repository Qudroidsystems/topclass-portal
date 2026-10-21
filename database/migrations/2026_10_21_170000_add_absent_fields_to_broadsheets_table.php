<?php
// Records which score columns (ca1, ca2, ca3, exam) a student was ABSENT for,
// so the scoresheet can tell "absent" apart from a genuine score of 0.
// Stored as a comma list, e.g. "exam" or "ca2,exam". The score itself stays 0.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('broadsheets', 'absent_fields')) {
            Schema::table('broadsheets', function (Blueprint $table) {
                $table->string('absent_fields', 50)->nullable()->after('exam');
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('broadsheets', 'absent_fields')) {
            Schema::table('broadsheets', function (Blueprint $table) {
                $table->dropColumn('absent_fields');
            });
        }
    }
};
