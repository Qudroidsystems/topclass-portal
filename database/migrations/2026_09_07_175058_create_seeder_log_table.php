<?php
// database/migrations/2026_09_07_175058_create_seeder_log_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('seeder_log')) {
            return;
        }

        Schema::create('seeder_log', function (Blueprint $table) {
            $table->id();
            $table->string('seeder_name')->unique();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seeder_log');
    }
};
