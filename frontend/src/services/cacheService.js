/**
 * Service de cache local pour les données de carte
 * Utilise localStorage avec expiration automatique
 */
class LocalCacheService {
  constructor() {
    this.prefix = 'moov_cache_';
  }

  /**
   * Calculer la taille d'une donnée en MB
   * @param {any} data - Données à mesurer
   * @returns {number} Taille en MB
   */
  getDataSize(data) {
    return new Blob([JSON.stringify(data)]).size / (1024 * 1024);
  }

  /**
   * Supprimer les N entrées les plus anciennes
   * @param {number} count - Nombre d'entrées à supprimer
   */
  removeOldest(count = 3) {
    try {
      const entries = [];
      for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key && key.startsWith(this.prefix)) {
          try {
            const item = JSON.parse(localStorage.getItem(key));
            entries.push({ key, timestamp: item.timestamp || 0 });
          } catch (e) {
            // Entrée corrompue, la supprimer
            localStorage.removeItem(key);
          }
        }
      }
      
      // Trier par timestamp et supprimer les plus anciennes
      entries.sort((a, b) => a.timestamp - b.timestamp);
      for (let i = 0; i < Math.min(count, entries.length); i++) {
        localStorage.removeItem(entries[i].key);
        console.log(`[Cache] Suppression entrée ancienne: ${entries[i].key}`);
      }
    } catch (e) {
      console.error('[Cache] Erreur lors de la suppression des anciennes entrées:', e);
    }
  }

  /**
   * Définir une valeur dans le cache avec expiration
   * @param {string} key - Clé du cache
   * @param {any} value - Valeur à stocker
   * @param {number} ttlMinutes - Durée de vie en minutes (défaut: 30)
   * @param {number} maxSizeMB - Taille maximale en MB (défaut: 2)
   * @returns {boolean} True si mise en cache réussie
   */
  set(key, value, ttlMinutes = 30, maxSizeMB = 2) {
    try {
      const item = {
        value: value,
        expiry: Date.now() + ttlMinutes * 60 * 1000,
        timestamp: Date.now()
      };
      
      const itemStr = JSON.stringify(item);
      const sizeMB = new Blob([itemStr]).size / (1024 * 1024);
      
      // Vérifier si les données ne sont pas trop grandes
      if (sizeMB > maxSizeMB) {
        console.warn(`[Cache] Données trop volumineuses (${sizeMB.toFixed(2)}MB > ${maxSizeMB}MB), cache ignoré pour: ${key}`);
        return false;
      }
      
      localStorage.setItem(this.prefix + key, itemStr);
      return true;
    } catch (e) {
      console.warn('[Cache] Erreur lors de la mise en cache:', e);
      // Si localStorage est plein, on supprime les anciennes entrées
      if (e.name === 'QuotaExceededError') {
        console.warn('[Cache] Quota dépassé, nettoyage du cache...');
        this.cleanup();
        
        // Supprimer aussi les entrées les plus anciennes
        this.removeOldest(3);
        
        // Réessayer UNE SEULE fois
        try {
          const item = {
            value: value,
            expiry: Date.now() + ttlMinutes * 60 * 1000,
            timestamp: Date.now()
          };
          localStorage.setItem(this.prefix + key, JSON.stringify(item));
          console.log('[Cache] Mise en cache réussie après nettoyage');
          return true;
        } catch (e2) {
          console.error('[Cache] Impossible de mettre en cache même après nettoyage:', e2);
          return false;
        }
      }
      return false;
    }
  }

  /**
   * Récupérer une valeur du cache
   * @param {string} key - Clé du cache
   * @returns {any|null} Valeur ou null si expirée/inexistante
   */
  get(key) {
    try {
      const itemStr = localStorage.getItem(this.prefix + key);
      if (!itemStr) {
        return null;
      }

      const item = JSON.parse(itemStr);
      
      // Vérifier l'expiration
      if (Date.now() > item.expiry) {
        localStorage.removeItem(this.prefix + key);
        return null;
      }

      return item.value;
    } catch (e) {
      console.warn('Erreur lors de la lecture du cache:', e);
      return null;
    }
  }

  /**
   * Supprimer une entrée du cache
   * @param {string} key - Clé du cache
   */
  remove(key) {
    try {
      localStorage.removeItem(this.prefix + key);
    } catch (e) {
      console.warn('Erreur lors de la suppression du cache:', e);
    }
  }

  /**
   * Vider tout le cache de l'application
   */
  clear() {
    try {
      const keys = Object.keys(localStorage);
      keys.forEach(key => {
        if (key.startsWith(this.prefix)) {
          localStorage.removeItem(key);
        }
      });
    } catch (e) {
      console.warn('Erreur lors du vidage du cache:', e);
    }
  }

  /**
   * Nettoyer les entrées expirées
   */
  cleanup() {
    try {
      const keys = Object.keys(localStorage);
      const now = Date.now();
      
      keys.forEach(key => {
        if (key.startsWith(this.prefix)) {
          try {
            const itemStr = localStorage.getItem(key);
            if (itemStr) {
              const item = JSON.parse(itemStr);
              if (now > item.expiry) {
                localStorage.removeItem(key);
              }
            }
          } catch (e) {
            // Supprimer les entrées corrompues
            localStorage.removeItem(key);
          }
        }
      });
    } catch (e) {
      console.warn('Erreur lors du nettoyage du cache:', e);
    }
  }

  /**
   * Obtenir la taille totale du cache en Ko
   * @returns {number} Taille en Ko
   */
  getSize() {
    try {
      let total = 0;
      const keys = Object.keys(localStorage);
      
      keys.forEach(key => {
        if (key.startsWith(this.prefix)) {
          const item = localStorage.getItem(key);
          if (item) {
            total += item.length;
          }
        }
      });
      
      return (total / 1024).toFixed(2);
    } catch (e) {
      return 0;
    }
  }

  /**
   * Obtenir des statistiques sur le cache
   * @returns {Object} Statistiques
   */
  getStats() {
    try {
      const keys = Object.keys(localStorage);
      const cacheKeys = keys.filter(k => k.startsWith(this.prefix));
      const now = Date.now();
      
      let expired = 0;
      let valid = 0;
      
      cacheKeys.forEach(key => {
        try {
          const itemStr = localStorage.getItem(key);
          if (itemStr) {
            const item = JSON.parse(itemStr);
            if (now > item.expiry) {
              expired++;
            } else {
              valid++;
            }
          }
        } catch (e) {
          expired++;
        }
      });
      
      return {
        total: cacheKeys.length,
        valid: valid,
        expired: expired,
        size: this.getSize() + ' Ko'
      };
    } catch (e) {
      return {
        total: 0,
        valid: 0,
        expired: 0,
        size: '0 Ko'
      };
    }
  }
}

export default new LocalCacheService();
