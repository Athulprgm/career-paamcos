<?php

namespace Tests\Feature;

use App\Models\AgentRank;
use App\Models\DepositScheme;
use App\Models\DepositSchemeInterestRate;
use App\Models\DepositSchemeRankCommission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DepositSchemeCommissionWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::create([
            'name' => 'Super Admin Workflow Test',
            'email' => 'sa_workflow_'.uniqid().'@multicred.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        for ($i = 1; $i <= 3; $i++) {
            AgentRank::firstOrCreate(['level' => $i], [
                'code' => 'RANK_'.$i,
                'name' => 'Level '.$i,
            ]);
        }
    }

    /**
     * Test Step 1: Create FD Scheme saves the scheme exactly once.
     */
    public function test_step_1_create_fd_scheme_saves_exactly_once(): DepositScheme
    {
        $code = 'FD_WF_'.strtoupper(substr(uniqid(), -4));

        $payload = [
            'name' => 'FD 1 Year Workflow Test',
            'code' => $code,
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 5000,
            'max_amount' => null,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 9.75,
            'senior_citizen_extra_rate' => 0.50,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
            'status' => DepositScheme::STATUS_ACTIVE,
            'tenure_rates_submitted' => 1,
        ];

        $initialCount = DepositScheme::where('code', $code)->count();
        $this->assertSame(0, $initialCount);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.store'), $payload);

        $response->assertRedirect();

        $schemes = DepositScheme::where('code', $code)->get();
        $this->assertCount(1, $schemes, 'Deposit scheme must be saved exactly once');

        $scheme = $schemes->first();
        $this->assertEquals(9.75, (float) $scheme->interest_rate);
        $this->assertEquals(0.50, (float) $scheme->senior_citizen_extra_rate);

        // Verify no artificial interest rate tiers were created for single flat rate
        $this->assertEquals(0, $scheme->interestRates()->count());

        return $scheme;
    }

    /**
     * Test Step 2 & 3: Configure Commission and Edit Commission never creates a new scheme.
     */
    public function test_step_2_and_3_configure_and_edit_commission_never_creates_duplicate_scheme(): void
    {
        $scheme = $this->test_step_1_create_fd_scheme_saves_exactly_once();
        $initialTotalSchemes = DepositScheme::count();

        $ranks = AgentRank::orderBy('level')->get();
        $rankPayload = [];
        foreach ($ranks as $rank) {
            $rankPayload[] = [
                'rank_id' => $rank->id,
                'is_eligible' => 1,
                'commission_percentage' => 1.50,
            ];
        }

        // Configure commission via store endpoint
        $configurePayload = [
            'deposit_scheme_id' => $scheme->id,
            'scheme_id' => $scheme->id,
            'name' => $scheme->name,
            'code' => $scheme->code,
            'scheme_type' => $scheme->scheme_type,
            'tenure' => $scheme->tenure,
            'tenure_unit' => $scheme->tenure_unit,
            'interest_rate' => $scheme->interest_rate,
            'status' => $scheme->status,
            'payout_year' => 1,
            'action_mode' => 'update_existing',
            'ranks' => $rankPayload,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.store'), $configurePayload);

        $response->assertRedirect();

        // Scheme count must NOT have increased
        $this->assertSame($initialTotalSchemes, DepositScheme::count(), 'Configuring commission must NOT create duplicate scheme');

        // Commissions must be saved against existing scheme ID
        $schemeCommissions = DepositSchemeRankCommission::where('deposit_scheme_id', $scheme->id)->get();
        $this->assertNotEmpty($schemeCommissions);

        // Now Edit Commission
        $updatedRankPayload = array_map(function ($c) {
            $c['commission_percentage'] = 2.00;

            return $c;
        }, $rankPayload);

        $editPayload = [
            'name' => $scheme->name,
            'code' => $scheme->code,
            'scheme_type' => $scheme->scheme_type,
            'tenure' => $scheme->tenure,
            'tenure_unit' => $scheme->tenure_unit,
            'interest_rate' => $scheme->interest_rate,
            'status' => $scheme->status,
            'ranks' => $updatedRankPayload,
        ];

        $updateResponse = $this->actingAs($this->superAdmin)
            ->put(route('superadmin.fd-commissions.update', $scheme), $editPayload);

        $updateResponse->assertRedirect();

        // Scheme count must remain the same
        $this->assertSame($initialTotalSchemes, DepositScheme::count(), 'Updating commission must NOT create duplicate scheme');
    }

    /**
     * Test that even if action_mode is sent as create_new or code has a variant, selecting an existing scheme never creates a duplicate.
     */
    public function test_configure_commission_with_existing_scheme_never_creates_duplicate_scheme_variant(): void
    {
        $scheme = $this->test_step_1_create_fd_scheme_saves_exactly_once();
        $initialTotalSchemes = DepositScheme::count();

        $ranks = AgentRank::orderBy('level')->get();
        $rankPayload = [];
        foreach ($ranks as $rank) {
            $rankPayload[] = [
                'rank_id' => $rank->id,
                'is_eligible' => 1,
                'commission_percentage' => 3.00,
            ];
        }

        // Deliberately simulate old bug where action_mode was 'create_new' and code had '-1'
        $configurePayload = [
            'deposit_scheme_id' => $scheme->id,
            'name' => $scheme->name,
            'code' => $scheme->code.'-1',
            'scheme_type' => $scheme->scheme_type,
            'tenure' => $scheme->tenure,
            'tenure_unit' => $scheme->tenure_unit,
            'interest_rate' => $scheme->interest_rate,
            'status' => $scheme->status,
            'payout_year' => 1,
            'action_mode' => 'create_new',
            'ranks' => $rankPayload,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.store'), $configurePayload);

        $response->assertRedirect();

        // Scheme count MUST NOT increase
        $this->assertSame($initialTotalSchemes, DepositScheme::count(), 'Configuring commission must NEVER create duplicate scheme');

        // Commissions must be saved to the existing scheme
        $schemeCommissions = DepositSchemeRankCommission::where('deposit_scheme_id', $scheme->id)->get();
        $this->assertNotEmpty($schemeCommissions);
        $this->assertEquals(3.00, (float) $schemeCommissions->first()->commission_percentage);
    }

    /**
     * Test Step 4: Edit Deposit Scheme updates base rate and senior rate without duplicate fields or unintended tiers.
     */
    public function test_step_4_edit_deposit_scheme_updates_cleanly_without_unintended_tiers(): void
    {
        $scheme = $this->test_step_1_create_fd_scheme_saves_exactly_once();

        $updatePayload = [
            'name' => $scheme->name,
            'code' => $scheme->code,
            'scheme_type' => $scheme->scheme_type,
            'min_amount' => 5000,
            'max_amount' => 1000000,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 10.25,
            'senior_citizen_extra_rate' => 0.75,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
            'status' => DepositScheme::STATUS_ACTIVE,
            'tenure_rates_submitted' => 1,
            // tenure_rates is empty or omitted because no multi-year tiers were added
        ];

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.deposit-schemes.update', $scheme), $updatePayload);

        $response->assertRedirect();

        $scheme->refresh();
        $this->assertEquals(10.25, (float) $scheme->interest_rate);
        $this->assertEquals(0.75, (float) $scheme->senior_citizen_extra_rate);
        $this->assertEquals(0, $scheme->interestRates()->count(), 'No unintended tiers should be created on scheme update');
    }

    /**
     * Test SuperAdmin Interest Rates create and update properly.
     */
    public function test_superadmin_interest_rates_create_and_update(): void
    {
        $scheme = $this->test_step_1_create_fd_scheme_saves_exactly_once();

        $ratePayload = [
            'deposit_scheme_id' => $scheme->id,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 11.00,
            'senior_citizen_extra_rate' => 0.50,
            'effective_from' => now()->toDateString(),
            'status' => DepositSchemeInterestRate::STATUS_ACTIVE,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.interest-rates.store'), $ratePayload);

        $response->assertRedirect();

        $createdRate = DepositSchemeInterestRate::where('deposit_scheme_id', $scheme->id)
            ->where('tenure', 2)
            ->first();

        $this->assertNotNull($createdRate);
        $this->assertEquals(11.00, (float) $createdRate->interest_rate);

        // Toggle status
        $toggleResponse = $this->actingAs($this->superAdmin)
            ->patch(route('superadmin.interest-rates.toggle-status', $createdRate));

        $toggleResponse->assertRedirect();
        $createdRate->refresh();
        $this->assertTrue($createdRate->isInactive());
    }
}
