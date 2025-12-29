import api from './api';

export default {
  /**
   * Upload multiple transaction files
   */
  async uploadFiles(files, onUploadProgress = null) {
    const formData = new FormData();
    
    for (let i = 0; i < files.length; i++) {
      formData.append('files[]', files[i]);
    }

    return api.post('/transactions/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      timeout: 600000, // 10 minutes timeout pour fichiers très volumineux
      onUploadProgress: onUploadProgress || undefined,
    });
  },

  /**
   * Get transaction stats for a specific PDV
   */
  async getStats(pdvId, params = {}) {
    return api.get(`/pdv/${pdvId}/stats`, { params });
  },
};
