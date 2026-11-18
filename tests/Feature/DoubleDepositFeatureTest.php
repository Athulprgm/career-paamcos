<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DoubleDepositFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected Branch $branch;

    protected Agent $agent;

    protected Customer $customer;

    protected DepositScheme $standardScheme;

    protected DepositScheme $doublingScheme;

    protected function setUp(): void
    {
        parent::setUp();

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin Double Tester',
            'email' => 'double_tester_'.uniqid().'@example.com',
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        $this->branch = Branch::create([
            'name' => 'Double Test Branch '.uniqid(),
            'code' => 'DTB_'.strtoupper(substr(uniqid(), -4)),
            'status' => 'active',
        ]);

        $agentUser = User::factory()->create([
            'name' => 'Double Test Agent User',
            'email' => 'agent_'.uniqid().'@example.com',
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
            'name' => 'Double Test Agent',
            'email' => $agentUser->email,
            'phone' => '987654'.rand(1000, 9999),
            'rank_id' => $rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'customer_number' => 'CUST_'.strtoupper(substr(uniqid(), -5)),
            'name' => 'Double Customer',
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $this->standardScheme = DepositScheme::create([
            'name' => 'FD 1 Year Standard',
            'code' => 'FD_1Y_'.strtoupper(substr(uniqid(), -4)),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'max_amount' => 1000000,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'is_double' => false,
            'status' => DepositScheme::STATUS_ACTIVE,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
        ]);

        $this->doublingScheme = DepositScheme::create([
            'name' => 'Double Money Multiplier 5Y',
            'code' => 'DDS_5Y_'.strtoupper(substr(uniqid(), -4)),
            'scheme_type' => DepositScheme::TYPE_DOUBLE,
            'min_amount' => 1000,
            'max_amount' => 1000000,
            'tenure' => 5,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 0.00,
            'is_double' => true,
            'status' => DepositScheme::STATUS_ACTIVE,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
        ]);
    }

    public function test_calculate_maturity_amount_doubles_principal_when_is_double_flag_is_true(): void
    {
        $amount = 50000.00;
        $maturity = Deposit::calculateMaturityAmount(
            amount: $amount,
            interestRate: 0.00,
            tenure: 5,
            tenureUnit: 'YEARS',
            schemeType: 'FD',
            frequency: 'ONE_TIME',
            isDouble: true
        );

        $this->assertEquals(100000.00, $maturity);
    }

    public function test_calculate_maturity_amount_doubles_when_scheme_type_is_double(): void
    {
        $amount = 25000.00;
        $maturity = Deposit::calculateMaturityAmount(
            amount: $amount,
            interestRate: 0.00,
            tenure: 3,
            tenureUnit: 'YEARS',
            schemeType: 'DOUBLE',
            frequency: 'ONE_TIME'
        );

        $this->assertEquals(50000.00, $maturity);
    }

    public function test_can_create_deposit_with_double_feature_via_controller(): void
    {
        $depositAmount = 75000.00;

        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->standardScheme->id,
            'amount' => $depositAmount,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'start_date' => now()->toDateString(),
            'is_double' => 1,
            'payment_mode' => 'CASH',
            'status_action' => 'active',
        ]);

        $response->assertRedirect();

        $deposit = Deposit::where('customer_id', $this->customer->id)
            ->where('amount', $depositAmount)
            ->latest('id')
            ->first();

        $this->assertNotNull($deposit);
        $this->assertTrue((bool) $deposit->is_double);
        $this->assertEquals(0.00, (float) $deposit->interest_rate);
        $this->assertEquals(150000.00, (float) $deposit->maturity_amount);
        $this->assertEquals(75000.00, (float) $deposit->estimated_interest);
    }

    public function test_deposit_under_doubling_scheme_automatically_doubles_and_has_zero_interest(): void
    {
        $depositAmount = 40000.00;

        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->doublingScheme->id,
            'amount' => $depositAmount,
            'tenure' => 5,
            'tenure_unit' => 'YEARS',
            'start_date' => now()->toDateString(),
            'payment_mode' => 'CASH',
            'status_action' => 'active',
        ]);

        $response->assertRedirect();

        $deposit = Deposit::where('customer_id', $this->customer->id)
            ->where('amount', $depositAmount)
            ->latest('id')
            ->first();

        $this->assertNotNull($deposit);
        $this->assertTrue((bool) $deposit->is_double);
        $this->assertEquals(0.00, (float) $deposit->interest_rate);
        $this->assertEquals(80000.00, (float) $deposit->maturity_amount);
    }

    public function test_can_update_deposit_to_double_feature(): void
    {
        $deposit = Deposit::create([
            'deposit_number' => 'DEP_TEST_'.uniqid(),
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->standardScheme->id,
            'deposit_type' => 'FD',
            'amount' => 50000.00,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 10.00,
            'is_double' => false,
            'start_date' => now()->toDateString(),
            'maturity_date' => now()->addYear()->toDateString(),
            'maturity_amount' => 55000.00,
            'payment_mode' => 'CASH',
            'status' => Deposit::STATUS_PENDING_APPROVAL,
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->put(route('admin.deposits.update', $deposit), [
            'deposit_scheme_id' => $this->standardScheme->id,
            'agent_id' => $this->agent->id,
            'amount' => 60000.00,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'start_date' => now()->toDateString(),
            'is_double' => 1,
            'payment_mode' => 'CASH',
        ]);

        $response->assertRedirect();

        $deposit->refresh();
        $this->assertTrue((bool) $deposit->is_double);
        $this->assertEquals(60000.00, (float) $deposit->amount);
        $this->assertEquals(0.00, (float) $deposit->interest_rate);
        $this->assertEquals(120000.00, (float) $deposit->maturity_amount);
    }

    public function test_can_create_deposit_scheme_with_double_feature_via_controller(): void
    {
        $code = 'DBL_'.strtoupper(substr(uniqid(), -4));

        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposit-schemes.store'), [
            'name' => 'Double Dhanvarsha Scheme',
            'code' => $code,
            'scheme_type' => 'DOUBLE',
            'min_amount' => 5000,
            'max_amount' => 500000,
            'tenure' => 6,
            'tenure_unit' => 'YEARS',
            'is_double' => 1,
            'collection_frequency' => 'ONE_TIME',
            'status' => 'ACTIVE',
        ]);

        $response->assertRedirect();

        $scheme = DepositScheme::where('code', $code)->first();
        $this->assertNotNull($scheme);
        $this->assertTrue((bool) $scheme->is_double);
        $this->assertEquals(0.00, (float) $scheme->interest_rate);
    }

    public function test_can_update_deposit_scheme_to_double_feature_via_controller(): void
    {
        $response = $this->actingAs($this->superAdmin)->put(route('admin.deposit-schemes.update', $this->standardScheme), [
            'name' => $this->standardScheme->name,
            'code' => $this->standardScheme->code,
            'scheme_type' => 'DOUBLE',
            'min_amount' => 1000,
            'max_amount' => 1000000,
            'tenure' => 1,
            'tenure_unit' => 'YEARS',
            'is_double' => 1,
            'collection_frequency' => 'ONE_TIME',
            'status' => 'ACTIVE',
        ]);

        $response->assertRedirect();

        $this->standardScheme->refresh();
        $this->assertTrue((bool) $this->standardScheme->is_double);
        $this->assertEquals(0.00, (float) $this->standardScheme->interest_rate);
    }
}
