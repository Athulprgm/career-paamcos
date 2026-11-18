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
        // ─── Staff table updates ──────────────────────────────────────────────
        Schema::table('staff', function (Blueprint $table) {
            if (! Schema::hasColumn('staff', 'employee_id')) {
                $table->string('employee_id', 50)->nullable()->unique()->after('staff_code');
            }

            if (! Schema::hasColumn('staff', 'position_id')) {
                $table->foreignId('position_id')
                    ->nullable()
                    ->after('branch_id')
                    ->constrained('positions')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('staff', 'reporting_manager_id')) {
                $table->foreignId('reporting_manager_id')
                    ->nullable()
                    ->after('position_id')
                    ->constrained('staff')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('staff', 'joining_date')) {
                $table->date('joining_date')->nullable()->after('reporting_manager_id');
            }
        });

        // Backfill staff employee_id from staff_code if available
        if (Schema::hasColumn('staff', 'employee_id') && Schema::hasColumn('staff', 'staff_code')) {
            DB::table('staff')
                ->whereNull('employee_id')
                ->whereNotNull('staff_code')
                ->update(['employee_id' => DB::raw('staff_code')]);
        }

        // ─── Agents table updates ─────────────────────────────────────────────
        Schema::table('agents', function (Blueprint $table) {
            if (! Schema::hasColumn('agents', 'agent_id')) {
                $table->string('agent_id', 50)->nullable()->unique()->after('member_code');
            }

            if (! Schema::hasColumn('agents', 'reporting_staff_id')) {
                $table->foreignId('reporting_staff_id')
                    ->nullable()
                    ->after('rank_id')
                    ->constrained('staff')
                    ->nullOnDelete();
            }
        });

        // Backfill agent_id from member_code if available
        if (Schema::hasColumn('agents', 'agent_id') && Schema::hasColumn('agents', 'member_code')) {
            DB::table('agents')
                ->whereNull('agent_id')
                ->whereNotNull('member_code')
                ->update(['agent_id' => DB::raw('member_code')]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            if (Schema::hasColumn('agents', 'reporting_staff_id')) {
                $table->dropForeign(['reporting_staff_id']);
                $table->dropColumn('reporting_staff_id');
            }
            if (Schema::hasColumn('agents', 'agent_id')) {
                $table->dropColumn('agent_id');
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            if (Schema::hasColumn('staff', 'reporting_manager_id')) {
                $table->dropForeign(['reporting_manager_id']);
                $table->dropColumn('reporting_manager_id');
            }
            if (Schema::hasColumn('staff', 'position_id')) {
                $table->dropForeign(['position_id']);
                $table->dropColumn('position_id');
            }
            if (Schema::hasColumn('staff', 'joining_date')) {
                $table->dropColumn('joining_date');
            }
            if (Schema::hasColumn('staff', 'employee_id')) {
                $table->dropColumn('employee_id');
            }
        });
    }
};
