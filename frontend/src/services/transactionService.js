import api from './api';

export default {
  /**
   * Upload multiple transaction files
   */
  async uploadFiles(files) {
    const formData = new FormData();
    
    for (let i = 0; i < files.length; i++) {
      formData.append('files[]', files[i]);
    }

    return api.post('/transactions/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
  },

  /**
   * Get transaction stats for a specific PDV
   */
  async getStats(pdvId) {
    return api.get(`/pdv/${pdvId}/stats`);
  },
};
