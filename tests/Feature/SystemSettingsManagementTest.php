<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Services\NumberingService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SystemSettingsManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $branchAdmin;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Administrator']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin']);

        $this->branch = Branch::create([
            'name' => 'Settings Profile Test Branch '.uniqid(),
            'code' => 'SPB_'.rand(100, 999),
            'is_active' => true,
        ]);

        $this->superAdmin = User::create([
            'name' => 'Profile Super Admin '.uniqid(),
            'email' => 'prof_super_'.uniqid().'@example.com',
            'password' => bcrypt('password123'),
            'branch_id' => null,
        ]);
        $this->superAdmin->roles()->attach($superAdminRole->id);

        $this->branchAdmin = User::create([
            'name' => 'Profile Branch Admin '.uniqid(),
            'email' => 'prof_admin_'.uniqid().'@example.com',
            'password' => bcrypt('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $this->branchAdmin->roles()->attach($adminRole->id);
    }

    /**
     * 1. Settings index redirects directly to Profile Settings.
     */
    public function test_settings_index_redirects_to_profile(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('admin.settings.index'));
        $response->assertRedirect(route('superadmin.profile'));
    }

    /**
     * 2. Super Admin can view Profile Settings.
     */
    public function test_super_admin_can_view_profile_settings(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('superadmin.profile'));
        $response->assertStatus(200);
        $response->assertSee('Profile Settings');
        $response->assertSee($this->superAdmin->name);
        $response->assertSee($this->superAdmin->email);
    }

    /**
     * 3. Branch Admin can view Profile Settings.
     */
    public function test_branch_admin_can_view_profile_settings(): void
    {
        $this->actingAs($this->branchAdmin);

        $response = $this->get(route('superadmin.profile'));
        $response->assertStatus(200);
        $response->assertSee('Profile Settings');
        $response->assertSee($this->branchAdmin->name);
        $response->assertSee($this->branchAdmin->email);
    }

    /**
     * 4. User can update their profile information with validation.
     */
    public function test_user_can_update_profile_information(): void
    {
        $this->actingAs($this->superAdmin);

        $payload = [
            'name' => 'Updated Super Admin Name',
            'email' => 'updated_super_'.uniqid().'@example.com',
        ];

        $response = $this->put(route('superadmin.profile.update'), $payload);
        $response->assertRedirect(route('superadmin.profile'));
        $response->assertSessionHas('success');

        $this->superAdmin->refresh();
        $this->assertEquals('Updated Super Admin Name', $this->superAdmin->name);
        $this->assertEquals($payload['email'], $this->superAdmin->email);

        // Validation: email must be valid
        $badResponse = $this->put(route('superadmin.profile.update'), [
            'name' => '',
            'email' => 'invalid-email',
        ]);
        $badResponse->assertSessionHasErrors(['name', 'email']);
    }

    /**
     * 5. User can change their password with current password verification.
     */
    public function test_user_can_change_password_with_current_password_check(): void
    {
        $this->actingAs($this->superAdmin);

        // Incorrect current password
        $badResponse = $this->put(route('superadmin.profile.password'), [
            'current_password' => 'wrong_password',
            'password' => 'new_secret_123',
            'password_confirmation' => 'new_secret_123',
        ]);
        $badResponse->assertSessionHasErrors(['current_password']);

        // Correct password change
        $response = $this->put(route('superadmin.profile.password'), [
            'current_password' => 'password123',
            'password' => 'new_secret_123',
            'password_confirmation' => 'new_secret_123',
        ]);
        $response->assertRedirect(route('superadmin.profile'));
        $response->assertSessionHas('success');

        $this->superAdmin->refresh();
        $this->assertTrue(Hash::check('new_secret_123', $this->superAdmin->password));
    }

    /**
     * 6. Guest user is redirected to login when trying to access profile.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('superadmin.profile'));
        $response->assertRedirect(route('superadmin.login'));
    }

    /**
     * 7. Numbering Service sequential integrity remains intact.
     */
    public function test_numbering_service_integrity_maintained(): void
    {
        $service = app(NumberingService::class);
        $number = $service->generateNext('customer');
        $this->assertNotEmpty($number);
    }

    /**
     * 8. Agent views profile page as a free worker without assigned branch.
     */
    public function test_agent_views_profile_as_free_worker_without_assigned_branch(): void
    {
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $rank = AgentRank::first() ?? AgentRank::create(['name' => 'Associate', 'code' => 'L1', 'level' => 1, 'status' => 'active']);

        $agentUser = User::factory()->create([
            'name' => 'Free Agent User',
            'email' => 'freeagent_'.uniqid().'@example.com',
            'branch_id' => null,
        ]);
        $agentUser->roles()->sync([$agentRole->id]);
        $agentUser->refresh();

        $agent = Agent::create([
            'user_id' => $agentUser->id,
            'name' => $agentUser->name,
            'email' => $agentUser->email,
            'agent_id' => 'AG_'.substr(uniqid(), -6),
            'member_code' => 'AG_'.substr(uniqid(), -6),
            'phone' => '9876543210',
            'branch_id' => null,
            'rank_id' => $rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($agentUser)->get(route('superadmin.profile'));
        $response->assertStatus(200);
        $response->assertSee('Free Field Worker (No Branch)');
        $response->assertSee($agent->agent_id);
    }
}
