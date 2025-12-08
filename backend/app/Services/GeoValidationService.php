<?php

namespace App\Services;

/**
 * Service de vérification de cohérence géographique
 * Vérifie si les coordonnées GPS correspondent à la région déclarée
 */
class GeoValidationService
{
    /**
     * Polygones des régions du Togo
     * Format: [longitude, latitude] pour chaque point
     */
    private array $regionBoundaries = [
        'MARITIME' => [
            'name' => 'Maritime',
            'polygon' => [
                [0.7500, 6.1000],
                [1.8000, 6.1000],
                [1.8000, 6.6000],
                [1.4500, 6.9000],
                [1.1000, 6.9000],
                [0.7500, 6.6500],
                [0.7500, 6.1000]
            ],
            'bounds' => ['minLat' => 6.1000, 'maxLat' => 6.9000, 'minLng' => 0.7500, 'maxLng' => 1.8000]
        ],
        'PLATEAUX' => [
            'name' => 'Plateaux',
            'polygon' => [
                [0.5000, 6.8500],
                [1.6500, 6.8500],
                [1.6500, 7.5500],
                [1.4000, 7.8500],
                [0.8500, 7.8500],
                [0.5000, 7.5500],
                [0.5000, 6.8500]
            ],
            'bounds' => ['minLat' => 6.8500, 'maxLat' => 7.8500, 'minLng' => 0.5000, 'maxLng' => 1.6500]
        ],
        'CENTRALE' => [
            'name' => 'Centrale',
            'polygon' => [
                [0.4000, 7.8000],
                [1.5500, 7.8000],
                [1.5500, 8.5000],
                [1.4500, 9.0500],
                [0.7500, 9.0500],
                [0.4000, 8.5000],
                [0.4000, 7.8000]
            ],
            'bounds' => ['minLat' => 7.8000, 'maxLat' => 9.0500, 'minLng' => 0.4000, 'maxLng' => 1.5500]
        ],
        'KARA' => [
            'name' => 'Kara',
            'polygon' => [
                [0.2500, 9.0000],
                [1.5000, 9.0000],
                [1.4500, 9.8000],
                [1.2500, 10.0500],
                [0.5500, 10.0500],
                [0.2500, 9.6000],
                [0.2500, 9.0000]
            ],
            'bounds' => ['minLat' => 9.0000, 'maxLat' => 10.0500, 'minLng' => 0.2500, 'maxLng' => 1.5000]
        ],
        'SAVANES' => [
            'name' => 'Savanes',
            'polygon' => [
                [0.0500, 10.0000],
                [1.2500, 10.0000],
                [1.0000, 10.5000],
                [0.7500, 11.1500],
                [0.1500, 11.1500],
                [-0.1500, 10.6500],
                [0.0500, 10.0000]
            ],
            'bounds' => ['minLat' => 10.0000, 'maxLat' => 11.1500, 'minLng' => -0.1500, 'maxLng' => 1.2500]
        ]
    ];

    /**
     * Vérifie si un point est dans une bounding box
     */
    private function isPointInBounds(float $lat, float $lng, array $bounds): bool
    {
        return $lat >= $bounds['minLat'] && $lat <= $bounds['maxLat'] &&
               $lng >= $bounds['minLng'] && $lng <= $bounds['maxLng'];
    }

    /**
     * Algorithme Ray Casting pour vérifier si un point est dans un polygone
     */
    private function isPointInPolygon(float $lat, float $lng, array $polygon): bool
    {
        $inside = false;
        $n = count($polygon);
        
        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $polygon[$i][0];
            $yi = $polygon[$i][1];
            $xj = $polygon[$j][0];
            $yj = $polygon[$j][1];
            
            if ((($yi > $lat) !== ($yj > $lat)) &&
                ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)) {
                $inside = !$inside;
            }
        }
        
        return $inside;
    }

    /**
     * Détermine la région réelle d'un point GPS
     */
    public function getRegionFromCoordinates(float $lat, float $lng): ?array
    {
        foreach ($this->regionBoundaries as $regionCode => $regionData) {
            // Optimisation: vérifier d'abord la bounding box
            if (!$this->isPointInBounds($lat, $lng, $regionData['bounds'])) {
                continue;
            }
            
            // Vérifier le polygone
            if ($this->isPointInPolygon($lat, $lng, $regionData['polygon'])) {
                return [
                    'region' => $regionCode,
                    'name' => $regionData['name']
                ];
            }
        }
        
        return null;
    }

    /**
     * Valide si les coordonnées GPS correspondent à la région déclarée
     */
    public function validateRegionCoordinates(?float $lat, ?float $lng, ?string $declaredRegion): array
    {
        if (!$lat || !$lng || !$declaredRegion) {
            return [
                'is_valid' => true,
                'has_alert' => false,
                'message' => null
            ];
        }
        
        $actualRegion = $this->getRegionFromCoordinates($lat, $lng);
        
        // Point hors du Togo
        if (!$actualRegion) {
            return [
                'is_valid' => false,
                'has_alert' => true,
                'alert_type' => 'error',
                'message' => 'Les coordonnées GPS semblent être situées en dehors du Togo',
                'declared_region' => $declaredRegion,
                'actual_region' => null
            ];
        }
        
        // Comparer les régions
        $normalizedDeclared = strtoupper($declaredRegion);
        $normalizedActual = strtoupper($actualRegion['region']);
        
        if ($normalizedDeclared !== $normalizedActual) {
            return [
                'is_valid' => false,
                'has_alert' => true,
                'alert_type' => 'warning',
                'message' => "Incohérence géographique : le PDV est déclaré dans la région \"{$declaredRegion}\" mais les coordonnées GPS indiquent la région \"{$actualRegion['name']}\"",
                'declared_region' => $declaredRegion,
                'actual_region' => $actualRegion['region'],
                'actual_region_name' => $actualRegion['name']
            ];
        }
        
        return [
            'is_valid' => true,
            'has_alert' => false,
            'declared_region' => $declaredRegion,
            'actual_region' => $actualRegion['region'],
            'actual_region_name' => $actualRegion['name']
        ];
    }

    /**
     * Vérifie la cohérence géographique pour un PDV
     * @param \App\Models\PointOfSale $pdv
     * @return array
     */
    public function checkPdvGeoConsistency($pdv): array
    {
        return $this->validateRegionCoordinates(
            $pdv->latitude,
            $pdv->longitude,
            $pdv->region
        );
    }
}
