<?php

use App\Models\CareerLevel;
use App\Models\Deposit;
use App\Models\PromotionRule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Standard 12 rank configuration with targets (Rs):
     * FA: 3L, FO: 10L, DO: 25L, DSM: 50L, BDM: 1Cr, ADM: 1.5Cr,
     * MM: 2Cr, SMM: 3Cr, DM: 5Cr, RDM: 7.5Cr, ZDM: 10Cr, DD: 15Cr.
     *
     * @var array<int, array{name: string, code: string, target: float, commission_rate: float, description: string}>
     */
    protected array $rankConfigurations = [
        1 => [
            'name' => 'Facilitator',
            'code' => 'FACILITATOR',
            'target' => 300000.00,
            'commission_rate' => 2.00,
            'description' => 'Entry-level agent mobilizing micro-savings and building initial member network.',
        ],
        2 => [
            'name' => 'Field Officer',
            'code' => 'FIELD_OFFICER',
            'target' => 1000000.00,
            'commission_rate' => 3.00,
            'description' => 'Field-level supervisor guiding direct facilitators and expanding society deposits.',
        ],
        3 => [
            'name' => 'Development Officer',
            'code' => 'DEVELOPMENT_OFFICER',
            'target' => 2500000.00,
            'commission_rate' => 4.00,
            'description' => 'Business development coordinator handling team portfolio and collection targets.',
        ],
        4 => [
            'name' => 'Deputy Sales Manager',
            'code' => 'DEPUTY_SALES_MANAGER',
            'target' => 5000000.00,
            'commission_rate' => 5.00,
            'description' => 'Sub-branch sales supervisor overseeing agent teams and customer onboarding.',
        ],
        5 => [
            'name' => 'Business Development Manager',
            'code' => 'BUSINESS_DEVELOPMENT_MANAGER',
            'target' => 10000000.00,
            'commission_rate' => 6.00,
            'description' => 'Branch business leader driving aggregate deposit growth and agency network.',
        ],
        6 => [
            'name' => 'Area Development Manager',
            'code' => 'AREA_DEVELOPMENT_MANAGER',
            'target' => 15000000.00,
            'commission_rate' => 7.00,
            'description' => 'Multi-branch area manager coordinating regional mobilization campaigns.',
        ],
        7 => [
            'name' => 'Marketing Manager',
            'code' => 'MARKETING_MANAGER',
            'target' => 20000000.00,
            'commission_rate' => 8.00,
            'description' => 'Marketing manager leading deposit mobilization strategies and promotional campaigns.',
        ],
        8 => [
            'name' => 'Senior Marketing Manager',
            'code' => 'SENIOR_MARKETING_MANAGER',
            'target' => 30000000.00,
            'commission_rate' => 9.00,
            'description' => 'Senior marketing manager coordinating strategic campaigns across branch networks.',
        ],
        9 => [
            'name' => 'Divisional Manager',
            'code' => 'DIVISIONAL_MANAGER',
            'target' => 50000000.00,
            'commission_rate' => 10.00,
            'description' => 'Divisional executive overseeing division performance and agency operations.',
        ],
        10 => [
            'name' => 'Regional Development Manager',
            'code' => 'REGIONAL_DEVELOPMENT_MANAGER',
            'target' => 75000000.00,
            'commission_rate' => 11.00,
            'description' => 'Regional development executive driving regional expansion and team performance.',
        ],
        11 => [
            'name' => 'Zonal Development Manager',
            'code' => 'ZONAL_DEVELOPMENT_MANAGER',
            'target' => 100000000.00,
            'commission_rate' => 12.00,
            'description' => 'Zonal development director directing multi-region mobilization and network governance.',
        ],
        12 => [
            'name' => 'Deputy Director',
            'code' => 'DEPUTY_DIRECTOR',
            'target' => 150000000.00,
            'commission_rate' => 13.00,
            'description' => 'Senior executive deputy director overseeing institutional deposit targets and society agency hierarchy.',
        ],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add composition columns to promotion_rules table if not present
        Schema::table('promotion_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('promotion_rules', 'rd_composition_percentage')) {
                $table->decimal('rd_composition_percentage', 5, 2)->default(10.00)->after('min_team_deposit');
            }
            if (! Schema::hasColumn('promotion_rules', 'fd_composition_percentage')) {
                $table->decimal('fd_composition_percentage', 5, 2)->default(90.00)->after('rd_composition_percentage');
            }
        });

        // 2. Update ranks with configured target deposit amounts and commission rates
        $ranksByLevel = [];
        foreach ($this->rankConfigurations as $level => $config) {
            $rank = CareerLevel::where('level', $level)
                ->orWhere('code', $config['code'])
                ->orWhere('name', $config['name'])
                ->first();

            if ($rank) {
                $updateData = [
                    'name' => $config['name'],
                    'code' => $config['code'],
                    'level' => $level,
                    'sort_order' => $level,
                    'minimum_deposit_amount' => $config['target'],
                    'min_deposit_amount' => $config['target'],
                    'status' => 'active',
                ];

                if ((float) $rank->commission_rate <= 0.0) {
                    $updateData['commission_rate'] = $config['commission_rate'];
                }

                $rank->update($updateData);
                $ranksByLevel[$level] = $rank;
            } else {
                $created = CareerLevel::create([
                    'name' => $config['name'],
                    'code' => $config['code'],
                    'level' => $level,
                    'sort_order' => $level,
                    'minimum_deposit_amount' => $config['target'],
                    'min_deposit_amount' => $config['target'],
                    'commission_rate' => $config['commission_rate'],
                    'status' => 'active',
                    'description' => $config['description'],
                ]);
                $ranksByLevel[$level] = $created;
            }
        }

        // 3. Configure the 11 promotion rules with exact targets, 10% RD + 90% FD composition,
        // and rank qualification downline rules:
        // FO (3 FA), DO (3 FO), DSM (2 DO), BDM (2 DSM), ADM (3 DSM), MM (2 BDM),
        // SMM (3 BDM), DM (2 MM), RDM (1 DM + 1 SMM), ZDM (3 SMM), DD (3 DM).
        $ruleSpecs = [
            // Level 1 (FA) -> Level 2 (FO): Target 3L, 3 FA
            1 => [
                'from_level' => 1,
                'to_level' => 2,
                'target' => 300000.00,
                'name' => 'Facilitator to Field Officer Standard Advancement',
                'description' => 'Promotion from Facilitator (Rank 1) to Field Officer (Rank 2). Requires ₹3,00,000 eligible team deposits (10% RD + 90% FD composition) and 3 active direct Facilitators.',
                'tiers' => [
                    ['count' => 3, 'level' => 1], // 3 FA
                ],
            ],
            // Level 2 (FO) -> Level 3 (DO): Target 10L, 3 FO
            2 => [
                'from_level' => 2,
                'to_level' => 3,
                'target' => 1000000.00,
                'name' => 'Field Officer to Development Officer Standard Advancement',
                'description' => 'Promotion from Field Officer (Rank 2) to Development Officer (Rank 3). Requires ₹10,00,000 eligible team deposits (10% RD + 90% FD composition) and 3 active direct Field Officers.',
                'tiers' => [
                    ['count' => 3, 'level' => 2], // 3 FO
                ],
            ],
            // Level 3 (DO) -> Level 4 (DSM): Target 25L, 2 DO
            3 => [
                'from_level' => 3,
                'to_level' => 4,
                'target' => 2500000.00,
                'name' => 'Development Officer to Deputy Sales Manager Standard Advancement',
                'description' => 'Promotion from Development Officer (Rank 3) to Deputy Sales Manager (Rank 4). Requires ₹25,00,000 eligible team deposits (10% RD + 90% FD composition) and 2 active direct Development Officers.',
                'tiers' => [
                    ['count' => 2, 'level' => 3], // 2 DO
                ],
            ],
            // Level 4 (DSM) -> Level 5 (BDM): Target 50L, 2 DSM
            4 => [
                'from_level' => 4,
                'to_level' => 5,
                'target' => 5000000.00,
                'name' => 'Deputy Sales Manager to Business Development Manager Standard Advancement',
                'description' => 'Promotion from Deputy Sales Manager (Rank 4) to Business Development Manager (Rank 5). Requires ₹50,00,000 eligible team deposits (10% RD + 90% FD composition) and 2 active direct Deputy Sales Managers.',
                'tiers' => [
                    ['count' => 2, 'level' => 4], // 2 DSM
                ],
            ],
            // Level 5 (BDM) -> Level 6 (ADM): Target 1Cr, 3 DSM
            5 => [
                'from_level' => 5,
                'to_level' => 6,
                'target' => 10000000.00,
                'name' => 'Business Development Manager to Area Development Manager Standard Advancement',
                'description' => 'Promotion from Business Development Manager (Rank 5) to Area Development Manager (Rank 6). Requires ₹1,00,00,000 eligible team deposits (10% RD + 90% FD composition) and 3 active direct Deputy Sales Managers.',
                'tiers' => [
                    ['count' => 3, 'level' => 4], // 3 DSM
                ],
            ],
            // Level 6 (ADM) -> Level 7 (MM): Target 1.5Cr, 2 BDM
            6 => [
                'from_level' => 6,
                'to_level' => 7,
                'target' => 15000000.00,
                'name' => 'Area Development Manager to Marketing Manager Standard Advancement',
                'description' => 'Promotion from Area Development Manager (Rank 6) to Marketing Manager (Rank 7). Requires ₹1,50,00,000 eligible team deposits (10% RD + 90% FD composition) and 2 active direct Business Development Managers.',
                'tiers' => [
                    ['count' => 2, 'level' => 5], // 2 BDM
                ],
            ],
            // Level 7 (MM) -> Level 8 (SMM): Target 2Cr, 3 ADM
            7 => [
                'from_level' => 7,
                'to_level' => 8,
                'target' => 20000000.00,
                'name' => 'Marketing Manager to Senior Marketing Manager Standard Advancement',
                'description' => 'Promotion from Marketing Manager (Rank 7) to Senior Marketing Manager (Rank 8). Requires ₹2,00,00,000 eligible team deposits (10% RD + 90% FD composition) and 3 active direct Area Development Managers.',
                'tiers' => [
                    ['count' => 3, 'level' => 6], // 3 ADM
                ],
            ],
            // Level 8 (SMM) -> Level 9 (DM): Target 3Cr, 2 MM
            8 => [
                'from_level' => 8,
                'to_level' => 9,
                'target' => 30000000.00,
                'name' => 'Senior Marketing Manager to Divisional Manager Standard Advancement',
                'description' => 'Promotion from Senior Marketing Manager (Rank 8) to Divisional Manager (Rank 9). Requires ₹3,00,00,000 eligible team deposits (10% RD + 90% FD composition) and 2 active direct Marketing Managers.',
                'tiers' => [
                    ['count' => 2, 'level' => 7], // 2 MM
                ],
            ],
            // Level 9 (DM) -> Level 10 (RDM): Target 5Cr, 1 DM + 1 SMM
            9 => [
                'from_level' => 9,
                'to_level' => 10,
                'target' => 50000000.00,
                'name' => 'Divisional Manager to Regional Development Manager Standard Advancement',
                'description' => 'Promotion from Divisional Manager (Rank 9) to Regional Development Manager (Rank 10). Requires ₹5,00,00,000 eligible team deposits (10% RD + 90% FD composition) and 1 Divisional Manager plus 1 Senior Marketing Manager.',
                'tiers' => [
                    ['count' => 1, 'level' => 9], // 1 DM
                    ['count' => 1, 'level' => 8], // 1 SMM
                ],
            ],
            // Level 10 (RDM) -> Level 11 (ZDM): Target 7.5Cr, 3 DM
            10 => [
                'from_level' => 10,
                'to_level' => 11,
                'target' => 75000000.00,
                'name' => 'Regional Development Manager to Zonal Development Manager Standard Advancement',
                'description' => 'Promotion from Regional Development Manager (Rank 10) to Zonal Development Manager (Rank 11). Requires ₹7,50,00,000 eligible team deposits (10% RD + 90% FD composition) and 3 active direct Divisional Managers.',
                'tiers' => [
                    ['count' => 3, 'level' => 9], // 3 DM
                ],
            ],
            // Level 11 (ZDM) -> Level 12 (DD): Target 10Cr, 3 RDM
            11 => [
                'from_level' => 11,
                'to_level' => 12,
                'target' => 100000000.00,
                'name' => 'Zonal Development Manager to Deputy Director Standard Advancement',
                'description' => 'Promotion from Zonal Development Manager (Rank 11) to Deputy Director (Rank 12). Requires ₹10,00,00,000 eligible team deposits (10% RD + 90% FD composition) and 3 active direct Regional Development Managers.',
                'tiers' => [
                    ['count' => 3, 'level' => 10], // 3 RDM
                ],
            ],
        ];

        foreach ($ruleSpecs as $spec) {
            $fromRank = $ranksByLevel[$spec['from_level']] ?? null;
            $toRank = $ranksByLevel[$spec['to_level']] ?? null;

            if (! $fromRank || ! $toRank) {
                continue;
            }

            // Map tier levels to actual rank IDs
            $resolvedTiers = [];
            $totalDirectRequired = 0;
            $primaryQualifyingRankId = null;

            foreach ($spec['tiers'] as $tier) {
                $tierRank = $ranksByLevel[$tier['level']] ?? null;
                $tierRankId = $tierRank?->id;
                $resolvedTiers[] = [
                    'count' => (int) $tier['count'],
                    'rank_id' => $tierRankId,
                ];
                $totalDirectRequired += (int) $tier['count'];
                if ($primaryQualifyingRankId === null && $tierRankId !== null) {
                    $primaryQualifyingRankId = $tierRankId;
                }
            }

            $rule = PromotionRule::where('from_rank_id', $fromRank->id)
                ->where('to_rank_id', $toRank->id)
                ->first();

            $attributes = [
                'name' => $spec['name'],
                'from_rank_id' => $fromRank->id,
                'to_rank_id' => $toRank->id,
                'min_individual_deposit' => 0.00,
                'min_team_deposit' => $spec['target'],
                'min_business_amount' => $spec['target'],
                'min_qualifying_direct_facilitators' => $totalDirectRequired,
                'qualifying_rank_id' => $primaryQualifyingRankId ?: $fromRank->id,
                'qualifying_agent_tiers' => $resolvedTiers,
                'rd_composition_percentage' => 10.00,
                'fd_composition_percentage' => 90.00,
                'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
                'qualifying_deposit_statuses' => [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED],
                'required_agent_status' => 'active',
                'requires_approval' => true,
                'authorized_approval_role' => PromotionRule::ROLE_ADMIN,
                'description' => $spec['description'],
                'status' => PromotionRule::STATUS_ACTIVE,
            ];

            if ($rule) {
                $rule->update($attributes);
            } else {
                PromotionRule::create($attributes);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('promotion_rules', 'rd_composition_percentage')) {
            Schema::table('promotion_rules', function (Blueprint $table) {
                $table->dropColumn(['rd_composition_percentage', 'fd_composition_percentage']);
            });
        }
    }
};
