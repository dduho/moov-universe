import apiClient from './api';

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

export default {
  /**
   * Detect fraud patterns
   * @param {Object} params - { scope, entity_id, start_date, end_date }
   */
  async detectFraud(params = {}) {
    const response = await apiClient.get('/fraud-detection', { params });
    return response.data;
  },

  /**
   * Export fraud detection alerts to Excel
   * @param {Object} params - { scope, entity_id, start_date, end_date }
   */
  async exportFraudExcel(params = {}) {
    const queryString = new URLSearchParams();
    
    if (params.scope) queryString.append('scope', params.scope);
    if (params.entity_id) queryString.append('entity_id', params.entity_id);
    if (params.start_date) queryString.append('start_date', params.start_date);
    if (params.end_date) queryString.append('end_date', params.end_date);

    // Get the token from localStorage
    const token = localStorage.getItem('token');
    
    // Construct the full URL
    const url = `${API_URL}/fraud-detection/export?${queryString.toString()}`;
    
    // Use fetch with blob response
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
      }
    });

    if (!response.ok) {
      throw new Error('Export failed');
    }

    // Create blob and download
    const blob = await response.blob();
    const downloadUrl = window.URL.createObjectURL(blob);
    
    // Get filename from response headers or use default
    const contentDisposition = response.headers.get('Content-Disposition');
    const filename = contentDisposition 
      ? contentDisposition.split('filename=')[1]?.replace(/"/g, '')
      : `comportements_suspicieux_${new Date().toISOString().split('T')[0]}.xlsx`;
    
    // Create link and trigger download
    const link = document.createElement('a');
    link.href = downloadUrl;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    window.URL.revokeObjectURL(downloadUrl);
  }
};
