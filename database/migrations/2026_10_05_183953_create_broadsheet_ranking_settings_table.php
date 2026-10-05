<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('broadsheet_ranking_settings')) {
            return;
        }

        Schema::create('broadsheet_ranking_settings', function (Blueprint $table) {
            $table->id();
            $table->string('section', 20)->unique();                 // junior | senior
            $table->string('primary_measure', 30)->default('cum_ave');
            $table->json('tiebreakers')->nullable();                  // up to 3 measure keys
            $table->unsignedTinyInteger('min_subjects')->default(1);
            $table->decimal('min_average', 5, 2)->nullable();
            $table->boolean('require_all_compulsory')->default(false);
            $table->boolean('exclude_failed')->default(false);
            $table->string('scope', 20)->default('both');             // class | arm | both
            $table->unsignedTinyInteger('top_n')->default(3);
            $table->unsignedTinyInteger('subject_top_n')->default(3);
            $table->boolean('show_rank_column')->default(false);
            $table->json('core_subject_ids')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('broadsheet_ranking_settings');
    }
};
