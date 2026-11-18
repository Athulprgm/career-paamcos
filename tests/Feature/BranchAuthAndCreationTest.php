<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BranchAuthAndCreationTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->first();
    }

    public function test_superadmin_can_create_branch_with_password_and_provision_admin(): void
    {
        $uniqueCode = 'BR-TEST-'.strtoupper(substr(uniqid(), -4));
        $uniqueEmail = 'branch_'.strtolower(substr(uniqid(), -5)).'@multicred.test';

        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.branches.store'), [
            'name' => 'Metro Testing Branch',
            'code' => $uniqueCode,
            'email' => $uniqueEmail,
            'phone' => '+91 9876543210',
            'address' => '123 Testing Lane, Tech City',
            'password' => 'BranchPass123!',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('superadmin.branches.index'));

        // Branch is created in database
        $branch = Branch::where('code', $uniqueCode)->first();
        $this->assertNotNull($branch);
        $this->assertEquals('Metro Testing Branch', $branch->name);
        $this->assertNotNull($branch->password);

        // Branch admin user is provisioned
        $adminUser = User::where('email', $uniqueEmail)->first();
        $this->assertNotNull($adminUser);
        $this->assertEquals($branch->id, $adminUser->branch_id);
        $this->assertTrue($adminUser->isBranchAdmin());
    }

    public function test_branch_admin_can_log_in_using_branch_code_as_username(): void
    {
        $uniqueCode = 'BR-CODE-'.strtoupper(substr(uniqid(), -4));
        $uniqueEmail = 'code_'.strtolower(substr(uniqid(), -5)).'@multicred.test';

        $branch = Branch::create([
            'name' => 'Code Login Branch',
            'code' => $uniqueCode,
            'email' => $uniqueEmail,
            'password' => bcrypt('BranchSecret123'),
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin']);
        $admin = User::create([
            'name' => 'Code Login Branch Admin',
            'email' => $uniqueEmail,
            'password' => bcrypt('BranchSecret123'),
            'branch_id' => $branch->id,
            'email_verified_at' => now(),
        ]);
        $admin->roles()->attach($adminRole->id);

        // Attempt login using Branch Code (uppercase)
        $response = $this->post(route('superadmin.login.post'), [
            'login' => $uniqueCode,
            'password' => 'BranchSecret123',
        ]);

        $response->assertRedirect(route('branch-admin.dashboard'));
        $this->assertAuthenticatedAs($admin);

        // Verify audit log recorded login with branch code
        $log = AuditLog::where('event', 'login')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertEquals($admin->id, $log->user_id);
        $this->assertStringContainsString($uniqueCode, $log->description);
    }

    public function test_branch_admin_can_log_in_using_case_insensitive_branch_code(): void
    {
        $uniqueCode = 'BR-LOWER-'.strtoupper(substr(uniqid(), -4));
        $uniqueEmail = 'lower_'.strtolower(substr(uniqid(), -5)).'@multicred.test';

        $branch = Branch::create([
            'name' => 'Lowercase Test Branch',
            'code' => $uniqueCode,
            'email' => $uniqueEmail,
            'password' => bcrypt('LowerSecret123'),
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin']);
        $admin = User::create([
            'name' => 'Lowercase Admin',
            'email' => $uniqueEmail,
            'password' => bcrypt('LowerSecret123'),
            'branch_id' => $branch->id,
            'email_verified_at' => now(),
        ]);
        $admin->roles()->attach($adminRole->id);

        // Attempt login using lowercase branch code
        $response = $this->post(route('superadmin.login.post'), [
            'login' => strtolower($uniqueCode),
            'password' => 'LowerSecret123',
        ]);

        $response->assertRedirect(route('branch-admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_branch_admin_can_log_in_using_email(): void
    {
        $uniqueCode = 'BR-MAIL-'.strtoupper(substr(uniqid(), -4));
        $uniqueEmail = 'email_'.strtolower(substr(uniqid(), -5)).'@multicred.test';

        $branch = Branch::create([
            'name' => 'Email Login Branch',
            'code' => $uniqueCode,
            'email' => $uniqueEmail,
            'password' => bcrypt('EmailSecret123'),
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin']);
        $admin = User::create([
            'name' => 'Email Branch Admin',
            'email' => $uniqueEmail,
            'password' => bcrypt('EmailSecret123'),
            'branch_id' => $branch->id,
            'email_verified_at' => now(),
        ]);
        $admin->roles()->attach($adminRole->id);

        $response = $this->post(route('superadmin.login.post'), [
            'login' => $uniqueEmail,
            'password' => 'EmailSecret123',
        ]);

        $response->assertRedirect(route('branch-admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_superadmin_can_update_branch_password_and_login_with_new_password(): void
    {
        $uniqueCode = 'BR-EDIT-'.strtoupper(substr(uniqid(), -4));
        $uniqueEmail = 'edit_'.strtolower(substr(uniqid(), -5)).'@multicred.test';

        $branch = Branch::create([
            'name' => 'Editable Branch',
            'code' => $uniqueCode,
            'email' => $uniqueEmail,
            'password' => bcrypt('OldSecret123'),
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin']);
        $admin = User::create([
            'name' => 'Editable Branch Admin',
            'email' => $uniqueEmail,
            'password' => bcrypt('OldSecret123'),
            'branch_id' => $branch->id,
            'email_verified_at' => now(),
        ]);
        $admin->roles()->attach($adminRole->id);

        // Super Admin updates the branch password
        $response = $this->actingAs($this->superAdmin)->put(route('superadmin.branches.update', $branch), [
            'name' => 'Editable Branch',
            'code' => $uniqueCode,
            'email' => $uniqueEmail,
            'password' => 'NewSecret456!',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('superadmin.branches.index'));

        // Log out super admin
        $this->post(route('superadmin.logout'));

        // Attempt login using Branch Code and NEW password
        $loginResponse = $this->post(route('superadmin.login.post'), [
            'login' => $uniqueCode,
            'password' => 'NewSecret456!',
        ]);

        $loginResponse->assertRedirect(route('branch-admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_fails_with_invalid_password_for_branch_code(): void
    {
        $uniqueCode = 'BR-WRONG-'.strtoupper(substr(uniqid(), -4));
        $uniqueEmail = 'wrong_'.strtolower(substr(uniqid(), -5)).'@multicred.test';

        $branch = Branch::create([
            'name' => 'Wrong Password Branch',
            'code' => $uniqueCode,
            'email' => $uniqueEmail,
            'password' => bcrypt('RightSecret123'),
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin']);
        $admin = User::create([
            'name' => 'Wrong Pass Admin',
            'email' => $uniqueEmail,
            'password' => bcrypt('RightSecret123'),
            'branch_id' => $branch->id,
            'email_verified_at' => now(),
        ]);
        $admin->roles()->attach($adminRole->id);

        $response = $this->post(route('superadmin.login.post'), [
            'login' => $uniqueCode,
            'password' => 'IncorrectPassword',
        ]);

        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    public function test_login_rejected_if_branch_is_inactive(): void
    {
        $uniqueCode = 'BR-OFF-'.strtoupper(substr(uniqid(), -4));
        $uniqueEmail = 'off_'.strtolower(substr(uniqid(), -5)).'@multicred.test';

        $branch = Branch::create([
            'name' => 'Inactive Branch',
            'code' => $uniqueCode,
            'email' => $uniqueEmail,
            'password' => bcrypt('ValidSecret123'),
            'is_active' => false,
        ]);

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin']);
        $admin = User::create([
            'name' => 'Inactive Branch Admin',
            'email' => $uniqueEmail,
            'password' => bcrypt('ValidSecret123'),
            'branch_id' => $branch->id,
            'email_verified_at' => now(),
        ]);
        $admin->roles()->attach($adminRole->id);

        $response = $this->post(route('superadmin.login.post'), [
            'login' => $uniqueCode,
            'password' => 'ValidSecret123',
        ]);

        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    public function test_superadmin_can_delete_branch(): void
    {
        $branch = Branch::create([
            'name' => 'Branch To Delete',
            'code' => 'BR-DEL-'.strtoupper(substr(uniqid(), -4)),
            'email' => 'delete_'.strtolower(substr(uniqid(), -5)).'@multicred.test',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('superadmin.branches.destroy', $branch));

        $response->assertRedirect(route('superadmin.branches.index'));
        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);

        $log = AuditLog::where('event', 'branch_deleted')->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertStringContainsString($branch->code, $log->description);
    }

    public function test_branch_list_table_renders_delete_button(): void
    {
        $branch = Branch::create([
            'name' => 'Button Check Branch',
            'code' => 'BR-BTN-'.strtoupper(substr(uniqid(), -4)),
            'email' => 'btn_'.strtolower(substr(uniqid(), -5)).'@multicred.test',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.branches.index'));

        $response->assertOk();
        $response->assertSee(route('superadmin.branches.destroy', $branch));
        $response->assertSee('bi-trash');
    }

    public function test_branch_admin_can_be_created_and_updated_without_password_field(): void
    {
        $branch = Branch::first() ?? Branch::create([
            'name' => 'Main Test Branch',
            'code' => 'BR_AUTH_01',
            'is_active' => true,
        ]);
        $email = 'admin_nopass_'.uniqid().'@multicred.test';

        // Create Branch Admin without password field
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.branch-admins.store'), [
            'name' => 'No Password Admin',
            'email' => $email,
            'mobile' => '+91 9876543210',
            'branch_id' => $branch->id,
        ]);

        $response->assertRedirect(route('superadmin.branch-admins.index'));

        $admin = User::where('email', $email)->first();
        $this->assertNotNull($admin);
        $this->assertEquals($branch->id, $admin->branch_id);
        $this->assertTrue($admin->isBranchAdmin());

        // Update Branch Admin without password field
        $updateResponse = $this->actingAs($this->superAdmin)->put(route('superadmin.branch-admins.update', $admin), [
            'name' => 'Updated No Password Admin',
            'email' => $email,
            'mobile' => '+91 9999999999',
            'branch_id' => $branch->id,
        ]);

        $updateResponse->assertRedirect(route('superadmin.branch-admins.index'));
        $admin->refresh();
        $this->assertEquals('Updated No Password Admin', $admin->name);
    }
}
