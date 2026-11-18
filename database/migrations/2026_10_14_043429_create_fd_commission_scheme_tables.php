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
        // 1. Add total_commission_percentage to deposit_schemes if not present
        Schema::table('deposit_schemes', function (Blueprint $table) {
            if (! Schema::hasColumn('deposit_schemes', 'total_commission_percentage')) {
                $table->decimal('total_commission_percentage', 8, 2)->default(0.00)->after('interest_rate');
            }
        });

        // 2. Normalized table for Scheme-specific rank commission rules
        if (! Schema::hasTable('deposit_scheme_rank_commissions')) {
            Schema::create('deposit_scheme_rank_commissions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deposit_scheme_id')->constrained('deposit_schemes')->cascadeOnDelete();
                $table->foreignId('rank_id')->constrained('ranks')->cascadeOnDelete();
                $table->decimal('commission_percentage', 8, 2)->default(0.00);
                $table->boolean('is_eligible')->default(true);
                $table->timestamps();

                $table->unique(['deposit_scheme_id', 'rank_id'], 'uk_scheme_rank_comm');
                $table->index(['deposit_scheme_id', 'is_eligible'], 'idx_scheme_rank_eligible');
            });
        }

        // 3. Normalized audit history table for commission configuration changes
        if (! Schema::hasTable('deposit_scheme_commission_histories')) {
            Schema::create('deposit_scheme_commission_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deposit_scheme_id')->constrained('deposit_schemes')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 50); // created, updated, status_toggled, deleted
                $table->decimal('total_commission_percentage', 8, 2)->default(0.00);
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->json('rank_rules_snapshot')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['deposit_scheme_id', 'created_at'], 'idx_scheme_hist_date');
                $table->index('user_id', 'idx_scheme_hist_user');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposit_scheme_commission_histories');
        Schema::dropIfExists('deposit_scheme_rank_commissions');

        Schema::table('deposit_schemes', function (Blueprint $table) {
            if (Schema::hasColumn('deposit_schemes', 'total_commission_percentage')) {
                $table->dropColumn('total_commission_percentage');
            }
        });
    }
};
