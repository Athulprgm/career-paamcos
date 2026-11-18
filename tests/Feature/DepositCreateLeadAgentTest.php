<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DepositCreateLeadAgentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_branch_admin_sees_free_field_agents_and_lead_agent_in_deposit_create(): void
    {
        $branch = Branch::first() ?? Branch::create([
            'code' => 'BR_DEP_01',
            'name' => 'Deposit Test Branch',
            'is_active' => true,
            'address' => 'Test Address',
            'phone' => '1234567890',
            'email' => 'branch_dep@test.com',
        ]);

        $role = Role::where('slug', 'admin')->orWhere('name', 'Branch Admin')->first()
            ?? Role::create(['slug' => 'admin', 'name' => 'Branch Admin', 'status' => 'active']);

        $branchAdmin = User::create([
            'name' => 'Branch Admin User',
            'email' => 'ba_dep_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'branch_id' => $branch->id,
            'role' => 'admin',
        ]);
        $branchAdmin->roles()->syncWithoutDetaching([$role->id]);

        $rank = AgentRank::first() ?? AgentRank::create(['name' => 'Advisor', 'code' => 'ADV', 'level' => 1]);

        // Create a free-field agent (branch_id = null)
        $freeAgent = Agent::create([
            'name' => 'Suresh Sourcing Agent',
            'agent_id' => 'AG_SUR_'.rand(1000, 9999),
            'member_code' => 'AG_SUR_'.rand(1000, 9999),
            'email' => 'suresh_'.uniqid().'@test.com',
            'phone' => '9876543210',
            'branch_id' => null, // Free field agent
            'rank_id' => $rank->id,
            'status' => 'active',
            'joining_date' => now(),
        ]);

        // Create a lead linked to this agent
        $lead = Lead::create([
            'name' => 'Virat Customer',
            'mobile' => '8778788767',
            'branch_id' => $branch->id,
            'agent_id' => $freeAgent->id,
            'status' => Lead::STATUS_NEW,
            'expected_amount' => 60000,
        ]);

        $response = $this->actingAs($branchAdmin)->get(route('admin.deposits.create', ['lead_id' => $lead->id]));

        $response->assertStatus(200);
        // The free-field agent must be present in the HTML dropdown options and pre-selected
        $response->assertSee('value="'.$freeAgent->id.'"', false);
        $response->assertSee($freeAgent->name);
        $response->assertSee('Virat Customer');
        $response->assertSee('selected', false);
    }

    public function test_can_fetch_agent_leads_via_ajax_endpoint(): void
    {
        $superadmin = User::whereHas('roles', fn ($q) => $q->where('slug', 'superadmin'))->first() ?? User::first();
        $branch = Branch::first() ?? Branch::create([
            'code' => 'BR_TEST_AJAX',
            'name' => 'Ajax Test Branch',
            'is_active' => true,
            'address' => 'Test Address',
            'phone' => '1234567890',
            'email' => 'ajax@test.com',
        ]);
        $rank = AgentRank::first() ?? AgentRank::create(['name' => 'Advisor', 'code' => 'ADV', 'level' => 1]);

        $agent = Agent::create([
            'name' => 'Test Source Agent',
            'agent_id' => 'AG_SRC_'.rand(1000, 9999),
            'member_code' => 'AG_SRC_'.rand(1000, 9999),
            'email' => 'src_'.uniqid().'@test.com',
            'phone' => '9876543299',
            'branch_id' => $branch->id,
            'rank_id' => $rank->id,
            'status' => 'active',
            'joining_date' => now(),
        ]);

        $lead1 = Lead::create([
            'name' => 'Alice Lead',
            'mobile' => '9111111111',
            'branch_id' => $branch->id,
            'agent_id' => $agent->id,
            'status' => Lead::STATUS_NEW,
            'expected_amount' => 50000,
        ]);

        $lead2 = Lead::create([
            'name' => 'Bob Lead',
            'mobile' => '9222222222',
            'branch_id' => $branch->id,
            'agent_id' => $agent->id,
            'status' => Lead::STATUS_CONTACTED,
            'expected_amount' => 75000,
        ]);

        $response = $this->actingAs($superadmin)
            ->getJson(route('admin.deposits.agent-leads', $agent));

        $response->assertStatus(200);
        $response->assertJsonCount(2);
        $response->assertJsonFragment(['name' => 'Alice Lead', 'mobile' => '9111111111']);
        $response->assertJsonFragment(['name' => 'Bob Lead', 'mobile' => '9222222222']);
    }

    public function test_branch_admin_cannot_fetch_other_branch_agent_leads(): void
    {
        $branch1 = Branch::create([
            'code' => 'BR_AJAX_01',
            'name' => 'Branch One',
            'is_active' => true,
            'address' => 'Address 1',
            'phone' => '1111111111',
            'email' => 'b1@test.com',
        ]);
        $branch2 = Branch::create([
            'code' => 'BR_AJAX_02',
            'name' => 'Branch Two',
            'is_active' => true,
            'address' => 'Address 2',
            'phone' => '2222222222',
            'email' => 'b2@test.com',
        ]);

        $role = Role::where('slug', 'admin')->orWhere('name', 'Branch Admin')->first()
            ?? Role::create(['slug' => 'admin', 'name' => 'Branch Admin', 'status' => 'active']);

        $branchAdmin = User::create([
            'name' => 'Branch 1 Admin',
            'email' => 'b1admin_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'branch_id' => $branch1->id,
            'role' => 'admin',
        ]);
        $branchAdmin->roles()->syncWithoutDetaching([$role->id]);

        $rank = AgentRank::first() ?? AgentRank::create(['name' => 'Advisor', 'code' => 'ADV', 'level' => 1]);

        $agentInBranch2 = Agent::create([
            'name' => 'Branch 2 Agent',
            'agent_id' => 'AG_B2_'.rand(1000, 9999),
            'member_code' => 'AG_B2_'.rand(1000, 9999),
            'email' => 'b2ag_'.uniqid().'@test.com',
            'phone' => '9999988888',
            'branch_id' => $branch2->id,
            'rank_id' => $rank->id,
            'status' => 'active',
            'joining_date' => now(),
        ]);

        $response = $this->actingAs($branchAdmin)
            ->getJson(route('admin.deposits.agent-leads', $agentInBranch2));

        $response->assertStatus(403);
    }
}
