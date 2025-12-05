import api from './api';

export default {
  /**
   * Récupérer toutes les tâches
   */
  async getTasks(params = {}) {
    const response = await api.get('/tasks', { params });
    return response.data;
  },

  /**
   * Récupérer une tâche spécifique
   */
  async getTask(id) {
    const response = await api.get(`/tasks/${id}`);
    return response.data;
  },

  /**
   * Créer une nouvelle tâche (Admin uniquement)
   */
  async createTask(data) {
    const response = await api.post('/tasks', data);
    return response.data;
  },

  /**
   * Marquer une tâche comme complétée
   */
  async completeTask(id) {
    const response = await api.post(`/tasks/${id}/complete`);
    return response.data;
  },

  /**
   * Valider une tâche (Admin uniquement)
   */
  async validateTask(id) {
    const response = await api.post(`/tasks/${id}/validate`);
    return response.data;
  },

  /**
   * Demander une révision (Admin uniquement)
   */
  async requestRevision(id, feedback) {
    const response = await api.post(`/tasks/${id}/request-revision`, { feedback });
    return response.data;
  },

  /**
   * Supprimer une tâche (Admin uniquement)
   */
  async deleteTask(id) {
    const response = await api.delete(`/tasks/${id}`);
    return response.data;
  },

  /**
   * Récupérer les commerciaux d'un dealer (Admin uniquement)
   */
  async getCommercialsByDealer(pointOfSaleId) {
    const response = await api.get(`/tasks/commercials/${pointOfSaleId}`);
    return response.data;
  }
};
