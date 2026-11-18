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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_number', 30)->unique();
            $table->string('name');
            $table->string('customer_type', 20)->default('individual'); // individual, business
            $table->string('email')->nullable();
            $table->string('mobile', 20);
            $table->date('date_of_birth');
            $table->text('address');
            $table->string('pin_code', 10);
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('agent_id')->constrained('agents')->cascadeOnDelete();
            $table->string('status', 20)->default('PENDING')->index(); // PENDING, ACTIVE, REJECTED, INACTIVE
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['branch_id', 'status']);
            $table->index(['agent_id', 'status']);
        });

        Schema::create('customer_kyc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->string('id_type', 50)->nullable(); // Aadhaar, PAN, Passport, Driving License, Voter ID, Business Registration
            $table->string('id_number', 100)->nullable();
            $table->string('id_document_path', 500)->nullable();
            $table->string('address_proof_path', 500)->nullable();
            $table->string('status', 20)->default('PENDING')->index(); // PENDING, UNDER_REVIEW, APPROVED, REJECTED
            $table->text('rejection_reason')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_kyc');
        Schema::dropIfExists('customers');
    }
};
