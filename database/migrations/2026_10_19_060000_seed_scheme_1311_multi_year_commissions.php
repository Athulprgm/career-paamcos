<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $targetScheme = DB::table('deposit_schemes')->where('id', 1311)->first();
        if (! $targetScheme) {
            return;
        }

        // Fetch Years 3 and 4 rank commissions from template scheme 1248
        $templateCommissions = DB::table('deposit_scheme_rank_commissions')
            ->where('deposit_scheme_id', 1248)
            ->whereIn('payout_year', [3, 4])
            ->get();

        foreach ($templateCommissions as $tc) {
            DB::table('deposit_scheme_rank_commissions')->updateOrInsert(
                [
                    'deposit_scheme_id' => 1311,
                    'rank_id' => $tc->rank_id,
                    'payout_year' => $tc->payout_year,
                ],
                [
                    'commission_percentage' => $tc->commission_percentage,
                    'is_eligible' => $tc->is_eligible,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('deposit_scheme_rank_commissions')
            ->where('deposit_scheme_id', 1311)
            ->whereIn('payout_year', [3, 4])
            ->delete();
    }
};
