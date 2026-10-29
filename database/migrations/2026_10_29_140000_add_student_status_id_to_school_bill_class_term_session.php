<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a bill assignment (bill + class + term + session) say which students it
 * applies to.
 *
 *   NULL -> not set: fall back to the bill's own Old/New type (legacy rows)
 *   0    -> both old and new students
 *   1    -> old students only   (student_status.id = 1)
 *   2    -> new students only   (student_status.id = 2)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('school_bill_class_term_session', 'student_status_id')) {
            Schema::table('school_bill_class_term_session', function (Blueprint $table) {
                $table->unsignedTinyInteger('student_status_id')->nullable()->after('session_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('school_bill_class_term_session', 'student_status_id')) {
            Schema::table('school_bill_class_term_session', function (Blueprint $table) {
                $table->dropColumn('student_status_id');
            });
        }
    }
};
