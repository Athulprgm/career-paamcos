<?php

namespace Tests\Feature;

use App\Models\PromotionRule;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PromotionRulesRealtimeFilterTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);

        $this->superAdmin = User::create([
            'name' => 'Super Admin Realtime Test',
            'email' => 'sarealtime_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->roles()->attach($saRole->id);
    }

    public function test_superadmin_can_view_promotion_rules_page_with_realtime_filter_elements(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.promotion-rules.index'));

        $response->assertStatus(200);
        $response->assertSee('id="promotion-rules-filter-form"', false);
        $response->assertSee('id="rules-table-container"', false);
        $response->assertSee('id="filter-search"', false);
        $response->assertSee('id="filter-from-rank"', false);
        $response->assertSee('id="filter-to-rank"', false);
        $response->assertSee('id="filter-status"', false);
        $response->assertSee('id="filter-period"', false);
        $response->assertSee('id="filter-approval-role"', false);
        $response->assertSee('id="filter-clear-all"', false);
    }

    public function test_ajax_search_returns_json_with_html_and_matching_rules(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('superadmin.promotion-rules.index', [
                'search' => 'Facilitator',
                'ajax' => '1',
            ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'html',
            'total',
            'first_item',
            'last_item',
            'has_pages',
        ]);

        $json = $response->json();
        $this->assertGreaterThan(0, $json['total']);
        $this->assertStringContainsString('Facilitator', $json['html']);
    }

    public function test_ajax_filter_by_status(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('superadmin.promotion-rules.index', [
                'status' => 'active',
                'ajax' => '1',
            ]));

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(0, $response->json('total'));
        $this->assertStringContainsString('Active', $response->json('html'));
    }

    public function test_ajax_filter_by_from_and_to_ranks(): void
    {
        $rule = PromotionRule::first();
        $this->assertNotNull($rule);

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('superadmin.promotion-rules.index', [
                'from_rank_id' => $rule->from_rank_id,
                'to_rank_id' => $rule->to_rank_id,
                'ajax' => '1',
            ]));

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $response->json('total'));
        $this->assertStringContainsString($rule->name, $response->json('html'));
    }

    public function test_ajax_filter_by_period(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('superadmin.promotion-rules.index', [
                'period' => PromotionRule::PERIOD_ALL_TIME,
                'ajax' => '1',
            ]));

        $response->assertStatus(200);
        $this->assertGreaterThan(0, $response->json('total'));
        $this->assertStringContainsString('All Time', $response->json('html'));
    }

    public function test_ajax_filter_by_approval_role(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('superadmin.promotion-rules.index', [
                'approval_role' => PromotionRule::ROLE_SUPER_ADMIN,
                'ajax' => '1',
            ]));

        $response->assertStatus(200);
        $response->assertJsonStructure(['html', 'total']);
    }

    public function test_ajax_search_with_no_matches_returns_empty_state_with_reset_button(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('superadmin.promotion-rules.index', [
                'search' => 'NON_EXISTENT_PROMOTION_RULE_SEARCH_QUERY_XYZ',
                'ajax' => '1',
            ]));

        $response->assertStatus(200);
        $this->assertSame(0, $response->json('total'));
        $this->assertStringContainsString('No promotion rules found matching your filters.', $response->json('html'));
        $this->assertStringContainsString('Clear Filters', $response->json('html'));
    }

    public function test_superadmin_can_view_create_page(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.promotion-rules.create'));

        $response->assertStatus(200);
        $response->assertSee('Configure Promotion Rule');
        $response->assertSee('name="name"', false);
        $response->assertSee('id="from_rank_id"', false);
        $response->assertSee('id="to_rank_id"', false);
        $response->assertSee('id="min_team_deposit"', false);
        $response->assertSee('id="rd_composition_percentage"', false);
        $response->assertSee('id="fd_composition_percentage"', false);
    }

    public function test_superadmin_can_view_edit_page(): void
    {
        $rule = PromotionRule::first();
        $this->assertNotNull($rule);

        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.promotion-rules.edit', $rule));

        $response->assertStatus(200);
        $response->assertSee('Edit Promotion Rule');
        $response->assertSee('name="name"', false);
        $response->assertSee('id="from_rank_id"', false);
        $response->assertSee('id="to_rank_id"', false);
        $response->assertSee('id="min_team_deposit"', false);
        $response->assertSee('id="rd_composition_percentage"', false);
        $response->assertSee('id="fd_composition_percentage"', false);
    }

    public function test_superadmin_can_view_show_page(): void
    {
        $rule = PromotionRule::first();
        $this->assertNotNull($rule);

        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.promotion-rules.show', $rule));

        $response->assertStatus(200);
        $response->assertSee($rule->name);
        $response->assertSee('Career Path');
        $response->assertSee('Career Designation & Advancement Path');
        $response->assertSee('Team Deposit Composition');
        $response->assertSee('Team Deposit Target & Mandatory Split');
        $response->assertSee('Individual Target');
        $response->assertSee('Direct Agent Requirements');
        $response->assertSee('Calculation Period');
        $response->assertSee('Qualifying Statuses');
        $response->assertSee('Tenure Multipliers');
        $response->assertSee('Promotion Summary');
        $response->assertSee('Eligibility Overview');
        $response->assertSee('Linked Promotion Requests');
        $response->assertSee('LINKED PROMOTION REQUESTS');
        $response->assertSee('id="promotionRequestSearch"', false);
        $response->assertSee(route('superadmin.promotion-rules.edit', $rule));
        $response->assertSee(route('superadmin.promotion-rules.toggle-status', $rule));
        $response->assertSee(route('superadmin.promotion-rules.index'));
    }

    public function test_superadmin_show_page_renders_with_tenure_multipliers_and_personal_quota(): void
    {
        $rule = PromotionRule::first();
        $rule->update([
            'min_individual_deposit' => 75000.00,
            'apply_tenure_targets' => true,
            'requires_approval' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.promotion-rules.show', $rule));

        $response->assertStatus(200);
        $response->assertSee('75,000');
        $response->assertSee('Multipliers Active');
        $response->assertSee('Fixed Deposit (FD) Duration Schedule');
        $response->assertSee('Recurring Deposit (RD) Duration Schedule');
        $response->assertSee('Manual Review');
    }
}
