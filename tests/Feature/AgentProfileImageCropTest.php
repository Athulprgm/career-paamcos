<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AgentProfileImageCropTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $agentUser;

    protected Agent $agent;

    protected Branch $branch;

    protected AgentRank $rank;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);

        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR_CROP_TEST'],
            ['name' => 'Crop Test Branch', 'is_active' => true, 'address' => 'Branch Street', 'phone' => '9999999999', 'email' => 'croptest@test.com']
        );

        $this->rank = AgentRank::firstOrCreate(
            ['level' => 1],
            ['name' => 'Advisor', 'is_active' => true]
        );

        $this->superAdmin = User::create([
            'name' => 'Super Admin User',
            'email' => 'superadmin_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $this->superAdmin->roles()->attach($superAdminRole->id);

        $this->agentUser = User::create([
            'name' => 'Test Sponsor Agent',
            'email' => 'agent_'.uniqid().'@test.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $this->agentUser->roles()->attach($agentRole->id);

        $this->agent = Agent::create([
            'name' => 'Test Sponsor Agent',
            'agent_id' => 'AG_SP_'.rand(1000, 9999),
            'member_code' => 'AG_SP_'.rand(1000, 9999),
            'email' => $this->agentUser->email,
            'password' => Hash::make('password123'),
            'phone' => '9876543210',
            'branch_id' => $this->branch->id,
            'rank_id' => $this->rank->id,
            'user_id' => $this->agentUser->id,
            'joining_date' => now()->toDateString(),
            'status' => Agent::STATUS_ACTIVE,
        ]);
    }

    public function test_agent_add_form_renders_profile_image_cropper_inputs(): void
    {
        // Agent downline create form
        $response = $this->actingAs($this->agentUser)->get(route('agent.agents.create'));
        $response->assertStatus(200);
        $response->assertSee('profile-image-uploader-wrapper', false);
        $response->assertSee('profile_image', false);
        $response->assertSee('Crop & Adjust Profile Photo', false);

        // Admin agent create form
        $adminResponse = $this->actingAs($this->superAdmin)->get(route('admin.agents.create'));
        $adminResponse->assertStatus(200);
        $adminResponse->assertSee('profile-image-uploader-wrapper', false);
        $adminResponse->assertSee('Crop & Adjust Profile Photo', false);
    }

    public function test_downline_agent_can_be_created_with_cropped_base64_image(): void
    {
        // 1x1 transparent red PNG in base64
        $fakeCroppedBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

        $payload = [
            'name' => 'New Cropped Downline',
            'agent_id' => 'AG_CRP_'.rand(1000, 9999),
            'email' => 'cropped_downline_'.uniqid().'@test.com',
            'phone' => '9876500001',
            'rank_id' => $this->rank->id,
            'joining_date' => now()->toDateString(),
            'profile_image' => $fakeCroppedBase64,
        ];

        $response = $this->actingAs($this->agentUser)->post(route('agent.agents.store'), $payload);
        $response->assertRedirect(route('agent.agents.index'));

        $newAgent = Agent::where('email', $payload['email'])->first();
        $this->assertNotNull($newAgent);
        $this->assertNotNull($newAgent->profile_image);
        $this->assertStringStartsWith('agents/', $newAgent->profile_image);
        $this->assertTrue(Storage::disk('public')->exists($newAgent->profile_image));
        $this->assertNotNull($newAgent->profile_image_url);

        // Check user account sync
        $linkedUser = User::where('email', $payload['email'])->first();
        $this->assertNotNull($linkedUser);
        $this->assertEquals($newAgent->profile_image, $linkedUser->profile_image);
    }

    public function test_admin_can_create_agent_with_cropped_image(): void
    {
        $fakeCroppedBase64 = 'data:image/jpeg;base64,'.base64_encode('fake-binary-image-data');

        $payload = [
            'name' => 'Admin Registered Agent',
            'agent_id' => 'AG_ADM_'.rand(1000, 9999),
            'email' => 'admin_agent_'.uniqid().'@test.com',
            'phone' => '9876500002',
            'branch_id' => $this->branch->id,
            'rank_id' => $this->rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'profile_image' => $fakeCroppedBase64,
        ];

        $response = $this->actingAs($this->superAdmin)->post(route('admin.agents.store'), $payload);
        $response->assertRedirect(route('admin.agents.index'));

        $agent = Agent::where('email', $payload['email'])->first();
        $this->assertNotNull($agent);
        $this->assertNotNull($agent->profile_image);
        $this->assertTrue(Storage::disk('public')->exists($agent->profile_image));
    }

    public function test_admin_can_update_and_remove_profile_image(): void
    {
        // First set an image
        $fakeCroppedBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

        $payload = [
            'name' => $this->agent->name,
            'agent_id' => $this->agent->agent_id,
            'email' => $this->agent->email,
            'phone' => $this->agent->phone,
            'branch_id' => $this->branch->id,
            'rank_id' => $this->rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'profile_image' => $fakeCroppedBase64,
        ];

        $this->actingAs($this->superAdmin)->put(route('admin.agents.update', $this->agent), $payload);
        $this->agent->refresh();
        $this->assertNotNull($this->agent->profile_image);
        $originalPath = $this->agent->profile_image;
        $this->assertTrue(Storage::disk('public')->exists($originalPath));

        // Now remove the image
        $removePayload = array_merge($payload, [
            'remove_profile_image' => 1,
            'profile_image' => null,
        ]);

        $this->actingAs($this->superAdmin)->put(route('admin.agents.update', $this->agent), $removePayload);
        $this->agent->refresh();
        $this->assertNull($this->agent->profile_image);
        $this->assertFalse(Storage::disk('public')->exists($originalPath));
    }
}
