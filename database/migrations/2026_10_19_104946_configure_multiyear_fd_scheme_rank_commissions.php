<?php

use App\Models\AgentRank;
use App\Models\Commission;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\DepositSchemeRankCommission;
use App\Services\CommissionService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Multi-year FD schemes: Configure year-by-year rank commissions
        $fdMultiYearSchemes = DepositScheme::whereIn('id', [1240, 1241, 1242, 1244, 1245, 1246])->get();
        $ranks = AgentRank::all();

        foreach ($fdMultiYearSchemes as $scheme) {
            $tenureYears = $scheme->getTenureInYears();
            if ($tenureYears <= 1) {
                continue;
            }

            // Existing Year 1 rules
            $year1Rules = DepositSchemeRankCommission::where('deposit_scheme_id', $scheme->id)
                ->where('payout_year', 1)
                ->get()
                ->keyBy('rank_id');

            for ($year = 1; $year <= $tenureYears; $year++) {
                foreach ($ranks as $rank) {
                    $y1 = $year1Rules->get($rank->id);
                    $isEligible = $y1 ? (bool) $y1->is_eligible : true;

                    // Facilitator gets 1.00% per year (totaling tenureYears * 1.00% over the full tenure)
                    // Other ranks get 0.50% / tenureYears per year
                    $annualRate = ($rank->code === 'FACILITATOR' || $rank->name === 'Facilitator')
                        ? 1.00
                        : round(0.50 / $tenureYears, 2);

                    DepositSchemeRankCommission::updateOrCreate(
                        [
                            'deposit_scheme_id' => $scheme->id,
                            'rank_id' => $rank->id,
                            'payout_year' => $year,
                        ],
                        [
                            'commission_percentage' => $annualRate,
                            'is_eligible' => $isEligible,
                        ]
                    );
                }
            }

            $scheme->recalculateTotalCommission();
        }

        // 2. Re-generate multi-year commissions for deposit DEP000009
        $deposit = Deposit::where('deposit_number', 'DEP000009')->first();
        if ($deposit) {
            // Delete old commissions for DEP000009
            Commission::where('deposit_id', $deposit->id)->forceDelete();

            // Generate commissions for all 7 tenure years
            app(CommissionService::class)->calculateAndCreateForDeposit($deposit);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-reversible
    }
};
