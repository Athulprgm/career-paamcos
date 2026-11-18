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
        if (! Schema::hasTable('deposit_scheme_interest_rates')) {
            Schema::create('deposit_scheme_interest_rates', function (Blueprint $table) {
                $table->id();
                $table->foreignId('deposit_scheme_id')->constrained('deposit_schemes')->cascadeOnDelete();
                $table->unsignedInteger('tenure');
                $table->string('tenure_unit', 20)->default('YEARS');
                $table->decimal('interest_rate', 5, 2);
                $table->decimal('senior_citizen_extra_rate', 5, 2)->default(0.00)->nullable();
                $table->decimal('min_amount', 15, 2)->nullable();
                $table->decimal('max_amount', 15, 2)->nullable();
                $table->string('status', 20)->default('ACTIVE');
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['deposit_scheme_id', 'status']);
                $table->unique(['deposit_scheme_id', 'tenure', 'tenure_unit', 'deleted_at'], 'ds_interest_rates_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposit_scheme_interest_rates');
    }
};
