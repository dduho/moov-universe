<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailyAnalyticsCache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CacheDailyAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:cache-daily {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache les analytics quotidiennes pour optimiser les performances';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->argument('date') 
            ? Carbon::parse($this->argument('date')) 
            : Carbon::yesterday();

        $this->info("Calcul des analytics pour le {$date->format('Y-m-d')}...");

        // Calculer les stats du jour
        $stats = DB::table('pdv_transactions')
            ->whereDate('transaction_date', $date)
            ->selectRaw('
                SUM(retrait_keycost) as total_ca,
                SUM(count_depot + count_retrait) as total_transactions,
                SUM(sum_depot + sum_retrait) as total_volume,
                COUNT(DISTINCT pdv_numero) as pdv_actifs,
                SUM(count_depot) as total_depots,
                SUM(sum_depot) as total_depots_amount,
                SUM(count_retrait) as total_retraits,
                SUM(sum_retrait) as total_retraits_amount,
                SUM(count_give_send + count_give_receive) as total_transfers,
                SUM(sum_give_send + sum_give_receive) as total_transfers_amount,
                SUM(pdv_depot_commission + pdv_retrait_commission) as total_commission_pdv,
                SUM(dealer_depot_commission + dealer_retrait_commission) as total_commission_dealers
            ')
            ->first();

        if (!$stats || $stats->total_transactions == 0) {
            $this->warn("Aucune transaction trouvée pour cette date.");
            return 0;
        }

        // Upsert dans le cache
        DailyAnalyticsCache::updateOrCreate(
            ['date' => $date->format('Y-m-d')],
            [
                'total_ca' => $stats->total_ca ?? 0,
                'total_transactions' => $stats->total_transactions ?? 0,
                'total_volume' => $stats->total_volume ?? 0,
                'pdv_actifs' => $stats->pdv_actifs ?? 0,
                'total_depots' => $stats->total_depots ?? 0,
                'total_depots_amount' => $stats->total_depots_amount ?? 0,
                'total_retraits' => $stats->total_retraits ?? 0,
                'total_retraits_amount' => $stats->total_retraits_amount ?? 0,
                'total_transfers' => $stats->total_transfers ?? 0,
                'total_transfers_amount' => $stats->total_transfers_amount ?? 0,
                'total_commission_pdv' => $stats->total_commission_pdv ?? 0,
                'total_commission_dealers' => $stats->total_commission_dealers ?? 0,
            ]
        );

        $this->info("✓ Analytics cachées avec succès !");
        $this->line("  CA: " . number_format($stats->total_ca, 2) . " XOF");
        $this->line("  Transactions: " . number_format($stats->total_transactions));
        $this->line("  PDV actifs: " . number_format($stats->pdv_actifs));

        return 0;
    }
}
