<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('promotion_rules')) {
            Schema::create('promotion_rules', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->foreignId('from_rank_id')->constrained('ranks')->cascadeOnDelete();
                $table->foreignId('to_rank_id')->constrained('ranks')->cascadeOnDelete();
                $table->decimal('min_individual_deposit', 15, 2)->default(0.00);
                $table->decimal('min_team_deposit', 15, 2)->default(0.00);
                $table->unsignedInteger('min_qualifying_direct_facilitators')->default(0);
                $table->foreignId('qualifying_rank_id')->nullable()->constrained('ranks')->nullOnDelete();
                $table->string('calculation_period', 50)->default('all_time');
                $table->json('qualifying_deposit_statuses')->nullable();
                $table->string('required_agent_status', 30)->default('active');
                $table->date('effective_from')->nullable();
                $table->date('effective_to')->nullable();
                $table->boolean('requires_approval')->default(true);
                $table->string('authorized_approval_role', 50)->default('super_admin');
                $table->text('description')->nullable();
                $table->string('status', 20)->default('active');
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();
            });
        } else {
            Schema::table('promotion_rules', function (Blueprint $table) {
                if (! Schema::hasColumn('promotion_rules', 'name')) {
                    $table->string('name')->default('Promotion Rule')->after('id');
                }
                if (! Schema::hasColumn('promotion_rules', 'min_individual_deposit')) {
                    $table->decimal('min_individual_deposit', 15, 2)->default(0.00)->after('to_rank_id');
                }
                if (! Schema::hasColumn('promotion_rules', 'min_team_deposit')) {
                    $table->decimal('min_team_deposit', 15, 2)->default(0.00)->after('min_individual_deposit');
                }
                if (! Schema::hasColumn('promotion_rules', 'min_qualifying_direct_facilitators')) {
                    $table->unsignedInteger('min_qualifying_direct_facilitators')->default(0)->after('min_team_deposit');
                }
                if (! Schema::hasColumn('promotion_rules', 'qualifying_rank_id')) {
                    $table->foreignId('qualifying_rank_id')->nullable()->after('min_qualifying_direct_facilitators')->constrained('ranks')->nullOnDelete();
                }
                if (! Schema::hasColumn('promotion_rules', 'calculation_period')) {
                    $table->string('calculation_period', 50)->default('all_time')->after('qualifying_rank_id');
                }
                if (! Schema::hasColumn('promotion_rules', 'qualifying_deposit_statuses')) {
                    $table->json('qualifying_deposit_statuses')->nullable()->after('calculation_period');
                }
                if (! Schema::hasColumn('promotion_rules', 'required_agent_status')) {
                    $table->string('required_agent_status', 30)->default('active')->after('qualifying_deposit_statuses');
                }
                if (! Schema::hasColumn('promotion_rules', 'effective_from')) {
                    $table->date('effective_from')->nullable()->after('required_agent_status');
                }
                if (! Schema::hasColumn('promotion_rules', 'effective_to')) {
                    $table->date('effective_to')->nullable()->after('effective_from');
                }
                if (! Schema::hasColumn('promotion_rules', 'requires_approval')) {
                    $table->boolean('requires_approval')->default(true)->after('effective_to');
                }
                if (! Schema::hasColumn('promotion_rules', 'authorized_approval_role')) {
                    $table->string('authorized_approval_role', 50)->default('super_admin')->after('requires_approval');
                }
                if (! Schema::hasColumn('promotion_rules', 'description')) {
                    $table->text('description')->nullable()->after('authorized_approval_role');
                }
                if (! Schema::hasColumn('promotion_rules', 'created_by')) {
                    $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('promotion_rules', 'updated_by')) {
                    $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('promotion_rules', 'deleted_at')) {
                    $table->softDeletes()->after('updated_at');
                }
            });
        }

        Schema::table('promotion_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('promotion_requests', 'promotion_rule_id')) {
                $table->foreignId('promotion_rule_id')->nullable()->after('proposed_level_id')->constrained('promotion_rules')->nullOnDelete();
            }
            if (! Schema::hasColumn('promotion_requests', 'rule_snapshot')) {
                $table->json('rule_snapshot')->nullable()->after('eligibility_result');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotion_requests', function (Blueprint $table) {
            if (Schema::hasColumn('promotion_requests', 'promotion_rule_id')) {
                $table->dropForeign(['promotion_rule_id']);
                $table->dropColumn('promotion_rule_id');
            }
            if (Schema::hasColumn('promotion_requests', 'rule_snapshot')) {
                $table->dropColumn('rule_snapshot');
            }
        });

        Schema::table('promotion_rules', function (Blueprint $table) {
            $columns = [
                'name',
                'min_individual_deposit',
                'min_team_deposit',
                'min_qualifying_direct_facilitators',
                'qualifying_rank_id',
                'calculation_period',
                'qualifying_deposit_statuses',
                'required_agent_status',
                'effective_from',
                'effective_to',
                'requires_approval',
                'authorized_approval_role',
                'description',
                'created_by',
                'updated_by',
                'deleted_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('promotion_rules', $column)) {
                    if (in_array($column, ['qualifying_rank_id', 'created_by', 'updated_by'])) {
                        $table->dropForeign([$column]);
                    }
                    $table->dropColumn($column);
                }
            }
        });
    }
};
