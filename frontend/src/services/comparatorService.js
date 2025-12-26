import api from './api';

const ComparatorService = {
  /**
   * Comparer plusieurs PDV
   */
  async comparePdvs(pdvNumeros, period = 'month', date = null) {
    const response = await api.post('/comparator/compare', {
      type: 'pdv',
      entities: pdvNumeros,
      period,
      date,
    });
    return response.data;
  },

  /**
   * Comparer plusieurs dealers
   */
  async compareDealers(dealerNames, period = 'month', date = null) {
    const response = await api.post('/comparator/compare', {
      type: 'dealer',
      entities: dealerNames,
      period,
      date,
    });
    return response.data;
  },

  /**
   * Comparer plusieurs périodes
   */
  async comparePeriods(periods, period = 'month', date = null) {
    const response = await api.post('/comparator/compare', {
      type: 'period',
      entities: periods,
      period,
      date,
    });
    return response.data;
  },

  /**
   * Rechercher des PDV pour le comparateur
   */
  async searchPdvs(query) {
    const response = await api.get('/point-of-sales', {
      params: {
        search: query,
        per_page: 20,
      },
    });
    return response.data.data;
  },

  /**
   * Rechercher des dealers pour le comparateur
   */
  async searchDealers(query) {
    const response = await api.get('/dealers', {
      params: {
        search: query,
        per_page: 20,
      },
    });
    return response.data.data;
  },
};

export default ComparatorService;
