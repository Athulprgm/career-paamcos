<?php

namespace Tests\Feature;

use App\Models\AgentRank;
use App\Models\Commission;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\DepositSchemeRankCommission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SyncFdCommissionSchemesTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        $master = DepositScheme::firstOrCreate(
            ['code' => 'FD7Y'],
            [
                'name' => 'FD 7 Year (84 Months)',
                'scheme_type' => 'FD',
                'tenure' => 7,
                'tenure_unit' => 'YEARS',
                'interest_rate' => 10.0,
                'total_commission_percentage' => 55.00,
                'status' => 'ACTIVE',
            ]
        );

        $facilitator = AgentRank::firstOrCreate(
            ['sort_order' => 1],
            ['name' => 'Facilitator', 'level' => 1, 'code' => 'FAC']
        );
        $advisor = AgentRank::firstOrCreate(
            ['sort_order' => 2],
            ['name' => 'Advisor', 'level' => 2, 'code' => 'ADV']
        );

        $yearTotals = [
            1 => ['fac' => 1.00, 'adv' => 1.00], // 2.00
            2 => ['fac' => 2.00, 'adv' => 3.00], // 5.00
            3 => ['fac' => 3.00, 'adv' => 3.25], // 6.25
            4 => ['fac' => 4.00, 'adv' => 3.75], // 7.75
            5 => ['fac' => 5.00, 'adv' => 5.00], // 10.00
            6 => ['fac' => 6.00, 'adv' => 5.50], // 11.50
            7 => ['fac' => 7.00, 'adv' => 5.50], // 12.50
        ];

        foreach ($yearTotals as $yr => $data) {
            DepositSchemeRankCommission::firstOrCreate(
                ['deposit_scheme_id' => $master->id, 'rank_id' => $facilitator->id, 'payout_year' => $yr],
                ['commission_percentage' => $data['fac'], 'is_eligible' => true]
            );
            DepositSchemeRankCommission::firstOrCreate(
                ['deposit_scheme_id' => $master->id, 'rank_id' => $advisor->id, 'payout_year' => $yr],
                ['commission_percentage' => $data['adv'], 'is_eligible' => true]
            );
        }

        for ($t = 1; $t <= 6; $t++) {
            DepositScheme::firstOrCreate(
                ['code' => "FD{$t}Y"],
                [
                    'name' => "FD {$t} Year (".($t * 12).' Months)',
                    'scheme_type' => 'FD',
                    'tenure' => $t,
                    'tenure_unit' => 'YEARS',
                    'interest_rate' => 7.0 + $t * 0.5,
                    'total_commission_percentage' => 0.00,
                    'status' => 'ACTIVE',
                ]
            );
        }
    }

    public function test_command_syncs_all_fd_schemes_from_master_fd_7_year(): void
    {
        $this->artisan('commission:sync-fd-schemes --no-interaction')
            ->assertSuccessful();

        $fd7 = DepositScheme::where('code', 'FD7Y')->first();
        $this->assertNotNull($fd7);
        $this->assertEquals(55.00, (float) $fd7->total_commission_percentage);

        // Verify FD 1 Year
        $fd1 = DepositScheme::where('code', 'FD1Y')->first();
        $this->assertNotNull($fd1);
        $this->assertEquals(2.00, (float) $fd1->total_commission_percentage);
        $this->assertEquals(1, DepositSchemeRankCommission::where('deposit_scheme_id', $fd1->id)->max('payout_year'));

        // Verify FD 2 Year
        $fd2 = DepositScheme::where('code', 'FD2Y')->first();
        $this->assertNotNull($fd2);
        $this->assertEquals(7.00, (float) $fd2->total_commission_percentage);
        $this->assertEquals(2, DepositSchemeRankCommission::where('deposit_scheme_id', $fd2->id)->max('payout_year'));

        // Verify FD 3 Year
        $fd3 = DepositScheme::where('code', 'FD3Y')->first();
        $this->assertNotNull($fd3);
        $this->assertEquals(13.25, (float) $fd3->total_commission_percentage);
        $this->assertEquals(3, DepositSchemeRankCommission::where('deposit_scheme_id', $fd3->id)->max('payout_year'));

        // Verify FD 4 Year
        $fd4 = DepositScheme::where('code', 'FD4Y')->first();
        $this->assertNotNull($fd4);
        $this->assertEquals(21.00, (float) $fd4->total_commission_percentage);
        $this->assertEquals(4, DepositSchemeRankCommission::where('deposit_scheme_id', $fd4->id)->max('payout_year'));

        // Verify FD 5 Year
        $fd5 = DepositScheme::where('code', 'FD5Y')->first();
        $this->assertNotNull($fd5);
        $this->assertEquals(31.00, (float) $fd5->total_commission_percentage);
        $this->assertEquals(5, DepositSchemeRankCommission::where('deposit_scheme_id', $fd5->id)->max('payout_year'));

        // Verify FD 6 Year
        $fd6 = DepositScheme::where('code', 'FD6Y')->first();
        $this->assertNotNull($fd6);
        $this->assertEquals(42.50, (float) $fd6->total_commission_percentage);
        $this->assertEquals(6, DepositSchemeRankCommission::where('deposit_scheme_id', $fd6->id)->max('payout_year'));

        // Verify Facilitator rank rates match across all schemes for each year
        $facilitator = AgentRank::where('sort_order', 1)->first();
        $this->assertNotNull($facilitator);

        // Year 1 Facilitator rate should be 1.00% across all schemes
        foreach ([$fd1, $fd2, $fd3, $fd4, $fd5, $fd6, $fd7] as $scheme) {
            $rcY1 = DepositSchemeRankCommission::where('deposit_scheme_id', $scheme->id)
                ->where('rank_id', $facilitator->id)
                ->where('payout_year', 1)
                ->first();
            $this->assertNotNull($rcY1, "Missing Y1 Facilitator rank comm for {$scheme->code}");
            $this->assertEquals(1.00, (float) $rcY1->commission_percentage);
            $this->assertTrue((bool) $rcY1->is_eligible);
        }

        // Year 2 Facilitator rate should be 2.00% across schemes with tenure >= 2
        foreach ([$fd2, $fd3, $fd4, $fd5, $fd6, $fd7] as $scheme) {
            $rcY2 = DepositSchemeRankCommission::where('deposit_scheme_id', $scheme->id)
                ->where('rank_id', $facilitator->id)
                ->where('payout_year', 2)
                ->first();
            $this->assertNotNull($rcY2, "Missing Y2 Facilitator rank comm for {$scheme->code}");
            $this->assertEquals(2.00, (float) $rcY2->commission_percentage);
            $this->assertTrue((bool) $rcY2->is_eligible);
        }
    }

    public function test_command_synchronizes_active_deposit_commissions(): void
    {
        $this->artisan('commission:sync-fd-schemes --fix-deposits --no-interaction')
            ->assertSuccessful();

        // Check active deposit DEP000015 (FD 2 Year, 60,000)
        $dep = Deposit::where('deposit_number', 'DEP000015')->first();
        if ($dep) {
            $comms = Commission::where('deposit_id', $dep->id)->orderBy('payout_year')->get();
            $this->assertCount(2, $comms);

            $this->assertEquals(1.00, (float) $comms[0]->rate);
            $this->assertEquals(600.00, (float) $comms[0]->commission_amount);

            $this->assertEquals(2.00, (float) $comms[1]->rate);
            $this->assertEquals(1200.00, (float) $comms[1]->commission_amount);
        }

        // Check active deposit DEP000011 (FD 5 Year, 50,000)
        $dep5 = Deposit::where('deposit_number', 'DEP000011')->first();
        if ($dep5) {
            $comms = Commission::where('deposit_id', $dep5->id)->orderBy('payout_year')->get();
            $this->assertCount(5, $comms);

            $expectedRates = [1 => 1.00, 2 => 2.00, 3 => 3.00, 4 => 4.00, 5 => 5.00];
            $expectedAmts = [1 => 500.00, 2 => 1000.00, 3 => 1500.00, 4 => 2000.00, 5 => 2500.00];

            foreach ($comms as $c) {
                $yr = $c->payout_year;
                $this->assertEquals($expectedRates[$yr], (float) $c->rate);
                $this->assertEquals($expectedAmts[$yr], (float) $c->commission_amount);
            }
        }
    }
}
