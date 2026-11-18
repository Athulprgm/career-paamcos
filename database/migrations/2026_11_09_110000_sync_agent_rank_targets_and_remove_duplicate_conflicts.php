<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Standard Society 12 Rank Targets (₹):
     * Level 1 (Facilitator): 3L
     * Level 2 (Field Officer): 10L
     * Level 3 (Development Officer): 25L
     * Level 4 (Deputy Sales Manager): 50L
     * Level 5 (Business Development Manager): 1Cr
     * Level 6 (Area Development Manager): 1.5Cr
     * Level 7 (Marketing Manager): 2Cr
     * Level 8 (Senior Marketing Manager): 3Cr
     * Level 9 (Divisional Manager): 5Cr
     * Level 10 (Regional Development Manager): 7.5Cr
     * Level 11 (Zonal Development Manager): 10Cr
     * Level 12 (Deputy Director): 15Cr
     */
    protected array $standardRankTargets = [
        1 => ['total' => 300000.00,    'recruits' => 3],
        2 => ['total' => 1000000.00,   'recruits' => 3],
        3 => ['total' => 2500000.00,   'recruits' => 2],
        4 => ['total' => 5000000.00,   'recruits' => 2],
        5 => ['total' => 10000000.00,  'recruits' => 2],
        6 => ['total' => 15000000.00,  'recruits' => 3],
        7 => ['total' => 20000000.00,  'recruits' => 2],
        8 => ['total' => 30000000.00,  'recruits' => 3],
        9 => ['total' => 50000000.00,  'recruits' => 2],
        10 => ['total' => 75000000.00, 'recruits' => 2],
        11 => ['total' => 100000000.00, 'recruits' => 3],
        12 => ['total' => 150000000.00, 'recruits' => 3],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Synchronize duplicate/divergent fields on ranks table
        DB::statement('UPDATE ranks SET min_deposit_amount = minimum_deposit_amount WHERE minimum_deposit_amount > 0 AND (min_deposit_amount = 0 OR min_deposit_amount IS NULL)');
        DB::statement('UPDATE ranks SET minimum_deposit_amount = min_deposit_amount WHERE min_deposit_amount > 0 AND (minimum_deposit_amount = 0 OR minimum_deposit_amount IS NULL)');
        DB::statement('UPDATE ranks SET sort_order = level WHERE (sort_order IS NULL OR sort_order = 0) AND level > 0');
        DB::statement('UPDATE ranks SET level = sort_order WHERE (level IS NULL OR level = 0) AND sort_order > 0');

        // 2. Synchronize duplicate/legacy columns on promotion_rules
        DB::statement('UPDATE promotion_rules SET min_business_amount = min_team_deposit WHERE min_team_deposit > 0 AND (min_business_amount = 0 OR min_business_amount IS NULL)');
        DB::statement('UPDATE promotion_rules SET min_downline_agents = min_qualifying_direct_facilitators WHERE min_qualifying_direct_facilitators > 0 AND (min_downline_agents = 0 OR min_downline_agents IS NULL)');

        // 3. Update agent_rank_targets for all financial years with accurate standard rank targets
        $ranks = DB::table('ranks')->get();
        foreach ($ranks as $rank) {
            $level = (int) ($rank->level ?? $rank->sort_order ?? 1);
            $targetSpec = $this->standardRankTargets[$level] ?? null;

            if ($targetSpec) {
                $total = (float) $targetSpec['total'];
                $fdAmount = round($total * 0.90, 2);
                $rdAmount = round($total * 0.10, 2);
                $recruits = (int) $targetSpec['recruits'];

                DB::table('agent_rank_targets')
                    ->where('rank_id', $rank->id)
                    ->update([
                        'total_deposit_target' => $total,
                        'fd_percentage' => 90.00,
                        'rd_percentage' => 10.00,
                        'fd_target_amount' => $fdAmount,
                        'rd_target_amount' => $rdAmount,
                        'recruitment_target' => $recruits,
                        'updated_at' => now(),
                    ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructive reversal needed
    }
};
