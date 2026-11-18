<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\AgentTargetAssignment;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\FinancialYear;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PremiumBankingDashboardTest extends TestCase
{
    use DatabaseTransactions;

    protected Role $superAdminRole;

    protected Role $adminRole;

    protected AgentRank $rank;

    protected DepositScheme $fdScheme;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'status' => 'active']);

        $this->rank = AgentRank::first() ?? AgentRank::create([
            'name' => 'Advisor',
            'code' => 'ADV_'.uniqid(),
            'level' => 1,
            'is_active' => true,
        ]);

        $this->fdScheme = DepositScheme::create([
            'name' => 'Premier FD Plan',
            'code' => 'PFD_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 9.0,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);
    }

    public function test_superadmin_dashboard_computes_real_treasury_and_commission_telemetry(): void
    {
        $superAdmin = User::create([
            'name' => 'Chief Treasury Admin',
            'email' => 'sa_treasury_'.uniqid().'@multicred.com',
            'password' => Hash::make('password123'),
        ]);
        $superAdmin->roles()->attach($this->superAdminRole->id);

        $branch = Branch::create([
            'name' => 'Treasury Main Branch',
            'code' => 'TRB_'.rand(100, 999),
            'is_active' => true,
        ]);

        $agent = Agent::create([
            'name' => 'Lead Advisor',
            'agent_id' => 'AGT_TR_'.rand(1000, 9999),
            'member_code' => 'AGT_TR_'.rand(1000, 9999),
            'email' => 'agt_tr_'.uniqid().'@multicred.com',
            'phone' => '98'.rand(10000000, 99999999),
            'branch_id' => $branch->id,
            'rank_id' => $this->rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Treasury Depositor',
            'customer_type' => 'individual',
            'mobile' => '97'.rand(10000000, 99999999),
            'branch_id' => $branch->id,
            'agent_id' => $agent->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $superAdmin->id,
        ]);

        Deposit::create([
            'deposit_number' => 'DEP_TR_'.uniqid(),
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'agent_id' => $agent->id,
            'deposit_scheme_id' => $this->fdScheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 150000,
            'interest_rate' => 9.0,
            'maturity_date' => now()->addDays(20)->toDateString(),
            'maturity_amount' => 163500,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'status' => Deposit::STATUS_ACTIVE,
            'created_by' => $superAdmin->id,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('superadmin.dashboard'));

        $response->assertOk();
        $response->assertSee('Portfolio Breakdown');
        $response->assertSee('Mobilization Goal');
        $response->assertSee('Treasury Mobilization &amp; Cashflow Telemetry', false);
        $response->assertSee('MultiCred Treasury');
        $response->assertSee('Maturity Liquidity Horizon');
        $response->assertSee('1,50,000.00');
        $response->assertSee('1,63,500.00');
        $response->assertViewHas('twelveMonthTrends', fn (array $trends): bool => count($trends) === 12);
        $response->assertViewHas('maturityHorizon', fn (array $horizon): bool => $horizon['days_30']['count'] >= 1);
    }

    public function test_superadmin_dashboard_renders_minimal_clock_without_navbar_or_sidebar_collapse(): void
    {
        $superAdmin = User::create([
            'name' => 'Clock UI Admin',
            'email' => 'sa_clock_'.uniqid().'@multicred.com',
            'password' => Hash::make('password123'),
        ]);
        $superAdmin->roles()->attach($this->superAdminRole->id);

        $response = $this->actingAs($superAdmin)->get(route('superadmin.dashboard'));

        $response->assertOk();
        $response->assertSee('mxLiveClockWidget');
        $response->assertSee('mxDashboardFullDate');
        $response->assertSee('mxClockSec');
        $response->assertSee('mxFormatToggle');
        $response->assertDontSee('topbarDateTimePill');
        $response->assertDontSee('Collapse Sidebar');
    }

    public function test_superadmin_dashboard_computes_mobilization_goal_from_financial_year_targets(): void
    {
        $superAdmin = User::create([
            'name' => 'Goal Admin',
            'email' => 'sa_goal_'.uniqid().'@multicred.com',
            'password' => Hash::make('password123'),
        ]);
        $superAdmin->roles()->attach($this->superAdminRole->id);

        $branch = Branch::create(['name' => 'Goal Branch', 'code' => 'GLB_'.rand(100, 999), 'is_active' => true]);

        $agent = Agent::create([
            'name' => 'Goal Advisor',
            'agent_id' => 'AGT_GL_'.rand(1000, 9999),
            'member_code' => 'AGT_GL_'.rand(1000, 9999),
            'email' => 'agt_gl_'.uniqid().'@multicred.com',
            'phone' => '98'.rand(10000000, 99999999),
            'branch_id' => $branch->id,
            'rank_id' => $this->rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        FinancialYear::query()->update(['is_current' => false]);
        $fy = FinancialYear::create([
            'name' => 'FY Test Goal',
            'code' => 'FY_GL_'.rand(100, 999),
            'start_date' => now()->startOfYear()->toDateString(),
            'end_date' => now()->endOfYear()->toDateString(),
            'is_current' => true,
            'status' => FinancialYear::STATUS_ACTIVE,
        ]);

        AgentTargetAssignment::create([
            'agent_id' => $agent->id,
            'financial_year_id' => $fy->id,
            'rank_id' => $this->rank->id,
            'total_deposit_target' => 200000,
            'fd_percentage' => 90,
            'rd_percentage' => 10,
            'effective_from' => $fy->start_date,
            'effective_to' => $fy->end_date,
            'status' => AgentTargetAssignment::STATUS_ACTIVE,
        ]);

        $customer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Goal Depositor',
            'customer_type' => 'individual',
            'mobile' => '93'.rand(10000000, 99999999),
            'branch_id' => $branch->id,
            'agent_id' => $agent->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $superAdmin->id,
        ]);

        Deposit::create([
            'deposit_number' => 'DEP_GL_'.uniqid(),
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'agent_id' => $agent->id,
            'deposit_scheme_id' => $this->fdScheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 100000,
            'interest_rate' => 8.5,
            'maturity_date' => now()->addYear()->toDateString(),
            'maturity_amount' => 108500,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'status' => Deposit::STATUS_ACTIVE,
            'created_by' => $superAdmin->id,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('superadmin.dashboard'));

        $response->assertOk();
        $response->assertSee('FY Test Goal');
        $response->assertSee('Target Achieved');
        $response->assertViewHas('mobilizationGoal', fn (array $g): bool => $g['mode'] === 'fy_target' && $g['target'] >= 200000.0 && $g['achieved'] >= 100000.0);
    }

    public function test_branch_admin_dashboard_scopes_treasury_and_pipeline_to_assigned_branch(): void
    {
        $branchA = Branch::create(['name' => 'Branch Alpha', 'code' => 'BRA_'.rand(100, 999), 'is_active' => true]);
        $branchB = Branch::create(['name' => 'Branch Beta', 'code' => 'BRB_'.rand(100, 999), 'is_active' => true]);

        $adminA = User::create([
            'name' => 'Alpha Admin',
            'email' => 'alpha_adm_'.uniqid().'@multicred.com',
            'password' => Hash::make('password123'),
            'branch_id' => $branchA->id,
        ]);
        $adminA->roles()->attach($this->adminRole->id);

        $agentA = Agent::create([
            'name' => 'Alpha Agent',
            'agent_id' => 'AG_A_'.rand(1000, 9999),
            'member_code' => 'AG_A_'.rand(1000, 9999),
            'email' => 'aga_'.uniqid().'@multicred.com',
            'phone' => '98'.rand(10000000, 99999999),
            'branch_id' => $branchA->id,
            'rank_id' => $this->rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $agentB = Agent::create([
            'name' => 'Beta Agent',
            'agent_id' => 'AG_B_'.rand(1000, 9999),
            'member_code' => 'AG_B_'.rand(1000, 9999),
            'email' => 'agb_'.uniqid().'@multicred.com',
            'phone' => '98'.rand(10000000, 99999999),
            'branch_id' => $branchB->id,
            'rank_id' => $this->rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $custA = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Alpha Cust',
            'customer_type' => 'individual',
            'mobile' => '96'.rand(10000000, 99999999),
            'branch_id' => $branchA->id,
            'agent_id' => $agentA->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $adminA->id,
        ]);

        $custB = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Beta Cust',
            'customer_type' => 'individual',
            'mobile' => '95'.rand(10000000, 99999999),
            'branch_id' => $branchB->id,
            'agent_id' => $agentB->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $adminA->id,
        ]);

        Deposit::create([
            'deposit_number' => 'DEP_A_'.uniqid(),
            'customer_id' => $custA->id,
            'branch_id' => $branchA->id,
            'agent_id' => $agentA->id,
            'deposit_scheme_id' => $this->fdScheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 45000,
            'interest_rate' => 8.5,
            'maturity_date' => now()->addDays(45)->toDateString(),
            'maturity_amount' => 48825,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'status' => Deposit::STATUS_ACTIVE,
            'created_by' => $adminA->id,
        ]);

        Deposit::create([
            'deposit_number' => 'DEP_B_'.uniqid(),
            'customer_id' => $custB->id,
            'branch_id' => $branchB->id,
            'agent_id' => $agentB->id,
            'deposit_scheme_id' => $this->fdScheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 999000,
            'interest_rate' => 8.5,
            'maturity_date' => now()->addDays(15)->toDateString(),
            'maturity_amount' => 1083915,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'status' => Deposit::STATUS_ACTIVE,
            'created_by' => $adminA->id,
        ]);

        $response = $this->actingAs($adminA)->get(route('branch-admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Branch Deposit Mobilization &amp; Commission Flow', false);
        $response->assertSee('Branch Alpha Vault');
        $response->assertSee('45,000.00');
        $response->assertDontSee('9,99,000.00');
        $response->assertViewHas('treasury', fn (array $t): bool => $t['active_aum'] === 45000.0);
        $response->assertViewHas('maturityHorizon', fn (array $h): bool => $h['days_60']['count'] === 1 && $h['days_30']['count'] === 0);
    }
}
