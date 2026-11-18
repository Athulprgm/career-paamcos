<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SuperAdminLeadTest extends TestCase
{
    use DatabaseTransactions;

    public function test_superadmin_can_view_clean_fresh_leads_page_with_empty_state(): void
    {
        $role = Role::where('slug', 'superadmin')->orWhere('name', 'Super Admin')->first()
            ?? Role::create(['slug' => 'superadmin', 'name' => 'Super Admin', 'status' => 'active']);
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@pristineagro.org'],
            ['name' => 'Super Administrator', 'password' => bcrypt('password'), 'role' => 'superadmin', 'status' => 'active']
        );
        $superadmin->roles()->syncWithoutDetaching([$role->id]);

        $response = $this->actingAs($superadmin)->get(route('admin.leads.index'));

        $response->assertStatus(200);
        $response->assertSee('Agent Business Leads');
        if (Lead::count() === 0) {
            $response->assertSee('No business leads found');
        } else {
            $response->assertSee('Lead Name');
        }
    }

    public function test_newly_created_lead_is_displayed_on_admin_leads_page(): void
    {
        $role = Role::where('slug', 'superadmin')->orWhere('name', 'Super Admin')->first()
            ?? Role::create(['slug' => 'superadmin', 'name' => 'Super Admin', 'status' => 'active']);
        $superadmin = User::firstOrCreate(
            ['email' => 'superadmin@pristineagro.org'],
            ['name' => 'Super Administrator', 'password' => bcrypt('password'), 'role' => 'superadmin', 'status' => 'active']
        );
        $superadmin->roles()->syncWithoutDetaching([$role->id]);

        $branch = Branch::first() ?? Branch::create([
            'code' => 'BR_TEST',
            'name' => 'Test Branch',
            'address' => 'Test Address',
            'phone' => '9999999999',
            'email' => 'testbranch@example.com',
            'is_active' => true,
        ]);

        $rank = AgentRank::first() ?? AgentRank::create([
            'name' => 'Rank 1',
            'code' => 'R1',
            'level' => 1,
            'sort_order' => 1,
            'status' => 'active',
        ]);

        $agent = Agent::first() ?? Agent::create([
            'branch_id' => $branch->id,
            'agent_id' => 'AG_TEST1',
            'rank_id' => $rank->id,
            'name' => 'Test Agent',
            'email' => 'testagent_'.uniqid().'@example.com',
            'phone' => '9888888888',
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $lead = Lead::create([
            'branch_id' => $branch->id,
            'agent_id' => $agent->id,
            'name' => 'Fresh Potential Customer',
            'mobile' => '9111122222',
            'status' => Lead::STATUS_NEW,
            'expected_amount' => 25000,
        ]);

        $response = $this->actingAs($superadmin)->get(route('admin.leads.index'));

        $response->assertStatus(200);
        $response->assertSee('Fresh Potential Customer');
        $response->assertSee('9111122222');
        $response->assertSee('NEW');
    }
}
