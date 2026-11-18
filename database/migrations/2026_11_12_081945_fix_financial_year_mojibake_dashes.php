<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $mojibake = ["\xC3\xA2\xE2\x82\xAC\xE2\x80\x9C", 'â€“', '–'];

        DB::table('financial_years')->get(['id', 'name'])->each(function (object $row) use ($mojibake): void {
            $cleaned = str_replace($mojibake, '-', (string) $row->name);
            if ($cleaned !== $row->name) {
                DB::table('financial_years')->where('id', $row->id)->update(['name' => $cleaned]);
            }
        });

        DB::table('agent_rank_targets')->get(['id', 'description'])->each(function (object $row) use ($mojibake): void {
            if ($row->description === null) {
                return;
            }
            $cleaned = str_replace($mojibake, '-', (string) $row->description);
            if ($cleaned !== $row->description) {
                DB::table('agent_rank_targets')->where('id', $row->id)->update(['description' => $cleaned]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Normalization is one-way safe cleanup.
    }
};
