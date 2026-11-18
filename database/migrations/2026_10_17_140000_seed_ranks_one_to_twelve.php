<?php

use App\Models\CareerLevel;
use App\Models\Deposit;
use App\Models\PromotionRule;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Standard 12 career progression ranks.
     */
    protected array $standardRanks = [
        1 => [
            'name' => 'Facilitator',
            'code' => 'FACILITATOR',
            'description' => 'Entry-level agent mobilizing micro-savings and building initial member network.',
        ],
        2 => [
            'name' => 'Field Officer',
            'code' => 'FIELD_OFFICER',
            'description' => 'Field-level supervisor guiding direct facilitators and expanding society deposits.',
        ],
        3 => [
            'name' => 'Development Officer',
            'code' => 'DEVELOPMENT_OFFICER',
            'description' => 'Business development coordinator handling team portfolio and collection targets.',
        ],
        4 => [
            'name' => 'Deputy Sales Manager',
            'code' => 'DEPUTY_SALES_MANAGER',
            'description' => 'Sub-branch sales supervisor overseeing agent teams and customer onboarding.',
        ],
        5 => [
            'name' => 'Business Development Manager',
            'code' => 'BUSINESS_DEVELOPMENT_MANAGER',
            'description' => 'Branch business leader driving aggregate deposit growth and agency network.',
        ],
        6 => [
            'name' => 'Area Development Manager',
            'code' => 'AREA_DEVELOPMENT_MANAGER',
            'description' => 'Multi-branch area manager coordinating regional mobilization campaigns.',
        ],
        7 => [
            'name' => 'Marketing Manager',
            'code' => 'MARKETING_MANAGER',
            'description' => 'Marketing manager leading deposit mobilization strategies and promotional campaigns.',
        ],
        8 => [
            'name' => 'Senior Marketing Manager',
            'code' => 'SENIOR_MARKETING_MANAGER',
            'description' => 'Senior marketing manager coordinating strategic campaigns across branch networks.',
        ],
        9 => [
            'name' => 'Divisional Manager',
            'code' => 'DIVISIONAL_MANAGER',
            'description' => 'Divisional executive overseeing division performance and agency operations.',
        ],
        10 => [
            'name' => 'Regional Development Manager',
            'code' => 'REGIONAL_DEVELOPMENT_MANAGER',
            'description' => 'Regional development executive driving regional expansion and team performance.',
        ],
        11 => [
            'name' => 'Zonal Development Manager',
            'code' => 'ZONAL_DEVELOPMENT_MANAGER',
            'description' => 'Zonal development director directing multi-region mobilization and network governance.',
        ],
        12 => [
            'name' => 'Deputy Director',
            'code' => 'DEPUTY_DIRECTOR',
            'description' => 'Senior executive deputy director overseeing institutional deposit targets and society agency hierarchy.',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $createdLevels = [];

        foreach ($this->standardRanks as $level => $rankData) {
            $sortOrder = $level;

            $existing = CareerLevel::where('level', $level)
                ->orWhere('name', $rankData['name'])
                ->orWhere('code', $rankData['code'])
                ->first();

            if ($existing) {
                $existing->update([
                    'name' => $rankData['name'],
                    'code' => $rankData['code'],
                    'level' => $level,
                    'sort_order' => $sortOrder,
                    'status' => 'active',
                    'description' => $existing->description ?: $rankData['description'],
                ]);
                $createdLevels[$level] = $existing;
            } else {
                $createdLevels[$level] = CareerLevel::create([
                    'name' => $rankData['name'],
                    'code' => $rankData['code'],
                    'level' => $level,
                    'sort_order' => $sortOrder,
                    'status' => 'active',
                    'description' => $rankData['description'],
                    'minimum_deposit_amount' => 0.00,
                    'commission_rate' => 0.00,
                ]);
            }
        }

        // Create default configurable example promotion rule: Facilitator -> Field Officer
        // Target: ₹3,00,000 eligible team deposits and 3 qualifying active direct Facilitators
        if (isset($createdLevels[1], $createdLevels[2])) {
            $existingRule = PromotionRule::where('from_rank_id', $createdLevels[1]->id)
                ->where('to_rank_id', $createdLevels[2]->id)
                ->first();

            if (! $existingRule) {
                PromotionRule::create([
                    'name' => 'Facilitator to Field Officer Standard Advancement',
                    'from_rank_id' => $createdLevels[1]->id,
                    'to_rank_id' => $createdLevels[2]->id,
                    'min_individual_deposit' => 0.00,
                    'min_team_deposit' => 300000.00,
                    'min_qualifying_direct_facilitators' => 3,
                    'qualifying_rank_id' => $createdLevels[1]->id,
                    'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
                    'qualifying_deposit_statuses' => [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED],
                    'required_agent_status' => 'active',
                    'requires_approval' => true,
                    'authorized_approval_role' => PromotionRule::ROLE_ADMIN,
                    'description' => 'Promotion from Facilitator (Rank 1) to Field Officer (Rank 2). Requires ₹3,00,000 eligible team deposits and 3 active direct Facilitators.',
                    'status' => PromotionRule::STATUS_ACTIVE,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep existing financial and rank records safe
    }
};
