<?php

namespace Tests\Feature;

use App\Models\CareerLevel;
use App\Models\Deposit;
use App\Models\FinancialYear;
use App\Models\PromotionRule;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PromotionRuleCreateAndViewWorkflowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected CareerLevel $rank1;

    protected CareerLevel $rank2;

    protected CareerLevel $rank3;

    protected FinancialYear $financialYear;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::create([
            'name' => 'Super Admin Workflow Test',
            'email' => 'workflow_admin_'.uniqid().'@example.com',
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
            'code' => 'DEV_OFFICER',
            'level' => 3,
            'sort_order' => 3,
            'minimum_deposit_amount' => 2500000.00,
            'min_deposit_amount' => 2500000.00,
            'status' => 'active',
        ]);

        $this->financialYear = FinancialYear::create([
            'name' => 'FY '.uniqid(),
            'code' => 'FY-'.rand(2040, 2099),
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_current' => false,
            'status' => 'active',
        ]);
    }

    public function test_superadmin_can_access_promotion_rules_index_and_see_add_action(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.promotion-rules.index'));

        $response->assertStatus(200);
        $response->assertSee(route('superadmin.promotion-rules.create'));
        $response->assertSee('Add Promotion Rule');
    }

    public function test_superadmin_can_view_create_page_with_complete_configuration_sections(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.promotion-rules.create'));

        $response->assertStatus(200);
        $response->assertSee('Add Promotion Rule');
        $response->assertSee('Career Operations');
        $response->assertSee(route('superadmin.promotion-rules.store'));
        $response->assertSee('name="name"', false);
        $response->assertSee('id="from_rank_id"', false);
        $response->assertSee('id="to_rank_id"', false);
        $response->assertSee('id="financial_year_id"', false);
        $response->assertSee('id="min_team_deposit"', false);
        $response->assertSee('id="rd_composition_percentage"', false);
        $response->assertSee('id="fd_composition_percentage"', false);
        $response->assertSee('name="qualifying_deposit_statuses[]"', false);
        $response->assertSee('id="min_individual_deposit"', false);
        $response->assertSee('id="add-tier-btn"', false);
        $response->assertSee('id="calculation_period"', false);
        $response->assertSee('id="required_agent_status"', false);
        $response->assertSee('id="apply_tenure_targets"', false);
        $response->assertSee('id="requires_approval"', false);
        $response->assertSee('id="authorized_approval_role"', false);
        $response->assertSee('id="status"', false);
        $response->assertSee('id="effective_from"', false);
        $response->assertSee('id="effective_to"', false);
        $response->assertSee(route('superadmin.promotion-rules.index'));
    }

    public function test_validation_prevents_invalid_skip_ranks_and_unbalanced_percentages(): void
    {
        // 1. Non-sequential rank progression
        $skipRankResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.promotion-rules.store'), [
            'name' => 'Invalid Skip Progression',
            'from_rank_id' => $this->rank1->id,
            'to_rank_id' => $this->rank3->id,
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'required_agent_status' => 'active',
            'authorized_approval_role' => PromotionRule::ROLE_SUPER_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);
        $skipRankResponse->assertSessionHasErrors('to_rank_id');

        // 2. Unbalanced RD and FD composition percentages
        $splitResponse = $this->actingAs($this->superAdmin)->post(route('superadmin.promotion-rules.store'), [
            'name' => 'Invalid Composition Rule',
            'from_rank_id' => $this->rank1->id,
            'to_rank_id' => $this->rank2->id,
            'rd_composition_percentage' => 30.00,
            'fd_composition_percentage' => 50.00, // Total 80% != 100%
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'required_agent_status' => 'active',
            'authorized_approval_role' => PromotionRule::ROLE_SUPER_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);
        $splitResponse->assertSessionHasErrors(['rd_composition_percentage', 'fd_composition_percentage']);
    }

    public function test_complete_create_view_edit_and_update_workflow(): void
    {
        $ruleName = 'Unique Promotion Pathway '.uniqid();

        // 1. CREATE (POST to store)
        $createPayload = [
            'name' => $ruleName,
            'from_rank_id' => $this->rank1->id,
            'to_rank_id' => $this->rank2->id,
            'financial_year_id' => $this->financialYear->id,
            'min_team_deposit' => 750000.00,
            'min_individual_deposit' => 50000.00,
            'rd_composition_percentage' => 20.00,
            'fd_composition_percentage' => 80.00,
            'apply_tenure_targets' => 1,
            'tenure_target_percentages' => [
                'fd' => ['1' => 25.0, '2' => 35.0, '3' => 45.0, '5' => 85.0, '6' => 85.0],
                'rd' => ['1' => 25.0, '2' => 35.0, '3' => 45.0, '5' => 85.0, '6' => 85.0, '7' => 95.0],
                'dd' => ['1' => 20.0, '2' => 40.0, '3' => 50.0],
            ],
            'qualifying_agent_tiers' => [
                ['count' => 3, 'rank_id' => $this->rank1->id],
            ],
            'calculation_period' => PromotionRule::PERIOD_FINANCIAL_YEAR,
            'qualifying_deposit_statuses' => [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED],
            'required_agent_status' => 'active',
            'requires_approval' => 1,
            'authorized_approval_role' => PromotionRule::ROLE_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
            'effective_from' => now()->toDateString(),
            'description' => 'Comprehensive executive workflow test rule.',
        ];

        $storeResponse = $this->actingAs($this->superAdmin)->post(
            route('superadmin.promotion-rules.store'),
            $createPayload
        );

        $storeResponse->assertRedirect(route('superadmin.promotion-rules.index'));
        $storeResponse->assertSessionHas('success');

        // 2. VERIFY RECORD IN DATABASE
        $this->assertDatabaseHas('promotion_rules', [
            'name' => $ruleName,
            'from_rank_id' => $this->rank1->id,
            'to_rank_id' => $this->rank2->id,
            'financial_year_id' => $this->financialYear->id,
            'min_team_deposit' => 750000.00,
            'min_individual_deposit' => 50000.00,
            'rd_composition_percentage' => 20.00,
            'fd_composition_percentage' => 80.00,
            'apply_tenure_targets' => true,
            'requires_approval' => true,
            'authorized_approval_role' => PromotionRule::ROLE_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        $createdRule = PromotionRule::where('name', $ruleName)->firstOrFail();

        // 3. VIEW / DETAILS PAGE (GET show)
        $showResponse = $this->actingAs($this->superAdmin)->get(route('superadmin.promotion-rules.show', $createdRule));

        $showResponse->assertStatus(200);
        $showResponse->assertSee($ruleName);
        $showResponse->assertSee($this->rank1->name);
        $showResponse->assertSee($this->rank2->name);
        $showResponse->assertSee('7,50,000');
        $showResponse->assertSee('50,000');
        $showResponse->assertSee('20% RD');
        $showResponse->assertSee('80% FD');
        $showResponse->assertSee('Multipliers Active');
        $showResponse->assertSee('Fixed Deposit (FD) Duration Schedule');
        $showResponse->assertSee('Recurring Deposit (RD) Duration Schedule');
        $showResponse->assertSee('Manual Review');
        $showResponse->assertSee('Branch / Super Admin');
        $showResponse->assertSee(route('superadmin.promotion-rules.edit', $createdRule));
        $showResponse->assertSee(route('superadmin.promotion-rules.index'));

        // 4. EDIT PAGE (GET edit)
        $editResponse = $this->actingAs($this->superAdmin)->get(route('superadmin.promotion-rules.edit', $createdRule));

        $editResponse->assertStatus(200);
        $editResponse->assertSee('Edit Promotion Rule');
        $editResponse->assertSee('value="'.$ruleName.'"', false);
        $editResponse->assertSee('value="750000.00"', false);
        $editResponse->assertSee('value="50000.00"', false);
        $editResponse->assertSee('value="20.00"', false);
        $editResponse->assertSee('value="80.00"', false);

        // 5. UPDATE (PUT to update)
        $updatedRuleName = $ruleName.' (Revised)';
        $updatePayload = array_merge($createPayload, [
            'name' => $updatedRuleName,
            'min_team_deposit' => 900000.00,
            'min_individual_deposit' => 75000.00,
            'rd_composition_percentage' => 15.00,
            'fd_composition_percentage' => 85.00,
            'description' => 'Updated executive note for testing.',
        ]);

        $updateResponse = $this->actingAs($this->superAdmin)->put(
            route('superadmin.promotion-rules.update', $createdRule),
            $updatePayload
        );

        $updateResponse->assertRedirect(route('superadmin.promotion-rules.show', $createdRule));
        $updateResponse->assertSessionHas('success');

        // 6. CONFIRM UPDATED VALUES IN VIEW AND INDEX
        $this->assertDatabaseHas('promotion_rules', [
            'id' => $createdRule->id,
            'name' => $updatedRuleName,
            'min_team_deposit' => 900000.00,
            'min_individual_deposit' => 75000.00,
            'rd_composition_percentage' => 15.00,
            'fd_composition_percentage' => 85.00,
        ]);

        // Verify updated values appear on Show View
        $updatedShowResponse = $this->actingAs($this->superAdmin)->get(route('superadmin.promotion-rules.show', $createdRule));
        $updatedShowResponse->assertStatus(200);
        $updatedShowResponse->assertSee($updatedRuleName);
        $updatedShowResponse->assertSee('9,00,000');
        $updatedShowResponse->assertSee('75,000');
        $updatedShowResponse->assertSee('15% RD');
        $updatedShowResponse->assertSee('85% FD');
        $updatedShowResponse->assertSee('Updated executive note for testing.');

        // Verify updated values appear on Index View
        $indexResponse = $this->actingAs($this->superAdmin)->get(route('superadmin.promotion-rules.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee($updatedRuleName);
        $indexResponse->assertSee('9,00,000');
    }
}
