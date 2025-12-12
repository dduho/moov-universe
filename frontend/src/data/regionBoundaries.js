/**
 * Polygones des régions administratives du Togo
 * Coordonnées approximatives basées sur les frontières géographiques officielles
 * Format: [longitude, latitude] pour chaque point du polygone
 */

export const regionBoundaries = {
  MARITIME: {
    name: 'Maritime',
    // Polygone précis suivant frontière Ghana (ouest), côte (sud) et fleuve Mono (est)
    polygon: [
      [1.1667, 6.1000],  // Sud-Ouest : embouchure frontière Ghana-Togo (côte atlantique)
      [0.9000, 6.7000],  // Ouest : le long de la frontière Ghana jusqu’à la limite Plateaux
      [1.1000, 6.9000],  // Nord : limite avec Plateaux (intérieur)
      [1.5000, 7.0000],  // Nord-Est : sur le fleuve Mono vers la région Plateaux
      [1.6650, 6.1000],  // Sud-Est : frontière Togo–Bénin sur la côte (Grand-Popo)
      [1.1667, 6.1000]   // Fermeture du polygone (retour au point de départ)
    ],
    bounds: {
      minLat: 6.1000,
      maxLat: 7.0000,
      minLng: 0.9000,
      maxLng: 1.6650
    }
  },

  PLATEAUX: {
    name: 'Plateaux',
    // Polygone ajusté pour couvrir Agoú-Gare et Kpalimé
    polygon: [
      [0.5000, 6.8000],  // Sud-Ouest : frontière Ghana
      [0.3000, 7.5000],  // Ouest : intérieur
      [0.3000, 8.3000],  // Nord-Ouest : frontière Ghana
      [1.5000, 8.3000],  // Nord-Est : frontière Bénin
      [1.5000, 7.0000],  // Sud-Est : fleuve Mono
      [0.9000, 6.8000],  // Sud : frontière Maritime
      [0.5000, 6.8000]   // Fermeture
    ],
    bounds: {
      minLat: 6.8000,
      maxLat: 8.3000,
      minLng: 0.3000,
      maxLng: 1.5000
    }
  },

  CENTRALE: {
    name: 'Centrale',
    // Polygone couvrant la région centrale (entre Plateaux et Kara, du Ghana au Bénin)
    polygon: [
      [0.3000, 8.3000],  // Sud-Ouest : commence à la jonction avec Plateaux sur frontière Ghana
      [0.2000, 9.5000],  // Nord-Ouest : sur la frontière Ghana jusqu’à la région Kara
      [1.3000, 9.5000],  // Nord-Est : frontière avec la Kara vers le Bénin
      [1.5000, 8.3000],  // Sud-Est : sur la frontière Bénin à la jonction avec Plateaux
      [0.3000, 8.3000]   // Fermeture du polygone
    ],
    bounds: {
      minLat: 8.3000,
      maxLat: 9.5000,
      minLng: 0.2000,
      maxLng: 1.5000
    }
  },

  KARA: {
    name: 'Kara',
    // Polygone de la région de la Kara (entre Centrale et Savanes, Ghana à l'ouest, Bénin à l'est)
    polygon: [
      [0.2000, 9.5000],   // Sud-Ouest : départ à la frontière Ghana (limite avec Centrale)
      [0.0000, 10.5000],  // Nord-Ouest : frontière Ghana jusqu’à la région des Savanes
      [1.2000, 10.5000],  // Nord-Est : extrémité est vers la frontière du Bénin (limite Savanes)
      [1.3000, 9.5000],   // Sud-Est : sur la frontière Bénin à la limite de la Centrale
      [0.2000, 9.5000]    // Fermeture du polygone
    ],
    bounds: {
      minLat: 9.5000,
      maxLat: 10.5000,
      minLng: 0.0000,
      maxLng: 1.3000
    }
  },

  SAVANES: {
    name: 'Savanes',
    // Polygone de la région des Savanes (extrême nord, frontières Ghana, Burkina Faso, Bénin)
    polygon: [
      [0.0000, 10.5000],  // Sud-Ouest : départ à la frontière Ghana (limite avec Kara)
      [-0.1000, 10.9000], // Ouest : remontée le long de la frontière Ghana
      [0.0000, 11.1000],  // Nord-Ouest : tripoint Ghana–Burkina–Togo (frontière Burkina Faso)
      [1.0000, 11.1000],  // Nord-Est : tripoint Burkina–Bénin–Togo (frontière Burkina/Bénin)
      [1.2000, 10.5000],  // Sud-Est : sur la frontière du Bénin (limite avec Kara)
      [0.0000, 10.5000]   // Fermeture du polygone
    ],
    bounds: {
      minLat: 10.5000,
      maxLat: 11.1000,
      minLng: -0.1000,
      maxLng: 1.2000
    }
  },

  LOME: {
    name: 'Grand Lomé',
    // Polygone de la zone spéciale Grand Lomé (dans Maritime)
    polygon: [
      [1.1700, 6.1000],   // Sud-Ouest : frontière Ghana au niveau de Lomé (Aflao)
      [1.3000, 6.1000],   // Sud-Est : côté est de Lomé, vers le lac Togo
      [1.3000, 6.2500],   // Nord-Est : limite nord de l’agglomération de Lomé
      [1.1700, 6.2500],   // Nord-Ouest : limite nord-ouest (vers Agoè)
      [1.1700, 6.1000]    // Fermeture du polygone
    ],
    bounds: {
      minLat: 6.1000,
      maxLat: 6.2500,
      minLng: 1.1700,
      maxLng: 1.3000
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
    
    // Ignorer les côtés horizontaux (même latitude)
    if (yi === yj) {
      continue;
    }
    
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
