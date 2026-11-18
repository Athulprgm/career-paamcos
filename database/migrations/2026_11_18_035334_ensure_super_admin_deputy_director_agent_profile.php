<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $superAdminUsers = User::whereHas('roles', function ($q) {
            $q->where('slug', 'super_admin');
        })->orWhere('email', 'superadmin@multicred.com')->get();

        foreach ($superAdminUsers as $superAdmin) {
            $superAdmin->ensureSuperAdminAgentProfile();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Keep agent profiles safe
    }
};
