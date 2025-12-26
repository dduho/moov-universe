import apiClient from './api';

/**
 * Service de forecasting et prédictions CA
 */
const ForecastingService = {
  /**
   * Obtenir les prévisions CA
   * @param {object} params - Paramètres de prévision
   * @param {string} params.scope - 'global', 'region', 'dealer', 'pdv'
   * @param {string} params.entity_id - ID de l'entité (optionnel)
   * @returns {Promise}
   */
  async getForecast(params = {}) {
    const response = await apiClient.get('/forecasting', { params });
    return response.data;
  },

  /**
   * Obtenir les prévisions globales
   * @returns {Promise}
   */
  async getGlobalForecast() {
    return this.getForecast({ scope: 'global' });
  },

  /**
   * Obtenir les prévisions par région
   * @param {string} region - Nom de la région
   * @returns {Promise}
   */
  async getRegionForecast(region) {
    return this.getForecast({ scope: 'region', entity_id: region });
  },

  /**
   * Obtenir les prévisions par dealer
   * @param {string} dealerName - Nom du dealer
   * @returns {Promise}
   */
  async getDealerForecast(dealerName) {
    return this.getForecast({ scope: 'dealer', entity_id: dealerName });
  },

  /**
   * Obtenir les prévisions pour un PDV
   * @param {string} pdvNumero - Numéro du PDV
   * @returns {Promise}
   */
  async getPdvForecast(pdvNumero) {
    return this.getForecast({ scope: 'pdv', entity_id: pdvNumero });
  }
};

export default ForecastingService;
