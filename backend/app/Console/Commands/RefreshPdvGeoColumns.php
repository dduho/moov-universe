<?php

namespace App\Console\Commands;

use App\Services\GeoValidationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshPdvGeoColumns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pdv:refresh-geo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalcule la région déduite du GPS et l\'alerte d\'incohérence géographique de tous les PDV';

    /**
     * Execute the console command.
     */
    public function handle(GeoValidationService $geo)
    {
        $updated = 0;
        $alerts = 0;

        DB::table('point_of_sales')
            ->select('id', 'latitude', 'longitude', 'region', 'geo_actual_region', 'geo_has_alert')
            ->orderBy('id')
            ->chunkById(2000, function ($rows) use ($geo, &$updated, &$alerts) {
                // Regroupe les mises à jour par valeur : une requête par combinaison (région, alerte)
                $groups = [];

                foreach ($rows as $row) {
                    $validation = $geo->validateRegionCoordinates(
                        $row->latitude !== null ? (float) $row->latitude : null,
                        $row->longitude !== null ? (float) $row->longitude : null,
                        $row->region
                    );

                    $actual = $validation['actual_region'] ?? null;
                    $alert = (bool) ($validation['has_alert'] ?? false);
                    $alerts += $alert ? 1 : 0;

                    if ($row->geo_actual_region !== $actual || (bool) $row->geo_has_alert !== $alert) {
                        $groups[($actual ?? '') . '|' . (int) $alert][] = $row->id;
                    }
                }

                foreach ($groups as $key => $ids) {
                    [$actual, $alert] = explode('|', $key);
                    DB::table('point_of_sales')->whereIn('id', $ids)->update([
                        'geo_actual_region' => $actual === '' ? null : $actual,
                        'geo_has_alert' => (bool) $alert,
                    ]);
                    $updated += count($ids);
                }
            });

        \App\Models\PointOfSale::bumpMapCacheVersion();

        $this->info("{$updated} PDV mis à jour, {$alerts} PDV en incohérence géographique.");

        return self::SUCCESS;
    }
}
