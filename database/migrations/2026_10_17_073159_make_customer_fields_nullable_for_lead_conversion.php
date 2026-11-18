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
        Schema::table('customers', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->change();
            $table->text('address')->nullable()->change();
            $table->string('pin_code', 10)->nullable()->change();
            $table->string('mobile', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable(false)->change();
            $table->text('address')->nullable(false)->change();
            $table->string('pin_code', 10)->nullable(false)->change();
            $table->string('mobile', 20)->nullable(false)->change();
        });
    }
};
