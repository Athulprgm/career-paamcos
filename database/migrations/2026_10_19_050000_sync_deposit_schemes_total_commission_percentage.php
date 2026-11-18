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
        // Sync total_commission_percentage in deposit_schemes with Year 1 eligible commissions sum
        $schemes = DB::table('deposit_schemes')->get();

        foreach ($schemes as $scheme) {
            $year1Commissions = DB::table('deposit_scheme_rank_commissions')
                ->where('deposit_scheme_id', $scheme->id)
                ->where('payout_year', 1)
                ->where('is_eligible', 1)
                ->sum('commission_percentage');

            if ($year1Commissions > 0) {
                DB::table('deposit_schemes')
                    ->where('id', $scheme->id)
                    ->update([
                        'total_commission_percentage' => round($year1Commissions, 2),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverse operation needed
    }
};
