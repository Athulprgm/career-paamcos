<?php

namespace Tests\Feature;

use App\Mail\ResetPasswordMail;
use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ForgotPasswordFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'status' => 'active']);
        Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'status' => 'active']);

        $this->superAdmin = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->first()
            ?? User::factory()->create([
                'email' => 'superadmin_pwd_'.uniqid().'@example.com',
                'password' => Hash::make('password123'),
            ]);
        $this->superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR_PWD_'.uniqid()],
            [
                'name' => 'Northern Hub Branch',
                'email' => 'northern.pwd@example.com',
                'phone' => '9876543210',
                'is_active' => true,
            ]
        );
    }

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $redirectResponse = $this->get('/forgot-password');
        $redirectResponse->assertRedirect(route('superadmin.password.request'));

        $response = $this->get('/superadmin/forgot-password');
        $response->assertStatus(200);
        $response->assertSee('Forgot Password?');
        $response->assertSee('Send Reset Link');
        $response->assertSee('Back to Login');
    }

    public function test_login_screen_contains_forgot_password_link(): void
    {
        $response = $this->get('/superadmin/login');
        $response->assertStatus(200);
        $response->assertSee(route('password.request'));
        $response->assertSee('Forgot password?');
    }

    public function test_authenticated_users_are_redirected_away_from_forgot_password(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/superadmin/forgot-password');
        $response->assertRedirect(route('superadmin.dashboard'));
    }

    public function test_reset_link_can_be_requested_with_valid_email(): void
    {
        Mail::fake();

        $testEmail = 'reset.user.'.uniqid().'@example.com';
        $user = User::factory()->create([
            'name' => 'Test Reset User',
            'email' => $testEmail,
            'password' => Hash::make('oldSecretPass123'),
        ]);

        $response = $this->post('/forgot-password', [
            'email' => $testEmail,
        ]);

        $response->assertSessionHas('status');
        Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) use ($testEmail) {
            return $mail->hasTo($testEmail) &&
                   ! empty($mail->token) &&
                   str_contains($mail->resetUrl, $mail->token);
        });

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'password_reset_requested',
            'user_id' => $user->id,
        ]);
    }

    public function test_reset_link_can_be_requested_with_agent_code(): void
    {
        Mail::fake();

        $agentRank = AgentRank::first() ?? AgentRank::create([
            'name' => 'Agent Rank Test',
            'code' => 'RANK_'.uniqid(),
            'level' => 1,
            'minimum_active_deposits' => 0,
            'status' => 'active',
        ]);

        $agentUser = User::factory()->create([
            'name' => 'Agent Reset User',
            'email' => 'agent.reset.'.uniqid().'@example.com',
            'password' => Hash::make('oldPass123'),
        ]);

        $agentCode = 'AG'.rand(10000, 99999);
        Agent::create([
            'user_id' => $agentUser->id,
            'branch_id' => $this->branch->id,
            'rank_id' => $agentRank->id,
            'member_code' => $agentCode,
            'agent_id' => $agentCode,
            'name' => 'Agent Reset User',
            'email' => $agentUser->email,
            'phone' => '9876543211',
            'status' => 'active',
            'joining_date' => now()->toDateString(),
            'password' => Hash::make('oldPass123'),
        ]);

        $response = $this->post('/superadmin/forgot-password', [
            'email' => $agentCode,
        ]);

        $response->assertSessionHas('status');
        Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) use ($agentUser) {
            return $mail->hasTo($agentUser->email);
        });
    }

    public function test_reset_link_can_be_requested_with_staff_code(): void
    {
        Mail::fake();

        $position = StaffPosition::first() ?? StaffPosition::create([
            'name' => 'Position Test',
            'code' => 'POS_'.uniqid(),
            'is_active' => true,
        ]);

        $staffUser = User::factory()->create([
            'name' => 'Staff Reset User',
            'email' => 'staff.reset.'.uniqid().'@example.com',
            'password' => Hash::make('oldPass123'),
        ]);

        $staffCode = 'STF_'.uniqid();
        Staff::create([
            'user_id' => $staffUser->id,
            'branch_id' => $this->branch->id,
            'position_id' => $position->id,
            'staff_code' => $staffCode,
            'employee_id' => $staffCode,
            'name' => 'Staff Reset User',
            'email' => $staffUser->email,
            'phone' => '9876543212',
            'status' => 'active',
            'joining_date' => now()->toDateString(),
            'password' => Hash::make('oldPass123'),
        ]);

        $response = $this->post('/forgot-password', [
            'email' => $staffCode,
        ]);

        $response->assertSessionHas('status');
        Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) use ($staffUser) {
            return $mail->hasTo($staffUser->email);
        });
    }

    public function test_reset_link_request_with_unknown_identifier_does_not_leak_or_send_mail(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password', [
            'email' => 'nonexistent_account_'.uniqid().'@example.com',
        ]);

        $response->assertSessionHas('status');
        Mail::assertNothingSent();

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'password_reset_requested_unknown',
        ]);
    }

    public function test_reset_password_screen_can_be_rendered_with_token(): void
    {
        $user = User::factory()->create([
            'email' => 'render.test.'.uniqid().'@example.com',
        ]);
        $token = Password::broker()->createToken($user);

        $response = $this->get("/reset-password/{$token}?email=".urlencode($user->email));
        $response->assertStatus(200);
        $response->assertSee('Reset Password');
        $response->assertSee('New Password');
        $response->assertSee('Confirm New Password');
        $response->assertSee($user->email);
    }

    public function test_password_can_be_reset_with_valid_token_and_confirmation(): void
    {
        $user = User::factory()->create([
            'name' => 'Reset Verified User',
            'email' => 'verified.reset.'.uniqid().'@example.com',
            'password' => Hash::make('oldPassword123'),
        ]);

        $rank = AgentRank::first() ?? AgentRank::create([
            'name' => 'Rank Test',
            'code' => 'RNK_'.uniqid(),
            'level' => 1,
            'minimum_active_deposits' => 0,
            'status' => 'active',
        ]);

        $syncCode = 'AG'.rand(10000, 99999);
        $agent = Agent::create([
            'user_id' => $user->id,
            'branch_id' => $this->branch->id,
            'rank_id' => $rank->id,
            'member_code' => $syncCode,
            'agent_id' => $syncCode,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '9876543213',
            'status' => 'active',
            'joining_date' => now()->toDateString(),
            'password' => $user->password,
        ]);

        $token = Password::broker()->createToken($user);
        $newPassword = 'BrandNewPassword123#';

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $user->refresh();
        $this->assertTrue(password_verify($newPassword, $user->password));

        $agent->refresh();
        $this->assertTrue(password_verify($newPassword, $agent->password));

        // Token should now be invalidated/deleted
        $this->assertFalse(Password::broker()->tokenExists($user, $token));

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'password_reset_completed',
            'user_id' => $user->id,
        ]);
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'invalid.token.'.uniqid().'@example.com',
            'password' => Hash::make('oldPassword123'),
        ]);

        $response = $this->post('/reset-password', [
            'token' => 'completely-invalid-token-string',
            'email' => $user->email,
            'password' => 'NewValidPassword123',
            'password_confirmation' => 'NewValidPassword123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(password_verify('oldPassword123', $user->fresh()->password));
    }

    public function test_password_reset_validation_rules_enforce_min_length_and_confirmation(): void
    {
        $user = User::factory()->create([
            'email' => 'validation.test.'.uniqid().'@example.com',
        ]);
        $token = Password::broker()->createToken($user);

        // Password too short (< 8)
        $responseShort = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'short1',
            'password_confirmation' => 'short1',
        ]);
        $responseShort->assertSessionHasErrors('password');

        // Confirmation mismatch
        $responseMismatch = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'validPass123',
            'password_confirmation' => 'differentPass123',
        ]);
        $responseMismatch->assertSessionHasErrors('password');
    }

    public function test_mail_preview_route_is_accessible_in_testing(): void
    {
        $response = $this->get('/mail-preview/reset-password');
        $response->assertStatus(200);
        $response->assertSee('Reset Your Password');
        $response->assertSee('Security Notification');
    }
}
