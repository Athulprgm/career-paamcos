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

class SeniorCitizenAndDoubleDepositTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;

    protected Branch $branch;

    protected Agent $agent;

    protected Customer $regularCustomer;

    protected Customer $seniorCustomer;

    protected DepositScheme $standardScheme;

    protected DepositScheme $tieredScheme;

    protected DepositScheme $doublingScheme;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->admin = User::factory()->create([
            'email' => 'admin_senior_'.uniqid().'@example.com',
        ]);
        $this->admin->roles()->attach($adminRole->id);

        $this->branch = Branch::create([
            'name' => 'Senior Test Branch '.uniqid(),
            'code' => 'STB_'.strtoupper(substr(uniqid(), -4)),
            'status' => 'active',
        ]);

        $agentUser = User::factory()->create([
            'branch_id' => $this->branch->id,
        ]);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $agentUser->roles()->attach($agentRole->id);

        $rank = AgentRank::first() ?? AgentRank::create([
            'name' => 'Advisor',
            'level' => 1,
            'min_business' => 0,
        ]);

        $this->agent = Agent::create([
            'user_id' => $agentUser->id,
            'branch_id' => $this->branch->id,
            'member_code' => 'AG_'.strtoupper(substr(uniqid(), -4)),
            'agent_id' => 'AG_'.strtoupper(substr(uniqid(), -4)),
            'name' => 'Senior Test Agent',
            'email' => $agentUser->email,
            'phone' => '987654'.rand(1000, 9999),
            'rank_id' => $rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        // Regular customer (age 35)
        $this->regularCustomer = Customer::create([
            'customer_number' => 'CUST_'.strtoupper(substr(uniqid(), -5)),
            'name' => 'Regular Customer (35)',
            'date_of_birth' => now()->subYears(35)->toDateString(),
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        // Senior citizen customer (age 65)
        $this->seniorCustomer = Customer::create([
            'customer_number' => 'CUST_'.strtoupper(substr(uniqid(), -5)),
            'name' => 'Senior Customer (65)',
            'date_of_birth' => now()->subYears(65)->toDateString(),
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        // Standard scheme with base tenure = 1 year, rate = 8.00%, senior extra = 0.50%
        $this->standardScheme = DepositScheme::create([
            'name' => 'Standard FD 1 Year',
            'code' => 'STD_1Y_'.strtoupper(substr(uniqid(), -4)),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'max_amount' => 1000000,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 8.00,
            'senior_citizen_extra_rate' => 0.50,
            'is_double' => false,
            'status' => DepositScheme::STATUS_ACTIVE,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
        ]);

        // Tiered scheme with multi-year interest rate tiers
        $this->tieredScheme = DepositScheme::create([
            'name' => 'Multi-Tier FD Scheme',
            'code' => 'TIER_'.strtoupper(substr(uniqid(), -4)),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'max_amount' => 1000000,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 7.00,
            'senior_citizen_extra_rate' => 0.50,
            'is_double' => false,
            'status' => DepositScheme::STATUS_ACTIVE,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
        ]);

        DepositSchemeInterestRate::create([
            'deposit_scheme_id' => $this->tieredScheme->id,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 7.50,
            'senior_citizen_extra_rate' => 0.50,
            'effective_from' => '2020-01-01',
            'status' => DepositSchemeInterestRate::STATUS_ACTIVE,
        ]);

        DepositSchemeInterestRate::create([
            'deposit_scheme_id' => $this->tieredScheme->id,
            'tenure' => 3,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 8.50,
            'senior_citizen_extra_rate' => 0.75,
            'effective_from' => '2020-01-01',
            'status' => DepositSchemeInterestRate::STATUS_ACTIVE,
        ]);

        // Doubling scheme: 6 Years fixed 2x payout
        $this->doublingScheme = DepositScheme::create([
            'name' => 'FD 6 Year Doubling Plan',
            'code' => 'DBL_6Y_'.strtoupper(substr(uniqid(), -4)),
            'scheme_type' => DepositScheme::TYPE_DOUBLE,
            'min_amount' => 1000,
            'max_amount' => 1000000,
            'tenure' => 6,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 0.00,
            'senior_citizen_extra_rate' => 0.00,
            'is_double' => true,
            'status' => DepositScheme::STATUS_ACTIVE,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
        ]);
    }

    public function test_senior_citizen_customer_by_dob_automatically_receives_extra_rate(): void
    {
        $amount = 100000.00;

        $response = $this->actingAs($this->admin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->seniorCustomer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->standardScheme->id,
            'amount' => $amount,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'start_date' => now()->toDateString(),
            'payment_mode' => 'CASH',
            'status_action' => 'active',
        ]);

        $response->assertRedirect();

        $deposit = Deposit::where('customer_id', $this->seniorCustomer->id)->latest('id')->first();
        $this->assertNotNull($deposit);
        $this->assertTrue((bool) $deposit->is_senior_citizen);
        $this->assertTrue($deposit->isSeniorCitizen());
        // Base 8.00% + 0.50% senior extra = 8.50%
        $this->assertEquals(8.50, (float) $deposit->interest_rate);
        $expectedMaturity = round(100000.00 * (1 + 0.085), 2);
        $this->assertEquals($expectedMaturity, (float) $deposit->maturity_amount);
    }

    public function test_regular_customer_with_explicit_senior_citizen_checkbox_receives_extra_rate(): void
    {
        $amount = 50000.00;

        $response = $this->actingAs($this->admin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->regularCustomer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->standardScheme->id,
            'is_senior_citizen' => 1,
            'amount' => $amount,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'start_date' => now()->toDateString(),
            'payment_mode' => 'CASH',
            'status_action' => 'active',
        ]);

        $response->assertRedirect();

        $deposit = Deposit::where('customer_id', $this->regularCustomer->id)->latest('id')->first();
        $this->assertNotNull($deposit);
        $this->assertTrue((bool) $deposit->is_senior_citizen);
        $this->assertEquals(8.50, (float) $deposit->interest_rate);
    }

    public function test_tiered_scheme_applies_tier_specific_senior_citizen_rate(): void
    {
        $amount = 100000.00;

        // Choose 3 years tier: base 8.50% + senior extra 0.75% = 9.25%
        $response = $this->actingAs($this->admin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->seniorCustomer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->tieredScheme->id,
            'tenure' => 3,
            'tenure_unit' => 'YEARS',
            'amount' => $amount,
            'start_date' => now()->toDateString(),
            'payment_mode' => 'CASH',
            'status_action' => 'active',
        ]);

        $response->assertRedirect();

        $deposit = Deposit::where('customer_id', $this->seniorCustomer->id)->latest('id')->first();
        $this->assertNotNull($deposit);
        $this->assertTrue((bool) $deposit->is_senior_citizen);
        $this->assertEquals(9.25, (float) $deposit->interest_rate);
    }

    public function test_amount_doubling_scheme_doubles_principal_and_does_not_apply_senior_interest(): void
    {
        $amount = 60000.00;

        // Senior citizen applies for a doubling scheme
        $response = $this->actingAs($this->admin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->seniorCustomer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->doublingScheme->id,
            'is_senior_citizen' => 1,
            'amount' => $amount,
            'tenure' => 6,
            'tenure_unit' => 'YEARS',
            'start_date' => now()->toDateString(),
            'payment_mode' => 'CASH',
            'status_action' => 'active',
        ]);

        $response->assertRedirect();

        $deposit = Deposit::where('customer_id', $this->seniorCustomer->id)->latest('id')->first();
        $this->assertNotNull($deposit);
        $this->assertTrue((bool) $deposit->is_double);
        $this->assertTrue($deposit->isDouble());
        // Doubling scheme has 0% interest and exactly 2x maturity
        $this->assertEquals(0.00, (float) $deposit->interest_rate);
        $this->assertEquals(120000.00, (float) $deposit->maturity_amount);
        $this->assertEquals(60000.00, (float) $deposit->estimated_interest);
    }

    public function test_can_update_deposit_with_senior_citizen_status(): void
    {
        $deposit = Deposit::create([
            'deposit_number' => 'DEP_SENIOR_'.uniqid(),
            'customer_id' => $this->regularCustomer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->standardScheme->id,
            'deposit_type' => 'FD',
            'amount' => 50000.00,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 8.00,
            'is_double' => false,
            'is_senior_citizen' => false,
            'start_date' => now()->toDateString(),
            'maturity_date' => now()->addYear()->toDateString(),
            'maturity_amount' => 54000.00,
            'payment_mode' => 'CASH',
            'status' => Deposit::STATUS_PENDING_APPROVAL,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.deposits.update', $deposit), [
            'deposit_scheme_id' => $this->standardScheme->id,
            'agent_id' => $this->agent->id,
            'amount' => 50000.00,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'start_date' => now()->toDateString(),
            'is_senior_citizen' => 1,
            'is_double' => 0,
            'payment_mode' => 'CASH',
        ]);

        $response->assertRedirect();

        $deposit->refresh();
        $this->assertTrue((bool) $deposit->is_senior_citizen);
        $this->assertEquals(8.50, (float) $deposit->interest_rate);
        $this->assertEquals(54250.00, (float) $deposit->maturity_amount);
    }

    public function test_can_update_deposit_to_double_feature_disabling_interest(): void
    {
        $deposit = Deposit::create([
            'deposit_number' => 'DEP_DBL_'.uniqid(),
            'customer_id' => $this->seniorCustomer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->standardScheme->id,
            'deposit_type' => 'FD',
            'amount' => 80000.00,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 8.50,
            'is_double' => false,
            'is_senior_citizen' => true,
            'start_date' => now()->toDateString(),
            'maturity_date' => now()->addYear()->toDateString(),
            'maturity_amount' => 86800.00,
            'payment_mode' => 'CASH',
            'status' => Deposit::STATUS_PENDING_APPROVAL,
            'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.deposits.update', $deposit), [
            'deposit_scheme_id' => $this->standardScheme->id,
            'agent_id' => $this->agent->id,
            'amount' => 80000.00,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'start_date' => now()->toDateString(),
            'is_double' => 1,
            'payment_mode' => 'CASH',
        ]);

        $response->assertRedirect();

        $deposit->refresh();
        $this->assertTrue((bool) $deposit->is_double);
        $this->assertEquals(0.00, (float) $deposit->interest_rate);
        $this->assertEquals(160000.00, (float) $deposit->maturity_amount);
    }

    public function test_create_and_edit_deposit_views_render_senior_and_double_elements(): void
    {
        $createResponse = $this->actingAs($this->admin)->get(route('admin.deposits.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertSee('is_senior_citizen');
        $createResponse->assertSee('is_double');
        $createResponse->assertSee('Senior Citizen');

        $deposit = Deposit::create([
            'deposit_number' => 'DEP_VIEW_'.uniqid(),
            'customer_id' => $this->seniorCustomer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->standardScheme->id,
            'deposit_type' => 'FD',
            'amount' => 20000.00,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 8.50,
            'is_double' => false,
            'is_senior_citizen' => true,
            'start_date' => now()->toDateString(),
            'maturity_date' => now()->addYear()->toDateString(),
            'maturity_amount' => 21700.00,
            'payment_mode' => 'CASH',
            'status' => Deposit::STATUS_PENDING_APPROVAL,
            'created_by' => $this->admin->id,
        ]);

        $editResponse = $this->actingAs($this->admin)->get(route('admin.deposits.edit', $deposit));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('is_senior_citizen');
        $editResponse->assertSee('is_double');
        $editResponse->assertSee('checked', false); // Senior citizen is checked

        $showResponse = $this->actingAs($this->admin)->get(route('admin.deposits.show', $deposit));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Senior Citizen');
    }

    public function test_deposit_create_locks_interest_rate_and_double_toggle_to_scheme(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.deposits.create'));

        $response->assertOk();
        $response->assertSee('id="interest_rate_display" class="form-control bg-light bank-mono-input" readonly', false);
        $response->assertSee('type="hidden" name="is_double" id="is_double_hidden"', false);
        $response->assertDontSee('id="is_double"', false);
    }

    public function test_deposit_scheme_rates_endpoint_returns_scheme_limits_and_double_status(): void
    {
        $standardResponse = $this->actingAs($this->admin)->getJson(
            route('admin.deposit-schemes.rates', $this->tieredScheme)
        );

        $standardResponse->assertOk()
            ->assertJson([
                'success' => true,
                'scheme_id' => $this->tieredScheme->id,
                'is_double' => false,
                'min_amount' => 1000,
                'max_amount' => 1000000,
                'default_interest_rate' => 7.0,
            ])
            ->assertJsonCount(2, 'rates')
            ->assertJsonFragment([
                'tenure' => 3,
                'tenure_unit' => 'YEARS',
                'interest_rate' => 8.5,
                'formatted_tenure' => '3 Years',
            ]);

        $doubleResponse = $this->actingAs($this->admin)->getJson(
            route('admin.deposit-schemes.rates', $this->doublingScheme)
        );

        $doubleResponse->assertOk()
            ->assertJson([
                'success' => true,
                'scheme_id' => $this->doublingScheme->id,
                'is_double' => true,
                'min_amount' => 1000,
                'default_interest_rate' => 0.0,
            ]);
    }
}
