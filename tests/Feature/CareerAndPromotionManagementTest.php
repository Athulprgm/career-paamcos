<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\CareerHistory;
use App\Models\CareerLevel;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositCollection;
use App\Models\DepositScheme;
use App\Models\PromotionRequest;
use App\Models\PromotionRule;
use App\Models\Role;
use App\Models\User;
use App\Services\CareerPerformanceService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CareerAndPromotionManagementTest extends TestCase
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

    protected ?CareerLevel $level1 = null;

    protected ?CareerLevel $level2 = null;

    protected ?CareerLevel $level3 = null;

    protected ?DepositScheme $scheme = null;

    protected ?CareerPerformanceService $careerService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->careerService = app(CareerPerformanceService::class);

        // 1. Roles
        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin', 'status' => 'active']);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);

        // 2. Branches
        $this->branch1 = Branch::firstOrCreate(
            ['code' => 'BR_P7_1'],
            ['name' => 'Branch One Phase 7', 'is_active' => true]
        );

        $this->branch2 = Branch::firstOrCreate(
            ['code' => 'BR_P7_2'],
            ['name' => 'Branch Two Phase 7', 'is_active' => true]
        );

        // 3. Super Admin
        $this->superAdmin = User::create([
            'name' => 'Super Admin P7',
            'email' => 'superadmin_p7_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        // 4. Branch Admins
        $this->branchAdmin1 = User::create([
            'name' => 'Branch Admin 1 P7',
            'email' => 'badmin1_p7_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch1->id,
        ]);
        $this->branchAdmin1->roles()->attach($adminRole->id);

        $this->branchAdmin2 = User::create([
            'name' => 'Branch Admin 2 P7',
            'email' => 'badmin2_p7_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch2->id,
        ]);
        $this->branchAdmin2->roles()->attach($adminRole->id);

        // 5. Configured Career Levels
        $this->level1 = CareerLevel::firstOrCreate(
            ['code' => 'FAC_P7'],
            [
                'name' => 'Facilitator P7',
                'level' => 101,
                'sort_order' => 101,
                'status' => 'active',
                'min_service_days' => 0,
                'min_active_customers' => 0,
                'min_deposit_count' => 0,
                'min_deposit_amount' => 0.00,
                'min_collection_amount' => 0.00,
                'min_commission_amount' => 0.00,
            ]
        );

        $this->level2 = CareerLevel::firstOrCreate(
            ['code' => 'FO_P7'],
            [
                'name' => 'Field Officer P7',
                'level' => 102,
                'sort_order' => 102,
                'status' => 'active',
                'min_service_days' => 30,
                'min_active_customers' => 2,
                'min_deposit_count' => 2,
                'min_deposit_amount' => 20000.00,
                'min_collection_amount' => 5000.00,
                'min_commission_amount' => 200.00,
                'min_performance_score' => 20.00,
            ]
        );

        $this->level3 = CareerLevel::firstOrCreate(
            ['code' => 'DO_P7'],
            [
                'name' => 'Development Officer P7',
                'level' => 103,
                'sort_order' => 103,
                'status' => 'active',
                'min_service_days' => 60,
                'min_active_customers' => 5,
                'min_deposit_count' => 5,
                'min_deposit_amount' => 100000.00,
                'min_collection_amount' => 25000.00,
                'min_commission_amount' => 1000.00,
                'min_performance_score' => 50.00,
            ]
        );

        // 6. Agents
        $this->agentUser1 = User::create([
            'name' => 'Agent 1 P7',
            'email' => 'agent1_p7_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch1->id,
        ]);
        $this->agentUser1->roles()->attach($agentRole->id);

        $this->agent1 = Agent::create([
            'user_id' => $this->agentUser1->id,
            'member_code' => 'AGP7_1_'.rand(1000, 9999),
            'agent_id' => 'AGP7_1_'.rand(1000, 9999),
            'name' => 'Agent One P7',
            'email' => $this->agentUser1->email,
            'phone' => '9876541111',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->level1->id,
            'joining_date' => now()->subDays(45)->toDateString(),
            'status' => 'active',
        ]);

        $this->agentUser2 = User::create([
            'name' => 'Agent 2 P7',
            'email' => 'agent2_p7_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch2->id,
        ]);
        $this->agentUser2->roles()->attach($agentRole->id);

        $this->agent2 = Agent::create([
            'user_id' => $this->agentUser2->id,
            'member_code' => 'AGP7_2_'.rand(1000, 9999),
            'agent_id' => 'AGP7_2_'.rand(1000, 9999),
            'name' => 'Agent Two P7',
            'email' => $this->agentUser2->email,
            'phone' => '9876542222',
            'branch_id' => $this->branch2->id,
            'rank_id' => $this->level1->id,
            'joining_date' => now()->subDays(10)->toDateString(),
            'status' => 'active',
        ]);

        // 7. Scheme
        $this->scheme = DepositScheme::create([
            'name' => 'Career Test Scheme '.uniqid(),
            'code' => 'CTS_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_RD,
            'min_amount' => '500.00',
            'max_amount' => '50000.00',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => '6.50',
            'collection_frequency' => DepositScheme::FREQ_MONTHLY,
            'status' => DepositScheme::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    /**
     * Helper to seed business data for agent1 to qualify for level2.
     */
    protected function seedBusinessDataForAgent1(): void
    {
        for ($i = 1; $i <= 2; $i++) {
            $customer = Customer::create([
                'customer_number' => Customer::generateCustomerNumber(),
                'name' => "P7 Customer {$i} ".uniqid(),
                'customer_type' => 'individual',
                'mobile' => '98765432'.rand(10, 99),
                'date_of_birth' => '1990-01-01',
                'address' => 'Branch 1 Road',
                'pin_code' => '682001',
                'branch_id' => $this->branch1->id,
                'agent_id' => $this->agent1->id,
                'status' => Customer::STATUS_ACTIVE,
                'created_by' => $this->superAdmin->id,
            ]);

            $deposit = Deposit::create([
                'deposit_number' => Deposit::generateDepositNumber(),
                'customer_id' => $customer->id,
                'deposit_scheme_id' => $this->scheme->id,
                'branch_id' => $this->branch1->id,
                'agent_id' => $this->agent1->id,
                'amount' => '15000.00',
                'balance' => '0.00',
                'maturity_amount' => '16000.00',
                'start_date' => now()->toDateString(),
                'maturity_date' => now()->addMonths(12)->toDateString(),
                'status' => Deposit::STATUS_ACTIVE,
                'created_by' => $this->superAdmin->id,
            ]);

            $collection = DepositCollection::create([
                'collection_number' => DepositCollection::generateCollectionNumber(),
                'deposit_id' => $deposit->id,
                'customer_id' => $customer->id,
                'branch_id' => $this->branch1->id,
                'agent_id' => $this->agent1->id,
                'installment_number' => 1,
                'due_date' => now()->toDateString(),
                'due_amount' => '3000.00',
                'paid_amount' => '3000.00',
                'outstanding_amount' => '0.00',
                'status' => DepositCollection::STATUS_PAID,
                'collection_date' => now()->toDateString(),
            ]);

            Commission::create([
                'commission_number' => Commission::generateCommissionNumber(),
                'commission_id' => Commission::generateCommissionNumber(),
                'agent_id' => $this->agent1->id,
                'branch_id' => $this->branch1->id,
                'customer_id' => $customer->id,
                'deposit_id' => $deposit->id,
                'deposit_collection_id' => $collection->id,
                'base_amount' => '3000.00',
                'commission_rate' => '5.00',
                'commission_amount' => '150.00',
                'status' => Commission::STATUS_PAID,
                'period' => now()->format('Y-m'),
                'created_by' => $this->superAdmin->id,
                'member_code' => $this->agent1->member_code,
                'rank_id' => $this->level1->id,
            ]);
        }
    }

    /**
     * Test real-time performance calculation from database data.
     */
    public function test_calculate_agent_performance_from_database(): void
    {
        $this->seedBusinessDataForAgent1();

        $performance = $this->careerService->calculateAgentPerformance($this->agent1);

        $this->assertEquals(2, $performance['active_customers']);
        $this->assertEquals(2, $performance['deposit_count']);
        $this->assertEquals(30000.00, $performance['deposit_amount']); // 15000 * 2
        $this->assertEquals(2, $performance['collection_count']);
        $this->assertEquals(6000.00, $performance['collection_amount']); // 3000 * 2
        $this->assertEquals(300.00, $performance['commission_amount']); // 150 * 2
        $this->assertGreaterThan(0, $performance['performance_score']);
        $this->assertEquals(45, $performance['tenure_days']);
    }

    /**
     * Test eligibility engine: Agent qualifies when criteria are achieved.
     */
    public function test_eligibility_engine_evaluates_qualifications(): void
    {
        // Agent 2 has not mobilized sufficient business yet
        $ineligibleResult = $this->careerService->checkEligibility($this->agent2, $this->level2);
        $this->assertFalse($ineligibleResult['is_eligible']);
        $this->assertNotEmpty($ineligibleResult['reasons']);

        // Agent 1 with seeded data achieves Level 2 criteria
        $this->seedBusinessDataForAgent1();
        $eligibleResult = $this->careerService->checkEligibility($this->agent1, $this->level2);

        $this->assertTrue($eligibleResult['is_eligible']);
        $this->assertEquals($this->level2->id, $eligibleResult['target_level']->id);
        $this->assertTrue($eligibleResult['criteria']['service_days']['achieved']);
        $this->assertTrue($eligibleResult['criteria']['active_customers']['achieved']);
        $this->assertTrue($eligibleResult['criteria']['deposit_amount']['achieved']);
        $this->assertTrue($eligibleResult['criteria']['collection_amount']['achieved']);
    }

    /**
     * Test inactive agent is never eligible for promotion.
     */
    public function test_inactive_agent_cannot_be_eligible(): void
    {
        $this->seedBusinessDataForAgent1();
        $this->agent1->update(['status' => 'inactive']);

        $result = $this->careerService->checkEligibility($this->agent1, $this->level2);
        $this->assertFalse($result['is_eligible']);
        $this->assertStringContainsString('inactive', $result['reasons'][0]);

        $this->expectException(ValidationException::class);
        $this->careerService->submitPromotionRequest($this->agent1, $this->level2, 'Promo', null, $this->superAdmin);
    }

    /**
     * Test complete promotion lifecycle:
     * Request -> PENDING_REVIEW -> Approve -> PROMOTED -> Agent Rank Updated -> Career History Created.
     */
    public function test_complete_promotion_and_approval_workflow(): void
    {
        $this->seedBusinessDataForAgent1();

        // 1. Submit Promotion Request
        $request = $this->careerService->submitPromotionRequest(
            agent: $this->agent1,
            proposedLevel: $this->level2,
            remarks: 'Strong initial field performance.',
            period: now()->format('Y-m'),
            requester: $this->superAdmin
        );

        $this->assertInstanceOf(PromotionRequest::class, $request);
        $this->assertEquals(PromotionRequest::STATUS_PENDING_REVIEW, $request->status);
        $this->assertStringStartsWith('PR', $request->request_number);
        $this->assertEquals($this->agent1->id, $request->agent_id);
        $this->assertEquals($this->level2->id, $request->proposed_level_id);

        // 2. Approve & Promote
        $promotedRequest = $this->careerService->approveAndPromote($request, $this->superAdmin, 'Approved by Committee.');

        $this->assertEquals(PromotionRequest::STATUS_PROMOTED, $promotedRequest->status);
        $this->assertNotNull($promotedRequest->promoted_at);
        $this->assertEquals($this->superAdmin->id, $promotedRequest->reviewed_by);

        // 3. Verify Agent Rank Updated
        $this->agent1->refresh();
        $this->assertEquals($this->level2->id, $this->agent1->rank_id);

        // 4. Verify Permanent Career History Created
        $history = CareerHistory::where('agent_id', $this->agent1->id)->latest('id')->first();
        $this->assertNotNull($history);
        $this->assertEquals($this->level1->id, $history->previous_level_id);
        $this->assertEquals($this->level2->id, $history->new_level_id);
        $this->assertEquals($this->superAdmin->id, $history->approved_by);
        $this->assertEquals(CareerHistory::ACTION_PROMOTION, $history->action);
    }

    /**
     * Test promotion rejection workflow:
     * Request -> PENDING_REVIEW -> Reject -> Agent remains at current level -> Audit history created.
     */
    public function test_promotion_rejection_workflow(): void
    {
        $request = $this->careerService->submitPromotionRequest(
            agent: $this->agent1,
            proposedLevel: $this->level2,
            remarks: 'Review needed.',
            requester: $this->superAdmin
        );

        $this->careerService->rejectPromotion($request, 'Target deposit volume shortfall.', $this->superAdmin);

        $request->refresh();
        $this->assertEquals(PromotionRequest::STATUS_REJECTED, $request->status);
        $this->assertEquals('Target deposit volume shortfall.', $request->rejection_reason);

        // Agent rank must remain unchanged
        $this->agent1->refresh();
        $this->assertEquals($this->level1->id, $this->agent1->rank_id);
    }

    /**
     * Test duplicate promotion prevention: Cannot submit second request while one is pending.
     */
    public function test_prevents_duplicate_pending_promotion_requests(): void
    {
        $this->careerService->submitPromotionRequest(
            agent: $this->agent1,
            proposedLevel: $this->level2,
            requester: $this->superAdmin
        );

        $this->expectException(ValidationException::class);
        $this->careerService->submitPromotionRequest(
            agent: $this->agent1,
            proposedLevel: $this->level2,
            requester: $this->superAdmin
        );
    }

    /**
     * Test self-approval is strictly forbidden: Agent cannot approve their own promotion.
     */
    public function test_agent_cannot_approve_own_promotion(): void
    {
        $request = $this->careerService->submitPromotionRequest(
            agent: $this->agent1,
            proposedLevel: $this->level2,
            requester: $this->agentUser1
        );

        // Agent attempts to approve own request
        $this->expectException(ValidationException::class);
        $this->careerService->approveAndPromote($request, $this->agentUser1);
    }

    /**
     * Test authorization boundaries:
     * - Branch Admin 2 cannot view or approve Branch 1 agent's promotion (403)
     * - Agent cannot configure career levels (403)
     * - Agent can view own scorecard (200)
     */
    public function test_authorization_and_cross_branch_boundaries(): void
    {
        $request = $this->careerService->submitPromotionRequest(
            agent: $this->agent1,
            proposedLevel: $this->level2,
            requester: $this->superAdmin
        );

        // Branch Admin 2 tries to access Branch 1 promotion request -> 403 Forbidden
        $this->actingAs($this->branchAdmin2)
            ->get(route('admin.career.requests.show', $request))
            ->assertStatus(403);

        // Branch Admin 2 tries to approve Branch 1 promotion request -> 403 Forbidden
        $this->actingAs($this->branchAdmin2)
            ->post(route('admin.career.requests.approve', $request))
            ->assertStatus(403);

        // Agent tries to create agent rank -> 403 Forbidden
        $this->actingAs($this->agentUser1)
            ->get(route('superadmin.agent-ranks.create'))
            ->assertStatus(403);

        // Agent views own scorecard -> 200 OK
        $this->actingAs($this->agentUser1)
            ->get(route('admin.career.agent-performance', $this->agent1))
            ->assertStatus(200);

        // Agent tries to view another agent's scorecard -> 403 Forbidden
        $this->actingAs($this->agentUser1)
            ->get(route('admin.career.agent-performance', $this->agent2))
            ->assertStatus(403);
    }

    /**
     * Test Agent Rank CRUD and status toggle under Super Admin.
     */
    public function test_agent_rank_crud_and_status_toggle(): void
    {
        // 1. Create rank
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.agent-ranks.store'), [
            'name' => 'Senior Team Leader P7',
            'sort_order' => 40,
            'status' => 'active',
            'description' => 'Team leader overseeing cluster operations.',
        ]);

        $response->assertRedirect(route('superadmin.agent-ranks.index'));
        $this->assertDatabaseHas('ranks', [
            'name' => 'Senior Team Leader P7',
            'sort_order' => 40,
        ]);

        $level = AgentRank::where('name', 'Senior Team Leader P7')->first();

        // 2. Edit rank
        $this->actingAs($this->superAdmin)->put(route('superadmin.agent-ranks.update', $level), [
            'name' => 'Senior Team Leader P7 Updated',
            'sort_order' => 45,
            'status' => 'active',
            'description' => 'Updated description.',
        ])->assertRedirect(route('superadmin.agent-ranks.index'));

        $level->refresh();
        $this->assertEquals(45, $level->sort_order);

        // 3. Toggle Status
        $this->actingAs($this->superAdmin)->post(route('superadmin.agent-ranks.toggle-status', $level));
        $level->refresh();
        $this->assertEquals('inactive', $level->status);
    }

    /**
     * Test UI endpoints for Career module load successfully.
     */
    public function test_career_ui_endpoints_load_successfully(): void
    {
        $request = $this->careerService->submitPromotionRequest(
            agent: $this->agent1,
            proposedLevel: $this->level2,
            requester: $this->superAdmin
        );

        // Overview
        $this->actingAs($this->superAdmin)
            ->get(route('admin.career.overview'))
            ->assertStatus(200)
            ->assertSee('Career & Performance Overview', false);

        // Performance Matrix
        $this->actingAs($this->superAdmin)
            ->get(route('admin.career.performance'))
            ->assertStatus(200)
            ->assertSee($this->agent1->name);

        // Agent Scorecard
        $this->actingAs($this->superAdmin)
            ->get(route('admin.career.agent-performance', $this->agent1))
            ->assertStatus(200)
            ->assertSee('Eligibility Evaluation');

        // Requests Index
        $this->actingAs($this->superAdmin)
            ->get(route('admin.career.requests.index'))
            ->assertStatus(200)
            ->assertSee($request->request_number);

        // Show Request
        $this->actingAs($this->superAdmin)
            ->get(route('admin.career.requests.show', $request))
            ->assertStatus(200)
            ->assertSee($request->request_number);

        // Career History
        $this->actingAs($this->superAdmin)
            ->get(route('admin.career.history'))
            ->assertStatus(200)
            ->assertSee('Career History');
    }

    /**
     * Test scorecard view renders successfully when agent is evaluated against dynamic promotion rule.
     */
    public function test_agent_performance_scorecard_renders_with_dynamic_promotion_rule(): void
    {
        PromotionRule::create([
            'name' => 'Field Promotion Rule',
            'from_rank_id' => $this->level1->id,
            'to_rank_id' => $this->level2->id,
            'min_individual_deposit' => 50000,
            'min_team_deposit' => 100000,
            'min_qualifying_direct_facilitators' => 2,
            'qualifying_rank_id' => $this->level1->id,
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'qualifying_deposit_statuses' => [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED],
            'required_agent_status' => 'active',
            'status' => PromotionRule::STATUS_ACTIVE,
            'requires_approval' => true,
            'authorized_approval_role' => 'admin',
        ]);

        $eval = $this->careerService->checkEligibility($this->agent1, $this->level2);
        $this->assertTrue($eval['has_rule']);
        $this->assertArrayHasKey('agent_status', $eval['criteria']);
        $this->assertArrayHasKey('achieved', $eval['criteria']['agent_status']);
        $this->assertArrayHasKey('met', $eval['criteria']['agent_status']);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.career.agent-performance', $this->agent1));

        $response->assertStatus(200);
        $response->assertSee('Eligibility Evaluation');
        $response->assertSee('Minimum Personal Deposit Business');
    }

    /**
     * Test Super Admin can create, manage, and toggle promotion rules across ranks.
     */
    public function test_super_admin_can_create_and_manage_promotion_rules(): void
    {
        $indexResponse = $this->actingAs($this->superAdmin)->get(route('superadmin.promotion-rules.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('modalAddPromotionRule');
        $indexResponse->assertSee('Add Promotion Rule');

        $agentRanksResponse = $this->actingAs($this->superAdmin)->get(route('superadmin.agent-ranks.index'));
        $agentRanksResponse->assertOk();
        $agentRanksResponse->assertSee(route('superadmin.promotion-rules.index'));

        $createResponse = $this->actingAs($this->superAdmin)->get(route('superadmin.promotion-rules.create'));
        $createResponse->assertOk();
        $createResponse->assertSee('Add Promotion Rule');

        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.promotion-rules.store'), [
            'name' => 'Field Officer to Development Officer Advancement',
            'from_rank_id' => $this->level2->id,
            'to_rank_id' => $this->level3->id,
            'min_individual_deposit' => 25000.00,
            'min_team_deposit' => 500000.00,
            'min_qualifying_direct_facilitators' => 5,
            'qualifying_rank_id' => $this->level2->id,
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'qualifying_deposit_statuses' => [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED],
            'required_agent_status' => 'active',
            'requires_approval' => 1,
            'authorized_approval_role' => PromotionRule::ROLE_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
            'description' => 'Advancement to Development Officer requires team business and qualifying field officers.',
        ]);

        $response->assertRedirect(route('superadmin.promotion-rules.index'));
        $this->assertDatabaseHas('promotion_rules', [
            'name' => 'Field Officer to Development Officer Advancement',
            'from_rank_id' => $this->level2->id,
            'to_rank_id' => $this->level3->id,
            'min_team_deposit' => 500000.00,
            'min_business_amount' => 500000.00,
            'min_qualifying_direct_facilitators' => 5,
            'min_downline_agents' => 5,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        $rule = PromotionRule::where('name', 'Field Officer to Development Officer Advancement')->first();

        // Verify index page displays cash text format
        $indexAfterCreate = $this->actingAs($this->superAdmin)->get(route('superadmin.promotion-rules.index'));
        $indexAfterCreate->assertOk();
        $indexAfterCreate->assertSee('25 Thousand');
        $indexAfterCreate->assertSee('5 Lakh');

        // Toggle status to inactive
        $this->actingAs($this->superAdmin)->post(route('superadmin.promotion-rules.toggle-status', $rule));
        $rule->refresh();
        $this->assertEquals(PromotionRule::STATUS_INACTIVE, $rule->status);

        // Toggle status back to active
        $this->actingAs($this->superAdmin)->post(route('superadmin.promotion-rules.toggle-status', $rule));
        $rule->refresh();
        $this->assertEquals(PromotionRule::STATUS_ACTIVE, $rule->status);
    }

    /**
     * Test promotion rule creation enforces sequential next rank and rejects non-next ranks.
     */
    public function test_promotion_rule_enforces_sequential_next_rank(): void
    {
        // Attempting to skip rank (level1 -> level3, skipping level2) should fail validation
        $invalidResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.promotion-rules.store'), [
            'name' => 'Invalid Skip Rank Rule',
            'from_rank_id' => $this->level1->id,
            'to_rank_id' => $this->level3->id,
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'required_agent_status' => 'active',
            'requires_approval' => 1,
            'authorized_approval_role' => PromotionRule::ROLE_SUPER_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        $invalidResponse->assertSessionHasErrors(['to_rank_id']);

        // Attempting to create rule from max level (where no nextLevel exists) should fail validation
        $maxRankResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.promotion-rules.store'), [
            'name' => 'Max Level Rule',
            'from_rank_id' => $this->level3->id, // level3 is highest in this test suite
            'to_rank_id' => $this->level2->id,
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'required_agent_status' => 'active',
            'requires_approval' => 1,
            'authorized_approval_role' => PromotionRule::ROLE_SUPER_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        $maxRankResponse->assertSessionHasErrors(['from_rank_id']);

        // Valid sequential progression (level1 -> level2) succeeds
        $validResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.promotion-rules.store'), [
            'name' => 'Valid Sequential Progression',
            'from_rank_id' => $this->level1->id,
            'to_rank_id' => $this->level2->id,
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'required_agent_status' => 'active',
            'requires_approval' => 1,
            'authorized_approval_role' => PromotionRule::ROLE_SUPER_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        $validResponse->assertRedirect(route('superadmin.promotion-rules.index'));
        $this->assertDatabaseHas('promotion_rules', [
            'name' => 'Valid Sequential Progression',
            'from_rank_id' => $this->level1->id,
            'to_rank_id' => $this->level2->id,
        ]);
    }

    /**
     * Test Agent Progress page displays current rank, next rank, targets, achieved amounts, and eligibility status.
     */
    public function test_agent_progress_page_renders_with_targets_and_eligibility(): void
    {
        // Configure dynamic rule from level1 to level2
        PromotionRule::create([
            'name' => 'Standard Facilitator Progression',
            'from_rank_id' => $this->level1->id,
            'to_rank_id' => $this->level2->id,
            'min_individual_deposit' => 10000.00,
            'min_team_deposit' => 30000.00,
            'min_qualifying_direct_facilitators' => 2,
            'qualifying_rank_id' => $this->level1->id,
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'qualifying_deposit_statuses' => [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED],
            'required_agent_status' => 'active',
            'requires_approval' => true,
            'authorized_approval_role' => PromotionRule::ROLE_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        // Add 2 active direct downline agents to agent1
        for ($d = 1; $d <= 2; $d++) {
            $code = 'DL'.$d.'_'.rand(1000, 9999);
            Agent::create([
                'member_code' => $code,
                'agent_id' => $code,
                'name' => "Downline Agent {$d}",
                'email' => "downline_p7_{$d}_".uniqid().'@example.com',
                'phone' => "987654330{$d}",
                'branch_id' => $this->branch1->id,
                'rank_id' => $this->level1->id,
                'parent_agent_id' => $this->agent1->id,
                'joining_date' => now()->toDateString(),
                'status' => 'active',
            ]);
        }

        $this->seedBusinessDataForAgent1();

        // Agent visits their career progress page
        $response = $this->actingAs($this->agentUser1)->get(route('agent.progress'));
        $response->assertStatus(200);
        $response->assertSee('My Career Progress');
        $response->assertSee($this->agent1->name);
        $response->assertSee($this->level1->name);
        $response->assertSee($this->level2->name);
        $response->assertSee('ELIGIBLE FOR PROMOTION');
        $response->assertSee('Request Promotion');
    }

    /**
     * Test eligible agent can submit promotion request and system prevents duplicate requests.
     */
    public function test_eligible_agent_can_submit_promotion_request_and_prevents_duplicate(): void
    {
        $this->seedBusinessDataForAgent1();

        // Agent submits promotion request via portal
        $response = $this->actingAs($this->agentUser1)->post(route('agent.promotion-request.store'), [
            'remarks' => 'Completed all field business goals successfully.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('promotion_requests', [
            'agent_id' => $this->agent1->id,
            'proposed_level_id' => $this->level2->id,
            'status' => PromotionRequest::STATUS_PENDING_REVIEW,
        ]);

        // Attempting to submit another request while one is pending is prevented
        $duplicateResponse = $this->actingAs($this->agentUser1)->post(route('agent.promotion-request.store'), [
            'remarks' => 'Attempting duplicate submission.',
        ]);

        $duplicateResponse->assertSessionHas('error');
        $this->assertEquals(1, PromotionRequest::where('agent_id', $this->agent1->id)->count());
    }

    /**
     * Test ineligible agent cannot submit promotion request.
     */
    public function test_ineligible_agent_cannot_submit_promotion_request(): void
    {
        // Agent 2 has not mobilized sufficient business
        $response = $this->actingAs($this->agentUser2)->post(route('agent.promotion-request.store'), [
            'remarks' => 'Premature request without meeting targets.',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('promotion_requests', [
            'agent_id' => $this->agent2->id,
        ]);
    }

    /**
     * Test sequential promotion is strictly enforced and ranks cannot be skipped.
     */
    public function test_sequential_promotion_strictly_enforced(): void
    {
        $this->seedBusinessDataForAgent1();

        // Agent 1 is at level1. Attempting to skip level2 and jump directly to level3
        $this->expectException(ValidationException::class);
        $this->careerService->submitPromotionRequest(
            agent: $this->agent1,
            proposedLevel: $this->level3, // Skipping level2
            requester: $this->superAdmin
        );
    }

    /**
     * Test Branch Admin can review, approve, update rank, and create permanent career history.
     */
    public function test_branch_admin_approves_request_updating_rank_and_history(): void
    {
        $this->seedBusinessDataForAgent1();

        $request = $this->careerService->submitPromotionRequest(
            agent: $this->agent1,
            proposedLevel: $this->level2,
            requester: $this->agentUser1
        );

        // Branch Admin 1 approves promotion for their branch's agent
        $response = $this->actingAs($this->branchAdmin1)->post(route('admin.career.requests.approve', $request), [
            'reason' => 'Outstanding field mobilization and member recruitment.',
        ]);

        $response->assertRedirect(route('admin.career.requests.show', $request));
        $response->assertSessionHas('success');

        $request->refresh();
        $this->assertEquals(PromotionRequest::STATUS_PROMOTED, $request->status);
        $this->assertEquals($this->branchAdmin1->id, $request->reviewed_by);

        // Verify agent rank updated
        $this->agent1->refresh();
        $this->assertEquals($this->level2->id, $this->agent1->rank_id);

        // Verify permanent CareerHistory record
        $this->assertDatabaseHas('career_histories', [
            'agent_id' => $this->agent1->id,
            'previous_level_id' => $this->level1->id,
            'new_level_id' => $this->level2->id,
            'approved_by' => $this->branchAdmin1->id,
            'action' => CareerHistory::ACTION_PROMOTION,
        ]);
    }

    /**
     * Test Branch Admin can reject promotion request with mandatory reason.
     */
    public function test_branch_admin_can_reject_request_with_mandatory_reason(): void
    {
        $request = $this->careerService->submitPromotionRequest(
            agent: $this->agent1,
            proposedLevel: $this->level2,
            requester: $this->superAdmin
        );

        $response = $this->actingAs($this->branchAdmin1)->post(route('admin.career.requests.reject', $request), [
            'rejection_reason' => 'Substandard recurring collection consistency.',
        ]);

        $response->assertRedirect(route('admin.career.requests.show', $request));
        $request->refresh();
        $this->assertEquals(PromotionRequest::STATUS_REJECTED, $request->status);
        $this->assertEquals('Substandard recurring collection consistency.', $request->rejection_reason);

        // Agent rank remains unchanged
        $this->agent1->refresh();
        $this->assertEquals($this->level1->id, $this->agent1->rank_id);
    }

    /**
     * Test cross-branch access protection: Branch Admin 2 cannot approve or reject Branch Admin 1 requests.
     */
    public function test_cross_branch_admin_cannot_approve_or_reject_other_branch_requests(): void
    {
        $request = $this->careerService->submitPromotionRequest(
            agent: $this->agent1, // Belongs to branch 1
            proposedLevel: $this->level2,
            requester: $this->superAdmin
        );

        // Branch Admin 2 (Branch 2) attempts to approve Branch 1 request -> 403 Forbidden
        $this->actingAs($this->branchAdmin2)
            ->post(route('admin.career.requests.approve', $request), ['reason' => 'Unauthorized'])
            ->assertStatus(403);

        // Branch Admin 2 (Branch 2) attempts to reject Branch 1 request -> 403 Forbidden
        $this->actingAs($this->branchAdmin2)
            ->post(route('admin.career.requests.reject', $request), ['rejection_reason' => 'Unauthorized'])
            ->assertStatus(403);
    }

    /**
     * Test Agent can view their dedicated Promotion History page.
     */
    public function test_agent_can_view_promotion_history_page(): void
    {
        $response = $this->actingAs($this->agentUser1)->get(route('agent.promotion-history'));
        $response->assertStatus(200);
        $response->assertSee('My Promotion History');
        $response->assertSee($this->agent1->name);
    }
}
