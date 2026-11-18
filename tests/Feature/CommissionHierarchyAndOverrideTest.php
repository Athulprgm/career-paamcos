<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CareerHistory;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\DepositSchemeRankCommission;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CommissionHierarchyAndOverrideTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected Branch $branch;

    protected DepositScheme $scheme;

    protected CommissionService $commissionService;

    // Ranks 1 to 12
    protected array $ranks = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->commissionService = app(CommissionService::class);

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::factory()->create([
            'email' => 'sa_comm_test_'.uniqid().'@test.com',
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR_COMM_TEST_'.uniqid()],
            ['name' => 'Commission Test Branch', 'is_active' => true]
        );

        // Standard 12 ranks
        for ($i = 1; $i <= 12; $i++) {
            $rank = AgentRank::where('level', $i)->first();
            if (! $rank) {
                $rank = AgentRank::create([
                    'code' => 'L'.$i.'_COMM_'.uniqid(),
                    'name' => 'Rank Level '.$i,
                    'level' => $i,
                    'sort_order' => $i,
                ]);
            }
            $this->ranks[$i] = $rank;
        }

        // Create a 1-year FD Scheme
        $this->scheme = DepositScheme::create([
            'code' => 'SCH_COMM_'.uniqid(),
            'name' => 'FD 1 Year High Yield',
            'scheme_type' => DepositScheme::TYPE_FD,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 9.00,
            'min_amount' => 10000,
            'max_amount' => 1000000,
            'is_active' => true,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'total_commission_percentage' => 10.00,
            'created_by' => $this->superAdmin->id,
        ]);

        // Configure scheme rank commissions for all 12 ranks (Year 1)
        // L1: 4%, L2: 1.5%, L3: 1%, L4: 0.8%, L5: 0.7%, L6: 0.5%, L7: 0.4%, L8: 0.3%, L9: 0.3%, L10: 0.2%, L11: 0.2%, L12: 0.1%
        $rates = [
            1 => 4.00,
            2 => 1.50,
            3 => 1.00,
            4 => 0.80,
            5 => 0.70,
            6 => 0.50,
            7 => 0.40,
            8 => 0.30,
            9 => 0.30,
            10 => 0.20,
            11 => 0.20,
            12 => 0.10,
        ];

        foreach ($rates as $level => $rate) {
            DepositSchemeRankCommission::create([
                'deposit_scheme_id' => $this->scheme->id,
                'rank_id' => $this->ranks[$level]->id,
                'payout_year' => 1,
                'commission_percentage' => $rate,
            ]);
        }
    }

    protected function createAgent(int $level, ?Agent $parentAgent = null, ?Branch $branch = null, bool $active = true): Agent
    {
        $agentUser = User::factory()->create([
            'email' => 'ag_usr_'.uniqid().'@test.com',
            'branch_id' => $branch?->id,
        ]);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $agentUser->roles()->attach($agentRole->id);

        return Agent::create([
            'user_id' => $agentUser->id,
            'branch_id' => $branch?->id,
            'parent_agent_id' => $parentAgent?->id,
            'rank_id' => $this->ranks[$level]->id,
            'agent_id' => 'AG_'.uniqid(),
            'name' => 'Agent L'.$level.'_'.uniqid(),
            'phone' => '98'.rand(10000000, 99999999),
            'email' => 'agent_l'.$level.'_'.uniqid().'@test.com',
            'joining_date' => now()->subMonths(6)->toDateString(),
            'status' => $active ? 'active' : 'inactive',
            'created_by' => $this->superAdmin->id,
        ]);
    }

    protected function createCustomer(Agent $agent, ?Branch $branch = null): Customer
    {
        return Customer::create([
            'customer_number' => 'CUST_'.uniqid(),
            'name' => 'Test Customer '.uniqid(),
            'customer_type' => 'INDIVIDUAL',
            'mobile' => '99'.rand(10000000, 99999999),
            'date_of_birth' => '1985-05-15',
            'branch_id' => $branch?->id ?? $this->branch->id,
            'agent_id' => $agent->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    protected function createDeposit(Customer $customer, Agent $agent, float $amount = 100000.00, ?Lead $lead = null): Deposit
    {
        return Deposit::create([
            'deposit_number' => 'DEP_'.uniqid(),
            'customer_id' => $customer->id,
            'lead_id' => $lead?->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $agent->id,
            'deposit_scheme_id' => $this->scheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => $amount,
            'start_date' => now()->toDateString(),
            'deposit_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_rate' => 9.00,
            'maturity_date' => now()->addMonths(12)->toDateString(),
            'maturity_amount' => $amount * 1.09,
            'payment_mode' => Deposit::PAYMENT_MODE_CASH,
            'status' => Deposit::STATUS_ACTIVE,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => now(),
            'activated_at' => now(),
            'created_by' => $this->superAdmin->id,
        ]);
    }

    /**
     * 1. Same-rank recruitment: No override solely for recruiting an agent at the same rank.
     */
    public function test_1_same_rank_recruiter_earns_no_override(): void
    {
        $recruiterL1 = $this->createAgent(level: 1, branch: $this->branch);
        $directL1 = $this->createAgent(level: 1, parentAgent: $recruiterL1, branch: $this->branch);
        $customer = $this->createCustomer($directL1, $this->branch);
        $deposit = $this->createDeposit($customer, $directL1, 100000.00);

        $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);

        // Direct agent L1 gets 4% = ₹4,000
        $this->assertCount(1, $commissions);
        $directComm = $commissions->first();
        $this->assertEquals($directL1->id, $directComm->agent_id);
        $this->assertEquals(4000.00, (float) $directComm->commission_amount);

        // Recruiter L1 gets NOTHING because they are at the same rank (L1 vs L1)
        $recruiterComm = Commission::where('deposit_id', $deposit->id)->where('agent_id', $recruiterL1->id)->first();
        $this->assertNull($recruiterComm);
    }

    /**
     * 2. Higher-rank recruiter: Override is permitted only when rank eligibility and existing commission rules authorize it.
     */
    public function test_2_higher_rank_recruiter_override_eligibility(): void
    {
        $recruiterL2 = $this->createAgent(level: 2, branch: $this->branch, active: true);
        $directL1 = $this->createAgent(level: 1, parentAgent: $recruiterL2, branch: $this->branch);
        $customer = $this->createCustomer($directL1, $this->branch);
        $deposit = $this->createDeposit($customer, $directL1, 100000.00);

        $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);

        // Both direct L1 (4%) and recruiter L2 (1.5%) receive commission
        $this->assertCount(2, $commissions);

        $directComm = $commissions->where('agent_id', $directL1->id)->first();
        $this->assertNotNull($directComm);
        $this->assertEquals(4000.00, (float) $directComm->commission_amount);

        $overrideComm = $commissions->where('agent_id', $recruiterL2->id)->first();
        $this->assertNotNull($overrideComm);
        $this->assertEquals(1500.00, (float) $overrideComm->commission_amount); // 1.5% of 100,000

        // Ineligible recruiter test: Inactive recruiter earns NO override
        $inactiveRecruiterL2 = $this->createAgent(level: 2, branch: $this->branch, active: false);
        $directL1_2 = $this->createAgent(level: 1, parentAgent: $inactiveRecruiterL2, branch: $this->branch);
        $customer2 = $this->createCustomer($directL1_2, $this->branch);
        $deposit2 = $this->createDeposit($customer2, $directL1_2, 100000.00);

        $commissions2 = $this->commissionService->calculateAndCreateForDeposit($deposit2, $this->superAdmin);
        $this->assertCount(1, $commissions2);
        $this->assertEquals($directL1_2->id, $commissions2->first()->agent_id);
    }

    /**
     * 3. Rank comparisons: Use actual rank ordering (level/sort_order), never assume database IDs represent rank order.
     */
    public function test_3_rank_comparisons_across_levels_respect_level_order_not_database_ids(): void
    {
        // Notice in database, L7 Marketing Manager has id=9, L6 has id=6, etc.
        $l6Agent = $this->createAgent(level: 6, branch: $this->branch);
        $l7Agent = $this->createAgent(level: 7, parentAgent: $l6Agent, branch: $this->branch); // Child is L7 (higher level)
        $customer = $this->createCustomer($l7Agent, $this->branch);
        $deposit = $this->createDeposit($customer, $l7Agent, 100000.00);

        $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);

        // Child L7 earns direct commission (0.40% = ₹400)
        $this->assertCount(1, $commissions);
        $this->assertEquals($l7Agent->id, $commissions->first()->agent_id);
        $this->assertEquals(400.00, (float) $commissions->first()->commission_amount);

        // Recruiter L6 has lower rank than direct agent L7 (even if recruiter's DB id < child's DB id).
        // Recruiter must NOT earn override!
        $l6Comm = Commission::where('deposit_id', $deposit->id)->where('agent_id', $l6Agent->id)->first();
        $this->assertNull($l6Comm);
    }

    /**
     * 4. Direct commission: Award the eligible lead owner according to existing attribution rules.
     */
    public function test_4_direct_commission_awarded_to_eligible_lead_owner(): void
    {
        $leadAgent = $this->createAgent(level: 1, branch: $this->branch);
        $submittingAgent = $this->createAgent(level: 2, branch: $this->branch);

        $lead = Lead::create([
            'branch_id' => $this->branch->id,
            'agent_id' => $leadAgent->id,
            'name' => 'Owner Prospect',
            'mobile' => '9944332211',
            'status' => Lead::STATUS_INTERESTED,
            'expected_amount' => 100000,
            'interested_scheme_id' => $this->scheme->id,
            'created_by' => $this->superAdmin->id,
        ]);

        $customer = $this->createCustomer($leadAgent, $this->branch);

        // Submitting deposit with submittingAgent, but pointing to the lead owned by leadAgent
        $deposit = $this->createDeposit($customer, $submittingAgent, 100000.00, $lead);

        $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);

        // Direct commission must be awarded to the lead owner (leadAgent)
        $directComm = $commissions->where('agent_id', $leadAgent->id)->first();
        $this->assertNotNull($directComm);
        $this->assertEquals(4000.00, (float) $directComm->commission_amount);
    }

    /**
     * 5. Promotions: Apply existing effective-date rules. Never retroactively award commissions simply because of a later promotion.
     */
    public function test_5_promotion_effective_date_preserves_historical_commissions(): void
    {
        $agent = $this->createAgent(level: 1, branch: $this->branch);
        $customer = $this->createCustomer($agent, $this->branch);

        // Deposit created in the past when agent was L1
        $deposit = Deposit::create([
            'deposit_number' => 'HIST_DEP_'.uniqid(),
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $agent->id,
            'deposit_scheme_id' => $this->scheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 100000.00,
            'start_date' => '2026-01-01',
            'deposit_date' => '2026-01-01',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_rate' => 9.00,
            'maturity_date' => '2027-01-01',
            'maturity_amount' => 109000.00,
            'payment_mode' => Deposit::PAYMENT_MODE_CASH,
            'status' => Deposit::STATUS_ACTIVE,
            'approved_by' => $this->superAdmin->id,
            'approved_at' => '2026-01-01 10:00:00',
            'activated_at' => '2026-01-01 10:00:00',
            'created_by' => $this->superAdmin->id,
        ]);

        // Promote agent to L3 on 2026-06-01
        $agent->update(['rank_id' => $this->ranks[3]->id]);
        CareerHistory::create([
            'agent_id' => $agent->id,
            'previous_level_id' => $this->ranks[1]->id,
            'new_level_id' => $this->ranks[3]->id,
            'promotion_date' => '2026-06-01',
            'action' => CareerHistory::ACTION_PROMOTION,
            'approved_by' => $this->superAdmin->id,
        ]);

        // When calculating for the 2026-01-01 deposit, effective rank MUST be L1 (not L3)
        $effectiveRank = $this->commissionService->getEffectiveRankForAgent($agent, $deposit->deposit_date);
        $this->assertEquals($this->ranks[1]->id, $effectiveRank->id);

        $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);
        $this->assertEquals(4000.00, (float) $commissions->first()->commission_amount); // L1 rate of 4%, not L3 rate of 1%
    }

    /**
     * 6. Dynamic configuration: Rates and calculation bases come from existing configuration, without hardcoded values.
     */
    public function test_6_dynamic_configuration_changes_apply_to_new_deposits(): void
    {
        $agent = $this->createAgent(level: 1, branch: $this->branch);
        $customer = $this->createCustomer($agent, $this->branch);
        $deposit1 = $this->createDeposit($customer, $agent, 100000.00);

        $commissions1 = $this->commissionService->calculateAndCreateForDeposit($deposit1, $this->superAdmin);
        $this->assertEquals(4000.00, (float) $commissions1->first()->commission_amount); // 4%

        // Dynamically update the configuration in deposit_scheme_rank_commissions to 5.5%
        DepositSchemeRankCommission::where('deposit_scheme_id', $this->scheme->id)
            ->where('rank_id', $this->ranks[1]->id)
            ->where('payout_year', 1)
            ->update(['commission_percentage' => 5.50]);

        $deposit2 = $this->createDeposit($customer, $agent, 100000.00);
        $commissions2 = $this->commissionService->calculateAndCreateForDeposit($deposit2, $this->superAdmin);
        $this->assertEquals(5500.00, (float) $commissions2->first()->commission_amount); // 5.5%

        // Old deposit remains untouched
        $commissions1->first()->refresh();
        $this->assertEquals(4000.00, (float) $commissions1->first()->commission_amount);
    }

    /**
     * 7. Multi-level recruitment policy: L1 -> L1 -> L2 -> L3 -> L3 -> L4 chain.
     */
    public function test_7_multi_level_recruitment_hierarchy_differential_distribution(): void
    {
        // Hierarchy from top to bottom:
        // L4 -> L3_A -> L3_B -> L2 -> L1_Recruiter -> L1_Direct
        $l4 = $this->createAgent(level: 4, branch: $this->branch);
        $l3_a = $this->createAgent(level: 3, parentAgent: $l4, branch: $this->branch);
        $l3_b = $this->createAgent(level: 3, parentAgent: $l3_a, branch: $this->branch);
        $l2 = $this->createAgent(level: 2, parentAgent: $l3_b, branch: $this->branch);
        $l1_recruiter = $this->createAgent(level: 1, parentAgent: $l2, branch: $this->branch);
        $l1_direct = $this->createAgent(level: 1, parentAgent: $l1_recruiter, branch: $this->branch);

        $customer = $this->createCustomer($l1_direct, $this->branch);
        $deposit = $this->createDeposit($customer, $l1_direct, 100000.00);

        $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);

        // Expected payouts:
        // 1. Direct L1: 4.00% = ₹4,000
        // 2. L1_Recruiter: 0% (same rank as L1 direct)
        // 3. L2: 1.50% = ₹1,500 (L2 > L1)
        // 4. L3_B: 1.00% = ₹1,000 (L3 > L2)
        // 5. L3_A: 0% (same rank as L3_B)
        // 6. L4: 0.80% = ₹800 (L4 > L3)
        $this->assertCount(4, $commissions);

        $commDirect = $commissions->where('agent_id', $l1_direct->id)->first();
        $this->assertNotNull($commDirect);
        $this->assertEquals(4000.00, (float) $commDirect->commission_amount);

        $this->assertNull($commissions->where('agent_id', $l1_recruiter->id)->first());

        $commL2 = $commissions->where('agent_id', $l2->id)->first();
        $this->assertNotNull($commL2);
        $this->assertEquals(1500.00, (float) $commL2->commission_amount);

        $commL3B = $commissions->where('agent_id', $l3_b->id)->first();
        $this->assertNotNull($commL3B);
        $this->assertEquals(1000.00, (float) $commL3B->commission_amount);

        $this->assertNull($commissions->where('agent_id', $l3_a->id)->first());

        $commL4 = $commissions->where('agent_id', $l4->id)->first();
        $this->assertNotNull($commL4);
        $this->assertEquals(800.00, (float) $commL4->commission_amount);
    }

    /**
     * 8. Missing/inactive commission rules: Gracefully handled with 0 commission created.
     */
    public function test_8_missing_or_inactive_commission_rules_handled_gracefully(): void
    {
        $bareScheme = DepositScheme::create([
            'code' => 'SCH_NO_COMM_'.uniqid(),
            'name' => 'Bare Scheme Without Commission Matrix',
            'scheme_type' => DepositScheme::TYPE_FD,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.00,
            'min_amount' => 5000,
            'max_amount' => 100000,
            'is_active' => true,
            'created_by' => $this->superAdmin->id,
        ]);

        $agent = $this->createAgent(level: 1, branch: $this->branch);
        $customer = $this->createCustomer($agent, $this->branch);

        $deposit = Deposit::create([
            'deposit_number' => 'DEP_NO_RULE_'.uniqid(),
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $agent->id,
            'deposit_scheme_id' => $bareScheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 50000.00,
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.00,
            'maturity_date' => now()->addMonths(12)->toDateString(),
            'maturity_amount' => 53500.00,
            'payment_mode' => Deposit::PAYMENT_MODE_CASH,
            'status' => Deposit::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);
        $this->assertCount(0, $commissions);
    }

    /**
     * 9. Independent agents: Do not reject agents solely because their branch is null.
     */
    public function test_9_independent_agents_with_null_branch_processed_safely(): void
    {
        // Agent with null branch_id (independent/free-field agent)
        $freeAgent = $this->createAgent(level: 1, branch: null);
        $this->assertNull($freeAgent->branch_id);

        $customer = $this->createCustomer($freeAgent, $this->branch);
        $deposit = $this->createDeposit($customer, $freeAgent, 100000.00);

        $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);

        $this->assertCount(1, $commissions);
        $comm = $commissions->first();
        $this->assertEquals($freeAgent->id, $comm->agent_id);
        $this->assertEquals(4000.00, (float) $comm->commission_amount);
        // Commission branch_id must safely fall back to deposit branch
        $this->assertEquals($deposit->branch_id, $comm->branch_id);
    }

    /**
     * 10. Duplicate and concurrent processing: Repeated processing must not create duplicate commission entitlements.
     */
    public function test_10_duplicate_processing_prevention(): void
    {
        $agent = $this->createAgent(level: 1, branch: $this->branch);
        $customer = $this->createCustomer($agent, $this->branch);
        $deposit = $this->createDeposit($customer, $agent, 100000.00);

        // First execution creates vouchers
        $firstRun = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);
        $this->assertCount(1, $firstRun);
        $this->assertEquals(1, Commission::where('deposit_id', $deposit->id)->count());

        // Second execution returns existing vouchers without duplicating
        $secondRun = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);
        $this->assertCount(1, $secondRun);
        $this->assertEquals(1, Commission::where('deposit_id', $deposit->id)->count());
    }

    /**
     * 11. Deposit cancellations and reversals: Rejecting deposit cancels active commissions with audit trail.
     */
    public function test_11_deposit_cancellation_and_reversal(): void
    {
        $agent = $this->createAgent(level: 1, branch: $this->branch);
        $customer = $this->createCustomer($agent, $this->branch);
        $deposit = $this->createDeposit($customer, $agent, 100000.00);

        $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);
        $commission = $commissions->first();
        $this->assertEquals(Commission::STATUS_PENDING, $commission->status);

        // Cancel/reverse deposit commissions
        $cancelled = $this->commissionService->cancelOrReverseForDeposit($deposit, 'Deposit application rejected by auditor', $this->superAdmin);
        $this->assertCount(1, $cancelled);

        $commission->refresh();
        $this->assertEquals(Commission::STATUS_REJECTED, $commission->status);

        // Verify audit log
        $auditLog = AuditLog::where('auditable_type', Commission::class)
            ->where('auditable_id', $commission->id)
            ->where('event', 'commission_reversed')
            ->first();
        $this->assertNotNull($auditLog);
    }

    /**
     * 12. Full end-to-end integration: Lead -> Deposit -> Commission -> Approval -> Payment.
     */
    public function test_12_full_workflow_lead_to_commission_approval(): void
    {
        $recruiter = $this->createAgent(level: 2, branch: $this->branch);
        $agent = $this->createAgent(level: 1, parentAgent: $recruiter, branch: $this->branch);

        $lead = Lead::create([
            'branch_id' => $this->branch->id,
            'agent_id' => $agent->id,
            'name' => 'E2E Customer Prospect',
            'mobile' => '9812345678',
            'status' => Lead::STATUS_INTERESTED,
            'expected_amount' => 100000,
            'interested_scheme_id' => $this->scheme->id,
            'created_by' => $this->superAdmin->id,
        ]);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'lead_id' => $lead->id,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 100000,
            'start_date' => now()->toDateString(),
            'status_action' => 'active',
        ]);
        $response->assertRedirect();

        $deposit = Deposit::where('lead_id', $lead->id)->first();
        $this->assertNotNull($deposit);

        $commissions = Commission::where('deposit_id', $deposit->id)->get();
        // Direct L1 + Recruiter L2
        $this->assertCount(2, $commissions);

        foreach ($commissions as $comm) {
            $this->assertEquals(Commission::STATUS_PENDING, $comm->status);
            $approveResp = $this->actingAs($this->superAdmin)->post(route('admin.commissions.approve', $comm));
            $approveResp->assertRedirect();
            $comm->refresh();
            $this->assertEquals(Commission::STATUS_APPROVED, $comm->status);
            $this->assertEquals($this->superAdmin->id, $comm->approved_by);
        }
    }
}
