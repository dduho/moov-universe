<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Cache\RedisStore;
use App\Models\SystemSetting;

class CacheSettingController extends Controller
{
    // Liste des widgets gérés
    private $widgets = [
        'cache_map',
        'cache_geolocation',
        'cache_rentability',
        'cache_predictions',
        'cache_analytics',
        'cache_pdv',
        'cache_fraud_detection',
        'cache_network_optimization',
        'cache_risk_compliance',
    ];

    // Patterns approximatifs pour nettoyer les clés non taguées par widget
    private $widgetPatterns = [
        'cache_map' => ['map_data_*'],
        'cache_geolocation' => ['geo_*'],
        'cache_rentability' => ['rentability_*'],
        'cache_predictions' => ['prediction_*', 'trends_*', 'alerts_*', 'correlations_*', 'optimization_*', 'simulation_*'],
        'cache_analytics' => ['analytics_*'],
        'cache_pdv' => ['pdv-index*', 'pdv_stats_*', 'map_data_*'],
        'cache_fraud_detection' => ['fraud-*'],
        'cache_network_optimization' => ['comparator_*', 'recommendations_*', 'forecast_*'],
        'cache_risk_compliance' => ['risk_*'],
    ];

    public function getCacheSettings()
    {
        $settings = [];
        foreach ($this->widgets as $widget) {
            $enabled = SystemSetting::getValue("{$widget}_enabled", true);
            $ttl = SystemSetting::getValue("{$widget}_ttl", 60);
            $settings[$widget] = [
                'enabled' => (bool)$enabled,
                'ttl' => (int)$ttl
            ];
        }
        return response()->json($settings);
    }

    public function updateCacheSetting(Request $request, $widget)
    {
        if (!in_array($widget, $this->widgets)) {
            return response()->json(['error' => 'Widget not found'], 404);
        }
        $request->validate([
            'enabled' => 'required|boolean',
            'ttl' => 'required|integer|min:1|max:1440',
        ]);
        SystemSetting::setValue("{$widget}_enabled", $request->enabled);
        SystemSetting::setValue("{$widget}_ttl", $request->ttl);
        return response()->json(['success' => true]);
    }

    public function clearWidgetCache($widget)
    {
        if (!in_array($widget, $this->widgets)) {
            return response()->json(['error' => 'Widget not found'], 404);
        }
        
        try {
            // Utiliser les tags pour vider seulement le cache du widget spécifique
            Cache::tags([$widget, 'analytics'])->flush();
            $this->clearPatterns($this->widgetPatterns[$widget] ?? []);
        } catch (\Exception $e) {
            // Fallback: ne rien faire si la commande est désactivée
            \Log::warning('Widget cache flush failed: ' . $e->getMessage());
        }
        
        return response()->json(['success' => true]);
    }

    public function clearAllCaches()
    {
        $errors = [];

        try {
            // Nettoyage sécurisé sans utiliser FLUSHDB (souvent interdit en prod)
            $store = Cache::getStore();

            // 1) Si Redis, supprimer uniquement les clés préfixées par l'application
            if ($store instanceof RedisStore) {
                $this->clearRedisPrefixedKeys($store, ['*']);
            }

            // 2) Tags connus (pour les drivers qui supportent les tags)
            try {
                Cache::tags(array_merge($this->widgets, ['analytics', 'transactions', 'rentability', 'fraud-detection', 'pdv-index']))->flush();
            } catch (\Throwable $e) {
                $errors[] = 'Tags flush failed: ' . $e->getMessage();
                \Log::warning('Cache tags flush failed: ' . $e->getMessage());
            }

            // 3) Artisan commandes hors cache (ne doivent pas planter la requête)
            foreach (['config:clear', 'route:clear', 'view:clear'] as $command) {
                try {
                    \Artisan::call($command);
                } catch (\Throwable $e) {
                    $errors[] = "artisan {$command} failed: " . $e->getMessage();
                    \Log::warning("Artisan {$command} failed: " . $e->getMessage());
                }
            }
        } catch (\Exception $e) {
            \Log::error('Clear all caches failed: ' . $e->getMessage());
            return response()->json(['error' => 'Cache clear failed'], 500);
        }

        return response()->json([
            'success' => true,
            'warnings' => $errors,
        ]);
    }

    private function clearPatterns(array $patterns): void
    {
        $store = Cache::getStore();
        if ($store instanceof RedisStore) {
            $this->clearRedisPrefixedKeys($store, $patterns);
        }
    }

    private function clearRedisPrefixedKeys(RedisStore $store, array $patterns): void
    {
        $redis = $store->connection();
        $prefix = $store->getPrefix();

        foreach ($patterns as $pattern) {
            $fullPattern = $prefix . $pattern;

            try {
                // php-redis extension
                if ($redis instanceof \Redis || $redis instanceof \RedisCluster) {
                    $iterator = null;
                    do {
                        $keys = $redis->scan($iterator, $fullPattern, 1000);
                        if ($keys !== false && !empty($keys)) {
                            $redis->del($keys);
                        }
                    } while ($iterator !== 0 && $keys !== false);
                    continue;
                }

                // Predis client
                $cursor = null;
                do {
                    $result = $redis->scan($cursor ?? 0, ['match' => $fullPattern, 'count' => 1000]);
                    $cursor = $result[0] ?? 0;
                    $keys = $result[1] ?? [];
                    if (!empty($keys)) {
                        $redis->del($keys);
                    }
                } while ($cursor !== 0);
            } catch (\Throwable $e) {
                // Fallback KEYS si SCAN n'est pas dispo
                try {
                    $keys = $redis->keys($fullPattern);
                    if (!empty($keys)) {
                        $redis->del($keys);
                    }
                } catch (\Throwable $inner) {
                    \Log::warning('Redis key deletion failed: ' . $inner->getMessage());
                }
            }
        }
    }
}
