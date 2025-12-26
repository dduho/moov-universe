import apiClient from './api';

/**
 * Service de recherche globale
 */
const GlobalSearchService = {
  /**
   * Recherche globale multi-entités
   * @param {string} query - Terme de recherche
   * @param {object} options - Options de recherche
   * @param {string} options.type - Type de recherche: 'all', 'pdv', 'dealer', 'region', 'prefecture', 'commune', 'ville'
   * @param {object} options.filters - Filtres additionnels
   * @param {number} options.limit - Nombre de résultats par catégorie (défaut: 10)
   * @returns {Promise}
   */
  async search(query, options = {}) {
    const params = {
      query,
      type: options.type || 'all',
      limit: options.limit || 10,
    };

    // Add filters if provided
    if (options.filters) {
      params.filters = options.filters;
    }

    const response = await apiClient.get('/search', { params });
    return response.data;
  },

  /**
   * Suggestions de recherche pour autocomplete
   * @param {string} query - Terme de recherche
   * @param {number} limit - Nombre de suggestions (défaut: 8)
   * @returns {Promise}
   */
  async getSuggestions(query, limit = 8) {
    const response = await apiClient.get('/search/suggestions', {
      params: { query, limit }
    });
    return response.data;
  },

  /**
   * Recherche uniquement des PDV
   * @param {string} query - Terme de recherche
   * @param {object} filters - Filtres additionnels
   * @returns {Promise}
   */
  async searchPdv(query, filters = {}) {
    return this.search(query, {
      type: 'pdv',
      filters,
      limit: 20
    });
  },

  /**
   * Recherche uniquement des dealers
   * @param {string} query - Terme de recherche
   * @returns {Promise}
   */
  async searchDealers(query) {
    return this.search(query, {
      type: 'dealer',
      limit: 15
    });
  },

  /**
   * Recherche géographique (régions, préfectures, communes, villes)
   * @param {string} query - Terme de recherche
   * @param {string} geoType - Type géographique: 'region', 'prefecture', 'commune', 'ville'
   * @returns {Promise}
   */
  async searchGeographic(query, geoType = 'all') {
    return this.search(query, {
      type: geoType,
      limit: 15
    });
  }
};

export default GlobalSearchService;
