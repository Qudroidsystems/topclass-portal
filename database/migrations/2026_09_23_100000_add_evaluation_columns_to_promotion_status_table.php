<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * New columns only — the existing promotionStatus table/migration is
     * never modified. These back the evaluation metadata that
     * PromotionEvaluator::persistResult() and PromotionController already
     * try to write (evaluated_at is already set in update()/bulkPromote()),
     * but which was silently dropped because the columns didn't exist yet.
     */
    public function up(): void
    {
        Schema::table('promotionStatus', function (Blueprint $table) {
            $table->timestamp('evaluated_at')->nullable();
            $table->string('rule_applied')->nullable();
            $table->decimal('overall_average', 5, 2)->nullable();
            $table->decimal('promotion_pass_average', 5, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('promotionStatus', function (Blueprint $table) {
            $table->dropColumn(['evaluated_at', 'rule_applied', 'overall_average', 'promotion_pass_average']);
        });
    }
};
