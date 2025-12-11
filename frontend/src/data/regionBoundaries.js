/**
 * Polygones des régions administratives du Togo
 * Coordonnées approximatives basées sur les frontières géographiques officielles
 * Format: [longitude, latitude] pour chaque point du polygone
 */

export const regionBoundaries = {
  MARITIME: {
    name: 'Maritime',
    // Polygone plus précis pour la région Maritime
    polygon: [
      [0.7000, 6.1000],   // Sud-Ouest (frontière Ghana, côte)
      [1.8000, 6.1000],   // Sud-Est (frontière Bénin, côte)
      [1.8000, 6.5000],   // Est
      [1.6000, 7.2000],   // Nord-Est
      [1.2000, 7.2000],   // Nord
      [0.7000, 7.0000],   // Nord-Ouest
      [0.7000, 6.1000]    // Fermeture
    ],
    bounds: {
      minLat: 6.1000,
      maxLat: 7.2000,
      minLng: 0.7000,
      maxLng: 1.8000
    }
  },
  
  PLATEAUX: {
    name: 'Plateaux',
    polygon: [
      [0.5000, 7.2000],   // Sud-Ouest
      [1.6000, 7.2000],   // Sud-Est
      [1.6000, 8.3000],   // Nord-Est
      [1.4000, 8.3000],   // Est
      [0.8000, 8.3000],   // Nord
      [0.5000, 8.1000],   // Nord-Ouest
      [0.5000, 7.2000]    // Fermeture
    ],
    bounds: {
      minLat: 7.2000,
      maxLat: 8.3000,
      minLng: 0.5000,
      maxLng: 1.6000
    }
  },
  
  CENTRALE: {
    name: 'Centrale',
    polygon: [
      [0.4000, 8.3000],   // Sud-Ouest
      [1.5000, 8.3000],   // Sud-Est
      [1.5000, 9.5000],   // Nord-Est
      [1.3000, 9.5000],   // Est
      [0.7000, 9.5000],   // Nord
      [0.4000, 9.2000],   // Nord-Ouest
      [0.4000, 8.3000]    // Fermeture
    ],
    bounds: {
      minLat: 8.3000,
      maxLat: 9.5000,
      minLng: 0.4000,
      maxLng: 1.5000
    }
  },
  
  KARA: {
    name: 'Kara',
    polygon: [
      [0.2000, 9.5000],   // Sud-Ouest
      [1.4000, 9.5000],   // Sud-Est
      [1.4000, 10.5000],  // Nord-Est
      [1.2000, 10.5000],  // Est
      [0.5000, 10.5000],  // Nord
      [0.2000, 10.2000],  // Nord-Ouest
      [0.2000, 9.5000]    // Fermeture
    ],
    bounds: {
      minLat: 9.5000,
      maxLat: 10.5000,
      minLng: 0.2000,
      maxLng: 1.4000
    }
  },
  
  SAVANES: {
    name: 'Savanes',
    polygon: [
      [0.0000, 10.5000],  // Sud-Ouest
      [1.2000, 10.5000],  // Sud-Est (frontière Bénin)
      [1.0000, 11.1000],  // Est
      [0.7000, 11.1000],  // Nord-Est (frontière Burkina)
      [0.1000, 11.1000],  // Nord (frontière Burkina)
      [-0.1000, 10.8000], // Nord-Ouest (frontière Ghana)
      [0.0000, 10.5000]   // Fermeture
    ],
    bounds: {
      minLat: 10.5000,
      maxLat: 11.1000,
      minLng: -0.1000,
      maxLng: 1.2000
    }
  },
  
  // Lomé comme zone spéciale (incluse dans Maritime)
  LOME: {
    name: 'Grand Lomé',
    polygon: [
      [1.0500, 6.1000],   // Sud-Ouest
      [1.3500, 6.1000],   // Sud-Est
      [1.3500, 6.2500],   // Nord-Est
      [1.0500, 6.2500],   // Nord-Ouest
      [1.0500, 6.1000]    // Fermeture
    ],
    bounds: {
      minLat: 6.1000,
      maxLat: 6.2500,
      minLng: 1.0500,
      maxLng: 1.3500
    }
  }
};

/**
 * Algorithme Ray Casting pour déterminer si un point est dans un polygone
 * @param {number} lat - Latitude du point
 * @param {number} lng - Longitude du point
 * @param {Array} polygon - Array de [lng, lat] définissant le polygone
 * @returns {boolean} - true si le point est dans le polygone
 */
export function isPointInPolygon(lat, lng, polygon) {
  let inside = false;
  const n = polygon.length;
  
  for (let i = 0, j = n - 1; i < n; j = i++) {
    const xi = polygon[i][0], yi = polygon[i][1];
    const xj = polygon[j][0], yj = polygon[j][1];
    
    if (((yi > lat) !== (yj > lat)) &&
        (lng < (xj - xi) * (lat - yi) / (yj - yi) + xi)) {
      inside = !inside;
    }
  }
  
  return inside;
}

/**
 * Vérification rapide avec bounding box avant le test du polygone
 * @param {number} lat - Latitude du point
 * @param {number} lng - Longitude du point
 * @param {Object} bounds - Bounding box {minLat, maxLat, minLng, maxLng}
 * @returns {boolean} - true si le point est dans la bounding box
 */
export function isPointInBounds(lat, lng, bounds) {
  return lat >= bounds.minLat && lat <= bounds.maxLat &&
         lng >= bounds.minLng && lng <= bounds.maxLng;
}

/**
 * Détermine la région réelle d'un point GPS
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 * @returns {Object} - {region: string, name: string} ou null si hors du Togo
 */
export function getRegionFromCoordinates(lat, lng) {
  // Vérifier d'abord le Grand Lomé (zone spéciale)
  const lome = regionBoundaries.LOME;
  if (isPointInBounds(lat, lng, lome.bounds) && isPointInPolygon(lat, lng, lome.polygon)) {
    // Lomé est dans Maritime
    return { region: 'MARITIME', name: 'Grand Lomé (Maritime)' };
  }
  
  // Vérifier les autres régions
  for (const [regionCode, regionData] of Object.entries(regionBoundaries)) {
    if (regionCode === 'LOME') continue; // Déjà vérifié
    
    // Optimisation: vérifier d'abord la bounding box
    if (!isPointInBounds(lat, lng, regionData.bounds)) continue;
    
    // Vérifier le polygone
    if (isPointInPolygon(lat, lng, regionData.polygon)) {
      return { region: regionCode, name: regionData.name };
    }
  }
  
  return null; // Point hors du Togo
}

/**
 * Vérifie si les coordonnées GPS correspondent à la région déclarée
 * @param {number} lat - Latitude
 * @param {number} lng - Longitude
 * @param {string} declaredRegion - Région déclarée par l'utilisateur
 * @returns {Object} - Résultat de la vérification
 */
export function validateRegionCoordinates(lat, lng, declaredRegion) {
  if (!lat || !lng || !declaredRegion) {
    return {
      isValid: true,
      hasAlert: false,
      message: null
    };
  }
  
  const actualRegion = getRegionFromCoordinates(lat, lng);
  
  // Point hors du Togo
  if (!actualRegion) {
    return {
      isValid: false,
      hasAlert: true,
      alertType: 'error',
      message: 'Les coordonnées GPS semblent être situées en dehors du Togo',
      declaredRegion: declaredRegion,
      actualRegion: null
    };
  }
  
  // Comparer les régions (en majuscules pour éviter les erreurs de casse)
  const normalizedDeclared = declaredRegion.toUpperCase();
  const normalizedActual = actualRegion.region.toUpperCase();
  
  if (normalizedDeclared !== normalizedActual) {
    return {
      isValid: false,
      hasAlert: true,
      alertType: 'warning',
      message: `Incohérence géographique détectée : le PDV est déclaré dans la région "${declaredRegion}" mais les coordonnées GPS indiquent la région "${actualRegion.name}"`,
      declaredRegion: declaredRegion,
      actualRegion: actualRegion.region,
      actualRegionName: actualRegion.name
    };
  }
  
  return {
    isValid: true,
    hasAlert: false,
    declaredRegion: declaredRegion,
    actualRegion: actualRegion.region,
    actualRegionName: actualRegion.name
  };
}

/**
 * Obtenir toutes les régions disponibles
 * @returns {Array} - Liste des régions avec leurs codes et noms
 */
export function getAvailableRegions() {
  return Object.entries(regionBoundaries)
    .filter(([code]) => code !== 'LOME')
    .map(([code, data]) => ({
      code,
      name: data.name
    }));
}

export default {
  regionBoundaries,
  isPointInPolygon,
  isPointInBounds,
  getRegionFromCoordinates,
  validateRegionCoordinates,
  getAvailableRegions
};
