<?php
// database/migrations/2026_09_06_000000_add_publish_fields_to_timetable_settings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timetable_settings', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->after('is_active');
            $table->timestamp('published_at')->nullable()->after('is_published');
            $table->unsignedBigInteger('published_by')->nullable()->after('published_at');
            $table->foreign('published_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('timetable_settings', function (Blueprint $table) {
            $table->dropForeign(['published_by']);
            $table->dropColumn(['is_published', 'published_at', 'published_by']);
        });
    }
};