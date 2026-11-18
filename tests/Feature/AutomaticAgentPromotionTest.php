<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Branch;
use App\Models\CareerHistory;
use App\Models\CareerLevel;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\PromotionRequest;
use App\Models\PromotionRule;
use App\Models\Role;
use App\Models\User;
use App\Services\CareerPerformanceService;
use App\Services\PromotionEligibilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AutomaticAgentPromotionTest extends TestCase
{
    use DatabaseTransactions;

    protected CareerPerformanceService $careerService;

    protected PromotionEligibilityService $eligibilityService;

    protected User $superAdmin;

    protected Branch $branch;

    protected CareerLevel $level1;

    protected CareerLevel $level2;

    protected CareerLevel $level3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->careerService = app(CareerPerformanceService::class);
        $this->eligibilityService = app(PromotionEligibilityService::class);

        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR_TEST_AUTO_PROMO'],
            ['name' => 'Auto Promotion Branch', 'is_active' => true]
        );

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::create([
            'name' => 'Super Admin Auto Promo',
            'email' => 'sa_autopromo_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        $this->level1 = CareerLevel::where('level', 1)->firstOrFail();
        $this->level2 = CareerLevel::where('level', 2)->firstOrFail();
        $this->level3 = CareerLevel::where('level', 3)->firstOrFail();
    }

    protected function createAgentWithUser(string $name, CareerLevel $rank, ?Agent $parentAgent = null): Agent
    {
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);

        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)).'_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
        ]);
        $user->roles()->attach($agentRole->id);

        return Agent::create([
            'user_id' => $user->id,
            'member_code' => 'AG_'.rand(10000, 99999),
            'agent_id' => 'AG_'.rand(10000, 99999),
            'name' => $name,
            'email' => $user->email,
            'phone' => '98'.rand(10000000, 99999999),
            'branch_id' => $this->branch->id,
            'rank_id' => $rank->id,
            'parent_agent_id' => $parentAgent?->id,
            'joining_date' => now()->subMonths(6)->toDateString(),
            'status' => 'active',
        ]);
    }

    protected function createDepositForAgent(Agent $agent, string $type, int|float $tenureYears, float $amount, string $status = Deposit::STATUS_ACTIVE): Deposit
    {
        $tenureMonths = (int) round($tenureYears * 12);

        $scheme = DepositScheme::create([
            'name' => "Scheme {$type} {$tenureYears}Y ".uniqid(),
            'code' => 'SCH_'.strtoupper($type)."_{$tenureMonths}M_".rand(100, 999),
            'scheme_type' => $type,
            'min_amount' => '100.00',
            'max_amount' => '50000000.00',
            'tenure' => $tenureMonths,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => '8.00',
            'collection_frequency' => $type === DepositScheme::TYPE_DD ? DepositScheme::FREQ_DAILY : ($type === DepositScheme::TYPE_RD ? DepositScheme::FREQ_MONTHLY : DepositScheme::FREQ_ONE_TIME),
            'status' => DepositScheme::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $customer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Cust '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '91'.rand(10000000, 99999999),
            'date_of_birth' => '1990-01-01',
            'address' => 'Test Address',
            'branch_id' => $this->branch->id,
            'agent_id' => $agent->id,
            'created_by' => $this->superAdmin->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        return Deposit::create([
            'deposit_number' => 'DEP_'.uniqid(),
            'customer_id' => $customer->id,
            'agent_id' => $agent->id,
            'branch_id' => $this->branch->id,
            'deposit_scheme_id' => $scheme->id,
            'scheme_id' => $scheme->id,
            'deposit_type' => $type,
            'amount' => $amount,
            'total_deposited' => $amount,
            'interest_rate' => $scheme->interest_rate,
            'tenure' => $tenureMonths,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'collection_frequency' => $scheme->collection_frequency,
            'start_date' => now()->subDays(10)->toDateString(),
            'maturity_date' => now()->addMonths($tenureMonths)->toDateString(),
            'maturity_amount' => $amount * 1.08,
            'status' => $status,
        ]);
    }

    /**
     * Test agent is automatically promoted when promotional targets are completed with no approval needed.
     */
    public function test_agent_is_automatically_promoted_when_promotional_targets_are_completed(): void
    {
        $rule = PromotionRule::where('from_rank_id', $this->level1->id)
            ->where('to_rank_id', $this->level2->id)
            ->firstOrFail();

        $rule->update([
            'min_individual_deposit' => 0.00,
            'min_team_deposit' => 300000.00,
            'rd_composition_percentage' => 10.00,
            'fd_composition_percentage' => 90.00,
            'min_qualifying_direct_facilitators' => 3,
            'qualifying_agent_tiers' => [['count' => 3, 'rank_id' => $this->level1->id]],
            'apply_tenure_targets' => false,
            'requires_approval' => false,
            'status' => PromotionRule::STATUS_INACTIVE,
        ]);

        $agent = $this->createAgentWithUser('Auto Promote Target Agent', $this->level1);

        // Add 3 qualifying direct downline facilitators
        $this->createAgentWithUser('Downline Agent 1', $this->level1, $agent);
        $this->createAgentWithUser('Downline Agent 2', $this->level1, $agent);
        $this->createAgentWithUser('Downline Agent 3', $this->level1, $agent);

        // Fulfill business targets: 10% RD = ₹40,000, 90% FD = ₹270,000 (Total = ₹3,10,000 >= ₹3,00,000)
        $this->createDepositForAgent($agent, DepositScheme::TYPE_RD, 1, 40000.00);
        $this->createDepositForAgent($agent, DepositScheme::TYPE_FD, 1, 270000.00);

        // Verify agent is still Level 1 before rule is activated
        $agent->refresh();
        $this->assertEquals($this->level1->id, $agent->rank_id);

        // Activate the rule and trigger automatic promotion check
        $rule->update(['status' => PromotionRule::STATUS_ACTIVE]);
        $promotedRequest = $this->careerService->checkAndAutoPromote($agent);

        $this->assertNotNull($promotedRequest);
        $this->assertEquals(PromotionRequest::STATUS_PROMOTED, $promotedRequest->status);
        $this->assertEquals($this->level2->id, $promotedRequest->proposed_level_id);

        $agent->refresh();
        $this->assertEquals($this->level2->id, $agent->rank_id, 'Agent rank must be elevated to Field Officer automatically');

        // Permanent career history record created
        $this->assertDatabaseHas('career_histories', [
            'agent_id' => $agent->id,
            'new_level_id' => $this->level2->id,
            'action' => CareerHistory::ACTION_PROMOTION,
        ]);
    }

    /**
     * Test deposit creation automatically promotes agent when deposit fulfills promotional targets.
     */
    public function test_deposit_creation_automatically_promotes_agent(): void
    {
        $rule = PromotionRule::where('from_rank_id', $this->level1->id)
            ->where('to_rank_id', $this->level2->id)
            ->firstOrFail();

        $rule->update([
            'min_individual_deposit' => 0.00,
            'min_team_deposit' => 100000.00,
            'rd_composition_percentage' => 10.00,
            'fd_composition_percentage' => 90.00,
            'min_qualifying_direct_facilitators' => 0,
            'qualifying_agent_tiers' => [],
            'apply_tenure_targets' => false,
            'requires_approval' => false,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        $agent = $this->createAgentWithUser('Instant Deposit Agent', $this->level1);

        // RD Deposit 10%: ₹20,000
        $this->createDepositForAgent($agent, DepositScheme::TYPE_RD, 1, 20000.00);

        // Agent should not yet be promoted (FD missing)
        $agent->refresh();
        $this->assertEquals($this->level1->id, $agent->rank_id);

        // FD Deposit 90%: ₹90,000 -> completes all promotional targets
        $this->createDepositForAgent($agent, DepositScheme::TYPE_FD, 1, 90000.00);

        // Run checkAndAutoPromote
        $this->careerService->checkAndAutoPromote($agent);

        $agent->refresh();
        $this->assertEquals($this->level2->id, $agent->rank_id, 'Agent must be automatically promoted upon deposit fulfillment');
    }

    /**
     * Test downline agent advancement automatically promotes upline parent agent when team targets are met.
     */
    public function test_downline_agent_promotion_automatically_triggers_upline_promotion(): void
    {
        // Rule 1: Facilitator -> Field Officer (requires only business)
        $rule1 = PromotionRule::where('from_rank_id', $this->level1->id)
            ->where('to_rank_id', $this->level2->id)
            ->firstOrFail();

        $rule1->update([
            'min_individual_deposit' => 0.00,
            'min_team_deposit' => 50000.00,
            'rd_composition_percentage' => 10.00,
            'fd_composition_percentage' => 90.00,
            'min_qualifying_direct_facilitators' => 0,
            'qualifying_agent_tiers' => [],
            'apply_tenure_targets' => false,
            'requires_approval' => false,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        // Rule 2: Field Officer -> Development Officer (requires 1 qualifying direct Field Officer)
        $rule2 = PromotionRule::where('from_rank_id', $this->level2->id)
            ->where('to_rank_id', $this->level3->id)
            ->firstOrFail();

        $rule2->update([
            'min_individual_deposit' => 0.00,
            'min_team_deposit' => 50000.00,
            'rd_composition_percentage' => 10.00,
            'fd_composition_percentage' => 90.00,
            'min_qualifying_direct_facilitators' => 1,
            'qualifying_rank_id' => $this->level2->id,
            'qualifying_agent_tiers' => [['count' => 1, 'rank_id' => $this->level2->id]],
            'apply_tenure_targets' => false,
            'requires_approval' => false,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        // Upline agent is already at Level 2 (Field Officer)
        $uplineAgent = $this->createAgentWithUser('Parent Field Officer', $this->level2);

        // Downline agent is at Level 1 (Facilitator)
        $downlineAgent = $this->createAgentWithUser('Junior Facilitator', $this->level1, $uplineAgent);

        // Provide business volume for both
        $this->createDepositForAgent($downlineAgent, DepositScheme::TYPE_RD, 1, 10000.00);
        $this->createDepositForAgent($downlineAgent, DepositScheme::TYPE_FD, 1, 50000.00);

        // Downline achieves Level 1 -> Level 2 promotion
        $this->careerService->checkAndAutoPromote($downlineAgent);

        $downlineAgent->refresh();
        $this->assertEquals($this->level2->id, $downlineAgent->rank_id, 'Downline agent must be promoted to Field Officer');

        // Upline parent agent should also automatically be promoted to Level 3 (Development Officer)
        $uplineAgent->refresh();
        $this->assertEquals($this->level3->id, $uplineAgent->rank_id, 'Parent agent must be cascade-promoted to Development Officer');
    }

    /**
     * Test agent portal automatically promotes agent on viewing performance page.
     */
    public function test_agent_portal_auto_promotes_when_visiting_performance_page(): void
    {
        $rule = PromotionRule::where('from_rank_id', $this->level1->id)
            ->where('to_rank_id', $this->level2->id)
            ->firstOrFail();

        $rule->update([
            'min_individual_deposit' => 0.00,
            'min_team_deposit' => 50000.00,
            'rd_composition_percentage' => 10.00,
            'fd_composition_percentage' => 90.00,
            'min_qualifying_direct_facilitators' => 0,
            'qualifying_agent_tiers' => [],
            'apply_tenure_targets' => false,
            'requires_approval' => false,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        $agent = $this->createAgentWithUser('Portal Auto Agent', $this->level1);
        $this->createDepositForAgent($agent, DepositScheme::TYPE_RD, 1, 10000.00);
        $this->createDepositForAgent($agent, DepositScheme::TYPE_FD, 1, 50000.00);

        $response = $this->actingAs($agent->user)->get(route('agent.performance'));
        $response->assertOk();

        $agent->refresh();
        $this->assertEquals($this->level2->id, $agent->rank_id, 'Visiting performance page must automatically promote eligible agent');
    }

    /**
     * Test submitting promotion request when auto-promote is active executes immediately without pending review.
     */
    public function test_agent_submitting_request_auto_promotes_immediately_without_approval(): void
    {
        $rule = PromotionRule::where('from_rank_id', $this->level1->id)
            ->where('to_rank_id', $this->level2->id)
            ->firstOrFail();

        $rule->update([
            'min_individual_deposit' => 0.00,
            'min_team_deposit' => 50000.00,
            'rd_composition_percentage' => 10.00,
            'fd_composition_percentage' => 90.00,
            'min_qualifying_direct_facilitators' => 0,
            'qualifying_agent_tiers' => [],
            'apply_tenure_targets' => false,
            'requires_approval' => false,
            'status' => PromotionRule::STATUS_INACTIVE,
        ]);

        $agent = $this->createAgentWithUser('Claim Agent', $this->level1);
        $this->createDepositForAgent($agent, DepositScheme::TYPE_RD, 1, 10000.00);
        $this->createDepositForAgent($agent, DepositScheme::TYPE_FD, 1, 50000.00);

        // Verify agent is still Level 1 before rule activation
        $agent->refresh();
        $this->assertEquals($this->level1->id, $agent->rank_id);

        // Activate rule so the manual submission executes auto-promotion immediately
        $rule->update(['status' => PromotionRule::STATUS_ACTIVE]);

        $response = $this->actingAs($agent->user)->post(route('agent.promotion-request.store'), [
            'remarks' => 'Completed all promotional targets.',
        ]);

        $response->assertSessionHas('success');

        $agent->refresh();
        $this->assertEquals($this->level2->id, $agent->rank_id);

        // Must be in PROMOTED status, not PENDING_REVIEW
        $this->assertDatabaseHas('promotion_requests', [
            'agent_id' => $agent->id,
            'proposed_level_id' => $this->level2->id,
            'status' => PromotionRequest::STATUS_PROMOTED,
        ]);
    }
}
