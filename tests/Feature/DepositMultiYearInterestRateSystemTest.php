<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\DepositSchemeInterestRate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DepositMultiYearInterestRateSystemTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected ?User $branchAdmin = null;

    protected ?User $agentUser = null;

    protected ?Branch $branch = null;

    protected ?Agent $agent = null;

    protected ?Customer $customer = null;

    protected ?DepositScheme $fdScheme = null;

    protected ?DepositScheme $rdScheme = null;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles & Super Admin
        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin', 'status' => 'active']);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);

        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin Financial Engineer',
            'email' => 'superadmin_fe_'.uniqid().'@multicred.test',
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        // 2. Branch
        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR_FIN_01'],
            ['name' => 'Financial Systems Test Branch', 'status' => 'active']
        );

        // 3. Branch Admin (Non-superadmin)
        $this->branchAdmin = User::factory()->create([
            'name' => 'Branch Admin Test',
            'email' => 'badmin_fe_'.uniqid().'@multicred.test',
            'branch_id' => $this->branch->id,
        ]);
        $this->branchAdmin->roles()->attach($adminRole->id);

        // 4. Agent & Rank
        $rank = AgentRank::first();
        if (! $rank) {
            $rank = AgentRank::create([
                'name' => 'Senior Advisor',
                'level' => 1,
                'min_business' => 0,
            ]);
        }

        $this->agentUser = User::factory()->create([
            'name' => 'Agent User Test',
            'email' => 'agent_fe_'.uniqid().'@multicred.test',
            'branch_id' => $this->branch->id,
        ]);
        $this->agentUser->roles()->attach($agentRole->id);

        $this->agent = Agent::create([
            'user_id' => $this->agentUser->id,
            'member_code' => 'AGT_'.rand(10000, 99999),
            'agent_id' => 'AGT_'.rand(10000, 99999),
            'name' => 'Agent Dynamic Rate',
            'email' => $this->agentUser->email,
            'phone' => '9876500001',
            'branch_id' => $this->branch->id,
            'rank_id' => $rank->id,
            'status' => 'active',
            'joining_date' => now()->toDateString(),
        ]);

        // 5. Customer
        $this->customer = Customer::create([
            'customer_number' => 'CUST_'.rand(10000, 99999),
            'name' => 'Test Investor',
            'mobile' => '9123456789',
            'email' => 'investor_'.uniqid().'@test.com',
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        // 6. Schemes
        $this->fdScheme = DepositScheme::create([
            'name' => 'Fixed Deposit Multi-Year '.uniqid(),
            'code' => 'FD_'.strtoupper(substr(uniqid(), -5)),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 10000,
            'max_amount' => 10000000,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 9.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $this->rdScheme = DepositScheme::create([
            'name' => 'Recurring Deposit Multi-Year '.uniqid(),
            'code' => 'RD_'.strtoupper(substr(uniqid(), -5)),
            'scheme_type' => DepositScheme::TYPE_RD,
            'min_amount' => 1000,
            'max_amount' => 100000,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.00,
            'collection_frequency' => DepositScheme::FREQ_MONTHLY,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);
    }

    public function test_superadmin_can_access_interest_rates_index(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.interest-rates.index'));

        $response->assertRedirect(route('admin.deposit-schemes.index'));
    }

    /**
     * Scenario 1 & 2 & 3: Super Admin configures multiple tenure rates under the same scheme.
     */
    public function test_super_admin_can_configure_multiple_rates_for_different_tenures(): void
    {
        // 1 Year: 9.50%
        $response1 = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.interest-rates.store'), [
                'deposit_scheme_id' => $this->fdScheme->id,
                'tenure' => 1,
                'tenure_unit' => 'YEARS',
                'interest_rate' => 9.50,
                'senior_citizen_extra_rate' => 0.50,
                'effective_from' => '2026-01-01',
                'effective_to' => null,
                'status' => 'ACTIVE',
                'calculation_method' => 'SIMPLE_INTEREST',
            ]);
        $response1->assertRedirect();

        // 2 Years: 10.50%
        $response2 = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.interest-rates.store'), [
                'deposit_scheme_id' => $this->fdScheme->id,
                'tenure' => 2,
                'tenure_unit' => 'YEARS',
                'interest_rate' => 10.50,
                'senior_citizen_extra_rate' => 0.50,
                'effective_from' => '2026-01-01',
                'effective_to' => null,
                'status' => 'ACTIVE',
                'calculation_method' => 'SIMPLE_INTEREST',
            ]);
        $response2->assertRedirect();

        // 3 Years: 11.00%
        $this->actingAs($this->superAdmin)->post(route('superadmin.interest-rates.store'), [
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 3,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 11.00,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        // 4 Years: 11.50%
        $this->actingAs($this->superAdmin)->post(route('superadmin.interest-rates.store'), [
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 4,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 11.50,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        // 5 Years: 12.00%
        $this->actingAs($this->superAdmin)->post(route('superadmin.interest-rates.store'), [
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 5,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 12.00,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        $this->assertDatabaseHas('deposit_scheme_interest_rates', [
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 9.50,
            'status' => 'ACTIVE',
        ]);

        $this->assertDatabaseHas('deposit_scheme_interest_rates', [
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 10.50,
            'status' => 'ACTIVE',
        ]);

        $this->assertDatabaseHas('deposit_scheme_interest_rates', [
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 5,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 12.00,
            'status' => 'ACTIVE',
        ]);

        $this->assertEquals(5, $this->fdScheme->interestRates()->count());
    }

    /**
     * Scenario 4 & 5 & 6: Retrieve correct rate for each tenure and scheme via API.
     */
    public function test_api_retrieves_correct_rate_for_tenures_and_schemes(): void
    {
        DepositSchemeInterestRate::create([
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 10.50,
            'senior_citizen_extra_rate' => 0.50,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        DepositSchemeInterestRate::create([
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 4,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 11.50,
            'senior_citizen_extra_rate' => 0.50,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        DepositSchemeInterestRate::create([
            'deposit_scheme_id' => $this->rdScheme->id,
            'tenure' => 3,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 8.75,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        // Query FD 2 Years
        $resp2Y = $this->actingAs($this->superAdmin)->getJson(
            route('admin.deposit-schemes.rate-for-tenure', [$this->fdScheme, 'tenure' => 2, 'tenure_unit' => 'YEARS'])
        );
        $resp2Y->assertOk();
        $resp2Y->assertJson([
            'success' => true,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 10.50,
            'effective_rate' => 10.50,
        ]);

        // Query FD 4 Years
        $resp4Y = $this->actingAs($this->superAdmin)->getJson(
            route('admin.deposit-schemes.rate-for-tenure', [$this->fdScheme, 'tenure' => 4, 'tenure_unit' => 'YEARS'])
        );
        $resp4Y->assertOk();
        $resp4Y->assertJson([
            'success' => true,
            'tenure' => 4,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 11.50,
            'effective_rate' => 11.50,
        ]);

        // Query RD 3 Years
        $respRD = $this->actingAs($this->superAdmin)->getJson(
            route('admin.deposit-schemes.rate-for-tenure', [$this->rdScheme, 'tenure' => 3, 'tenure_unit' => 'YEARS'])
        );
        $respRD->assertOk();
        $respRD->assertJson([
            'success' => true,
            'tenure' => 3,
            'interest_rate' => 8.75,
        ]);
    }

    /**
     * Scenario 7 & 8: Reject deposit when no active configured rate exists for tenure.
     */
    public function test_reject_deposit_when_no_applicable_rate_exists(): void
    {
        // Only 1 Year and 2 Years are configured
        DepositSchemeInterestRate::create([
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 9.50,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);
        DepositSchemeInterestRate::create([
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 10.50,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        // Attempt to create a 7-Year deposit under this scheme (no configuration exists for 7 Years)
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'deposit_scheme_id' => $this->fdScheme->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'customer_id' => $this->customer->id,
            'amount' => 50000,
            'tenure' => 7,
            'tenure_unit' => 'YEARS',
            'start_date' => '2026-06-01',
            'payment_mode' => 'CASH',
            'status_action' => 'active',
        ]);

        $response->assertSessionHasErrors('tenure');
        $this->assertDatabaseMissing('deposits', [
            'customer_id' => $this->customer->id,
            'tenure' => 7,
        ]);
    }

    /**
     * Scenario 9: Prevent duplicate or overlapping active rate configurations.
     */
    public function test_prevent_duplicate_or_overlapping_active_configurations(): void
    {
        DepositSchemeInterestRate::create([
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 10.50,
            'effective_from' => '2026-01-01',
            'effective_to' => '2026-12-31',
            'status' => 'ACTIVE',
        ]);

        // Attempt to create another active rate for 2 Years overlapping the same window
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.interest-rates.store'), [
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 11.25,
            'effective_from' => '2026-06-01', // Overlaps!
            'effective_to' => '2027-05-31',
            'status' => 'ACTIVE',
        ]);

        $response->assertSessionHasErrors('tenure');
    }

    /**
     * Scenario 10: Unauthorized users cannot modify interest rate configurations.
     */
    public function test_unauthorized_users_cannot_modify_configurations(): void
    {
        $rate = DepositSchemeInterestRate::create([
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 9.50,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        // Guest attempt
        $guestResp = $this->post(route('superadmin.interest-rates.store'), [
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 3,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 11.00,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);
        $guestResp->assertRedirect(route('superadmin.login'));

        // Branch Admin (non-superadmin) attempt
        $adminResp = $this->actingAs($this->branchAdmin)->post(route('superadmin.interest-rates.store'), [
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 3,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 11.00,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);
        $adminResp->assertForbidden();

        // Branch Admin attempt to edit/update
        $updateResp = $this->actingAs($this->branchAdmin)->put(route('superadmin.interest-rates.update', $rate), [
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 15.00,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);
        $updateResp->assertForbidden();

        $this->assertEquals(9.50, $rate->fresh()->interest_rate);
    }

    /**
     * Scenario 11: Changed rate does not alter existing historical deposits.
     */
    public function test_changed_rate_does_not_alter_existing_deposits(): void
    {
        // 1. Initial 2-Year rate is 10.50%
        $rateConfig = DepositSchemeInterestRate::create([
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 10.50,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        // 2. Customer opens a 2-Year FD for ₹1,00,000
        $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'deposit_scheme_id' => $this->fdScheme->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'customer_id' => $this->customer->id,
            'amount' => 100000,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'start_date' => '2026-02-01',
            'payment_mode' => 'CASH',
            'status_action' => 'active',
        ]);

        $deposit = Deposit::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($deposit);
        $this->assertEquals(10.50, (float) $deposit->interest_rate);
        $this->assertEquals(122102.50, (float) $deposit->maturity_amount); // 100,000 * (1 + 0.105)^2 = 122,102.50

        // 3. Super Admin changes the configured 2-Year rate to 11.50%
        $rateConfig->update([
            'interest_rate' => 11.50,
        ]);

        // 4. Verify existing deposit remains completely unchanged
        $freshDeposit = $deposit->fresh();
        $this->assertEquals(10.50, (float) $freshDeposit->interest_rate);
        $this->assertEquals(122102.50, (float) $freshDeposit->maturity_amount);
        $this->assertEquals(100000.00, (float) $freshDeposit->amount);
    }

    /**
     * Scenario 12 & 13 & 14: Verify exact maturity calculation for Customer A (2Y@10.5%) vs Customer B (4Y@11.5%).
     */
    public function test_exact_calculation_and_rate_selection_for_different_customers(): void
    {
        // Rate tiers: 2 Years @ 10.50%, 4 Years @ 11.50%
        DepositSchemeInterestRate::create([
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 10.50,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        DepositSchemeInterestRate::create([
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 4,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 11.50,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        $customerB = Customer::create([
            'customer_number' => 'CUST_'.rand(10000, 99999),
            'name' => 'Investor Customer B',
            'mobile' => '9888800002',
            'email' => 'customer_b_'.uniqid().'@test.com',
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        // Customer A: 2 Years, ₹1,00,000 -> 10.5% simple interest -> ₹21,000 interest -> ₹1,21,000 maturity
        $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'deposit_scheme_id' => $this->fdScheme->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'customer_id' => $this->customer->id,
            'amount' => 100000,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'start_date' => '2026-03-01',
            'payment_mode' => 'CASH',
            'status_action' => 'active',
        ]);

        // Customer B: 4 Years, ₹1,00,000 -> 11.5% simple interest -> ₹46,000 interest -> ₹1,46,000 maturity
        $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'deposit_scheme_id' => $this->fdScheme->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'customer_id' => $customerB->id,
            'amount' => 100000,
            'tenure' => 4,
            'tenure_unit' => 'YEARS',
            'start_date' => '2026-03-01',
            'payment_mode' => 'CASH',
            'status_action' => 'active',
        ]);

        $depositA = Deposit::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($depositA);
        $this->assertEquals(10.50, (float) $depositA->interest_rate);
        $this->assertEquals(122102.50, (float) $depositA->maturity_amount); // 100,000 * (1 + 0.105)^2

        $depositB = Deposit::where('customer_id', $customerB->id)->latest('id')->first();
        $this->assertNotNull($depositB);
        $this->assertEquals(11.50, (float) $depositB->interest_rate);
        $this->assertEquals(154560.84, (float) $depositB->maturity_amount); // 100,000 * (1 + 0.115)^4
    }

    /**
     * Scenario 15 & 16: Commissions remain independent and consistent with rank rules.
     */
    public function test_commissions_remain_unaffected_by_interest_rate(): void
    {
        DepositSchemeInterestRate::create([
            'deposit_scheme_id' => $this->fdScheme->id,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 9.50,
            'effective_from' => '2026-01-01',
            'status' => 'ACTIVE',
        ]);

        $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'deposit_scheme_id' => $this->fdScheme->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'customer_id' => $this->customer->id,
            'amount' => 50000,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'start_date' => '2026-03-01',
            'payment_mode' => 'CASH',
            'status_action' => 'active',
        ]);

        $deposit = Deposit::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($deposit);
        $this->assertEquals(50000.00, (float) $deposit->amount);
        $this->assertEquals(9.50, (float) $deposit->interest_rate);
    }
}
