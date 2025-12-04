// Liste complète des quartiers du Togo par ville
// Région > Préfecture > Commune > Ville > Quartiers

export const neighborhoods = {
  MARITIME: {
    Agoè_Nyivé: {
      Agoè_Nyivé_1: {
        Agoè: ['Agoè', 'Agnidokoui', 'Kasao', 'Sogbossito', 'Agoè-Logopé']
      },
      Agoè_Nyivé_2: {
        Agoè: ['Légbassito', 'Djidjolé', 'Kélégougan', 'Akodésséwa']
      },
      Agoè_Nyivé_3: {
        Agoè: ['Cacavéli', 'Agbalépédogan', 'Adidogomé', 'Totsi']
      },
      Agoè_Nyivé_4: {
        Agoè: ['Sémassi', 'Zanguéra', 'Agoè-Assiyéyé', 'Kagomé']
      },
      Agoè_Nyivé_5: {
        Agoè: ['Togblékopé', 'Démakpoé', 'Davié', 'Aképé-Kondji']
      },
      Agoè_Nyivé_6: {
        Agoè: ['Aképé', 'Adidogomé', 'Agbalépédogan-Ouest', 'Djagblé']
      }
    },
    Golfe: {
      Golfe_1: {
        Lomé: ['Lomé-Centre', 'Tokoin', 'Adoboukomé', 'Nyekonakpoé', 'Gbényédji', 'Kodjoviakopé']
      },
      Golfe_2: {
        Lomé: ['Bè', 'Bè-Kpéhénou', 'Bè-Klikamé', 'Bè-Apéyémé']
      },
      Golfe_3: {
        Lomé: ['Ablogamé', 'Hanoukopé', 'Aflao-Sagbado', 'Agoè-Nyivé']
      },
      Golfe_4: {
        Lomé: ['Akodésséwa', 'Baguida', 'Aného-Gare', 'Agbalepedo']
      },
      Golfe_5: {
        Lomé: ['Kanyikopé', 'Kégué', 'Adidogomé-Assiyéyé']
      },
      Golfe_6: {
        Lomé: ['Amoutivé', 'Agoè-Logopé', 'Démakpoé']
      },
      Golfe_7: {
        Lomé: ['Gbossimé', 'Casablanca', 'Bè-Kpota']
      }
    }
  }
};

// Fonction utilitaire pour rechercher des quartiers
export function searchNeighborhoods(region, prefecture, commune, city, query = '') {
  if (!region || !prefecture || !commune || !city) return [];
  
  const neighborhoodsByCity = neighborhoods[region]?.[prefecture]?.[commune] || {};
  const cityNeighborhoods = neighborhoodsByCity[city] || [];
  
  if (!query) return cityNeighborhoods;
  
  const searchTerm = query.toLowerCase().trim();
  return cityNeighborhoods.filter(neighborhood => 
    neighborhood.toLowerCase().includes(searchTerm)
  );
}

// Fonction pour obtenir tous les quartiers d'une ville
export function getNeighborhoodsByCity(region, prefecture, commune, city) {
  const neighborhoodsByCity = neighborhoods[region]?.[prefecture]?.[commune] || {};
  return neighborhoodsByCity[city] || [];
}
