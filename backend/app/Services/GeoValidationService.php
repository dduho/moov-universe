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
                [1.1667, 6.1000],  // Sud-Ouest : embouchure frontière Ghana-Togo (côte atlantique)
                [0.9000, 6.7000],  // Ouest : le long de la frontière Ghana jusqu’à la limite Plateaux
                [1.1000, 6.9000],  // Nord : limite avec Plateaux (intérieur)
                [1.5000, 7.0000],  // Nord-Est : sur le fleuve Mono vers la région Plateaux
                [1.6650, 6.1000],  // Sud-Est : frontière Togo–Bénin sur la côte (Grand-Popo)
                [1.1667, 6.1000]   // Fermeture du polygone (retour au point de départ)
            ],
            'bounds' => ['minLat' => 6.1000, 'maxLat' => 7.0000, 'minLng' => 0.9000, 'maxLng' => 1.6650]
        ],
        'PLATEAUX' => [
            'name' => 'Plateaux',
            'polygon' => [
                [0.9000, 6.8000],  // Sud-Ouest : sur la frontière Ghana, début de la région Plateaux
                [0.3000, 8.3000],  // Nord-Ouest : frontière Ghana jusqu’à la région Centrale
                [1.5000, 8.3000],  // Nord-Est : frontière avec le Bénin à la limite de la Centrale
                [1.5000, 7.0000],  // Sud-Est : descendant le long du fleuve Mono (frontière Bénin) vers Maritime
                [0.9000, 6.8000]   // Fermeture du polygone (retour au point de départ)
            ],
            'bounds' => ['minLat' => 6.8000, 'maxLat' => 8.3000, 'minLng' => 0.3000, 'maxLng' => 1.5000]
        ],
        'CENTRALE' => [
            'name' => 'Centrale',
            'polygon' => [
                [0.3000, 8.3000],  // Sud-Ouest : commence à la jonction avec Plateaux sur frontière Ghana
                [0.2000, 9.5000],  // Nord-Ouest : sur la frontière Ghana jusqu’à la région Kara
                [1.3000, 9.5000],  // Nord-Est : frontière avec la Kara vers le Bénin
                [1.5000, 8.3000],  // Sud-Est : sur la frontière Bénin à la jonction avec Plateaux
                [0.3000, 8.3000]   // Fermeture du polygone
            ],
            'bounds' => ['minLat' => 8.3000, 'maxLat' => 9.5000, 'minLng' => 0.2000, 'maxLng' => 1.5000]
        ],
        'KARA' => [
            'name' => 'Kara',
            'polygon' => [
                [0.2000, 9.5000],   // Sud-Ouest : départ à la frontière Ghana (limite avec Centrale)
                [0.0000, 10.5000],  // Nord-Ouest : frontière Ghana jusqu’à la région des Savanes
                [1.2000, 10.5000],  // Nord-Est : extrémité est vers la frontière du Bénin (limite Savanes)
                [1.3000, 9.5000],   // Sud-Est : sur la frontière Bénin à la limite de la Centrale
                [0.2000, 9.5000]    // Fermeture du polygone
            ],
            'bounds' => ['minLat' => 9.5000, 'maxLat' => 10.5000, 'minLng' => 0.0000, 'maxLng' => 1.3000]
        ],
        'SAVANES' => [
            'name' => 'Savanes',
            'polygon' => [
                [0.0000, 10.5000],  // Sud-Ouest : départ à la frontière Ghana (limite avec Kara)
                [-0.1000, 10.9000], // Ouest : remontée le long de la frontière Ghana
                [0.0000, 11.1000],  // Nord-Ouest : tripoint Ghana–Burkina–Togo (frontière Burkina Faso)
                [1.0000, 11.1000],  // Nord-Est : tripoint Burkina–Bénin–Togo (frontière Burkina/Bénin)
                [1.2000, 10.5000],  // Sud-Est : sur la frontière du Bénin (limite avec Kara)
                [0.0000, 10.5000]   // Fermeture du polygone
            ],
            'bounds' => ['minLat' => 10.5000, 'maxLat' => 11.1000, 'minLng' => -0.1000, 'maxLng' => 1.2000]
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
