<?php

namespace App\Services;

use App\Models\PointOfSale;
use App\Models\SystemSetting;

class ProximityAlertService
{
    /**
     * Check for nearby PDVs within alert distance
     */
    public function checkProximity(float $lat, float $lng, ?int $excludeId = null): array
    {
        $alertDistance = SystemSetting::getValue('pdv_proximity_threshold', 300);
        
        $nearbyPdvs = PointOfSale::query()
            ->where('status', 'validated')
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->get()
            ->filter(function ($pdv) use ($lat, $lng, $alertDistance) {
                $distance = $this->haversineDistance($lat, $lng, $pdv->latitude, $pdv->longitude);
                return $distance <= $alertDistance;
            })
            ->map(function ($pdv) use ($lat, $lng) {
                $pdv->distance = round($this->haversineDistance($lat, $lng, $pdv->latitude, $pdv->longitude), 2);
                return $pdv;
            })
            ->sortBy('distance')
            ->values();
        
        return [
            'has_nearby' => $nearbyPdvs->isNotEmpty(),
            'nearby_pdvs' => $nearbyPdvs,
            'alert_distance' => $alertDistance,
            'count' => $nearbyPdvs->count(),
        ];
    }

    /**
     * Calculate distance between two GPS coordinates using Haversine formula
     * 
     * @param float $lat1 Latitude of first point
     * @param float $lon1 Longitude of first point
     * @param float $lat2 Latitude of second point
     * @param float $lon2 Longitude of second point
     * @return float Distance in meters
     */
    private function haversineDistance($lat1, $lon1, $lat2, $lon2): float
    {
        $earthRadius = 6371000; // Earth's radius in meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        
        $a = sin($dLat/2) * sin($dLat/2) + 
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * 
             sin($dLon/2) * sin($dLon/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }
}
