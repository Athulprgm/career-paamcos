<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRankTarget;
use App\Models\CareerLevel;
use App\Models\FinancialYear;
use App\Models\PromotionRule;
use App\Models\Role;
use App\Models\User;
use App\Services\AgentTargetCalculationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PromotionRuleDuplicateAndTargetSyncTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected CareerLevel $rank1;

    protected CareerLevel $rank2;

    protected CareerLevel $rank3;

    protected FinancialYear $fy;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::create([
            'name' => 'Super Admin Test',
            'email' => 'superadmin_test_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->roles()->attach($superAdminRole->id);

        $this->rank1 = CareerLevel::where('level', 1)->first() ?? CareerLevel::create([
            'name' => 'Facilitator',
            'code' => 'FACILITATOR',
            'level' => 1,
            'sort_order' => 1,
            'minimum_deposit_amount' => 300000.00,
            'min_deposit_amount' => 300000.00,
            'status' => 'active',
        ]);

        $this->rank2 = CareerLevel::where('level', 2)->first() ?? CareerLevel::create([
            'name' => 'Field Officer',
            'code' => 'FIELD_OFFICER',
            'level' => 2,
            'sort_order' => 2,
            'minimum_deposit_amount' => 1000000.00,
            'min_deposit_amount' => 1000000.00,
            'status' => 'active',
        ]);

        $this->rank3 = CareerLevel::where('level', 3)->first() ?? CareerLevel::create([
            'name' => 'Development Officer',
            'code' => 'DEVELOPMENT_OFFICER',
            'level' => 3,
            'sort_order' => 3,
            'minimum_deposit_amount' => 2500000.00,
            'min_deposit_amount' => 2500000.00,
            'status' => 'active',
        ]);

        $this->fy = FinancialYear::current() ?? FinancialYear::create([
            'name' => 'FY 2026–27',
            'code' => 'FY2026-27',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
            'status' => 'active',
        ]);
    }

    public function test_cannot_create_duplicate_promotion_rule_for_same_rank_transition(): void
    {
        // 1. Ensure an existing rule from Rank 1 to Rank 2 exists
        $existing = PromotionRule::firstOrCreate(
            ['from_rank_id' => $this->rank1->id, 'to_rank_id' => $this->rank2->id],
            [
                'name' => 'Rule 1 to 2 Initial',
                'min_individual_deposit' => 0.00,
                'min_team_deposit' => 300000.00,
                'rd_composition_percentage' => 10.00,
                'fd_composition_percentage' => 90.00,
                'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
                'required_agent_status' => 'active',
                'requires_approval' => true,
                'authorized_approval_role' => PromotionRule::ROLE_SUPER_ADMIN,
                'status' => PromotionRule::STATUS_ACTIVE,
            ]
        );

        $this->assertNotNull($existing);

        // 2. Attempt to create another promotion rule for the exact same rank pair
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.promotion-rules.store'), [
            'name' => 'Duplicate Rule Attempt',
            'from_rank_id' => $this->rank1->id,
            'to_rank_id' => $this->rank2->id,
            'min_individual_deposit' => 0,
            'min_team_deposit' => 300000,
            'rd_composition_percentage' => 10,
            'fd_composition_percentage' => 90,
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'required_agent_status' => 'active',
            'requires_approval' => 1,
            'authorized_approval_role' => PromotionRule::ROLE_SUPER_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        $response->assertSessionHasErrors(['from_rank_id']);
        $this->assertEquals(
            1,
            PromotionRule::where('from_rank_id', $this->rank1->id)->where('to_rank_id', $this->rank2->id)->count()
        );
    }

    public function test_agent_rank_targets_are_correctly_synchronized_with_society_standards(): void
    {
        $target1 = AgentRankTarget::where('rank_id', $this->rank1->id)->where('financial_year_id', $this->fy->id)->first();
        $target2 = AgentRankTarget::where('rank_id', $this->rank2->id)->where('financial_year_id', $this->fy->id)->first();
        $target3 = AgentRankTarget::where('rank_id', $this->rank3->id)->where('financial_year_id', $this->fy->id)->first();

        $this->assertNotNull($target1);
        $this->assertNotNull($target2);
        $this->assertNotNull($target3);

        // Rank 1 Facilitator: ₹3,00,000 total target (FD: ₹2.7L, RD: ₹30k)
        $this->assertEquals(300000.00, (float) $target1->total_deposit_target);
        $this->assertEquals(270000.00, (float) $target1->fd_target_amount);
        $this->assertEquals(30000.00, (float) $target1->rd_target_amount);

        // Rank 2 Field Officer: ₹10,00,000 total target (FD: ₹9L, RD: ₹1L)
        $this->assertEquals(1000000.00, (float) $target2->total_deposit_target);
        $this->assertEquals(900000.00, (float) $target2->fd_target_amount);
        $this->assertEquals(100000.00, (float) $target2->rd_target_amount);

        // Rank 3 Development Officer: ₹25,00,000 total target (FD: ₹22.5L, RD: ₹2.5L)
        $this->assertEquals(2500000.00, (float) $target3->total_deposit_target);
        $this->assertEquals(2250000.00, (float) $target3->fd_target_amount);
        $this->assertEquals(250000.00, (float) $target3->rd_target_amount);
    }

    public function test_career_level_synchronizes_duplicate_columns_automatically(): void
    {
        $level = CareerLevel::create([
            'name' => 'Test Sync Rank '.uniqid(),
            'code' => 'TSR_'.uniqid(),
            'minimum_deposit_amount' => 450000.00,
            'sort_order' => 99,
            'status' => 'active',
        ]);

        $this->assertEquals(450000.00, (float) $level->min_deposit_amount);
        $this->assertEquals(99, (int) $level->level);

        $level->update(['min_deposit_amount' => 550000.00]);
        $level->refresh();

        $this->assertEquals(550000.00, (float) $level->minimum_deposit_amount);
    }

    public function test_target_calculation_service_uses_standard_rank_target_defaults(): void
    {
        $service = app(AgentTargetCalculationService::class);
        $agent = Agent::create([
            'name' => 'Target Test Agent '.uniqid(),
            'email' => 'agent_target_'.uniqid().'@example.com',
            'phone' => '98'.rand(10000000, 99999999),
            'agent_id' => 'AG_T_'.uniqid(),
            'member_code' => 'MEM_T_'.uniqid(),
            'rank_id' => $this->rank1->id,
            'joining_date' => '2026-05-01',
            'status' => Agent::STATUS_ACTIVE,
        ]);

        $target = $service->getAgentTarget($agent, $this->fy);

        $this->assertEquals(300000.00, (float) $target['total_deposit_target']);
        $this->assertEquals(270000.00, (float) $target['fd_target_amount']);
        $this->assertEquals(30000.00, (float) $target['rd_target_amount']);
    }
}
