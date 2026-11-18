<?php

namespace Tests\Feature;

use App\Models\DepositScheme;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DepositSchemeConfigurationTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected ?User $branchAdmin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin Test',
            'email' => 'superadmin_'.uniqid().'@multicred.com',
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Branch Admin', 'status' => 'active']);
        $this->branchAdmin = User::factory()->create([
            'name' => 'Branch Admin Test',
            'email' => 'branchadmin_'.uniqid().'@multicred.com',
        ]);
        $this->branchAdmin->roles()->attach($adminRole->id);
    }

    public function test_super_admin_can_access_deposit_schemes_index(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.index'));

        $response->assertStatus(200);
        $response->assertSee('Manage Schemes');
    }

    public function test_sidebar_displays_active_deposit_schemes(): void
    {
        $uniqueName = 'Custom Super Scheme '.uniqid();
        $scheme = DepositScheme::create([
            'name' => $uniqueName,
            'code' => 'CSS_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_RD,
            'min_amount' => 500,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.00,
            'collection_frequency' => DepositScheme::FREQ_MONTHLY,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.index'));

        $response->assertStatus(200);
        $response->assertSee($uniqueName);
    }

    public function test_non_super_admin_cannot_create_deposit_scheme(): void
    {
        $response = $this->actingAs($this->branchAdmin)
            ->get(route('admin.deposit-schemes.create'));

        $response->assertStatus(403);
    }

    public function test_super_admin_can_create_deposit_scheme_with_banking_fields(): void
    {
        $code = 'FD_'.uniqid();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.store'), [
                'name' => 'Senior Growth Fixed Deposit',
                'code' => $code,
                'scheme_type' => DepositScheme::TYPE_FD,
                'description' => 'Real-world 12M term deposit scheme with senior citizen benefit',
                'min_amount' => 5000,
                'max_amount' => 1000000,
                'tenure' => 12,
                'tenure_unit' => DepositScheme::TENURE_MONTHS,
                'interest_rate' => 8.50,
                'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
                'senior_citizen_extra_rate' => 0.50,
                'lock_in_months' => 3,
                'allow_premature_withdrawal' => 1,
                'premature_penalty_percent' => 1.00,
                'allow_loan_against_deposit' => 1,
                'max_loan_percentage' => 85.00,
                'grace_period_days' => 7,
                'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
                'status' => DepositScheme::STATUS_ACTIVE,
            ]);

        $scheme = DepositScheme::where('code', $code)->firstOrFail();
        $response->assertRedirect(route('admin.deposit-schemes.show', $scheme));

        $this->assertDatabaseHas('deposit_schemes', [
            'code' => $code,
            'name' => 'Senior Growth Fixed Deposit',
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
            'senior_citizen_extra_rate' => 0.50,
            'lock_in_months' => 3,
            'allow_premature_withdrawal' => 1,
            'premature_penalty_percent' => 1.00,
            'allow_loan_against_deposit' => 1,
            'max_loan_percentage' => 85.00,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);
    }

    public function test_super_admin_can_create_deposit_scheme_with_unified_scheme_name(): void
    {
        $code = 'RD_'.uniqid();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.store'), [
                'code' => $code,
                'scheme_type' => DepositScheme::TYPE_RD,
                'min_amount' => 500,
                'tenure' => 12,
                'tenure_unit' => DepositScheme::TENURE_MONTHS,
                'interest_rate' => 7.25,
                'collection_frequency' => DepositScheme::FREQ_MONTHLY,
                'status' => DepositScheme::STATUS_ACTIVE,
            ]);

        $scheme = DepositScheme::where('code', $code)->firstOrFail();
        $response->assertRedirect(route('admin.deposit-schemes.show', $scheme));

        $this->assertDatabaseHas('deposit_schemes', [
            'code' => $code,
            'name' => 'Recurring Deposit (RD)',
            'scheme_type' => DepositScheme::TYPE_RD,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);
    }

    public function test_super_admin_can_create_deposit_scheme_with_text_name_input_only(): void
    {
        $code = 'TEXT_'.uniqid();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.store'), [
                'name' => 'Special Daily Pigmy Scheme',
                'code' => $code,
                'min_amount' => 100,
                'tenure' => 100,
                'tenure_unit' => DepositScheme::TENURE_DAYS,
                'interest_rate' => 6.00,
                'status' => DepositScheme::STATUS_ACTIVE,
            ]);

        $scheme = DepositScheme::where('code', $code)->firstOrFail();
        $response->assertRedirect(route('admin.deposit-schemes.show', $scheme));

        $this->assertDatabaseHas('deposit_schemes', [
            'code' => $code,
            'name' => 'Special Daily Pigmy Scheme',
            'scheme_type' => DepositScheme::TYPE_DD,
            'collection_frequency' => DepositScheme::FREQ_DAILY,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);
    }

    public function test_super_admin_can_update_deposit_scheme(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Existing Term Scheme',
            'code' => 'EXT_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.deposit-schemes.update', $scheme), [
                'name' => 'Updated Term Scheme',
                'code' => $scheme->code,
                'scheme_type' => DepositScheme::TYPE_FD,
                'min_amount' => 2000,
                'tenure' => 24,
                'tenure_unit' => DepositScheme::TENURE_MONTHS,
                'interest_rate' => 8.25,
                'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
                'status' => DepositScheme::STATUS_ACTIVE,
            ]);

        $response->assertRedirect(route('admin.deposit-schemes.show', $scheme));
        $this->assertEquals('Updated Term Scheme', $scheme->fresh()->name);
        $this->assertEquals(2000.0, (float) $scheme->fresh()->min_amount);
    }

    public function test_super_admin_can_toggle_deposit_scheme_status(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Toggle Scheme',
            'code' => 'TOG_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_RD,
            'min_amount' => 500,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.50,
            'collection_frequency' => DepositScheme::FREQ_MONTHLY,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.toggle-status', $scheme));

        $response->assertRedirect();
        $this->assertEquals(DepositScheme::STATUS_INACTIVE, $scheme->fresh()->status);
    }

    public function test_deposit_schemes_table_displays_standard_interest_rates(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Dual Rate Test FD',
            'code' => 'DRT_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'tenure' => 2,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.25,
            'senior_citizen_extra_rate' => 0.50,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.index'));

        $response->assertStatus(200);
        $response->assertSee('Interest Rates');
        $response->assertSee('Dual Rate Test FD');
        $response->assertSee('10.25%');
        $response->assertSee('Standard');
        $response->assertSee('Senior');
        $response->assertSee('10.75%');
    }

    public function test_deposit_schemes_form_does_not_contain_multi_year_tenure_tiers_section(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Form Section Check FD',
            'code' => 'FSC_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 8.50,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $createResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertDontSee('Multi-Year / Duration-Based Interest Rates (Tenure Tiers)');

        $editResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.edit', $scheme));
        $editResponse->assertStatus(200);
        $editResponse->assertDontSee('Multi-Year / Duration-Based Interest Rates (Tenure Tiers)');
    }
}
