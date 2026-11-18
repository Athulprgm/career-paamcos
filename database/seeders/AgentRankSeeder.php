<?php

namespace Database\Seeders;

use App\Models\CareerLevel;
use Illuminate\Database\Seeder;

class AgentRankSeeder extends Seeder
{
    /**
     * Standard 12 career progression ranks with targets.
     *
     * @var array<int, array{name: string, code: string, target: float, commission_rate: float, description: string}>
     */
    protected array $standardRanks = [
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
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->standardRanks as $level => $rankData) {
            $existing = CareerLevel::where('level', $level)
                ->orWhere('name', $rankData['name'])
                ->orWhere('code', $rankData['code'])
                ->first();

            if ($existing) {
                $existing->update([
                    'name' => $rankData['name'],
                    'code' => $rankData['code'],
                    'level' => $level,
                    'sort_order' => $level,
                    'status' => 'active',
                    'minimum_deposit_amount' => $rankData['target'],
                    'min_deposit_amount' => $rankData['target'],
                    'commission_rate' => $rankData['commission_rate'],
                    'description' => $existing->description ?: $rankData['description'],
                ]);
            } else {
                CareerLevel::create([
                    'name' => $rankData['name'],
                    'code' => $rankData['code'],
                    'level' => $level,
                    'sort_order' => $level,
                    'status' => 'active',
                    'description' => $rankData['description'],
                    'minimum_deposit_amount' => $rankData['target'],
                    'min_deposit_amount' => $rankData['target'],
                    'commission_rate' => $rankData['commission_rate'],
                ]);
            }
        }
    }
}
