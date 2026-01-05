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
        Cache::flush();
        return response()->json(['success' => true]);
    }

    public function clearAllCaches()
    {
        Cache::flush();
        return response()->json(['success' => true]);
    }
}
