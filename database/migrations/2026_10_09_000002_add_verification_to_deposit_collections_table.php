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
        Schema::table('deposit_collections', function (Blueprint $table) {
            $table->foreignId('verified_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable()->after('verified_by');
            $table->string('verification_status', 30)->default('UNVERIFIED')->after('verified_at')->index();
            $table->text('verification_remarks')->nullable()->after('verification_status');
            $table->boolean('is_flagged')->default(false)->after('verification_remarks');
            $table->text('flag_reason')->nullable()->after('is_flagged');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposit_collections', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn([
                'verified_by',
                'verified_at',
                'verification_status',
                'verification_remarks',
                'is_flagged',
                'flag_reason',
            ]);
        });
    }
};
