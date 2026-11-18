<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRankTarget;
use App\Models\AgentTargetAssignment;
use App\Models\Branch;
use App\Models\CareerLevel;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\FinancialYear;
use App\Models\PromotionRule;
use App\Models\Role;
use App\Models\User;
use App\Services\AgentTargetCalculationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AgentFinancialYearTargetManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected AgentTargetCalculationService $targetService;

    protected FinancialYear $fy2026;

    protected CareerLevel $rank1;

    protected CareerLevel $rank2;

    protected CareerLevel $rank3;

    protected Branch $branch1;

    protected Branch $branch2;

    protected User $superAdmin;

    protected User $branchAdmin1;

    protected User $agentUser1;

    protected Agent $agent1;

    protected DepositScheme $fdScheme;

    protected DepositScheme $rdScheme;

    protected DepositScheme $ddScheme;

    protected function createUserWithRole(string $roleSlug, ?int $branchId = null): User
    {
        $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => ucfirst($roleSlug), 'status' => 'active']);
        $user = User::create([
            'name' => 'Test User '.uniqid(),
            'email' => 'test_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $branchId,
        ]);
        $user->roles()->attach($role->id);

        return $user;
    }

    protected function createCustomer(?int $branchId = null): Customer
    {
        return Customer::create([
            'branch_id' => $branchId ?? $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'customer_number' => 'CUST_'.uniqid(),
            'name' => 'Test Customer '.uniqid(),
            'customer_type' => 'INDIVIDUAL',
            'mobile' => '98'.rand(10000000, 99999999),
            'status' => Customer::STATUS_ACTIVE,
        ]);
    }

    protected function createAgent(array $attributes = []): Agent
    {
        $branchId = $attributes['branch_id'] ?? $this->branch1->id;
        $userId = $attributes['user_id'] ?? $this->createUserWithRole('agent', $branchId)->id;

        return Agent::create(array_merge([
            'user_id' => $userId,
            'branch_id' => $branchId,
            'rank_id' => $this->rank1->id,
            'name' => 'Agent '.uniqid(),
            'email' => 'agent_'.uniqid().'@example.com',
            'phone' => '99'.rand(10000000, 99999999),
            'agent_id' => 'AG_'.uniqid(),
            'member_code' => 'MEM_'.uniqid(),
            'status' => Agent::STATUS_ACTIVE,
            'joining_date' => '2026-05-01',
        ], $attributes));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->targetService = app(AgentTargetCalculationService::class);

        // Ensure roles
        Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin', 'status' => 'active']);
        Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);

        // Branches
        $this->branch1 = Branch::firstOrCreate(
            ['code' => 'BR_TEST_TGT_A'],
            ['name' => 'Target Test Branch A', 'is_active' => true]
        );
        $this->branch2 = Branch::firstOrCreate(
            ['code' => 'BR_TEST_TGT_B'],
            ['name' => 'Target Test Branch B', 'is_active' => true]
        );

        // Financial Year 2026-27 (1 April 2026 to 31 March 2027)
        $this->fy2026 = FinancialYear::firstOrCreate(
            ['code' => 'FY2026-27'],
            [
                'name' => 'Financial Year 2026-27',
                'start_year' => 2026,
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_current' => true,
                'status' => 'active',
            ]
        );

        // Career Ranks (Levels 1, 2, 3)
        $this->rank1 = CareerLevel::where('level', 1)->first() ?? CareerLevel::create([
            'level' => 1,
            'name' => 'Facilitator',
            'code' => 'FAC',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->rank2 = CareerLevel::where('level', 2)->first() ?? CareerLevel::create([
            'level' => 2,
            'name' => 'Advisor',
            'code' => 'ADV',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $this->rank3 = CareerLevel::where('level', 3)->first() ?? CareerLevel::create([
            'level' => 3,
            'name' => 'Senior Advisor',
            'code' => 'SAD',
            'sort_order' => 3,
            'is_active' => true,
        ]);

        // Users
        $this->superAdmin = $this->createUserWithRole('super_admin');
        $this->branchAdmin1 = $this->createUserWithRole('admin', $this->branch1->id);
        $this->agentUser1 = $this->createUserWithRole('agent', $this->branch1->id);

        $this->agent1 = Agent::create([
            'user_id' => $this->agentUser1->id,
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank1->id,
            'name' => 'Kavitha Ramesh',
            'email' => 'kavitha.test@pristineagro.org',
            'phone' => '9845012345',
            'agent_id' => 'AG_TGT_001',
            'member_code' => 'MEM_TGT_001',
            'status' => Agent::STATUS_ACTIVE,
            'joining_date' => '2026-04-05',
        ]);

        // Deposit Schemes
        $this->fdScheme = DepositScheme::firstOrCreate(
            ['code' => 'FD_SCHEME_TEST'],
            [
                'name' => 'Fixed Deposit 1Yr Test',
                'deposit_type' => 'FD',
                'scheme_type' => 'FD',
                'min_amount' => 1000,
                'is_active' => true,
            ]
        );

        $this->rdScheme = DepositScheme::firstOrCreate(
            ['code' => 'RD_SCHEME_TEST'],
            [
                'name' => 'Recurring Deposit 1Yr Test',
                'deposit_type' => 'RD',
                'scheme_type' => 'RD',
                'min_amount' => 500,
                'is_active' => true,
            ]
        );

        $this->ddScheme = DepositScheme::firstOrCreate(
            ['code' => 'DD_SCHEME_TEST'],
            [
                'name' => 'Daily Deposit Test',
                'deposit_type' => 'DD',
                'scheme_type' => 'DD',
                'min_amount' => 100,
                'is_active' => true,
            ]
        );
    }

    /**
     * Test 1: Different deposit targets configured for different ranks.
     */
    public function test_different_targets_for_different_ranks(): void
    {
        // Rank 1 target: ₹10,00,000 (FD 90% = ₹9L, RD 10% = ₹1L, Recruits: 5)
        AgentRankTarget::updateOrCreate(
            ['financial_year_id' => $this->fy2026->id, 'rank_id' => $this->rank1->id],
            [
                'total_deposit_target' => 1000000,
                'fd_percentage' => 90,
                'rd_percentage' => 10,
                'fd_target_amount' => 900000,
                'rd_target_amount' => 100000,
                'recruitment_target' => 5,
                'status' => 'active',
            ]
        );

        // Rank 2 target: ₹7,00,000 (FD 90% = ₹6.3L, RD 10% = ₹70k, Recruits: 4)
        AgentRankTarget::updateOrCreate(
            ['financial_year_id' => $this->fy2026->id, 'rank_id' => $this->rank2->id],
            [
                'total_deposit_target' => 700000,
                'fd_percentage' => 90,
                'rd_percentage' => 10,
                'fd_target_amount' => 630000,
                'rd_target_amount' => 70000,
                'recruitment_target' => 4,
                'status' => 'active',
            ]
        );

        // Service resolves appropriate target for Rank 1
        $target1 = $this->targetService->getAgentTarget($this->agent1, $this->fy2026);
        $this->assertEquals(1000000, $target1['total_deposit']);
        $this->assertEquals(900000, $target1['fd_amount']);
        $this->assertEquals(100000, $target1['rd_amount']);
        $this->assertEquals(5, $target1['recruitment']);

        // Temporarily promote agent to Rank 2
        $this->agent1->rank_id = $this->rank2->id;
        $this->agent1->save();
        $this->agent1->refresh();
        $this->agent1->unsetRelation('careerLevel');
        $this->agent1->unsetRelation('rank');

        $target2 = $this->targetService->getAgentTarget($this->agent1, $this->fy2026);
        $this->assertEquals(700000, $target2['total_deposit']);
        $this->assertEquals(630000, $target2['fd_amount']);
        $this->assertEquals(70000, $target2['rd_amount']);
        $this->assertEquals(4, $target2['recruitment']);
    }

    /**
     * Test 2: Financial year boundaries (1 April – 31 March).
     * Transactions opened outside the financial year are excluded.
     */
    public function test_financial_year_boundaries_strictly_enforced(): void
    {
        $customer = $this->createCustomer($this->branch1->id);

        // Prior FY deposit (opened on 2026-03-31, prior to FY start 2026-04-01)
        Deposit::create([
            'deposit_number' => 'DEP_PRIOR_01',
            'agent_id' => $this->agent1->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch1->id,
            'deposit_scheme_id' => $this->fdScheme->id,
            'scheme_type' => 'FD',
            'amount' => 200000,
            'deposit_amount' => 200000,
            'status' => Deposit::STATUS_ACTIVE,
            'start_date' => '2026-03-31',
            'deposit_date' => '2026-03-31',
            'created_at' => '2026-03-31 23:59:59',
        ]);

        // Current FY deposit (opened on 2026-04-01 00:00:01)
        Deposit::create([
            'deposit_number' => 'DEP_CURRENT_01',
            'agent_id' => $this->agent1->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch1->id,
            'deposit_scheme_id' => $this->fdScheme->id,
            'scheme_type' => 'FD',
            'amount' => 300000,
            'deposit_amount' => 300000,
            'status' => Deposit::STATUS_ACTIVE,
            'start_date' => '2026-04-01',
            'deposit_date' => '2026-04-01',
            'created_at' => '2026-04-01 00:00:01',
        ]);

        // Next FY deposit (opened on 2027-04-01 00:00:01, after FY end 2027-03-31)
        Deposit::create([
            'deposit_number' => 'DEP_NEXT_01',
            'agent_id' => $this->agent1->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch1->id,
            'deposit_scheme_id' => $this->fdScheme->id,
            'scheme_type' => 'FD',
            'amount' => 500000,
            'deposit_amount' => 500000,
            'status' => Deposit::STATUS_ACTIVE,
            'start_date' => '2027-04-01',
            'deposit_date' => '2027-04-01',
            'created_at' => '2027-04-01 00:00:01',
        ]);

        $achievement = $this->targetService->calculateAgentDepositAchievement($this->agent1, $this->fy2026);

        // Only the ₹3,00,000 deposit inside FY 2026-27 should be included!
        $this->assertEquals(300000, $achievement['fd_achieved']);
        $this->assertEquals(300000, $achievement['total_achieved']);
        $this->assertEquals(1, $achievement['eligible_deposits']->count());
    }

    /**
     * Test 3: FD / RD percentage calculations and 100% sum constraint validation.
     */
    public function test_fd_rd_percentage_calculations_and_validation(): void
    {
        $this->actingAs($this->superAdmin);

        // Ensure no pre-existing configuration for this test rank
        AgentRankTarget::where('financial_year_id', $this->fy2026->id)->where('rank_id', $this->rank3->id)->delete();

        // Attempting to post FD 80% + RD 10% (sum = 90% != 100%) must fail
        $response = $this->post(route('admin.targets.configurations.store'), [
            'financial_year_id' => $this->fy2026->id,
            'rank_id' => $this->rank3->id,
            'total_deposit_target' => 500000,
            'fd_percentage' => 80,
            'rd_percentage' => 10,
            'recruitment_target' => 3,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['fd_percentage']);

        // Posting exact 100% split (FD 90% + RD 10%) succeeds and computes amounts
        $validResponse = $this->post(route('admin.targets.configurations.store'), [
            'financial_year_id' => $this->fy2026->id,
            'rank_id' => $this->rank3->id,
            'total_deposit_target' => 500000,
            'fd_percentage' => 90,
            'rd_percentage' => 10,
            'recruitment_target' => 3,
            'status' => 'active',
        ]);

        $validResponse->assertRedirect();

        $savedTarget = AgentRankTarget::where('financial_year_id', $this->fy2026->id)
            ->where('rank_id', $this->rank3->id)
            ->first();

        $this->assertNotNull($savedTarget);
        $this->assertEquals(450000, $savedTarget->fd_target_amount); // 90% of 5,00,000
        $this->assertEquals(50000, $savedTarget->rd_target_amount);   // 10% of 5,00,000
    }

    /**
     * Test 4: Deposit achievement calculation: FD, RD, Total, Remaining, and Percentages.
     */
    public function test_deposit_achievement_and_remaining_calculation(): void
    {
        AgentRankTarget::updateOrCreate(
            ['financial_year_id' => $this->fy2026->id, 'rank_id' => $this->rank1->id],
            [
                'total_deposit_target' => 1000000,
                'fd_percentage' => 90,
                'rd_percentage' => 10,
                'fd_target_amount' => 900000,
                'rd_target_amount' => 100000,
                'recruitment_target' => 5,
                'status' => 'active',
            ]
        );

        $customer = $this->createCustomer($this->branch1->id);

        // Mobilise ₹4,50,000 FD
        Deposit::create([
            'deposit_number' => 'DEP_FD_001',
            'agent_id' => $this->agent1->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch1->id,
            'deposit_scheme_id' => $this->fdScheme->id,
            'scheme_type' => 'FD',
            'amount' => 450000,
            'deposit_amount' => 450000,
            'status' => Deposit::STATUS_ACTIVE,
            'created_at' => '2026-06-15 10:00:00',
        ]);

        // Mobilise ₹60,000 RD
        Deposit::create([
            'deposit_number' => 'DEP_RD_001',
            'agent_id' => $this->agent1->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch1->id,
            'deposit_scheme_id' => $this->rdScheme->id,
            'scheme_type' => 'RD',
            'amount' => 60000,
            'deposit_amount' => 60000,
            'status' => Deposit::STATUS_ACTIVE,
            'created_at' => '2026-07-20 10:00:00',
        ]);

        $performance = $this->targetService->calculateAgentTargetPerformance($this->agent1, $this->fy2026);
        $dep = $performance['deposits'];

        // Total: 4,50,000 + 60,000 = 5,10,000 out of 10,00,000 (51.0%)
        $this->assertEquals(510000, $dep['total_achieved']);
        $this->assertEquals(490000, $dep['total_remaining']);
        $this->assertEquals(51.0, $dep['total_percentage']);

        // FD: 4,50,000 out of 9,00,000 (50.0%)
        $this->assertEquals(450000, $dep['fd_achieved']);
        $this->assertEquals(450000, $dep['fd_remaining']);
        $this->assertEquals(50.0, $dep['fd_percentage']);

        // RD: 60,000 out of 100,000 (60.0%)
        $this->assertEquals(60000, $dep['rd_achieved']);
        $this->assertEquals(40000, $dep['rd_remaining']);
        $this->assertEquals(60.0, $dep['rd_percentage']);
    }

    /**
     * Test 5: Cancelled, reversed, and rejected transactions are strictly excluded.
     */
    public function test_cancelled_and_reversed_deposits_are_excluded(): void
    {
        $customer = $this->createCustomer($this->branch1->id);

        // Active deposit (₹1,00,000)
        Deposit::create([
            'deposit_number' => 'DEP_ACTIVE_001',
            'agent_id' => $this->agent1->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch1->id,
            'deposit_scheme_id' => $this->fdScheme->id,
            'scheme_type' => 'FD',
            'amount' => 100000,
            'deposit_amount' => 100000,
            'status' => Deposit::STATUS_ACTIVE,
            'created_at' => '2026-05-10 10:00:00',
        ]);

        // Cancelled deposit (₹2,00,000)
        Deposit::create([
            'deposit_number' => 'DEP_CANCELLED_001',
            'agent_id' => $this->agent1->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch1->id,
            'deposit_scheme_id' => $this->fdScheme->id,
            'scheme_type' => 'FD',
            'amount' => 200000,
            'deposit_amount' => 200000,
            'status' => 'CANCELLED',
            'created_at' => '2026-05-15 10:00:00',
        ]);

        // Rejected deposit (₹1,50,000)
        Deposit::create([
            'deposit_number' => 'DEP_REJECTED_001',
            'agent_id' => $this->agent1->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch1->id,
            'deposit_scheme_id' => $this->fdScheme->id,
            'scheme_type' => 'FD',
            'amount' => 150000,
            'deposit_amount' => 150000,
            'status' => Deposit::STATUS_REJECTED,
            'created_at' => '2026-05-20 10:00:00',
        ]);

        $achievement = $this->targetService->calculateAgentDepositAchievement($this->agent1, $this->fy2026);

        // Only the active ₹1,00,000 deposit qualifies!
        $this->assertEquals(100000, $achievement['fd_achieved']);
        $this->assertEquals(100000, $achievement['total_achieved']);
        $this->assertEquals(1, $achievement['eligible_deposits']->count());
    }

    /**
     * Test 6: DD (Daily Deposits) and Kisan deposits are segregated and excluded from standard targets.
     */
    public function test_dd_and_kisan_deposits_excluded_from_standard_target(): void
    {
        $customer = $this->createCustomer($this->branch1->id);

        // DD deposit (₹50,000)
        Deposit::create([
            'deposit_number' => 'DEP_DD_001',
            'agent_id' => $this->agent1->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->branch1->id,
            'deposit_scheme_id' => $this->ddScheme->id,
            'scheme_type' => 'DD',
            'deposit_type' => 'DD',
            'amount' => 50000,
            'deposit_amount' => 50000,
            'status' => Deposit::STATUS_ACTIVE,
            'created_at' => '2026-08-01 10:00:00',
        ]);

        $achievement = $this->targetService->calculateAgentDepositAchievement($this->agent1, $this->fy2026);

        // Standard FD and RD achievement must be 0
        $this->assertEquals(0, $achievement['fd_achieved']);
        $this->assertEquals(0, $achievement['rd_achieved']);
        $this->assertEquals(0, $achievement['total_achieved']);
    }

    /**
     * Test 7: Direct agent recruitment counting (parent_agent_id = $agent->id only).
     */
    public function test_direct_agent_recruitment_counting(): void
    {
        // Child 1: Direct recruit of agent1 in FY 2026-27 (active)
        $child1 = $this->createAgent([
            'branch_id' => $this->branch1->id,
            'parent_agent_id' => $this->agent1->id,
            'rank_id' => $this->rank1->id,
            'name' => 'Direct Recruit 1',
            'agent_id' => 'AG_REC_001',
            'member_code' => 'MEM_REC_001',
            'status' => Agent::STATUS_ACTIVE,
            'joining_date' => '2026-05-10',
            'created_at' => '2026-05-10 10:00:00',
        ]);

        // Child 2: Direct recruit of agent1 in FY 2026-27 (active)
        $child2 = $this->createAgent([
            'branch_id' => $this->branch1->id,
            'parent_agent_id' => $this->agent1->id,
            'rank_id' => $this->rank1->id,
            'name' => 'Direct Recruit 2',
            'agent_id' => 'AG_REC_002',
            'member_code' => 'MEM_REC_002',
            'status' => Agent::STATUS_ACTIVE,
            'joining_date' => '2026-06-15',
            'created_at' => '2026-06-15 10:00:00',
        ]);

        // Grandchild: Recruited by Child 1 (indirect downline of agent1)
        $this->createAgent([
            'branch_id' => $this->branch1->id,
            'parent_agent_id' => $child1->id,
            'rank_id' => $this->rank1->id,
            'name' => 'Grandchild Agent',
            'agent_id' => 'AG_REC_003',
            'member_code' => 'MEM_REC_003',
            'status' => Agent::STATUS_ACTIVE,
            'joining_date' => '2026-07-20',
            'created_at' => '2026-07-20 10:00:00',
        ]);

        $recruitment = $this->targetService->calculateAgentRecruitmentAchievement($this->agent1, $this->fy2026);

        // Only direct recruits (child1 and child2) count for agent1: 2 recruits
        $this->assertEquals(2, $recruitment['achieved']);
        $this->assertEquals(2, $recruitment['recruits']->count());
    }

    /**
     * Test 8: Agent approval and activation requirements for recruitment target.
     * Excludes rejected, inactive, and pending agents.
     */
    public function test_only_active_and_approved_agents_count_towards_recruitment(): void
    {
        // Active recruit
        $this->createAgent([
            'branch_id' => $this->branch1->id,
            'parent_agent_id' => $this->agent1->id,
            'rank_id' => $this->rank1->id,
            'name' => 'Active Recruit',
            'agent_id' => 'AG_REC_ACT',
            'member_code' => 'MEM_REC_ACT',
            'status' => Agent::STATUS_ACTIVE,
            'joining_date' => '2026-05-01',
            'created_at' => '2026-05-01 10:00:00',
        ]);

        // Inactive recruit
        $this->createAgent([
            'branch_id' => $this->branch1->id,
            'parent_agent_id' => $this->agent1->id,
            'rank_id' => $this->rank1->id,
            'name' => 'Inactive Recruit',
            'agent_id' => 'AG_REC_INACT',
            'member_code' => 'MEM_REC_INACT',
            'status' => 'inactive',
            'joining_date' => '2026-05-02',
            'created_at' => '2026-05-02 10:00:00',
        ]);

        // Pending approval recruit
        $this->createAgent([
            'branch_id' => $this->branch1->id,
            'parent_agent_id' => $this->agent1->id,
            'rank_id' => $this->rank1->id,
            'name' => 'Pending Recruit',
            'agent_id' => 'AG_REC_PEND',
            'member_code' => 'MEM_REC_PEND',
            'status' => Agent::STATUS_PENDING,
            'joining_date' => '2026-05-03',
            'created_at' => '2026-05-03 10:00:00',
        ]);

        // Rejected recruit
        $this->createAgent([
            'branch_id' => $this->branch1->id,
            'parent_agent_id' => $this->agent1->id,
            'rank_id' => $this->rank1->id,
            'name' => 'Rejected Recruit',
            'agent_id' => 'AG_REC_REJ',
            'member_code' => 'MEM_REC_REJ',
            'status' => Agent::STATUS_REJECTED,
            'joining_date' => '2026-05-04',
            'created_at' => '2026-05-04 10:00:00',
        ]);

        $recruitment = $this->targetService->calculateAgentRecruitmentAchievement($this->agent1, $this->fy2026);

        // Only the 1 active recruit must count
        $this->assertEquals(1, $recruitment['achieved']);
        $this->assertEquals(1, $recruitment['recruits']->count());
    }

    /**
     * Test 9: Rank promotion and target revision history tracking.
     * Preserves effective dates and does not overwrite historical achievements.
     */
    public function test_target_revision_history_and_effective_dates(): void
    {
        $this->actingAs($this->superAdmin);

        // Assign custom target revision for agent1
        $response = $this->post(route('admin.targets.agents.assign-custom', $this->agent1->id), [
            'financial_year_id' => $this->fy2026->id,
            'total_deposit_target' => 1500000,
            'fd_percentage' => 90,
            'rd_percentage' => 10,
            'recruitment_target' => 8,
            'effective_from' => '2026-06-01',
            'revision_reason' => 'Mid-year performance incentive revision',
        ]);

        $response->assertRedirect();

        // Verify assignment recorded
        $assignment = AgentTargetAssignment::where('agent_id', $this->agent1->id)
            ->where('financial_year_id', $this->fy2026->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertEquals(1500000, $assignment->total_deposit_target);
        $this->assertEquals(1350000, $assignment->fd_target_amount);
        $this->assertEquals(150000, $assignment->rd_target_amount);
        $this->assertEquals(8, $assignment->recruitment_target);
        $this->assertEquals('2026-06-01', $assignment->effective_from->toDateString());

        // Target calculation service now resolves the custom revision
        $resolved = $this->targetService->getAgentTarget($this->agent1, $this->fy2026);
        $this->assertEquals('custom', $resolved['target_type']);
        $this->assertEquals(1500000, $resolved['total_deposit']);
        $this->assertEquals(8, $resolved['recruitment']);
    }

    /**
     * Test 10: Branch restriction enforcement for Branch Admin.
     */
    public function test_branch_admin_restrictions(): void
    {
        // Agent 2 belongs to Branch 2
        $agent2 = $this->createAgent([
            'branch_id' => $this->branch2->id,
            'name' => 'Agent Branch 2',
            'agent_id' => 'AG_BR2_001',
            'member_code' => 'MEM_BR2_001',
            'status' => Agent::STATUS_ACTIVE,
        ]);

        // Branch Admin 1 cannot access Agent 2 details (403 forbidden)
        $this->actingAs($this->branchAdmin1);
        $forbiddenResponse = $this->get(route('admin.targets.agents.show', $agent2->id));
        $forbiddenResponse->assertForbidden();

        // Branch Admin 1 can access Agent 1 in their branch (redirects to career performance)
        $allowedResponse = $this->get(route('admin.targets.agents.show', $this->agent1->id));
        $allowedResponse->assertRedirect(route('admin.career.agent-performance', $this->agent1->id));

        // Super Admin can access Agent 2 in any branch (redirects to career performance)
        $this->actingAs($this->superAdmin);
        $superResponse = $this->get(route('admin.targets.agents.show', $agent2->id));
        $superResponse->assertRedirect(route('admin.career.agent-performance', $agent2->id));
    }

    /**
     * Test 11: Agent role authorization and access boundaries.
     * Agent is redirected to Career Progression, and cannot access admin configurations.
     */
    public function test_agent_permissions_and_boundaries(): void
    {
        $this->actingAs($this->agentUser1);

        // Agent accessing My Targets is redirected to Career Progression
        $myTargetResponse = $this->get(route('agent.targets.index'));
        $myTargetResponse->assertRedirect(route('agent.performance'));

        // Agent redirected when visiting admin overview
        $adminOverviewResponse = $this->get(route('admin.targets.index'));
        $adminOverviewResponse->assertRedirect(route('agent.performance'));

        // Agent gets redirected from target configurations to promotion rules
        $configResponse = $this->get(route('admin.targets.configurations'));
        $configResponse->assertRedirect(route('superadmin.promotion-rules.index'));
    }

    /**
     * Test 12: Target routes redirect to consolidated Promotion Rules and Career pages.
     */
    public function test_all_target_views_render_successfully(): void
    {
        $this->actingAs($this->superAdmin);

        // 1. Overview Dashboard redirects to Promotion Rules
        $this->get(route('admin.targets.index'))->assertRedirect(route('superadmin.promotion-rules.index'));

        // 2. Rank Configurations Directory redirects to Promotion Rules
        $this->get(route('admin.targets.configurations'))->assertRedirect(route('superadmin.promotion-rules.index'));

        // 3. Create Configuration Page redirects to Promotion Rules create
        $this->get(route('admin.targets.configurations.create'))->assertRedirect(route('superadmin.promotion-rules.create'));

        // 4. Agent Directory redirects to Career Performance Matrix
        $this->get(route('admin.targets.agents.index'))->assertRedirect(route('admin.career.performance'));

        // 5. Individual Agent Details redirects to Career Agent Performance
        $this->get(route('admin.targets.agents.show', $this->agent1->id))->assertRedirect(route('admin.career.agent-performance', $this->agent1->id));

        // 6. Target Revisions History redirects to Career History
        $this->get(route('admin.targets.history'))->assertRedirect(route('admin.career.history'));
    }

    /**
     * Test 13: Super Admin can create a new Financial Year as current and targets are auto-cloned.
     */
    public function test_superadmin_can_create_new_financial_year_with_is_current_and_targets_are_cloned(): void
    {
        $this->actingAs($this->superAdmin);

        $startYear = 2030;
        $response = $this->post(route('admin.targets.financial-years.store'), [
            'start_year' => $startYear,
            'is_current' => 1,
            'status' => 'active',
            'description' => 'Test Next Operating Financial Year',
        ]);

        $response->assertRedirect(route('superadmin.promotion-rules.index'));
        $response->assertSessionHas('success');

        $newFy = FinancialYear::where('code', 'FY2030-31')->firstOrFail();
        $this->assertTrue((bool) $newFy->is_current);

        // Verify baseline targets were successfully cloned for the new FY
        $clonedCount = AgentRankTarget::where('financial_year_id', $newFy->id)->count();
        $this->assertGreaterThan(0, $clonedCount, 'Rank targets should be cloned to the new current Financial Year.');
    }

    /**
     * Test 14: Toggling target configuration status records an audit log.
     */
    public function test_superadmin_can_toggle_target_configuration_status_with_audit_log(): void
    {
        $this->actingAs($this->superAdmin);

        $target = AgentRankTarget::firstOrFail();
        $originalStatus = $target->status;

        $response = $this->post(route('admin.targets.configurations.toggle-status', $target->id));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $target->refresh();
        $expectedStatus = $originalStatus === AgentRankTarget::STATUS_ACTIVE ? AgentRankTarget::STATUS_INACTIVE : AgentRankTarget::STATUS_ACTIVE;
        $this->assertEquals($expectedStatus, $target->status);

        // Verify Audit Log recorded
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'target_status_toggled',
            'auditable_type' => AgentRankTarget::class,
            'auditable_id' => $target->id,
        ]);
    }

    /**
     * Test 15: Tenure-based multipliers credit deposits according to duration percentages in agent targets.
     */
    public function test_tenure_based_multipliers_credit_deposits_proportional_to_duration_in_agent_targets(): void
    {
        // 1. Configure active promotion rule with tenure targets enabled
        $rule = PromotionRule::firstOrCreate(
            [
                'from_rank_id' => $this->rank1->id,
                'to_rank_id' => $this->rank2->id,
            ],
            [
                'name' => 'Promotion Test Rule Tenure',
                'status' => PromotionRule::STATUS_ACTIVE,
                'min_team_deposit' => 300000.00,
                'min_individual_deposit' => 50000.00,
                'apply_tenure_targets' => true,
                'tenure_target_percentages' => PromotionRule::DEFAULT_TENURE_TARGETS,
            ]
        );
        $rule->update([
            'status' => PromotionRule::STATUS_ACTIVE,
            'apply_tenure_targets' => true,
            'tenure_target_percentages' => PromotionRule::DEFAULT_TENURE_TARGETS,
        ]);

        $customer = $this->createCustomer();

        // 1 Year FD: ₹1,00,000 -> 20% = ₹20,000
        Deposit::create([
            'deposit_number' => 'DEP_TENURE_1Y',
            'customer_id' => $customer->id,
            'agent_id' => $this->agent1->id,
            'branch_id' => $this->branch1->id,
            'deposit_scheme_id' => $this->fdScheme->id,
            'deposit_type' => 'FD',
            'amount' => 100000.00,
            'start_date' => '2026-05-01',
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'maturity_date' => '2027-05-01',
            'status' => Deposit::STATUS_ACTIVE,
            'created_at' => '2026-05-01 10:00:00',
        ]);

        // 2 Year FD: ₹1,00,000 -> 30% = ₹30,000
        Deposit::create([
            'deposit_number' => 'DEP_TENURE_2Y',
            'customer_id' => $customer->id,
            'agent_id' => $this->agent1->id,
            'branch_id' => $this->branch1->id,
            'deposit_scheme_id' => $this->fdScheme->id,
            'deposit_type' => 'FD',
            'amount' => 100000.00,
            'start_date' => '2026-06-01',
            'tenure' => 24,
            'tenure_unit' => 'MONTHS',
            'maturity_date' => '2028-06-01',
            'status' => Deposit::STATUS_ACTIVE,
            'created_at' => '2026-06-01 10:00:00',
        ]);

        // 7 Year RD: ₹1,00,000 -> 90% = ₹90,000
        Deposit::create([
            'deposit_number' => 'DEP_TENURE_7Y_RD',
            'customer_id' => $customer->id,
            'agent_id' => $this->agent1->id,
            'branch_id' => $this->branch1->id,
            'deposit_scheme_id' => $this->rdScheme->id,
            'deposit_type' => 'RD',
            'amount' => 100000.00,
            'start_date' => '2026-07-01',
            'tenure' => 84,
            'tenure_unit' => 'MONTHS',
            'maturity_date' => '2033-07-01',
            'status' => Deposit::STATUS_ACTIVE,
            'created_at' => '2026-07-01 10:00:00',
        ]);

        $achievement = $this->targetService->calculateAgentDepositAchievement($this->agent1, $this->fy2026);

        $this->assertTrue($achievement['apply_tenure_targets']);
        // FD: ₹20,000 (1Y) + ₹30,000 (2Y) = ₹50,000 credited
        $this->assertEquals(50000.00, $achievement['fd_achieved']);
        $this->assertEquals(200000.00, $achievement['fd_gross_achieved']);

        // RD: ₹90,000 (7Y) = ₹90,000 credited
        $this->assertEquals(90000.00, $achievement['rd_achieved']);
        $this->assertEquals(100000.00, $achievement['rd_gross_achieved']);

        // Total credited = 50,000 + 90,000 = 1,40,000 (Gross = 3,00,000)
        $this->assertEquals(140000.00, $achievement['total_achieved']);
        $this->assertEquals(300000.00, $achievement['total_gross_achieved']);
    }

    /**
     * Test 16: When tenure multipliers are disabled, agent target credits at 100% face value.
     */
    public function test_tenure_multipliers_disabled_defaults_to_100_percent_face_value(): void
    {
        PromotionRule::query()->update(['apply_tenure_targets' => false]);
        DB::table('settings')->updateOrInsert(
            ['key' => 'career.apply_tenure_targets'],
            ['value' => '0', 'type' => 'boolean', 'group' => 'career']
        );

        $customer = $this->createCustomer();

        Deposit::create([
            'deposit_number' => 'DEP_TENURE_DIS_1Y',
            'customer_id' => $customer->id,
            'agent_id' => $this->agent1->id,
            'branch_id' => $this->branch1->id,
            'deposit_scheme_id' => $this->fdScheme->id,
            'deposit_type' => 'FD',
            'amount' => 100000.00,
            'start_date' => '2026-05-10',
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'maturity_date' => '2027-05-10',
            'status' => Deposit::STATUS_ACTIVE,
            'created_at' => '2026-05-10 10:00:00',
        ]);

        $achievement = $this->targetService->calculateAgentDepositAchievement($this->agent1, $this->fy2026);

        $this->assertFalse($achievement['apply_tenure_targets']);
        $this->assertEquals(100000.00, $achievement['fd_achieved']);
        $this->assertEquals(100000.00, $achievement['total_achieved']);
    }
}
