<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\CareerLevel;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\Lead;
use App\Models\PromotionRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AgentModuleTest extends TestCase
{
    use DatabaseTransactions;

    protected Branch $branch1;

    protected Branch $branch2;

    protected AgentRank $rank;

    protected User $agentUser1;

    protected Agent $agent1;

    protected User $agentUser2;

    protected Agent $agent2;

    protected DepositScheme $activeScheme;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);

        // 2. Branches
        $this->branch1 = Branch::firstOrCreate(
            ['code' => 'BR_TEST_AG_01'],
            ['name' => 'Agent Test Branch 1', 'is_active' => true, 'address' => 'Test Address 1', 'phone' => '1111111111', 'email' => 'branch1@test.com']
        );

        $this->branch2 = Branch::firstOrCreate(
            ['code' => 'BR_TEST_AG_02'],
            ['name' => 'Agent Test Branch 2', 'is_active' => true, 'address' => 'Test Address 2', 'phone' => '2222222222', 'email' => 'branch2@test.com']
        );

        // 3. Rank
        $this->rank = AgentRank::first() ?? AgentRank::create([
            'code' => 'RANK_'.uniqid(),
            'name' => 'Test Rank',
            'level' => ((int) AgentRank::max('level')) + 1,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        // 4. Agent 1 (Branch 1)
        $this->agentUser1 = User::create([
            'name' => 'Agent One',
            'email' => 'agent1_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch1->id,
        ]);
        $this->agentUser1->roles()->attach($agentRole->id);

        $this->agent1 = Agent::create([
            'user_id' => $this->agentUser1->id,
            'member_code' => 'AG1_'.uniqid(),
            'name' => 'Agent One',
            'email' => $this->agentUser1->email,
            'password' => Hash::make('password123'),
            'phone' => '9876543210',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'status' => 'active',
            'joining_date' => now()->subMonths(3),
        ]);

        // 5. Agent 2 (Branch 2)
        $this->agentUser2 = User::create([
            'name' => 'Agent Two',
            'email' => 'agent2_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch2->id,
        ]);
        $this->agentUser2->roles()->attach($agentRole->id);

        $this->agent2 = Agent::create([
            'user_id' => $this->agentUser2->id,
            'member_code' => 'AG2_'.uniqid(),
            'name' => 'Agent Two',
            'email' => $this->agentUser2->email,
            'password' => Hash::make('password123'),
            'phone' => '9876543211',
            'branch_id' => $this->branch2->id,
            'rank_id' => $this->rank->id,
            'status' => 'active',
            'joining_date' => now()->subMonths(2),
        ]);

        // 6. Active Scheme
        $this->activeScheme = DepositScheme::first() ?? DepositScheme::create([
            'code' => 'SCH_'.uniqid(),
            'name' => 'Daily Test Deposit Scheme',
            'scheme_type' => 'rd',
            'deposit_frequency' => 'daily',
            'interest_rate' => 6.5,
            'min_amount' => 100,
            'max_amount' => 50000,
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'collection_frequency' => 'DAILY',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_agent_login_and_welcome_redirects_to_agent_dashboard(): void
    {
        // 1. Post login
        $response = $this->post(route('superadmin.login.post'), [
            'login' => $this->agentUser1->email,
            'password' => 'password123',
        ]);
        $response->assertRedirect(route('agent.dashboard'));

        // 2. Root route redirection
        $rootResponse = $this->actingAs($this->agentUser1)->get('/');
        $rootResponse->assertRedirect(route('agent.dashboard'));
    }

    public function test_agent_dashboard_renders_with_real_metrics_and_sidebar(): void
    {
        // Create lead under agent 1
        $lead = Lead::create([
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'name' => 'Lead Under Agent 1',
            'mobile' => '9111111111',
            'status' => Lead::STATUS_NEW,
            'created_by' => $this->agentUser1->id,
        ]);

        $response = $this->actingAs($this->agentUser1)->get(route('agent.dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Agent Operational Dashboard');
        $response->assertSee($this->agent1->name);
        $response->assertSee('My Business Leads');
        $response->assertSee('Converted Leads');
        $response->assertSee('My Recent Leads');
        $response->assertSee('Business / Leads');
        $response->assertSee('My Leads');
        $response->assertSee('Performance');

        // Assert customer and deposit features are completely removed from agent view
        $response->assertDontSee('Add Customer');
        $response->assertDontSee('My Customers');
        $response->assertDontSee('New Deposit');
        $response->assertDontSee('My Deposits');
        $response->assertDontSee('Recent Deposits');
        $response->assertDontSee('Staff Positions');
        $response->assertDontSee('Global Deposit Schemes');
        $response->assertDontSee('Audit Logs');
    }

    public function test_agent_cannot_access_customers_module(): void
    {
        $cust1 = Customer::create([
            'customer_number' => 'CUST_A1_'.uniqid(),
            'name' => 'Customer Agent 1',
            'customer_type' => 'individual',
            'mobile' => '9222222222',
            'date_of_birth' => '1990-01-01',
            'address' => 'Branch 1 address',
            'pin_code' => '123456',
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $this->agentUser1->id,
        ]);

        // Customer module is completely removed (returns 404)
        $indexResponse = $this->actingAs($this->agentUser1)->get('/admin/customers');
        $indexResponse->assertStatus(404);

        $show1 = $this->actingAs($this->agentUser1)->get("/admin/customers/{$cust1->id}");
        $show1->assertStatus(404);

    }

    public function test_agent_leads_crud_and_isolation(): void
    {
        // 1. Create Lead
        $leadData = [
            'name' => 'Prospective Investor',
            'mobile' => '9555555555',
            'email' => 'investor@test.com',
            'address' => 'City Center',
            'interested_scheme_id' => $this->activeScheme->id,
            'expected_amount' => 25000,
            'status' => Lead::STATUS_NEW,
            'follow_up_date' => now()->addDays(3)->toDateString(),
            'notes' => 'Very interested in recurring deposits.',
        ];

        $createResponse = $this->actingAs($this->agentUser1)->post(route('agent.leads.store'), $leadData);
        $createResponse->assertSessionHasNoErrors();

        $lead = Lead::where('mobile', '9555555555')->first();
        $this->assertNotNull($lead);
        $this->assertEquals($this->agent1->id, $lead->agent_id);
        $this->assertEquals($this->branch1->id, $lead->branch_id);

        // 2. View Lead
        $showResponse = $this->actingAs($this->agentUser1)->get(route('agent.leads.show', $lead));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Prospective Investor');

        // 3. Edit & Update Lead
        $updateResponse = $this->actingAs($this->agentUser1)->put(route('agent.leads.update', $lead), array_merge($leadData, [
            'status' => Lead::STATUS_INTERESTED,
            'notes' => 'Updated notes after initial phone call.',
        ]));
        $updateResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => Lead::STATUS_INTERESTED,
        ]);

        // 4. Agent 2 cannot access Agent 1's lead (403)
        $unauthorizedShow = $this->actingAs($this->agentUser2)->get(route('agent.leads.show', $lead));
        $unauthorizedShow->assertStatus(403);

        $unauthorizedUpdate = $this->actingAs($this->agentUser2)->put(route('agent.leads.update', $lead), $leadData);
        $unauthorizedUpdate->assertStatus(403);
    }

    public function test_free_agent_can_add_leads_for_different_branches_and_manage_them(): void
    {
        // Free agent visiting create lead form sees both branches
        $formResponse = $this->actingAs($this->agentUser1)->get(route('agent.leads.create'));
        $formResponse->assertStatus(200);
        $formResponse->assertSee($this->branch1->name);
        $formResponse->assertSee($this->branch2->name);

        // 1. Agent 1 adds a lead for Branch 1 (e.g. Kozhikode area)
        $leadPayload1 = [
            'branch_id' => $this->branch1->id,
            'name' => 'Kozhikode Customer',
            'mobile' => '9888800001',
            'email' => 'calicut@test.com',
            'status' => Lead::STATUS_NEW,
        ];
        $resp1 = $this->actingAs($this->agentUser1)->post(route('agent.leads.store'), $leadPayload1);
        $resp1->assertSessionHasNoErrors();

        // 2. The SAME agent adds a lead for Branch 2 (e.g. Kollam area)
        $leadPayload2 = [
            'branch_id' => $this->branch2->id,
            'name' => 'Kollam Customer',
            'mobile' => '9888800002',
            'email' => 'kollam@test.com',
            'status' => Lead::STATUS_INTERESTED,
        ];
        $resp2 = $this->actingAs($this->agentUser1)->post(route('agent.leads.store'), $leadPayload2);
        $resp2->assertSessionHasNoErrors();

        $leadKozhikode = Lead::where('mobile', '9888800001')->first();
        $leadKollam = Lead::where('mobile', '9888800002')->first();

        $this->assertNotNull($leadKozhikode);
        $this->assertNotNull($leadKollam);
        $this->assertEquals($this->branch1->id, $leadKozhikode->branch_id);
        $this->assertEquals($this->branch2->id, $leadKollam->branch_id);
        $this->assertEquals($this->agent1->id, $leadKozhikode->agent_id);
        $this->assertEquals($this->agent1->id, $leadKollam->agent_id);

        // 3. Agent can view both leads even if lead's branch differs from agent's home branch
        $viewKollam = $this->actingAs($this->agentUser1)->get(route('agent.leads.show', $leadKollam));
        $viewKollam->assertStatus(200);
        $viewKollam->assertSee('Kollam Customer');
        $viewKollam->assertSee($this->branch2->name);

        // 4. Agent can edit and change branch for lead
        $updateResp = $this->actingAs($this->agentUser1)->put(route('agent.leads.update', $leadKollam), [
            'branch_id' => $this->branch1->id,
            'name' => 'Kollam Customer Relocated',
            'mobile' => '9888800002',
            'status' => Lead::STATUS_CONTACTED,
        ]);
        $updateResp->assertSessionHasNoErrors();
        $this->assertEquals($this->branch1->id, $leadKollam->fresh()->branch_id);

        // 5. Agent index lists leads and supports filtering by branch
        $indexResponse = $this->actingAs($this->agentUser1)->get(route('agent.leads.index', ['branch_id' => $this->branch1->id]));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('Kozhikode Customer');
    }

    public function test_agent_lead_status_shows_converted_after_successful_deposit(): void
    {
        // 1. Agent creates a lead
        $lead = Lead::create([
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'name' => 'Deposit Prospect',
            'mobile' => '9870001122',
            'email' => 'prospect@test.com',
            'status' => Lead::STATUS_NEW,
            'expected_amount' => 5000,
            'created_by' => $this->agentUser1->id,
        ]);

        $this->assertEquals(Lead::STATUS_NEW, $lead->status);

        // 2. Admin creates a deposit for this lead (with manual customer details as submitted from form)
        $adminRole = Role::where('slug', 'super_admin')->first() ?? Role::where('name', 'Super Admin')->first();
        if (! $adminRole) {
            $adminRole = Role::create(['name' => 'Super Admin', 'slug' => 'super_admin', 'status' => 'active']);
        }
        $adminUser = User::create([
            'name' => 'Super Admin',
            'email' => 'admin_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
        ]);
        $adminUser->roles()->attach($adminRole->id);

        $depositPayload = [
            'lead_id' => $lead->id,
            'manual_customer_name' => 'Deposit Prospect',
            'manual_customer_mobile' => '9870001122',
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => 5000,
            'start_date' => now()->toDateString(),
            'status_action' => 'active',
        ];

        $resp = $this->actingAs($adminUser)->post(route('admin.deposits.store'), $depositPayload);
        $resp->assertSessionHasNoErrors();

        // 3. Lead status in database is now CONVERTED
        $lead->refresh();
        $this->assertEquals(Lead::STATUS_CONVERTED, $lead->status);
        $this->assertNotNull($lead->converted_customer_id);
        $this->assertNotNull($lead->converted_at);

        // 4. In Agent Module, lead index shows CONVERTED
        $agentIndex = $this->actingAs($this->agentUser1)->get(route('agent.leads.index'));
        $agentIndex->assertStatus(200);
        $agentIndex->assertSee('CONVERTED');
        $agentIndex->assertSee('Deposit Prospect');

        // 5. In Agent Module, lead show page shows CONVERTED and deposit account
        $agentShow = $this->actingAs($this->agentUser1)->get(route('agent.leads.show', $lead));
        $agentShow->assertStatus(200);
        $agentShow->assertSee('CONVERTED');
        $agentShow->assertSee('Converted Account');
        $agentShow->assertSee('Deposit Successful');
    }

    public function test_lead_conversion_feature_is_removed_from_agent_module(): void
    {
        $lead = Lead::create([
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'name' => 'Prospect Lead',
            'mobile' => '9666666666',
            'email' => 'leadconv@test.com',
            'address' => 'Prospect Street',
            'status' => Lead::STATUS_INTERESTED,
            'created_by' => $this->agentUser1->id,
        ]);

        // 1. Convert route is removed and returns 404
        $formResponse = $this->actingAs($this->agentUser1)->get('/agent/leads/'.$lead->id.'/convert');
        $formResponse->assertStatus(404);

        // 2. Lead details view does not show "Convert to Customer" or "Conversion Status" card for unconverted leads
        $showResponse = $this->actingAs($this->agentUser1)->get(route('agent.leads.show', $lead));
        $showResponse->assertStatus(200);
        $showResponse->assertDontSee('Convert to Customer');
        $showResponse->assertDontSee('Conversion Status');

        // 3. Agent leads index does not display "Convert" button
        $indexResponse = $this->actingAs($this->agentUser1)->get(route('agent.leads.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertDontSee('Convert to Customer');
    }

    public function test_agent_cannot_access_deposits_module(): void
    {
        $customer = Customer::create([
            'customer_number' => 'CUST_DEP_'.uniqid(),
            'name' => 'Deposit Client',
            'customer_type' => 'individual',
            'mobile' => '9777777777',
            'date_of_birth' => '1990-01-01',
            'address' => 'Customer address',
            'pin_code' => '123456',
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $this->agentUser1->id,
        ]);

        $deposit = Deposit::create([
            'deposit_number' => 'DEP_'.uniqid(),
            'customer_id' => $customer->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => 5000,
            'start_date' => now()->toDateString(),
            'maturity_date' => now()->addYear()->toDateString(),
            'interest_rate' => 6.5,
            'status' => Deposit::STATUS_DRAFT,
            'created_by' => $this->agentUser1->id,
        ]);

        // Agent 1 can view their own scoped deposit list
        $listResponse = $this->actingAs($this->agentUser1)->get(route('admin.deposits.index'));
        $listResponse->assertStatus(200);
        $listResponse->assertSee($deposit->deposit_number);

        // Agent 1 can view their own deposit show details
        $showResponse = $this->actingAs($this->agentUser1)->get(route('admin.deposits.show', $deposit));
        $showResponse->assertStatus(200);

        // Agent 2 is BLOCKED with 403 from viewing Agent 1's deposit show details
        $crossShow = $this->actingAs($this->agentUser2)->get(route('admin.deposits.show', $deposit));
        $crossShow->assertStatus(403);

        // Agent 1 is BLOCKED with 403 from accessing deposit creation
        $createResponse = $this->actingAs($this->agentUser1)->get(route('admin.deposits.create'));
        $createResponse->assertStatus(403);

        // Agent 1 is BLOCKED with 403 from approving deposits
        $approveResponse = $this->actingAs($this->agentUser1)->post(route('admin.deposits.approve', $deposit));
        $approveResponse->assertStatus(403);

        // Agent 1 is BLOCKED with 403 from viewing deposit history
        $historyResponse = $this->actingAs($this->agentUser1)->get(route('admin.deposits.history'));
        $historyResponse->assertStatus(403);

        // Removed pending approval queue routes return 404
        $this->actingAs($this->agentUser1)->get('/admin/deposits/pending-approval')->assertNotFound();
        $this->actingAs($this->agentUser1)->get('/admin/deposits/pending')->assertNotFound();
    }

    public function test_agent_commission_scoping(): void
    {
        $customer = Customer::create([
            'customer_number' => 'CUST_COMM_'.uniqid(),
            'name' => 'Comm Client',
            'customer_type' => 'individual',
            'mobile' => '9999111111',
            'date_of_birth' => '1990-01-01',
            'address' => 'Customer address',
            'pin_code' => '123456',
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $this->agentUser1->id,
        ]);

        $deposit1 = Deposit::create([
            'deposit_number' => 'DEP_COMM1_'.uniqid(),
            'customer_id' => $customer->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => 1000,
            'start_date' => now()->toDateString(),
            'status' => Deposit::STATUS_ACTIVE,
            'created_by' => $this->agentUser1->id,
        ]);

        $deposit2 = Deposit::create([
            'deposit_number' => 'DEP_COMM2_'.uniqid(),
            'customer_id' => $customer->id,
            'branch_id' => $this->branch2->id,
            'agent_id' => $this->agent2->id,
            'deposit_scheme_id' => $this->activeScheme->id,
            'amount' => 2000,
            'start_date' => now()->toDateString(),
            'status' => Deposit::STATUS_ACTIVE,
            'created_by' => $this->agentUser2->id,
        ]);

        $commission1 = Commission::create([
            'commission_number' => 'COM1_'.uniqid(),
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'customer_id' => $customer->id,
            'deposit_id' => $deposit1->id,
            'base_amount' => 1000,
            'commission_rate' => 5,
            'commission_amount' => 50,
            'period' => now()->format('Y-m'),
            'status' => Commission::STATUS_PENDING,
        ]);

        $commission2 = Commission::create([
            'commission_number' => 'COM2_'.uniqid(),
            'branch_id' => $this->branch2->id,
            'agent_id' => $this->agent2->id,
            'customer_id' => $customer->id,
            'deposit_id' => $deposit2->id,
            'base_amount' => 2000,
            'commission_rate' => 5,
            'commission_amount' => 100,
            'period' => now()->format('Y-m'),
            'status' => Commission::STATUS_PENDING,
        ]);

        // Agent 1 can view own commission
        $show1 = $this->actingAs($this->agentUser1)->get(route('admin.commissions.show', $commission1));
        $show1->assertStatus(200);

        // Agent 1 is BLOCKED from viewing Agent 2's commission (403)
        $show2 = $this->actingAs($this->agentUser1)->get(route('admin.commissions.show', $commission2));
        $show2->assertStatus(403);

        // Agent 1 is BLOCKED from approving commission (403)
        $approve = $this->actingAs($this->agentUser1)->post(route('admin.commissions.approve', $commission1));
        $approve->assertStatus(403);
    }

    public function test_agent_performance_scorecard_and_career_isolation(): void
    {
        // Agent 1 can view their own performance scorecard
        $perfResponse = $this->actingAs($this->agentUser1)->get(route('agent.performance'));
        $perfResponse->assertStatus(200);
        $perfResponse->assertSee('My Performance & Career');
        $perfResponse->assertSee($this->agent1->name);

        // Agent 1 is BLOCKED from viewing Agent 2's performance scorecard (403)
        $crossPerf = $this->actingAs($this->agentUser1)->get(route('admin.career.agent-performance', $this->agent2));
        $crossPerf->assertStatus(403);
    }

    public function test_agent_notifications_view_renders(): void
    {
        $response = $this->actingAs($this->agentUser1)->get(route('agent.notifications'));
        $response->assertStatus(200);
        $response->assertSee('Operational Notifications', false);
    }

    public function test_agent_profile_updates_and_syncs_agent_record(): void
    {
        $newEmail = 'updated_agent_'.uniqid().'@test.com';
        $updateResponse = $this->actingAs($this->agentUser1)->put(route('superadmin.profile.update'), [
            'name' => 'Agent One Updated',
            'email' => $newEmail,
        ]);
        $updateResponse->assertSessionHasNoErrors();

        // Both User and linked Agent record are updated
        $this->assertDatabaseHas('users', [
            'id' => $this->agentUser1->id,
            'name' => 'Agent One Updated',
            'email' => $newEmail,
        ]);

        $this->assertDatabaseHas('agents', [
            'id' => $this->agent1->id,
            'name' => 'Agent One Updated',
            'email' => $newEmail,
        ]);
    }

    public function test_agent_strictly_blocked_from_admin_modules(): void
    {
        // Staff management
        $this->actingAs($this->agentUser1)->get(route('admin.staff.index'))->assertStatus(403);

        // Agent management
        $this->actingAs($this->agentUser1)->get(route('admin.agents.index'))->assertStatus(403);

        // Deposit schemes
        $this->actingAs($this->agentUser1)->get(route('admin.deposit-schemes.index'))->assertStatus(403);

        // Reports
        $this->actingAs($this->agentUser1)->get(route('admin.reports.overview'))->assertStatus(403);

        // Audit logs page removed (returns 404)
        $this->actingAs($this->agentUser1)->get('/admin/audit-logs')->assertStatus(404);

        // Superadmin branches
        $this->actingAs($this->agentUser1)->get(route('superadmin.branches.index'))->assertStatus(403);

        // Branch admin dashboard
        $this->actingAs($this->agentUser1)->get(route('branch-admin.dashboard'))->assertStatus(403);
    }

    public function test_super_admin_can_delete_agent_without_dependencies(): void
    {
        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $superAdmin = User::create([
            'name' => 'Master Super Admin',
            'email' => 'master_sa_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
        ]);
        $superAdmin->roles()->attach($superAdminRole->id);

        $delUser = User::create([
            'name' => 'Agent To Delete',
            'email' => 'del_agent_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch1->id,
        ]);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $delUser->roles()->attach($agentRole->id);

        $agentToDelete = Agent::create([
            'user_id' => $delUser->id,
            'member_code' => 'DEL_'.rand(1000, 9999),
            'agent_id' => 'DEL_'.rand(1000, 9999),
            'name' => 'Agent To Delete',
            'email' => $delUser->email,
            'password' => Hash::make('password123'),
            'phone' => '9876543209',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'status' => 'active',
            'joining_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($superAdmin)->delete(route('admin.agents.destroy', $agentToDelete));

        $response->assertRedirect(route('admin.agents.index'));
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('agents', ['id' => $agentToDelete->id]);
        $this->assertDatabaseMissing('users', ['id' => $delUser->id]);
    }

    public function test_super_admin_cannot_delete_agent_with_downlines(): void
    {
        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $superAdmin = User::create([
            'name' => 'Master Super Admin',
            'email' => 'master_sa2_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
        ]);
        $superAdmin->roles()->attach($superAdminRole->id);

        // agent1 has a downline
        Agent::create([
            'member_code' => 'SUB_'.rand(1000, 9999),
            'agent_id' => 'SUB_'.rand(1000, 9999),
            'name' => 'Subordinate Agent',
            'email' => 'sub_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'phone' => '9876543208',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'parent_agent_id' => $this->agent1->id,
            'status' => 'active',
            'joining_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($superAdmin)->delete(route('admin.agents.destroy', $this->agent1));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('agents', ['id' => $this->agent1->id, 'deleted_at' => null]);
    }

    public function test_super_admin_cannot_delete_agent_with_customers(): void
    {
        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $superAdmin = User::create([
            'name' => 'Master Super Admin',
            'email' => 'master_sa3_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
        ]);
        $superAdmin->roles()->attach($superAdminRole->id);

        Customer::create([
            'customer_number' => 'CUST_'.rand(10000, 99999),
            'name' => 'Customer With Agent',
            'customer_type' => 'individual',
            'mobile' => '9876543207',
            'date_of_birth' => '1990-01-01',
            'address' => '123 Main Street',
            'pin_code' => '123456',
            'branch_id' => $this->branch2->id,
            'agent_id' => $this->agent2->id,
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($superAdmin)->delete(route('admin.agents.destroy', $this->agent2));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('agents', ['id' => $this->agent2->id, 'deleted_at' => null]);
    }

    public function test_non_super_admin_cannot_delete_agents(): void
    {
        $response = $this->actingAs($this->agentUser1)->delete(route('admin.agents.destroy', $this->agent2));
        $response->assertStatus(403);
    }

    public function test_agent_notifications_renders_with_promotion_requests_and_badges(): void
    {
        $careerLevel = CareerLevel::first() ?? CareerLevel::create([
            'level_number' => 1,
            'name' => 'Junior Advisor',
            'commission_rate' => 5.0,
            'is_active' => true,
        ]);

        PromotionRequest::create([
            'agent_id' => $this->agent1->id,
            'branch_id' => $this->branch1->id,
            'current_level_id' => $careerLevel->id,
            'proposed_level_id' => $careerLevel->id,
            'status' => PromotionRequest::STATUS_PENDING_REVIEW,
            'requested_by' => $this->agentUser1->id,
        ]);

        $response = $this->actingAs($this->agentUser1)->get(route('agent.notifications'));
        $response->assertOk();
        $response->assertSee('Career & Promotion Requests', false);
        $response->assertSee('PENDING_REVIEW');
    }
}
