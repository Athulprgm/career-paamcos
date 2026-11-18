<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CareerLevel;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\CustomerKyc;
use App\Models\Deposit;
use App\Models\DepositCollection;
use App\Models\DepositScheme;
use App\Models\PromotionRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ReportsAndAnalyticsTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected ?User $branchAdmin1 = null;

    protected ?User $branchAdmin2 = null;

    protected ?User $agentUser1 = null;

    protected ?Branch $branch1 = null;

    protected ?Branch $branch2 = null;

    protected ?Agent $agent1 = null;

    protected ?Agent $agent2 = null;

    protected ?Customer $customer1 = null;

    protected ?Customer $customer2 = null;

    protected ?DepositScheme $scheme = null;

    protected ?Deposit $deposit1 = null;

    protected ?Deposit $deposit2 = null;

    protected ?DepositCollection $collection1 = null;

    protected ?DepositCollection $collection2 = null;

    protected ?Commission $commission1 = null;

    protected ?Commission $commission2 = null;

    protected ?CareerLevel $level1 = null;

    protected ?CareerLevel $level2 = null;

    protected ?ReportService $reportService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportService = app(ReportService::class);

        // 1. Roles
        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin', 'status' => 'active']);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);

        // 2. Branches
        $this->branch1 = Branch::firstOrCreate(
            ['code' => 'BR_P8_1'],
            ['name' => 'Branch One Phase 8', 'is_active' => true]
        );

        $this->branch2 = Branch::firstOrCreate(
            ['code' => 'BR_P8_2'],
            ['name' => 'Branch Two Phase 8', 'is_active' => true]
        );

        // 3. Super Admin
        $this->superAdmin = User::create([
            'name' => 'Super Admin P8',
            'email' => 'superadmin_p8_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        // 4. Branch Admins
        $this->branchAdmin1 = User::create([
            'name' => 'Branch Admin 1 P8',
            'email' => 'badmin1_p8_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch1->id,
        ]);
        $this->branchAdmin1->roles()->attach($adminRole->id);

        $this->branchAdmin2 = User::create([
            'name' => 'Branch Admin 2 P8',
            'email' => 'badmin2_p8_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch2->id,
        ]);
        $this->branchAdmin2->roles()->attach($adminRole->id);

        // 5. Agent Users
        $this->agentUser1 = User::create([
            'name' => 'Agent User 1 P8',
            'email' => 'agentuser1_p8_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch1->id,
        ]);
        $this->agentUser1->roles()->attach($agentRole->id);

        // 6. Career Levels
        $this->level1 = CareerLevel::firstOrCreate(
            ['code' => 'FAC_P8'],
            [
                'name' => 'Facilitator P8',
                'level' => 201,
                'sort_order' => 10,
                'status' => 'active',
                'min_service_days' => 30,
                'min_active_customers' => 2,
                'min_deposit_count' => 1,
                'min_deposit_amount' => 5000.00,
                'min_collection_amount' => 2000.00,
                'min_commission_amount' => 500.00,
                'min_performance_score' => 20.00,
            ]
        );

        $this->level2 = CareerLevel::firstOrCreate(
            ['code' => 'FO_P8'],
            [
                'name' => 'Field Officer P8',
                'level' => 202,
                'sort_order' => 20,
                'status' => 'active',
                'min_service_days' => 60,
                'min_active_customers' => 5,
                'min_deposit_count' => 3,
                'min_deposit_amount' => 15000.00,
                'min_collection_amount' => 5000.00,
                'min_commission_amount' => 1000.00,
                'min_performance_score' => 40.00,
            ]
        );

        // 7. Agents
        $this->agent1 = Agent::create([
            'user_id' => $this->agentUser1->id,
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->level1->id,
            'member_code' => 'AGP8_1_'.rand(1000, 9999),
            'agent_id' => 'AGP8_1_'.rand(1000, 9999),
            'name' => 'Agent One P8',
            'email' => $this->agentUser1->email,
            'phone' => '98765'.rand(10000, 99999),
            'joining_date' => Carbon::now()->subMonths(3),
            'status' => 'active',
        ]);

        $this->agent2 = Agent::create([
            'branch_id' => $this->branch2->id,
            'rank_id' => $this->level1->id,
            'member_code' => 'AGP8_2_'.rand(1000, 9999),
            'agent_id' => 'AGP8_2_'.rand(1000, 9999),
            'name' => 'Agent Two P8',
            'email' => 'agent2_p8_'.uniqid().'@example.com',
            'phone' => '98765'.rand(10000, 99999),
            'joining_date' => Carbon::now()->subMonths(2),
            'status' => 'active',
        ]);

        // 8. Customers
        $this->customer1 = Customer::create([
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'customer_number' => 'CUSTP8_1_'.rand(1000, 9999),
            'name' => 'Customer One P8',
            'customer_type' => 'individual',
            'mobile' => '91234'.rand(10000, 99999),
            'date_of_birth' => '1990-01-01',
            'address' => 'Branch 1 Road',
            'pin_code' => '682001',
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        CustomerKyc::create([
            'customer_id' => $this->customer1->id,
            'id_type' => 'AADHAAR',
            'id_number' => '123456789012',
            'status' => CustomerKyc::STATUS_APPROVED,
        ]);

        $this->customer2 = Customer::create([
            'branch_id' => $this->branch2->id,
            'agent_id' => $this->agent2->id,
            'customer_number' => 'CUSTP8_2_'.rand(1000, 9999),
            'name' => 'Customer Two P8',
            'customer_type' => 'individual',
            'mobile' => '91234'.rand(10000, 99999),
            'date_of_birth' => '1992-05-15',
            'address' => 'Branch 2 Road',
            'pin_code' => '682002',
            'status' => Customer::STATUS_PENDING,
            'created_by' => $this->superAdmin->id,
        ]);

        CustomerKyc::create([
            'customer_id' => $this->customer2->id,
            'id_type' => 'PAN',
            'id_number' => 'ABCDE1234F',
            'status' => CustomerKyc::STATUS_PENDING,
        ]);

        // 9. Deposit Scheme
        $this->scheme = DepositScheme::firstOrCreate(
            ['code' => 'SCH_P8'],
            [
                'name' => 'Scheme Phase 8',
                'scheme_type' => DepositScheme::TYPE_RD,
                'min_amount' => 500,
                'max_amount' => 500000,
                'tenure' => 12,
                'tenure_unit' => DepositScheme::TENURE_MONTHS,
                'interest_rate' => 8.5,
                'collection_frequency' => DepositScheme::FREQ_MONTHLY,
                'status' => DepositScheme::STATUS_ACTIVE,
            ]
        );

        // 10. Deposits
        $this->deposit1 = Deposit::create([
            'customer_id' => $this->customer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 20000.00,
            'start_date' => Carbon::now()->subMonths(1)->format('Y-m-d'),
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'collection_frequency' => DepositScheme::FREQ_MONTHLY,
            'interest_rate' => 8.5,
            'maturity_date' => Carbon::now()->addMonths(11)->format('Y-m-d'),
            'status' => Deposit::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->deposit2 = Deposit::create([
            'customer_id' => $this->customer2->id,
            'branch_id' => $this->branch2->id,
            'agent_id' => $this->agent2->id,
            'deposit_scheme_id' => $this->scheme->id,
            'amount' => 15000.00,
            'start_date' => Carbon::now()->subMonths(1)->format('Y-m-d'),
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'collection_frequency' => DepositScheme::FREQ_MONTHLY,
            'interest_rate' => 8.5,
            'maturity_date' => Carbon::now()->addMonths(11)->format('Y-m-d'),
            'status' => Deposit::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        // 11. Collections
        $this->collection1 = DepositCollection::create([
            'collection_number' => 'COLP8_1_'.rand(1000, 9999),
            'deposit_id' => $this->deposit1->id,
            'customer_id' => $this->customer1->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'installment_number' => 1,
            'due_date' => Carbon::now()->subDays(5),
            'due_amount' => 2000.00,
            'paid_amount' => 2000.00,
            'collection_date' => Carbon::now()->subDays(5),
            'payment_method' => DepositCollection::METHOD_CASH,
            'status' => DepositCollection::STATUS_PAID,
            'collected_by' => $this->superAdmin->id,
        ]);

        $this->collection2 = DepositCollection::create([
            'collection_number' => 'COLP8_2_'.rand(1000, 9999),
            'deposit_id' => $this->deposit2->id,
            'customer_id' => $this->customer2->id,
            'branch_id' => $this->branch2->id,
            'agent_id' => $this->agent2->id,
            'installment_number' => 1,
            'due_date' => Carbon::now()->addDays(5),
            'due_amount' => 1500.00,
            'paid_amount' => 0.00,
            'payment_method' => DepositCollection::METHOD_CASH,
            'status' => DepositCollection::STATUS_DUE,
            'collected_by' => $this->superAdmin->id,
        ]);

        // 12. Commissions
        $this->commission1 = Commission::create([
            'commission_number' => 'COMP8_1_'.rand(1000, 9999),
            'commission_id' => 'COMP8_1_'.rand(1000, 9999),
            'agent_id' => $this->agent1->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $this->customer1->id,
            'deposit_id' => $this->deposit1->id,
            'deposit_collection_id' => $this->collection1->id,
            'base_amount' => 2000.00,
            'rate' => 5.00,
            'commission_rate' => 5.00,
            'calculated_amount' => 100.00,
            'commission_amount' => 100.00,
            'status' => Commission::STATUS_PAID,
            'period' => Carbon::now()->format('Y-m'),
            'created_by' => $this->superAdmin->id,
        ]);

        $this->commission2 = Commission::create([
            'commission_number' => 'COMP8_2_'.rand(1000, 9999),
            'commission_id' => 'COMP8_2_'.rand(1000, 9999),
            'agent_id' => $this->agent2->id,
            'branch_id' => $this->branch2->id,
            'customer_id' => $this->customer2->id,
            'deposit_id' => $this->deposit2->id,
            'deposit_collection_id' => $this->collection2->id,
            'base_amount' => 1500.00,
            'rate' => 5.00,
            'commission_rate' => 5.00,
            'calculated_amount' => 75.00,
            'commission_amount' => 75.00,
            'status' => Commission::STATUS_PENDING,
            'period' => Carbon::now()->format('Y-m'),
            'created_by' => $this->superAdmin->id,
        ]);

        // 13. Audit Log
        AuditLog::record(
            'DEPOSIT_CREATED',
            'Created deposit contract for testing Phase 8',
            $this->deposit1,
            null,
            null,
            20000.00
        );
    }

    /**
     * Test Overview Dashboard metrics match database aggregations.
     */
    public function test_overview_dashboard_computes_real_database_kpis(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.overview'));

        $response->assertStatus(200);
        $response->assertSee('Reports & Analytics Overview', false);
        $response->assertSee('Customer Base');
        $response->assertSee('Active Deposits');
        $response->assertSee('Commissions Paid');

        $data = $this->reportService->getOverviewData();
        $this->assertGreaterThanOrEqual(2, $data['total_customers']);
        $this->assertGreaterThanOrEqual(1, $data['active_customers']);
        $this->assertGreaterThanOrEqual(35000.0, $data['total_deposit_value']);
        $this->assertGreaterThanOrEqual(2000.0, $data['total_collected']);
        $this->assertGreaterThanOrEqual(100.0, $data['paid_commission']);
    }

    /**
     * Test Deposit Reports and Filters match actual database records.
     */
    public function test_deposit_reports_and_filters_match_actual_records(): void
    {
        // 1. General Deposit Report
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.deposits'));

        $response->assertStatus(200);
        $response->assertSee($this->deposit1->deposit_number);
        $response->assertSee($this->deposit2->deposit_number);

        // 2. Filter by Branch 1
        $filteredResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.deposits', ['branch_id' => $this->branch1->id]));

        $filteredResponse->assertStatus(200);
        $filteredResponse->assertSee($this->deposit1->deposit_number);
        $filteredResponse->assertDontSee($this->deposit2->deposit_number);

        // 3. Accuracy check via Service
        $data = $this->reportService->getDepositReportData(['branch_id' => $this->branch1->id]);
        $this->assertEquals(1, $data['summary']['active_count']);
        $this->assertEquals(20000.00, $data['summary']['active_amount']);
    }

    /**
     * Test Commission Reports match actual commission ledgers.
     */
    public function test_commission_reports_and_filters_match_actual_records(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.commissions'));

        $response->assertStatus(200);
        $response->assertSee($this->commission1->commission_number);
        $response->assertSee($this->commission2->commission_number);

        // Filter by Status = PAID
        $paidResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.commissions', ['status' => Commission::STATUS_PAID]));

        $paidResponse->assertStatus(200);
        $paidResponse->assertSee($this->commission1->commission_number);
        $paidResponse->assertDontSee($this->commission2->commission_number);

        $data = $this->reportService->getCommissionReportData(['status' => Commission::STATUS_PAID]);
        $this->assertGreaterThanOrEqual(100.0, $data['summary']['paid_commission']);
    }

    /**
     * Test Agent Performance Report reuses Phase 7 logic and computes live scores.
     */
    public function test_agent_performance_report_uses_phase7_service(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.agents'));

        $response->assertStatus(200);
        $response->assertSee($this->agent1->name);
        $response->assertSee($this->agent2->name);

        $data = $this->reportService->getAgentPerformanceReportData(['agent_id' => $this->agent1->id]);
        $agentRow = collect($data['records']->items())->firstWhere('agent.id', $this->agent1->id);

        $this->assertNotNull($agentRow);
        $this->assertEquals(1, $agentRow['active_customers']);
        $this->assertEquals(1, $agentRow['deposit_count']);
        $this->assertEquals(20000.00, $agentRow['deposit_amount']);
        $this->assertEquals(1, $agentRow['collection_count']);
        $this->assertEquals(2000.00, $agentRow['collection_amount']);
        $this->assertGreaterThan(0, $agentRow['performance_score']);
    }

    /**
     * Test Customer Reports and status filtering.
     */
    public function test_customer_report_and_kyc_breakdown(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.customers'));

        $response->assertStatus(200);
        $response->assertSee($this->customer1->name);
        $response->assertSee($this->customer2->name);

        // Filter by Active status
        $activeResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.customers', ['status' => Customer::STATUS_ACTIVE]));

        $activeResponse->assertStatus(200);
        $activeResponse->assertSee($this->customer1->name);
        $activeResponse->assertDontSee($this->customer2->name);
    }

    /**
     * Test Career Reports displays ladder and promotion nominations.
     */
    public function test_career_reports_displays_ladder_and_promotions(): void
    {
        // Submit a promotion request
        PromotionRequest::create([
            'request_number' => 'PR_P8_'.rand(1000, 9999),
            'agent_id' => $this->agent1->id,
            'branch_id' => $this->branch1->id,
            'current_level_id' => $this->level1->id,
            'proposed_level_id' => $this->level2->id,
            'performance_period' => Carbon::now()->format('Y-m'),
            'eligibility_result' => ['is_eligible' => true],
            'status' => PromotionRequest::STATUS_PENDING_REVIEW,
            'requested_by' => $this->superAdmin->id,
            'request_date' => Carbon::today(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.career'));

        $response->assertStatus(200);
        $response->assertSee('Career Reports');
        $response->assertSee('Facilitator P8');
        $response->assertSee($this->agent1->name);
    }

    /**
     * Test Audit Reports displays security logs.
     */
    public function test_audit_reports_displays_security_logs(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.audit'));

        $response->assertStatus(200);
        $response->assertSee('Audit Reports');
        $response->assertSee('DEPOSIT_CREATED');
        $response->assertSee('20,000.00');
    }

    /**
     * Test CSV Exports stream proper CSV data with headers.
     */
    public function test_csv_exports_return_streamed_file_with_correct_headers(): void
    {
        // Deposit CSV Export
        $depExport = $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.deposits.export'));

        $depExport->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $depExport->headers->get('content-type'));

        // Commission CSV Export
        $comExport = $this->actingAs($this->superAdmin)
            ->get(route('admin.reports.commissions.export'));

        $comExport->assertStatus(200);
        $this->assertStringContainsString('text/csv', (string) $comExport->headers->get('content-type'));
    }

    /**
     * Test Branch Isolation prevents cross-branch report data leakage.
     */
    public function test_branch_isolation_restricts_branch_admin_from_other_branch_data(): void
    {
        // Branch Admin 1 must only see Branch 1's deposits
        $response = $this->actingAs($this->branchAdmin1)
            ->get(route('admin.reports.deposits'));

        $response->assertStatus(200);
        $response->assertSee($this->deposit1->deposit_number);
        $response->assertDontSee($this->deposit2->deposit_number);

        // Even if Branch Admin 1 explicitly passes branch_id = branch2 in query, system enforces restriction
        $tamperedResponse = $this->actingAs($this->branchAdmin1)
            ->get(route('admin.reports.deposits', ['branch_id' => $this->branch2->id]));

        $tamperedResponse->assertStatus(200);
        $tamperedResponse->assertSee($this->deposit1->deposit_number);
        $tamperedResponse->assertDontSee($this->deposit2->deposit_number);
    }
}
