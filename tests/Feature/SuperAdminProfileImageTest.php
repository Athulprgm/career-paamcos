<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SuperAdminProfileImageTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);

        $this->superAdmin = User::create([
            'name' => 'Super Administrator Test',
            'email' => 'superadmin_test_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'profile_image' => null,
        ]);
        $this->superAdmin->roles()->attach($superAdminRole->id);
        $this->superAdmin->load('roles');
    }

    public function test_super_admin_defaults_to_logo_as_profile_image(): void
    {
        $this->assertNull($this->superAdmin->profile_image);
        $this->assertNotNull($this->superAdmin->profile_image_url);
        $this->assertStringContainsString('logo.png', $this->superAdmin->profile_image_url);
    }

    public function test_super_admin_profile_page_renders_logo_avatar_and_cropper(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.profile'));

        $response->assertStatus(200);
        $response->assertSee('Profile Photo / Society Logo', false);
        $response->assertSee('logo.png', false);
    }

    public function test_super_admin_can_update_profile_image(): void
    {
        $fakeCroppedBase64 = 'data:image/png;base64,'.base64_encode('fake-cropped-superadmin-png-content');

        $response = $this->actingAs($this->superAdmin)
            ->put(route('superadmin.profile.update'), [
                'name' => 'Updated Super Admin',
                'email' => $this->superAdmin->email,
                'profile_image' => $fakeCroppedBase64,
            ]);

        $response->assertRedirect(route('superadmin.profile'));
        $this->superAdmin->refresh();

        $this->assertNotNull($this->superAdmin->profile_image);
        $this->assertStringStartsWith('profiles/', $this->superAdmin->profile_image);
        $this->assertTrue(Storage::disk('public')->exists($this->superAdmin->profile_image));
    }

    public function test_super_admin_can_remove_custom_photo_to_restore_logo(): void
    {
        $fakeCroppedBase64 = 'data:image/png;base64,'.base64_encode('fake-custom-superadmin-photo');

        $this->actingAs($this->superAdmin)
            ->put(route('superadmin.profile.update'), [
                'name' => $this->superAdmin->name,
                'email' => $this->superAdmin->email,
                'profile_image' => $fakeCroppedBase64,
            ]);

        $this->superAdmin->refresh();
        $this->assertNotNull($this->superAdmin->profile_image);

        // Now remove it
        $response = $this->actingAs($this->superAdmin)
            ->put(route('superadmin.profile.update'), [
                'name' => $this->superAdmin->name,
                'email' => $this->superAdmin->email,
                'remove_profile_image' => '1',
            ]);

        $response->assertRedirect(route('superadmin.profile'));
        $this->superAdmin->refresh();

        $this->assertEquals('logo.png', $this->superAdmin->profile_image);
        $this->assertStringContainsString('logo.png', $this->superAdmin->profile_image_url);
    }

    public function test_super_admin_agent_profile_inherits_profile_image_url(): void
    {
        $agent = $this->superAdmin->agentProfile;
        $this->assertNotNull($agent);
        $this->assertNotNull($agent->profile_image_url);
        $this->assertStringContainsString('logo.png', $agent->profile_image_url);

        // Update profile photo
        $fakeCroppedBase64 = 'data:image/png;base64,'.base64_encode('agent-custom-photo');
        $this->actingAs($this->superAdmin)
            ->put(route('superadmin.profile.update'), [
                'name' => $this->superAdmin->name,
                'email' => $this->superAdmin->email,
                'profile_image' => $fakeCroppedBase64,
            ]);

        $this->superAdmin->refresh();
        $agent->refresh();

        $this->assertNotNull($agent->profile_image_url);
        $this->assertStringContainsString('storage/profiles/', $agent->profile_image_url);
    }

    public function test_super_admin_dashboard_shows_super_admin_agent_profile_image(): void
    {
        $agent = $this->superAdmin->agentProfile;
        $this->assertNotNull($agent);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Top Field Advisors', false);
        $response->assertSee($agent->profile_image_url, false);
    }
}
