<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Commission;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\DepositSchemeRankCommission;
use App\Services\CommissionService;
use App\Services\FdCommissionSchemeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncFdCommissionSchemesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'commission:sync-fd-schemes
                            {--fix-deposits : Also recalculate and synchronize existing active deposit commissions}
                            {--force : Run without interactive confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize FD 1 Year through 6 Year deposit schemes from the master FD 7 Year scheme commission schedule.';

    public function handle(
        FdCommissionSchemeService $schemeService,
        CommissionService $commissionService
    ): int {
        $this->info('Starting FD Deposit Schemes Commission Synchronization...');

        // 1. Locate Master Golden Scheme: FD 7 Year (FD7Y)
        $masterScheme = DepositScheme::where('code', 'FD7Y')
            ->orWhere(function ($q) {
                $q->where('scheme_type', 'FD')
                    ->where('tenure', 7)
                    ->where('tenure_unit', 'YEARS');
            })
            ->first();

        if (! $masterScheme) {
            $this->error('Master FD 7 Year deposit scheme (FD7Y) not found.');

            return self::FAILURE;
        }

        $this->info("Found Master Scheme: [{$masterScheme->name}] (Code: {$masterScheme->code}, ID: {$masterScheme->id})");

        $masterRankCommissions = DepositSchemeRankCommission::where('deposit_scheme_id', $masterScheme->id)
            ->orderBy('payout_year')
            ->orderBy('rank_id')
            ->get()
            ->groupBy('payout_year');

        if ($masterRankCommissions->isEmpty()) {
            $this->error('Master FD 7 Year scheme has no rank commission configuration.');

            return self::FAILURE;
        }

        $availableMasterYears = $masterRankCommissions->keys()->map(fn ($y) => (int) $y)->all();
        $this->info('Master Scheme configured payout years: '.implode(', ', $availableMasterYears));

        // 2. Identify Target Schemes: FD 1 Year through FD 6 Year
        $targetCodes = [
            1 => 'FD1Y',
            2 => 'FD2Y',
            3 => 'FD3Y',
            4 => 'FD4Y',
            5 => 'FD5Y',
            6 => 'FD6Y',
        ];

        DB::beginTransaction();

        try {
            foreach ($targetCodes as $tenureYears => $code) {
                $scheme = DepositScheme::where('code', $code)
                    ->orWhere(function ($q) use ($tenureYears) {
                        $q->where('scheme_type', 'FD')
                            ->where('tenure', $tenureYears)
                            ->where('tenure_unit', 'YEARS');
                    })
                    ->first();

                if (! $scheme) {
                    $this->warn("Scheme for {$tenureYears} Year(s) ({$code}) not found, skipping.");

                    continue;
                }

                $this->line("Synchronizing [{$scheme->name}] ({$scheme->code}, ID: {$scheme->id}, Tenure: {$tenureYears} Years)...");

                $syncedSnapshot = [];

                for ($year = 1; $year <= $tenureYears; $year++) {
                    $masterYearRanks = $masterRankCommissions->get($year);
                    if (! $masterYearRanks) {
                        $this->warn("Master scheme has no rules for Year {$year}!");

                        continue;
                    }

                    $masterRankIds = [];

                    foreach ($masterYearRanks as $masterRow) {
                        $masterRankIds[] = $masterRow->rank_id;
                        $rankComm = DepositSchemeRankCommission::updateOrCreate(
                            [
                                'deposit_scheme_id' => $scheme->id,
                                'rank_id' => $masterRow->rank_id,
                                'payout_year' => $year,
                            ],
                            [
                                'commission_percentage' => $masterRow->commission_percentage,
                                'is_eligible' => (bool) $masterRow->is_eligible,
                            ]
                        );

                        $syncedSnapshot[] = [
                            'rank_id' => $masterRow->rank_id,
                            'rank_name' => $masterRow->rank?->name,
                            'rank_code' => $masterRow->rank?->code,
                            'sort_order' => $masterRow->rank?->sort_order,
                            'is_eligible' => (bool) $masterRow->is_eligible,
                            'commission_percentage' => (float) $masterRow->commission_percentage,
                            'payout_year' => $year,
                        ];
                    }

                    // Zero out any ranks for this year that are not in the master scheme
                    DepositSchemeRankCommission::where('deposit_scheme_id', $scheme->id)
                        ->where('payout_year', $year)
                        ->whereNotIn('rank_id', $masterRankIds)
                        ->update([
                            'commission_percentage' => 0.00,
                            'is_eligible' => false,
                        ]);
                }

                // Delete any rank commissions for years beyond this scheme's tenure
                DepositSchemeRankCommission::where('deposit_scheme_id', $scheme->id)
                    ->where('payout_year', '>', $tenureYears)
                    ->delete();

                // Recalculate scheme total commission percentage
                $newTotalCommission = (float) DepositSchemeRankCommission::where('deposit_scheme_id', $scheme->id)
                    ->where('is_eligible', true)
                    ->sum('commission_percentage');

                $oldTotal = (float) $scheme->total_commission_percentage;
                $scheme->update([
                    'total_commission_percentage' => round($newTotalCommission, 2),
                ]);

                // Sync CommissionRules table
                $schemeService->syncCommissionRules($scheme, $syncedSnapshot);

                $this->info(" -> Synced {$scheme->code}: {$tenureYears} year(s) configured. Total commission updated: {$oldTotal}% => {$newTotalCommission}%");

                AuditLog::record(
                    event: 'fd_scheme_synced_from_master',
                    description: "Synchronized FD Scheme [{$scheme->name}] ({$scheme->code}) commission schedule from master FD 7 Year scheme. Total commission updated to {$newTotalCommission}%.",
                    auditable: $scheme,
                    oldValues: ['total_commission_percentage' => $oldTotal],
                    newValues: ['total_commission_percentage' => $newTotalCommission]
                );
            }

            // Also make sure master scheme rules are completely in sync
            $schemeService->syncCommissionRules($masterScheme);

            // 3. Fix & Synchronize existing active deposit commissions if requested or default
            if ($this->option('fix-deposits') || ! $this->option('no-interaction')) {
                $this->info('Synchronizing active deposit commissions for FD schemes...');

                $allFdSchemeIds = array_merge(
                    DepositScheme::whereIn('code', array_values($targetCodes))->pluck('id')->all(),
                    [$masterScheme->id]
                );

                $deposits = Deposit::with(['scheme', 'agent.rank'])
                    ->whereIn('deposit_scheme_id', $allFdSchemeIds)
                    ->where('status', Deposit::STATUS_ACTIVE)
                    ->get();

                $updatedCount = 0;
                $generatedCount = 0;

                foreach ($deposits as $deposit) {
                    $tenureYears = $deposit->getTenureInYears();
                    $agent = $deposit->agent;

                    if (! $agent || $agent->status !== 'active') {
                        continue;
                    }

                    $exactYear = max(1, $tenureYears);
                    $rule = $commissionService->findApplicableRuleForDeposit($deposit, $exactYear);
                    if (! $rule) {
                        continue;
                    }

                    $existingComm = Commission::where('deposit_id', $deposit->id)
                        ->where('agent_id', $agent->id)
                        ->where('status', '!=', Commission::STATUS_REJECTED)
                        ->where(function ($q) use ($exactYear) {
                            $q->where('payout_year', $exactYear);
                            if ($exactYear === 1) {
                                $q->orWhereNull('payout_year');
                            }
                        })
                        ->first();

                    $expectedRate = (float) $rule->rate;
                    $expectedAmount = round(((float) $deposit->amount) * ($expectedRate / 100), 2);

                    if ($existingComm) {
                        if ($existingComm->status === Commission::STATUS_PENDING) {
                            $needsUpdate = false;
                            $updates = [];

                            if ((float) $existingComm->rate !== $expectedRate) {
                                $updates['rate'] = $expectedRate;
                                $updates['commission_rate'] = $expectedRate;
                                $needsUpdate = true;
                            }

                            if ((float) $existingComm->commission_amount !== $expectedAmount) {
                                $updates['calculated_amount'] = $expectedAmount;
                                $updates['commission_amount'] = $expectedAmount;
                                $needsUpdate = true;
                            }

                            if ($existingComm->payout_year !== $exactYear) {
                                $updates['payout_year'] = $exactYear;
                                $needsUpdate = true;
                            }

                            if ($existingComm->rule_id !== $rule->id) {
                                $updates['rule_id'] = $rule->id;
                                $needsUpdate = true;
                            }

                            if ($needsUpdate) {
                                $updates['payout_remarks'] = "Year {$exactYear} commission at {$expectedRate}% (synced)";
                                $existingComm->update($updates);
                                $updatedCount++;
                                $this->line("  Updated Pending Comm #{$existingComm->commission_number} (Deposit: {$deposit->deposit_number}, Year: {$exactYear}): Rate {$expectedRate}%, Amt: {$expectedAmount}");
                            }
                        }
                    } else {
                        // Missing exact-tenure commission, generate it
                        $newComm = $commissionService->calculateAndCreateForDepositYear($deposit, $exactYear);
                        if ($newComm) {
                            $generatedCount++;
                            $this->line("  Generated Missing Comm #{$newComm->commission_number} (Deposit: {$deposit->deposit_number}, Year: {$exactYear}): Rate {$expectedRate}%, Amt: {$expectedAmount}");
                        }
                    }
                }

                $this->info("Deposit commission synchronization completed: {$updatedCount} updated, {$generatedCount} newly generated.");
            }

            DB::commit();

            $this->info('FD Schemes commission synchronization completed successfully!');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Failed to synchronize FD schemes: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
