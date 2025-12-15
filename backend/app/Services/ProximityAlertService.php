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

    /**
     * Get all proximity alerts for dashboard
     * 
     * @param \App\Models\User $user
     * @return array
     */
    public function getAllProximityAlerts($user): array
    {
        $alertDistance = SystemSetting::getValue('pdv_proximity_threshold', 300);
        
        // Get all validated PDVs with coordinates
        $query = PointOfSale::query()
            ->where('status', 'validated')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->with(['organization', 'creator']);
        
        // Filter by organization for dealer owners
        if ($user->role->name === 'dealer_owner') {
            $organizationId = $user->organization_id;
        } else {
            $organizationId = null;
        }
        
        $pdvs = $query->get();
        
        // Limit to prevent timeout
        if ($pdvs->count() > 500) {
            $pdvs = $pdvs->take(500);
        }
        
        // Find all pairs that are too close
        $proximityPairs = [];
        $pdvInCluster = [];
        
        // Use spatial grid for optimization
        $gridSize = $alertDistance / 111000;
        $grid = [];
        
        foreach ($pdvs as $index => $pdv) {
            $cellX = floor($pdv->longitude / $gridSize);
            $cellY = floor($pdv->latitude / $gridSize);
            $cellKey = "{$cellX},{$cellY}";
            
            if (!isset($grid[$cellKey])) {
                $grid[$cellKey] = [];
            }
            $grid[$cellKey][] = ['pdv' => $pdv, 'index' => $index];
        }
        
        $checked = [];
        
        // Check only neighboring cells
        foreach ($grid as $cellKey => $cellPdvs) {
            [$cellX, $cellY] = explode(',', $cellKey);
            
            for ($dx = -1; $dx <= 1; $dx++) {
                for ($dy = -1; $dy <= 1; $dy++) {
                    $neighborKey = ($cellX + $dx) . ',' . ($cellY + $dy);
                    if (!isset($grid[$neighborKey])) continue;
                    
                    $neighborPdvs = $grid[$neighborKey];
                    
                    foreach ($cellPdvs as $p1) {
                        foreach ($neighborPdvs as $p2) {
                            if ($p1['index'] >= $p2['index']) continue;
                            
                            $pairKey = $p1['index'] . '-' . $p2['index'];
                            if (isset($checked[$pairKey])) continue;
                            $checked[$pairKey] = true;
                            
                            $pdv1 = $p1['pdv'];
                            $pdv2 = $p2['pdv'];
                            
                            $distance = $this->haversineDistance(
                                $pdv1->latitude, 
                                $pdv1->longitude, 
                                $pdv2->latitude, 
                                $pdv2->longitude
                            );
                            
                            if ($distance <= $alertDistance) {
                                // For dealer owners, check if at least one PDV belongs to their org
                                if ($organizationId) {
                                    if ($pdv1->organization_id !== $organizationId && $pdv2->organization_id !== $organizationId) {
                                        continue;
                                    }
                                }
                                
                                $proximityPairs[] = [
                                    'pdv1_id' => $pdv1->id,
                                    'pdv2_id' => $pdv2->id,
                                    'pdv1' => $pdv1,
                                    'pdv2' => $pdv2,
                                    'distance' => round($distance, 2),
                                ];
                                
                                $pdvInCluster[$pdv1->id] = $pdv1;
                                $pdvInCluster[$pdv2->id] = $pdv2;
                            }
                        }
                    }
                }
            }
        }
        
        // Group PDVs into clusters using Union-Find algorithm
        $clusters = $this->clusterProximityPairs($proximityPairs, $pdvInCluster, $user, $organizationId, $alertDistance);
        
        return [
            'clusters' => $clusters,
            'count' => count($clusters),
            'threshold' => $alertDistance,
            'total_pdv_affected' => count($pdvInCluster),
        ];
    }
    
    /**
     * Cluster PDVs that are close to each other
     */
    private function clusterProximityPairs($pairs, $pdvInCluster, $user, $organizationId, $alertDistance): array
    {
        if (empty($pairs)) {
            return [];
        }
        
        // Union-Find to group connected PDVs
        $parent = [];
        foreach ($pdvInCluster as $id => $pdv) {
            $parent[$id] = $id;
        }
        
        $find = function($id) use (&$parent, &$find) {
            if ($parent[$id] !== $id) {
                $parent[$id] = $find($parent[$id]);
            }
            return $parent[$id];
        };
        
        $union = function($id1, $id2) use (&$parent, &$find) {
            $root1 = $find($id1);
            $root2 = $find($id2);
            if ($root1 !== $root2) {
                $parent[$root2] = $root1;
            }
        };
        
        // Group PDVs that are connected
        foreach ($pairs as $pair) {
            $union($pair['pdv1_id'], $pair['pdv2_id']);
        }
        
        // Build clusters
        $clusterGroups = [];
        foreach ($pdvInCluster as $id => $pdv) {
            $root = $find($id);
            if (!isset($clusterGroups[$root])) {
                $clusterGroups[$root] = [];
            }
            $clusterGroups[$root][] = $pdv;
        }
        
        // Build distance matrix for each cluster
        $clusters = [];
        foreach ($clusterGroups as $group) {
            if (count($group) < 2) continue;
            
            $pdvList = [];
            $distances = [];
            
            foreach ($group as $pdv) {
                $canAccess = $user->role->name === 'admin' || $pdv->organization_id === $organizationId;
                
                $pdvList[] = [
                    'id' => $pdv->id,
                    'nom_point' => $canAccess ? $pdv->nom_point : '***',
                    'numero_flooz' => $canAccess ? $pdv->numero_flooz : '***',
                    'region' => $pdv->region,
                    'ville' => $canAccess ? $pdv->ville : '***',
                    'quartier' => $canAccess ? $pdv->quartier : '***',
                    'organization_name' => $pdv->organization->name ?? 'N/A',
                    'can_access' => $canAccess,
                    'is_own' => $pdv->organization_id === $organizationId,
                ];
            }
            
            // Calculate distances between all PDVs in the cluster
            foreach ($group as $i => $pdv1) {
                foreach ($group as $j => $pdv2) {
                    if ($i >= $j) continue;
                    
                    $distance = $this->haversineDistance(
                        $pdv1->latitude,
                        $pdv1->longitude,
                        $pdv2->latitude,
                        $pdv2->longitude
                    );
                    
                    // Only include distances that are within the alert threshold
                    if ($distance <= $alertDistance) {
                        $distances[] = [
                            'from_id' => $pdv1->id,
                            'to_id' => $pdv2->id,
                            'distance' => round($distance, 2),
                        ];
                    }
                }
            }
            
            // Skip if no valid distances (shouldn't happen, but safety check)
            if (empty($distances)) {
                continue;
            }
            
            // Find the minimum distance in the cluster
            $minDistance = min(array_column($distances, 'distance'));
            
            $clusters[] = [
                'pdvs' => $pdvList,
                'count' => count($pdvList),
                'distances' => $distances,
                'min_distance' => $minDistance,
            ];
        }
        
        // Sort clusters by minimum distance (most critical first)
        usort($clusters, function($a, $b) {
            return $a['min_distance'] <=> $b['min_distance'];
        });
        
        return $clusters;
    }
}
