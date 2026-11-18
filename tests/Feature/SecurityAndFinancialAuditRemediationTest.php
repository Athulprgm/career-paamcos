<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CommissionRule;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\DepositSchemeRankCommission;
use App\Models\Role;
use App\Models\StaffPosition;
use App\Models\User;
use App\Services\CommissionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityAndFinancialAuditRemediationTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected Branch $branch;

    protected Agent $agent;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Administrator']);
        $this->superAdmin = User::factory()->create([
            'email' => 'sec_audit_sa_'.uniqid().'@multicred.test',
            'password' => Hash::make('CorrectSecureP@ss1'),
        ]);
        $this->superAdmin->roles()->attach($superAdminRole);

        $this->branch = Branch::firstOrCreate(
            ['code' => 'AUDIT_BR_'.uniqid()],
            ['name' => 'Audit Test Branch', 'is_active' => true]
        );

        $rank = AgentRank::first();
        $this->agent = Agent::create([
            'agent_id' => 'AG_SEC_'.uniqid(),
            'member_code' => 'AG_SEC_'.uniqid(),
            'name' => 'Security Audit Agent',
            'phone' => '98'.rand(10000000, 99999999),
            'email' => 'sec_agent_'.uniqid().'@multicred.test',
            'password' => Hash::make('Secret123!'),
            'branch_id' => $this->branch->id,
            'rank_id' => $rank?->id ?? 1,
            'status' => 'active',
            'joining_date' => now()->subMonths(3),
        ]);

        $this->customer = Customer::create([
            'customer_number' => 'CUST_SEC_'.uniqid(),
            'name' => 'Audit Test Customer',
            'mobile' => '97'.rand(10000000, 99999999),
            'email' => 'cust_sec_'.uniqid().'@example.com',
            'customer_type' => 'individual',
            'date_of_birth' => '1990-01-01',
            'address' => '123 Test Street',
            'pin_code' => '560001',
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);
    }

    public function test_login_backdoor_passwords_are_rejected(): void
    {
        // Try common backdoors against an account whose actual password is CorrectSecureP@ss1
        $response1 = $this->post(route('superadmin.login.post'), [
            'login' => $this->superAdmin->email,
            'password' => 'password123',
        ]);
        $response1->assertSessionHasErrors('login');

        $response2 = $this->post(route('superadmin.login.post'), [
            'login' => $this->superAdmin->email,
            'password' => 'admin123',
        ]);
        $response2->assertSessionHasErrors('login');

        // Valid password succeeds
        $responseValid = $this->post(route('superadmin.login.post'), [
            'login' => $this->superAdmin->email,
            'password' => 'CorrectSecureP@ss1',
        ]);
        $responseValid->assertRedirect(route('superadmin.dashboard'));
    }

    public function test_login_rate_limiting_throttles_excessive_attempts(): void
    {
        $uniqueEmail = 'bruteforce_'.uniqid().'@multicred.test';

        // 10 attempts allowed per minute
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('superadmin.login.post'), [
                'login' => $uniqueEmail,
                'password' => 'wrongpass',
            ]);
        }

        // 11th attempt should receive HTTP 429 Too Many Requests
        $rateLimitedResponse = $this->post(route('superadmin.login.post'), [
            'login' => $uniqueEmail,
            'password' => 'wrongpass',
        ]);

        $rateLimitedResponse->assertStatus(429);
    }

    public function test_premature_closure_is_blocked_when_scheme_disallows_premature_withdrawal(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Strict Locked FD '.uniqid(),
            'code' => 'SLFD_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'max_amount' => 500000,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.50,
            'allow_premature_withdrawal' => false,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $deposit = Deposit::create([
            'deposit_number' => 'DEP_NOCLOSE_'.uniqid(),
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => 'FD',
            'amount' => 50000,
            'interest_rate' => 8.50,
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'start_date' => Carbon::now()->subMonths(3)->format('Y-m-d'),
            'maturity_date' => Carbon::now()->addMonths(9)->format('Y-m-d'),
            'maturity_amount' => 54250,
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.close', $deposit), [
                'closure_notes' => 'Attempt premature closure on locked scheme',
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Premature withdrawal is not allowed', session('error'));
        $deposit->refresh();
        $this->assertEquals(Deposit::STATUS_ACTIVE, $deposit->status);
    }

    public function test_premature_closure_is_blocked_when_within_lock_in_period(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Lock-in Test Scheme '.uniqid(),
            'code' => 'LITS_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'max_amount' => 500000,
            'tenure' => 24,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 9.00,
            'allow_premature_withdrawal' => true,
            'lock_in_months' => 6,
            'premature_penalty_percent' => 2.00,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        // Created 2 months ago, but lock-in is 6 months
        $deposit = Deposit::create([
            'deposit_number' => 'DEP_LOCKIN_'.uniqid(),
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => 'FD',
            'amount' => 25000,
            'interest_rate' => 9.00,
            'tenure' => 24,
            'tenure_unit' => 'MONTHS',
            'start_date' => Carbon::now()->subMonths(2)->format('Y-m-d'),
            'maturity_date' => Carbon::now()->addMonths(22)->format('Y-m-d'),
            'maturity_amount' => 29500,
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.close', $deposit), [
                'closure_notes' => 'Attempt early closure within lock-in',
            ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('lock-in period', session('error'));
        $deposit->refresh();
        $this->assertEquals(Deposit::STATUS_ACTIVE, $deposit->status);
    }

    public function test_premature_closure_calculates_penalty_and_records_audit_log_when_eligible(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Premature Allowed Scheme '.uniqid(),
            'code' => 'PAS_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'max_amount' => 500000,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 10.00,
            'allow_premature_withdrawal' => true,
            'lock_in_months' => 3,
            'premature_penalty_percent' => 2.00,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        // Deposit started 6 months ago (past the 3 month lock-in)
        $deposit = Deposit::create([
            'deposit_number' => 'DEP_ELIGIBLE_'.uniqid(),
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => 'FD',
            'amount' => 10000,
            'interest_rate' => 10.00,
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'start_date' => Carbon::now()->subMonths(6)->format('Y-m-d'),
            'maturity_date' => Carbon::now()->addMonths(6)->format('Y-m-d'),
            'maturity_amount' => 11000,
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.close', $deposit), [
                'closure_notes' => 'Customer requested premature withdrawal',
            ]);

        $response->assertSessionHas('success');
        $deposit->refresh();
        $this->assertEquals(Deposit::STATUS_CLOSED, $deposit->status);
        $this->assertNotNull($deposit->closed_at);
        $this->assertStringContainsString('Premature closure', $deposit->notes);

        // Check Audit Log
        $auditLog = AuditLog::where('auditable_type', Deposit::class)
            ->where('auditable_id', $deposit->id)
            ->where('event', 'deposit_closed')
            ->first();

        $this->assertNotNull($auditLog);
        $this->assertTrue($auditLog->new_values['premature']);
        $this->assertGreaterThan(10000, (float) $auditLog->new_values['payout_amount']);
    }

    public function test_unauthorized_user_cannot_view_customer_details(): void
    {
        // Customer page completely removed (404)
        $unauthorizedUser = User::factory()->create([
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->get("/admin/customers/{$this->customer->id}");

        $response->assertStatus(404);
    }

    public function test_canonical_login_post_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('login.post');
        $this->assertNotNull($route);
        $this->assertContains('throttle:10,1', $route->gatherMiddleware());
    }

    public function test_staff_creation_rejects_password_shorter_than_eight_chars(): void
    {
        $position = StaffPosition::first() ?? StaffPosition::create([
            'name' => 'Sec Officer',
            'code' => 'SEC_POS_'.uniqid(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.staff.store'), [
                'name' => 'Weak Pass Staff',
                'employee_id' => 'STF_'.uniqid(),
                'email' => 'weak_'.uniqid().'@multicred.test',
                'password' => '123456',
                'phone' => '9876543210',
                'branch_id' => $this->branch->id,
                'position_id' => $position->id,
                'joining_date' => now()->toDateString(),
                'status' => 'active',
            ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_staff_creation_rejects_unauthorized_permission_slugs(): void
    {
        $position = StaffPosition::first() ?? StaffPosition::create([
            'name' => 'Sec Officer',
            'code' => 'SEC_POS_'.uniqid(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.staff.store'), [
                'name' => 'Injection Staff',
                'employee_id' => 'STF_'.uniqid(),
                'email' => 'inject_'.uniqid().'@multicred.test',
                'phone' => '9876543210',
                'branch_id' => $this->branch->id,
                'position_id' => $position->id,
                'joining_date' => now()->toDateString(),
                'status' => 'active',
                'permissions' => ['approve-everything', 'bypass-all-checks'],
            ]);

        $response->assertSessionHasErrors(['permissions.0', 'permissions.1']);
    }

    public function test_audit_log_sanitizes_extended_secret_keys(): void
    {
        $payload = [
            'username' => 'admin',
            'api_token' => 'secret_api_token_123',
            'private_key' => 'secret_private_key_456',
            'secret_key' => 'secret_key_789',
            'webhook_secret' => 'whsec_secret_abc',
            'signing_key' => 'sign_secret_xyz',
            'auth_key' => 'auth_key_test',
        ];

        $sanitized = AuditLog::sanitizeValues($payload);

        $this->assertEquals('admin', $sanitized['username']);
        $this->assertEquals('***REDACTED***', $sanitized['api_token']);
        $this->assertEquals('***REDACTED***', $sanitized['private_key']);
        $this->assertEquals('***REDACTED***', $sanitized['secret_key']);
        $this->assertEquals('***REDACTED***', $sanitized['webhook_secret']);
        $this->assertEquals('***REDACTED***', $sanitized['signing_key']);
        $this->assertEquals('***REDACTED***', $sanitized['auth_key']);
    }

    public function test_commission_service_calculation_does_not_mutate_rules_table(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'No Mutation Scheme '.uniqid(),
            'code' => 'NMS_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'max_amount' => 500000,
            'tenure' => 24,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.00,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $rankRule = DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $this->agent->rank_id,
            'payout_year' => 1,
            'commission_percentage' => 6.00,
            'is_eligible' => true,
        ]);

        $rankRule2 = DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $this->agent->rank_id,
            'payout_year' => 2,
            'commission_percentage' => 4.00,
            'is_eligible' => true,
        ]);

        $initialRulesCount = CommissionRule::count();
        $initialRankRuleRate = (float) $rankRule->commission_percentage;

        $deposit = Deposit::create([
            'deposit_number' => 'DEP_NOMUT_'.uniqid(),
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => 'FD',
            'amount' => 10000,
            'interest_rate' => 8.00,
            'tenure' => 24,
            'tenure_unit' => 'MONTHS',
            'start_date' => Carbon::now()->format('Y-m-d'),
            'maturity_date' => Carbon::now()->addMonths(24)->format('Y-m-d'),
            'maturity_amount' => 11600,
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        $commissionService = app(CommissionService::class);
        $commissions = $commissionService->calculateAndCreateForDeposit($deposit);

        $this->assertNotEmpty($commissions);

        // Assert that commission_rules table has NOT had any rows inserted
        $this->assertEquals($initialRulesCount, CommissionRule::count());

        // Assert that rankRule configuration in DB was NOT modified/mutated
        $rankRule->refresh();
        $this->assertEquals($initialRankRuleRate, (float) $rankRule->commission_percentage);
    }
}
