<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('classcategories', function (Blueprint $table) {
            if (!Schema::hasColumn('classcategories', 'ca3score')) {
                $table->double('ca3score', 5, 2)->default('0')->after('ca2score');
            }
            if (!Schema::hasColumn('classcategories', 'is_senior')) {
                $table->boolean('is_senior')->default(false)->after('examscore');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('classcategories', function (Blueprint $table) {
            if (Schema::hasColumn('classcategories', 'ca3score')) {
                $table->dropColumn('ca3score');
            }
            if (Schema::hasColumn('classcategories', 'is_senior')) {
                $table->dropColumn('is_senior');
            }
        });
    }
};