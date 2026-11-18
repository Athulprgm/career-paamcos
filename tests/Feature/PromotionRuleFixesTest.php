<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Branch;
use App\Models\CareerLevel;
use App\Models\Customer;
use App\Models\CustomerKyc;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\FinancialYear;
use App\Models\PromotionRequest;
use App\Models\PromotionRule;
use App\Models\Role;
use App\Models\User;
use App\Services\CareerPerformanceService;
use App\Services\PromotionEligibilityService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PromotionRuleFixesTest extends TestCase
{
    use DatabaseTransactions;

    private function createSuperAdmin(): User
    {
        $role = Role::firstOrCreate(
            ['slug' => 'super_admin'],
            ['name' => 'Super Admin', 'status' => 'active']
        );

        $user = User::factory()->create([
            'email' => 'sa_'.uniqid('', true).'@example.com',
        ]);
        $user->roles()->attach($role->id);

        return $user;
    }

    private function createBranch(): Branch
    {
        return Branch::create([
            'name' => 'Test Branch '.uniqid(),
            'code' => 'TB_'.strtoupper(substr(uniqid(), -6)),
            'is_active' => true,
        ]);
    }

    /**
     * @return array{from: CareerLevel, to: CareerLevel}
     */
    private function createRankPair(int $baseLevel = 901): array
    {
        $suffix = strtoupper(substr(uniqid(), -5));

        $from = CareerLevel::create([
            'rank_number' => $baseLevel,
            'position_name' => 'Test From Rank '.$suffix,
            'name' => 'Test From Rank '.$suffix,
            'code' => 'TFR_'.$suffix,
            'level' => $baseLevel,
            'sort_order' => $baseLevel,
            'minimum_deposit_amount' => 100000.00,
            'commission_rate' => 2.00,
            'status' => 'active',
        ]);

        $to = CareerLevel::create([
            'rank_number' => $baseLevel + 1,
            'position_name' => 'Test To Rank '.$suffix,
            'name' => 'Test To Rank '.$suffix,
            'code' => 'TTR_'.$suffix,
            'level' => $baseLevel + 1,
            'sort_order' => $baseLevel + 1,
            'minimum_deposit_amount' => 300000.00,
            'commission_rate' => 3.00,
            'parent_rank_id' => $from->id,
            'status' => 'active',
        ]);

        return ['from' => $from, 'to' => $to];
    }

    private function createAgent(Branch $branch, CareerLevel $rank, string $status = Agent::STATUS_ACTIVE): Agent
    {
        $suffix = strtoupper(substr(uniqid(), -6));

        return Agent::create([
            'member_code' => 'AG_'.$suffix,
            'agent_id' => 'AG_'.$suffix,
            'name' => 'Test Agent '.$suffix,
            'email' => 'agent_'.$suffix.'@example.com',
            'phone' => '98765'.random_int(10000, 99999),
            'branch_id' => $branch->id,
            'rank_id' => $rank->id,
            'joining_date' => now()->toDateString(),
            'status' => $status,
        ]);
    }

    private function createApprovedCustomer(Branch $branch, Agent $agent, User $creator): Customer
    {
        $customer = Customer::create([
            'customer_number' => 'CUST_'.strtoupper(substr(uniqid(), -8)),
            'name' => 'Test Customer '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '98764'.random_int(10000, 99999),
            'date_of_birth' => '1990-01-01',
            'address' => 'Test Address',
            'pin_code' => '682001',
            'branch_id' => $branch->id,
            'agent_id' => $agent->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $creator->id,
        ]);

        CustomerKyc::create([
            'customer_id' => $customer->id,
            'id_type' => 'Aadhaar Card',
            'id_number' => (string) random_int(100000000000, 999999999999),
            'status' => CustomerKyc::STATUS_APPROVED,
            'verified_by' => $creator->id,
            'verified_at' => now(),
        ]);

        return $customer;
    }

    private function createScheme(User $creator, string $type = DepositScheme::TYPE_RD, ?float $promotionTargetPercentage = 20.00): DepositScheme
    {
        $suffix = strtoupper(substr(uniqid(), -5));

        return DepositScheme::create([
            'name' => 'Test '.$type.' Scheme '.$suffix,
            'code' => 'TS'.$type.$suffix,
            'scheme_type' => $type,
            'description' => 'Test scheme for promotion rules',
            'min_amount' => 1000.00,
            'max_amount' => 5000000.00,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 7.50,
            'promotion_target_percentage' => $promotionTargetPercentage,
            'collection_frequency' => $type === DepositScheme::TYPE_FD ? DepositScheme::FREQ_ONE_TIME : DepositScheme::FREQ_MONTHLY,
            'status' => DepositScheme::STATUS_ACTIVE,
            'created_by' => $creator->id,
        ]);
    }

    private function createDeposit(
        Branch $branch,
        Customer $customer,
        Agent $agent,
        DepositScheme $scheme,
        User $creator,
        float $amount = 100000.00,
        string $status = Deposit::STATUS_APPROVED
    ): Deposit {
        return Deposit::create([
            'deposit_number' => 'DEP_'.strtoupper(substr(uniqid(), -8)),
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'agent_id' => $agent->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => $scheme->scheme_type,
            'amount' => $amount,
            'start_date' => now()->toDateString(),
            'deposit_date' => now()->toDateString(),
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'collection_frequency' => $scheme->collection_frequency,
            'interest_rate' => $scheme->interest_rate,
            'maturity_date' => now()->addYear()->toDateString(),
            'maturity_amount' => $amount * 1.075,
            'payment_mode' => Deposit::PAYMENT_MODE_CASH,
            'status' => $status,
            'approved_by' => $creator->id,
            'approved_at' => now(),
            'created_by' => $creator->id,
        ]);
    }

    private function createPromotionRule(CareerLevel $from, CareerLevel $to, array $overrides = []): PromotionRule
    {
        return PromotionRule::create(array_merge([
            'name' => 'Rule '.uniqid(),
            'from_rank_id' => $from->id,
            'to_rank_id' => $to->id,
            'financial_year_id' => null,
            'min_individual_deposit' => 0.00,
            'min_team_deposit' => 100000.00,
            'min_business_amount' => 100000.00,
            'rd_composition_percentage' => 10.00,
            'fd_composition_percentage' => 90.00,
            'apply_tenure_targets' => false,
            'tenure_target_percentages' => PromotionRule::DEFAULT_TENURE_TARGETS,
            'min_qualifying_direct_facilitators' => 0,
            'min_downline_agents' => 0,
            'qualifying_agent_tiers' => null,
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'qualifying_deposit_statuses' => [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED],
            'required_agent_status' => 'active',
            'requires_approval' => false,
            'authorized_approval_role' => PromotionRule::ROLE_ANY_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
        ], $overrides));
    }

    public function test_disabled_tenure_targets_credits_full_face_value_even_when_scheme_has_tenure_percentage(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $branch = $this->createBranch();
        $ranks = $this->createRankPair(901);
        $agent = $this->createAgent($branch, $ranks['from']);
        $customer = $this->createApprovedCustomer($branch, $agent, $superAdmin);
        $rdScheme = $this->createScheme($superAdmin, DepositScheme::TYPE_RD, 20.00);
        $deposit = $this->createDeposit($branch, $customer, $agent, $rdScheme, $superAdmin, 100000.00);
        $rule = $this->createPromotionRule($ranks['from'], $ranks['to'], [
            'min_team_deposit' => 100000.00,
            'rd_composition_percentage' => 10.00,
            'fd_composition_percentage' => 90.00,
            'apply_tenure_targets' => false,
            'requires_approval' => false,
        ]);

        $deposit->load('scheme');
        $percentageWhenDisabled = $deposit->getPromotionTargetPercentage($rule);
        $creditedWhenDisabled = $deposit->getPromotionTargetCreditedAmount($rule);
        $schemePercentageWhenDisabled = $rdScheme->getPromotionTargetPercentage($rule);

        $this->assertSame(100.0, $percentageWhenDisabled);
        $this->assertSame(100000.0, $creditedWhenDisabled);
        $this->assertSame(100.0, $schemePercentageWhenDisabled);

        $rule->update(['apply_tenure_targets' => true]);
        $rule->refresh();

        $this->assertSame(20.0, $deposit->getPromotionTargetPercentage($rule));
        $this->assertSame(20000.0, $deposit->getPromotionTargetCreditedAmount($rule));

        $rule->update(['apply_tenure_targets' => false]);
        $promoted = app(CareerPerformanceService::class)->checkAndAutoPromote($agent, $superAdmin);

        $this->assertNotNull($promoted);
        $this->assertSame($ranks['to']->id, $agent->fresh()->rank_id);
    }

    public function test_required_agent_status_any_allows_inactive_agent_to_pass_status_criterion(): void
    {
        $branch = $this->createBranch();
        $ranks = $this->createRankPair(903);
        $inactiveAgent = $this->createAgent($branch, $ranks['from'], Agent::STATUS_INACTIVE);
        $rule = $this->createPromotionRule($ranks['from'], $ranks['to'], [
            'min_team_deposit' => 0.00,
            'required_agent_status' => 'any',
        ]);

        $eligibility = app(PromotionEligibilityService::class)->evaluateEligibility($inactiveAgent, $rule);

        $this->assertTrue($eligibility['is_eligible']);
        $this->assertTrue($eligibility['criteria']['agent_status']['met']);
        $this->assertSame('Any Status', $eligibility['criteria']['agent_status']['required']);
    }

    public function test_find_applicable_rule_prefers_current_fy_and_global_policy_over_closed_fy(): void
    {
        FinancialYear::query()->update(['is_current' => false]);

        $branch = $this->createBranch();
        $ranks = $this->createRankPair(905);
        $agent = $this->createAgent($branch, $ranks['from']);

        $closedFy = FinancialYear::create([
            'name' => 'FY 2024-25 Closed '.uniqid(),
            'code' => 'FY_CL_'.uniqid(),
            'start_date' => '2024-04-01',
            'end_date' => '2025-03-31',
            'is_current' => false,
            'status' => FinancialYear::STATUS_CLOSED,
        ]);

        $closedFyRule = $this->createPromotionRule($ranks['from'], $ranks['to'], [
            'name' => 'Closed FY Rule',
            'financial_year_id' => $closedFy->id,
        ]);

        $globalRule = $this->createPromotionRule($ranks['from'], $ranks['to'], [
            'name' => 'Global Rule',
            'financial_year_id' => null,
        ]);

        $service = app(PromotionEligibilityService::class);
        $selectedWithoutCurrentFy = $service->findApplicableRule($agent, $ranks['to']);

        $this->assertNotNull($selectedWithoutCurrentFy);
        $this->assertSame($globalRule->id, $selectedWithoutCurrentFy->id);
        $this->assertNotSame($closedFyRule->id, $selectedWithoutCurrentFy->id);

        $currentFy = FinancialYear::create([
            'name' => 'FY 2026-27 Current '.uniqid(),
            'code' => 'FY_CUR_'.uniqid(),
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
            'status' => FinancialYear::STATUS_ACTIVE,
        ]);

        $currentFyRule = $this->createPromotionRule($ranks['from'], $ranks['to'], [
            'name' => 'Current FY Rule',
            'financial_year_id' => $currentFy->id,
        ]);

        $selectedWithCurrentFy = $service->findApplicableRule($agent, $ranks['to']);

        $this->assertNotNull($selectedWithCurrentFy);
        $this->assertSame($currentFyRule->id, $selectedWithCurrentFy->id);
    }

    public function test_destroy_deletes_rule_without_pending_review_requests_and_blocks_when_pending_review_exists(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $branch = $this->createBranch();
        $ranksA = $this->createRankPair(907);
        $ranksB = $this->createRankPair(909);
        $agent = $this->createAgent($branch, $ranksB['from']);

        $deletableRule = $this->createPromotionRule($ranksA['from'], $ranksA['to']);
        $blockedRule = $this->createPromotionRule($ranksB['from'], $ranksB['to']);

        PromotionRequest::create([
            'request_number' => PromotionRequest::generateRequestNumber(),
            'agent_id' => $agent->id,
            'branch_id' => $branch->id,
            'current_level_id' => $ranksB['from']->id,
            'proposed_level_id' => $ranksB['to']->id,
            'promotion_rule_id' => $blockedRule->id,
            'performance_period' => PromotionRule::PERIOD_ALL_TIME,
            'status' => PromotionRequest::STATUS_PENDING_REVIEW,
            'requested_by' => $superAdmin->id,
            'request_date' => now()->toDateString(),
        ]);

        $deleteResponse = $this->actingAs($superAdmin)
            ->delete(route('superadmin.promotion-rules.destroy', $deletableRule));

        $deleteResponse->assertRedirect(route('superadmin.promotion-rules.index'));
        $deleteResponse->assertSessionHas('success');
        $this->assertSoftDeleted($deletableRule);

        $blockedResponse = $this->actingAs($superAdmin)
            ->delete(route('superadmin.promotion-rules.destroy', $blockedRule));

        $blockedResponse->assertRedirect(route('superadmin.promotion-rules.index'));
        $blockedResponse->assertSessionHas('error');
        $this->assertNotSoftDeleted($blockedRule);
    }

    public function test_updating_rule_syncs_legacy_targets_sums_tiers_preserves_custom_tenures_and_allows_inactive_update(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $ranks = $this->createRankPair(911);

        $activeRule = $this->createPromotionRule($ranks['from'], $ranks['to'], [
            'name' => 'Primary Active Rule',
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        $customTenures = PromotionRule::DEFAULT_TENURE_TARGETS;
        $customTenures['fd']['1'] = 33.5;

        $inactiveRule = $this->createPromotionRule($ranks['from'], $ranks['to'], [
            'name' => 'Archived Inactive Rule',
            'status' => PromotionRule::STATUS_INACTIVE,
            'tenure_target_percentages' => $customTenures,
        ]);

        $response = $this->actingAs($superAdmin)->put(route('superadmin.promotion-rules.update', $inactiveRule), [
            'name' => 'Archived Inactive Rule Updated',
            'from_rank_id' => $ranks['from']->id,
            'to_rank_id' => $ranks['to']->id,
            'financial_year_id' => null,
            'min_individual_deposit' => 50000,
            'min_team_deposit' => 750000,
            'rd_composition_percentage' => 10,
            'fd_composition_percentage' => 90,
            'apply_tenure_targets' => 1,
            'qualifying_agent_tiers' => [
                ['count' => 2, 'rank_id' => $ranks['from']->id],
                ['count' => 3, 'rank_id' => $ranks['to']->id],
            ],
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'qualifying_deposit_statuses' => [Deposit::STATUS_ACTIVE, Deposit::STATUS_APPROVED],
            'required_agent_status' => 'active',
            'authorized_approval_role' => PromotionRule::ROLE_ANY_ADMIN,
            'status' => PromotionRule::STATUS_INACTIVE,
        ]);

        $response->assertRedirect(route('superadmin.promotion-rules.show', $inactiveRule));
        $response->assertSessionHasNoErrors();

        $inactiveRule->refresh();
        $this->assertSame(750000.0, (float) $inactiveRule->min_business_amount);
        $this->assertSame(5, (int) $inactiveRule->min_qualifying_direct_facilitators);
        $this->assertSame(5, (int) $inactiveRule->min_downline_agents);
        $this->assertSame(33.5, (float) ($inactiveRule->tenure_target_percentages['fd']['1'] ?? 0));

        $duplicateActiveResponse = $this->actingAs($superAdmin)->put(route('superadmin.promotion-rules.update', $inactiveRule), [
            'name' => 'Attempt Duplicate Active',
            'from_rank_id' => $ranks['from']->id,
            'to_rank_id' => $ranks['to']->id,
            'financial_year_id' => null,
            'min_individual_deposit' => 50000,
            'min_team_deposit' => 750000,
            'rd_composition_percentage' => 10,
            'fd_composition_percentage' => 90,
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'required_agent_status' => 'active',
            'authorized_approval_role' => PromotionRule::ROLE_ANY_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        $duplicateActiveResponse->assertSessionHasErrors('from_rank_id');

        $activeRule->update([
            'min_team_deposit' => 900000.00,
            'min_qualifying_direct_facilitators' => 7,
        ]);
        $activeRule->refresh();

        $this->assertSame(900000.0, (float) $activeRule->min_business_amount);
        $this->assertSame(7, (int) $activeRule->min_downline_agents);
    }

    public function test_storing_rule_accepts_zero_count_tier_and_sums_multi_tier_counts(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $zeroRanks = $this->createRankPair(917);
        $multiRanks = $this->createRankPair(919);

        $zeroResponse = $this->actingAs($superAdmin)->post(route('superadmin.promotion-rules.store'), [
            'name' => 'Zero Tier Rule',
            'from_rank_id' => $zeroRanks['from']->id,
            'to_rank_id' => $zeroRanks['to']->id,
            'min_individual_deposit' => 0,
            'min_team_deposit' => 100000,
            'rd_composition_percentage' => 10,
            'fd_composition_percentage' => 90,
            'qualifying_agent_tiers' => [
                ['count' => 0, 'rank_id' => ''],
            ],
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'required_agent_status' => 'active',
            'authorized_approval_role' => PromotionRule::ROLE_ANY_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        $zeroResponse->assertRedirect(route('superadmin.promotion-rules.index'));
        $zeroResponse->assertSessionHasNoErrors();

        $zeroRule = PromotionRule::where('name', 'Zero Tier Rule')->firstOrFail();
        $this->assertNull($zeroRule->qualifying_agent_tiers);
        $this->assertSame(0, (int) $zeroRule->min_qualifying_direct_facilitators);
        $this->assertSame(0, (int) $zeroRule->min_downline_agents);

        $multiResponse = $this->actingAs($superAdmin)->post(route('superadmin.promotion-rules.store'), [
            'name' => 'Multi Tier Rule',
            'from_rank_id' => $multiRanks['from']->id,
            'to_rank_id' => $multiRanks['to']->id,
            'min_individual_deposit' => 0,
            'min_team_deposit' => 500000,
            'rd_composition_percentage' => 10,
            'fd_composition_percentage' => 90,
            'qualifying_agent_tiers' => [
                ['count' => 2, 'rank_id' => $multiRanks['from']->id],
                ['count' => 4, 'rank_id' => $multiRanks['to']->id],
            ],
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'required_agent_status' => 'active',
            'authorized_approval_role' => PromotionRule::ROLE_ANY_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        $multiResponse->assertRedirect(route('superadmin.promotion-rules.index'));
        $multiResponse->assertSessionHasNoErrors();

        $multiRule = PromotionRule::where('name', 'Multi Tier Rule')->firstOrFail();
        $this->assertSame(6, (int) $multiRule->min_qualifying_direct_facilitators);
        $this->assertSame(6, (int) $multiRule->min_downline_agents);
    }

    public function test_updating_deposit_agent_and_amount_refreshes_model_before_auto_promotion(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $branch = $this->createBranch();
        $ranks = $this->createRankPair(913);
        $originalAgent = $this->createAgent($branch, $ranks['from']);
        $targetAgent = $this->createAgent($branch, $ranks['from']);
        $customer = $this->createApprovedCustomer($branch, $originalAgent, $superAdmin);
        $scheme = $this->createScheme($superAdmin, DepositScheme::TYPE_RD, 20.00);
        $deposit = $this->createDeposit($branch, $customer, $originalAgent, $scheme, $superAdmin, 40000.00, Deposit::STATUS_ACTIVE);

        $this->createPromotionRule($ranks['from'], $ranks['to'], [
            'min_team_deposit' => 100000.00,
            'apply_tenure_targets' => false,
            'requires_approval' => false,
        ]);

        $response = $this->actingAs($superAdmin)->put(route('admin.deposits.update', $deposit), [
            'deposit_scheme_id' => $scheme->id,
            'agent_id' => $targetAgent->id,
            'amount' => 120000.00,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'start_date' => now()->toDateString(),
            'is_senior_citizen' => 0,
            'is_double' => 0,
            'payment_mode' => Deposit::PAYMENT_MODE_CASH,
        ]);

        $response->assertRedirect(route('admin.deposits.show', $deposit));
        $response->assertSessionHasNoErrors();

        $this->assertSame($ranks['from']->id, $originalAgent->fresh()->rank_id);
        $this->assertSame($ranks['to']->id, $targetAgent->fresh()->rank_id);
    }

    public function test_agent_show_displays_promoted_rank_when_auto_promotion_triggers_during_page_load(): void
    {
        $superAdmin = $this->createSuperAdmin();
        $branch = $this->createBranch();
        $ranks = $this->createRankPair(915);
        $agent = $this->createAgent($branch, $ranks['from']);
        $customer = $this->createApprovedCustomer($branch, $agent, $superAdmin);
        $scheme = $this->createScheme($superAdmin, DepositScheme::TYPE_RD, 20.00);
        $this->createDeposit($branch, $customer, $agent, $scheme, $superAdmin, 100000.00, Deposit::STATUS_APPROVED);

        $this->createPromotionRule($ranks['from'], $ranks['to'], [
            'min_team_deposit' => 100000.00,
            'apply_tenure_targets' => false,
            'requires_approval' => false,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.agents.show', $agent));

        $response->assertOk();
        $this->assertSame($ranks['to']->id, $agent->fresh()->rank_id);
        $response->assertViewHas('careerPerformance', fn (array $perf): bool => (int) $perf['current_rank']?->id === (int) $ranks['to']->id);
    }
}
