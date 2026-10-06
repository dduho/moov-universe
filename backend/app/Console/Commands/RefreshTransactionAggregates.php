<?php

namespace App\Console\Commands;

use App\Services\TransactionAggregates;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RefreshTransactionAggregates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:refresh-aggregates
                            {--date=* : Date(s) importée(s) : recalcule le mois correspondant}
                            {--days=0 : Recalcule les mois couvrant les N derniers jours}
                            {--all : Recalcule tous les mois}
                            {--pending : Traite les mois en attente après des imports}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcule les tables d\'agrégats des transactions (pdv_transaction_monthly, transaction_daily_summary)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $start = microtime(true);

        if ($this->option('pending')) {
            $count = TransactionAggregates::processPendingRefreshes(
                fn (array $dates) => \App\Http\Controllers\TransactionImportController::refreshAnalyticsCaches($dates)
            );
            if ($count > 0) {
                $this->info("{$count} mois en attente recalculés en " . round(microtime(true) - $start, 1) . 's');
            }
            return self::SUCCESS;
        }

        if ($this->option('all')) {
            $count = TransactionAggregates::refreshAll(fn ($month) => $this->line("  {$month} recalculé"));
            $this->info("{$count} mois recalculés en " . round(microtime(true) - $start, 1) . 's');
            return self::SUCCESS;
        }

        $months = collect($this->option('date'))
            ->map(fn ($date) => Carbon::parse($date)->startOfMonth()->toDateString());

        $days = (int) $this->option('days');
        if ($days > 0) {
            for ($d = Carbon::today()->subDays($days); $d->lte(Carbon::today()); $d->addMonthNoOverflow()->startOfMonth()) {
                $months->push($d->copy()->startOfMonth()->toDateString());
            }
        }

        $months = $months->unique()->sort()->values();

        if ($months->isEmpty()) {
            $this->warn('Rien à faire : utiliser --date=YYYY-MM-DD, --days=N ou --all');
            return self::INVALID;
        }

        foreach ($months as $month) {
            TransactionAggregates::refreshMonth($month);
            $this->line("  {$month} recalculé");
        }

        $this->info($months->count() . ' mois recalculés en ' . round(microtime(true) - $start, 1) . 's');

        return self::SUCCESS;
    }
}
