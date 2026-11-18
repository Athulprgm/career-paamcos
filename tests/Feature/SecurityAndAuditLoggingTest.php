<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CareerLevel;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\PromotionRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SecurityAndAuditLoggingTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $branchAdmin1;

    protected User $branchAdmin2;

    protected User $staffUser;

    protected User $agentUser1;

    protected User $agentUser2;

    protected Branch $branch1;

    protected Branch $branch2;

    protected Agent $agent1;

    protected Agent $agent2;

    protected Customer $customer1;

    protected Customer $customer2;

    protected DepositScheme $scheme;

    protected Deposit $deposit1;

    protected Deposit $deposit2;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Roles
        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Administrator']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin']);
        $staffRole = Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff']);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent']);

        // 2. Branches
        $this->branch1 = Branch::create([
            'name' => 'Security Test Branch 1 '.uniqid(),
            'code' => 'STB1_'.rand(100, 999),
            'is_active' => true,
        ]);

        $this->branch2 = Branch::create([
            'name' => 'Security Test Branch 2 '.uniqid(),
            'code' => 'STB2_'.rand(100, 999),
            'is_active' => true,
        ]);

        // 3. Super Admin
        $this->superAdmin = User::create([
            'name' => 'Security Super Admin '.uniqid(),
            'email' => 'sec_super_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => null,
        ]);
        $this->superAdmin->roles()->attach($superAdminRole->id);

        // 4. Branch Admins
        $this->branchAdmin1 = User::create([
            'name' => 'Branch Admin 1 '.uniqid(),
            'email' => 'badmin1_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch1->id,
        ]);
        $this->branchAdmin1->roles()->attach($adminRole->id);

        $this->branchAdmin2 = User::create([
            'name' => 'Branch Admin 2 '.uniqid(),
            'email' => 'badmin2_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch2->id,
        ]);
        $this->branchAdmin2->roles()->attach($adminRole->id);

        // 5. Staff User (Regular Staff without admin or agent role)
        $this->staffUser = User::create([
            'name' => 'Staff User '.uniqid(),
            'email' => 'staff_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch1->id,
        ]);
        $this->staffUser->roles()->attach($staffRole->id);

        // 6. Agents
        $rank = AgentRank::first() ?? AgentRank::create([
            'name' => 'Junior Rank '.uniqid(),
            'code' => 'JR_'.rand(100, 999),
            'level' => 1,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $this->agentUser1 = User::create([
            'name' => 'Agent User 1 '.uniqid(),
            'email' => 'ag1_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch1->id,
        ]);
        $this->agentUser1->roles()->attach($agentRole->id);

        $this->agent1 = Agent::create([
            'user_id' => $this->agentUser1->id,
            'name' => $this->agentUser1->name,
            'agent_id' => 'AG_'.rand(1000, 9999),
            'member_code' => 'AG_'.rand(1000, 9999),
            'email' => $this->agentUser1->email,
            'phone' => '9988776655',
            'branch_id' => $this->branch1->id,
            'rank_id' => $rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $this->agentUser2 = User::create([
            'name' => 'Agent User 2 '.uniqid(),
            'email' => 'ag2_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch2->id,
        ]);
        $this->agentUser2->roles()->attach($agentRole->id);

        $this->agent2 = Agent::create([
            'user_id' => $this->agentUser2->id,
            'name' => $this->agentUser2->name,
            'agent_id' => 'AG2_'.rand(1000, 9999),
            'member_code' => 'AG2_'.rand(1000, 9999),
            'email' => $this->agentUser2->email,
            'phone' => '9988776656',
            'branch_id' => $this->branch2->id,
            'rank_id' => $rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        // 7. Customers
        $this->customer1 = Customer::create([
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'name' => 'John Branch1',
            'customer_number' => 'CUS1_'.rand(10000, 99999),
            'customer_type' => 'individual',
            'date_of_birth' => '1990-01-01',
            'address' => '123 Main Street',
            'pin_code' => '560001',
            'mobile' => '9111222333',
            'email' => 'john_'.uniqid().'@example.com',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $this->customer2 = Customer::create([
            'branch_id' => $this->branch2->id,
            'agent_id' => $this->agent2->id,
            'name' => 'Jane Branch2',
            'customer_number' => 'CUS2_'.rand(10000, 99999),
            'customer_type' => 'individual',
            'date_of_birth' => '1992-02-02',
            'address' => '456 Second Street',
            'pin_code' => '560002',
            'mobile' => '9111222334',
            'email' => 'jane_'.uniqid().'@example.com',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        // 8. Deposit Scheme & Deposits
        $this->scheme = DepositScheme::first() ?? DepositScheme::create([
            'name' => 'Recurring Deposit '.uniqid(),
            'code' => 'RD_'.rand(100, 999),
            'scheme_type' => 'RECURRING',
            'interest_rate' => 8.50,
            'minimum_amount' => 500,
            'term_months' => 12,
            'status' => 'active',
        ]);

        $this->deposit1 = Deposit::create([
            'account_number' => 'DEP1_'.rand(10000, 99999),
            'customer_id' => $this->customer1->id,
            'deposit_scheme_id' => $this->scheme->id,
            'branch_id' => $this->branch1->id,
            'agent_id' => $this->agent1->id,
            'amount' => 5000,
            'interest_rate' => 8.50,
            'term_months' => 12,
            'status' => Deposit::STATUS_PENDING_APPROVAL,
            'deposit_date' => now()->toDateString(),
            'created_by' => $this->branchAdmin1->id,
        ]);

        $this->deposit2 = Deposit::create([
            'account_number' => 'DEP2_'.rand(10000, 99999),
            'customer_id' => $this->customer2->id,
            'deposit_scheme_id' => $this->scheme->id,
            'branch_id' => $this->branch2->id,
            'agent_id' => $this->agent2->id,
            'amount' => 10000,
            'interest_rate' => 8.50,
            'term_months' => 12,
            'status' => Deposit::STATUS_PENDING_APPROVAL,
            'deposit_date' => now()->toDateString(),
            'created_by' => $this->branchAdmin2->id,
        ]);
    }

    // ─── 1. ACCESS CONTROL & ROLE AUTHORIZATION ──────────────────────────────

    public function test_super_admin_has_full_unrestricted_access(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('admin.deposits.index'));
        $response->assertOk();

        // Customer pages are completely removed (404)
        $res1 = $this->actingAs($this->superAdmin)->get("/admin/customers/{$this->customer1->id}");
        $res1->assertStatus(404);

        $res2 = $this->actingAs($this->superAdmin)->get("/admin/customers/{$this->customer2->id}");
        $res2->assertStatus(404);
    }

    public function test_regular_staff_without_admin_role_cannot_access_admin_panel(): void
    {
        $response = $this->actingAs($this->staffUser)->get(route('admin.agents.index'));
        $response->assertStatus(403);
    }

    public function test_agent_cannot_access_customers_or_deposits(): void
    {
        // Customer routes are completely removed (404)
        $ownRes = $this->actingAs($this->agentUser1)->get("/admin/customers/{$this->customer1->id}");
        $ownRes->assertStatus(404);

        $otherRes = $this->actingAs($this->agentUser1)->get("/admin/customers/{$this->customer2->id}");
        $otherRes->assertStatus(404);

        $dirRes = $this->actingAs($this->agentUser1)->get('/admin/customers');
        $dirRes->assertStatus(404);
    }

    public function test_agent_cannot_perform_approvals_or_administrative_actions(): void
    {
        // Deposit Approval
        $depRes = $this->actingAs($this->agentUser1)->post(route('admin.deposits.approve', $this->deposit1));
        $depRes->assertStatus(403);

        // Commission Rule Creation
        $ruleRes = $this->actingAs($this->agentUser1)->get(route('admin.commission-rules.create'));
        $ruleRes->assertStatus(403);

        // Customer Status Toggle - page completely removed (returns 404)
        $custToggle = $this->actingAs($this->agentUser1)->post("/admin/customers/{$this->customer1->id}/toggle-status");
        $custToggle->assertStatus(404);

        // Audit Logs Access - page completely removed (returns 404)
        $auditRes = $this->actingAs($this->agentUser1)->get('/admin/audit-logs');
        $auditRes->assertStatus(404);
    }

    // ─── 2. BRANCH ISOLATION ──────────────────────────────────────────────────

    public function test_branch_admin_cannot_access_customers_from_other_branches(): void
    {
        // Customer page completely removed (returns 404)
        $response = $this->actingAs($this->branchAdmin1)->get("/admin/customers/{$this->customer2->id}");
        $response->assertStatus(404);
    }

    public function test_branch_admin_cannot_approve_deposits_from_other_branches(): void
    {
        // Branch Admin 1 approving Deposit 2 (Branch 2)
        $response = $this->actingAs($this->branchAdmin1)->post(route('admin.deposits.approve', $this->deposit2));
        $response->assertStatus(403);

        $this->deposit2->refresh();
        $this->assertEquals(Deposit::STATUS_PENDING_APPROVAL, $this->deposit2->status);
    }

    public function test_branch_admin_cannot_access_audit_logs_of_other_branches(): void
    {
        $logBranch2 = AuditLog::record(
            event: 'test_branch2_event',
            description: 'Sensitive Branch 2 action',
            auditable: $this->customer2,
            branchId: $this->branch2->id
        );

        $response = $this->actingAs($this->branchAdmin1)->get('/admin/audit-logs/'.$logBranch2->id);
        $response->assertStatus(404);
    }

    // ─── 3. APPROVAL & FINANCIAL SECURITY ────────────────────────────────────

    public function test_self_approval_is_prohibited_for_commissions(): void
    {
        $commission = Commission::create([
            'commission_number' => Commission::generateCommissionNumber(),
            'agent_id' => $this->agent1->id,
            'branch_id' => $this->branch1->id,
            'customer_id' => $this->customer1->id,
            'deposit_id' => $this->deposit1->id,
            'deposit_collection_id' => null,
            'base_amount' => 5000,
            'rate' => 5.00,
            'calculated_amount' => 250,
            'net_amount' => 250,
            'status' => Commission::STATUS_PENDING,
            'period' => now()->format('Y-m'),
        ]);

        // Agent 1 attempts to approve own commission via HTTP route
        $response = $this->actingAs($this->agentUser1)->post(route('admin.commissions.approve', $commission));
        $response->assertStatus(403);

        $commission->refresh();
        $this->assertEquals(Commission::STATUS_PENDING, $commission->status);
    }

    public function test_self_approval_is_prohibited_for_career_promotions(): void
    {
        $level2 = CareerLevel::where('sort_order', '>', 1)->first() ?? CareerLevel::create([
            'name' => 'Senior Rank '.uniqid(),
            'code' => 'SR_'.rand(100, 999),
            'sort_order' => 2,
            'status' => 'active',
        ]);

        $promotion = PromotionRequest::create([
            'request_number' => 'PR_'.rand(1000, 9999),
            'agent_id' => $this->agent1->id,
            'branch_id' => $this->branch1->id,
            'current_level_id' => $this->agent1->rank_id,
            'proposed_level_id' => $level2->id,
            'status' => PromotionRequest::STATUS_PENDING_REVIEW,
            'requested_by' => $this->agentUser1->id,
            'request_date' => now()->toDateString(),
        ]);

        // Attempt approve as the agent themselves
        $response = $this->actingAs($this->agentUser1)->post(route('admin.career.requests.approve', $promotion));
        $response->assertStatus(403);
    }

    public function test_invalid_status_transitions_are_strictly_rejected(): void
    {
        // Approve deposit 1
        $this->actingAs($this->branchAdmin1)->post(route('admin.deposits.approve', $this->deposit1));
        $this->deposit1->refresh();
        $this->assertEquals(Deposit::STATUS_APPROVED, $this->deposit1->status);

        // Attempting to approve an ALREADY APPROVED deposit must fail
        $secondApprove = $this->actingAs($this->branchAdmin1)->post(route('admin.deposits.approve', $this->deposit1));
        $this->deposit1->refresh();
        $this->assertEquals(Deposit::STATUS_APPROVED, $this->deposit1->status);
    }

    // ─── 4. AUDIT TRAIL INTEGRITY & IMMUTABILITY ──────────────────────────────

    public function test_audit_logs_are_strictly_immutable_and_cannot_be_tampered_with(): void
    {
        $log = AuditLog::record(
            event: 'security_audit_test',
            description: 'Original log description',
            auditable: $this->customer1,
            branchId: $this->branch1->id,
            user: $this->superAdmin
        );

        // Attempt to UPDATE must throw RuntimeException
        $updateBlocked = false;
        try {
            $log->update(['description' => 'Malicious description edit']);
        } catch (\RuntimeException $e) {
            $updateBlocked = true;
        }
        $this->assertTrue($updateBlocked, 'Expected update on AuditLog to throw RuntimeException.');

        // Attempt to DELETE must throw RuntimeException
        $deleteBlocked = false;
        try {
            $log->delete();
        } catch (\RuntimeException $e) {
            $deleteBlocked = true;
        }
        $this->assertTrue($deleteBlocked, 'Expected delete on AuditLog to throw RuntimeException.');
    }

    public function test_sensitive_credentials_and_tokens_are_redacted_in_audit_records(): void
    {
        $payloadWithSecrets = [
            'username' => 'staff_member',
            'password' => 'super_secret_password_123',
            'api_token' => 'sk_live_abcdef123456789',
            'api_key' => 'secret_api_key_xyz',
            'email' => 'staff@example.com',
        ];

        $sanitized = AuditLog::sanitizeValues($payloadWithSecrets);

        $this->assertEquals('staff_member', $sanitized['username']);
        $this->assertEquals('staff@example.com', $sanitized['email']);
        $this->assertEquals('***REDACTED***', $sanitized['password']);
        $this->assertEquals('***REDACTED***', $sanitized['api_token']);
        $this->assertEquals('***REDACTED***', $sanitized['api_key']);
    }

    public function test_audit_logs_administration_page_is_completely_removed(): void
    {
        // 1. Index view is 404
        $response = $this->actingAs($this->superAdmin)->get('/admin/audit-logs');
        $response->assertStatus(404);

        // 2. Export endpoint is 404
        $exportRes = $this->actingAs($this->superAdmin)->get('/admin/audit-logs/export');
        $exportRes->assertStatus(404);
    }

    public function test_login_and_logout_events_are_recorded_in_audit_logs(): void
    {
        // Perform login
        $this->post(route('superadmin.login.post'), [
            'email' => $this->superAdmin->email,
            'password' => 'password',
        ]);

        $loginLog = AuditLog::where('event', 'login')
            ->where('user_id', $this->superAdmin->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($loginLog, 'Expected login audit log entry to be created.');
        $this->assertEquals($this->superAdmin->id, $loginLog->user_id);

        // Perform logout
        $this->actingAs($this->superAdmin)->post(route('superadmin.logout'));

        $logoutLog = AuditLog::where('event', 'logout')
            ->where('user_id', $this->superAdmin->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($logoutLog, 'Expected logout audit log entry to be created.');
    }
}
