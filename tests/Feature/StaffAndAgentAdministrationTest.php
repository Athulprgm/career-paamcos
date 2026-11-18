<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffAndAgentAdministrationTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected ?User $branchAdmin = null;

    protected ?User $regularStaffUser = null;

    protected ?Branch $branch1 = null;

    protected ?Branch $branch2 = null;

    protected ?StaffPosition $position = null;

    protected ?AgentRank $rank = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Super Admin user
        $this->superAdmin = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->first();

        $this->branch1 = Branch::first() ?? Branch::create([
            'name' => 'Branch 1',
            'code' => 'BR_FALLBACK_01',
            'is_active' => true,
        ]);
        $this->branch2 = Branch::where('id', '!=', $this->branch1->id)->first() ?? Branch::create([
            'name' => 'Branch 2',
            'code' => 'BR_FALLBACK_02',
            'is_active' => true,
        ]);

        // Branch Admin user
        $admin = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))
            ->whereNotNull('branch_id')
            ->first();

        if (! $admin) {
            $adminRole = Role::where('slug', 'admin')->first();
            $admin = User::create([
                'name' => 'Test Branch Admin',
                'email' => 'test_branch_admin_'.uniqid().'@example.com',
                'password' => bcrypt('password'),
                'branch_id' => $this->branch1->id,
            ]);
            $admin->roles()->attach($adminRole->id);
        }

        $this->branchAdmin = $admin;
        $this->branch1 = Branch::find($admin->branch_id);
        $this->branch2 = Branch::where('id', '!=', $this->branch1->id)->first();

        // Regular Staff user (without admin role)
        $staffUser = User::whereHas('roles', fn ($q) => $q->where('slug', 'staff'))->first();
        if (! $staffUser) {
            $staffRole = Role::where('slug', 'staff')->first();
            $staffUser = User::create([
                'name' => 'Test Regular Staff',
                'email' => 'test_regular_staff_'.uniqid().'@example.com',
                'password' => bcrypt('password'),
                'branch_id' => $this->branch1->id,
            ]);
            $staffUser->roles()->attach($staffRole->id);
        }
        $this->regularStaffUser = $staffUser;

        // Position & Rank
        $this->position = StaffPosition::first() ?? StaffPosition::create(['name' => 'Officer', 'sort_order' => 1, 'status' => 'active']);
        $this->rank = AgentRank::first() ?? AgentRank::create(['name' => 'Agent Rank 1', 'code' => 'R1', 'level' => 1, 'sort_order' => 1, 'status' => 'active']);
    }

    // ─── STAFF TESTS ──────────────────────────────────────────────────────────

    public function test_super_admin_can_add_staff_and_assign_branch_and_position(): void
    {
        $employeeId = 'STF_TEST_'.rand(1000, 9999);
        $email = 'staff_'.uniqid().'@example.com';

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.staff.store'), [
                'name' => 'Test Staff Member',
                'employee_id' => $employeeId,
                'email' => $email,
                'phone' => '+91 9999888877',
                'branch_id' => $this->branch1->id,
                'position_id' => $this->position->id,
                'joining_date' => '2026-01-10',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.staff.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('staff', [
            'employee_id' => $employeeId,
            'email' => $email,
            'branch_id' => $this->branch1->id,
            'position_id' => $this->position->id,
            'status' => 'active',
        ]);
    }

    public function test_can_view_and_edit_staff(): void
    {
        $staff = Staff::create([
            'name' => 'Viewable Staff',
            'employee_id' => 'STF_VIEW_'.rand(1000, 9999),
            'email' => 'view_staff_'.uniqid().'@example.com',
            'phone' => '+91 9888777666',
            'branch_id' => $this->branch1->id,
            'position_id' => $this->position->id,
            'joining_date' => '2026-02-01',
            'status' => 'active',
        ]);

        // View staff
        $viewResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.staff.show', $staff));

        $viewResponse->assertOk();
        $viewResponse->assertSee($staff->name);
        $viewResponse->assertSee($staff->employee_id);

        // Edit staff
        $updateResponse = $this->actingAs($this->superAdmin)
            ->put(route('admin.staff.update', $staff), [
                'name' => 'Updated Staff Name',
                'employee_id' => $staff->employee_id,
                'email' => $staff->email,
                'phone' => '+91 9111222333',
                'branch_id' => $this->branch1->id,
                'position_id' => $this->position->id,
                'joining_date' => '2026-02-01',
                'status' => 'active',
            ]);

        $updateResponse->assertRedirect(route('admin.staff.index'));
        $this->assertDatabaseHas('staff', [
            'id' => $staff->id,
            'name' => 'Updated Staff Name',
            'phone' => '+91 9111222333',
        ]);
    }

    public function test_can_deactivate_and_reactivate_staff(): void
    {
        $staff = Staff::create([
            'name' => 'Toggle Staff',
            'employee_id' => 'STF_TOGGLE_'.rand(1000, 9999),
            'email' => 'toggle_staff_'.uniqid().'@example.com',
            'phone' => '+91 9555444333',
            'branch_id' => $this->branch1->id,
            'position_id' => $this->position->id,
            'joining_date' => '2026-03-01',
            'status' => 'active',
        ]);

        // Deactivate via toggle
        $this->actingAs($this->superAdmin)
            ->post(route('admin.staff.toggle-status', $staff));

        $staff->refresh();
        $this->assertEquals('inactive', $staff->status);

        // Reactivate via toggle
        $this->actingAs($this->superAdmin)
            ->post(route('admin.staff.toggle-status', $staff));

        $staff->refresh();
        $this->assertEquals('active', $staff->status);
    }

    // ─── AGENT TESTS ──────────────────────────────────────────────────────────

    public function test_super_admin_can_add_agent_and_assign_branch_and_rank(): void
    {
        $agentId = 'AGT_TEST_'.rand(1000, 9999);
        $email = 'agent_'.uniqid().'@example.com';

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.store'), [
                'name' => 'Test Agent Member',
                'agent_id' => $agentId,
                'email' => $email,
                'phone' => '+91 9777666555',
                'branch_id' => $this->branch1->id,
                'rank_id' => $this->rank->id,
                'joining_date' => '2026-01-15',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.agents.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('agents', [
            'agent_id' => $agentId,
            'email' => $email,
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'status' => 'active',
        ]);
    }

    public function test_create_agent_view_renders_password_field(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.agents.create'));

        $response->assertOk();
        $response->assertSee('name="send_welcome_email"', false);
    }

    public function test_agents_index_renders_downline_agents_nested_under_their_parent(): void
    {
        $suffix = strtoupper(substr(uniqid(), -6));

        $parentAgent = Agent::create([
            'member_code' => 'AGT_TREE_P_'.$suffix,
            'agent_id' => 'AGT_TREE_P_'.$suffix,
            'name' => 'Tree Parent '.$suffix,
            'email' => 'tree_parent_'.strtolower($suffix).'@example.com',
            'phone' => '9876543210',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'status' => 'active',
            'joining_date' => now(),
        ]);

        $childAgent = Agent::create([
            'member_code' => 'AGT_TREE_C_'.$suffix,
            'agent_id' => 'AGT_TREE_C_'.$suffix,
            'name' => 'Tree Child '.$suffix,
            'email' => 'tree_child_'.strtolower($suffix).'@example.com',
            'phone' => '9876543211',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'parent_agent_id' => $parentAgent->id,
            'status' => 'active',
            'joining_date' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.agents.index'));

        $response->assertOk();
        // The child must render inside the parent's subtree (depth 1), not as a root
        $response->assertSeeInOrder([
            'data-agent-id="'.$parentAgent->id.'" data-depth="0"',
            'data-agent-id="'.$childAgent->id.'" data-depth="1"',
        ], false);
    }

    public function test_agents_index_renders_parent_child_cycles_as_roots_instead_of_dropping_them(): void
    {
        $suffix = strtoupper(substr(uniqid(), -6));

        $agentA = Agent::create([
            'member_code' => 'AGT_CYCLE_A_'.$suffix,
            'agent_id' => 'AGT_CYCLE_A_'.$suffix,
            'name' => 'Cycle Agent A '.$suffix,
            'email' => 'cycle_a_'.strtolower($suffix).'@example.com',
            'phone' => '9876543210',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'status' => 'active',
            'joining_date' => now(),
        ]);

        $agentB = Agent::create([
            'member_code' => 'AGT_CYCLE_B_'.$suffix,
            'agent_id' => 'AGT_CYCLE_B_'.$suffix,
            'name' => 'Cycle Agent B '.$suffix,
            'email' => 'cycle_b_'.strtolower($suffix).'@example.com',
            'phone' => '9876543211',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'parent_agent_id' => $agentA->id,
            'status' => 'active',
            'joining_date' => now(),
        ]);

        // Close the loop: A reports to B while B already reports to A
        $agentA->parent_agent_id = $agentB->id;
        $agentA->save();

        $response = $this->actingAs($this->superAdmin)->get(route('admin.agents.index'));

        $response->assertOk();
        $response->assertSee('data-agent-id="'.$agentA->id.'" data-depth="0"', false);
        $response->assertSee('data-agent-id="'.$agentB->id.'" data-depth="0"', false);
    }

    public function test_super_admin_can_create_agent_with_custom_password_and_login(): void
    {
        $agentId = 'AGT_PWD_'.rand(1000, 9999);
        $email = 'agent_pwd_'.uniqid().'@example.com';
        $customPassword = 'MySecretPassword123';

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.store'), [
                'name' => 'Agent With Password',
                'agent_id' => $agentId,
                'email' => $email,
                'password' => $customPassword,
                'phone' => '+91 9888777666',
                'branch_id' => $this->branch1->id,
                'rank_id' => $this->rank->id,
                'joining_date' => '2026-03-01',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.agents.index'));

        $agent = Agent::where('agent_id', $agentId)->first();
        $this->assertNotNull($agent);
        $this->assertNotNull($agent->user_id);

        $user = $agent->user;
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check($customPassword, $user->password));
        $this->assertTrue($user->isAgent());

        // Test login with agent ID
        $this->post(route('superadmin.logout'));
        $loginResponse = $this->post(route('superadmin.login.post'), [
            'login' => $agentId,
            'password' => $customPassword,
        ]);
        $loginResponse->assertRedirect(route('agent.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_can_view_and_edit_agent(): void
    {
        $agent = Agent::create([
            'name' => 'Viewable Agent',
            'agent_id' => 'AGT_VIEW_'.rand(1000, 9999),
            'email' => 'view_agent_'.uniqid().'@example.com',
            'phone' => '+91 9666555444',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'joining_date' => '2026-02-15',
            'status' => 'active',
        ]);

        // View agent
        $viewResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.agents.show', $agent));

        $viewResponse->assertOk();
        $viewResponse->assertSee($agent->name);
        $viewResponse->assertSee($agent->agent_id);

        // Edit agent
        $updateResponse = $this->actingAs($this->superAdmin)
            ->put(route('admin.agents.update', $agent), [
                'name' => 'Updated Agent Name',
                'agent_id' => $agent->agent_id,
                'email' => $agent->email,
                'phone' => '+91 9222333444',
                'branch_id' => $this->branch1->id,
                'rank_id' => $this->rank->id,
                'joining_date' => '2026-02-15',
                'status' => 'active',
            ]);

        $updateResponse->assertRedirect(route('admin.agents.index'));
        $this->assertDatabaseHas('agents', [
            'id' => $agent->id,
            'name' => 'Updated Agent Name',
            'phone' => '+91 9222333444',
        ]);
    }

    public function test_can_deactivate_and_reactivate_agent(): void
    {
        $agent = Agent::create([
            'name' => 'Toggle Agent',
            'agent_id' => 'AGT_TOGGLE_'.rand(1000, 9999),
            'email' => 'toggle_agent_'.uniqid().'@example.com',
            'phone' => '+91 9333222111',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'joining_date' => '2026-03-15',
            'status' => 'active',
        ]);

        // Deactivate via toggle
        $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.toggle-status', $agent));

        $agent->refresh();
        $this->assertEquals('inactive', $agent->status);

        // Reactivate via toggle
        $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.toggle-status', $agent));

        $agent->refresh();
        $this->assertEquals('active', $agent->status);
    }

    // ─── VALIDATION TESTS ─────────────────────────────────────────────────────

    public function test_validation_rejects_duplicate_employee_id(): void
    {
        $existingStaff = Staff::first() ?? Staff::create([
            'name' => 'Existing Staff',
            'employee_id' => 'EMP_EXISTING_01',
            'staff_code' => 'STF_EXISTING_01',
            'email' => 'existing_staff_01@example.com',
            'phone' => '+91 9876543210',
            'branch_id' => $this->branch1->id,
            'position_id' => $this->position->id,
            'joining_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.staff.store'), [
                'name' => 'Duplicate ID Staff',
                'employee_id' => $existingStaff->employee_id,
                'email' => 'unique_'.uniqid().'@example.com',
                'phone' => '+91 9876543210',
                'branch_id' => $this->branch1->id,
                'position_id' => $this->position->id,
                'joining_date' => '2026-01-01',
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors('employee_id');
    }

    public function test_validation_rejects_duplicate_agent_id(): void
    {
        $existingAgent = Agent::first() ?? Agent::create([
            'name' => 'Existing Agent',
            'agent_id' => 'AGT_EXISTING_01',
            'member_code' => 'AGT_EXISTING_01',
            'email' => 'existing_agent_01@example.com',
            'phone' => '+91 9876543210',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'joining_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.store'), [
                'name' => 'Duplicate Agent',
                'agent_id' => $existingAgent->agent_id,
                'email' => 'unique_'.uniqid().'@example.com',
                'phone' => '+91 9876543210',
                'branch_id' => $this->branch1->id,
                'rank_id' => $this->rank->id,
                'joining_date' => '2026-01-01',
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors('agent_id');
    }

    public function test_validation_rejects_missing_required_fields(): void
    {
        $staffResponse = $this->actingAs($this->superAdmin)
            ->post(route('admin.staff.store'), []);

        $staffResponse->assertSessionHasErrors(['name', 'employee_id', 'email', 'phone', 'branch_id', 'position_id', 'joining_date', 'status']);

        $agentResponse = $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.store'), []);

        $agentResponse->assertSessionHasErrors(['name', 'email', 'phone', 'rank_id', 'joining_date', 'status']);
        $agentResponse->assertSessionDoesntHaveErrors(['branch_id', 'agent_id']);
    }

    public function test_agent_id_is_automatically_generated_when_omitted(): void
    {
        $email1 = 'suresh_'.uniqid().'@example.com';
        $response1 = $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.store'), [
                'name' => 'Suresh Kumar',
                'agent_id' => '', // Left blank intentionally for auto-generation
                'email' => $email1,
                'phone' => '+91 9988776655',
                'rank_id' => $this->rank->id,
                'joining_date' => '2026-10-01',
                'status' => 'active',
            ]);

        $response1->assertRedirect(route('admin.agents.index'));
        $createdAgent1 = Agent::where('email', $email1)->first();
        $this->assertNotNull($createdAgent1);
        $this->assertEquals('PAG10261', $createdAgent1->agent_id);
        $this->assertEquals('PAG10261', $createdAgent1->member_code);

        // Second agent with same joining month/year should increment sequence
        $email2 = 'sunil_'.uniqid().'@example.com';
        $response2 = $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.store'), [
                'name' => 'Sunil Joshi',
                'agent_id' => '', // Left blank intentionally for auto-generation
                'email' => $email2,
                'phone' => '+91 9988776656',
                'rank_id' => $this->rank->id,
                'joining_date' => '2026-10-15',
                'status' => 'active',
            ]);

        $response2->assertRedirect(route('admin.agents.index'));
        $createdAgent2 = Agent::where('email', $email2)->first();
        $this->assertNotNull($createdAgent2);
        $this->assertEquals('PAG10262', $createdAgent2->agent_id);
        $this->assertEquals('PAG10262', $createdAgent2->member_code);
    }

    public function test_next_id_endpoint_returns_json_preview(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('admin.agents.next-id', [
                'name' => 'Ramesh Patel',
                'joining_date' => '2026-12-05',
            ]));

        $response->assertOk();
        $response->assertJsonStructure(['success', 'agent_id']);
        $this->assertTrue($response->json('success'));
        $this->assertEquals('PAG12261', $response->json('agent_id'));
    }

    public function test_agent_can_be_created_as_free_field_agent_without_branch(): void
    {
        $uniqueId = 'AG_FREE_'.rand(1000, 9999);
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.store'), [
                'name' => 'Free Field Agent User',
                'agent_id' => $uniqueId,
                'email' => 'freefield_'.uniqid().'@example.com',
                'phone' => '+91 9988776655',
                'branch_id' => null, // Free field agent: no particular branch assigned
                'place' => 'City Center Area',
                'rank_id' => $this->rank->id,
                'joining_date' => '2026-02-01',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.agents.index'));
        $this->assertDatabaseHas('agents', [
            'agent_id' => $uniqueId,
            'branch_id' => null,
            'place' => 'City Center Area',
            'status' => 'active',
        ]);
    }

    public function test_validation_rejects_invalid_branch_position_and_rank(): void
    {
        // Invalid branch & position for staff
        $staffResponse = $this->actingAs($this->superAdmin)
            ->post(route('admin.staff.store'), [
                'name' => 'Invalid Ref Staff',
                'employee_id' => 'STF_INVALID_'.rand(1000, 9999),
                'email' => 'invalid_'.uniqid().'@example.com',
                'phone' => '+91 9876543210',
                'branch_id' => 999999, // Non-existent branch
                'position_id' => 999999, // Non-existent position
                'joining_date' => '2026-01-01',
                'status' => 'active',
            ]);

        $staffResponse->assertSessionHasErrors(['branch_id', 'position_id']);

        // Invalid rank for agent
        $agentResponse = $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.store'), [
                'name' => 'Invalid Ref Agent',
                'agent_id' => 'AGT_INVALID_'.rand(1000, 9999),
                'email' => 'invalid_agt_'.uniqid().'@example.com',
                'phone' => '+91 9876543210',
                'branch_id' => $this->branch1->id,
                'rank_id' => 999999, // Non-existent rank
                'joining_date' => '2026-01-01',
                'status' => 'active',
            ]);

        $agentResponse->assertSessionHasErrors('rank_id');
    }

    public function test_validation_rejects_reporting_manager_from_different_branch(): void
    {
        // Staff 1 in Branch 2
        $managerInBranch2 = Staff::create([
            'name' => 'Branch 2 Manager',
            'employee_id' => 'STF_B2_'.rand(1000, 9999),
            'email' => 'b2_mgr_'.uniqid().'@example.com',
            'phone' => '+91 9123456789',
            'branch_id' => $this->branch2->id,
            'position_id' => $this->position->id,
            'joining_date' => '2026-01-01',
            'status' => 'active',
        ]);

        // Attempt to create staff in Branch 1 reporting to manager in Branch 2
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.staff.store'), [
                'name' => 'Cross Branch Staff',
                'employee_id' => 'STF_CROSS_'.rand(1000, 9999),
                'email' => 'cross_'.uniqid().'@example.com',
                'phone' => '+91 9123456780',
                'branch_id' => $this->branch1->id,
                'position_id' => $this->position->id,
                'reporting_manager_id' => $managerInBranch2->id,
                'joining_date' => '2026-01-01',
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors('reporting_manager_id');
    }

    // ─── AUTHORIZATION & BRANCH RESTRICTION TESTS ─────────────────────────────

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.staff.index'))
            ->assertRedirect(route('superadmin.login'));

        $this->get(route('admin.agents.index'))
            ->assertRedirect(route('superadmin.login'));
    }

    public function test_regular_staff_user_is_denied_admin_access(): void
    {
        $this->actingAs($this->regularStaffUser)
            ->get(route('admin.staff.index'))
            ->assertForbidden();

        $this->actingAs($this->regularStaffUser)
            ->get(route('admin.agents.index'))
            ->assertForbidden();
    }

    public function test_branch_admin_is_structurally_restricted_to_own_branch(): void
    {
        // Create staff in Branch 2
        $branch2Staff = Staff::create([
            'name' => 'Branch 2 Other Staff',
            'employee_id' => 'STF_OTHER_'.rand(1000, 9999),
            'email' => 'other_'.uniqid().'@example.com',
            'phone' => '+91 9990001112',
            'branch_id' => $this->branch2->id,
            'position_id' => $this->position->id,
            'joining_date' => '2026-01-01',
            'status' => 'active',
        ]);

        // Branch Admin of Branch 1 cannot view Branch 2 staff
        $this->actingAs($this->branchAdmin)
            ->get(route('admin.staff.show', $branch2Staff))
            ->assertForbidden();

        // Branch Admin cannot edit Branch 2 staff
        $this->actingAs($this->branchAdmin)
            ->get(route('admin.staff.edit', $branch2Staff))
            ->assertForbidden();

        // Branch Admin cannot toggle Branch 2 staff
        $this->actingAs($this->branchAdmin)
            ->post(route('admin.staff.toggle-status', $branch2Staff))
            ->assertForbidden();

        // Branch Admin's list automatically filters out Branch 2 staff
        $listResponse = $this->actingAs($this->branchAdmin)
            ->get(route('admin.staff.index'));

        $listResponse->assertOk();
        $listResponse->assertDontSee($branch2Staff->employee_id);
    }

    // ─── SEARCH, FILTERS & PAGINATION TESTS ───────────────────────────────────

    public function test_search_and_filters_work(): void
    {
        $uniqueStaff = Staff::create([
            'name' => 'UniqueSearchPerson',
            'employee_id' => 'STF_SEARCH_'.rand(1000, 9999),
            'email' => 'search_unique_'.uniqid().'@example.com',
            'phone' => '+91 9000111222',
            'branch_id' => $this->branch1->id,
            'position_id' => $this->position->id,
            'joining_date' => '2026-01-01',
            'status' => 'active',
        ]);

        // Search by name
        $searchResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.staff.index', ['search' => 'UniqueSearchPerson']));

        $searchResponse->assertOk();
        $searchResponse->assertSee($uniqueStaff->name);
        $searchResponse->assertSee($uniqueStaff->employee_id);

        // Filter by position
        $posResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.staff.index', ['position_id' => $this->position->id]));

        $posResponse->assertOk();
        $posResponse->assertSee($uniqueStaff->name);

        // Filter by status
        $statusResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.staff.index', ['status' => 'inactive']));

        $statusResponse->assertOk();
        $statusResponse->assertDontSee($uniqueStaff->name);
    }

    public function test_agent_can_be_assigned_reporting_staff_within_same_branch(): void
    {
        $staff = Staff::create([
            'name' => 'Staff Supervisor',
            'employee_id' => 'STF_SUP_'.rand(1000, 9999),
            'email' => 'sup_'.uniqid().'@example.com',
            'phone' => '+91 9123456780',
            'branch_id' => $this->branch1->id,
            'position_id' => $this->position->id,
            'joining_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $agentId = 'AGT_REP_'.rand(1000, 9999);
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.store'), [
                'name' => 'Agent Under Staff',
                'agent_id' => $agentId,
                'email' => 'rep_agent_'.uniqid().'@example.com',
                'phone' => '+91 9777111222',
                'branch_id' => $this->branch1->id,
                'rank_id' => $this->rank->id,
                'reporting_staff_id' => $staff->id,
                'joining_date' => '2026-01-20',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.agents.index'));
        $this->assertDatabaseHas('agents', [
            'agent_id' => $agentId,
            'reporting_staff_id' => $staff->id,
        ]);
    }

    public function test_super_admin_can_assign_agent_to_any_branch_and_place(): void
    {
        $agentId = 'AGT_FREE_'.rand(1000, 9999);
        $email = 'free_agt_'.uniqid().'@example.com';

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.store'), [
                'name' => 'Free Agent In Kochi',
                'agent_id' => $agentId,
                'email' => $email,
                'phone' => '+91 9777111444',
                'place' => 'Kochi Central Area',
                'address' => '42 MG Road, Kochi',
                'branch_id' => $this->branch2->id,
                'rank_id' => $this->rank->id,
                'joining_date' => '2026-01-20',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.agents.index'));
        $this->assertDatabaseHas('agents', [
            'agent_id' => $agentId,
            'place' => 'Kochi Central Area',
            'branch_id' => $this->branch2->id,
        ]);
    }

    public function test_super_admin_can_assign_cross_branch_parent_agent_and_reporting_staff(): void
    {
        $otherBranchStaff = Staff::create([
            'name' => 'Other Branch Staff',
            'employee_id' => 'STF_OTHER_'.rand(1000, 9999),
            'email' => 'other_b_'.uniqid().'@example.com',
            'phone' => '+91 9123456781',
            'branch_id' => $this->branch2->id,
            'position_id' => $this->position->id,
            'joining_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $otherBranchParentAgent = Agent::create([
            'member_code' => 'AGT_PARENT_'.rand(1000, 9999),
            'agent_id' => 'AGT_PARENT_'.rand(1000, 9999),
            'name' => 'Parent Agent In Branch 2',
            'email' => 'parent_b2_'.uniqid().'@example.com',
            'phone' => '9876543219',
            'branch_id' => $this->branch2->id,
            'rank_id' => $this->rank->id,
            'status' => 'active',
            'joining_date' => now(),
        ]);

        $agentId = 'AGT_CROSS_'.rand(1000, 9999);
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.store'), [
                'name' => 'Agent Cross Branch Free',
                'agent_id' => $agentId,
                'email' => 'cross_free_'.uniqid().'@example.com',
                'phone' => '+91 9777111333',
                'place' => 'Mumbai Suburban',
                'branch_id' => $this->branch1->id,
                'rank_id' => $this->rank->id,
                'parent_agent_id' => $otherBranchParentAgent->id,
                'reporting_staff_id' => $otherBranchStaff->id,
                'joining_date' => '2026-01-20',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('admin.agents.index'));
        $this->assertDatabaseHas('agents', [
            'agent_id' => $agentId,
            'branch_id' => $this->branch1->id,
            'place' => 'Mumbai Suburban',
            'parent_agent_id' => $otherBranchParentAgent->id,
            'reporting_staff_id' => $otherBranchStaff->id,
        ]);
    }

    public function test_super_admin_can_verify_and_accept_agent_with_assigned_branch_and_place(): void
    {
        $pendingAgent = Agent::create([
            'member_code' => 'AGT_PEND_'.rand(1000, 9999),
            'agent_id' => 'AGT_PEND_'.rand(1000, 9999),
            'name' => 'Recruited Pending Agent',
            'email' => 'pend_'.uniqid().'@example.com',
            'phone' => '9876543218',
            'branch_id' => $this->branch1->id,
            'rank_id' => $this->rank->id,
            'status' => Agent::STATUS_PENDING,
            'joining_date' => now(),
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.agents.accept', $pendingAgent), [
                'place' => 'Ernakulam North',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('agents', [
            'id' => $pendingAgent->id,
            'status' => Agent::STATUS_ACTIVE,
            'branch_id' => null,
            'place' => 'Ernakulam North',
        ]);
    }

    public function test_staff_cannot_report_to_self(): void
    {
        $staff = Staff::create([
            'name' => 'Self Staff',
            'employee_id' => 'STF_SELF_'.rand(1000, 9999),
            'email' => 'self_'.uniqid().'@example.com',
            'phone' => '+91 9123456782',
            'branch_id' => $this->branch1->id,
            'position_id' => $this->position->id,
            'joining_date' => '2026-01-01',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.staff.update', $staff), [
                'name' => 'Self Staff Updated',
                'employee_id' => $staff->employee_id,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'branch_id' => $staff->branch_id,
                'position_id' => $staff->position_id,
                'reporting_manager_id' => $staff->id, // Self!
                'joining_date' => '2026-01-01',
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors('reporting_manager_id');
    }
}
