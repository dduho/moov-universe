import * as XLSX from 'xlsx';

const ExportService = {
  /**
   * Export data to Excel file
   * @param {Array} data - Array of objects to export
   * @param {string} filename - Name of the file (without extension)
   * @param {string} sheetName - Name of the worksheet
   */
  exportToExcel(data, filename = 'export', sheetName = 'Data') {
    // Create worksheet
    const worksheet = XLSX.utils.json_to_sheet(data);
    
    // Create workbook
    const workbook = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(workbook, worksheet, sheetName);
    
    // Generate Excel file and trigger download
    XLSX.writeFile(workbook, `${filename}.xlsx`);
  },

  /**
   * Export data to CSV file
   * @param {Array} data - Array of objects to export
   * @param {string} filename - Name of the file (without extension)
   */
  exportToCSV(data, filename = 'export') {
    // Create worksheet
    const worksheet = XLSX.utils.json_to_sheet(data);
    
    // Convert to CSV
    const csv = XLSX.utils.sheet_to_csv(worksheet);
    
    // Create blob and trigger download
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', `${filename}.csv`);
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  },

  /**
   * Export PDV data with custom formatting
   * @param {Array} pdvList - List of PDV objects
   * @param {string} format - 'excel' or 'csv'
   */
  exportPDV(pdvList, format = 'excel') {
    // Transform data for export - matching import template headers exactly
    const exportData = pdvList.map(pdv => ({
      'DEALER_NAME': pdv.dealer_name || '',
      'NUMERO_FLOOZ': pdv.numero_flooz || '',
      'SHORTCODE': pdv.shortcode || '',
      'NOM DU POINT': pdv.nom_point || '',
      'PROFIL': pdv.profil || '',
      'FIRSTNAME': pdv.firstname || '',
      'LASTNAME': pdv.lastname || '',
      'GENDER': pdv.gender || '',
      'IDDESCRIPTION': pdv.id_description || '',
      'IDNUMBER': pdv.id_number || '',
      'IDEXPIRYDATE': pdv.id_expiry_date || '',
      'NATIONALITY': pdv.nationality || '',
      'PROFESSION': pdv.profession || '',
      "TYPE D'ACTIVITE": pdv.type_activite || '',
      'LOCALISATION': pdv.localisation || '',
      'REGION': pdv.region || '',
      'PREFECTURE': pdv.prefecture || '',
      'COMMUNE': pdv.commune || '',
      'CANTON': pdv.canton || '',
      'QUARTIER': pdv.quartier || '',
      'VILLE': pdv.ville || '',
      'LATITUDE': pdv.latitude || '',
      'LONGITUDE': pdv.longitude || '',
      'PROPRIETAIRE': pdv.numero_proprietaire || '',
      'AUTRE CONTACT': pdv.autre_contact || '',
      'SEXE DU GERANT': pdv.sexe_gerant || '',
      'NIF': pdv.nif || '',
      'REGIME FISCAL': pdv.regime_fiscal || '',
      'SUPPORT DE VISIBILITE': pdv.support_visibilite || '',
      'ETAT DU SUPPORT DE VISIBILITE': pdv.etat_support || '',
      'NUMERO_CAGNT': pdv.numero_cagnt || ''
    }));

    const filename = `pdv_export_${new Date().toISOString().split('T')[0]}`;
    
    if (format === 'excel') {
      this.exportToExcel(exportData, filename, 'Points de Vente');
    } else {
      this.exportToCSV(exportData, filename);
    }
  },

  /**
   * Export users data with custom formatting
   * @param {Array} userList - List of user objects
   * @param {string} format - 'excel' or 'csv'
   */
  exportUsers(userList, format = 'excel') {
    const exportData = userList.map(user => ({
      'ID': user.id,
      'Nom': user.name,
      'Email': user.email,
      'Téléphone': user.phone || '',
      'Rôle': user.role?.display_name || '',
      'Organisation': user.organization?.name || '',
      'Statut': user.is_active ? 'Actif' : 'Inactif',
      'Date de création': new Date(user.created_at).toLocaleDateString('fr-FR'),
      'Dernière connexion': user.last_login_at ? new Date(user.last_login_at).toLocaleDateString('fr-FR') : 'Jamais'
    }));

    const filename = `users_export_${new Date().toISOString().split('T')[0]}`;
    
    if (format === 'excel') {
      this.exportToExcel(exportData, filename, 'Utilisateurs');
    } else {
      this.exportToCSV(exportData, filename);
    }
  },

  /**
   * Export dealers data with custom formatting
   * @param {Array} dealerList - List of dealer objects
   * @param {string} format - 'excel' or 'csv'
   */
  exportDealers(dealerList, format = 'excel') {
    const exportData = dealerList.map(dealer => ({
      'ID': dealer.id,
      'Nom': dealer.name,
      'Type': dealer.type === 'direct' ? 'Direct' : 'Sous-distributeur',
      'Code': dealer.code || '',
      'Email': dealer.contact_email,
      'Téléphone': dealer.contact_phone,
      'Adresse': dealer.address || '',
      'Nombre de PDV': dealer.pdv_count || 0,
      'Statut': dealer.is_active ? 'Actif' : 'Inactif',
      'Date de création': new Date(dealer.created_at).toLocaleDateString('fr-FR')
    }));

    const filename = `dealers_export_${new Date().toISOString().split('T')[0]}`;
    
    if (format === 'excel') {
      this.exportToExcel(exportData, filename, 'Dealers');
    } else {
      this.exportToCSV(exportData, filename);
    }
  },

  /**
   * Read Excel/CSV file and parse data
   * @param {File} file - File object from input
   * @returns {Promise<Array>} Parsed data as array of objects
   */
  async importFile(file) {
    return new Promise((resolve, reject) => {
      const reader = new FileReader();
      
      reader.onload = (e) => {
        try {
          const data = new Uint8Array(e.target.result);
          const workbook = XLSX.read(data, { type: 'array' });
          
          // Get first sheet
          const firstSheetName = workbook.SheetNames[0];
          const worksheet = workbook.Sheets[firstSheetName];
          
          // Convert to JSON
          const jsonData = XLSX.utils.sheet_to_json(worksheet);
          
          resolve(jsonData);
        } catch (error) {
          reject(new Error('Erreur lors de la lecture du fichier: ' + error.message));
        }
      };
      
      reader.onerror = () => {
        reject(new Error('Erreur lors de la lecture du fichier'));
      };
      
      reader.readAsArrayBuffer(file);
    });
  },

  /**
   * Validate imported PDV data
   * @param {Array} data - Imported data
   * @returns {Object} { valid: Array, errors: Array }
   */
  validatePDVImport(data) {
    const valid = [];
    const errors = [];

    data.forEach((row, index) => {
      const rowErrors = [];
      const lineNumber = index + 2; // +2 because index starts at 0 and header is row 1

      // Required fields validation
      if (!row['Nom du PDV']) rowErrors.push('Nom du PDV manquant');
      if (!row['Numéro Flooz']) rowErrors.push('Numéro Flooz manquant');
      if (!row['Région']) rowErrors.push('Région manquante');
      if (!row['Ville']) rowErrors.push('Ville manquante');
      if (!row['Quartier']) rowErrors.push('Quartier manquant');
      if (!row['Latitude']) rowErrors.push('Latitude manquante');
      if (!row['Longitude']) rowErrors.push('Longitude manquante');
      if (!row['Propriétaire']) rowErrors.push('Propriétaire manquant');

      // Format validation
      if (row['Latitude'] && (isNaN(parseFloat(row['Latitude'])) || parseFloat(row['Latitude']) < -90 || parseFloat(row['Latitude']) > 90)) {
        rowErrors.push('Latitude invalide (doit être entre -90 et 90)');
      }
      if (row['Longitude'] && (isNaN(parseFloat(row['Longitude'])) || parseFloat(row['Longitude']) < -180 || parseFloat(row['Longitude']) > 180)) {
        rowErrors.push('Longitude invalide (doit être entre -180 et 180)');
      }

      if (rowErrors.length > 0) {
        errors.push({
          line: lineNumber,
          data: row,
          errors: rowErrors
        });
      } else {
        valid.push({
          point_name: row['Nom du PDV'],
          flooz_number: row['Numéro Flooz'],
          shortcode: row['Shortcode'] || '',
          region: row['Région'],
          prefecture: row['Préfecture'] || '',
          commune: row['Commune'] || '',
          city: row['Ville'],
          neighborhood: row['Quartier'],
          latitude: parseFloat(row['Latitude']),
          longitude: parseFloat(row['Longitude']),
          owner_name: row['Propriétaire'],
          owner_phone: row['Téléphone'] || '',
          nif: row['NIF'] || '',
          tax_regime: row['Régime fiscal'] || ''
        });
      }
    });

    return { valid, errors };
  },

  /**
   * Generate Excel template for PDV import
   */
  generatePDVTemplate() {
    const template = [
      {
        'Nom du PDV': 'Exemple PDV',
        'Numéro Flooz': '+228 90 00 00 00',
        'Shortcode': '1234',
        'Propriétaire': 'Jean Dupont',
        'Téléphone': '+228 91 00 00 00',
        'Région': 'Maritime',
        'Préfecture': 'Golfe',
        'Commune': 'Lomé',
        'Ville': 'Lomé',
        'Quartier': 'Bè',
        'Latitude': '6.1256',
        'Longitude': '1.2223',
        'NIF': '123456789',
        'Régime fiscal': 'Régime réel'
      }
    ];

    this.exportToExcel(template, 'template_import_pdv', 'Template');
  }
};

export default ExportService;
