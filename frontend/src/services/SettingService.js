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
};

export default SettingService;
