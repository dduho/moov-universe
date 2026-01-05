import api from './api';

const SettingService = {
  /**
   * Récupérer tous les paramètres
   */
  async getAll() {
    const response = await api.get('/settings');
    return response.data;
  },

  /**
   * Récupérer un paramètre spécifique
   */
  async get(key) {
    const response = await api.get(`/settings/${key}`);
    return response.data;
  },

  /**
   * Mettre à jour un paramètre
   */
  async update(key, value) {
    const response = await api.put(`/settings/${key}`, { value });
    return response.data;
  },

  /**
   * Récupérer les paramètres de cache
   */
  async getCacheSettings() {
    const { data } = await api.get('/settings/cache');
    return data;
  },

  /**
   * Mettre à jour un paramètre de cache
   */
  async updateCacheSetting(widget, payload) {
    return api.post(`/settings/cache/${widget}`, payload);
  },

  /**
   * Effacer un cache
   */
  async clearCache(widget) {
    return api.post(`/settings/cache/${widget}/clear`);
  },

  /**
   * Effacer tous les caches
   */
  async clearAllCaches() {
    return api.post('/settings/cache/clear-all');
  },
};

export default SettingService;
