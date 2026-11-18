<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_accessing_root_login_route_redirects_to_superadmin_login(): void
    {
        $response = $this->get(route('login'));

        $response->assertRedirect(route('superadmin.login'));
    }

    public function test_superadmin_login_page_renders_redesigned_form_for_127_0_0_1_host(): void
    {
        Setting::set('society.name', 'Pristine Agro & Allied Multi State Co-operative Society Ltd.', 'string', 'society');
        Setting::set('society.short_name', 'PAAMCO', 'string', 'society');
        Setting::set('society.registration_number', 'MSCS/CR/1725/2026', 'string', 'society');

        $response = $this->get('http://127.0.0.1:8000/superadmin/login');

        $response->assertOk();
        $response->assertViewIs('superadmin.auth.login');
        $response->assertSee('action="http://127.0.0.1:8000/superadmin/login"', false);
        $response->assertSee('name="login"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="remember"', false);
        $response->assertSee('id="themeToggleBtn"', false);
        $response->assertSee('Pristine Agro &amp; Allied Multi State Co-operative Society Ltd.', false);
        $response->assertSee('PAAMCO');
        $response->assertSee('MSCS/CR/1725/2026');
        $response->assertSee('Welcome Back!');
        $response->assertSee('Login Now');
        $response->assertSee('auth-right-panel', false);
        $response->assertSee('--auth-panel-bg: #090F0C;', false);
        $response->assertDontSee('Login with Google');
        $response->assertDontSee('class="auth-right-panel relative flex flex-col justify-between bg-white', false);
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function authenticatedRoleDashboards(): array
    {
        return [
            'super admin' => ['super_admin', 'superadmin.dashboard'],
            'branch admin' => ['admin', 'branch-admin.dashboard'],
            'agent' => ['agent', 'agent.dashboard'],
            'staff' => ['staff', 'staff.dashboard'],
        ];
    }

    #[DataProvider('authenticatedRoleDashboards')]
    public function test_authenticated_users_visiting_superadmin_login_redirect_to_their_dashboard(
        string $roleSlug,
        string $expectedDashboardRoute
    ): void {
        $role = Role::firstOrCreate(['slug' => $roleSlug], ['name' => ucfirst($roleSlug), 'status' => 'active']);
        $user = User::factory()->create();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)->get(route('superadmin.login'));

        $response->assertRedirect(route($expectedDashboardRoute));
    }

    public function test_submitting_empty_login_form_requires_both_identifier_and_password(): void
    {
        $response = $this->from(route('superadmin.login'))
            ->post(route('superadmin.login.post'), [
                'login' => '',
                'password' => '',
                'remember' => '1',
            ]);

        $response->assertRedirect(route('superadmin.login'));
        $response->assertSessionHasErrors([
            'login' => 'Please provide your email address or user ID.',
            'password' => 'The password field is required.',
        ]);
        $response->assertSessionHasInput('remember', '1');
        $this->assertGuest();
    }

    public function test_failed_login_preserves_identifier_and_remember_without_duplicate_error_text(): void
    {
        $response = $this->followingRedirects()
            ->from(route('superadmin.login'))
            ->post(route('superadmin.login.post'), [
                'login' => 'unknown-user@example.com',
                'password' => 'WrongPassword123',
                'remember' => '1',
            ]);

        $response->assertOk();
        $response->assertSee('unknown-user@example.com');
        $response->assertSee('checked', false);
        $this->assertSame(
            1,
            substr_count($response->getContent(), 'These credentials do not match our records.')
        );
        $this->assertGuest();
    }

    public function test_valid_superadmin_can_sign_in_and_sign_out_via_superadmin_login(): void
    {
        $role = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $user = User::factory()->create([
            'email' => 'sa_login_test_'.uniqid().'@multicred.test',
            'password' => Hash::make('StrongPass123!'),
        ]);
        $user->roles()->attach($role->id);

        $loginResponse = $this->post(route('superadmin.login.post'), [
            'login' => $user->email,
            'password' => 'StrongPass123!',
            'remember' => '1',
        ]);

        $loginResponse->assertRedirect(route('superadmin.dashboard'));
        $this->assertAuthenticatedAs($user);

        $logoutResponse = $this->post(route('superadmin.logout'));

        $logoutResponse->assertRedirect(route('superadmin.login'));
        $this->assertGuest();
    }
}
