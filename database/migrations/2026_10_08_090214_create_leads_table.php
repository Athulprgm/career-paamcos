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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('name');
            $table->string('mobile', 20);
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('interested_scheme_id')->nullable()->constrained('deposit_schemes')->nullOnDelete();
            $table->decimal('expected_amount', 15, 2)->nullable();
            $table->string('status', 30)->default('NEW');
            $table->date('follow_up_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('converted_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['agent_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index('follow_up_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
