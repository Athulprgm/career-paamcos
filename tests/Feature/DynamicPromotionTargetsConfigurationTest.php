<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\CareerLevel;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\DepositSchemeCommissionHistory;
use App\Models\FinancialYear;
use App\Models\PromotionRequest;
use App\Models\PromotionRule;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\CareerPerformanceService;
use App\Services\PromotionEligibilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DynamicPromotionTargetsConfigurationTest extends TestCase
{
    use DatabaseTransactions;

    protected CareerPerformanceService $careerService;

    protected PromotionEligibilityService $eligibilityService;

    protected User $superAdmin;

    protected Branch $branch;

    protected CareerLevel $level1;

    protected CareerLevel $level2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->careerService = app(CareerPerformanceService::class);
        $this->eligibilityService = app(PromotionEligibilityService::class);

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);

        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR_TEST_DYN_TGT'],
            ['name' => 'Dynamic Target Branch', 'is_active' => true]
        );

        $this->superAdmin = User::create([
            'name' => 'Super Admin Dynamic Target',
            'email' => 'sadynamic_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        $this->level1 = CareerLevel::where('level', 1)->firstOrFail();
        $this->level2 = CareerLevel::where('level', 2)->firstOrFail();
    }

    protected function createAgentWithUser(string $name, CareerLevel $rank): Agent
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
            'joining_date' => now()->subMonths(6)->toDateString(),
            'status' => 'active',
        ]);
    }

    protected function createDepositForAgent(Agent $agent, string $type, int|float $tenureYears, float $amount, string $status = Deposit::STATUS_ACTIVE): Deposit
    {
        $tenureMonths = (int) round($tenureYears * 12);

        $scheme = DepositScheme::create([
            'name' => "Dynamic Scheme {$type} {$tenureYears}Y ".uniqid(),
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
            'branch_id' => $agent->branch_id,
            'agent_id' => $agent->id,
            'created_by' => $this->superAdmin->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        return Deposit::create([
            'deposit_number' => 'DEP_'.uniqid(),
            'customer_id' => $customer->id,
            'agent_id' => $agent->id,
            'branch_id' => $agent->branch_id,
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

    public function test_superadmin_can_view_promotion_rules_index_with_targets_policy_banner(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.promotion-rules.index'));

        $response->assertStatus(200);
        $response->assertSee('Promotion Targets Policy');
        $response->assertSee('Dynamic Promotion Targets &amp; Compulsory Conditions', false);
        $response->assertSee('Configure Policy');
    }

    public function test_superadmin_can_update_global_targets_and_bulk_sync_rules(): void
    {
        $payload = [
            'rd_composition_percentage' => 10,
            'fd_composition_percentage' => 90,
            'apply_tenure_targets' => '1',
            'sync_all_rules' => '1',
            'tenure_target_percentages' => [
                'fd' => [
                    '1' => 20,
                    '2' => 30,
                    '3' => 40,
                    '5' => 80,
                    '6' => 80,
                ],
                'rd' => [
                    '1' => 20,
                    '2' => 30,
                    '3' => 40,
                    '5' => 80,
                    '6' => 80,
                    '7' => 90,
                ],
                'dd' => [
                    '1' => 20,
                    '2' => 40,
                    '3' => 50,
                ],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)->post(
            route('superadmin.promotion-rules.global-targets'),
            $payload
        );

        $response->assertRedirect(route('superadmin.promotion-rules.index'));
        $response->assertSessionHas('success');

        // Verify settings updated
        $this->assertEquals(10, (float) Setting::get('career.rd_composition_percentage'));
        $this->assertEquals(90, (float) Setting::get('career.fd_composition_percentage'));

        $savedTargets = Setting::get('career.tenure_target_percentages');
        if (is_string($savedTargets)) {
            $savedTargets = json_decode($savedTargets, true);
        }

        $this->assertEquals(20, $savedTargets['fd']['1']);
        $this->assertEquals(30, $savedTargets['fd']['2']);
        $this->assertEquals(40, $savedTargets['fd']['3']);
        $this->assertEquals(80, $savedTargets['fd']['5']);
        $this->assertEquals(80, $savedTargets['fd']['6']);

        $this->assertEquals(20, $savedTargets['rd']['1']);
        $this->assertEquals(30, $savedTargets['rd']['2']);
        $this->assertEquals(40, $savedTargets['rd']['3']);
        $this->assertEquals(80, $savedTargets['rd']['5']);
        $this->assertEquals(80, $savedTargets['rd']['6']);
        $this->assertEquals(90, $savedTargets['rd']['7']);

        $this->assertEquals(20, $savedTargets['dd']['1']);
        $this->assertEquals(40, $savedTargets['dd']['2']);
        $this->assertEquals(50, $savedTargets['dd']['3']);

        // Verify active rules synced
        $rule = PromotionRule::where('from_rank_id', $this->level1->id)
            ->where('to_rank_id', $this->level2->id)
            ->first();

        $this->assertNotNull($rule);
        $this->assertTrue((bool) $rule->apply_tenure_targets);
        $this->assertEquals(20, $rule->getDepositPromotionPercentage(DepositScheme::TYPE_FD, 1));
        $this->assertEquals(30, $rule->getDepositPromotionPercentage(DepositScheme::TYPE_FD, 2));
        $this->assertEquals(40, $rule->getDepositPromotionPercentage(DepositScheme::TYPE_FD, 3));
        $this->assertEquals(80, $rule->getDepositPromotionPercentage(DepositScheme::TYPE_FD, 5));
        $this->assertEquals(80, $rule->getDepositPromotionPercentage(DepositScheme::TYPE_FD, 6));
        $this->assertEquals(0, $rule->getDepositPromotionPercentage(DepositScheme::TYPE_FD, 7)); // 7Y FD is unconfigured -> 0%
        $this->assertEquals(90, $rule->getDepositPromotionPercentage(DepositScheme::TYPE_RD, 7)); // 7Y RD is 90%
        $this->assertEquals(20, $rule->getDepositPromotionPercentage(DepositScheme::TYPE_DD, 1));
        $this->assertEquals(40, $rule->getDepositPromotionPercentage(DepositScheme::TYPE_DD, 2));
        $this->assertEquals(50, $rule->getDepositPromotionPercentage(DepositScheme::TYPE_DD, 3));
    }

    public function test_tenure_credited_amounts_match_business_target_percentages(): void
    {
        $rule = PromotionRule::where('from_rank_id', $this->level1->id)
            ->where('to_rank_id', $this->level2->id)
            ->firstOrFail();

        $rule->update([
            'apply_tenure_targets' => true,
            'tenure_target_percentages' => PromotionRule::DEFAULT_TENURE_TARGETS,
            'requires_approval' => true,
        ]);

        $agent = $this->createAgentWithUser('Tenure Test Agent', $this->level1);

        // 1 Year FD: ₹1,00,000 -> 20% credit = ₹20,000
        $fd1 = $this->createDepositForAgent($agent, DepositScheme::TYPE_FD, 1, 100000.00);
        $this->assertEquals(20.0, $fd1->getPromotionTargetPercentage($rule));
        $this->assertEquals(20000.00, $fd1->getPromotionTargetCreditedAmount($rule));

        // 2 Year FD: ₹1,00,000 -> 30% credit = ₹30,000
        $fd2 = $this->createDepositForAgent($agent, DepositScheme::TYPE_FD, 2, 100000.00);
        $this->assertEquals(30.0, $fd2->getPromotionTargetPercentage($rule));
        $this->assertEquals(30000.00, $fd2->getPromotionTargetCreditedAmount($rule));

        // 3 Year FD: ₹1,00,000 -> 40% credit = ₹40,000
        $fd3 = $this->createDepositForAgent($agent, DepositScheme::TYPE_FD, 3, 100000.00);
        $this->assertEquals(40.0, $fd3->getPromotionTargetPercentage($rule));
        $this->assertEquals(40000.00, $fd3->getPromotionTargetCreditedAmount($rule));

        // 5 Year FD: ₹1,00,000 -> 80% credit = ₹80,000
        $fd5 = $this->createDepositForAgent($agent, DepositScheme::TYPE_FD, 5, 100000.00);
        $this->assertEquals(80.0, $fd5->getPromotionTargetPercentage($rule));
        $this->assertEquals(80000.00, $fd5->getPromotionTargetCreditedAmount($rule));

        // 6 Year FD: ₹1,00,000 -> 80% credit = ₹80,000
        $fd6 = $this->createDepositForAgent($agent, DepositScheme::TYPE_FD, 6, 100000.00);
        $this->assertEquals(80.0, $fd6->getPromotionTargetPercentage($rule));
        $this->assertEquals(80000.00, $fd6->getPromotionTargetCreditedAmount($rule));

        // 7 Year FD: unconfigured duration rule -> 0% credit
        $fd7 = $this->createDepositForAgent($agent, DepositScheme::TYPE_FD, 7, 100000.00);
        $this->assertEquals(0.0, $fd7->getPromotionTargetPercentage($rule));
        $this->assertEquals(0.00, $fd7->getPromotionTargetCreditedAmount($rule));

        // 7 Year RD: ₹1,00,000 -> 90% credit = ₹90,000
        $rd7 = $this->createDepositForAgent($agent, DepositScheme::TYPE_RD, 7, 100000.00);
        $this->assertEquals(90.0, $rd7->getPromotionTargetPercentage($rule));
        $this->assertEquals(90000.00, $rd7->getPromotionTargetCreditedAmount($rule));

        // 1 Year DD: ₹1,00,000 -> 20% credit = ₹20,000
        $dd1 = $this->createDepositForAgent($agent, DepositScheme::TYPE_DD, 1, 100000.00);
        $this->assertEquals(20.0, $dd1->getPromotionTargetPercentage($rule));
        $this->assertEquals(20000.00, $dd1->getPromotionTargetCreditedAmount($rule));

        // 2 Year DD: ₹1,00,000 -> 40% credit = ₹40,000
        $dd2 = $this->createDepositForAgent($agent, DepositScheme::TYPE_DD, 2, 100000.00);
        $this->assertEquals(40.0, $dd2->getPromotionTargetPercentage($rule));
        $this->assertEquals(40000.00, $dd2->getPromotionTargetCreditedAmount($rule));

        // 3 Year DD: ₹1,00,000 -> 50% credit = ₹50,000
        $dd3 = $this->createDepositForAgent($agent, DepositScheme::TYPE_DD, 3, 100000.00);
        $this->assertEquals(50.0, $dd3->getPromotionTargetPercentage($rule));
        $this->assertEquals(50000.00, $dd3->getPromotionTargetCreditedAmount($rule));

        // Unconfigured duration: 4 Year FD (not in rules) -> 0% credit, does not invent rule
        $fd4 = $this->createDepositForAgent($agent, DepositScheme::TYPE_FD, 4, 100000.00);
        $this->assertEquals(0.0, $fd4->getPromotionTargetPercentage($rule));
        $this->assertEquals(0.00, $fd4->getPromotionTargetCreditedAmount($rule));

        // Sub-1-year duration: 6 Months FD (0.5Y) -> 0% credit, not rounded up to 1Y
        $fdHalf = $this->createDepositForAgent($agent, DepositScheme::TYPE_FD, 0.5, 100000.00);
        $this->assertEquals(0.0, $fdHalf->getPromotionTargetPercentage($rule));
        $this->assertEquals(0.00, $fdHalf->getPromotionTargetCreditedAmount($rule));
    }

    public function test_cumulative_deposits_aggregation_and_deduplication(): void
    {
        $rule = PromotionRule::where('from_rank_id', $this->level1->id)
            ->where('to_rank_id', $this->level2->id)
            ->firstOrFail();

        $rule->update([
            'apply_tenure_targets' => true,
            'tenure_target_percentages' => PromotionRule::DEFAULT_TENURE_TARGETS,
            'rd_composition_percentage' => 10,
            'fd_composition_percentage' => 90,
        ]);

        $agent = $this->createAgentWithUser('Cumulative Agent', $this->level1);

        // Deposit 1: 1-Year FD ₹3,00,000 (20% credit = ₹60,000)
        $this->createDepositForAgent($agent, DepositScheme::TYPE_FD, 1, 300000.00);

        // Deposit 2: 7-Year RD ₹3,00,000 (90% credit = ₹270,000)
        $this->createDepositForAgent($agent, DepositScheme::TYPE_RD, 7, 300000.00);

        $achievement = $this->careerService->calculateTargetAchievement($agent);

        // Both deposits aggregated: Gross = ₹6,00,000, Credited = ₹60,000 + ₹270,000 = ₹330,000
        $this->assertEquals(600000.00, $achievement['total_deposits']);
        $this->assertEquals(330000.00, $achievement['credited_deposits']);
        $this->assertEquals(300000.00, $achievement['eligible_deposits']);
        $this->assertEquals(0.00, $achievement['remaining_amount']);
        $this->assertEquals(100.0, $achievement['progress_percentage']);
        $this->assertTrue($achievement['rd_progress']['is_met']);
    }

    public function test_deposit_edit_and_cancellation_recalculates_promotion_achievement(): void
    {
        $rule = PromotionRule::where('from_rank_id', $this->level1->id)
            ->where('to_rank_id', $this->level2->id)
            ->firstOrFail();

        $rule->update([
            'apply_tenure_targets' => true,
            'tenure_target_percentages' => PromotionRule::DEFAULT_TENURE_TARGETS,
            'requires_approval' => true,
        ]);

        $agent = $this->createAgentWithUser('Edit Cancel Agent', $this->level1);

        // Create deposit: 1 Year FD of ₹1,00,000 (20% = ₹20,000)
        $deposit = $this->createDepositForAgent($agent, DepositScheme::TYPE_FD, 1, 100000.00);

        $achievement1 = $this->careerService->calculateTargetAchievement($agent);
        $this->assertEquals(20000.00, $achievement1['credited_deposits']);

        // 1. Edit deposit amount to ₹2,00,000 (20% = ₹40,000)
        $deposit->update(['amount' => 200000.00]);
        $achievement2 = $this->careerService->calculateTargetAchievement($agent);
        $this->assertEquals(40000.00, $achievement2['credited_deposits']);

        // 2. Reject deposit -> must immediately be excluded from promotion contribution
        $deposit->update(['status' => Deposit::STATUS_REJECTED]);
        $achievement3 = $this->careerService->calculateTargetAchievement($agent);
        $this->assertEquals(0.00, $achievement3['credited_deposits']);

        // 3. Reactivate deposit and then soft-delete it -> must also be excluded
        $deposit->update(['status' => Deposit::STATUS_ACTIVE]);
        $deposit->delete(); // Soft delete
        $achievement4 = $this->careerService->calculateTargetAchievement($agent);
        $this->assertEquals(0.00, $achievement4['credited_deposits']);
    }

    public function test_duplicate_promotion_requests_and_awards_are_prevented(): void
    {
        $rule = PromotionRule::where('from_rank_id', $this->level1->id)
            ->where('to_rank_id', $this->level2->id)
            ->firstOrFail();

        $rule->update([
            'apply_tenure_targets' => true,
            'tenure_target_percentages' => PromotionRule::DEFAULT_TENURE_TARGETS,
            'requires_approval' => true,
        ]);

        $agent = $this->createAgentWithUser('Award Test Agent', $this->level1);

        // Fulfill target with 7-Year RD (90% on ₹3,50,000 = ₹3,15,000 credited >= ₹3,00,000)
        $this->createDepositForAgent($agent, DepositScheme::TYPE_RD, 7, 350000.00);

        // 1. Submit promotion request
        $request1 = $this->careerService->submitPromotionRequest($agent, $this->level2, null, null, $this->superAdmin);
        $this->assertEquals(PromotionRequest::STATUS_PENDING_REVIEW, $request1->status);

        // 2. Submitting second request while one is pending must throw ValidationException
        $this->expectException(ValidationException::class);
        $this->careerService->submitPromotionRequest($agent, $this->level2, null, null, $this->superAdmin);
    }

    public function test_superadmin_cannot_create_duplicate_active_promotion_rule(): void
    {
        // Try creating a second active promotion rule with identical from_rank_id and to_rank_id
        $payload = [
            'name' => 'Duplicate Rule Test',
            'from_rank_id' => $this->level1->id,
            'to_rank_id' => $this->level2->id,
            'min_team_deposit' => 300000,
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'required_agent_status' => 'active',
            'requires_approval' => '1',
            'authorized_approval_role' => PromotionRule::ROLE_SUPER_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
        ];

        $response = $this->actingAs($this->superAdmin)->post(
            route('superadmin.promotion-rules.store'),
            $payload
        );

        $response->assertSessionHasErrors('from_rank_id');
    }

    public function test_superadmin_can_create_deposit_commission_scheme_with_promotion_target_percentage(): void
    {
        $ranks = AgentRank::all();
        $ranksPayload = [];
        foreach ($ranks as $rank) {
            $ranksPayload[] = [
                'rank_id' => $rank->id,
                'is_eligible' => true,
                'commission_percentage' => '1.00',
            ];
        }

        $code = 'SCH_PROMO_TGT_'.rand(1000, 9999);
        $payload = [
            'name' => 'FD 1 Year Promo Target Scheme',
            'code' => $code,
            'scheme_type' => 'FD',
            'collection_frequency' => 'ONE_TIME',
            'interest_payout_frequency' => 'AT_MATURITY',
            'interest_rate' => '8.00',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'min_amount' => 1000,
            'max_amount' => 500000,
            'status' => DepositScheme::STATUS_ACTIVE,
            'promotion_target_percentage' => '20.00',
            'description' => 'Scheme with 20% promotion target credit',
            'notes' => 'Testing promotion target percentage configuration',
            'ranks' => $ranksPayload,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.store'), $payload);

        $response->assertSessionHasNoErrors();

        $scheme = DepositScheme::where('code', $code)->first();
        $this->assertNotNull($scheme);
        $this->assertEquals(20.00, (float) $scheme->promotion_target_percentage);
        $this->assertEquals(20.00, $scheme->getPromotionTargetPercentage());
    }

    public function test_superadmin_can_update_deposit_commission_scheme_promotion_target_percentage(): void
    {
        $ranks = AgentRank::all();
        $ranksPayload = [];
        foreach ($ranks as $rank) {
            $ranksPayload[] = [
                'rank_id' => $rank->id,
                'is_eligible' => true,
                'commission_percentage' => '1.00',
            ];
        }

        $scheme = DepositScheme::create([
            'name' => 'Editable Promo Scheme '.uniqid(),
            'code' => 'SCH_EDIT_'.rand(1000, 9999),
            'scheme_type' => 'FD',
            'min_amount' => '100.00',
            'max_amount' => '500000.00',
            'tenure' => 24,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.50,
            'total_commission_percentage' => 3.00,
            'promotion_target_percentage' => 30.00,
            'status' => DepositScheme::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $updatePayload = [
            'name' => $scheme->name,
            'code' => $scheme->code,
            'scheme_type' => 'FD',
            'collection_frequency' => 'ONE_TIME',
            'interest_payout_frequency' => 'AT_MATURITY',
            'interest_rate' => '8.00',
            'tenure' => 24,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'status' => DepositScheme::STATUS_ACTIVE,
            'promotion_target_percentage' => '45.00',
            'notes' => 'Updated promo target credit from 30% to 45%',
            'ranks' => $ranksPayload,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->put(route('superadmin.fd-commissions.update', $scheme), $updatePayload);

        $response->assertSessionHasNoErrors();

        $scheme->refresh();
        $this->assertEquals(45.00, (float) $scheme->promotion_target_percentage);
        $this->assertEquals(45.00, $scheme->getPromotionTargetPercentage());

        // Verify history logged new values
        $history = DepositSchemeCommissionHistory::where('deposit_scheme_id', $scheme->id)
            ->where('action', 'updated')
            ->latest('id')
            ->first();
        $this->assertNotNull($history);
        $newValues = is_array($history->new_values) ? $history->new_values : json_decode($history->new_values, true);
        $this->assertEquals(45.00, (float) $newValues['promotion_target_percentage']);
    }

    public function test_deposit_scheme_promotion_target_percentage_validation(): void
    {
        $ranks = AgentRank::all();
        $ranksPayload = [];
        foreach ($ranks as $rank) {
            $ranksPayload[] = [
                'rank_id' => $rank->id,
                'is_eligible' => true,
                'commission_percentage' => '1.00',
            ];
        }

        // 1. Percentage > 100 must fail
        $payload1 = [
            'name' => 'Invalid Promo Scheme',
            'code' => 'SCH_INV_'.rand(1000, 9999),
            'scheme_type' => 'FD',
            'interest_rate' => '7.50',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'status' => DepositScheme::STATUS_ACTIVE,
            'promotion_target_percentage' => '105.00',
            'ranks' => $ranksPayload,
        ];

        $response1 = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.store'), $payload1);
        $response1->assertSessionHasErrors('promotion_target_percentage');

        // 2. Percentage < 0 must fail
        $payload2 = array_merge($payload1, [
            'code' => 'SCH_INV2_'.rand(1000, 9999),
            'promotion_target_percentage' => '-5.00',
        ]);
        $response2 = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.store'), $payload2);
        $response2->assertSessionHasErrors('promotion_target_percentage');
    }

    public function test_agent_promotion_credited_amount_uses_scheme_level_configured_percentage(): void
    {
        // 1. Create a custom scheme with 25.00% promotion target credit
        $customScheme = DepositScheme::create([
            'name' => 'FD 25% Promo Credit Scheme '.uniqid(),
            'code' => 'SCH_25PCT_'.rand(1000, 9999),
            'scheme_type' => 'FD',
            'min_amount' => '100.00',
            'max_amount' => '5000000.00',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.00,
            'total_commission_percentage' => 3.00,
            'promotion_target_percentage' => 25.00,
            'status' => DepositScheme::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $agent = $this->createAgentWithUser('Scheme Override Agent', $this->level1);
        $customer = Customer::create([
            'customer_number' => 'CUST_'.rand(10000, 99999),
            'name' => 'Scheme Override Customer',
            'mobile' => '98'.rand(10000000, 99999999),
            'branch_id' => $this->branch->id,
            'agent_id' => $agent->id,
            'status' => 'active',
        ]);

        // Customer deposits ₹4,00,000 under this 25% scheme
        $deposit = Deposit::create([
            'deposit_number' => 'DEP_SCH_'.rand(10000, 99999),
            'deposit_id' => 'DEP_SCH_'.rand(10000, 99999),
            'customer_id' => $customer->id,
            'agent_id' => $agent->id,
            'branch_id' => $this->branch->id,
            'deposit_scheme_id' => $customScheme->id,
            'deposit_type' => 'FD',
            'amount' => 400000.00,
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.00,
            'maturity_date' => now()->addYear()->toDateString(),
            'maturity_amount' => 428000.00,
            'deposit_date' => now()->toDateString(),
            'status' => Deposit::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        // 2. Deposit calculation verification
        $this->assertEquals(25.00, $deposit->getPromotionTargetPercentage());
        // ₹4,00,000 * 25% = ₹1,00,000 credited
        $this->assertEquals(100000.00, $deposit->getPromotionTargetCreditedAmount());

        // 3. Promotion Rule Target: Level 1 -> Level 2 requires ₹3,00,000
        $rule = PromotionRule::where('from_rank_id', $this->level1->id)
            ->where('to_rank_id', $this->level2->id)
            ->firstOrFail();

        $rule->update([
            'apply_tenure_targets' => true,
            'min_qualifying_direct_facilitators' => 0,
            'qualifying_agent_tiers' => [],
        ]);

        $eval = $this->eligibilityService->evaluateEligibility($agent, $rule);
        // Gross deposit is ₹4,00,000, but credited deposit is ₹1,00,000
        $this->assertEquals(400000.00, (float) $eval['gross_deposits']);
        $this->assertEquals(100000.00, (float) $eval['credited_deposits']);
        // ₹1,00,000 credited < ₹3,00,000 required -> NOT achieved
        $this->assertFalse($eval['is_eligible']);

        // 4. If agent only deposits FD up to ₹3,00,000 credited (e.g. ₹8,00,000 more * 25% = ₹2,00,000),
        // total credited is ₹3,00,000, BUT the 10% compulsory RD requirement is not met (0% RD).
        $fdDeposit2 = Deposit::create([
            'deposit_number' => 'DEP_SCH2_'.rand(10000, 99999),
            'deposit_id' => 'DEP_SCH2_'.rand(10000, 99999),
            'customer_id' => $customer->id,
            'agent_id' => $agent->id,
            'branch_id' => $this->branch->id,
            'deposit_scheme_id' => $customScheme->id,
            'deposit_type' => 'FD',
            'amount' => 800000.00,
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.00,
            'maturity_date' => now()->addYear()->toDateString(),
            'maturity_amount' => 856000.00,
            'deposit_date' => now()->toDateString(),
            'status' => Deposit::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $eval2 = $this->eligibilityService->evaluateEligibility($agent, $rule);
        // Total gross: ₹12,00,000; Total credited: ₹1,00,000 + ₹2,00,000 = ₹3,00,000 >= ₹3,00,000
        $this->assertEquals(1200000.00, (float) $eval2['gross_deposits']);
        $this->assertEquals(300000.00, (float) $eval2['credited_deposits']);
        // Compulsory 10% RD is unmet (actual RD = ₹0), so is_eligible remains false
        $this->assertFalse($eval2['is_eligible']);
        $this->assertFalse($eval2['rd_met']);

        // 5. Adjust FD so credited FD = ₹2,70,000 (90% cap of ₹3,00,000)
        // ₹4,00,000 + ₹6,80,000 = ₹10,80,000 * 25% = ₹2,70,000 credited
        $fdDeposit2->update(['amount' => 680000.00]);

        // Add 10% RD requirement: ₹1,50,000 deposit in 1-Year RD scheme with 20% credit = ₹30,000 credited RD
        $rdScheme = DepositScheme::create([
            'name' => 'RD 1 Year Compulsory Target Scheme '.uniqid(),
            'code' => 'SCH_RD_COMP_'.rand(1000, 9999),
            'scheme_type' => 'RD',
            'min_amount' => '100.00',
            'max_amount' => '5000000.00',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.00,
            'total_commission_percentage' => 3.00,
            'promotion_target_percentage' => 20.00,
            'status' => DepositScheme::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        Deposit::create([
            'deposit_number' => 'DEP_RD_'.rand(10000, 99999),
            'deposit_id' => 'DEP_RD_'.rand(10000, 99999),
            'customer_id' => $customer->id,
            'agent_id' => $agent->id,
            'branch_id' => $this->branch->id,
            'deposit_scheme_id' => $rdScheme->id,
            'deposit_type' => 'RD',
            'amount' => 150000.00,
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.00,
            'maturity_date' => now()->addYear()->toDateString(),
            'maturity_amount' => 160000.00,
            'deposit_date' => now()->toDateString(),
            'status' => Deposit::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $eval3 = $this->eligibilityService->evaluateEligibility($agent, $rule);
        // Total credited = ₹2,70,000 (FD) + ₹30,000 (RD) = ₹3,00,000
        $this->assertEquals(300000.00, (float) $eval3['credited_deposits']);
        $this->assertTrue($eval3['rd_met']);
        $this->assertTrue($eval3['fd_met']);
        $this->assertTrue($eval3['is_eligible']);
    }

    public function test_promotion_rule_creation_fails_when_composition_split_does_not_equal_100(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(
            route('superadmin.promotion-rules.store'),
            [
                'name' => 'Invalid Split Rule',
                'from_rank_id' => $this->level1->id,
                'to_rank_id' => $this->level2->id,
                'min_individual_deposit' => 0,
                'min_team_deposit' => 300000,
                'rd_composition_percentage' => 30, // 30 + 80 = 110% != 100%
                'fd_composition_percentage' => 80,
                'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
                'required_agent_status' => 'active',
                'requires_approval' => 1,
                'authorized_approval_role' => PromotionRule::ROLE_ADMIN,
                'status' => PromotionRule::STATUS_ACTIVE,
            ]
        );

        $response->assertSessionHasErrors(['rd_composition_percentage', 'fd_composition_percentage']);
    }

    public function test_global_targets_policy_reflects_saved_custom_tenure_multipliers_on_index_page(): void
    {
        // 1. Update global targets with custom percentages (e.g. 25% for 1 Year, 35% for 2 Year)
        $this->actingAs($this->superAdmin)->post(
            route('superadmin.promotion-rules.global-targets'),
            [
                'rd_composition_percentage' => 10,
                'fd_composition_percentage' => 90,
                'apply_tenure_targets' => '1',
                'sync_all_rules' => '1',
                'tenure_target_percentages' => [
                    'fd_rd' => [
                        '1' => 25,
                        '2' => 35,
                        '3' => 45,
                        '5' => 85,
                        '6' => 85,
                        '7' => 95,
                    ],
                    'dd' => [
                        '1' => 22,
                        '2' => 42,
                        '3' => 52,
                    ],
                ],
            ]
        )->assertRedirect();

        // 2. View index page and verify custom 25% and 35% values are present in the HTML modal inputs
        $indexResponse = $this->get(route('superadmin.promotion-rules.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee('value="25"', false);
        $indexResponse->assertSee('value="35"', false);
        $indexResponse->assertSee('value="45"', false);
    }

    public function test_superadmin_can_create_and_update_rule_with_financial_year(): void
    {
        $fy = FinancialYear::first();

        // 1. Create rule with financial_year_id and PERIOD_FINANCIAL_YEAR
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.promotion-rules.store'), [
            'name' => 'FY Scoped Promotion Rule '.uniqid(),
            'from_rank_id' => $this->level1->id,
            'to_rank_id' => $this->level2->id,
            'financial_year_id' => $fy->id,
            'min_individual_deposit' => 30000.00,
            'min_team_deposit' => 300000.00,
            'rd_composition_percentage' => 10,
            'fd_composition_percentage' => 90,
            'calculation_period' => PromotionRule::PERIOD_FINANCIAL_YEAR,
            'qualifying_deposit_statuses' => [Deposit::STATUS_ACTIVE],
            'required_agent_status' => 'active',
            'requires_approval' => 1,
            'authorized_approval_role' => PromotionRule::ROLE_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        $response->assertRedirect(route('superadmin.promotion-rules.index'));
        $rule = PromotionRule::where('from_rank_id', $this->level1->id)
            ->where('to_rank_id', $this->level2->id)
            ->where('financial_year_id', $fy->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($rule);
        $this->assertEquals($fy->id, $rule->financial_year_id);
        $this->assertEquals(PromotionRule::PERIOD_FINANCIAL_YEAR, $rule->calculation_period);
        $this->assertEquals(30000.00, (float) $rule->min_individual_deposit);
        $this->assertEquals(300000.00, (float) $rule->min_team_deposit);

        // 2. Update rule targets while maintaining FY scoping
        $updateResponse = $this->actingAs($this->superAdmin)->put(route('superadmin.promotion-rules.update', $rule), [
            'name' => $rule->name.' Updated',
            'from_rank_id' => $this->level1->id,
            'to_rank_id' => $this->level2->id,
            'financial_year_id' => $fy->id,
            'min_individual_deposit' => 35000.00,
            'min_team_deposit' => 350000.00,
            'rd_composition_percentage' => 15,
            'fd_composition_percentage' => 85,
            'calculation_period' => PromotionRule::PERIOD_FINANCIAL_YEAR,
            'qualifying_deposit_statuses' => [Deposit::STATUS_ACTIVE],
            'required_agent_status' => 'active',
            'requires_approval' => 1,
            'authorized_approval_role' => PromotionRule::ROLE_SUPER_ADMIN,
            'status' => PromotionRule::STATUS_ACTIVE,
        ]);

        $updateResponse->assertRedirect(route('superadmin.promotion-rules.show', $rule));
        $rule->refresh();
        $this->assertEquals($fy->id, $rule->financial_year_id);
        $this->assertEquals(35000.00, (float) $rule->min_individual_deposit);
        $this->assertEquals(350000.00, (float) $rule->min_team_deposit);
        $this->assertEquals(PromotionRule::PERIOD_FINANCIAL_YEAR, $rule->calculation_period);
    }

    public function test_eligibility_service_prioritizes_current_financial_year_rule(): void
    {
        $currentFy = FinancialYear::where('is_current', true)->first();
        $this->assertNotNull($currentFy);

        // Deactivate any existing active rules between level1 and level2
        PromotionRule::where('from_rank_id', $this->level1->id)
            ->where('to_rank_id', $this->level2->id)
            ->update(['status' => PromotionRule::STATUS_INACTIVE]);

        // Create a Global Rule (FY = null) with higher target
        $globalRule = PromotionRule::create([
            'name' => 'Global Baseline Rule '.uniqid(),
            'from_rank_id' => $this->level1->id,
            'to_rank_id' => $this->level2->id,
            'financial_year_id' => null,
            'min_individual_deposit' => 50000.00,
            'min_team_deposit' => 500000.00,
            'rd_composition_percentage' => 10,
            'fd_composition_percentage' => 90,
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'qualifying_deposit_statuses' => [Deposit::STATUS_ACTIVE],
            'required_agent_status' => 'active',
            'status' => PromotionRule::STATUS_ACTIVE,
            'requires_approval' => true,
            'authorized_approval_role' => 'admin',
        ]);

        // Create Current FY Rule with lower target
        $fyRule = PromotionRule::create([
            'name' => 'Current FY Specific Rule '.uniqid(),
            'from_rank_id' => $this->level1->id,
            'to_rank_id' => $this->level2->id,
            'financial_year_id' => $currentFy->id,
            'min_individual_deposit' => 20000.00,
            'min_team_deposit' => 200000.00,
            'rd_composition_percentage' => 10,
            'fd_composition_percentage' => 90,
            'calculation_period' => PromotionRule::PERIOD_FINANCIAL_YEAR,
            'qualifying_deposit_statuses' => [Deposit::STATUS_ACTIVE],
            'required_agent_status' => 'active',
            'status' => PromotionRule::STATUS_ACTIVE,
            'requires_approval' => true,
            'authorized_approval_role' => 'admin',
        ]);

        $agent = $this->createAgentWithUser('FY Priority Agent', $this->level1);

        // Evaluate rule resolution
        $resolvedRule = $this->eligibilityService->findApplicableRule($agent, $this->level2);
        $this->assertNotNull($resolvedRule);
        $this->assertEquals($fyRule->id, $resolvedRule->id, 'Current FY-specific rule must take precedence over global rule');

        // Deactivate FY rule and confirm global fallback
        $fyRule->update(['status' => PromotionRule::STATUS_INACTIVE]);
        $fallbackRule = $this->eligibilityService->findApplicableRule($agent, $this->level2);
        $this->assertNotNull($fallbackRule);
        $this->assertEquals($globalRule->id, $fallbackRule->id, 'Global rule must serve as fallback when no FY rule is active');
    }

    public function test_financial_year_rule_deposit_filters_respect_financial_year_date_boundaries(): void
    {
        $currentFy = FinancialYear::where('is_current', true)->first();
        $this->assertNotNull($currentFy);

        // Deactivate existing rules
        PromotionRule::where('from_rank_id', $this->level1->id)
            ->where('to_rank_id', $this->level2->id)
            ->update(['status' => PromotionRule::STATUS_INACTIVE]);

        $rule = PromotionRule::create([
            'name' => 'FY Boundary Test Rule '.uniqid(),
            'from_rank_id' => $this->level1->id,
            'to_rank_id' => $this->level2->id,
            'financial_year_id' => $currentFy->id,
            'min_individual_deposit' => 20000.00,
            'min_team_deposit' => 50000.00,
            'rd_composition_percentage' => 10,
            'fd_composition_percentage' => 90,
            'apply_tenure_targets' => false,
            'calculation_period' => PromotionRule::PERIOD_FINANCIAL_YEAR,
            'qualifying_deposit_statuses' => [Deposit::STATUS_ACTIVE],
            'required_agent_status' => 'active',
            'status' => PromotionRule::STATUS_ACTIVE,
            'requires_approval' => true,
            'authorized_approval_role' => 'admin',
        ]);

        $agent = $this->createAgentWithUser('FY Boundary Agent', $this->level1);

        // 1. Create a deposit OUTSIDE current FY (e.g. 1 year prior)
        $outsideDate = Carbon::parse($currentFy->start_date)->subMonths(3)->toDateString();
        $outsideDeposit = $this->createDepositForAgent($agent, DepositScheme::TYPE_FD, 1, 30000.00);
        $outsideDeposit->update([
            'start_date' => $outsideDate,
            'created_at' => $outsideDate,
        ]);

        // 2. Create a deposit INSIDE current FY
        $insideDate = Carbon::parse($currentFy->start_date)->addMonths(2)->toDateString();
        $insideDeposit = $this->createDepositForAgent($agent, DepositScheme::TYPE_RD, 1, 25000.00);
        $insideDeposit->update([
            'start_date' => $insideDate,
            'created_at' => $insideDate,
        ]);

        $eval = $this->eligibilityService->evaluateEligibility($agent, $rule);

        // The personal achieved deposit should only include the inside deposit (25,000), not the outside deposit (30,000)
        $this->assertEquals(25000.00, (float) $eval['criteria']['individual_deposit']['actual']);
        $this->assertTrue($eval['criteria']['individual_deposit']['met']);
    }

    public function test_eligibility_service_evaluates_both_team_target_and_individual_target(): void
    {
        // Deactivate existing rules
        PromotionRule::where('from_rank_id', $this->level1->id)
            ->where('to_rank_id', $this->level2->id)
            ->update(['status' => PromotionRule::STATUS_INACTIVE]);

        $rule = PromotionRule::create([
            'name' => 'Team & Individual Dual Target Rule '.uniqid(),
            'from_rank_id' => $this->level1->id,
            'to_rank_id' => $this->level2->id,
            'financial_year_id' => null,
            'min_individual_deposit' => 20000.00,
            'min_team_deposit' => 100000.00,
            'rd_composition_percentage' => 10,
            'fd_composition_percentage' => 90,
            'apply_tenure_targets' => false,
            'calculation_period' => PromotionRule::PERIOD_ALL_TIME,
            'qualifying_deposit_statuses' => [Deposit::STATUS_ACTIVE],
            'required_agent_status' => 'active',
            'status' => PromotionRule::STATUS_ACTIVE,
            'requires_approval' => true,
            'authorized_approval_role' => 'admin',
        ]);

        $leader = $this->createAgentWithUser('Dual Target Leader', $this->level1);
        $downline = $this->createAgentWithUser('Dual Target Downline', $this->level1);
        $downline->update(['parent_agent_id' => $leader->id]);

        // Leader has only 10,000 personal deposit (short of 20,000 personal target)
        $this->createDepositForAgent($leader, DepositScheme::TYPE_RD, 1, 10000.00);
        // Downline has 95,000 deposit -> Total team deposit = 105,000 (exceeds 100,000 team target)
        $this->createDepositForAgent($downline, DepositScheme::TYPE_FD, 1, 95000.00);

        $eval = $this->eligibilityService->evaluateEligibility($leader, $rule);

        $this->assertTrue($eval['criteria']['team_deposit']['met'], 'Team deposit should be met (105k >= 100k)');
        $this->assertFalse($eval['criteria']['individual_deposit']['met'], 'Personal deposit should NOT be met (10k < 20k)');
        $this->assertFalse($eval['is_eligible'], 'Agent must not be eligible when personal target is failed');

        // Now leader adds another 15,000 personal deposit (total personal = 25,000 >= 20,000)
        $this->createDepositForAgent($leader, DepositScheme::TYPE_RD, 1, 15000.00);

        $evalAfter = $this->eligibilityService->evaluateEligibility($leader, $rule);
        $this->assertTrue($evalAfter['criteria']['individual_deposit']['met']);
        $this->assertTrue($evalAfter['criteria']['team_deposit']['met']);
        $this->assertTrue($evalAfter['is_eligible']);
    }
}
