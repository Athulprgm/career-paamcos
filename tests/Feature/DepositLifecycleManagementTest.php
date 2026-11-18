<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerKyc;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DepositLifecycleManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected ?User $branchAdmin1 = null;

    protected ?User $branchAdmin2 = null;

    protected ?User $agentUser1 = null;

    protected ?User $agentUser2 = null;

    protected ?Branch $branch1 = null;

    protected ?Branch $branch2 = null;

    protected ?Agent $agent1 = null;

    protected ?Agent $agent2 = null;

    protected ?Agent $inactiveAgent = null;

    protected ?DepositScheme $activeScheme = null;

    protected ?DepositScheme $inactiveScheme = null;

    protected ?Customer $eligibleCustomer1 = null;

    protected ?Customer $pendingKycCustomer = null;

    protected ?Customer $rejectedKycCustomer = null;

    protected ?Customer $inactiveCustomer = null;

    protected ?Customer $customerBranch2 = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Super Admin
        $this->superAdmin = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->first();
        if (! $this->superAdmin) {
            $this->superAdmin = User::factory()->create([
                'name' => 'Super Admin Test',
                'email' => 'superadmin_'.uniqid().'@multicred.com',
            ]);
            $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
            $this->superAdmin->roles()->attach($saRole->id);
        }

        // Branches
        $this->branch1 = Branch::firstOrCreate(
            ['code' => 'BR_TEST_1'],
            ['name' => 'Branch One Test', 'is_active' => true]
        );

        $this->branch2 = Branch::firstOrCreate(
            ['code' => 'BR_TEST_2'],
            ['name' => 'Branch Two Test', 'is_active' => true]
        );

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin', 'status' => 'active']);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $rank = AgentRank::first();

        // Branch Admin 1
        $this->branchAdmin1 = User::create([
            'name' => 'Branch Admin 1 '.uniqid(),
            'email' => 'badmin1_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch1->id,
        ]);
        $this->branchAdmin1->roles()->attach($adminRole->id);

        // Branch Admin 2
        $this->branchAdmin2 = User::create([
            'name' => 'Branch Admin 2 '.uniqid(),
            'email' => 'badmin2_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch2->id,
        ]);
        $this->branchAdmin2->roles()->attach($adminRole->id);

        // Agent 1 (Branch 1)
        $this->agentUser1 = User::create([
            'name' => 'Agent User 1 '.uniqid(),
            'email' => 'agent1_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch1->id,
        ]);
        $this->agentUser1->roles()->attach($agentRole->id);

        $this->agent1 = Agent::create([
            'user_id' => $this->agentUser1->id,
            'member_code' => 'AG1_'.rand(1000, 9999),
            'agent_id' => 'AG1_'.rand(1000, 9999),
            'name' => 'Agent One '.uniqid(),
            'email' => $this->agentUser1->email,
            'phone' => '9876543210',
            'branch_id' => $this->branch1->id,
            'rank_id' => $rank?->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        // Agent 2 (Branch 2)
        $this->agentUser2 = User::create([
            'name' => 'Agent User 2 '.uniqid(),
            'email' => 'agent2_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch2->id,
        ]);
        $this->agentUser2->roles()->attach($agentRole->id);

        $this->agent2 = Agent::create([
            'user_id' => $this->agentUser2->id,
            'member_code' => 'AG2_'.rand(1000, 9999),
            'agent_id' => 'AG2_'.rand(1000, 9999),
            'name' => 'Agent Two '.uniqid(),
            'email' => $this->agentUser2->email,
            'phone' => '9876543211',
            'branch_id' => $this->branch2->id,
            'rank_id' => $rank?->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        // Inactive Agent (Branch 1)
        $this->inactiveAgent = Agent::create([
            'member_code' => 'AGIN_'.rand(1000, 9999),
            'agent_id' => 'AGIN_'.rand(1000, 9999),
            'name' => 'Inactive Agent '.uniqid(),
            'email' => 'inactive_'.uniqid().'@example.com',
            'phone' => '9876543212',
            'branch_id' => $this->branch1->id,
            'rank_id' => $rank?->id,
            'joining_date' => now()->toDateString(),
            'status' => 'inactive',
        ]);

        // Schemes
        $this->activeScheme = DepositScheme::create([
            'name' => 'Fixed Deposit Plus '.uniqid(),
            'code' => 'FD_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'description' => 'Standard test fixed deposit',
            'min_amount' => '1000.00',
            'max_amount' => '100000.00',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => '7.50',
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->inactiveScheme = DepositScheme::create([
            'name' => 'Inactive Scheme '.uniqid(),
            'code' => 'DIS_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_RD,
            'description' => 'Suspended scheme',
            'min_amount' => '500.00',
            'max_amount' => '50000.00',
            'tenure' => 6,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => '6.00',
            'collection_frequency' => DepositScheme::FREQ_MONTHLY,
            'status' => DepositScheme::STATUS_INACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        // Eligible Customer (Active + Approved KYC in Branch 1 under Agent 1)
        $this->eligibleCustomer1 = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Eligible Customer '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '9876500001',
            'date_of_birth' => '1990-01-01',
            'address' => '123 Main Street',
            'pin_code' => '682001',
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);
        CustomerKyc::create([
            'customer_id' => $this->eligibleCustomer1->id,
            'id_type' => 'Aadhaar Card',
            'id_number' => '123456789012',
            'status' => CustomerKyc::STATUS_APPROVED,
            'verified_by' => $this->superAdmin->id,
            'verified_at' => now(),
        ]);

        // Customer with Pending KYC
        $this->pendingKycCustomer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Pending KYC Customer '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '9876500002',
            'date_of_birth' => '1992-05-15',
            'address' => '456 Second Street',
            'pin_code' => '682002',
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'status' => Customer::STATUS_PENDING,
            'created_by' => $this->superAdmin->id,
        ]);
        CustomerKyc::create([
            'customer_id' => $this->pendingKycCustomer->id,
            'id_type' => 'PAN Card',
            'id_number' => 'ABCDE1234F',
            'status' => CustomerKyc::STATUS_PENDING,
        ]);

        // Customer with Rejected KYC
        $this->rejectedKycCustomer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Rejected KYC Customer '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '9876500003',
            'date_of_birth' => '1988-10-20',
            'address' => '789 Third Street',
            'pin_code' => '682003',
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'status' => Customer::STATUS_REJECTED,
            'created_by' => $this->superAdmin->id,
        ]);
        CustomerKyc::create([
            'customer_id' => $this->rejectedKycCustomer->id,
            'id_type' => 'Passport',
            'id_number' => 'P1234567',
            'status' => CustomerKyc::STATUS_REJECTED,
            'rejection_reason' => 'Blurry document copy',
        ]);

        // Inactive Customer
        $this->inactiveCustomer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Inactive Customer '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '9876500004',
            'date_of_birth' => '1995-12-12',
            'address' => '101 Inactive Ave',
            'pin_code' => '682004',
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'status' => Customer::STATUS_INACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);
        CustomerKyc::create([
            'customer_id' => $this->inactiveCustomer->id,
            'id_type' => 'Voter ID',
            'id_number' => 'VOT123456',
            'status' => CustomerKyc::STATUS_APPROVED,
        ]);

        // Customer in Branch 2 (Eligible in Branch 2 under Agent 2)
        $this->customerBranch2 = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Branch 2 Customer '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '9876500005',
            'date_of_birth' => '1993-03-03',
            'address' => '555 Branch Two Blvd',
            'pin_code' => '682005',
            'branch_id' => $this->branch2->id,
            'agent_id' => $this->agent2->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);
        CustomerKyc::create([
            'customer_id' => $this->customerBranch2->id,
            'id_type' => 'Aadhaar Card',
            'id_number' => '999988887777',
            'status' => CustomerKyc::STATUS_APPROVED,
            'verified_by' => $this->branchAdmin2->id,
            'verified_at' => now(),
        ]);
    }

    // ─── 1. DEPOSIT SCHEME TESTS ─────────────────────────────────────────────

    public function test_deposit_scheme_can_be_created_by_super_admin(): void
    {
        $code = 'RD_TEST_'.rand(100, 999);
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposit-schemes.store'), [
            'name' => 'Recurring Deposit Plan',
            'code' => $code,
            'scheme_type' => DepositScheme::TYPE_RD,
            'description' => 'Monthly compounding scheme',
            'min_amount' => '500.00',
            'max_amount' => '50000.00',
            'tenure' => 24,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => '8.25',
            'collection_frequency' => DepositScheme::FREQ_MONTHLY,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('deposit_schemes', [
            'code' => $code,
            'scheme_type' => DepositScheme::TYPE_RD,
            'status' => DepositScheme::STATUS_ACTIVE,
            'tenure' => 24,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'deposit_scheme_created',
            'auditable_type' => DepositScheme::class,
        ]);
    }

    public function test_deposit_scheme_code_must_be_unique(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposit-schemes.store'), [
            'name' => 'Duplicate Scheme',
            'code' => $this->activeScheme->code, // Duplicate!
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => '1000.00',
            'max_amount' => '20000.00',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => '7.00',
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_deposit_scheme_min_amount_cannot_exceed_max_amount(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposit-schemes.store'), [
            'name' => 'Invalid Bounds Scheme',
            'code' => 'INV_BOUND_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => '50000.00',
            'max_amount' => '10000.00', // max < min!
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => '7.00',
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $response->assertSessionHasErrors(['max_amount']);
    }

    public function test_deposit_scheme_can_be_toggled_active_and_inactive(): void
    {
        $this->assertTrue($this->activeScheme->isActive());

        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposit-schemes.toggle-status', $this->activeScheme));
        $response->assertRedirect();

        $this->activeScheme->refresh();
        $this->assertTrue($this->activeScheme->isInactive());

        // Toggle back to active
        $this->actingAs($this->superAdmin)->post(route('admin.deposit-schemes.toggle-status', $this->activeScheme));
        $this->activeScheme->refresh();
        $this->assertTrue($this->activeScheme->isActive());
    }

    // ─── 2. DEPOSIT CREATION & ELIGIBILITY TESTS ─────────────────────────────

    public function test_successful_deposit_creation_flow(): void
    {
        $startDate = now()->toDateString();
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '15000.00',
            'start_date' => $startDate,
            'notes' => 'Initial fixed deposit account',
        ]);

        $response->assertRedirect();
        $deposit = Deposit::where('customer_id', $this->eligibleCustomer1->id)->latest('id')->first();
        $this->assertNotNull($deposit);

        // Verify Deposit Number is unique and does not expose raw DB ID
        $this->assertStringStartsWith('DEP', $deposit->deposit_number);
        $this->assertNotEquals((string) $deposit->id, $deposit->deposit_number);

        // Verify Lifecycle Initial Status
        $this->assertEquals(Deposit::STATUS_PENDING_APPROVAL, $deposit->status);

        // Verify Maturity Date calculation = Start Date + Tenure (12 months)
        $expectedMaturity = Carbon::parse($startDate)->addMonths(12)->toDateString();
        $this->assertEquals($expectedMaturity, $deposit->maturity_date->toDateString());

        // Verify Amount stored accurately in DECIMAL
        $this->assertEquals('15000.00', $deposit->amount);

        // Verify Audit Log
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'deposit_created',
            'auditable_type' => Deposit::class,
            'auditable_id' => $deposit->id,
        ]);
    }

    public function test_inactive_scheme_cannot_be_used_for_new_deposit(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->inactiveScheme->id, // INACTIVE!
            'amount' => '5000.00',
            'start_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['deposit_scheme_id']);
    }

    public function test_inactive_customer_cannot_create_deposit(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->inactiveCustomer->id, // INACTIVE!
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '5000.00',
            'start_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['customer_id']);
    }

    public function test_customer_with_pending_kyc_cannot_create_deposit(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->pendingKycCustomer->id, // PENDING KYC!
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '5000.00',
            'start_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['customer_id']);
    }

    public function test_customer_with_rejected_kyc_cannot_create_deposit(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->rejectedKycCustomer->id, // REJECTED KYC!
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '5000.00',
            'start_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['customer_id']);
    }

    public function test_amount_below_minimum_fails_validation(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '500.00', // Minimum is 1000.00!
            'start_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    public function test_amount_above_maximum_fails_validation(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '150000.00', // Maximum is 100000.00!
            'start_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['amount']);
    }

    public function test_cross_branch_customer_and_deposit_branch_fails_validation(): void
    {
        // Customer belongs to branch 1, but deposit submitted under branch 2
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch2->id, // MISMATCH!
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '5000.00',
            'start_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['branch_id']);
    }

    public function test_cross_branch_agent_fails_validation(): void
    {
        // Customer belongs to branch 1, but agent belongs to branch 2
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent2->id, // Agent belongs to branch 2!
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '5000.00',
            'start_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['agent_id']);
    }

    public function test_inactive_agent_fails_validation(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->inactiveAgent->id, // INACTIVE!
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '5000.00',
            'start_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors(['agent_id']);
    }

    // ─── 3. LIFECYCLE WORKFLOW TESTS ─────────────────────────────────────────

    public function test_deposit_approval_and_activation_workflow(): void
    {
        $deposit = Deposit::create([
            'deposit_number' => Deposit::generateDepositNumber(),
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '20000.00',
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'collection_frequency' => 'ONE_TIME',
            'status' => Deposit::STATUS_PENDING_APPROVAL,
            'created_by' => $this->branchAdmin1->id,
        ]);

        $this->assertTrue($deposit->canBeApproved());

        // Approve
        $response = $this->actingAs($this->branchAdmin1)->post(route('admin.deposits.approve', $deposit));
        $response->assertRedirect();

        $deposit->refresh();
        $this->assertEquals(Deposit::STATUS_APPROVED, $deposit->status);
        $this->assertEquals($this->branchAdmin1->id, $deposit->approved_by);
        $this->assertNotNull($deposit->approved_at);

        // Activate
        $this->assertTrue($deposit->canBeActivated());
        $response = $this->actingAs($this->branchAdmin1)->post(route('admin.deposits.activate', $deposit));
        $response->assertRedirect();

        $deposit->refresh();
        $this->assertEquals(Deposit::STATUS_ACTIVE, $deposit->status);
        $this->assertNotNull($deposit->activated_at);
    }

    public function test_deposit_rejection_workflow(): void
    {
        $deposit = Deposit::create([
            'deposit_number' => Deposit::generateDepositNumber(),
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '10000.00',
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'collection_frequency' => 'ONE_TIME',
            'status' => Deposit::STATUS_PENDING_APPROVAL,
            'created_by' => $this->branchAdmin1->id,
        ]);

        $response = $this->actingAs($this->branchAdmin1)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'Customer requested cancellation before account processing',
        ]);
        $response->assertRedirect();

        $deposit->refresh();
        $this->assertEquals(Deposit::STATUS_REJECTED, $deposit->status);
        $this->assertEquals($this->branchAdmin1->id, $deposit->rejected_by);
        $this->assertNotNull($deposit->rejected_at);
        $this->assertEquals('Customer requested cancellation before account processing', $deposit->rejection_reason);
    }

    public function test_deposit_maturity_and_closure_workflow(): void
    {
        $deposit = Deposit::create([
            'deposit_number' => Deposit::generateDepositNumber(),
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '25000.00',
            'start_date' => now()->subMonths(12)->toDateString(),
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'collection_frequency' => 'ONE_TIME',
            'status' => Deposit::STATUS_ACTIVE,
            'created_by' => $this->branchAdmin1->id,
            'activated_at' => now()->subMonths(12),
        ]);

        // Mature
        $this->assertTrue($deposit->canBeMatured());
        $response = $this->actingAs($this->branchAdmin1)->post(route('admin.deposits.mature', $deposit));
        $response->assertRedirect();

        $deposit->refresh();
        $this->assertEquals(Deposit::STATUS_MATURED, $deposit->status);
        $this->assertNotNull($deposit->matured_at);

        // Close
        $this->assertTrue($deposit->canBeClosed());
        $response = $this->actingAs($this->branchAdmin1)->post(route('admin.deposits.close', $deposit), [
            'closure_notes' => 'Payout credited to savings ledger',
        ]);
        $response->assertRedirect();

        $deposit->refresh();
        $this->assertEquals(Deposit::STATUS_CLOSED, $deposit->status);
        $this->assertNotNull($deposit->closed_at);
        $this->assertEquals($this->branchAdmin1->id, $deposit->closed_by);
    }

    public function test_arbitrary_or_invalid_status_transitions_are_blocked(): void
    {
        // 1. Rejected deposit cannot be approved
        $rejectedDeposit = Deposit::create([
            'deposit_number' => Deposit::generateDepositNumber(),
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '10000.00',
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'collection_frequency' => 'ONE_TIME',
            'status' => Deposit::STATUS_REJECTED,
        ]);

        $this->assertFalse($rejectedDeposit->canBeApproved());
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.approve', $rejectedDeposit));
        $response->assertSessionHas('error');
        $this->assertEquals(Deposit::STATUS_REJECTED, $rejectedDeposit->fresh()->status);

        // 2. Draft cannot be approved directly
        $draftDeposit = Deposit::create([
            'deposit_number' => Deposit::generateDepositNumber(),
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '10000.00',
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'collection_frequency' => 'ONE_TIME',
            'status' => Deposit::STATUS_DRAFT,
        ]);

        $this->assertFalse($draftDeposit->canBeApproved());
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.approve', $draftDeposit));
        $response->assertSessionHas('error');
        $this->assertEquals(Deposit::STATUS_DRAFT, $draftDeposit->fresh()->status);
    }

    // ─── 4. ACCESS CONTROL & BRANCH ISOLATION TESTS ──────────────────────────

    public function test_branch_admin_cannot_view_or_modify_cross_branch_deposit(): void
    {
        // Deposit in Branch 2
        $branch2Deposit = Deposit::create([
            'deposit_number' => Deposit::generateDepositNumber(),
            'customer_id' => $this->customerBranch2->id,
            'branch_id' => $this->branch2->id,
            'agent_id' => $this->agent2->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '10000.00',
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'collection_frequency' => 'ONE_TIME',
            'status' => Deposit::STATUS_PENDING_APPROVAL,
        ]);

        // Branch Admin 1 (Branch 1) attempts to view Branch 2 Deposit
        $response = $this->actingAs($this->branchAdmin1)->get(route('admin.deposits.show', $branch2Deposit));
        $response->assertStatus(403);

        // Branch Admin 1 attempts to approve Branch 2 Deposit
        $response = $this->actingAs($this->branchAdmin1)->post(route('admin.deposits.approve', $branch2Deposit));
        $response->assertStatus(403);
    }

    public function test_agent_cannot_approve_or_reject_deposits(): void
    {
        $deposit = Deposit::create([
            'deposit_number' => Deposit::generateDepositNumber(),
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '10000.00',
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'collection_frequency' => 'ONE_TIME',
            'status' => Deposit::STATUS_PENDING_APPROVAL,
        ]);

        // Agent attempts to approve deposit
        $response = $this->actingAs($this->agentUser1)->post(route('admin.deposits.approve', $deposit));
        $response->assertStatus(403);

        // Agent attempts to reject deposit
        $response = $this->actingAs($this->agentUser1)->post(route('admin.deposits.reject', $deposit), [
            'rejection_reason' => 'Unauthorized rejection',
        ]);
        $response->assertStatus(403);
    }

    public function test_agent_can_only_view_their_own_customers_deposits(): void
    {
        // Deposit in Branch 1 under Agent 1
        $depositAgent1 = Deposit::create([
            'deposit_number' => Deposit::generateDepositNumber(),
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '10000.00',
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'collection_frequency' => 'ONE_TIME',
            'status' => Deposit::STATUS_PENDING_APPROVAL,
        ]);

        // Agent 1 can view their own customer's deposit (200)
        $response = $this->actingAs($this->agentUser1)->get(route('admin.deposits.show', $depositAgent1));
        $response->assertStatus(200);

        // Agent 2 (Branch 2) cannot view deposits either (403)
        $response = $this->actingAs($this->agentUser2)->get(route('admin.deposits.show', $depositAgent1));
        $response->assertStatus(403);
    }

    // ─── 5. SEARCH & FILTERING TESTS ─────────────────────────────────────────

    public function test_deposits_search_and_filters(): void
    {
        $uniqueNum = 'DEP_FIND_'.rand(1000, 9999);
        $deposit = Deposit::create([
            'deposit_number' => $uniqueNum,
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '50000.00',
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'collection_frequency' => 'ONE_TIME',
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        // Search by deposit number
        $response = $this->actingAs($this->superAdmin)->get(route('admin.deposits.index', ['search' => $uniqueNum]));
        $response->assertOk();
        $response->assertSee($uniqueNum);

        // Filter by status ACTIVE
        $response = $this->actingAs($this->superAdmin)->get(route('admin.deposits.index', ['status' => 'ACTIVE']));
        $response->assertOk();
        $response->assertSee($uniqueNum);

        // Filter by status REJECTED should not see this active deposit
        $response = $this->actingAs($this->superAdmin)->get(route('admin.deposits.index', ['status' => 'REJECTED']));
        $response->assertOk();
        $response->assertDontSee($uniqueNum);
    }

    public function test_deposit_schemes_index_displays_table_with_actions(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.deposit-schemes.index'));

        $response->assertOk();
        $response->assertSee('Deposit Schemes');
        $response->assertSee('Manage Schemes');
        $response->assertSee(route('admin.deposit-schemes.create'));
        $response->assertSee($this->activeScheme->code);
        $response->assertSee($this->activeScheme->name);
        $response->assertSee(route('admin.deposit-schemes.show', $this->activeScheme));
        $response->assertSee(route('admin.deposit-schemes.edit', $this->activeScheme));
        $response->assertSee(route('admin.deposit-schemes.destroy', $this->activeScheme));
    }

    public function test_deposit_scheme_can_be_deleted_when_no_deposits_exist(): void
    {
        $standaloneScheme = DepositScheme::create([
            'name' => 'Standalone Test Scheme',
            'code' => 'STANDALONE',
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => '1000.00',
            'max_amount' => '100000.00',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => '6.50',
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('admin.deposit-schemes.destroy', $standaloneScheme));

        $response->assertRedirect(route('admin.deposit-schemes.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('deposit_schemes', ['id' => $standaloneScheme->id]);
    }

    public function test_deposit_scheme_cannot_be_deleted_when_deposits_exist(): void
    {
        // $this->activeScheme has deposits attached
        Deposit::create([
            'deposit_number' => Deposit::generateDepositNumber(),
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '10000.00',
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'collection_frequency' => 'ONE_TIME',
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('admin.deposit-schemes.destroy', $this->activeScheme));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('deposit_schemes', [
            'id' => $this->activeScheme->id,
            'deleted_at' => null,
        ]);
    }

    public function test_pending_deposits_pages_are_removed_and_ui_links_updated(): void
    {
        Deposit::create([
            'deposit_number' => Deposit::generateDepositNumber(),
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '15000.00',
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'collection_frequency' => 'ONE_TIME',
            'status' => Deposit::STATUS_PENDING_APPROVAL,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->actingAs($this->superAdmin)->get('/admin/deposits/pending-approval')->assertNotFound();
        $this->actingAs($this->superAdmin)->get('/admin/deposits/pending')->assertNotFound();
        $this->actingAs($this->branchAdmin1)->get('/admin/deposits/pending-approval')->assertNotFound();
        $this->actingAs($this->branchAdmin1)->get('/admin/deposits/pending')->assertNotFound();

        $indexResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposits.index', ['status' => Deposit::STATUS_PENDING_APPROVAL]));

        $indexResponse->assertViewIs('admin.deposits.index');
        $indexResponse->assertViewHas('schemes');
        $indexResponse->assertSee($this->activeScheme->name);
        $indexResponse->assertDontSee('/admin/deposits/pending-approval');
        $indexResponse->assertDontSee('Verification Queue (Pending)');

        $superAdminDashboard = $this->actingAs($this->superAdmin)->get(route('superadmin.dashboard'));
        $superAdminDashboard->assertSee(route('admin.deposits.index', ['status' => Deposit::STATUS_PENDING_APPROVAL]), false);
        $superAdminDashboard->assertDontSee('/admin/deposits/pending-approval');
        $superAdminDashboard->assertDontSee('Verification Queue');

        $branchAdminDashboard = $this->actingAs($this->branchAdmin1)->get(route('branch-admin.dashboard'));
        $branchAdminDashboard->assertDontSee('/admin/deposits/pending-approval');
        $branchAdminDashboard->assertDontSee('Pending Approval');
    }

    public function test_pending_deposits_can_be_filtered_by_status_and_scheme_on_index(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposits.index', [
                'status' => Deposit::STATUS_PENDING_APPROVAL,
                'scheme_id' => $this->activeScheme->id,
            ]));

        $response->assertViewIs('admin.deposits.index');
        $response->assertViewHas('schemes');
    }

    public function test_deposit_can_be_created_with_real_world_banking_fields(): void
    {
        $startDate = now()->toDateString();
        $response = $this->actingAs($this->branchAdmin1)->post(route('admin.deposits.store'), [
            'customer_id' => $this->eligibleCustomer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => '25000.00',
            'start_date' => $startDate,
            'deposit_date' => $startDate,
            'payment_mode' => Deposit::PAYMENT_MODE_BANK_TRANSFER,
            'payment_reference' => 'UTR987654321',
            'nominee_name' => 'Jane Doe',
            'nominee_relation' => 'Spouse',
            'nominee_phone' => '9876543210',
            'nominee_age' => 32,
            'auto_renewal' => Deposit::AUTO_RENEWAL_PRINCIPAL,
            'payout_frequency' => Deposit::PAYOUT_AT_MATURITY,
            'notes' => 'Real-world deposit test with complete KYC and nominee',
        ]);

        $response->assertRedirect();
        $deposit = Deposit::where('customer_id', $this->eligibleCustomer1->id)->latest('id')->first();
        $this->assertNotNull($deposit);

        // Verify Real-world banking fields are stored accurately
        $this->assertEquals(Deposit::PAYMENT_MODE_BANK_TRANSFER, $deposit->payment_mode);
        $this->assertEquals('UTR987654321', $deposit->payment_reference);
        $this->assertEquals('Jane Doe', $deposit->nominee_name);
        $this->assertEquals('Spouse', $deposit->nominee_relation);
        $this->assertEquals('9876543210', $deposit->nominee_phone);
        $this->assertEquals(32, $deposit->nominee_age);
        $this->assertEquals(Deposit::AUTO_RENEWAL_PRINCIPAL, $deposit->auto_renewal);
        $this->assertEquals(Deposit::PAYOUT_AT_MATURITY, $deposit->payout_frequency);
        $this->assertEquals($this->activeScheme->scheme_type, $deposit->deposit_type);

        // Verify maturity calculation (25000 at 7.5% for 12 months = 25000 + 1875 = 26875.00)
        $this->assertNotNull($deposit->maturity_amount);
        $this->assertEquals('26875.00', $deposit->maturity_amount);
        $this->assertEquals(1875.00, $deposit->estimated_interest);

        // Verify Show View renders real-world fields
        $showResponse = $this->actingAs($this->branchAdmin1)->get(route('admin.deposits.show', $deposit));
        $showResponse->assertOk();
        $showResponse->assertSee('UTR987654321');
        $showResponse->assertSee('Jane Doe');
        $showResponse->assertSee('Spouse');
        $showResponse->assertSee('26,875.00');
    }
}
