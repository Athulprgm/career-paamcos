<?php

use App\Models\CareerLevel;
use App\Models\PromotionRule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Resolve ranks dynamically by level or code to ensure robust portability
        $adm = CareerLevel::where('level', 6)->orWhere('code', 'AREA_DEVELOPMENT_MANAGER')->first();
        $mm = CareerLevel::where('level', 7)->orWhere('code', 'MARKETING_MANAGER')->first();
        $smm = CareerLevel::where('level', 8)->orWhere('code', 'SENIOR_MARKETING_MANAGER')->first();
        $dm = CareerLevel::where('level', 9)->orWhere('code', 'DIVISIONAL_MANAGER')->first();
        $rdm = CareerLevel::where('level', 10)->orWhere('code', 'REGIONAL_DEVELOPMENT_MANAGER')->first();
        $zdm = CareerLevel::where('level', 11)->orWhere('code', 'ZONAL_DEVELOPMENT_MANAGER')->first();
        $dd = CareerLevel::where('level', 12)->orWhere('code', 'DEPUTY_DIRECTOR')->first();

        // 1. Fix Rule: Marketing Manager (L7) -> Senior Marketing Manager (L8)
        // Qualifying rank was wrongly set to Level 5 (BDM); fix to Level 6 (Area Development Manager)
        if ($mm && $smm && $adm) {
            $rule12 = PromotionRule::where('from_rank_id', $mm->id)
                ->where('to_rank_id', $smm->id)
                ->first();

            if ($rule12) {
                $rule12->update([
                    'qualifying_rank_id' => $adm->id,
                    'qualifying_agent_tiers' => [
                        ['count' => 3, 'rank_id' => $adm->id],
                    ],
                    'description' => 'Promotion from Marketing Manager (Rank 7) to Senior Marketing Manager (Rank 8). Requires ₹2,00,00,000 eligible team deposits (10% RD + 90% FD composition) and 3 active direct Area Development Managers.',
                ]);
            }
        }

        // 2. Fix Rule: Regional Development Manager (L10) -> Zonal Development Manager (L11)
        // Qualifying rank was wrongly set to Level 8 (SMM); fix to Level 9 (Divisional Manager)
        if ($rdm && $zdm && $dm) {
            $rule15 = PromotionRule::where('from_rank_id', $rdm->id)
                ->where('to_rank_id', $zdm->id)
                ->first();

            if ($rule15) {
                $rule15->update([
                    'qualifying_rank_id' => $dm->id,
                    'qualifying_agent_tiers' => [
                        ['count' => 3, 'rank_id' => $dm->id],
                    ],
                    'description' => 'Promotion from Regional Development Manager (Rank 10) to Zonal Development Manager (Rank 11). Requires ₹7,50,00,000 eligible team deposits (10% RD + 90% FD composition) and 3 active direct Divisional Managers.',
                ]);
            }
        }

        // 3. Fix Rule: Zonal Development Manager (L11) -> Deputy Director (L12)
        // Qualifying rank was wrongly set to Level 9 (DM); fix to Level 10 (Regional Development Manager)
        if ($zdm && $dd && $rdm) {
            $rule16 = PromotionRule::where('from_rank_id', $zdm->id)
                ->where('to_rank_id', $dd->id)
                ->first();

            if ($rule16) {
                $rule16->update([
                    'qualifying_rank_id' => $rdm->id,
                    'qualifying_agent_tiers' => [
                        ['count' => 3, 'rank_id' => $rdm->id],
                    ],
                    'description' => 'Promotion from Zonal Development Manager (Rank 11) to Deputy Director (Rank 12). Requires ₹10,00,00,000 eligible team deposits (10% RD + 90% FD composition) and 3 active direct Regional Development Managers.',
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reversal logic if needed
    }
};
