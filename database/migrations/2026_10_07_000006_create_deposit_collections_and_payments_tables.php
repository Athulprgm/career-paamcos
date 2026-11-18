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
        // ─── 1. Deposit Collections Table ──────────────────────────────────────
        Schema::create('deposit_collections', function (Blueprint $table) {
            $table->id();
            $table->string('collection_number', 30)->unique();
            $table->foreignId('deposit_id')->constrained('deposits')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->unsignedInteger('installment_number')->default(1);
            $table->date('due_date')->index();
            $table->decimal('due_amount', 14, 2);
            $table->decimal('paid_amount', 14, 2)->default(0.00);
            $table->date('collection_date')->nullable()->index();
            $table->string('payment_method', 50)->nullable();
            $table->string('reference_number', 100)->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('DUE')->index(); // DUE, PARTIAL, PAID, OVERDUE, CANCELLED
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // Composite indexes for fast query filtering
            $table->index(['branch_id', 'status'], 'idx_dep_coll_branch_status');
            $table->index(['agent_id', 'status'], 'idx_dep_coll_agent_status');
            $table->index(['customer_id', 'status'], 'idx_dep_coll_customer_status');
            $table->index(['due_date', 'status'], 'idx_dep_coll_due_status');
            $table->index(['deposit_id', 'installment_number'], 'idx_dep_coll_deposit_inst');
        });

        // ─── 2. Collection Payments (Transactions Audit Trail) ─────────────────
        Schema::create('collection_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 30)->unique();
            $table->foreignId('deposit_collection_id')->constrained('deposit_collections')->cascadeOnDelete();
            $table->foreignId('deposit_id')->constrained('deposits')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('agent_id')->nullable()->constrained('agents')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->date('payment_date')->index();
            $table->string('payment_method', 50);
            $table->string('reference_number', 100)->nullable();
            $table->foreignId('collected_by')->constrained('users')->restrictOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indexes for fast history reporting
            $table->index(['branch_id', 'payment_date'], 'idx_coll_pay_branch_date');
            $table->index(['agent_id', 'payment_date'], 'idx_coll_pay_agent_date');
            $table->index(['deposit_id', 'payment_date'], 'idx_coll_pay_deposit_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_payments');
        Schema::dropIfExists('deposit_collections');
    }
};
