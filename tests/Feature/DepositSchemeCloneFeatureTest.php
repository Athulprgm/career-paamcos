<?php

namespace Tests\Feature;

use App\Models\AgentRank;
use App\Models\AuditLog;
use App\Models\CommissionRule;
use App\Models\DepositScheme;
use App\Models\DepositSchemeInterestRate;
use App\Models\DepositSchemeRankCommission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DepositSchemeCloneFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected ?DepositScheme $sampleScheme = null;

    protected function setUp(): void
    {
        parent::setUp();

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin Test',
            'email' => 'superadmin_'.uniqid().'@multicred.com',
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        $this->sampleScheme = DepositScheme::create([
            'name' => 'Fixed Growth Plan '.uniqid(),
            'code' => 'FGP'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'description' => 'Reliable long-term wealth builder scheme.',
            'min_amount' => 5000.00,
            'max_amount' => 500000.00,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.25,
            'senior_citizen_extra_rate' => 0.50,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
            'status' => DepositScheme::STATUS_ACTIVE,
            'total_commission_percentage' => 4.50,
            'promotion_target_percentage' => 25.00,
            'created_by' => $this->superAdmin->id,
        ]);

        $this->sampleScheme->syncPrimaryInterestRate($this->superAdmin->id);

        // Add an additional duration tier
        $this->sampleScheme->interestRates()->create([
            'tenure' => 24,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.75,
            'senior_citizen_extra_rate' => 0.50,
            'min_amount' => 5000.00,
            'max_amount' => 500000.00,
            'status' => DepositSchemeInterestRate::STATUS_ACTIVE,
            'effective_from' => now()->toDateString(),
            'created_by' => $this->superAdmin->id,
        ]);

        // Add a rank commission rule
        $rank = AgentRank::first();
        if ($rank) {
            DepositSchemeRankCommission::create([
                'deposit_scheme_id' => $this->sampleScheme->id,
                'rank_id' => $rank->id,
                'commission_percentage' => 2.50,
                'is_eligible' => true,
                'payout_year' => 1,
            ]);

            CommissionRule::create([
                'deposit_scheme_id' => $this->sampleScheme->id,
                'rank_id' => $rank->id,
                'deposit_type' => $this->sampleScheme->scheme_type,
                'name' => $this->sampleScheme->name.' - Facilitator',
                'code' => CommissionRule::generateRuleCode(),
                'commission_type' => CommissionRule::TYPE_PERCENTAGE,
                'calculation_basis' => CommissionRule::BASIS_COLLECTION_AMOUNT,
                'rate' => 2.50,
                'status' => CommissionRule::STATUS_ACTIVE,
                'effective_from' => now()->toDateString(),
                'created_by' => $this->superAdmin->id,
            ]);
        }
    }

    public function test_clone_button_is_rendered_on_deposit_schemes_index_table(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.index'));

        $response->assertStatus(200);
        $response->assertSee(route('admin.deposit-schemes.clone', $this->sampleScheme));
        $response->assertSee('Clone Scheme: '.$this->sampleScheme->name);
        $response->assertSee('bi-copy');

        // Test with filters
        $activeResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.index', ['status' => 'ACTIVE']));
        $activeResponse->assertStatus(200);

        $inactiveResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.index', ['status' => 'INACTIVE']));
        $inactiveResponse->assertStatus(200);

        $searchResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.index', ['search' => 'Growth', 'type' => DepositScheme::TYPE_FD]));
        $searchResponse->assertStatus(200);
    }

    public function test_clone_button_is_rendered_on_show_and_edit_pages(): void
    {
        $showResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.show', $this->sampleScheme));
        $showResponse->assertStatus(200);
        $showResponse->assertSee(route('admin.deposit-schemes.clone', $this->sampleScheme));
        $showResponse->assertSee('Clone Scheme');

        $editResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.edit', $this->sampleScheme));
        $editResponse->assertStatus(200);
        $editResponse->assertSee(route('admin.deposit-schemes.clone', $this->sampleScheme));
        $editResponse->assertSee('Clone Scheme');
    }

    public function test_get_clone_route_renders_prefilled_create_form_with_unique_code_and_name(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.clone', $this->sampleScheme));

        $response->assertStatus(200);
        $response->assertSee('Clone Deposit Scheme');
        $response->assertSee($this->sampleScheme->name.' (Copy)');
        $response->assertSee('name="cloned_from_id" value="'.$this->sampleScheme->id.'"', false);
        $response->assertSee('Cloning from Scheme: '.$this->sampleScheme->name);
        $response->assertSee('Save Cloned Scheme');
    }

    public function test_get_create_route_with_clone_from_param_renders_prefilled_clone_form(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.create', ['clone_from' => $this->sampleScheme->id]));

        $response->assertStatus(200);
        $response->assertSee('Clone Deposit Scheme');
        $response->assertSee($this->sampleScheme->name.' (Copy)');
        $response->assertSee('name="cloned_from_id" value="'.$this->sampleScheme->id.'"', false);
    }

    public function test_submitting_clone_form_successfully_clones_scheme_with_rates_and_commissions(): void
    {
        $newCode = 'CLONED'.rand(100, 999).'Y';
        $newName = 'Cloned Scheme Variant '.uniqid();

        $payload = [
            'cloned_from_id' => $this->sampleScheme->id,
            'name' => $newName,
            'code' => $newCode,
            'scheme_type' => DepositScheme::TYPE_FD,
            'description' => 'A newly cloned variant with custom tweaks.',
            'min_amount' => 5000.00,
            'max_amount' => 500000.00,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.50,
            'senior_citizen_extra_rate' => 0.50,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
            'status' => DepositScheme::STATUS_ACTIVE,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.store'), $payload);

        $cloned = DepositScheme::where('code', $newCode)->first();
        $this->assertNotNull($cloned);
        $response->assertRedirect(route('admin.deposit-schemes.show', $cloned));

        $this->assertEquals($newName, $cloned->name);
        $this->assertEquals(8.50, (float) $cloned->interest_rate);
        $this->assertEquals(4.50, (float) $cloned->total_commission_percentage);
        $this->assertEquals(25.00, (float) $cloned->promotion_target_percentage);

        // Verify multi-tier rates were cloned
        $tiers = $cloned->interestRates;
        $this->assertGreaterThanOrEqual(2, $tiers->count());
        $this->assertTrue($tiers->contains('tenure', 24));

        // Verify rank commissions were cloned
        if (AgentRank::exists()) {
            $this->assertGreaterThanOrEqual(1, $cloned->rankCommissions()->count());
        }

        // Verify commission rules were cloned with a fresh, unique code
        $clonedRules = CommissionRule::where('deposit_scheme_id', $cloned->id)->get();
        $this->assertCount(1, $clonedRules);
        $this->assertNotEquals($this->sampleScheme->commissionRules()->first()->code, $clonedRules->first()->code);

        // Verify audit log
        $log = AuditLog::where('auditable_type', DepositScheme::class)
            ->where('auditable_id', $cloned->id)
            ->where('event', 'deposit_scheme_cloned')
            ->first();
        $this->assertNotNull($log);
    }

    public function test_direct_duplicate_route_clones_scheme_immediately(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.duplicate', $this->sampleScheme));

        $duplicate = DepositScheme::where('name', 'like', $this->sampleScheme->name.' (Copy%')->latest('id')->first();
        $this->assertNotNull($duplicate);
        $response->assertRedirect(route('admin.deposit-schemes.show', $duplicate));

        $this->assertNotEquals($this->sampleScheme->id, $duplicate->id);
        $this->assertNotEquals($this->sampleScheme->code, $duplicate->code);
        $this->assertEquals($this->sampleScheme->interest_rate, $duplicate->interest_rate);
        $this->assertEquals($this->sampleScheme->min_amount, $duplicate->min_amount);

        // Verify commission rules were cloned with a fresh, unique code
        $duplicateRules = CommissionRule::where('deposit_scheme_id', $duplicate->id)->get();
        $this->assertCount(1, $duplicateRules);
        $this->assertNotEquals($this->sampleScheme->commissionRules()->first()->code, $duplicateRules->first()->code);

        // Verify audit log
        $log = AuditLog::where('auditable_type', DepositScheme::class)
            ->where('auditable_id', $duplicate->id)
            ->where('event', 'deposit_scheme_duplicated')
            ->first();
        $this->assertNotNull($log);
    }
}
