<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\DepositScheme;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BranchAdminLeadAndCustomerTest extends TestCase
{
    use DatabaseTransactions;

    protected function getDefaultRankId(): int
    {
        return AgentRank::value('id') ?? AgentRank::create([
            'name' => 'Agent Rank '.uniqid(),
            'code' => 'RK_'.uniqid(),
            'level' => (AgentRank::max('level') ?? 0) + 1,
            'sort_order' => 1,
            'status' => 'active',
        ])->id;
    }

    protected function findOrCreateRole(string $slug, string $name = ''): Role
    {
        return Role::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name ?: ucwords(str_replace('_', ' ', $slug)), 'status' => 'active']
        );
    }

    protected function createBranch(array $attrs = []): Branch
    {
        $uniq = uniqid();

        return Branch::create(array_merge([
            'code' => 'BR_'.substr($uniq, -6),
            'name' => 'Branch '.$uniq,
            'address' => '123 Test Street',
            'phone' => '9876543210',
            'email' => 'branch_'.$uniq.'@example.com',
            'is_active' => true,
        ], $attrs));
    }

    protected function makeBranchAdmin(?Branch $branch = null): User
    {
        $role = $this->findOrCreateRole('admin', 'Branch Admin');
        $branch ??= $this->createBranch();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->roles()->sync([$role->id]);

        return $user;
    }

    protected function createAgent(Branch $branch, array $attrs = []): Agent
    {
        $uniq = uniqid();

        return Agent::create(array_merge([
            'member_code' => 'AGT_'.strtoupper(substr($uniq, -6)),
            'agent_id' => 'AGT_'.strtoupper(substr($uniq, -6)),
            'name' => 'Agent '.$uniq,
            'email' => 'agent_'.$uniq.'@example.com',
            'phone' => '9876543210',
            'branch_id' => $branch->id,
            'rank_id' => $this->getDefaultRankId(),
            'status' => 'active',
            'joining_date' => now(),
        ], $attrs));
    }

    protected function createLead(Branch $branch, Agent $agent, array $attrs = []): Lead
    {
        $uniq = uniqid();

        return Lead::create(array_merge([
            'branch_id' => $branch->id,
            'agent_id' => $agent->id,
            'name' => 'Lead '.$uniq,
            'mobile' => '9876543210',
            'email' => 'lead_'.$uniq.'@example.com',
            'address' => '789 Prospect Lane',
            'status' => Lead::STATUS_NEW,
        ], $attrs));
    }

    public function test_branch_admin_does_not_see_customers_nav_in_sidebar_nav(): void
    {
        $branch = $this->createBranch();
        $branchAdmin = $this->makeBranchAdmin($branch);

        $response = $this->actingAs($branchAdmin)->get(route('branch-admin.dashboard'));

        $response->assertStatus(200);
        $response->assertDontSee('Add Customer');
        $response->assertSee(route('admin.leads.index'), false);
        $response->assertSee('Agent Leads');
    }

    public function test_branch_admin_cannot_access_add_customer_page(): void
    {
        $branch = $this->createBranch();
        $branchAdmin = $this->makeBranchAdmin($branch);

        $response = $this->actingAs($branchAdmin)->get('/admin/customers/create');

        $response->assertStatus(404);
    }

    public function test_branch_admin_can_view_branch_agents_leads_list(): void
    {
        $branch = $this->createBranch();
        $branchAdmin = $this->makeBranchAdmin($branch);
        $agent = $this->createAgent($branch);
        $lead = $this->createLead($branch, $agent, ['name' => 'Exclusive Branch Lead']);

        $response = $this->actingAs($branchAdmin)->get(route('admin.leads.index'));

        $response->assertStatus(200);
        $response->assertSee('Exclusive Branch Lead');
        $response->assertSee($agent->name);
    }

    public function test_branch_admin_only_sees_leads_belonging_to_their_own_branch(): void
    {
        $branchA = $this->createBranch();
        $branchB = $this->createBranch();

        $branchAdminA = $this->makeBranchAdmin($branchA);

        $agentA = $this->createAgent($branchA);
        $agentB = $this->createAgent($branchB);

        $leadA = $this->createLead($branchA, $agentA, ['name' => 'Branch A Lead Alpha']);
        $leadB = $this->createLead($branchB, $agentB, ['name' => 'Branch B Lead Beta']);

        $response = $this->actingAs($branchAdminA)->get(route('admin.leads.index'));

        $response->assertStatus(200);
        $response->assertSee('Branch A Lead Alpha');
        $response->assertDontSee('Branch B Lead Beta');
    }

    public function test_branch_admin_can_view_lead_details(): void
    {
        $branch = $this->createBranch();
        $branchAdmin = $this->makeBranchAdmin($branch);
        $agent = $this->createAgent($branch);
        $lead = $this->createLead($branch, $agent, ['name' => 'Detailed Lead Info']);

        $response = $this->actingAs($branchAdmin)->get(route('admin.leads.show', $lead));

        $response->assertStatus(200);
        $response->assertSee('Detailed Lead Info');
        $response->assertSee($agent->name);
        $response->assertSee('Add Deposit for this Lead');
    }

    public function test_branch_admin_cannot_view_lead_details_of_another_branch(): void
    {
        $branchA = $this->createBranch();
        $branchB = $this->createBranch();

        $branchAdminA = $this->makeBranchAdmin($branchA);
        $agentB = $this->createAgent($branchB);
        $leadB = $this->createLead($branchB, $agentB, ['name' => 'Secret Branch B Lead']);

        $response = $this->actingAs($branchAdminA)->get(route('admin.leads.show', $leadB));

        $response->assertStatus(403);
    }

    public function test_branch_admin_can_view_agent_leads_on_agent_profile_page(): void
    {
        $branch = $this->createBranch();
        $branchAdmin = $this->makeBranchAdmin($branch);
        $agent = $this->createAgent($branch);
        $lead = $this->createLead($branch, $agent, ['name' => 'Agent Profile Lead']);

        $response = $this->actingAs($branchAdmin)->get(route('admin.agents.show', $agent));

        $response->assertStatus(200);
        $response->assertSee('Business Leads Added by '.$agent->name);
        $response->assertSee('Agent Profile Lead');
    }

    public function test_customer_creation_from_lead_converts_the_lead(): void
    {
        $branch = $this->createBranch();
        $branchAdmin = $this->makeBranchAdmin($branch);
        $agent = $this->createAgent($branch);
        $scheme = DepositScheme::firstOrCreate(
            ['code' => 'FD_TEST_CONV'],
            [
                'name' => 'FD Conversion Scheme',
                'scheme_type' => 'FD',
                'interest_rate' => 8.0,
                'min_amount' => 1000,
                'max_amount' => 1000000,
                'tenure' => 12,
                'tenure_unit' => 'MONTHS',
                'status' => 'active',
            ]
        );
        $lead = $this->createLead($branch, $agent, [
            'name' => 'Convertible Prospect',
            'mobile' => '9876500000',
            'email' => 'prospect@example.com',
            'address' => '100 Main St',
        ]);

        $this->assertEquals(Lead::STATUS_NEW, $lead->status);

        $response = $this->actingAs($branchAdmin)->post(route('admin.deposits.store'), [
            'lead_id' => $lead->id,
            'branch_id' => $branch->id,
            'agent_id' => $agent->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => 'FD',
            'amount' => 10000,
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'interest_rate' => 8.0,
            'start_date' => now()->toDateString(),
            'payment_mode' => 'CASH',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $lead->refresh();
        $this->assertEquals(Lead::STATUS_CONVERTED, $lead->status);
        $this->assertNotNull($lead->converted_customer_id);
        $this->assertNotNull($lead->converted_at);
    }
}
