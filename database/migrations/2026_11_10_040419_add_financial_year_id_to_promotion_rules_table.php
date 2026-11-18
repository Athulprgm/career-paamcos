<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('promotion_rules', function (Blueprint $table) {
            $table->foreignId('financial_year_id')
                ->nullable()
                ->after('to_rank_id')
                ->constrained('financial_years')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotion_rules', function (Blueprint $table) {
            $table->dropForeign(['financial_year_id']);
            $table->dropColumn('financial_year_id');
        });
    }
};
