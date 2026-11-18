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
        Schema::create('deposit_scheme_mrp_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deposit_scheme_id')
                ->constrained('deposit_schemes')
                ->cascadeOnDelete();
            $table->boolean('is_enabled')->default(true);
            $table->decimal('contribution_amount', 15, 2)->default(100000.00);
            $table->unsignedInteger('min_tenure')->default(1);
            $table->unsignedInteger('max_tenure')->default(5);
            $table->string('tenure_unit', 20)->default('YEARS');
            $table->decimal('general_rate', 5, 2)->nullable();
            $table->decimal('senior_rate', 5, 2)->nullable();
            $table->string('calculation_method', 50)->default('STANDARD');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique('deposit_scheme_id');
        });

        Schema::create('deposit_scheme_mrp_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deposit_scheme_id')
                ->constrained('deposit_schemes')
                ->cascadeOnDelete();
            $table->foreignId('mrp_configuration_id')
                ->constrained('deposit_scheme_mrp_configurations')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('general_rate', 5, 2);
            $table->decimal('general_monthly_return', 15, 2);
            $table->decimal('senior_rate', 5, 2);
            $table->decimal('senior_monthly_return', 15, 2);
            $table->timestamps();

            $table->unique(['deposit_scheme_id', 'year']);
            $table->index(['mrp_configuration_id', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deposit_scheme_mrp_rates');
        Schema::dropIfExists('deposit_scheme_mrp_configurations');
    }
};
