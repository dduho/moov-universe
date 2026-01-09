<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\SystemSetting;

class CacheSettingController extends Controller
{
    // Liste des widgets gérés
    private $widgets = [
        'network_optimization',
        'risk_compliance',
        'advanced_geospatial',
        'offline_dashboard',
        'dealer_analytics',
        'fraud_detection',
    ];

    public function getCacheSettings()
    {
        $settings = [];
        foreach ($this->widgets as $widget) {
            $enabled = SystemSetting::getValue("cache_{$widget}_enabled", true);
            $ttl = SystemSetting::getValue("cache_{$widget}_ttl", 60);
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
        SystemSetting::setValue("cache_{$widget}_enabled", $request->enabled);
        SystemSetting::setValue("cache_{$widget}_ttl", $request->ttl);
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
        } catch (\Exception $e) {
            // Fallback: ne rien faire si la commande est désactivée
            \Log::warning('Widget cache flush failed: ' . $e->getMessage());
        }
        
        return response()->json(['success' => true]);
    }

    public function clearAllCaches()
    {
        try {
            // Utiliser artisan cache:clear qui gère mieux les restrictions Redis
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('route:clear');
            \Artisan::call('view:clear');
        } catch (\Exception $e) {
            \Log::error('Clear all caches failed: ' . $e->getMessage());
            return response()->json(['error' => 'Cache clear failed'], 500);
        }
        
        return response()->json(['success' => true]);
    }
}
