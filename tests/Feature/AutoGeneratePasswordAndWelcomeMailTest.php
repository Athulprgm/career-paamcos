<?php

namespace Tests\Feature;

use App\Mail\AccountWelcomeMail;
use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use App\Services\PasswordGeneratorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AutoGeneratePasswordAndWelcomeMailTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected Branch $branch;

    protected AgentRank $rank;

    protected StaffPosition $position;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'status' => 'active']);
        Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'status' => 'active']);

        $this->superAdmin = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->first()
            ?? User::factory()->create([
                'email' => 'superadmin_test_'.uniqid().'@example.com',
                'password' => Hash::make('password123'),
            ]);
        $this->superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR999'],
            [
                'name' => 'Northern Hub Branch',
                'email' => 'northern.hub@example.com',
                'phone' => '9876543210',
                'is_active' => true,
            ]
        );

        $this->rank = AgentRank::first() ?? AgentRank::create([
            'name' => 'Bronze Representative',
            'code' => 'BRONZE_'.uniqid(),
            'level' => 999,
            'is_active' => true,
        ]);

        $this->position = StaffPosition::firstOrCreate(
            ['code' => 'OPS_OFFICER'],
            [
                'name' => 'Operations Officer',
                'is_active' => true,
            ]
        );
    }

    public function test_password_generator_service_produces_secure_password(): void
    {
        $password = PasswordGeneratorService::generate(12);

        $this->assertSame(12, strlen($password));
        $this->assertMatchesRegularExpression('/[A-Z]/', $password);
        $this->assertMatchesRegularExpression('/[a-z]/', $password);
        $this->assertMatchesRegularExpression('/[0-9]/', $password);
        $this->assertMatchesRegularExpression('/[@#$%&*!]/', $password);
    }

    public function test_adding_agent_auto_generates_password_and_sends_welcome_email(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->superAdmin)->post(route('admin.agents.store'), [
            'name' => 'Anil Verma',
            'agent_id' => 'AG99001',
            'email' => 'anil.verma@example.com',
            'phone' => '9123456780',
            'branch_id' => $this->branch->id,
            'rank_id' => $this->rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'password' => '', // Intentionally left blank for auto-generation
            'send_welcome_email' => '1',
        ]);

        $response->assertRedirect(route('admin.agents.index'));
        $response->assertSessionHas('success');

        $agent = Agent::where('email', 'anil.verma@example.com')->first();
        $this->assertNotNull($agent);

        $user = User::where('email', 'anil.verma@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotEmpty($user->password);

        Mail::assertSent(AccountWelcomeMail::class, function (AccountWelcomeMail $mail): bool {
            return $mail->hasTo('anil.verma@example.com')
                && $mail->accountType === 'Agent'
                && ! empty($mail->rawPassword)
                && $mail->identifier === 'AG99001';
        });
    }

    public function test_adding_branch_auto_generates_password_and_sends_credentials_email(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.branches.store'), [
            'name' => 'Eastern Coastal Branch',
            'code' => 'BR888',
            'email' => 'eastern.branch@example.com',
            'phone' => '9988776655',
            'password' => '', // Left empty to auto-generate
            'send_welcome_email' => '1',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('superadmin.branches.index'));
        $response->assertSessionHas('success');

        $branch = Branch::where('code', 'BR888')->first();
        $this->assertNotNull($branch);
        $this->assertNotNull($branch->password);

        $adminUser = User::where('email', 'eastern.branch@example.com')->first();
        $this->assertNotNull($adminUser);

        Mail::assertSent(AccountWelcomeMail::class, function (AccountWelcomeMail $mail): bool {
            return $mail->hasTo('eastern.branch@example.com')
                && $mail->accountType === 'Branch Admin'
                && ! empty($mail->rawPassword)
                && $mail->identifier === 'BR888';
        });
    }

    public function test_assigning_branch_admin_auto_generates_password_and_sends_welcome_email(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.branch-admins.store'), [
            'name' => 'Kavita Nair',
            'email' => 'kavita.nair@example.com',
            'mobile' => '9876500112',
            'branch_id' => $this->branch->id,
            'password' => '', // Auto-generate
            'send_welcome_email' => '1',
        ]);

        $response->assertRedirect(route('superadmin.branch-admins.index'));
        $response->assertSessionHas('success');

        $adminUser = User::where('email', 'kavita.nair@example.com')->first();
        $this->assertNotNull($adminUser);
        $this->assertTrue($adminUser->isBranchAdmin());

        Mail::assertSent(AccountWelcomeMail::class, function (AccountWelcomeMail $mail): bool {
            return $mail->hasTo('kavita.nair@example.com')
                && $mail->accountType === 'Branch Admin'
                && ! empty($mail->rawPassword)
                && $mail->recipientName === 'Kavita Nair';
        });
    }

    public function test_adding_staff_auto_generates_password_and_sends_welcome_email(): void
    {
        Mail::fake();

        $response = $this->actingAs($this->superAdmin)->post(route('admin.staff.store'), [
            'name' => 'Meera Patel',
            'employee_id' => 'EMP9901',
            'email' => 'meera.patel@example.com',
            'phone' => '9876123450',
            'branch_id' => $this->branch->id,
            'position_id' => $this->position->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'password' => '', // Auto-generate
            'send_welcome_email' => '1',
        ]);

        $response->assertRedirect(route('admin.staff.index'));
        $response->assertSessionHas('success');

        $staff = Staff::where('email', 'meera.patel@example.com')->first();
        $this->assertNotNull($staff);

        $user = User::where('email', 'meera.patel@example.com')->first();
        $this->assertNotNull($user);

        Mail::assertSent(AccountWelcomeMail::class, function (AccountWelcomeMail $mail): bool {
            return $mail->hasTo('meera.patel@example.com')
                && $mail->accountType === 'Staff Member'
                && ! empty($mail->rawPassword)
                && $mail->identifier === 'EMP9901';
        });
    }

    public function test_welcome_email_renders_successfully(): void
    {
        $mailable = new AccountWelcomeMail(
            recipientName: 'Test Recipient',
            accountType: 'Branch Admin',
            loginEmail: 'test.admin@example.com',
            rawPassword: 'AutoPassword123#',
            loginUrl: 'http://localhost:8000/superadmin/login',
            identifier: 'BR001',
            branchName: 'Main Branch',
            roleOrRank: 'Branch Administrator'
        );

        $html = $mailable->render();

        $this->assertStringContainsString('Test Recipient', $html);
        $this->assertStringContainsString('test.admin@example.com', $html);
        $this->assertStringContainsString('AutoPassword123#', $html);
        $this->assertStringContainsString('Branch Admin', $html);
        $this->assertStringContainsString('Main Branch', $html);
        $this->assertStringContainsString('Sign In to Portal', $html);
    }

    public function test_create_views_contain_temporary_password_input_field(): void
    {
        // 1. Staff create view
        $responseStaff = $this->actingAs($this->superAdmin)->get(route('admin.staff.create'));
        $responseStaff->assertOk();
        $responseStaff->assertSee('name="password"', false);
        $responseStaff->assertSee('Temporary Login Password');
        $responseStaff->assertSee('name="send_welcome_email"', false);
        $responseStaff->assertDontSee('System Access Role');

        // 2. Agent create view
        $responseAgent = $this->actingAs($this->superAdmin)->get(route('admin.agents.create'));
        $responseAgent->assertOk();
        $responseAgent->assertSee('name="password"', false);
        $responseAgent->assertSee('Temporary Login Password');
        $responseAgent->assertSee('name="send_welcome_email"', false);

        // 3. Branch create view
        $responseBranch = $this->actingAs($this->superAdmin)->get(route('superadmin.branches.create'));
        $responseBranch->assertOk();
        $responseBranch->assertSee('name="password"', false);
        $responseBranch->assertSee('Temporary Login Password');
        $responseBranch->assertSee('name="send_welcome_email"', false);

        // 4. Branch admin create view inherits branch login password
        $responseBranchAdmin = $this->actingAs($this->superAdmin)->get(route('superadmin.branch-admins.create'));
        $responseBranchAdmin->assertOk();
        $responseBranchAdmin->assertDontSee('name="password"', false);
        $responseBranchAdmin->assertSee('Branch Login Credentials');
        $responseBranchAdmin->assertSee('name="send_welcome_email"', false);

        // 5. Agent downline create view has manual & auto password generator input
        $agentUser = User::create([
            'name' => 'Recruiting Agent',
            'email' => 'recruiter_'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $agentUser->roles()->attach($agentRole->id);

        $agent = Agent::create([
            'user_id' => $agentUser->id,
            'member_code' => 'AGT_REC_'.rand(1000, 9999),
            'agent_id' => 'AGT_REC_'.rand(1000, 9999),
            'name' => 'Recruiting Agent',
            'email' => $agentUser->email,
            'password' => Hash::make('password123'),
            'phone' => '9876543210',
            'branch_id' => $this->branch->id,
            'rank_id' => $this->rank->id,
            'status' => 'active',
            'joining_date' => now()->toDateString(),
        ]);

        $responseAgentDownline = $this->actingAs($agentUser)->get(route('agent.agents.create'));
        $responseAgentDownline->assertOk();
        $responseAgentDownline->assertSee('name="password"', false);
        $responseAgentDownline->assertSee('Login Password');
        $responseAgentDownline->assertSee('name="send_welcome_email"', false);
    }

    public function test_custom_temporary_password_can_be_set_and_emailed(): void
    {
        Mail::fake();

        $customTempPass = 'TempPass@2026!';

        // Create staff with custom temporary password
        $this->actingAs($this->superAdmin)->post(route('admin.staff.store'), [
            'name' => 'Custom Pass Staff',
            'employee_id' => 'EMP_CUSTOM',
            'email' => 'custom.staff@example.com',
            'phone' => '9876543219',
            'branch_id' => $this->branch->id,
            'position_id' => $this->position->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'password' => $customTempPass,
            'send_welcome_email' => '1',
        ]);

        $user = User::where('email', 'custom.staff@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check($customTempPass, $user->password));

        Mail::assertSent(AccountWelcomeMail::class, function (AccountWelcomeMail $mail) use ($customTempPass): bool {
            return $mail->hasTo('custom.staff@example.com') && $mail->rawPassword === $customTempPass;
        });
    }

    public function test_agent_can_manually_set_password_for_downline_recruit(): void
    {
        Mail::fake();

        $agentUser = User::create([
            'name' => 'Team Lead Agent',
            'email' => 'teamlead_'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $agentUser->roles()->attach($agentRole->id);

        $sponsorAgent = Agent::create([
            'user_id' => $agentUser->id,
            'member_code' => 'SP_LEAD_'.rand(1000, 9999),
            'agent_id' => 'SP_LEAD_'.rand(1000, 9999),
            'name' => 'Team Lead Agent',
            'email' => $agentUser->email,
            'password' => Hash::make('password123'),
            'phone' => '9876543210',
            'branch_id' => $this->branch->id,
            'rank_id' => $this->rank->id,
            'status' => 'active',
            'joining_date' => now()->toDateString(),
        ]);

        $manualPassword = 'ManualPass@2026';
        $recruitEmail = 'downline_manual_'.uniqid().'@example.com';

        $response = $this->actingAs($agentUser)->post(route('agent.agents.store'), [
            'name' => 'Manual Pass Recruit',
            'agent_id' => 'AG_MAN_'.rand(1000, 9999),
            'email' => $recruitEmail,
            'phone' => '9876543211',
            'rank_id' => $this->rank->id,
            'joining_date' => now()->toDateString(),
            'password' => $manualPassword,
            'send_welcome_email' => '1',
        ]);

        $response->assertRedirect(route('agent.agents.index'));
        $response->assertSessionHas('success');

        $recruitAgent = Agent::where('email', $recruitEmail)->first();
        $this->assertNotNull($recruitAgent);
        $this->assertTrue(Hash::check($manualPassword, $recruitAgent->password));

        $recruitUser = User::where('email', $recruitEmail)->first();
        $this->assertNotNull($recruitUser);
        $this->assertTrue(Hash::check($manualPassword, $recruitUser->password));

        Mail::assertSent(AccountWelcomeMail::class, function (AccountWelcomeMail $mail) use ($recruitEmail, $manualPassword): bool {
            return $mail->hasTo($recruitEmail) && $mail->rawPassword === $manualPassword;
        });
    }
}
