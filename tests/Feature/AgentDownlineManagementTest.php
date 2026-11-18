<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AgentDownlineManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected Branch $branch1;

    protected Branch $branch2;

    protected AgentRank $rank;

    protected User $agentUser1;

    protected Agent $agent1;

    protected User $agentUser2;

    protected Agent $agent2;

    protected function setUp(): void
    {
        parent::setUp();

        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);

        $this->branch1 = Branch::firstOrCreate(
            ['code' => 'BR_TEST_AG_01'],
            ['name' => 'Agent Test Branch 1', 'is_active' => true, 'address' => 'Test Address 1', 'phone' => '1111111111', 'email' => 'branch1@test.com']
        );

        $this->branch2 = Branch::firstOrCreate(
            ['code' => 'BR_TEST_AG_02'],
            ['name' => 'Agent Test Branch 2', 'is_active' => true, 'address' => 'Test Address 2', 'phone' => '2222222222', 'email' => 'branch2@test.com']
        );

        $this->rank = AgentRank::where('level', 2)->first() ?? AgentRank::where('level', '>=', 2)->first() ?? AgentRank::first();

        // Agent 1 in Branch 1
        $this->agentUser1 = User::create([
            'name' => 'Sponsor Agent One',
            'email' => 'sponsor1_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch1->id,
        ]);
        $this->agentUser1->roles()->attach($agentRole->id);

        $sp1Code = 'SP1_'.rand(1000, 9999);
        $this->agent1 = Agent::create([
            'user_id' => $this->agentUser1->id,
            'member_code' => $sp1Code,
            'agent_id' => $sp1Code,
            'name' => 'Sponsor Agent One',
            'email' => $this->agentUser1->email,
            'password' => Hash::make('password123'),
            'phone' => '9876543210',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'status' => 'active',
            'joining_date' => now()->subMonths(3)->toDateString(),
        ]);

        // Agent 2 in Branch 2 (Independent agent / separate network)
        $this->agentUser2 = User::create([
            'name' => 'Independent Agent Two',
            'email' => 'sponsor2_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch2->id,
        ]);
        $this->agentUser2->roles()->attach($agentRole->id);

        $sp2Code = 'SP2_'.rand(1000, 9999);
        $this->agent2 = Agent::create([
            'user_id' => $this->agentUser2->id,
            'member_code' => $sp2Code,
            'agent_id' => $sp2Code,
            'name' => 'Independent Agent Two',
            'email' => $this->agentUser2->email,
            'password' => Hash::make('password123'),
            'phone' => '9876543212',
            'branch_id' => $this->branch2->id,
            'rank_id' => $this->rank->id,
            'status' => 'active',
            'joining_date' => now()->subMonths(2)->toDateString(),
        ]);
    }

    public function test_agent_can_view_downline_agents_index(): void
    {
        // Downline under Agent 1
        $dlCode = 'DL1_'.rand(1000, 9999);
        $downline = Agent::create([
            'member_code' => $dlCode,
            'agent_id' => $dlCode,
            'name' => 'Recruited Sub-Agent',
            'email' => 'downline_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'phone' => '9876543220',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'parent_agent_id' => $this->agent1->id,
            'status' => 'active',
            'joining_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->agentUser1)->get(route('agent.agents.index'));

        $response->assertStatus(200);
        $response->assertSee('My Team / Downline Agents');
        $response->assertSee($downline->name);
        $response->assertSee($downline->agent_id);
    }

    public function test_agent_downlines_list_is_isolated_to_own_network(): void
    {
        // Downline under Agent 2
        $dl2Code = 'DL2_'.rand(1000, 9999);
        $downline2 = Agent::create([
            'member_code' => $dl2Code,
            'agent_id' => $dl2Code,
            'name' => 'Agent Two Downline Secret',
            'email' => 'downline2_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'phone' => '9876543221',
            'branch_id' => $this->branch2->id,
            'rank_id' => $this->rank->id,
            'parent_agent_id' => $this->agent2->id,
            'status' => 'active',
            'joining_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->agentUser1)->get(route('agent.agents.index'));

        $response->assertStatus(200);
        $response->assertDontSee('Agent Two Downline Secret');
    }

    public function test_agent_can_view_add_agent_form(): void
    {
        $response = $this->actingAs($this->agentUser1)->get(route('agent.agents.create'));

        $response->assertStatus(200);
        $response->assertSee('Add Downline Agent');
        $response->assertSee($this->agent1->name);
        $response->assertSee($this->branch1->name);
        $response->assertSee($this->rank->name);
    }

    public function test_agent_can_successfully_recruit_downline_agent(): void
    {
        $email = 'newrecruit_'.uniqid().'@test.com';
        $agentId = 'AGR_'.rand(100000, 999999);

        $payload = [
            'name' => 'Anil Sharma',
            'agent_id' => $agentId,
            'email' => $email,
            'phone' => '9876543299',
            'password' => 'secret123',
            'rank_id' => $this->rank->id,
            'address' => '123 Market Road, City',
            'joining_date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->agentUser1)->post(route('agent.agents.store'), $payload);

        $response->assertRedirect(route('agent.agents.index'));
        $response->assertSessionHas('success');

        // Check agent record created with parent_agent_id = agent1 and branch = branch1
        // Recruited agents are created in 'pending' status awaiting Super Admin verification
        $this->assertDatabaseHas('agents', [
            'name' => 'Anil Sharma',
            'email' => $email,
            'agent_id' => $agentId,
            'branch_id' => $this->branch1->id,
            'parent_agent_id' => $this->agent1->id,
            'status' => 'pending',
        ]);

        // Check synced user record created
        $this->assertDatabaseHas('users', [
            'name' => 'Anil Sharma',
            'email' => $email,
            'branch_id' => $this->branch1->id,
        ]);

        // Verify newly created agent user exists
        $newUser = User::where('email', $email)->first();
        $this->assertNotNull($newUser);
        $this->assertTrue(Hash::check('secret123', $newUser->password));
        $this->assertTrue($newUser->isAgent());
    }

    public function test_agent_cannot_recruit_agent_with_higher_priority_rank(): void
    {
        // Higher rank with level higher than sponsor agent
        $higherRank = AgentRank::where('level', '>', (int) $this->rank->level)->first();
        if (! $higherRank) {
            $higherRank = AgentRank::create([
                'code' => 'HIGHER_'.uniqid(),
                'name' => 'Top Rank '.uniqid(),
                'level' => ((int) AgentRank::max('level')) + 1,
                'sort_order' => ((int) AgentRank::max('sort_order')) + 1,
                'status' => 'active',
            ]);
        }

        $payload = [
            'name' => 'Attempted Higher Rank Agent',
            'email' => 'higher_'.uniqid().'@test.com',
            'phone' => '9876543211',
            'rank_id' => $higherRank->id,
            'joining_date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->agentUser1)->post(route('agent.agents.store'), $payload);

        $response->assertSessionHasErrors('rank_id');
    }

    public function test_agent_can_recruit_agent_with_same_or_lower_priority_rank(): void
    {
        // Lower or equal rank with level <= sponsor agent
        $lowerRank = AgentRank::where('level', '<', (int) $this->rank->level)->first() ?? $this->rank;

        $email = 'lower_'.uniqid().'@test.com';
        $payload = [
            'name' => 'Lower Rank Agent',
            'email' => $email,
            'phone' => '9876543219',
            'rank_id' => $lowerRank->id,
            'joining_date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->agentUser1)->post(route('agent.agents.store'), $payload);

        $response->assertRedirect(route('agent.agents.index'));
        $this->assertDatabaseHas('agents', [
            'email' => $email,
            'rank_id' => $lowerRank->id,
            'status' => 'pending',
        ]);
    }

    public function test_pending_agent_cannot_log_in_until_super_admin_approves(): void
    {
        $email = 'pending_login_'.uniqid().'@test.com';
        $pCode = 'PEND_'.rand(1000, 9999);

        $user = User::create([
            'name' => 'Pending Agent User',
            'email' => $email,
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch1->id,
        ]);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $user->roles()->attach($agentRole->id);

        $pendingAgent = Agent::create([
            'user_id' => $user->id,
            'member_code' => $pCode,
            'agent_id' => $pCode,
            'name' => 'Pending Agent User',
            'email' => $email,
            'password' => Hash::make('password123'),
            'phone' => '9876543200',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'parent_agent_id' => $this->agent1->id,
            'status' => 'pending',
            'joining_date' => now()->toDateString(),
        ]);

        // Attempt login via superadmin.login.post
        $response = $this->post(route('superadmin.login.post'), [
            'login' => $email,
            'password' => 'password123',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors();
    }

    public function test_super_admin_can_verify_and_accept_pending_agent(): void
    {
        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $superAdmin = User::create([
            'name' => 'Master Super Admin',
            'email' => 'superadmin_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
        ]);
        $superAdmin->roles()->attach($superAdminRole->id);

        $email = 'to_verify_'.uniqid().'@test.com';
        $vCode = 'VER_'.rand(1000, 9999);
        $agentToVerify = Agent::create([
            'member_code' => $vCode,
            'agent_id' => $vCode,
            'name' => 'Awaiting Verification Agent',
            'email' => $email,
            'password' => Hash::make('password123'),
            'phone' => '9876543201',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'parent_agent_id' => $this->agent1->id,
            'status' => 'pending',
            'joining_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.agents.accept', $agentToVerify));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('agents', [
            'id' => $agentToVerify->id,
            'status' => 'active',
        ]);
    }

    public function test_super_admin_can_reject_pending_agent(): void
    {
        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $superAdmin = User::create([
            'name' => 'Master Super Admin',
            'email' => 'superadmin_rej_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
        ]);
        $superAdmin->roles()->attach($superAdminRole->id);

        $email = 'to_reject_'.uniqid().'@test.com';
        $rCode = 'REJ_'.rand(1000, 9999);
        $agentToReject = Agent::create([
            'member_code' => $rCode,
            'agent_id' => $rCode,
            'name' => 'Awaiting Rejection Agent',
            'email' => $email,
            'password' => Hash::make('password123'),
            'phone' => '9876543202',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'parent_agent_id' => $this->agent1->id,
            'status' => 'pending',
            'joining_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($superAdmin)->post(route('admin.agents.reject', $agentToReject));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('agents', [
            'id' => $agentToReject->id,
            'status' => 'rejected',
        ]);
    }

    public function test_non_super_admin_cannot_accept_or_reject_agent(): void
    {
        $pCode = 'NOAUTH_'.rand(1000, 9999);
        $pendingAgent = Agent::create([
            'member_code' => $pCode,
            'agent_id' => $pCode,
            'name' => 'Protected Agent',
            'email' => 'noauth_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'phone' => '9876543203',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'parent_agent_id' => $this->agent1->id,
            'status' => 'pending',
            'joining_date' => now()->toDateString(),
        ]);

        // Sponsoring agent tries to approve -> 403 Forbidden
        $response = $this->actingAs($this->agentUser1)->post(route('admin.agents.accept', $pendingAgent));
        $response->assertStatus(403);
    }

    public function test_agent_recruiting_auto_generates_agent_id_when_omitted(): void
    {
        $email = 'autorecruit_'.uniqid().'@test.com';

        $payload = [
            'name' => 'Geetha Kumari',
            'email' => $email,
            'phone' => '9876543288',
            'rank_id' => $this->rank->id,
            'joining_date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->agentUser1)->post(route('agent.agents.store'), $payload);

        $response->assertRedirect(route('agent.agents.index'));
        $response->assertSessionHas('success');

        $created = Agent::where('email', $email)->first();
        $this->assertNotNull($created);
        $this->assertNotEmpty($created->agent_id);
        $this->assertEquals($this->agent1->id, $created->parent_agent_id);
        $this->assertEquals($this->branch1->id, $created->branch_id);
    }

    public function test_agent_recruiting_validates_required_fields_and_uniqueness(): void
    {
        // 1. Missing name & phone
        $response = $this->actingAs($this->agentUser1)->post(route('agent.agents.store'), [
            'email' => 'valid_'.uniqid().'@test.com',
        ]);
        $response->assertSessionHasErrors(['name', 'phone']);

        // 2. Duplicate email
        $response = $this->actingAs($this->agentUser1)->post(route('agent.agents.store'), [
            'name' => 'Duplicate Email Agent',
            'email' => $this->agentUser1->email,
            'phone' => '9876543277',
        ]);
        $response->assertSessionHasErrors('email');

        // 3. Duplicate agent_id
        $response = $this->actingAs($this->agentUser1)->post(route('agent.agents.store'), [
            'name' => 'Duplicate ID Agent',
            'agent_id' => $this->agent1->agent_id,
            'email' => 'unique_'.uniqid().'@test.com',
            'phone' => '9876543276',
        ]);
        $response->assertSessionHasErrors('agent_id');
    }

    public function test_agent_can_view_own_downline_agent_profile(): void
    {
        $dlShowCode = 'DLS_'.rand(1000, 9999);
        $downline = Agent::create([
            'member_code' => $dlShowCode,
            'agent_id' => $dlShowCode,
            'name' => 'Viewable Downline Member',
            'email' => 'viewable_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'phone' => '9876543266',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'parent_agent_id' => $this->agent1->id,
            'status' => 'active',
            'joining_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->agentUser1)->get(route('agent.agents.show', $downline));

        $response->assertStatus(200);
        $response->assertSee('Viewable Downline Member');
        $response->assertSee($downline->agent_id);
    }

    public function test_agent_cannot_view_agents_outside_their_direct_downline(): void
    {
        // Downline under Agent 2
        $forCode = 'FOR_'.rand(1000, 9999);
        $foreignDownline = Agent::create([
            'member_code' => $forCode,
            'agent_id' => $forCode,
            'name' => 'Foreign Network Agent',
            'email' => 'foreign_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'phone' => '9876543255',
            'branch_id' => $this->branch2->id,
            'rank_id' => $this->rank->id,
            'parent_agent_id' => $this->agent2->id,
            'status' => 'active',
            'joining_date' => now()->toDateString(),
        ]);

        // Agent 1 attempts to view Agent 2's downline -> 403 Forbidden
        $response = $this->actingAs($this->agentUser1)->get(route('agent.agents.show', $foreignDownline));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_agent_downlines(): void
    {
        $response = $this->get(route('agent.agents.index'));
        $response->assertRedirect();

        $responsePost = $this->post(route('agent.agents.store'), [
            'name' => 'Hacker Agent',
            'email' => 'hack@test.com',
            'phone' => '9999999999',
        ]);
        $responsePost->assertRedirect();
    }
}
