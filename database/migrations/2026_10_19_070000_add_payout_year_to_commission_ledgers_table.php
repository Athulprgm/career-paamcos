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
        Schema::table('commission_ledgers', function (Blueprint $table) {
            if (! Schema::hasColumn('commission_ledgers', 'payout_year')) {
                $table->unsignedTinyInteger('payout_year')
                    ->nullable()
                    ->default(1)
                    ->after('rank_id')
                    ->comment('Deposit tenure year (1 to maturity year) this commission corresponds to');

                $table->index(['deposit_id', 'agent_id', 'payout_year'], 'idx_comm_deposit_agent_year');
            }
        });

        // Set existing deposit commissions to payout_year = 1 if null
        DB::statement('UPDATE commission_ledgers SET payout_year = 1 WHERE deposit_id IS NOT NULL AND payout_year IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_ledgers', function (Blueprint $table) {
            if (Schema::hasColumn('commission_ledgers', 'payout_year')) {
                $table->dropIndex('idx_comm_deposit_agent_year');
                $table->dropColumn('payout_year');
            }
        });
    }
};
