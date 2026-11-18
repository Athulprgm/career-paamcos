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
use Tests\TestCase;

class SystemRefinementsTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected ?User $branchAdmin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->first();

        $branch = Branch::first() ?? Branch::create([
            'name' => 'Main Branch',
            'code' => 'BR_SR_01',
            'is_active' => true,
        ]);
        $adminRole = Role::where('slug', 'admin')->first();

        $this->branchAdmin = User::create([
            'name' => 'Branch Admin Refinement Tester',
            'email' => 'branch_admin_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $branch->id,
        ]);
        $this->branchAdmin->roles()->attach($adminRole->id);

        StaffPosition::firstOrCreate(
            ['name' => 'Manager'],
            ['description' => 'Branch Manager', 'sort_order' => 1, 'status' => 'active']
        );

        AgentRank::firstOrCreate(
            ['level' => 1],
            ['name' => 'Advisor', 'code' => 'ADV', 'description' => 'Financial Advisor', 'sort_order' => 1, 'status' => 'active']
        );
    }

    public function test_superadmin_dashboard_renders_with_banking_statistics(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.dashboard'));

        $response->assertOk();
        $response->assertSee('Total Customers');
        $response->assertSee('Deposit Volume');
        $response->assertSee('Pending Commissions');
    }

    public function test_staff_position_can_be_toggled(): void
    {
        $position = StaffPosition::first();
        $originalStatus = $position->status;
        $expectedStatus = $originalStatus === 'active' ? 'inactive' : 'active';

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.staff-positions.toggle-status', $position));

        $response->assertRedirect();
        $position->refresh();
        $this->assertEquals($expectedStatus, $position->status);
    }

    public function test_staff_positions_index_renders_delete_button_and_modal(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.staff-positions.index'));

        $response->assertOk();
        $response->assertSee('btn-delete-position');
        $response->assertSee('deletePositionModal');
    }

    public function test_staff_positions_create_page_does_not_have_order_field(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.staff-positions.create'));

        $response->assertOk();
        $response->assertDontSee('name="sort_order"', false);
    }

    public function test_staff_position_can_be_created_without_sort_order_and_auto_assigns_order(): void
    {
        $uniqueName = 'Auto Order Pos '.uniqid();
        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.staff-positions.store'), [
                'name' => $uniqueName,
                'description' => 'Created without sort order',
                'status' => 'active',
            ]);

        $response->assertRedirect(route('superadmin.staff-positions.index'));
        $this->assertDatabaseHas('positions', [
            'name' => $uniqueName,
        ]);
        $created = StaffPosition::where('name', $uniqueName)->first();
        $this->assertNotNull($created);
        $this->assertGreaterThan(0, $created->sort_order);
    }

    public function test_staff_position_can_be_deleted_when_unassigned(): void
    {
        $position = StaffPosition::create([
            'name' => 'Temporary Position '.uniqid(),
            'description' => 'To be deleted',
            'sort_order' => 999,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.staff-positions.destroy', $position));

        $response->assertRedirect(route('superadmin.staff-positions.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('positions', ['id' => $position->id]);
    }

    public function test_staff_position_deletion_is_blocked_when_staff_assigned(): void
    {
        $position = StaffPosition::first();
        $branch = Branch::first();

        $staff = Staff::create([
            'user_id' => $this->branchAdmin->id,
            'branch_id' => $branch->id,
            'position_id' => $position->id,
            'employee_id' => 'EMP_'.rand(1000, 9999),
            'name' => 'Delete Guard Staff',
            'email' => 'staff_guard_'.uniqid().'@example.com',
            'phone' => '9876543210',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.staff-positions.destroy', $position));

        $response->assertRedirect(route('superadmin.staff-positions.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('positions', ['id' => $position->id]);
    }

    public function test_agent_ranks_index_renders_delete_button_and_modal(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.agent-ranks.index'));

        $response->assertOk();
        $response->assertSee('btn-delete-rank');
        $response->assertSee('deleteRankModal');
    }

    public function test_agent_rank_can_be_deleted_when_unassigned(): void
    {
        $rank = AgentRank::create([
            'name' => 'Temporary Rank '.uniqid(),
            'code' => 'TMP_RANK_'.uniqid(),
            'description' => 'To be deleted',
            'sort_order' => 999,
            'level' => 99,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.agent-ranks.destroy', $rank));

        $response->assertRedirect(route('superadmin.agent-ranks.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('ranks', ['id' => $rank->id]);
    }

    public function test_agent_rank_can_be_toggled(): void
    {
        $rank = AgentRank::first();
        $originalStatus = $rank->status;
        $expectedStatus = $originalStatus === 'active' ? 'inactive' : 'active';

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.agent-ranks.toggle-status', $rank));

        $response->assertRedirect();
        $rank->refresh();
        $this->assertEquals($expectedStatus, $rank->status);
    }

    public function test_agent_rank_deletion_is_blocked_when_agents_assigned(): void
    {
        $rank = AgentRank::first();
        $branch = Branch::first();

        $agent = Agent::create([
            'user_id' => $this->branchAdmin->id,
            'branch_id' => $branch->id,
            'rank_id' => $rank->id,
            'agent_id' => 'AGT_'.rand(1000, 9999),
            'name' => 'Delete Guard Agent',
            'email' => 'agent_guard_'.uniqid().'@example.com',
            'phone' => '9876543211',
            'joining_date' => '2026-02-15',
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('superadmin.agent-ranks.destroy', $rank));

        $response->assertRedirect(route('superadmin.agent-ranks.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('ranks', ['id' => $rank->id]);
    }

    public function test_branch_admin_can_access_and_update_profile(): void
    {
        $response = $this->actingAs($this->branchAdmin)
            ->get(route('superadmin.profile'));

        $response->assertOk();
        $response->assertSee($this->branchAdmin->name);

        $updateResponse = $this->actingAs($this->branchAdmin)
            ->put(route('superadmin.profile.update'), [
                'name' => 'Updated Branch Admin',
                'email' => $this->branchAdmin->email,
            ]);

        $updateResponse->assertRedirect(route('superadmin.profile'));
        $this->branchAdmin->refresh();
        $this->assertEquals('Updated Branch Admin', $this->branchAdmin->name);
    }

    public function test_settings_index_redirects_safely_to_first_accessible_category(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.settings.index'));

        $response->assertRedirect(route('superadmin.profile'));
    }

    public function test_staff_create_page_does_not_contain_reporting_manager_field(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.staff.create'));

        $response->assertOk();
        $response->assertDontSee('name="reporting_manager_id"', false);
        $response->assertDontSee('Reporting Manager');
    }

    public function test_agent_create_page_rank_options_do_not_contain_percentage(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.agents.create'));

        $response->assertOk();
        $response->assertDontSee('0.00%');
        $response->assertDontSee('%</option>', false);
    }

    public function test_staff_index_page_does_not_contain_reporting_manager_column(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.staff.index'));

        $response->assertOk();
        $response->assertDontSee('<th>Reporting Manager</th>', false);
    }

    public function test_staff_index_page_uses_custom_status_toggle_modal_instead_of_default_confirm(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.staff.index'));

        $response->assertOk();
        $response->assertSee('id="toggleStaffStatusModal"', false);
        $response->assertDontSee('onsubmit="return confirm', false);
    }
}
