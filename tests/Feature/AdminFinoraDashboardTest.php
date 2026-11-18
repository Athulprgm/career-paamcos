<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminFinoraDashboardTest extends TestCase
{
    use DatabaseTransactions;

    protected Role $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Admin', 'status' => 'active']
        );
    }

    public function test_guests_are_redirected_from_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('superadmin.login'));
    }

    public function test_authenticated_admin_can_view_finora_dashboard_with_structured_data(): void
    {
        $admin = User::create([
            'name' => 'David Miller',
            'email' => 'finora_admin_'.uniqid().'@multicred.com',
            'password' => Hash::make('password123'),
        ]);
        $admin->roles()->attach($this->adminRole->id);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas('adminName', 'David');
        $response->assertViewHas('kpis', fn (array $kpis): bool => count($kpis) === 4);
        $response->assertViewHas('budget', fn (array $budget): bool => $budget['used_percent'] === 64);
        $response->assertViewHas('cardDetails', fn (array $card): bool => $card['balance'] === '$53,760.99');
        $response->assertViewHas('upcomingBills', fn (array $bills): bool => count($bills) === 4);

        $response->assertSee('Finora');
        $response->assertSee('Good Morning, David');
        $response->assertSee('David Miller');
        $response->assertSee('Current Balance');
        $response->assertSee('$5,847');
        $response->assertSee('Cash Flow Overview');
        $response->assertSee('Budget Overview');
        $response->assertSee('64% used');
        $response->assertSee('All Expenses');
        $response->assertSee('My Card');
        $response->assertSee('$53,760.99');
        $response->assertSee('Freepik Premium');
        $response->assertSee('Upcoming Bills');
        $response->assertSee('Spotify Subscription');
        $response->assertSee('Netflix');
        $response->assertSee('Apple One');
        $response->assertSee('Adobe Creative Cloud');
    }

    public function test_canonical_logout_route_logs_out_user(): void
    {
        $admin = User::create([
            'name' => 'John Smith',
            'email' => 'finora_logout_'.uniqid().'@multicred.com',
            'password' => Hash::make('password123'),
        ]);
        $admin->roles()->attach($this->adminRole->id);

        $response = $this->actingAs($admin)->post(route('logout'));

        $response->assertRedirect(route('superadmin.login'));
        $this->assertGuest();
    }
}
