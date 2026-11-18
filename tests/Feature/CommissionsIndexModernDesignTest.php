<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CommissionsIndexModernDesignTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected Branch $branch;

    protected Agent $agent;

    protected Customer $customer;

    protected DepositScheme $scheme;

    protected function setUp(): void
    {
        parent::setUp();

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::create([
            'name' => 'Super Admin UI Tester',
            'email' => 'sa_comm_ui_'.uniqid().'@multicred.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR_COMM_UI'],
            ['name' => 'UI Test Branch', 'status' => 'active']
        );

        $agentUser = User::create([
            'name' => 'UI Test Agent',
            'email' => 'agent_ui_'.uniqid().'@multicred.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
        ]);

        $rank = AgentRank::firstOrCreate(['level' => 1], ['code' => 'ADV_UI', 'name' => 'Advisor']);

        $this->agent = Agent::create([
            'user_id' => $agentUser->id,
            'branch_id' => $this->branch->id,
            'rank_id' => $rank->id,
            'name' => 'UI Test Agent',
            'email' => $agentUser->email,
            'phone' => '98'.rand(10000000, 99999999),
            'member_code' => 'AG_UI_'.rand(100, 999),
            'agent_id' => 'AG_UI_'.rand(100, 999),
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'is_active' => true,
        ]);

        $customerUser = User::create([
            'name' => 'UI Test Customer',
            'email' => 'cust_ui_'.uniqid().'@multicred.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
        ]);

        $this->customer = Customer::create([
            'user_id' => $customerUser->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'name' => 'UI Test Customer',
            'mobile_no' => '91'.rand(10000000, 99999999),
            'customer_number' => 'CUST_UI_'.rand(100, 999),
            'status' => 'active',
        ]);

        $this->scheme = DepositScheme::create([
            'name' => 'Modern FD 3 Years',
            'code' => 'MFD3_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 8.50,
            'tenure_months' => 36,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);
    }

    public function test_commissions_page_renders_modern_kpi_cards_and_status_tabs(): void
    {
        $deposit = Deposit::create([
            'deposit_number' => 'DEP_UI_'.uniqid(),
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'customer_id' => $this->customer->id,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 100000,
            'tenure_months' => 36,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        Commission::create([
            'commission_number' => 'COM_UI_1_'.uniqid(),
            'deposit_id' => $deposit->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'customer_id' => $this->customer->id,
            'payout_year' => 1,
            'base_amount' => 100000,
            'commission_rate' => 5.0,
            'commission_amount' => 5000,
            'status' => Commission::STATUS_PENDING,
            'period' => '2026-10',
        ]);

        Commission::create([
            'commission_number' => 'COM_UI_2_'.uniqid(),
            'deposit_id' => $deposit->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'customer_id' => $this->customer->id,
            'payout_year' => 2,
            'base_amount' => 100000,
            'commission_rate' => 3.0,
            'commission_amount' => 3000,
            'status' => Commission::STATUS_PAID,
            'period' => '2027-10',
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.commissions.index'));

        $response->assertStatus(200);

        // Header & KPI metric cards
        $response->assertSee('Commissions');
        $response->assertSee('Total Commission Pool');
        $response->assertSee('Pending Review');
        $response->assertSee('Ready For Payout');
        $response->assertSee('Settled & Paid', false);

        // Status segmented tabs
        $response->assertSee('All Records');
        $response->assertSee('Pending');
        $response->assertSee('Approved');
        $response->assertSee('Payable');
        $response->assertSee('Paid');
        $response->assertSee('Rejected');

        // Deposit & commission rows
        $response->assertSee($deposit->deposit_number);
        $response->assertSee($this->customer->name);
        $response->assertSee($this->agent->name);
        $response->assertSee($this->scheme->name);
        $response->assertSee('Year 1');
        $response->assertSee('Year 2');
        $response->assertSee('5,000.00');
        $response->assertSee('3,000.00');
        $response->assertSee('8,000.00'); // Group total
    }

    public function test_commissions_page_filtering_by_status(): void
    {
        $deposit = Deposit::create([
            'deposit_number' => 'DEP_FILT_'.uniqid(),
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'customer_id' => $this->customer->id,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 50000,
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        $pendingComm = Commission::create([
            'commission_number' => 'COM_P_'.uniqid(),
            'deposit_id' => $deposit->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'customer_id' => $this->customer->id,
            'base_amount' => 50000,
            'commission_rate' => 4.0,
            'commission_amount' => 2000,
            'status' => Commission::STATUS_PENDING,
            'period' => '2026-10',
        ]);

        $paidComm = Commission::create([
            'commission_number' => 'COM_D_'.uniqid(),
            'deposit_id' => $deposit->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'customer_id' => $this->customer->id,
            'base_amount' => 50000,
            'commission_rate' => 4.0,
            'commission_amount' => 2000,
            'status' => Commission::STATUS_PAID,
            'period' => '2026-10',
        ]);

        // Filter Pending
        $pendingResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.commissions.index', ['status' => 'PENDING', 'search' => $pendingComm->commission_number]));

        $pendingResponse->assertStatus(200);
        $pendingResponse->assertSee($pendingComm->commission_number);
        $pendingResponse->assertDontSee($paidComm->commission_number);

        // Filter Paid
        $paidResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.commissions.index', ['status' => 'PAID', 'search' => $paidComm->commission_number]));

        $paidResponse->assertStatus(200);
        $paidResponse->assertSee($paidComm->commission_number);
        $paidResponse->assertDontSee($pendingComm->commission_number);
    }
}
