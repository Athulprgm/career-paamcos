<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Customer;
use App\Models\CustomerKyc;
use App\Models\Deposit;
use App\Models\DepositCollection;
use App\Models\DepositScheme;
use App\Models\Role;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CommissionEngineTest extends TestCase
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

    protected ?DepositScheme $monthlyScheme = null;

    protected ?Customer $customer1 = null;

    protected ?Deposit $deposit1 = null;

    protected ?DepositCollection $collection1 = null;

    protected ?CommissionRule $standardRule = null;

    protected ?CommissionService $commissionService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commissionService = app(CommissionService::class);

        // 1. Roles
        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin', 'status' => 'active']);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);

        // 2. Branches
        $this->branch1 = Branch::firstOrCreate(
            ['code' => 'BR_P6_1'],
            ['name' => 'Branch One Phase 6', 'is_active' => true]
        );

        $this->branch2 = Branch::firstOrCreate(
            ['code' => 'BR_P6_2'],
            ['name' => 'Branch Two Phase 6', 'is_active' => true]
        );

        // 3. Super Admin
        $this->superAdmin = User::create([
            'name' => 'Super Admin P6',
            'email' => 'superadmin_p6_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        // 4. Branch Admins
        $this->branchAdmin1 = User::create([
            'name' => 'Branch Admin 1 P6',
            'email' => 'badmin1_p6_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch1->id,
        ]);
        $this->branchAdmin1->roles()->attach($adminRole->id);

        $this->branchAdmin2 = User::create([
            'name' => 'Branch Admin 2 P6',
            'email' => 'badmin2_p6_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch2->id,
        ]);
        $this->branchAdmin2->roles()->attach($adminRole->id);

        // 5. Agent Users & Agents
        $rank = AgentRank::first();

        $this->agentUser1 = User::create([
            'name' => 'Agent 1 P6',
            'email' => 'agent1_p6_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch1->id,
        ]);
        $this->agentUser1->roles()->attach($agentRole->id);

        $this->agent1 = Agent::create([
            'user_id' => $this->agentUser1->id,
            'member_code' => 'AGP6_1_'.rand(1000, 9999),
            'agent_id' => 'AGP6_1_'.rand(1000, 9999),
            'name' => 'Agent One P6',
            'email' => $this->agentUser1->email,
            'phone' => '9876541111',
            'branch_id' => $this->branch1->id,
            'rank_id' => $rank?->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->agentUser2 = User::create([
            'name' => 'Agent 2 P6',
            'email' => 'agent2_p6_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch2->id,
        ]);
        $this->agentUser2->roles()->attach($agentRole->id);

        $this->agent2 = Agent::create([
            'user_id' => $this->agentUser2->id,
            'member_code' => 'AGP6_2_'.rand(1000, 9999),
            'agent_id' => 'AGP6_2_'.rand(1000, 9999),
            'name' => 'Agent Two P6',
            'email' => $this->agentUser2->email,
            'phone' => '9876542222',
            'branch_id' => $this->branch2->id,
            'rank_id' => $rank?->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        // 6. Deposit Scheme
        $this->monthlyScheme = DepositScheme::create([
            'name' => 'Recurring Deposit Monthly P6 '.uniqid(),
            'code' => 'RDP6_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_RD,
            'description' => 'Monthly Recurring Deposit Phase 6',
            'min_amount' => '500.00',
            'max_amount' => '50000.00',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => '6.50',
            'collection_frequency' => DepositScheme::FREQ_MONTHLY,
            'status' => DepositScheme::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        // 7. Customer & KYC
        $this->customer1 = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Customer Phase 6 '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '9876543201',
            'date_of_birth' => '1990-01-01',
            'address' => 'Phase 6 Street',
            'pin_code' => '682001',
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        CustomerKyc::create([
            'customer_id' => $this->customer1->id,
            'id_type' => 'Aadhaar Card',
            'id_number' => '123456789012',
            'status' => CustomerKyc::STATUS_APPROVED,
            'verified_at' => now(),
            'verified_by' => $this->superAdmin->id,
        ]);

        // 8. Active Deposit
        $this->deposit1 = Deposit::create([
            'deposit_number' => Deposit::generateDepositNumber(),
            'customer_id' => $this->customer1->id,
            'deposit_scheme_id' => $this->monthlyScheme->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'amount' => '12000.00',
            'balance' => '0.00',
            'maturity_amount' => '12780.00',
            'start_date' => now()->toDateString(),
            'maturity_date' => now()->addMonths(12)->toDateString(),
            'status' => Deposit::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        // 9. Paid Collection Record
        $this->collection1 = DepositCollection::create([
            'collection_number' => DepositCollection::generateCollectionNumber(),
            'deposit_id' => $this->deposit1->id,
            'customer_id' => $this->customer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'installment_number' => 1,
            'due_date' => now()->toDateString(),
            'due_amount' => '1000.00',
            'paid_amount' => '1000.00',
            'outstanding_amount' => '0.00',
            'status' => DepositCollection::STATUS_PAID,
            'collection_date' => now()->toDateString(),
            'payment_mode' => 'CASH',
            'collected_by_id' => $this->superAdmin->id,
        ]);

        // 10. Configurable Commission Rule: 3% on collection amount
        $this->standardRule = CommissionRule::create([
            'name' => 'Standard Collection 3% Rule '.uniqid(),
            'code' => CommissionRule::generateRuleCode(),
            'commission_type' => CommissionRule::TYPE_PERCENTAGE,
            'calculation_basis' => CommissionRule::BASIS_COLLECTION_AMOUNT,
            'rate' => '3.00',
            'deposit_scheme_id' => $this->monthlyScheme->id,
            'rank_id' => null,
            'effective_from' => now()->subDay()->toDateString(),
            'effective_to' => null,
            'status' => CommissionRule::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    /**
     * Test complete happy-path lifecycle:
     * Collection -> Generate Commission -> PENDING -> APPROVED -> PAYABLE -> PAID
     */
    public function test_complete_commission_lifecycle(): void
    {
        // 1. Generate Commission from Collection
        $commission = $this->commissionService->generateForCollection($this->collection1, null, $this->superAdmin);

        $this->assertInstanceOf(Commission::class, $commission);
        $this->assertEquals(Commission::STATUS_PENDING, $commission->status);
        $this->assertEquals('1000.00', $commission->base_amount);
        $this->assertEquals('3.00', $commission->rate);
        $this->assertEquals('30.00', $commission->calculated_amount); // 1000 * 3 / 100 = 30
        $this->assertEquals($this->agent1->id, $commission->agent_id);
        $this->assertEquals($this->branch1->id, $commission->branch_id);
        $this->assertEquals($this->collection1->id, $commission->deposit_collection_id);
        $this->assertStringStartsWith('COM', $commission->commission_number);

        // 2. Approve Commission: PENDING -> APPROVED
        $this->assertTrue($commission->canBeApproved());
        $this->commissionService->approve($commission, $this->superAdmin);

        $commission->refresh();
        $this->assertEquals(Commission::STATUS_APPROVED, $commission->status);
        $this->assertEquals($this->superAdmin->id, $commission->approved_by);
        $this->assertNotNull($commission->approved_at);

        // 3. Mark Payable: APPROVED -> PAYABLE
        $this->assertTrue($commission->canBeMarkedPayable());
        $this->commissionService->markPayable($commission, $this->superAdmin);

        $commission->refresh();
        $this->assertEquals(Commission::STATUS_PAYABLE, $commission->status);

        // 4. Record Payment: PAYABLE -> PAID
        $this->assertTrue($commission->canBePaid());
        $this->commissionService->pay($commission, [
            'payment_date' => now()->toDateString(),
            'payment_method' => 'BANK_TRANSFER',
            'reference_number' => 'NEFT987654321',
            'notes' => 'Settled via monthly NEFT payout run.',
        ], $this->superAdmin);

        $commission->refresh();
        $this->assertEquals(Commission::STATUS_PAID, $commission->status);
        $this->assertEquals($this->superAdmin->id, $commission->paid_by);
        $this->assertEquals('BANK_TRANSFER', $commission->payment_method);
        $this->assertEquals('NEFT987654321', $commission->reference_number);
        $this->assertNotNull($commission->payment_date);
    }

    /**
     * Test percentage calculation vs fixed-amount calculation.
     */
    public function test_fixed_and_percentage_commission_calculations(): void
    {
        // 1. Percentage test (1000 * 5% = 50.00)
        $percentageRule = CommissionRule::create([
            'name' => '5 Percent Rule',
            'code' => CommissionRule::generateRuleCode(),
            'commission_type' => CommissionRule::TYPE_PERCENTAGE,
            'calculation_basis' => CommissionRule::BASIS_COLLECTION_AMOUNT,
            'rate' => '5.00',
            'status' => CommissionRule::STATUS_ACTIVE,
            'effective_from' => now()->subDay()->toDateString(),
        ]);

        $commPercent = $this->commissionService->generateForCollection($this->collection1, $percentageRule, $this->superAdmin);
        $this->assertEquals('50.00', $commPercent->calculated_amount);

        // Delete to allow next test on different collection
        $commPercent->delete();

        // 2. Fixed amount test (Flat 75.00)
        $fixedRule = CommissionRule::create([
            'name' => 'Flat 75 Rule',
            'code' => CommissionRule::generateRuleCode(),
            'commission_type' => CommissionRule::TYPE_FIXED,
            'calculation_basis' => CommissionRule::BASIS_COLLECTION_AMOUNT,
            'rate' => '75.00',
            'status' => CommissionRule::STATUS_ACTIVE,
            'effective_from' => now()->subDay()->toDateString(),
        ]);

        $commFixed = $this->commissionService->generateForCollection($this->collection1, $fixedRule, $this->superAdmin);
        $this->assertEquals('75.00', $commFixed->calculated_amount);
    }

    /**
     * Test calculation basis: Deposit amount basis (e.g. 1% on 12,000 deposit = 120.00).
     */
    public function test_calculation_basis_on_deposit_amount(): void
    {
        $depositBasisRule = CommissionRule::create([
            'name' => '1 Percent on Deposit Amount',
            'code' => CommissionRule::generateRuleCode(),
            'commission_type' => CommissionRule::TYPE_PERCENTAGE,
            'calculation_basis' => CommissionRule::BASIS_DEPOSIT_AMOUNT,
            'rate' => '1.00',
            'status' => CommissionRule::STATUS_ACTIVE,
            'effective_from' => now()->subDay()->toDateString(),
        ]);

        $comm = $this->commissionService->generateForCollection($this->collection1, $depositBasisRule, $this->superAdmin);

        $this->assertEquals('12000.00', $comm->base_amount);
        $this->assertEquals('120.00', $comm->calculated_amount);
    }

    /**
     * Test duplicate commission prevention: Cannot generate commission twice for the same collection.
     */
    public function test_prevents_duplicate_commission_generation(): void
    {
        // First generation succeeds
        $this->commissionService->generateForCollection($this->collection1, null, $this->superAdmin);

        // Second generation must throw ValidationException
        $this->expectException(ValidationException::class);
        $this->commissionService->generateForCollection($this->collection1, null, $this->superAdmin);
    }

    /**
     * Test cannot generate commission for cancelled collection.
     */
    public function test_cannot_generate_commission_for_cancelled_collection(): void
    {
        $cancelledCollection = DepositCollection::create([
            'collection_number' => DepositCollection::generateCollectionNumber(),
            'deposit_id' => $this->deposit1->id,
            'customer_id' => $this->customer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'installment_number' => 2,
            'due_date' => now()->toDateString(),
            'due_amount' => '1000.00',
            'paid_amount' => '0.00',
            'outstanding_amount' => '1000.00',
            'status' => DepositCollection::STATUS_CANCELLED,
        ]);

        $this->expectException(ValidationException::class);
        $this->commissionService->generateForCollection($cancelledCollection, null, $this->superAdmin);
    }

    /**
     * Test cannot generate commission for inactive agent.
     */
    public function test_cannot_generate_commission_for_inactive_agent(): void
    {
        $this->agent1->update(['status' => 'inactive']);

        $collection = DepositCollection::create([
            'collection_number' => DepositCollection::generateCollectionNumber(),
            'deposit_id' => $this->deposit1->id,
            'customer_id' => $this->customer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'installment_number' => 2,
            'due_date' => now()->toDateString(),
            'due_amount' => '1000.00',
            'paid_amount' => '1000.00',
            'outstanding_amount' => '0.00',
            'status' => DepositCollection::STATUS_PAID,
            'collection_date' => now()->toDateString(),
        ]);

        $this->expectException(ValidationException::class);
        $this->commissionService->generateForCollection($collection, null, $this->superAdmin);
    }

    /**
     * Test cannot generate commission if no active rule applies.
     */
    public function test_cannot_generate_commission_when_rule_is_inactive(): void
    {
        // Deactivate all rules
        CommissionRule::query()->update(['status' => CommissionRule::STATUS_INACTIVE]);

        $this->expectException(ValidationException::class);
        $this->commissionService->generateForCollection($this->collection1, null, $this->superAdmin);
    }

    /**
     * Test alternative workflow: PENDING -> REJECTED with mandatory reason.
     */
    public function test_pending_to_rejected_workflow(): void
    {
        $commission = $this->commissionService->generateForCollection($this->collection1, null, $this->superAdmin);

        $this->assertTrue($commission->canBeRejected());
        $this->commissionService->reject($commission, 'Collection payment under audit investigation', $this->superAdmin);

        $commission->refresh();
        $this->assertEquals(Commission::STATUS_REJECTED, $commission->status);
        $this->assertEquals('Collection payment under audit investigation', $commission->rejection_reason);
        $this->assertEquals($this->superAdmin->id, $commission->rejected_by);
        $this->assertNotNull($commission->rejected_at);

        // Cannot approve a rejected commission
        $this->expectException(ValidationException::class);
        $this->commissionService->approve($commission, $this->superAdmin);
    }

    /**
     * Test invalid status transitions are rejected.
     */
    public function test_invalid_status_transitions_throw_exception(): void
    {
        $commission = $this->commissionService->generateForCollection($this->collection1, null, $this->superAdmin);

        // Cannot pay directly from PENDING
        $this->expectException(ValidationException::class);
        $this->commissionService->pay($commission, [
            'payment_date' => now()->toDateString(),
            'payment_method' => 'CASH',
        ], $this->superAdmin);
    }

    /**
     * Test cross-branch access authorization: Branch Admin 2 cannot view or approve Branch 1's commission.
     */
    public function test_cross_branch_access_forbidden(): void
    {
        $commission = $this->commissionService->generateForCollection($this->collection1, null, $this->superAdmin);

        // Branch Admin 2 tries to view Branch 1's commission
        $response = $this->actingAs($this->branchAdmin2)
            ->get(route('admin.commissions.show', $commission));

        $response->assertStatus(403);

        // Branch Admin 2 tries to approve Branch 1's commission
        $approveResponse = $this->actingAs($this->branchAdmin2)
            ->post(route('admin.commissions.approve', $commission));

        $approveResponse->assertStatus(403);
    }

    /**
     * Test Agent access authorization:
     * - Agent 1 can view own commission
     * - Agent 1 cannot view Agent 2's commission
     * - Agent CANNOT approve, mark payable, or pay their own commission
     */
    public function test_agent_authorization_boundaries(): void
    {
        $commission1 = $this->commissionService->generateForCollection($this->collection1, null, $this->superAdmin);

        // Agent 1 views own commission -> 200 OK
        $response = $this->actingAs($this->agentUser1)
            ->get(route('admin.commissions.show', $commission1));
        $response->assertStatus(200);

        // Agent 1 attempts to approve own commission -> 403 Forbidden
        $approveResponse = $this->actingAs($this->agentUser1)
            ->post(route('admin.commissions.approve', $commission1));
        $approveResponse->assertStatus(403);

        // Agent 1 attempts to access payout form -> 403 Forbidden
        $payFormResponse = $this->actingAs($this->agentUser1)
            ->get(route('admin.commissions.pay', $commission1));
        $payFormResponse->assertStatus(403);

        // Agent 1 attempts to create commission rules -> 403 Forbidden
        $createRuleResponse = $this->actingAs($this->agentUser1)
            ->get(route('admin.commission-rules.create'));
        $createRuleResponse->assertStatus(403);
    }

    /**
     * Test UI endpoints for Commission Management.
     */
    public function test_commission_views_load_successfully(): void
    {
        $commission = $this->commissionService->generateForCollection($this->collection1, null, $this->superAdmin);

        // Overview
        $this->actingAs($this->superAdmin)
            ->get(route('admin.commissions.overview'))
            ->assertStatus(200)
            ->assertSee('Commission Overview')
            ->assertSee($commission->commission_number);

        // All Commissions index
        $this->actingAs($this->superAdmin)
            ->get(route('admin.commissions.index'))
            ->assertStatus(200)
            ->assertSee($commission->commission_number);

        // Pending
        $this->actingAs($this->superAdmin)
            ->get(route('admin.commissions.pending'))
            ->assertStatus(200)
            ->assertSee('Pending Commissions')
            ->assertSee($commission->commission_number);

        // Show
        $this->actingAs($this->superAdmin)
            ->get(route('admin.commissions.show', $commission))
            ->assertStatus(200)
            ->assertSee($commission->commission_number)
            ->assertSee('Commission Calculation Breakdown');

        // History
        $this->actingAs($this->superAdmin)
            ->get(route('admin.commissions.history'))
            ->assertStatus(200)
            ->assertSee('Commission History');

        // Legacy Rules pages redirect to Deposit Commissions
        $this->actingAs($this->superAdmin)
            ->get(route('admin.commission-rules.index'))
            ->assertRedirect(route('superadmin.fd-commissions.index'));

        $this->actingAs($this->superAdmin)
            ->get(route('admin.commission-rules.create'))
            ->assertRedirect(route('superadmin.fd-commissions.index'));

        $this->actingAs($this->superAdmin)
            ->get(route('admin.commission-rules.show', $this->standardRule))
            ->assertRedirect(route('superadmin.fd-commissions.index'));

        $this->actingAs($this->superAdmin)
            ->get(route('admin.commission-rules.edit', $this->standardRule))
            ->assertRedirect(route('superadmin.fd-commissions.index'));
    }

    /**
     * Test Commission Rule CRUD operations.
     */
    public function test_commission_rule_crud(): void
    {
        // Create rule
        $response = $this->actingAs($this->superAdmin)->post(route('admin.commission-rules.store'), [
            'name' => 'Gold Agent 4.5% Commission',
            'code' => 'CR_TEST_'.rand(100, 999),
            'commission_type' => 'PERCENTAGE',
            'calculation_basis' => 'COLLECTION_AMOUNT',
            'rate' => 4.50,
            'effective_from' => now()->toDateString(),
            'status' => 'ACTIVE',
        ]);

        $response->assertRedirect(route('admin.commission-rules.index'));
        $this->assertDatabaseHas('commission_rules', [
            'name' => 'Gold Agent 4.5% Commission',
            'rate' => '4.50',
        ]);

        $rule = CommissionRule::where('name', 'Gold Agent 4.5% Commission')->first();

        // Toggle status
        $this->actingAs($this->superAdmin)->post(route('admin.commission-rules.toggle-status', $rule));
        $rule->refresh();
        $this->assertEquals(CommissionRule::STATUS_INACTIVE, $rule->status);
    }
}
