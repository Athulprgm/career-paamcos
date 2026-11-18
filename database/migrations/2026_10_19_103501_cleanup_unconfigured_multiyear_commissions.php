<?php

use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\DepositSchemeRankCommission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Remove commissions for deposit payout years where no rank commission was configured in the scheme
        $commissions = Commission::whereNotNull('deposit_id')
            ->where('payout_year', '>', 1)
            ->get();

        foreach ($commissions as $comm) {
            $deposit = $comm->deposit;
            if (! $deposit) {
                continue;
            }

            $hasRuleForYear = DepositSchemeRankCommission::where('deposit_scheme_id', $deposit->deposit_scheme_id)
                ->where('rank_id', $comm->agent?->rank_id)
                ->where('payout_year', $comm->payout_year)
                ->exists();

            if (! $hasRuleForYear) {
                $comm->forceDelete();
            }
        }

        // 2. Remove orphaned generated commission rules for unconfigured years
        $rules = CommissionRule::whereNotNull('deposit_scheme_id')
            ->where('name', 'like', '%(Year %')
            ->get();

        foreach ($rules as $rule) {
            if (preg_match('/\(Year (\d+)\)/', $rule->name, $matches)) {
                $year = (int) $matches[1];
                if ($year > 1) {
                    $hasRankRule = DepositSchemeRankCommission::where('deposit_scheme_id', $rule->deposit_scheme_id)
                        ->where('rank_id', $rule->rank_id)
                        ->where('payout_year', $year)
                        ->exists();

                    if (! $hasRankRule) {
                        $rule->forceDelete();
                    }
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-reversible cleanup
    }
};
