<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Branch;
use App\Models\CareerLevel;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\PromotionRule;
use App\Models\Role;
use App\Models\User;
use App\Services\CareerPerformanceService;
use App\Services\PromotionEligibilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AgentTargetManagementAndPromotionRulesTest extends TestCase
{
    use DatabaseTransactions;

    protected CareerPerformanceService $careerService;

    protected PromotionEligibilityService $eligibilityService;

    protected User $superAdmin;

    protected User $branchAdmin1;

    protected User $branchAdmin2;

    protected Branch $branch1;

    protected Branch $branch2;

    protected DepositScheme $fdScheme;

    protected DepositScheme $rdScheme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->careerService = app(CareerPerformanceService::class);
        $this->eligibilityService = app(PromotionEligibilityService::class);

        // Roles
        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin', 'status' => 'active']);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);

        // Branches
        $this->branch1 = Branch::firstOrCreate(
            ['code' => 'BR_TEST_TGT_1'],
            ['name' => 'Branch Target 1', 'is_active' => true]
        );
        $this->branch2 = Branch::firstOrCreate(
            ['code' => 'BR_TEST_TGT_2'],
            ['name' => 'Branch Target 2', 'is_active' => true]
        );

        // Super Admin
        $this->superAdmin = User::create([
            'name' => 'Super Admin Target Test',
            'email' => 'satarget_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        // Branch Admins
        $this->branchAdmin1 = User::create([
            'name' => 'Branch Admin 1',
            'email' => 'badmin1_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch1->id,
        ]);
        $this->branchAdmin1->roles()->attach($adminRole->id);

        $this->branchAdmin2 = User::create([
            'name' => 'Branch Admin 2',
            'email' => 'badmin2_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch2->id,
        ]);
        $this->branchAdmin2->roles()->attach($adminRole->id);

        // Schemes
        $this->fdScheme = DepositScheme::create([
            'name' => 'Target Test FD Scheme '.uniqid(),
            'code' => 'TTFD_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => '1000.00',
            'max_amount' => '50000000.00',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => '8.00',
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->rdScheme = DepositScheme::create([
            'name' => 'Target Test RD Scheme '.uniqid(),
            'code' => 'TTRD_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_RD,
            'min_amount' => '500.00',
            'max_amount' => '50000000.00',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => '7.50',
            'collection_frequency' => DepositScheme::FREQ_MONTHLY,
            'status' => DepositScheme::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);
    }

    protected function createAgentWithUser(string $name, CareerLevel $rank, Branch $branch, ?Agent $parent = null): Agent
    {
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);

        $user = User::create([
            'name' => $name,
            'email' => strtolower(str_replace(' ', '', $name)).'_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $branch->id,
        ]);
        $user->roles()->attach($agentRole->id);

        return Agent::create([
            'user_id' => $user->id,
            'member_code' => 'AG_'.rand(10000, 99999),
            'agent_id' => 'AG_'.rand(10000, 99999),
            'name' => $name,
            'email' => $user->email,
            'phone' => '98'.rand(10000000, 99999999),
            'branch_id' => $branch->id,
            'rank_id' => $rank->id,
            'parent_agent_id' => $parent?->id,
            'joining_date' => now()->subMonths(6)->toDateString(),
            'status' => 'active',
        ]);
    }

    protected function createDepositForAgent(Agent $agent, DepositScheme $scheme, float $amount): Deposit
    {
        $customer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Cust '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '91'.rand(10000000, 99999999),
            'date_of_birth' => '1990-01-01',
            'address' => 'Test Address',
            'branch_id' => $agent->branch_id,
            'agent_id' => $agent->id,
            'created_by' => $this->superAdmin->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        return Deposit::create([
            'account_number' => 'DEP_'.uniqid(),
            'customer_id' => $customer->id,
            'agent_id' => $agent->id,
            'branch_id' => $agent->branch_id,
            'scheme_id' => $scheme->id,
            'amount' => $amount,
            'interest_rate' => $scheme->interest_rate,
            'tenure_months' => 12,
            'start_date' => now()->subDays(10)->toDateString(),
            'maturity_date' => now()->addMonths(12)->toDateString(),
            'maturity_amount' => $amount * 1.08,
            'status' => Deposit::STATUS_ACTIVE,
        ]);
    }

    public function test_all_twelve_ranks_have_configured_targets_and_commission_rates(): void
    {
        $expectedRanks = [
            1 => ['code' => 'FACILITATOR', 'name' => 'Facilitator', 'target' => 300000.00, 'commission' => 2.00],
            2 => ['code' => 'FIELD_OFFICER', 'name' => 'Field Officer', 'target' => 1000000.00, 'commission' => 3.00],
            3 => ['code' => 'DEVELOPMENT_OFFICER', 'name' => 'Development Officer', 'target' => 2500000.00, 'commission' => 4.00],
            4 => ['code' => 'DEPUTY_SALES_MANAGER', 'name' => 'Deputy Sales Manager', 'target' => 5000000.00, 'commission' => 5.00],
            5 => ['code' => 'BUSINESS_DEVELOPMENT_MANAGER', 'name' => 'Business Development Manager', 'target' => 10000000.00, 'commission' => 6.00],
            6 => ['code' => 'AREA_DEVELOPMENT_MANAGER', 'name' => 'Area Development Manager', 'target' => 15000000.00, 'commission' => 7.00],
            7 => ['code' => 'MARKETING_MANAGER', 'name' => 'Marketing Manager', 'target' => 20000000.00, 'commission' => 8.00],
            8 => ['code' => 'SENIOR_MARKETING_MANAGER', 'name' => 'Senior Marketing Manager', 'target' => 30000000.00, 'commission' => 9.00],
            9 => ['code' => 'DIVISIONAL_MANAGER', 'name' => 'Divisional Manager', 'target' => 50000000.00, 'commission' => 10.00],
            10 => ['code' => 'REGIONAL_DEVELOPMENT_MANAGER', 'name' => 'Regional Development Manager', 'target' => 75000000.00, 'commission' => 11.00],
            11 => ['code' => 'ZONAL_DEVELOPMENT_MANAGER', 'name' => 'Zonal Development Manager', 'target' => 100000000.00, 'commission' => 12.00],
            12 => ['code' => 'DEPUTY_DIRECTOR', 'name' => 'Deputy Director', 'target' => 150000000.00, 'commission' => 13.00],
        ];

        foreach ($expectedRanks as $level => $expected) {
            $rank = CareerLevel::where('level', $level)->first();
            $this->assertNotNull($rank, "Rank Level {$level} ({$expected['name']}) must exist in ranks table.");
            $this->assertEquals((float) $expected['target'], (float) $rank->target_deposit, "Rank Level {$level} target deposit mismatch.");
            $this->assertEquals((float) $expected['commission'], (float) $rank->commission_percentage, "Rank Level {$level} commission rate mismatch.");
            $this->assertEquals($expected['code'], $rank->code, "Rank Level {$level} code mismatch.");
        }
    }

    public function test_all_eleven_promotion_rules_configured_with_rd_and_fd_composition(): void
    {
        $expectedRules = [
            1 => ['dest_level' => 2, 'target' => 300000.00, 'required_agent_count' => 3],
            2 => ['dest_level' => 3, 'target' => 1000000.00, 'required_agent_count' => 3],
            3 => ['dest_level' => 4, 'target' => 2500000.00, 'required_agent_count' => 2],
            4 => ['dest_level' => 5, 'target' => 5000000.00, 'required_agent_count' => 2],
            5 => ['dest_level' => 6, 'target' => 10000000.00, 'required_agent_count' => 3],
            6 => ['dest_level' => 7, 'target' => 15000000.00, 'required_agent_count' => 2],
            7 => ['dest_level' => 8, 'target' => 20000000.00, 'required_agent_count' => 3],
            8 => ['dest_level' => 9, 'target' => 30000000.00, 'required_agent_count' => 2],
            9 => ['dest_level' => 10, 'target' => 50000000.00, 'required_agent_count' => 2], // 1 DM + 1 SMM
            10 => ['dest_level' => 11, 'target' => 75000000.00, 'required_agent_count' => 3],
            11 => ['dest_level' => 12, 'target' => 100000000.00, 'required_agent_count' => 3],
        ];

        foreach ($expectedRules as $srcLevel => $expected) {
            $src = CareerLevel::where('level', $srcLevel)->firstOrFail();
            $dest = CareerLevel::where('level', $expected['dest_level'])->firstOrFail();

            $rule = PromotionRule::where('from_rank_id', $src->id)
                ->where('to_rank_id', $dest->id)
                ->first();

            $this->assertNotNull($rule, "Promotion rule from Level {$srcLevel} to Level {$expected['dest_level']} must exist.");
            $this->assertEquals(10.00, (float) $rule->rd_composition_percentage);
            $this->assertEquals(90.00, (float) $rule->fd_composition_percentage);
            $this->assertEquals((float) $expected['target'], (float) $rule->target_deposit_amount);

            $ruleTiers = $rule->qualifying_agent_tiers ?? [];
            $this->assertNotEmpty($ruleTiers, "Rule Level {$srcLevel}->{$expected['dest_level']} must have qualifying tiers.");

            $totalTierAgents = array_sum(array_column($ruleTiers, 'count'));
            $this->assertEquals($expected['required_agent_count'], $totalTierAgents);
        }
    }

    public function test_deposit_composition_fails_when_rd_target_is_not_met(): void
    {
        $faRank = CareerLevel::where('level', 1)->firstOrFail();
        $agent = $this->createAgentWithUser('FA Zero RD Agent', $faRank, $this->branch1);

        // Deposit ₹3,00,000 completely in FD (0 RD)
        $this->createDepositForAgent($agent, $this->fdScheme, 300000.00);

        $achievement = $this->careerService->calculateTargetAchievement($agent);

        $this->assertEquals(300000.00, $achievement['target_amount']);
        // FD capped at 90% = ₹2,70,000
        $this->assertEquals(270000.00, $achievement['eligible_deposits']);
        // Remaining amount is ₹30,000
        $this->assertEquals(30000.00, $achievement['remaining_amount']);
        $this->assertEquals(90.0, $achievement['progress_percentage']);
        $this->assertFalse($achievement['rd_progress']['is_met']);
        $this->assertEquals(30000.00, $achievement['rd_progress']['required']);
        $this->assertEquals(0.00, $achievement['rd_progress']['actual']);
        $this->assertFalse($achievement['is_promotable']);

        // Check unmet conditions contains RD shortfall
        $hasRdUnmet = false;
        foreach ($achievement['unmet_conditions'] as $unmet) {
            if (str_contains($unmet, 'Recurring Deposit (RD) requirement') || str_contains($unmet, 'RD')) {
                $hasRdUnmet = true;
                break;
            }
        }
        $this->assertTrue($hasRdUnmet, 'Must include unmet condition for RD minimum requirement shortfall.');
    }

    public function test_deposit_composition_succeeds_with_exact_ten_percent_rd_and_ninety_percent_fd(): void
    {
        $faRank = CareerLevel::where('level', 1)->firstOrFail();
        $agent = $this->createAgentWithUser('FA Balanced Agent', $faRank, $this->branch1);

        // Deposit ₹30,000 in RD (10%) and ₹2,70,000 in FD (90%)
        $this->createDepositForAgent($agent, $this->rdScheme, 30000.00);
        $this->createDepositForAgent($agent, $this->fdScheme, 270000.00);

        $achievement = $this->careerService->calculateTargetAchievement($agent);

        $this->assertEquals(300000.00, $achievement['target_amount']);
        $this->assertEquals(300000.00, $achievement['eligible_deposits']);
        $this->assertEquals(0.00, $achievement['remaining_amount']);
        $this->assertEquals(100.0, $achievement['progress_percentage']);
        $this->assertTrue($achievement['rd_progress']['is_met']);
    }

    public function test_deposit_composition_succeeds_with_higher_rd_percentage(): void
    {
        $faRank = CareerLevel::where('level', 1)->firstOrFail();
        $agent = $this->createAgentWithUser('FA High RD Agent', $faRank, $this->branch1);

        // Deposit ₹60,000 in RD (20%) and ₹2,40,000 in FD (80%)
        $this->createDepositForAgent($agent, $this->rdScheme, 60000.00);
        $this->createDepositForAgent($agent, $this->fdScheme, 240000.00);

        $achievement = $this->careerService->calculateTargetAchievement($agent);

        $this->assertEquals(300000.00, $achievement['target_amount']);
        $this->assertEquals(300000.00, $achievement['eligible_deposits']);
        $this->assertEquals(0.00, $achievement['remaining_amount']);
        $this->assertEquals(100.0, $achievement['progress_percentage']);
        $this->assertTrue($achievement['rd_progress']['is_met']);
    }

    public function test_multi_tier_downline_qualification_deduplicates_agents_correctly(): void
    {
        // RDM transition requires 1 DM and 1 SMM direct agents
        $dmRank = CareerLevel::where('level', 9)->firstOrFail();
        $smmRank = CareerLevel::where('level', 8)->firstOrFail();

        $candidate = $this->createAgentWithUser('DM Candidate', $dmRank, $this->branch1);

        // Meet target deposit (₹5 Cr with 10% RD)
        $this->createDepositForAgent($candidate, $this->rdScheme, 5000000.00);
        $this->createDepositForAgent($candidate, $this->fdScheme, 45000000.00);

        // Step 1: Add only 1 direct agent who is a DM
        $downline1 = $this->createAgentWithUser('Direct DM Agent', $dmRank, $this->branch1, $candidate);

        $eligibility1 = $this->careerService->checkEligibility($candidate);
        // Should NOT be eligible because 1 DM cannot count as BOTH 1 DM and 1 SMM
        $this->assertFalse($eligibility1['is_eligible'], 'Single downline must not be counted for both DM and SMM tiers.');
        $this->assertArrayHasKey('qualifying_facilitators', $eligibility1['criteria']);
        $this->assertFalse($eligibility1['criteria']['qualifying_facilitators']['met']);

        // Step 2: Add a second distinct direct agent who is an SMM
        $downline2 = $this->createAgentWithUser('Direct SMM Agent', $smmRank, $this->branch1, $candidate);

        $eligibility2 = $this->careerService->checkEligibility($candidate);
        $this->assertTrue($eligibility2['is_eligible'], 'With 1 DM and 1 SMM distinct direct agents, candidate must qualify.');
        $this->assertTrue($eligibility2['criteria']['qualifying_facilitators']['met']);
    }

    public function test_target_achievement_calculation_for_top_level_rank(): void
    {
        $ddRank = CareerLevel::where('level', 12)->firstOrFail();
        $agent = $this->createAgentWithUser('Top Rank DD Agent', $ddRank, $this->branch1);

        $achievement = $this->careerService->calculateTargetAchievement($agent);

        $this->assertFalse($achievement['has_target']);
        $this->assertFalse($achievement['is_promotable']);
        $this->assertEquals(0, $achievement['target_amount']);
        $this->assertEquals(0, $achievement['remaining_amount']);
    }

    public function test_admin_and_agent_views_display_target_achievement_and_unmet_conditions(): void
    {
        $faRank = CareerLevel::where('level', 1)->firstOrFail();
        $agent = $this->createAgentWithUser('FA View Test Agent', $faRank, $this->branch1);

        // Partial deposit: ₹1,00,000 FD
        $this->createDepositForAgent($agent, $this->fdScheme, 100000.00);

        // 1. SuperAdmin viewing admin.agents.show
        $response = $this->actingAs($this->superAdmin)->get(route('admin.agents.show', $agent));
        $response->assertStatus(200);
        $response->assertSee('Target Management &amp; Promotion Status', false);
        $response->assertSee('300,000.00'); // Target
        $response->assertSee('RD COMPOSITION (MIN 10%)');
        $response->assertSee('FD COMPOSITION (MAX 90% CAPPED)');

        // 2. SuperAdmin viewing admin.career.agent-performance
        $responsePerf = $this->actingAs($this->superAdmin)->get(route('admin.career.agent-performance', $agent));
        $responsePerf->assertStatus(200);
        $responsePerf->assertSee('Target Management &amp; Deposit Achievement', false);
        $responsePerf->assertSee('300,000.00');

        // 3. Agent viewing agent.performance
        $responseAgent = $this->actingAs($agent->user)->get(route('agent.performance'));
        $responseAgent->assertStatus(200);
        $responseAgent->assertSee('Target Management:', false);
        $responseAgent->assertSee('300,000.00');
    }

    public function test_cross_branch_security_enforcement_for_target_and_career_data(): void
    {
        $faRank = CareerLevel::where('level', 1)->firstOrFail();
        $agentBranch2 = $this->createAgentWithUser('Agent Branch 2', $faRank, $this->branch2);

        // Branch Admin 1 (from Branch 1) attempts to view Agent in Branch 2
        $response = $this->actingAs($this->branchAdmin1)->get(route('admin.career.agent-performance', $agentBranch2));
        $response->assertStatus(403);
    }
}
