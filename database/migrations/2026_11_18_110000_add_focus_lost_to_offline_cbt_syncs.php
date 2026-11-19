<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** How many times the student left the exam window on the offline CBT computer. */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('offline_cbt_syncs') && !Schema::hasColumn('offline_cbt_syncs', 'focus_lost')) {
            Schema::table('offline_cbt_syncs', function (Blueprint $t) {
                $t->unsignedInteger('focus_lost')->default(0)->after('workstation');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('offline_cbt_syncs', 'focus_lost')) {
            Schema::table('offline_cbt_syncs', fn (Blueprint $t) => $t->dropColumn('focus_lost'));
        }
    }
};
