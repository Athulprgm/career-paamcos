<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Merge duplicate active customer rows sharing the same branch, normalized name, and mobile.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $customers = DB::table('customers')
                ->whereNull('deleted_at')
                ->orderByDesc('id')
                ->get();

            $activeDepositCustomerIds = DB::table('deposits')
                ->whereNull('deleted_at')
                ->pluck('customer_id')
                ->filter()
                ->flip();

            $leadConvertedCustomerIds = DB::table('leads')
                ->whereNull('deleted_at')
                ->pluck('converted_customer_id')
                ->filter()
                ->flip();

            $groups = $customers
                ->filter(fn (object $c): bool => trim((string) ($c->mobile ?? '')) !== '')
                ->groupBy(function (object $c): string {
                    return implode('|', [
                        (string) ($c->branch_id ?? '0'),
                        mb_strtolower(trim((string) $c->name)),
                        trim((string) $c->mobile),
                    ]);
                });

            $now = now();

            foreach ($groups as $group) {
                if ($group->count() <= 1) {
                    continue;
                }

                $sorted = $group->sortByDesc(function (object $c) use ($activeDepositCustomerIds, $leadConvertedCustomerIds): array {
                    return [
                        $activeDepositCustomerIds->has($c->id) ? 1 : 0,
                        $leadConvertedCustomerIds->has($c->id) ? 1 : 0,
                        (int) $c->id,
                    ];
                })->values();

                $canonical = $sorted->first();
                $duplicates = $sorted->slice(1);

                foreach ($duplicates as $duplicate) {
                    DB::table('deposits')
                        ->where('customer_id', $duplicate->id)
                        ->update([
                            'customer_id' => $canonical->id,
                            'updated_at' => $now,
                        ]);

                    DB::table('deposits')
                        ->where('customer_id', $canonical->id)
                        ->where('member_code', $duplicate->customer_number)
                        ->whereNull('deleted_at')
                        ->update([
                            'member_code' => $canonical->customer_number,
                            'updated_at' => $now,
                        ]);

                    DB::table('deposit_collections')
                        ->where('customer_id', $duplicate->id)
                        ->update([
                            'customer_id' => $canonical->id,
                            'updated_at' => $now,
                        ]);

                    DB::table('collection_payments')
                        ->where('customer_id', $duplicate->id)
                        ->update([
                            'customer_id' => $canonical->id,
                            'updated_at' => $now,
                        ]);

                    DB::table('commission_ledgers')
                        ->where('customer_id', $duplicate->id)
                        ->update([
                            'customer_id' => $canonical->id,
                            'updated_at' => $now,
                        ]);

                    DB::table('leads')
                        ->where('converted_customer_id', $duplicate->id)
                        ->update([
                            'converted_customer_id' => $canonical->id,
                            'updated_at' => $now,
                        ]);

                    $canonicalHasKyc = DB::table('customer_kyc')
                        ->where('customer_id', $canonical->id)
                        ->exists();

                    if (! $canonicalHasKyc) {
                        DB::table('customer_kyc')
                            ->where('customer_id', $duplicate->id)
                            ->update([
                                'customer_id' => $canonical->id,
                                'updated_at' => $now,
                            ]);
                    } else {
                        DB::table('customer_kyc')
                            ->where('customer_id', $duplicate->id)
                            ->delete();
                    }

                    DB::table('customers')
                        ->where('id', $duplicate->id)
                        ->update([
                            'deleted_at' => $now,
                            'updated_at' => $now,
                        ]);
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive data cleanup migration; reversal not required.
    }
};
