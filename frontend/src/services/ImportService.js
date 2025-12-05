import api from './api';

export default {
  /**
   * Prévisualiser l'import (dry run)
   */
  async previewImport(file, organizationId) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('organization_id', organizationId);

    const response = await api.post('/point-of-sales/import/preview', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  /**
   * Importer les PDV
   */
  async importPDV(file, organizationId, skipDuplicates = true) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('organization_id', organizationId);
    formData.append('skip_duplicates', skipDuplicates ? '1' : '0');

    const response = await api.post('/point-of-sales/import', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    });
    return response.data;
  },

  /**
   * Télécharger le modèle Excel
   */
  async downloadTemplate() {
    const response = await api.get('/point-of-sales/import/template', {
      responseType: 'blob',
    });
    
    // Créer un lien de téléchargement
    const url = window.URL.createObjectURL(new Blob([response.data]));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', 'modele_import_pdv.xlsx');
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  },
};
