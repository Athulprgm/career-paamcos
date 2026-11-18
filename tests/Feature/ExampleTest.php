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

class ExampleTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test that root URL redirects guests to login and get started page is completely removed.
     */
    public function test_root_url_redirects_to_login_for_guests_and_get_started_page_is_completely_removed(): void
    {
        // 1. Root / must redirect guest directly to login
        $response = $this->get('/');
        $response->assertRedirect(route('login'));

        // 2. Following redirects must land on the superadmin login page
        $finalResponse = $this->followRedirects($response);
        $finalResponse->assertOk();
        $finalResponse->assertViewIs('superadmin.auth.login');

        // 3. Verify get started page and text are completely removed
        $finalResponse->assertDontSee('Get Started');
        $this->assertFalse(view()->exists('welcome'));
    }

    /**
     * Test that canonical login route redirects to superadmin login.
     */
    public function test_canonical_login_leads_to_superadmin_login(): void
    {
        $loginResponse = $this->get(route('login'));
        $loginResponse->assertRedirect(route('superadmin.login'));

        $finalResponse = $this->followRedirects($loginResponse);
        $finalResponse->assertOk();
        $finalResponse->assertViewIs('superadmin.auth.login');
    }

    /**
     * Test that authenticated users hitting root URL redirect directly to their dashboard.
     */
    public function test_authenticated_users_hitting_root_url_redirect_to_dashboard(): void
    {
        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'status' => 'active']);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $staffRole = Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'status' => 'active']);

        $branch = Branch::first() ?? Branch::create([
            'name' => 'Test Root Branch',
            'code' => 'BR_ROOT_'.uniqid(),
            'is_active' => true,
        ]);

        // Super Admin
        $superAdmin = User::create([
            'name' => 'Super Admin Root Test',
            'email' => 'superadmin_root_'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
        ]);
        $superAdmin->roles()->sync([$superAdminRole->id]);

        $resSuperAdmin = $this->actingAs($superAdmin)->get('/');
        $resSuperAdmin->assertRedirect(route('superadmin.dashboard'));

        // Branch Admin
        $branchAdmin = User::create([
            'name' => 'Branch Admin Root Test',
            'email' => 'branchadmin_root_'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'branch_id' => $branch->id,
        ]);
        $branchAdmin->roles()->sync([$adminRole->id]);

        $resBranchAdmin = $this->actingAs($branchAdmin)->get('/');
        $resBranchAdmin->assertRedirect(route('branch-admin.dashboard'));

        // Agent
        $agentUser = User::create([
            'name' => 'Agent Root Test',
            'email' => 'agent_root_'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'branch_id' => $branch->id,
        ]);
        $agentUser->roles()->sync([$agentRole->id]);

        $rank = AgentRank::first() ?? AgentRank::create([
            'name' => 'Representative',
            'code' => 'REP_'.uniqid(),
            'level' => 1,
            'is_active' => true,
        ]);

        Agent::create([
            'user_id' => $agentUser->id,
            'member_code' => 'AGT_ROOT_'.rand(10000, 99999),
            'agent_id' => 'AGT_ROOT_'.rand(10000, 99999),
            'name' => $agentUser->name,
            'email' => $agentUser->email,
            'phone' => '98'.rand(10000000, 99999999),
            'branch_id' => $branch->id,
            'rank_id' => $rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $resAgent = $this->actingAs($agentUser)->get('/');
        $resAgent->assertRedirect(route('agent.dashboard'));

        // Staff
        $staffUser = User::create([
            'name' => 'Staff Root Test',
            'email' => 'staff_root_'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'branch_id' => $branch->id,
        ]);
        $staffUser->roles()->sync([$staffRole->id]);

        $position = StaffPosition::first() ?? StaffPosition::create([
            'name' => 'Officer',
            'code' => 'OFF_'.uniqid(),
            'is_active' => true,
        ]);

        Staff::create([
            'user_id' => $staffUser->id,
            'name' => $staffUser->name,
            'email' => $staffUser->email,
            'phone' => '98'.rand(10000000, 99999999),
            'employee_id' => 'STF_ROOT_'.rand(1000, 9999),
            'branch_id' => $branch->id,
            'position_id' => $position->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $resStaff = $this->actingAs($staffUser)->get('/');
        $resStaff->assertRedirect(route('staff.dashboard'));
    }
}
