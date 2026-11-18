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
        // 1. Update scheme_types table
        if (Schema::hasTable('scheme_types')) {
            Schema::table('scheme_types', function (Blueprint $table) {
                if (! Schema::hasColumn('scheme_types', 'calculation_type')) {
                    $table->string('calculation_type', 50)->default('LUMP_SUM')->after('description');
                }
                if (! Schema::hasColumn('scheme_types', 'default_frequency')) {
                    $table->string('default_frequency', 50)->default('ONE_TIME')->after('calculation_type');
                }
            });

            // Normalize existing default scheme types
            DB::table('scheme_types')->where('code', 'FD')->update([
                'calculation_type' => 'LUMP_SUM',
                'default_frequency' => 'ONE_TIME',
            ]);
            DB::table('scheme_types')->where('code', 'RD')->update([
                'calculation_type' => 'RECURRING',
                'default_frequency' => 'MONTHLY',
            ]);
            DB::table('scheme_types')->where('code', 'DD')->update([
                'calculation_type' => 'DAILY',
                'default_frequency' => 'DAILY',
            ]);
            DB::table('scheme_types')->where('code', 'MIS')->update([
                'calculation_type' => 'LUMP_SUM',
                'default_frequency' => 'MONTHLY',
            ]);
            DB::table('scheme_types')->where('code', 'OTHER')->update([
                'calculation_type' => 'LUMP_SUM',
                'default_frequency' => 'ONE_TIME',
            ]);
        }

        // 2. Add scheme_type_id to deposit_schemes
        if (Schema::hasTable('deposit_schemes')) {
            Schema::table('deposit_schemes', function (Blueprint $table) {
                if (! Schema::hasColumn('deposit_schemes', 'scheme_type_id')) {
                    $table->foreignId('scheme_type_id')->nullable()->after('id')->constrained('scheme_types')->nullOnDelete();
                }
            });

            // Map existing scheme_type codes to scheme_type_id
            DB::statement(
                'UPDATE deposit_schemes ds JOIN scheme_types st ON UPPER(ds.scheme_type) = UPPER(st.code) SET ds.scheme_type_id = st.id WHERE ds.scheme_type_id IS NULL'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('deposit_schemes') && Schema::hasColumn('deposit_schemes', 'scheme_type_id')) {
            Schema::table('deposit_schemes', function (Blueprint $table) {
                $table->dropForeign(['scheme_type_id']);
                $table->dropColumn('scheme_type_id');
            });
        }

        if (Schema::hasTable('scheme_types')) {
            Schema::table('scheme_types', function (Blueprint $table) {
                $cols = [];
                if (Schema::hasColumn('scheme_types', 'default_frequency')) {
                    $cols[] = 'default_frequency';
                }
                if (Schema::hasColumn('scheme_types', 'calculation_type')) {
                    $cols[] = 'calculation_type';
                }
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
