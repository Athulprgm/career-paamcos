<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositCollection;
use App\Models\DepositScheme;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SimplifiedDepositWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected Branch $branch;

    protected Agent $agent;

    protected Customer $customer;

    protected DepositScheme $scheme;

    protected function setUp(): void
    {
        parent::setUp();

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::factory()->create([
            'email' => 'sa_simplified_'.uniqid().'@test.com',
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR_SMP_'.uniqid()],
            ['name' => 'Simplified Branch Test', 'is_active' => true]
        );

        $agentUser = User::factory()->create([
            'email' => 'ag_smp_'.uniqid().'@test.com',
            'branch_id' => $this->branch->id,
        ]);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $agentUser->roles()->attach($agentRole->id);

        $rank = AgentRank::first();
        if (! $rank) {
            $rank = AgentRank::create([
                'code' => 'RANK_SMP_1',
                'name' => 'Junior Associate',
                'level' => 999,
            ]);
        }

        $this->agent = Agent::create([
            'user_id' => $agentUser->id,
            'branch_id' => $this->branch->id,
            'rank_id' => $rank->id,
            'agent_id' => 'AG_SMP_'.rand(1000, 9999),
            'name' => 'Agent Simplified Test',
            'phone' => '9876543210',
            'email' => 'agent_smp_'.uniqid().'@test.com',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'created_by' => $this->superAdmin->id,
        ]);

        $this->customer = Customer::create([
            'customer_number' => 'CUST_SMP_'.rand(1000, 9999),
            'name' => 'Customer Simplified Test',
            'customer_type' => 'INDIVIDUAL',
            'mobile' => '9876543210',
            'date_of_birth' => '1990-01-01',
            'address' => 'Test Address 123',
            'pin_code' => '682001',
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->scheme = DepositScheme::create([
            'code' => 'SCH_SMP_'.uniqid(),
            'name' => 'Fixed Growth Scheme',
            'scheme_type' => DepositScheme::TYPE_FD,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.50,
            'min_amount' => 1000,
            'max_amount' => 500000,
            'is_active' => true,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    public function test_can_register_deposit_directly_active_with_custom_account_number(): void
    {
        $customDepositNumber = 'CORE-FD-'.rand(10000, 99999);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->scheme->id,
            'deposit_number' => $customDepositNumber,
            'amount' => 25000,
            'start_date' => now()->toDateString(),
            'status_action' => 'active',
        ]);

        $response->assertRedirect();

        $deposit = Deposit::where('deposit_number', $customDepositNumber)->first();
        $this->assertNotNull($deposit);
        $this->assertEquals(Deposit::STATUS_ACTIVE, $deposit->status);
        $this->assertEquals($customDepositNumber, $deposit->deposit_number);
        $this->assertNotNull($deposit->approved_at);
        $this->assertNotNull($deposit->activated_at);
        $this->assertEquals($this->superAdmin->id, $deposit->approved_by);

        // Collection schedule is automatically generated
        $collections = DepositCollection::where('deposit_id', $deposit->id)->get();
        $this->assertCount(1, $collections);
        $this->assertEquals(25000, (float) $collections->first()->due_amount);
    }

    public function test_cannot_create_deposit_without_customer_or_lead(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 10000,
            'start_date' => now()->toDateString(),
            'status_action' => 'active',
        ]);

        $response->assertSessionHasErrors('customer_id');
    }

    public function test_can_register_deposit_from_agent_lead_and_auto_convert_customer(): void
    {
        $lead = Lead::create([
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'name' => 'Agent Lead Prospect',
            'mobile' => '9845011223',
            'status' => Lead::STATUS_INTERESTED,
            'expected_amount' => 50000,
            'interested_scheme_id' => $this->scheme->id,
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'lead_id' => $lead->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 50000,
            'start_date' => now()->toDateString(),
            'status_action' => 'active',
        ]);

        $response->assertRedirect();

        // Lead should now be CONVERTED and customer created
        $lead->refresh();
        $this->assertEquals(Lead::STATUS_CONVERTED, $lead->status);
        $this->assertNotNull($lead->converted_customer_id);

        $customer = Customer::find($lead->converted_customer_id);
        $this->assertNotNull($customer);
        $this->assertEquals('Agent Lead Prospect', $customer->name);
        $this->assertEquals($this->agent->id, $customer->agent_id);

        $deposit = Deposit::where('customer_id', $customer->id)->first();
        $this->assertNotNull($deposit);
        $this->assertEquals(Deposit::STATUS_ACTIVE, $deposit->status);
        $this->assertNotNull($deposit->deposit_number);
    }

    public function test_can_register_deposit_via_manual_customer_textbox(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'manual_customer_name' => 'Rahul Sharma Manual',
            'manual_customer_mobile' => '9876543210',
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 15000,
            'start_date' => now()->toDateString(),
            'status_action' => 'active',
        ]);

        $response->assertRedirect();

        $customer = Customer::where('name', 'Rahul Sharma Manual')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('9876543210', $customer->mobile);
        $this->assertEquals($this->branch->id, $customer->branch_id);
        $this->assertEquals($this->agent->id, $customer->agent_id);
        $this->assertEquals(Customer::STATUS_ACTIVE, $customer->status);

        $deposit = Deposit::where('customer_id', $customer->id)->first();
        $this->assertNotNull($deposit);
        $this->assertEquals(Deposit::STATUS_ACTIVE, $deposit->status);
    }

    public function test_can_register_deposit_with_lead_id_passed_as_lead_prefix_customer_id(): void
    {
        $lead = Lead::create([
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'name' => 'Rahul Lead Test',
            'mobile' => '9878788767',
            'status' => Lead::STATUS_INTERESTED,
            'expected_amount' => 20000,
            'interested_scheme_id' => $this->scheme->id,
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => 'lead_'.$lead->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 20000,
            'start_date' => now()->toDateString(),
            'status_action' => 'active',
        ]);

        $response->assertRedirect();

        $lead->refresh();
        $this->assertEquals(Lead::STATUS_CONVERTED, $lead->status);
        $this->assertNotNull($lead->converted_customer_id);

        $customer = Customer::find($lead->converted_customer_id);
        $this->assertNotNull($customer);
        $this->assertEquals('Rahul Lead Test', $customer->name);
    }

    public function test_deposit_create_page_renders_simplified_form_elements(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.deposits.create'));

        $response->assertStatus(200);
        $response->assertDontSee('name="deposit_number"', false);
        $response->assertSee('agent_id');
        $response->assertSee('lead_id');
        $response->assertSee('manual_customer_name');
        $response->assertSee('manual_customer_mobile');
        $response->assertSee('deposit_scheme_id');
        $response->assertSee('amount');
        $response->assertDontSee('name="payment_reference"', false);
        $response->assertSee('start_date');
        $response->assertSee('Save &amp; Activate', false);
        $response->assertSee('scheme_amount_hint');
    }

    public function test_complete_simplified_workflow_agent_lead_to_deposit_to_commission_to_admin_approval(): void
    {
        // Setup commission rule for the scheme (5%)
        CommissionRule::create([
            'name' => 'FD 5% Standard',
            'code' => 'CR_SMP_'.uniqid(),
            'commission_type' => CommissionRule::TYPE_PERCENTAGE,
            'calculation_basis' => CommissionRule::BASIS_DEPOSIT_AMOUNT,
            'rate' => 5.00,
            'deposit_scheme_id' => $this->scheme->id,
            'rank_id' => $this->agent->rank_id,
            'effective_from' => now()->subDays(5)->toDateString(),
            'status' => CommissionRule::STATUS_ACTIVE,
        ]);

        // Step 1: Agent adds a lead with basic details
        $agentUser = $this->agent->user;
        $leadPayload = [
            'name' => 'Kiran Varma',
            'mobile' => '9895012345',
            'email' => 'kiran@example.com',
            'address' => '45 Marine Drive, Kochi',
            'interested_scheme_id' => $this->scheme->id,
            'expected_amount' => 100000,
            'status' => Lead::STATUS_INTERESTED,
        ];

        $agentResponse = $this->actingAs($agentUser)->post(route('agent.leads.store'), $leadPayload);
        $agentResponse->assertRedirect();

        $lead = Lead::where('mobile', '9895012345')->first();
        $this->assertNotNull($lead);
        $this->assertEquals($this->agent->id, $lead->agent_id);
        $this->assertEquals($this->branch->id, $lead->branch_id);

        // Step 2: Staff/Admin creates a deposit directly against that lead
        $adminDepositPayload = [
            'lead_id' => $lead->id,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 100000,
            'payment_mode' => 'CASH',
            'start_date' => now()->toDateString(),
            'status_action' => 'active',
        ];

        $depositResponse = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), $adminDepositPayload);
        $depositResponse->assertRedirect();

        // Step 3: Automatically link the deposit to the agent who originally added the lead
        $deposit = Deposit::where('lead_id', $lead->id)->first();
        $this->assertNotNull($deposit);
        $this->assertEquals($this->agent->id, $deposit->agent_id);
        $this->assertEquals($this->branch->id, $deposit->branch_id);
        $this->assertEquals(Deposit::STATUS_ACTIVE, $deposit->status);

        // Verify lead is converted
        $lead->refresh();
        $this->assertEquals(Lead::STATUS_CONVERTED, $lead->status);
        $this->assertNotNull($lead->converted_customer_id);

        // Step 4: Calculate the agent's commission using the existing commission rules in PENDING status
        $commission = Commission::where('deposit_id', $deposit->id)->first();
        $this->assertNotNull($commission);
        $this->assertEquals($this->agent->id, $commission->agent_id);
        $this->assertEquals(Commission::STATUS_PENDING, $commission->status);
        $this->assertEquals(5000.00, (float) $commission->commission_amount); // 5% of 100,000

        // Step 5: Display the commission and its status in the agent's dashboard
        $dashboardResponse = $this->actingAs($agentUser)->get(route('agent.dashboard'));
        $dashboardResponse->assertStatus(200);
        $dashboardResponse->assertSee('₹5,000.00');
        $dashboardResponse->assertSee('PENDING');
        $dashboardResponse->assertSee($deposit->deposit_number);

        // Step 6: Admin can approve or reject the commission
        $approveResponse = $this->actingAs($this->superAdmin)->post(route('admin.commissions.approve', $commission));
        $approveResponse->assertRedirect();

        // Step 7: Commission status updated to APPROVED and reflected in agent's dashboard
        $commission->refresh();
        $this->assertEquals(Commission::STATUS_APPROVED, $commission->status);
        $this->assertEquals($this->superAdmin->id, $commission->approved_by);
        $this->assertNotNull($commission->approved_at);

        $dashboardResponseApproved = $this->actingAs($agentUser)->get(route('agent.dashboard'));
        $dashboardResponseApproved->assertStatus(200);
        $dashboardResponseApproved->assertSee('APPROVED');
        $dashboardResponseApproved->assertSee('₹5,000.00');
    }

    public function test_deposit_show_page_does_not_render_mature_close_pay_or_collection_schedule(): void
    {
        $deposit = Deposit::create([
            'deposit_number' => 'DEP_SMP_'.rand(1000, 9999),
            'customer_id' => $this->customer->id,
            'deposit_scheme_id' => $this->scheme->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'amount' => 50000,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.5,
            'start_date' => now()->toDateString(),
            'maturity_date' => now()->addMonths(12)->toDateString(),
            'status' => Deposit::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.deposits.show', $deposit));

        $response->assertStatus(200);
        $response->assertDontSee('Mark as Matured');
        $response->assertDontSee('Close Account');
        $response->assertDontSee('closeModal');
        $response->assertDontSee('Collection Schedule & Installments');
        $response->assertDontSee('Record Payment');
    }

    public function test_can_create_deposit_with_selected_agent_and_manual_customer_name(): void
    {
        CommissionRule::create([
            'name' => 'FD 5% Rule',
            'code' => 'CR_MAN_'.uniqid(),
            'commission_type' => CommissionRule::TYPE_PERCENTAGE,
            'calculation_basis' => CommissionRule::BASIS_DEPOSIT_AMOUNT,
            'rate' => 5.00,
            'deposit_scheme_id' => $this->scheme->id,
            'effective_from' => now()->subDays(2)->toDateString(),
            'status' => CommissionRule::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'agent_id' => $this->agent->id,
            'manual_customer_name' => 'Sita Devi Manual',
            'manual_customer_mobile' => '9845112233',
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 50000,
            'start_date' => now()->toDateString(),
            'status_action' => 'active',
        ]);

        $response->assertRedirect();

        $customer = Customer::where('name', 'Sita Devi Manual')->first();
        $this->assertNotNull($customer);
        $this->assertEquals('9845112233', $customer->mobile);
        $this->assertEquals($this->agent->id, $customer->agent_id);
        $this->assertEquals($this->branch->id, $customer->branch_id);

        $deposit = Deposit::where('customer_id', $customer->id)->first();
        $this->assertNotNull($deposit);
        $this->assertEquals($this->agent->id, $deposit->agent_id);
        $this->assertEquals(Deposit::STATUS_ACTIVE, $deposit->status);

        // Verify commission auto calculated
        $commission = Commission::where('deposit_id', $deposit->id)->first();
        $this->assertNotNull($commission);
        $this->assertEquals($this->agent->id, $commission->agent_id);
        $this->assertEquals(2500.00, (float) $commission->commission_amount);
    }
}
