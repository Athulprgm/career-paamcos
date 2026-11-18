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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AgentLeadManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $agentUser;

    protected Agent $agent;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);

        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR_TEST_LD_01'],
            ['name' => 'Leads Test Branch', 'is_active' => true, 'address' => 'Test Address', 'phone' => '1234567890', 'email' => 'branch_lead@test.com']
        );

        $this->agentUser = User::create([
            'name' => 'Lead Test Agent',
            'email' => 'lead_agent_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $this->agentUser->roles()->attach($agentRole->id);

        $rank = AgentRank::first() ?? AgentRank::create(['name' => 'Agent', 'code' => 'AG', 'level' => 1]);

        $this->agent = Agent::create([
            'name' => 'Lead Test Agent',
            'agent_id' => 'AG_LD_'.rand(1000, 9999),
            'member_code' => 'AG_LD_'.rand(1000, 9999),
            'email' => $this->agentUser->email,
            'phone' => '98765'.rand(10000, 99999),
            'branch_id' => $this->branch->id,
            'user_id' => $this->agentUser->id,
            'rank_id' => $rank->id,
            'status' => 'active',
            'joining_date' => now(),
        ]);
    }

    public function test_agent_can_access_leads_index(): void
    {
        $response = $this->actingAs($this->agentUser)->get(route('agent.leads.index'));

        $response->assertStatus(200);
        $response->assertViewIs('agent.leads.index');
        $response->assertSee('Leads Management');
    }

    public function test_agent_can_access_create_lead_page(): void
    {
        $response = $this->actingAs($this->agentUser)->get(route('agent.leads.create'));

        $response->assertStatus(200);
        $response->assertViewIs('agent.leads.create');
        $response->assertSee('Add Business Lead');
        $response->assertSee('searchable-select');
        $response->assertSee('data-search-placeholder="Search branch name or code..."', false);
        $response->assertSee('Customer Area Branch');
    }

    public function test_agent_can_store_lead(): void
    {
        $scheme = DepositScheme::first();

        $leadData = [
            'name' => 'New Customer Lead',
            'mobile' => '9988776655',
            'email' => 'prospect@test.com',
            'branch_id' => $this->branch->id,
            'status' => Lead::STATUS_NEW,
            'expected_amount' => 50000,
            'interested_scheme_id' => $scheme?->id,
            'follow_up_date' => now()->addDays(2)->toDateString(),
            'notes' => 'Interested in 1 year FD scheme.',
        ];

        $response = $this->actingAs($this->agentUser)->post(route('agent.leads.store'), $leadData);

        $this->assertDatabaseHas('leads', [
            'name' => 'New Customer Lead',
            'mobile' => '9988776655',
            'agent_id' => $this->agent->id,
            'status' => Lead::STATUS_NEW,
        ]);

        $lead = Lead::where('name', 'New Customer Lead')->first();
        $response->assertRedirect(route('agent.leads.show', $lead));
    }

    public function test_agent_can_view_own_lead(): void
    {
        $lead = Lead::create([
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'name' => 'Prospect Person',
            'mobile' => '9123456780',
            'status' => Lead::STATUS_NEW,
            'created_by' => $this->agentUser->id,
        ]);

        $response = $this->actingAs($this->agentUser)->get(route('agent.leads.show', $lead));

        $response->assertStatus(200);
        $response->assertViewIs('agent.leads.show');
        $response->assertSee('Prospect Person');
    }

    public function test_agent_can_update_quick_status(): void
    {
        $lead = Lead::create([
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'name' => 'Quick Status Person',
            'mobile' => '9123456781',
            'status' => Lead::STATUS_NEW,
            'created_by' => $this->agentUser->id,
        ]);

        $response = $this->actingAs($this->agentUser)->patch(route('agent.leads.quick-status', $lead), [
            'status' => Lead::STATUS_CONTACTED,
            'follow_up_date' => now()->addDays(3)->toDateString(),
            'note' => 'Called and discussed benefits.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => Lead::STATUS_CONTACTED,
        ]);
    }

    public function test_agent_can_delete_unconverted_lead(): void
    {
        $lead = Lead::create([
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'name' => 'Delete Test Person',
            'mobile' => '9123456782',
            'status' => Lead::STATUS_NEW,
            'created_by' => $this->agentUser->id,
        ]);

        $response = $this->actingAs($this->agentUser)->delete(route('agent.leads.destroy', $lead));

        $response->assertRedirect(route('agent.leads.index'));
        $this->assertSoftDeleted('leads', [
            'id' => $lead->id,
        ]);
    }

    public function test_agent_can_realtime_search_leads_via_ajax(): void
    {
        Lead::create([
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'name' => 'Alice Wonder',
            'mobile' => '9888877771',
            'status' => Lead::STATUS_NEW,
            'created_by' => $this->agentUser->id,
        ]);

        Lead::create([
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'name' => 'Bob Builder',
            'mobile' => '9777766662',
            'status' => Lead::STATUS_CONTACTED,
            'created_by' => $this->agentUser->id,
        ]);

        // Search matching Alice
        $response = $this->actingAs($this->agentUser)
            ->getJson(route('agent.leads.index', ['search' => 'Alice']));

        $response->assertStatus(200);
        $response->assertJsonStructure(['html', 'total', 'first_item', 'last_item', 'count_text']);
        $this->assertEquals(1, $response->json('total'));
        $this->assertStringContainsString('Alice Wonder', $response->json('html'));
        $this->assertStringNotContainsString('Bob Builder', $response->json('html'));

        // Filter matching Bob by status
        $statusResponse = $this->actingAs($this->agentUser)
            ->getJson(route('agent.leads.index', ['status' => Lead::STATUS_CONTACTED]));

        $statusResponse->assertStatus(200);
        $this->assertStringContainsString('Bob Builder', $statusResponse->json('html'));
    }

    public function test_agent_can_access_edit_lead_page_with_searchable_select(): void
    {
        $lead = Lead::create([
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'name' => 'Edit Searchable Person',
            'mobile' => '9123456789',
            'status' => Lead::STATUS_NEW,
            'created_by' => $this->agentUser->id,
        ]);

        $response = $this->actingAs($this->agentUser)->get(route('agent.leads.edit', $lead));

        $response->assertStatus(200);
        $response->assertSee('searchable-select');
        $response->assertSee('Customer Area Branch');
        $response->assertSee('data-search-placeholder="Search branch name or code..."', false);
    }
}
