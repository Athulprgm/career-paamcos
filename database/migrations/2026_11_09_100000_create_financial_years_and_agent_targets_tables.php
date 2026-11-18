<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Financial Years
        Schema::create('financial_years', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50); // e.g. "FY 2026-27"
            $table->string('code', 30)->unique(); // e.g. "FY2026-27" or "2026-2027"
            $table->date('start_date'); // 2026-04-01
            $table->date('end_date'); // 2027-03-31
            $table->boolean('is_current')->default(false)->index();
            $table->string('status', 20)->default('active')->index(); // 'active', 'closed', 'upcoming', 'inactive'
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });

        // 2. Rank Target Configurations per Financial Year
        Schema::create('agent_rank_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_year_id')->constrained('financial_years')->cascadeOnDelete();
            $table->foreignId('rank_id')->constrained('ranks')->cascadeOnDelete();
            $table->decimal('total_deposit_target', 15, 2)->default(0.00);
            $table->decimal('fd_percentage', 5, 2)->default(90.00);
            $table->decimal('rd_percentage', 5, 2)->default(10.00);
            $table->decimal('fd_target_amount', 15, 2)->default(0.00);
            $table->decimal('rd_target_amount', 15, 2)->default(0.00);
            $table->unsignedInteger('recruitment_target')->default(0);
            $table->string('status', 20)->default('active')->index();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['financial_year_id', 'rank_id'], 'uk_fy_rank_target');
            $table->index(['rank_id', 'status']);
        });

        // 3. Individual Agent Target Assignments & History Tracking
        Schema::create('agent_target_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->foreignId('financial_year_id')->constrained('financial_years')->cascadeOnDelete();
            $table->foreignId('rank_id')->constrained('ranks')->cascadeOnDelete();
            $table->foreignId('target_configuration_id')->nullable()->constrained('agent_rank_targets')->nullOnDelete();
            $table->decimal('total_deposit_target', 15, 2)->default(0.00);
            $table->decimal('fd_percentage', 5, 2)->default(90.00);
            $table->decimal('rd_percentage', 5, 2)->default(10.00);
            $table->decimal('fd_target_amount', 15, 2)->default(0.00);
            $table->decimal('rd_target_amount', 15, 2)->default(0.00);
            $table->unsignedInteger('recruitment_target')->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->text('revision_reason')->nullable();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();

            $table->index(['agent_id', 'financial_year_id'], 'idx_agent_fy_target');
            $table->index(['effective_from', 'effective_to'], 'idx_agent_target_effective');
        });

        // Seed initial Financial Years and default rank configurations
        $now = now();
        $fy26Id = DB::table('financial_years')->insertGetId([
            'name' => 'FY 2026–27',
            'code' => 'FY2026-27',
            'start_date' => '2026-04-01',
            'end_date' => '2027-03-31',
            'is_current' => true,
            'status' => 'active',
            'description' => 'Current operating Financial Year for Pristine Agro Cooperative Society.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('financial_years')->insert([
            'name' => 'FY 2025–26',
            'code' => 'FY2025-26',
            'start_date' => '2025-04-01',
            'end_date' => '2026-03-31',
            'is_current' => false,
            'status' => 'closed',
            'description' => 'Previous completed Financial Year.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Default targets by rank for FY 2026-27
        $ranks = DB::table('ranks')->orderBy('level', 'asc')->get();
        $rankTargetDefaults = [
            1 => ['total' => 1000000.00, 'recruits' => 5],
            2 => ['total' => 700000.00,  'recruits' => 4],
            3 => ['total' => 500000.00,  'recruits' => 3],
            4 => ['total' => 300000.00,  'recruits' => 2],
            5 => ['total' => 1500000.00, 'recruits' => 5],
            6 => ['total' => 2000000.00, 'recruits' => 6],
            7 => ['total' => 3000000.00, 'recruits' => 7],
            8 => ['total' => 5000000.00, 'recruits' => 8],
            9 => ['total' => 7500000.00, 'recruits' => 10],
            10 => ['total' => 10000000.00, 'recruits' => 12],
            11 => ['total' => 15000000.00, 'recruits' => 15],
            12 => ['total' => 20000000.00, 'recruits' => 20],
        ];

        foreach ($ranks as $rank) {
            $level = (int) ($rank->level ?? $rank->sort_order ?? 1);
            $config = $rankTargetDefaults[$level] ?? ['total' => 500000.00, 'recruits' => 3];
            $total = (float) $config['total'];
            $fdAmount = round($total * 0.90, 2);
            $rdAmount = round($total * 0.10, 2);

            DB::table('agent_rank_targets')->insert([
                'financial_year_id' => $fy26Id,
                'rank_id' => $rank->id,
                'total_deposit_target' => $total,
                'fd_percentage' => 90.00,
                'rd_percentage' => 10.00,
                'fd_target_amount' => $fdAmount,
                'rd_target_amount' => $rdAmount,
                'recruitment_target' => $config['recruits'],
                'status' => 'active',
                'description' => "Standard FY 2026–27 Target for Rank {$level} ({$rank->name})",
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_target_assignments');
        Schema::dropIfExists('agent_rank_targets');
        Schema::dropIfExists('financial_years');
    }
};
