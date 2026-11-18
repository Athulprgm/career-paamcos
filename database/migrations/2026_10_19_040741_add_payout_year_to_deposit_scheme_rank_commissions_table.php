<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a payout_year column to deposit_scheme_rank_commissions so that
     * commissions can be configured per year of a multi-year deposit plan
     * (e.g. Year 1, Year 2, Year 3, Year 4).
     *
     * The existing unique key (deposit_scheme_id, rank_id) is dropped and
     * replaced with (deposit_scheme_id, rank_id, payout_year) so each
     * rank can have a distinct commission percentage for every year.
     *
     * Existing rows are seeded with payout_year = 1 to preserve backward
     * compatibility.
     */
    public function up(): void
    {
        Schema::table('deposit_scheme_rank_commissions', function (Blueprint $table) {
            // Drop the old unique constraint before adding the year column
            $table->dropUnique('uk_scheme_rank_comm');
        });

        Schema::table('deposit_scheme_rank_commissions', function (Blueprint $table) {
            $table->unsignedTinyInteger('payout_year')
                ->default(1)
                ->after('is_eligible')
                ->comment('Payout year this commission row applies to (1-based, max 4)');

            // New composite unique: one commission row per (scheme, rank, year)
            $table->unique(['deposit_scheme_id', 'rank_id', 'payout_year'], 'uk_scheme_rank_year_comm');

            // Index for fast year-based lookups
            $table->index(['deposit_scheme_id', 'payout_year'], 'idx_scheme_rank_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposit_scheme_rank_commissions', function (Blueprint $table) {
            $table->dropIndex('idx_scheme_rank_year');
            $table->dropUnique('uk_scheme_rank_year_comm');
            $table->dropColumn('payout_year');
        });

        Schema::table('deposit_scheme_rank_commissions', function (Blueprint $table) {
            $table->unique(['deposit_scheme_id', 'rank_id'], 'uk_scheme_rank_comm');
        });
    }
};
