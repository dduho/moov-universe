<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Import des transactions depuis SFTP chaque jour à 08:30
        $schedule->command('transactions:import-sftp')
                 ->dailyAt('08:30')
                 ->withoutOverlapping()
                 ->onOneServer();

        // Import des transactions depuis Outlook (si configuré)
        if (config('services.outlook.import_enabled')) {
            $schedule->command('transactions:import-outlook')
                     ->dailyAt(config('services.outlook.import_time', '08:30'))
                     ->timezone(config('services.outlook.import_timezone', 'UTC'))
                     ->withoutOverlapping()
                     ->onOneServer()
                     ->appendOutputTo(storage_path('logs/outlook-import.log'));
        }

        // Filet de sécurité : recalcul des agrégats des transactions sur les 3 derniers jours
        // (chaque import les recalcule déjà pour son mois)
        $schedule->command('analytics:refresh-aggregates --days=3')
                 ->dailyAt('08:55')
                 ->withoutOverlapping()
                 ->onOneServer()
                 ->appendOutputTo(storage_path('logs/analytics-aggregates.log'));

        // Recalcul complet hebdomadaire (rattachement dealer/région des PDV dans transaction_daily_summary)
        $schedule->command('analytics:refresh-aggregates --all')
                 ->weeklyOn(0, '03:00')
                 ->withoutOverlapping()
                 ->onOneServer()
                 ->appendOutputTo(storage_path('logs/analytics-aggregates.log'));

        // Calculer les analytics de J-1 après l'import (à 09:00)
        $schedule->command('analytics:cache-daily')
                 ->dailyAt('09:00')
                 ->withoutOverlapping()
                 ->onOneServer()
                 ->appendOutputTo(storage_path('logs/analytics-cache.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
