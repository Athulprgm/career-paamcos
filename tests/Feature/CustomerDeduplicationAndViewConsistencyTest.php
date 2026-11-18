<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerDeduplicationAndViewConsistencyTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $branchAdmin;

    protected Branch $branch;

    protected Agent $freeAgent;

    protected DepositScheme $scheme;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin', 'status' => 'active']);

        $this->branch = Branch::create([
            'code' => 'BR_DEDUP_'.uniqid(),
            'name' => 'Dedup Test Branch '.uniqid(),
            'address' => '101 Banking St',
            'phone' => '9876500001',
            'email' => 'branch_dedup_'.uniqid().'@test.com',
            'is_active' => true,
        ]);

        $this->superAdmin = User::factory()->create([
            'email' => 'sa_dedup_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
        ]);
        $this->superAdmin->roles()->sync([$superAdminRole->id]);

        $this->branchAdmin = User::factory()->create([
            'email' => 'ba_dedup_'.uniqid().'@test.com',
            'branch_id' => $this->branch->id,
            'password' => Hash::make('password123'),
        ]);
        $this->branchAdmin->roles()->sync([$adminRole->id]);

        $rank = AgentRank::first() ?? AgentRank::create([
            'name' => 'Advisor',
            'code' => 'ADV_'.uniqid(),
            'level' => 1,
            'status' => 'active',
        ]);

        $this->freeAgent = Agent::create([
            'name' => 'Free Field Agent '.uniqid(),
            'agent_id' => 'AG_FF_'.rand(10000, 99999),
            'member_code' => 'AG_FF_'.rand(10000, 99999),
            'email' => 'agent_ff_'.uniqid().'@test.com',
            'phone' => '9876500002',
            'branch_id' => null,
            'rank_id' => $rank->id,
            'status' => 'active',
            'joining_date' => now()->toDateString(),
        ]);

        $this->scheme = DepositScheme::create([
            'code' => 'SCH_DEDUP_'.uniqid(),
            'name' => 'Dedup FD Scheme',
            'scheme_type' => DepositScheme::TYPE_FD,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.50,
            'min_amount' => 5000,
            'max_amount' => 500000,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);
    }

    public function test_multiple_deposits_from_same_lead_with_prefilled_manual_fields_reuse_single_customer(): void
    {
        $mobile = '99'.rand(10000000, 99999999);
        $lead = Lead::create([
            'branch_id' => $this->branch->id,
            'agent_id' => $this->freeAgent->id,
            'name' => 'Nikitha Prospect',
            'mobile' => $mobile,
            'email' => 'nikitha_'.uniqid().'@example.com',
            'address' => 'Kanhagad',
            'status' => Lead::STATUS_NEW,
            'expected_amount' => 100000,
        ]);

        $firstResponse = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'lead_id' => $lead->id,
            'agent_id' => $this->freeAgent->id,
            'branch_id' => $this->branch->id,
            'manual_customer_name' => $lead->name,
            'manual_customer_mobile' => $mobile,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 10000,
            'start_date' => now()->toDateString(),
            'status_action' => 'active',
        ]);

        $firstResponse->assertSessionHasNoErrors();
        $firstResponse->assertRedirect();

        $secondResponse = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'lead_id' => $lead->id,
            'agent_id' => $this->freeAgent->id,
            'branch_id' => $this->branch->id,
            'manual_customer_name' => $lead->name,
            'manual_customer_mobile' => $mobile,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 20000,
            'start_date' => now()->toDateString(),
            'status_action' => 'pending',
        ]);

        $secondResponse->assertSessionHasNoErrors();
        $secondResponse->assertRedirect();

        $matchingCustomers = Customer::where('branch_id', $this->branch->id)
            ->where('mobile', $mobile)
            ->get();

        $this->assertCount(1, $matchingCustomers);
        $customer = $matchingCustomers->first();
        $this->assertSame(2, Deposit::where('customer_id', $customer->id)->count());

        $lead->refresh();
        $this->assertSame($customer->id, $lead->converted_customer_id);
        $this->assertSame(Lead::STATUS_CONVERTED, $lead->status);
    }

    public function test_manual_deposit_with_same_branch_name_and_mobile_reuses_existing_customer(): void
    {
        $mobile = '98'.rand(10000000, 99999999);
        $existingCustomer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Ananya Rao',
            'customer_type' => 'individual',
            'mobile' => $mobile,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->freeAgent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'agent_id' => $this->freeAgent->id,
            'branch_id' => $this->branch->id,
            'manual_customer_name' => '  ananya rao ',
            'manual_customer_mobile' => $mobile,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 15000,
            'start_date' => now()->toDateString(),
            'status_action' => 'active',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertSame(1, Customer::where('branch_id', $this->branch->id)->where('mobile', $mobile)->count());
        $this->assertSame(1, Deposit::where('customer_id', $existingCustomer->id)->count());
    }

    public function test_deleting_deposit_and_recreating_from_lead_preserves_single_customer_and_valid_status(): void
    {
        $mobile = '97'.rand(10000000, 99999999);
        $lead = Lead::create([
            'branch_id' => $this->branch->id,
            'agent_id' => $this->freeAgent->id,
            'name' => 'Deleted Deposit Prospect',
            'mobile' => $mobile,
            'status' => Lead::STATUS_NEW,
        ]);

        $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'lead_id' => $lead->id,
            'agent_id' => $this->freeAgent->id,
            'branch_id' => $this->branch->id,
            'manual_customer_name' => $lead->name,
            'manual_customer_mobile' => $mobile,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 10000,
            'start_date' => now()->toDateString(),
            'status_action' => 'active',
        ]);

        $firstDeposit = Deposit::where('lead_id', $lead->id)->firstOrFail();
        $originalCustomerId = $firstDeposit->customer_id;

        $deleteResponse = $this->actingAs($this->superAdmin)
            ->delete(route('admin.deposits.destroy', $firstDeposit));

        $deleteResponse->assertRedirect(route('admin.deposits.index'));

        $lead->refresh();
        $this->assertSame(Lead::STATUS_INTERESTED, $lead->status);
        $this->assertSame($originalCustomerId, $lead->converted_customer_id);

        $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'lead_id' => $lead->id,
            'agent_id' => $this->freeAgent->id,
            'branch_id' => $this->branch->id,
            'manual_customer_name' => $lead->name,
            'manual_customer_mobile' => $mobile,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 25000,
            'start_date' => now()->toDateString(),
            'status_action' => 'pending',
        ]);

        $this->assertSame(1, Customer::where('branch_id', $this->branch->id)->where('mobile', $mobile)->count());
        $secondDeposit = Deposit::where('lead_id', $lead->id)->firstOrFail();
        $this->assertSame($originalCustomerId, $secondDeposit->customer_id);
    }

    public function test_failed_deposit_validation_does_not_create_orphan_customer(): void
    {
        $mobile = '96'.rand(10000000, 99999999);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'agent_id' => $this->freeAgent->id,
            'branch_id' => $this->branch->id,
            'manual_customer_name' => 'Invalid Deposit Person',
            'manual_customer_mobile' => $mobile,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 100, // Below min_amount (5000)
            'start_date' => now()->toDateString(),
            'status_action' => 'active',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertDatabaseMissing('customers', [
            'mobile' => $mobile,
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_admin_customers_index_is_completely_removed(): void
    {
        $response = $this->actingAs($this->branchAdmin)->get('/admin/customers');

        $response->assertStatus(404);
    }

    public function test_admin_lead_show_renders_status_update_card_only_once(): void
    {
        $lead = Lead::create([
            'branch_id' => $this->branch->id,
            'agent_id' => $this->freeAgent->id,
            'name' => 'Single Card Lead',
            'mobile' => '94'.rand(10000000, 99999999),
            'status' => Lead::STATUS_NEW,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.leads.show', $lead));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertSame(1, substr_count($content, 'Update Status & Follow-up'));
    }

    public function test_customer_show_is_removed_and_deposit_prefill_from_customer_id_works_cleanly(): void
    {
        $customer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Prefill Profile Customer',
            'customer_type' => 'individual',
            'mobile' => '93'.rand(10000000, 99999999),
            'branch_id' => $this->branch->id,
            'agent_id' => $this->freeAgent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $showResponse = $this->actingAs($this->branchAdmin)->get("/admin/customers/{$customer->id}");
        $showResponse->assertStatus(404);

        $createResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposits.create', ['customer_id' => $customer->id]));
        $createResponse->assertOk();
        $createResponse->assertSee($customer->name);
        $createResponse->assertSee($customer->mobile);
    }
}
