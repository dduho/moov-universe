import api from './api';

export default {
  /**
   * Récupérer les notes d'un PDV
   */
  async getNotes(pointOfSaleId, page = 1, perPage = 10) {
    const response = await api.get(`/point-of-sales/${pointOfSaleId}/notes`, {
      params: { page, per_page: perPage }
    });
    return response.data;
  },

  /**
   * Créer une nouvelle note
   */
  async createNote(pointOfSaleId, data) {
    const response = await api.post(`/point-of-sales/${pointOfSaleId}/notes`, data);
    return response.data;
  },

  /**
   * Mettre à jour une note
   */
  async updateNote(pointOfSaleId, noteId, data) {
    const response = await api.put(`/point-of-sales/${pointOfSaleId}/notes/${noteId}`, data);
    return response.data;
  },

  /**
   * Supprimer une note
   */
  async deleteNote(pointOfSaleId, noteId) {
    const response = await api.delete(`/point-of-sales/${pointOfSaleId}/notes/${noteId}`);
    return response.data;
  },

  /**
   * Épingler/désépingler une note
   */
  async togglePin(pointOfSaleId, noteId) {
    const response = await api.post(`/point-of-sales/${pointOfSaleId}/notes/${noteId}/toggle-pin`);
    return response.data;
  },
};
