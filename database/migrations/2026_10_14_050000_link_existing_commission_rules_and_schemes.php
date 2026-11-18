<?php

use App\Models\AgentRank;
use App\Models\CommissionRule;
use App\Models\DepositScheme;
use App\Models\DepositSchemeRankCommission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Initialize rank commissions for existing deposit schemes that lack them (e.g., Scheme 6371 RD)
        $schemesWithoutRanks = DepositScheme::whereDoesntHave('rankCommissions')->get();
        $ranks = AgentRank::orderBy('sort_order')->get();

        foreach ($schemesWithoutRanks as $scheme) {
            $totalComm = 0.00;
            $schemeActive = ($scheme->status === DepositScheme::STATUS_ACTIVE);
            $today = now()->toDateString();

            foreach ($ranks as $index => $rank) {
                // Tiered default commission rates for ranks
                $rate = match ($rank->sort_order ?? ($index + 1)) {
                    1 => 1.00,
                    2 => 1.50,
                    3 => 2.00,
                    4 => 2.50,
                    5 => 3.00,
                    6 => 3.50,
                    default => 0.50,
                };

                DepositSchemeRankCommission::updateOrCreate(
                    [
                        'deposit_scheme_id' => $scheme->id,
                        'rank_id' => $rank->id,
                    ],
                    [
                        'commission_percentage' => $rate,
                        'is_eligible' => true,
                    ]
                );

                $totalComm += $rate;

                // Sync to commission_rules
                $ruleCode = "SCHEME-{$scheme->id}-RANK-{$rank->id}";
                $existingRule = CommissionRule::where('deposit_scheme_id', $scheme->id)
                    ->where('rank_id', $rank->id)
                    ->first();

                $basis = in_array($scheme->scheme_type, [DepositScheme::TYPE_RD, DepositScheme::TYPE_DD])
                    ? CommissionRule::BASIS_COLLECTION_AMOUNT
                    : CommissionRule::BASIS_DEPOSIT_AMOUNT;

                if (! $existingRule) {
                    CommissionRule::create([
                        'name' => "{$scheme->name} - {$rank->name}",
                        'code' => $ruleCode,
                        'commission_type' => CommissionRule::TYPE_PERCENTAGE,
                        'calculation_basis' => $basis,
                        'rate' => $rate,
                        'deposit_scheme_id' => $scheme->id,
                        'rank_id' => $rank->id,
                        'effective_from' => $today,
                        'status' => $schemeActive ? CommissionRule::STATUS_ACTIVE : CommissionRule::STATUS_INACTIVE,
                        'description' => "Dynamic rank commission rule for {$scheme->name} [{$scheme->code}].",
                    ]);
                }
            }

            $scheme->update(['total_commission_percentage' => $totalComm]);
        }

        // 2. Link historical / seeded commission rules that have NULL rank_id or NULL deposit_scheme_id
        $fdScheme = DepositScheme::where('scheme_type', DepositScheme::TYPE_FD)->first();
        $rdScheme = DepositScheme::where('scheme_type', DepositScheme::TYPE_RD)->first() ?? $fdScheme;

        $rankMap = [];
        foreach ($ranks as $index => $rank) {
            $level = $rank->sort_order ?? ($index + 1);
            $rankMap[$level] = $rank->id;
            $rankMap[strtolower($rank->name)] = $rank->id;
            $rankMap[strtolower($rank->code)] = $rank->id;
        }

        $allRules = CommissionRule::whereNull('rank_id')->orWhereNull('deposit_scheme_id')->get();
        foreach ($allRules as $rule) {
            $updated = false;

            // Link Rank
            if (! $rule->rank_id) {
                if (preg_match('/-R(\d+)$/', $rule->code, $m)) {
                    $lvl = (int) $m[1];
                    if (isset($rankMap[$lvl])) {
                        $rule->rank_id = $rankMap[$lvl];
                        $updated = true;
                    }
                } elseif (preg_match('/Rank\s*(\d+)/i', $rule->name, $m)) {
                    $lvl = (int) $m[1];
                    if (isset($rankMap[$lvl])) {
                        $rule->rank_id = $rankMap[$lvl];
                        $updated = true;
                    }
                } elseif (str_contains(strtolower($rule->name), 'facilitator') && isset($rankMap['facilitator'])) {
                    $rule->rank_id = $rankMap['facilitator'];
                    $updated = true;
                } elseif (str_contains(strtolower($rule->name), 'field officer') && isset($rankMap[2])) {
                    $rule->rank_id = $rankMap[2];
                    $updated = true;
                } elseif (str_contains(strtolower($rule->name), 'development officer') && isset($rankMap[3])) {
                    $rule->rank_id = $rankMap[3];
                    $updated = true;
                } elseif (str_contains(strtolower($rule->name), 'deputy sales manager') && isset($rankMap[4])) {
                    $rule->rank_id = $rankMap[4];
                    $updated = true;
                } elseif (str_contains(strtolower($rule->name), 'business development manager') && isset($rankMap[5])) {
                    $rule->rank_id = $rankMap[5];
                    $updated = true;
                } elseif (str_contains(strtolower($rule->name), 'area development manager') && isset($rankMap[6])) {
                    $rule->rank_id = $rankMap[6];
                    $updated = true;
                }
            }

            // Link Scheme
            if (! $rule->deposit_scheme_id) {
                if (str_starts_with($rule->code, 'CR-FIX') || str_starts_with($rule->code, 'FD-')) {
                    if ($fdScheme) {
                        $rule->deposit_scheme_id = $fdScheme->id;
                        $updated = true;
                    }
                } elseif (str_starts_with($rule->code, 'CR-REC') || str_starts_with($rule->code, 'RD-')) {
                    if ($rdScheme) {
                        $rule->deposit_scheme_id = $rdScheme->id;
                        $updated = true;
                    }
                }
            } elseif ($rule->deposit_scheme_id == 2 && $rdScheme && $rdScheme->id != 2) {
                // If rule pointed to soft-deleted scheme 2, remap to active RD scheme
                $rule->deposit_scheme_id = $rdScheme->id;
                $updated = true;
            }

            if ($updated) {
                $rule->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op to preserve financial integrity
    }
};
