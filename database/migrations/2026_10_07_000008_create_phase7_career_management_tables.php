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
        // 1. Add configurable promotion criteria columns to ranks table if not present
        Schema::table('ranks', function (Blueprint $table) {
            if (! Schema::hasColumn('ranks', 'min_service_days')) {
                $table->unsignedInteger('min_service_days')->default(0)->after('description');
            }
            if (! Schema::hasColumn('ranks', 'min_active_customers')) {
                $table->unsignedInteger('min_active_customers')->default(0)->after('min_service_days');
            }
            if (! Schema::hasColumn('ranks', 'min_deposit_count')) {
                $table->unsignedInteger('min_deposit_count')->default(0)->after('min_active_customers');
            }
            if (! Schema::hasColumn('ranks', 'min_deposit_amount')) {
                $table->decimal('min_deposit_amount', 15, 2)->default(0.00)->after('min_deposit_count');
            }
            if (! Schema::hasColumn('ranks', 'min_collection_amount')) {
                $table->decimal('min_collection_amount', 15, 2)->default(0.00)->after('min_deposit_amount');
            }
            if (! Schema::hasColumn('ranks', 'min_commission_amount')) {
                $table->decimal('min_commission_amount', 15, 2)->default(0.00)->after('min_collection_amount');
            }
            if (! Schema::hasColumn('ranks', 'min_performance_score')) {
                $table->decimal('min_performance_score', 5, 2)->default(0.00)->after('min_commission_amount');
            }
        });

        // 2. Promotion Requests table
        if (! Schema::hasTable('promotion_requests')) {
            Schema::create('promotion_requests', function (Blueprint $table) {
                $table->id();
                $table->string('request_number', 30)->unique();
                $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
                $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
                $table->foreignId('current_level_id')->constrained('ranks');
                $table->foreignId('proposed_level_id')->constrained('ranks');
                $table->string('performance_period', 20)->nullable()->index();
                $table->json('eligibility_result')->nullable();
                $table->string('status', 30)->default('PENDING_REVIEW')->index();
                $table->text('remarks')->nullable();
                $table->foreignId('requested_by')->constrained('users');
                $table->date('request_date');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_reason')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('promoted_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 3. Career Histories table (Permanent audit record)
        if (! Schema::hasTable('career_histories')) {
            Schema::create('career_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agent_id')->constrained('agents')->onDelete('cascade');
                $table->foreignId('promotion_request_id')->nullable()->constrained('promotion_requests')->nullOnDelete();
                $table->foreignId('previous_level_id')->nullable()->constrained('ranks')->nullOnDelete();
                $table->foreignId('new_level_id')->constrained('ranks');
                $table->string('performance_period', 20)->nullable();
                $table->date('promotion_date');
                $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 30)->default('PROMOTION'); // PROMOTION, DEMOTION, INITIAL_PLACEMENT
                $table->text('remarks')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('career_histories');
        Schema::dropIfExists('promotion_requests');

        Schema::table('ranks', function (Blueprint $table) {
            $columns = [
                'min_service_days',
                'min_active_customers',
                'min_deposit_count',
                'min_deposit_amount',
                'min_collection_amount',
                'min_commission_amount',
                'min_performance_score',
            ];
            foreach ($columns as $column) {
                if (Schema::hasColumn('ranks', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
