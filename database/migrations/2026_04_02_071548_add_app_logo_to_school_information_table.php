<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('school_information', 'app_logo')) {
            Schema::table('school_information', function (Blueprint $table) {
                $table->string('app_logo')->nullable()->after('school_logo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('school_information', 'app_logo')) {
            Schema::table('school_information', function (Blueprint $table) {
                $table->dropColumn('app_logo');
            });
        }
    }
};
