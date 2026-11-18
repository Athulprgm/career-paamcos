<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Commission;
use App\Models\CommissionRule;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositCollection;
use App\Models\DepositScheme;
use App\Models\DepositSchemeCommissionHistory;
use App\Models\DepositSchemeInterestRate;
use App\Models\DepositSchemeRankCommission;
use App\Models\Role;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\FdCommissionSchemeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FdCommissionManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected ?User $branchAdmin = null;

    protected ?User $agentUser = null;

    protected ?Branch $testBranch = null;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Setup Super Admin
        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::create([
            'name' => 'Super Admin Test',
            'email' => 'sa_fd_'.uniqid().'@multicred.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        // 2. Setup Branch & Branch Admin
        $this->testBranch = Branch::firstOrCreate(
            ['code' => 'BR_FD_TEST'],
            ['name' => 'Branch Test FD', 'status' => 'active']
        );

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin', 'status' => 'active']);
        $this->branchAdmin = User::create([
            'name' => 'Branch Admin Test',
            'email' => 'ba_fd_'.uniqid().'@multicred.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->testBranch->id,
        ]);
        $this->branchAdmin->roles()->attach($adminRole->id);

        // 3. Setup Agent User
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $this->agentUser = User::create([
            'name' => 'Agent User Test',
            'email' => 'ag_fd_'.uniqid().'@multicred.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->testBranch->id,
        ]);
        $this->agentUser->roles()->attach($agentRole->id);

        // Ensure at least 3 AgentRanks exist for matrix tests
        for ($i = 1; $i <= 3; $i++) {
            AgentRank::firstOrCreate(['level' => $i], [
                'code' => 'RANK_LVL_'.$i,
                'name' => 'Rank Level '.$i,
            ]);
        }
    }

    public function test_super_admin_can_access_fd_commissions_index(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.fd-commissions.index'));

        $response->assertStatus(200);
        $response->assertSee('FD Commission Management');
        $response->assertSee('Add Commission');
    }

    public function test_non_super_admin_is_forbidden_from_fd_commissions(): void
    {
        // Branch Admin should be denied
        $response = $this->actingAs($this->branchAdmin)
            ->get(route('superadmin.fd-commissions.index'));
        $response->assertStatus(403);

        // Agent should be denied
        $response = $this->actingAs($this->agentUser)
            ->get(route('superadmin.fd-commissions.index'));
        $response->assertStatus(403);

        // Guest should be redirected to login
        auth()->logout();
        $response = $this->get(route('superadmin.fd-commissions.index'));
        $response->assertRedirect(route('superadmin.login'));
    }

    public function test_super_admin_can_view_create_fd_scheme_page(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.fd-commissions.create'));

        $response->assertStatus(200);
        $response->assertSee('Create Fixed Deposit (FD) Scheme');
        $response->assertSee('Rank-wise Commission Configuration');

        // Verify ranks are rendered dynamically in the view
        $ranks = AgentRank::orderBy('sort_order')->take(3)->get();
        foreach ($ranks as $rank) {
            $response->assertSee($rank->name);
        }
    }

    public function test_super_admin_can_create_fd_scheme_with_dynamic_rank_commissions(): void
    {
        $ranks = AgentRank::orderBy('sort_order')->get();
        $this->assertGreaterThan(0, $ranks->count());

        $ranksPayload = [];
        $expectedTotal = '0.00';

        foreach ($ranks as $index => $rank) {
            // Make alternating ranks eligible with varied percentages
            $isEligible = ($index % 2 === 0);
            $pct = $isEligible ? (1.25 * ($index + 1)) : 0.00;

            $ranksPayload[] = [
                'rank_id' => $rank->id,
                'is_eligible' => $isEligible ? '1' : '0',
                'commission_percentage' => $isEligible ? (string) $pct : '',
            ];

            if ($isEligible) {
                $expectedTotal = bcadd($expectedTotal, number_format($pct, 2, '.', ''), 2);
            }
        }

        $code = 'FD_TEST_'.uniqid();
        $payload = [
            'name' => 'FD Special Growth Plan',
            'code' => $code,
            'interest_rate' => '7.85',
            'tenure' => 24,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'min_amount' => 5000,
            'max_amount' => 500000,
            'status' => DepositScheme::STATUS_ACTIVE,
            'description' => 'Test FD Scheme with dynamic rank commissions',
            'notes' => 'Created via unit test',
            'ranks' => $ranksPayload,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.store'), $payload);

        $response->assertSessionHasNoErrors();

        // 1. Check deposit_schemes record
        $scheme = DepositScheme::where('code', $code)->first();
        $this->assertNotNull($scheme);
        $this->assertEquals('FD Special Growth Plan', $scheme->name);
        $this->assertEquals(7.85, (float) $scheme->interest_rate);
        $this->assertEquals(24, $scheme->tenure);
        $this->assertEquals((float) $expectedTotal, (float) $scheme->total_commission_percentage);
        $this->assertTrue($scheme->isFixedDeposit());

        $response->assertRedirect(route('superadmin.fd-commissions.show', $scheme));

        // 2. Check normalized rank commission table
        $this->assertEquals($ranks->count(), $scheme->rankCommissions()->count());
        $eligibleCount = $scheme->rankCommissions()->where('is_eligible', true)->count();
        $this->assertGreaterThan(0, $eligibleCount);

        // 3. Check synced commission rules for calculation
        $syncedRules = CommissionRule::where('deposit_scheme_id', $scheme->id)->get();
        $this->assertEquals($eligibleCount, $syncedRules->where('status', CommissionRule::STATUS_ACTIVE)->count());

        // 4. Check configuration audit history
        $history = DepositSchemeCommissionHistory::where('deposit_scheme_id', $scheme->id)->first();
        $this->assertNotNull($history);
        $this->assertEquals('created', $history->action);
        $this->assertEquals((float) $expectedTotal, (float) $history->total_commission_percentage);
        $this->assertNotNull($history->rank_rules_snapshot);
    }

    public function test_create_rejects_duplicate_scheme_code(): void
    {
        $code = 'DUP_CODE_'.uniqid();
        DepositScheme::create([
            'name' => 'Existing FD Scheme',
            'code' => $code,
            'scheme_type' => DepositScheme::TYPE_FD,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 6.50,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $ranks = AgentRank::take(2)->get();
        $ranksPayload = [];
        foreach ($ranks as $rank) {
            $ranksPayload[] = [
                'rank_id' => $rank->id,
                'is_eligible' => '1',
                'commission_percentage' => '1.50',
            ];
        }

        $payload = [
            'name' => 'Duplicate Code FD Scheme',
            'code' => $code, // Duplicate!
            'interest_rate' => '8.00',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'status' => DepositScheme::STATUS_ACTIVE,
            'ranks' => $ranksPayload,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.store'), $payload);

        $response->assertSessionHasErrors('code');
    }

    public function test_create_rejects_negative_or_invalid_commission(): void
    {
        $ranks = AgentRank::take(2)->get();
        $ranksPayload = [
            [
                'rank_id' => $ranks[0]->id,
                'is_eligible' => '1',
                'commission_percentage' => '-5.00', // Negative!
            ],
        ];

        $payload = [
            'name' => 'Negative Commission Scheme',
            'code' => 'NEG_'.uniqid(),
            'interest_rate' => '7.00',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'status' => DepositScheme::STATUS_ACTIVE,
            'ranks' => $ranksPayload,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.store'), $payload);

        $response->assertSessionHasErrors('ranks.0.commission_percentage');
    }

    public function test_super_admin_can_view_fd_scheme_details_and_matrix(): void
    {
        $service = app(FdCommissionSchemeService::class);
        $ranks = AgentRank::take(3)->get();
        $ranksPayload = [
            ['rank_id' => $ranks[0]->id, 'is_eligible' => true, 'commission_percentage' => 1.50],
            ['rank_id' => $ranks[1]->id, 'is_eligible' => true, 'commission_percentage' => 2.00],
            ['rank_id' => $ranks[2]->id, 'is_eligible' => false, 'commission_percentage' => 0.00],
        ];

        $scheme = $service->createScheme([
            'name' => 'View Test Scheme',
            'code' => 'VTS_'.uniqid(),
            'interest_rate' => 7.20,
            'tenure' => 18,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'status' => DepositScheme::STATUS_ACTIVE,
        ], $ranksPayload, $this->superAdmin->id);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.fd-commissions.show', $scheme));

        $response->assertStatus(200);
        $response->assertSee('View Test Scheme');
        $response->assertSee($scheme->code);
        $response->assertSee('3.50%'); // 1.50 + 2.00 = 3.50
        $response->assertSee($ranks[0]->name);
        $response->assertSee('Eligible');
        $response->assertSee('Ineligible');
    }

    public function test_super_admin_can_edit_fd_scheme_and_rank_percentages(): void
    {
        $service = app(FdCommissionSchemeService::class);
        $ranks = AgentRank::take(2)->get();
        $ranksPayload = [
            ['rank_id' => $ranks[0]->id, 'is_eligible' => true, 'commission_percentage' => 1.00],
            ['rank_id' => $ranks[1]->id, 'is_eligible' => true, 'commission_percentage' => 1.50],
        ];

        $scheme = $service->createScheme([
            'name' => 'Initial Scheme Name',
            'code' => 'EDIT_'.uniqid(),
            'interest_rate' => 7.00,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'status' => DepositScheme::STATUS_ACTIVE,
        ], $ranksPayload, $this->superAdmin->id);

        $this->assertEquals(2.50, (float) $scheme->total_commission_percentage);

        // Edit scheme: update interest rate to 8.25% and rank commissions to 2.00% + 2.50% = 4.50%
        $updatedRanksPayload = [
            ['rank_id' => $ranks[0]->id, 'is_eligible' => '1', 'commission_percentage' => '2.00'],
            ['rank_id' => $ranks[1]->id, 'is_eligible' => '1', 'commission_percentage' => '2.50'],
        ];

        $updatePayload = [
            'name' => 'Updated Scheme Name',
            'code' => $scheme->code,
            'interest_rate' => '8.25',
            'tenure' => 36,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'status' => DepositScheme::STATUS_ACTIVE,
            'notes' => 'Increased rate for premium campaign',
            'ranks' => $updatedRanksPayload,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->put(route('superadmin.fd-commissions.update', $scheme), $updatePayload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('superadmin.fd-commissions.show', $scheme));

        $scheme->refresh();
        $this->assertEquals('Updated Scheme Name', $scheme->name);
        $this->assertEquals(8.25, (float) $scheme->interest_rate);
        $this->assertEquals(36, $scheme->tenure);
        $this->assertEquals(4.50, (float) $scheme->total_commission_percentage);

        // Verify configuration audit history logged 'updated' action
        $updateHistory = DepositSchemeCommissionHistory::where('deposit_scheme_id', $scheme->id)
            ->where('action', 'updated')
            ->first();
        $this->assertNotNull($updateHistory);
        $this->assertEquals(4.50, (float) $updateHistory->total_commission_percentage);
        $this->assertEquals('Increased rate for premium campaign', $updateHistory->notes);
    }

    public function test_super_admin_can_toggle_scheme_status(): void
    {
        $service = app(FdCommissionSchemeService::class);
        $ranks = AgentRank::take(1)->get();
        $scheme = $service->createScheme([
            'name' => 'Toggle Scheme',
            'code' => 'TOGGLE_'.uniqid(),
            'interest_rate' => 7.00,
            'tenure' => 12,
            'status' => DepositScheme::STATUS_ACTIVE,
        ], [
            ['rank_id' => $ranks[0]->id, 'is_eligible' => true, 'commission_percentage' => 1.50],
        ], $this->superAdmin->id);

        $this->assertTrue($scheme->isActive());

        // Toggle to INACTIVE
        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.toggle-status', $scheme));

        $response->assertRedirect();
        $scheme->refresh();
        $this->assertFalse($scheme->isActive());
        $this->assertEquals(DepositScheme::STATUS_INACTIVE, $scheme->status);

        // Verify synced commission rules are also INACTIVE
        $rule = CommissionRule::where('deposit_scheme_id', $scheme->id)->first();
        $this->assertEquals(CommissionRule::STATUS_INACTIVE, $rule->status);

        // Toggle back to ACTIVE
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.toggle-status', $scheme));

        $scheme->refresh();
        $this->assertTrue($scheme->isActive());
    }

    public function test_safe_deletion_prevents_deleting_scheme_with_existing_deposits(): void
    {
        $service = app(FdCommissionSchemeService::class);
        $ranks = AgentRank::take(1)->get();
        $scheme = $service->createScheme([
            'name' => 'Safe Delete Test Scheme',
            'code' => 'SAFE_'.uniqid(),
            'interest_rate' => 7.00,
            'tenure' => 12,
            'status' => DepositScheme::STATUS_ACTIVE,
        ], [
            ['rank_id' => $ranks[0]->id, 'is_eligible' => true, 'commission_percentage' => 1.50],
        ], $this->superAdmin->id);

        $agent = Agent::create([
            'user_id' => $this->agentUser->id,
            'member_code' => 'AG_SD_'.rand(1000, 9999),
            'agent_id' => 'AG_SD_'.rand(1000, 9999),
            'name' => 'Agent Safe Del',
            'email' => 'agent_sd_'.uniqid().'@test.com',
            'phone' => '98'.rand(10000000, 99999999),
            'branch_id' => $this->testBranch->id,
            'rank_id' => $ranks[0]->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Customer Safe Del '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '98'.rand(10000000, 99999999),
            'address' => '123 Test Society Road',
            'pin_code' => '682001',
            'date_of_birth' => '1990-01-01',
            'branch_id' => $this->testBranch->id,
            'agent_id' => $agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        Deposit::create([
            'deposit_number' => 'DEP_SAFE_'.uniqid(),
            'customer_id' => $customer->id,
            'branch_id' => $this->testBranch->id,
            'agent_id' => $agent->id,
            'deposit_scheme_id' => $scheme->id,
            'amount' => 50000,
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.00,
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        // Attempt deletion
        $response = $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.fd-commissions.destroy', $scheme));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('deposit_schemes', [
            'id' => $scheme->id,
            'deleted_at' => null,
        ]);
    }

    public function test_safe_deletion_succeeds_when_no_financial_records_exist(): void
    {
        $service = app(FdCommissionSchemeService::class);
        $ranks = AgentRank::take(1)->get();
        $scheme = $service->createScheme([
            'name' => 'Unused FD Scheme',
            'code' => 'UNUSED_'.uniqid(),
            'interest_rate' => 7.00,
            'tenure' => 12,
            'status' => DepositScheme::STATUS_ACTIVE,
        ], [
            ['rank_id' => $ranks[0]->id, 'is_eligible' => true, 'commission_percentage' => 1.50],
        ], $this->superAdmin->id);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.fd-commissions.destroy', $scheme));

        $response->assertSessionHas('success');
        $response->assertRedirect(route('superadmin.fd-commissions.index'));

        // Assert soft deleted
        $this->assertSoftDeleted('deposit_schemes', ['id' => $scheme->id]);
    }

    public function test_commission_calculation_uses_configured_scheme_rank_rate(): void
    {
        $service = app(FdCommissionSchemeService::class);
        $ranks = AgentRank::orderBy('sort_order')->take(2)->get();
        $eligibleRank = $ranks[0];
        $ineligibleRank = $ranks[1];

        // Eligible Rank = 2.50%, Ineligible Rank = 0.00%
        $scheme = $service->createScheme([
            'name' => 'FD High Yield 2026',
            'code' => 'FD_HY_'.uniqid(),
            'interest_rate' => 8.00,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'status' => DepositScheme::STATUS_ACTIVE,
        ], [
            ['rank_id' => $eligibleRank->id, 'is_eligible' => true, 'commission_percentage' => 2.50],
            ['rank_id' => $ineligibleRank->id, 'is_eligible' => false, 'commission_percentage' => 0.00],
        ], $this->superAdmin->id);

        $agent = Agent::create([
            'user_id' => $this->agentUser->id,
            'member_code' => 'AG_CALC_'.rand(1000, 9999),
            'agent_id' => 'AG_CALC_'.rand(1000, 9999),
            'name' => 'Agent Calc',
            'email' => 'agent_calc_'.uniqid().'@test.com',
            'phone' => '98'.rand(10000000, 99999999),
            'branch_id' => $this->testBranch->id,
            'rank_id' => $eligibleRank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Customer Calc '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '98'.rand(10000000, 99999999),
            'address' => '123 Test Society Road',
            'pin_code' => '682001',
            'date_of_birth' => '1990-01-01',
            'branch_id' => $this->testBranch->id,
            'agent_id' => $agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $deposit = Deposit::create([
            'deposit_number' => 'DEP_COMM_'.uniqid(),
            'customer_id' => $customer->id,
            'branch_id' => $this->testBranch->id,
            'agent_id' => $agent->id,
            'deposit_scheme_id' => $scheme->id,
            'amount' => 100000.00,
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.00,
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        $collection = DepositCollection::create([
            'collection_number' => 'COL_'.uniqid(),
            'deposit_id' => $deposit->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->testBranch->id,
            'agent_id' => $agent->id,
            'installment_number' => 1,
            'due_date' => now()->toDateString(),
            'due_amount' => 100000.00,
            'paid_amount' => 100000.00,
            'status' => DepositCollection::STATUS_PAID,
        ]);

        $commissionService = app(CommissionService::class);
        $eligibility = $commissionService->isEligibleForCommission($collection);

        $this->assertTrue($eligibility['eligible']);
        $this->assertNotNull($eligibility['rule']);
        $this->assertEquals(2.50, (float) $eligibility['rule']->rate);

        // Generate commission
        $commission = $commissionService->generateForCollection($collection);

        $this->assertNotNull($commission);
        $this->assertEquals(2.50, (float) $commission->commission_rate);
        $this->assertEquals(100000.00, (float) $commission->base_amount);
        // ₹100,000 * 2.5% = ₹2,500.00
        $this->assertEquals(2500.00, (float) $commission->commission_amount);
        $this->assertEquals(Commission::STATUS_PENDING, $commission->status);
    }

    public function test_ineligible_rank_does_not_receive_commission_under_scheme(): void
    {
        $service = app(FdCommissionSchemeService::class);
        $ranks = AgentRank::orderBy('sort_order')->take(2)->get();
        $eligibleRank = $ranks[0];
        $ineligibleRank = $ranks[1];

        // Rank 1 is eligible, Rank 2 is explicitly ineligible
        $scheme = $service->createScheme([
            'name' => 'FD Exclusivity Plan',
            'code' => 'FD_EXC_'.uniqid(),
            'interest_rate' => 7.50,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'status' => DepositScheme::STATUS_ACTIVE,
        ], [
            ['rank_id' => $eligibleRank->id, 'is_eligible' => true, 'commission_percentage' => 3.00],
            ['rank_id' => $ineligibleRank->id, 'is_eligible' => false, 'commission_percentage' => 0.00],
        ], $this->superAdmin->id);

        // Agent has ineligible rank
        $agent = Agent::create([
            'user_id' => $this->agentUser->id,
            'member_code' => 'AG_INEL_'.rand(1000, 9999),
            'agent_id' => 'AG_INEL_'.rand(1000, 9999),
            'name' => 'Agent Ineligible',
            'email' => 'agent_inel_'.uniqid().'@test.com',
            'phone' => '98'.rand(10000000, 99999999),
            'branch_id' => $this->testBranch->id,
            'rank_id' => $ineligibleRank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Customer Inel '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '98'.rand(10000000, 99999999),
            'address' => '123 Test Society Road',
            'pin_code' => '682001',
            'date_of_birth' => '1990-01-01',
            'branch_id' => $this->testBranch->id,
            'agent_id' => $agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $deposit = Deposit::create([
            'deposit_number' => 'DEP_INEL_'.uniqid(),
            'customer_id' => $customer->id,
            'branch_id' => $this->testBranch->id,
            'agent_id' => $agent->id,
            'deposit_scheme_id' => $scheme->id,
            'amount' => 50000.00,
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.50,
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        $collection = DepositCollection::create([
            'collection_number' => 'COL_INEL_'.uniqid(),
            'deposit_id' => $deposit->id,
            'customer_id' => $customer->id,
            'branch_id' => $this->testBranch->id,
            'agent_id' => $agent->id,
            'installment_number' => 1,
            'due_date' => now()->toDateString(),
            'due_amount' => 50000.00,
            'paid_amount' => 50000.00,
            'status' => DepositCollection::STATUS_PAID,
        ]);

        $commissionService = app(CommissionService::class);
        $eligibility = $commissionService->isEligibleForCommission($collection);

        // Must be ineligible because the agent's rank is not eligible in this scheme
        $this->assertFalse($eligibility['eligible']);
    }

    public function test_super_admin_can_view_create_page_with_deposit_schemes_selector(): void
    {
        $existingScheme = DepositScheme::create([
            'name' => 'Catalog Growth Scheme '.uniqid(),
            'code' => 'CAT_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 8.25,
            'tenure' => 24,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'min_amount' => 5000,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.fd-commissions.create', ['deposit_scheme_id' => $existingScheme->id]));

        $response->assertStatus(200);
        $response->assertSee('Fetch from Existing Deposit Scheme');
        $response->assertSee($existingScheme->name);
        $response->assertSee($existingScheme->code);
    }

    public function test_super_admin_can_fetch_scheme_details_via_json_endpoint(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'AJAX Scheme '.uniqid(),
            'code' => 'AJAX_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 7.50,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'min_amount' => 1000,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.fd-commissions.scheme-details', $scheme));

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $scheme->id,
            'name' => $scheme->name,
            'code' => $scheme->code,
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 7.50,
            'tenure' => 12,
        ]);
    }

    public function test_super_admin_can_update_commissions_for_existing_deposit_scheme(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Pre-existing Catalog Scheme '.uniqid(),
            'code' => 'EXIST_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 7.75,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'min_amount' => 2000,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $ranks = AgentRank::orderBy('sort_order')->get();
        $this->assertNotEmpty($ranks);

        $ranksPayload = [];
        foreach ($ranks as $index => $rank) {
            $ranksPayload[] = [
                'rank_id' => $rank->id,
                'is_eligible' => '1',
                'commission_percentage' => '1.50',
            ];
        }

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.store'), [
                'deposit_scheme_id' => $scheme->id,
                'name' => $scheme->name,
                'code' => $scheme->code,
                'scheme_type' => $scheme->scheme_type,
                'interest_rate' => '7.75',
                'tenure' => 12,
                'tenure_unit' => DepositScheme::TENURE_MONTHS,
                'min_amount' => 2000,
                'status' => DepositScheme::STATUS_ACTIVE,
                'ranks' => $ranksPayload,
                'notes' => 'Configured rank commissions for existing scheme.',
            ]);

        $response->assertRedirect(route('superadmin.fd-commissions.show', $scheme));

        $this->assertDatabaseHas('deposit_scheme_rank_commissions', [
            'deposit_scheme_id' => $scheme->id,
            'commission_percentage' => '1.50',
            'is_eligible' => 1,
        ]);

        $expectedTotal = bcmul('1.50', (string) $ranks->count(), 2);
        $this->assertEquals((float) $expectedTotal, (float) $scheme->fresh()->total_commission_percentage);
    }

    public function test_super_admin_can_dynamically_create_multiple_schemes_of_same_type_without_conflicts(): void
    {
        $ranks = AgentRank::orderBy('sort_order')->get();
        $this->assertNotEmpty($ranks);

        $ranksPayload = [];
        foreach ($ranks as $rank) {
            $ranksPayload[] = [
                'rank_id' => $rank->id,
                'is_eligible' => '1',
                'commission_percentage' => '1.00',
            ];
        }

        // 1. Create FD 1 Year
        $code1 = 'FD1Y_'.uniqid();
        $response1 = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.store'), [
                'name' => 'FD 1 Year Regular',
                'code' => $code1,
                'scheme_type' => DepositScheme::TYPE_FD,
                'interest_rate' => '7.00',
                'tenure' => 1,
                'tenure_unit' => DepositScheme::TENURE_YEARS,
                'min_amount' => 1000,
                'status' => DepositScheme::STATUS_ACTIVE,
                'ranks' => $ranksPayload,
            ]);
        $response1->assertSessionHasNoErrors();

        // 2. Create FD 2 Year (same scheme type 'FD', different tenure)
        $code2 = 'FD2Y_'.uniqid();
        $response2 = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.store'), [
                'name' => 'FD 2 Year Growth',
                'code' => $code2,
                'scheme_type' => DepositScheme::TYPE_FD,
                'interest_rate' => '7.50',
                'tenure' => 2,
                'tenure_unit' => DepositScheme::TENURE_YEARS,
                'min_amount' => 1000,
                'status' => DepositScheme::STATUS_ACTIVE,
                'ranks' => $ranksPayload,
            ]);
        $response2->assertSessionHasNoErrors();

        // Verify both coexist in the database as type 'FD'
        $scheme1 = DepositScheme::where('code', $code1)->first();
        $scheme2 = DepositScheme::where('code', $code2)->first();

        $this->assertNotNull($scheme1);
        $this->assertNotNull($scheme2);
        $this->assertNotEquals($scheme1->id, $scheme2->id);

        $this->assertEquals(DepositScheme::TYPE_FD, $scheme1->scheme_type);
        $this->assertEquals(DepositScheme::TYPE_FD, $scheme2->scheme_type);
        $this->assertEquals(1, $scheme1->tenure);
        $this->assertEquals(2, $scheme2->tenure);

        // Verify both have independent rank commission records
        $this->assertDatabaseHas('deposit_scheme_rank_commissions', ['deposit_scheme_id' => $scheme1->id]);
        $this->assertDatabaseHas('deposit_scheme_rank_commissions', ['deposit_scheme_id' => $scheme2->id]);
    }

    public function test_super_admin_can_clone_and_create_new_variant_from_existing_scheme_without_overwriting(): void
    {
        $original = DepositScheme::create([
            'name' => 'FD 1 Year Base '.uniqid(),
            'code' => 'FD1_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 7.00,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 1000,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $ranks = AgentRank::orderBy('sort_order')->get();
        $ranksPayload = [];
        foreach ($ranks as $rank) {
            $ranksPayload[] = [
                'rank_id' => $rank->id,
                'is_eligible' => '1',
                'commission_percentage' => '1.20',
            ];
        }

        $newCode = 'FD2_'.uniqid();
        $newName = 'FD 2 Year Variant '.uniqid();

        // Post with prefilled deposit_scheme_id, but action_mode = 'create_new'
        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.store'), [
                'deposit_scheme_id' => $original->id,
                'action_mode' => 'create_new',
                'name' => $newName,
                'code' => $newCode,
                'scheme_type' => DepositScheme::TYPE_FD,
                'interest_rate' => '7.75',
                'tenure' => 2,
                'tenure_unit' => DepositScheme::TENURE_YEARS,
                'min_amount' => 1000,
                'status' => DepositScheme::STATUS_ACTIVE,
                'ranks' => $ranksPayload,
            ]);

        $response->assertSessionHasNoErrors();

        // Original scheme must NOT be overwritten
        $freshOriginal = $original->fresh();
        $this->assertEquals(1, $freshOriginal->tenure);
        $this->assertNotEquals($newName, $freshOriginal->name);

        // A new scheme must exist
        $newScheme = DepositScheme::where('code', $newCode)->first();
        $this->assertNotNull($newScheme);
        $this->assertNotEquals($original->id, $newScheme->id);
        $this->assertEquals($newName, $newScheme->name);
        $this->assertEquals(2, $newScheme->tenure);
    }

    public function test_edit_page_renders_with_year_selection_and_existing_rank_commissions(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'FD Multi-Year Scheme '.uniqid(),
            'code' => 'FD_MY_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 7.50,
            'tenure' => 3,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 1000,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $ranks = AgentRank::take(2)->get();
        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $ranks[0]->id,
            'commission_percentage' => 2.50,
            'is_eligible' => true,
            'payout_year' => 1,
        ]);
        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $ranks[1]->id,
            'commission_percentage' => 3.00,
            'is_eligible' => true,
            'payout_year' => 2,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.fd-commissions.edit', $scheme));

        $response->assertOk();
        $response->assertSee('id="yearSelectionCard"', false);
        $response->assertSee('id="btnAddYear"', false);
        $response->assertSee('id="yearTabList"', false);
        $response->assertSee('id="payoutYearInput"', false);
        $response->assertSee('id="yearsDataInput"', false);
        $response->assertViewHas('activeYears', [1, 2]);
        $response->assertViewHas('rankCommissionsByYear');
    }

    public function test_edit_page_does_not_render_year_selection_card_for_single_year_scheme(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'FD Single Year Test '.uniqid(),
            'code' => 'FD_SN_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 7.50,
            'tenure' => 5,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 1000,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $ranks = AgentRank::take(2)->get();
        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $ranks[0]->id,
            'commission_percentage' => 2.50,
            'is_eligible' => true,
            'payout_year' => 5,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.fd-commissions.edit', $scheme));

        $response->assertOk();
        $response->assertDontSee('id="yearSelectionCard"', false);
        $response->assertDontSee('Payout Year Multi-Tier Commission');
        $response->assertSee('id="payoutYearInput"', false);
    }

    public function test_super_admin_can_update_multi_year_commissions_via_edit(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'FD Multi-Year Test '.uniqid(),
            'code' => 'FD_UP_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 7.50,
            'tenure' => 2,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 1000,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $ranks = AgentRank::take(2)->get();

        $yearsData = [
            1 => [
                ['rank_id' => $ranks[0]->id, 'is_eligible' => true, 'commission_percentage' => '1.50'],
                ['rank_id' => $ranks[1]->id, 'is_eligible' => true, 'commission_percentage' => '2.50'],
            ],
            2 => [
                ['rank_id' => $ranks[0]->id, 'is_eligible' => true, 'commission_percentage' => '2.00'],
                ['rank_id' => $ranks[1]->id, 'is_eligible' => true, 'commission_percentage' => '3.50'],
            ],
        ];

        $payload = [
            'name' => $scheme->name.' Updated',
            'code' => $scheme->code,
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => '8.00',
            'tenure' => 2,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 1000,
            'status' => DepositScheme::STATUS_ACTIVE,
            'payout_year' => 1,
            'ranks' => [
                ['rank_id' => $ranks[0]->id, 'is_eligible' => '1', 'commission_percentage' => '1.50'],
                ['rank_id' => $ranks[1]->id, 'is_eligible' => '1', 'commission_percentage' => '2.50'],
            ],
            'years_data' => json_encode($yearsData),
            'notes' => 'Updated multi-year rates',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->put(route('superadmin.fd-commissions.update', $scheme), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('superadmin.fd-commissions.show', $scheme));

        $this->assertDatabaseHas('deposit_scheme_rank_commissions', [
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $ranks[0]->id,
            'payout_year' => 1,
            'commission_percentage' => '1.50',
        ]);
        $this->assertDatabaseHas('deposit_scheme_rank_commissions', [
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $ranks[1]->id,
            'payout_year' => 1,
            'commission_percentage' => '2.50',
        ]);
        $this->assertDatabaseHas('deposit_scheme_rank_commissions', [
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $ranks[0]->id,
            'payout_year' => 2,
            'commission_percentage' => '2.00',
        ]);
        $this->assertDatabaseHas('deposit_scheme_rank_commissions', [
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $ranks[1]->id,
            'payout_year' => 2,
            'commission_percentage' => '3.50',
        ]);
    }

    public function test_super_admin_can_configure_and_save_commissions_beyond_four_years(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'RD 5 Year Multi-Year '.uniqid(),
            'code' => 'RD5Y_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_RD,
            'interest_rate' => 7.50,
            'tenure' => 5,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 500,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $ranks = AgentRank::take(2)->get();

        $yearsData = [
            1 => [
                ['rank_id' => $ranks[0]->id, 'is_eligible' => true, 'commission_percentage' => '1.00'],
            ],
            4 => [
                ['rank_id' => $ranks[0]->id, 'is_eligible' => true, 'commission_percentage' => '1.50'],
            ],
            5 => [
                ['rank_id' => $ranks[0]->id, 'is_eligible' => true, 'commission_percentage' => '2.00'],
            ],
            6 => [
                ['rank_id' => $ranks[0]->id, 'is_eligible' => true, 'commission_percentage' => '2.50'],
            ],
        ];

        $payload = [
            'name' => $scheme->name,
            'code' => $scheme->code,
            'scheme_type' => DepositScheme::TYPE_RD,
            'interest_rate' => '7.50',
            'tenure' => 5,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 500,
            'status' => DepositScheme::STATUS_ACTIVE,
            'payout_year' => 5,
            'ranks' => [
                ['rank_id' => $ranks[0]->id, 'is_eligible' => '1', 'commission_percentage' => '2.00'],
                ['rank_id' => $ranks[1]->id, 'is_eligible' => '0', 'commission_percentage' => '0.00'],
            ],
            'years_data' => json_encode($yearsData),
            'notes' => 'Configured Year 5 and 6 commissions',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->put(route('superadmin.fd-commissions.update', $scheme), $payload);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('superadmin.fd-commissions.show', $scheme));

        $this->assertDatabaseHas('deposit_scheme_rank_commissions', [
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $ranks[0]->id,
            'payout_year' => 5,
            'commission_percentage' => '2.00',
        ]);
        $this->assertDatabaseHas('deposit_scheme_rank_commissions', [
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $ranks[0]->id,
            'payout_year' => 6,
            'commission_percentage' => '2.50',
        ]);
    }

    public function test_customer_five_year_deposit_calculates_commission_from_year_one_to_year_five(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'FD 5 Year Multi-Year Samrudhi '.uniqid(),
            'code' => 'FD5Y_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 8.50,
            'tenure' => 5,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 5000,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $rank = AgentRank::first();
        $this->assertNotNull($rank);

        // Configure different commission percentages for Year 1 through Year 5
        $yearRates = [
            1 => 5.00,
            2 => 4.00,
            3 => 3.00,
            4 => 2.00,
            5 => 1.00,
        ];

        foreach ($yearRates as $yr => $rate) {
            DepositSchemeRankCommission::create([
                'deposit_scheme_id' => $scheme->id,
                'rank_id' => $rank->id,
                'payout_year' => $yr,
                'commission_percentage' => $rate,
                'is_eligible' => true,
            ]);
        }

        // Create Agent & Customer
        $agent = Agent::create([
            'user_id' => $this->agentUser->id,
            'member_code' => 'AG_MY_'.rand(1000, 9999),
            'agent_id' => 'AG_MY_'.rand(1000, 9999),
            'name' => 'Multi-Year Sourcing Agent',
            'email' => 'agent_my_'.uniqid().'@example.com',
            'phone' => '97'.rand(10000000, 99999999),
            'branch_id' => $this->testBranch->id,
            'rank_id' => $rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'name' => 'Multi-Year Customer Test',
            'phone' => '98'.rand(10000000, 99999999),
            'email' => 'cust_my_'.uniqid().'@example.com',
            'customer_number' => 'CUST_MY_'.uniqid(),
            'branch_id' => $this->testBranch->id,
            'agent_id' => $agent->id,
            'status' => 'active',
        ]);

        // Customer selects 5-year deposit
        $deposit = Deposit::create([
            'deposit_number' => 'DEP_5Y_'.uniqid(),
            'customer_id' => $customer->id,
            'agent_id' => $agent->id,
            'branch_id' => $this->testBranch->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 100000.00,
            'interest_rate' => $scheme->interest_rate,
            'tenure' => 5,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'start_date' => now()->toDateString(),
            'deposit_date' => now()->toDateString(),
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        $this->assertEquals(5, $deposit->getTenureInYears());

        // Calculate deposit commission (exact 5-year tenure commission only)
        $commissions = app(CommissionService::class)->calculateAndCreateForDeposit($deposit, $this->superAdmin);

        // Verify that commission is calculated ONLY for the exact deposit tenure (Year 5 only, never accumulating Years 1-4)
        $this->assertCount(1, $commissions);

        $savedCommissions = Commission::where('deposit_id', $deposit->id)
            ->where('agent_id', $agent->id)
            ->orderBy('payout_year')
            ->get();

        $this->assertCount(1, $savedCommissions);

        $comm = $savedCommissions->first();
        $this->assertEquals(5, $comm->payout_year);
        $this->assertEquals(1.00, (float) $comm->rate);
        $this->assertEquals(1000.00, (float) $comm->commission_amount);
        $this->assertEquals(Commission::STATUS_PENDING, $comm->status);

        // Verify Year 1 to 4 were NEVER accumulated
        for ($prevYear = 1; $prevYear <= 4; $prevYear++) {
            $this->assertEquals(0, Commission::where('deposit_id', $deposit->id)->where('payout_year', $prevYear)->count());
        }
    }

    public function test_create_page_and_scheme_details_endpoint_include_senior_citizen_extra_rate(): void
    {
        $fdScheme = DepositScheme::create([
            'name' => 'FD Senior Citizens Check '.uniqid(),
            'code' => 'FDSC_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 9.75,
            'senior_citizen_extra_rate' => 0.25,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'min_amount' => 5000,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $doubleScheme = DepositScheme::create([
            'name' => 'FD Double Check '.uniqid(),
            'code' => 'FDDBL_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 0.00,
            'senior_citizen_extra_rate' => 0.00,
            'is_double' => true,
            'tenure' => 72,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'min_amount' => 5000,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $createResponse = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.fd-commissions.create'));

        $createResponse->assertOk();
        $createResponse->assertSee('name="senior_citizen_extra_rate"', false);
        $createResponse->assertSee('id="seniorRateSummaryBar"', false);
        $createResponse->assertSee('"senior_citizen_extra_rate":0.25', false);
        $createResponse->assertSee('"is_double":true', false);

        $detailsResponse = $this->actingAs($this->superAdmin)
            ->getJson(route('superadmin.fd-commissions.scheme-details', $fdScheme));

        $detailsResponse->assertOk()
            ->assertJson([
                'id' => $fdScheme->id,
                'interest_rate' => 9.75,
                'senior_citizen_extra_rate' => 0.25,
                'is_double' => false,
            ]);

        $doubleDetailsResponse = $this->actingAs($this->superAdmin)
            ->getJson(route('superadmin.fd-commissions.scheme-details', $doubleScheme));

        $doubleDetailsResponse->assertOk()
            ->assertJson([
                'id' => $doubleScheme->id,
                'is_double' => true,
            ]);
    }

    public function test_store_and_update_persist_senior_citizen_extra_rate_and_sync_existing_interest_rate_row(): void
    {
        $rank = AgentRank::first();
        $this->assertNotNull($rank);

        $uniqueCode = 'FDSC_'.strtoupper(substr(uniqid(), -6));

        $storePayload = [
            'action_mode' => 'create_new',
            'name' => 'FD Senior Rate Test '.uniqid(),
            'code' => $uniqueCode,
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => '9.25',
            'senior_citizen_extra_rate' => '0.50',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'min_amount' => 5000,
            'status' => DepositScheme::STATUS_ACTIVE,
            'payout_year' => 1,
            'ranks' => [
                ['rank_id' => $rank->id, 'is_eligible' => '1', 'commission_percentage' => '2.00'],
            ],
        ];

        $storeResponse = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.store'), $storePayload);

        $storeResponse->assertSessionHasNoErrors();

        $created = DepositScheme::where('code', $uniqueCode)->firstOrFail();
        $this->assertEquals(0.50, (float) $created->senior_citizen_extra_rate);
        $this->assertEquals(0, $created->interestRates()->count());

        $invalidPayload = array_merge($storePayload, [
            'senior_citizen_extra_rate' => '12.50',
        ]);
        $this->actingAs($this->superAdmin)
            ->post(route('superadmin.fd-commissions.store'), $invalidPayload)
            ->assertSessionHasErrors('senior_citizen_extra_rate');

        $rateTier = DepositSchemeInterestRate::create([
            'deposit_scheme_id' => $created->id,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 9.25,
            'senior_citizen_extra_rate' => 0.50,
            'status' => DepositSchemeInterestRate::STATUS_ACTIVE,
        ]);

        $updatePayload = [
            'name' => $created->name,
            'code' => $created->code,
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => '9.50',
            'senior_citizen_extra_rate' => '0.75',
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'min_amount' => 5000,
            'status' => DepositScheme::STATUS_ACTIVE,
            'payout_year' => 1,
            'ranks' => [
                ['rank_id' => $rank->id, 'is_eligible' => '1', 'commission_percentage' => '2.50'],
            ],
        ];

        $updateResponse = $this->actingAs($this->superAdmin)
            ->put(route('superadmin.fd-commissions.update', $created), $updatePayload);

        $updateResponse->assertSessionHasNoErrors();
        $updateResponse->assertRedirect(route('superadmin.fd-commissions.show', $created));

        $this->assertEquals(0.75, (float) $created->fresh()->senior_citizen_extra_rate);
        $this->assertEquals(0.75, (float) $rateTier->fresh()->senior_citizen_extra_rate);
        $this->assertEquals(9.50, (float) $rateTier->fresh()->interest_rate);
    }

    public function test_edit_and_show_pages_display_senior_citizen_rate_and_delete_safety_state(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'FD Senior Display '.uniqid(),
            'code' => 'FDSD_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 9.75,
            'senior_citizen_extra_rate' => 0.25,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'min_amount' => 5000,
            'total_commission_percentage' => 8.00,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $editResponse = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.fd-commissions.edit', $scheme));

        $editResponse->assertOk();
        $editResponse->assertSee('name="senior_citizen_extra_rate"', false);
        $editResponse->assertSee('0.25');
        $editResponse->assertSee('10.00% p.a.');

        $showResponse = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.fd-commissions.show', $scheme));

        $showResponse->assertOk();
        $showResponse->assertSee('Senior: 10.00% (+0.25%)');
        $showResponse->assertSee('Senior Citizen Effective');
        $showResponse->assertSee('can be deleted safely');
    }

    public function test_index_displays_senior_citizen_rates_exact_payout_year_and_config_status_filter(): void
    {
        $tag = 'IDXTEST_'.uniqid();
        $rank = AgentRank::first();
        $this->assertNotNull($rank);

        $fd2y = DepositScheme::create([
            'name' => "{$tag} FD 2 Year",
            'code' => 'FD2T_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 10.25,
            'senior_citizen_extra_rate' => 0.25,
            'tenure' => 24,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'min_amount' => 5000,
            'total_commission_percentage' => 9.00,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $fd2y->id,
            'rank_id' => $rank->id,
            'payout_year' => 2,
            'commission_percentage' => 9.00,
            'is_eligible' => true,
        ]);

        $fdDouble = DepositScheme::create([
            'name' => "{$tag} FD Double 6Y",
            'code' => 'FD6D_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 0.00,
            'senior_citizen_extra_rate' => 0.00,
            'is_double' => true,
            'tenure' => 72,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'min_amount' => 5000,
            'total_commission_percentage' => 13.00,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $fdDouble->id,
            'rank_id' => $rank->id,
            'payout_year' => 6,
            'commission_percentage' => 13.00,
            'is_eligible' => true,
        ]);

        $rdUnconfigured = DepositScheme::create([
            'name' => "{$tag} RD Unconfigured",
            'code' => 'RDU_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_RD,
            'interest_rate' => 8.00,
            'senior_citizen_extra_rate' => 0.00,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'min_amount' => 500,
            'total_commission_percentage' => 0.00,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $allResponse = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.fd-commissions.index', ['search' => $tag]));

        $allResponse->assertOk();
        $allResponse->assertSee('10.50%');
        $allResponse->assertSee('+0.25%');
        $allResponse->assertSee('Double Feature');
        $allResponse->assertSee('Year 2');
        $allResponse->assertSee('Year 6');
        $allResponse->assertSee('Not Configured');

        $configuredResponse = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.fd-commissions.index', ['search' => $tag, 'commission_status' => 'configured']));

        $configuredResponse->assertOk();
        $configuredResponse->assertSee($fd2y->code);
        $configuredResponse->assertSee($fdDouble->code);
        $configuredResponse->assertDontSee($rdUnconfigured->code);

        $unconfiguredResponse = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.fd-commissions.index', ['search' => $tag, 'commission_status' => 'unconfigured']));

        $unconfiguredResponse->assertOk();
        $unconfiguredResponse->assertSee($rdUnconfigured->code);
        $unconfiguredResponse->assertDontSee($fd2y->code);
    }
}
