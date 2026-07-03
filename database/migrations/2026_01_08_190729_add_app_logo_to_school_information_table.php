<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('school_information', function (Blueprint $table) {
            if (!Schema::hasColumn('school_information', 'app_logo')) {
                $table->string('app_logo')->nullable()->after('school_logo');
            }
        });
    }

    public function down()
    {
        Schema::table('school_information', function (Blueprint $table) {
            if (Schema::hasColumn('school_information', 'app_logo')) {
                $table->dropColumn('app_logo');
            }
        });
    }
};
