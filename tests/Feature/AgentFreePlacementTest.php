<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AgentFreePlacementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected Branch $branch1;

    protected Branch $branch2;

    protected AgentRank $rank;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->first()
            ?? User::factory()->create();

        $this->branch1 = Branch::first() ?? Branch::create([
            'name' => 'Branch Central',
            'code' => 'BR_CENTRAL_01',
            'is_active' => true,
        ]);

        $this->branch2 = Branch::where('id', '!=', $this->branch1->id)->first() ?? Branch::create([
            'name' => 'Branch Coastal',
            'code' => 'BR_COASTAL_02',
            'is_active' => true,
        ]);

        $this->rank = AgentRank::first() ?? AgentRank::create([
            'name' => 'Standard Agent',
            'code' => 'STD_AGT',
            'level' => 1,
            'sort_order' => 1,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_assign_agent_to_any_branch_and_place_on_create(): void
    {
        $agentId = 'AGT_FREE_'.rand(1000, 9999);
        $email = 'free_agent_'.uniqid().'@example.com';

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.store'), [
                'name' => 'Free Field Agent',
                'agent_id' => $agentId,
                'email' => $email,
                'phone' => '+91 9888123456',
                'place' => 'Downtown Plaza Area',
                'address' => 'Shop 14, Commercial Complex',
                'branch_id' => $this->branch2->id,
                'rank_id' => $this->rank->id,
                'joining_date' => now()->toDateString(),
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.agents.index'));
        $this->assertDatabaseHas('agents', [
            'agent_id' => $agentId,
            'branch_id' => $this->branch2->id,
            'place' => 'Downtown Plaza Area',
            'address' => 'Shop 14, Commercial Complex',
        ]);
    }

    public function test_super_admin_can_reassign_agent_branch_and_place_on_update(): void
    {
        $agent = Agent::create([
            'member_code' => 'AGT_MOVE_'.rand(1000, 9999),
            'agent_id' => 'AGT_MOVE_'.rand(1000, 9999),
            'name' => 'Agent To Reassign',
            'email' => 'move_'.uniqid().'@example.com',
            'phone' => '9876543210',
            'place' => 'Old Town',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'status' => 'active',
            'joining_date' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.agents.update', $agent), [
                'name' => 'Agent To Reassign Renamed',
                'agent_id' => $agent->agent_id,
                'email' => $agent->email,
                'phone' => $agent->phone,
                'place' => 'New Metropolis Hub',
                'address' => 'Block C, Sector 12',
                'branch_id' => $this->branch2->id,
                'rank_id' => $this->rank->id,
                'joining_date' => now()->toDateString(),
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.agents.index'));
        $this->assertDatabaseHas('agents', [
            'id' => $agent->id,
            'branch_id' => $this->branch2->id,
            'place' => 'New Metropolis Hub',
            'address' => 'Block C, Sector 12',
        ]);
    }

    public function test_super_admin_can_verify_and_accept_pending_agent_with_custom_branch_and_place(): void
    {
        $pendingAgent = Agent::create([
            'member_code' => 'AGT_VERIFY_'.rand(1000, 9999),
            'agent_id' => 'AGT_VERIFY_'.rand(1000, 9999),
            'name' => 'Pending Verification Agent',
            'email' => 'verify_'.uniqid().'@example.com',
            'phone' => '9876543211',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'status' => Agent::STATUS_PENDING,
            'joining_date' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.accept', $pendingAgent), [
                'branch_id' => $this->branch2->id,
                'place' => 'Harbor Sector',
                'address' => 'Pier 4 Business Center',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('agents', [
            'id' => $pendingAgent->id,
            'status' => Agent::STATUS_ACTIVE,
            'branch_id' => $this->branch2->id,
            'place' => 'Harbor Sector',
            'address' => 'Pier 4 Business Center',
        ]);
    }

    public function test_recruiting_agent_can_add_downline_with_specified_place_and_branch(): void
    {
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], [
            'name' => 'Agent',
            'description' => 'Agent Role',
            'status' => 'active',
        ]);

        $sponsorUser = User::create([
            'name' => 'Sponsor Agent User',
            'email' => 'sponsor_'.uniqid().'@example.com',
            'password' => bcrypt('password123'),
            'branch_id' => $this->branch1->id,
        ]);
        $sponsorUser->roles()->attach($agentRole->id);

        $sponsorAgent = Agent::create([
            'user_id' => $sponsorUser->id,
            'member_code' => 'AGT_SPON_'.rand(1000, 9999),
            'agent_id' => 'AGT_SPON_'.rand(1000, 9999),
            'name' => $sponsorUser->name,
            'email' => $sponsorUser->email,
            'phone' => '9876543212',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'status' => Agent::STATUS_ACTIVE,
            'joining_date' => now(),
        ]);

        $downlineEmail = 'downline_'.uniqid().'@example.com';
        $downlineAgentId = 'AGT_DOWN_'.rand(1000, 9999);

        $response = $this->actingAs($sponsorUser)
            ->post(route('agent.agents.store'), [
                'name' => 'New Downline Recruited',
                'agent_id' => $downlineAgentId,
                'email' => $downlineEmail,
                'phone' => '+91 9123456789',
                'branch_id' => $this->branch2->id,
                'place' => 'North District Market',
                'address' => '77 Market Road',
                'rank_id' => $this->rank->id,
            ]);

        $response->assertRedirect(route('agent.agents.index'));
        $this->assertDatabaseHas('agents', [
            'agent_id' => $downlineAgentId,
            'parent_agent_id' => $sponsorAgent->id,
            'branch_id' => $this->branch2->id,
            'place' => 'North District Market',
            'status' => Agent::STATUS_PENDING,
        ]);
    }
}
