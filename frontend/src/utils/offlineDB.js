// Base de données IndexedDB pour le mode hors ligne
const DB_NAME = 'moov-offline-db';
const DB_VERSION = 1;

// Stores disponibles
const STORES = {
  PDV: 'pdv-list',
  PENDING_ACTIONS: 'pending-actions',
  CACHED_DATA: 'cached-data',
  SYNC_QUEUE: 'sync-queue'
};

class OfflineDB {
  constructor() {
    this.db = null;
  }

  // Initialiser la base de données
  async init() {
    return new Promise((resolve, reject) => {
      const request = indexedDB.open(DB_NAME, DB_VERSION);

      request.onerror = () => reject(request.error);
      request.onsuccess = () => {
        this.db = request.result;
        resolve(this.db);
      };

      request.onupgradeneeded = (event) => {
        const db = event.target.result;

        // Store pour la liste des PDV
        if (!db.objectStoreNames.contains(STORES.PDV)) {
          const pdvStore = db.createObjectStore(STORES.PDV, { keyPath: 'id' });
          pdvStore.createIndex('numero_flooz', 'numero_flooz', { unique: true });
          pdvStore.createIndex('region', 'region', { unique: false });
        }

        // Store pour les actions en attente de synchronisation
        if (!db.objectStoreNames.contains(STORES.PENDING_ACTIONS)) {
          const actionsStore = db.createObjectStore(STORES.PENDING_ACTIONS, { 
            keyPath: 'id', 
            autoIncrement: true 
          });
          actionsStore.createIndex('timestamp', 'timestamp', { unique: false });
          actionsStore.createIndex('type', 'type', { unique: false });
        }

        // Store pour les données en cache
        if (!db.objectStoreNames.contains(STORES.CACHED_DATA)) {
          const cacheStore = db.createObjectStore(STORES.CACHED_DATA, { keyPath: 'key' });
          cacheStore.createIndex('expiry', 'expiry', { unique: false });
        }

        // Store pour la file de synchronisation
        if (!db.objectStoreNames.contains(STORES.SYNC_QUEUE)) {
          const syncStore = db.createObjectStore(STORES.SYNC_QUEUE, { 
            keyPath: 'id', 
            autoIncrement: true 
          });
          syncStore.createIndex('status', 'status', { unique: false });
        }
      };
    });
  }

  // Méthodes génériques pour toutes les stores
  async add(storeName, data) {
    const tx = this.db.transaction(storeName, 'readwrite');
    const store = tx.objectStore(storeName);
    return new Promise((resolve, reject) => {
      const request = store.add(data);
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async put(storeName, data) {
    const tx = this.db.transaction(storeName, 'readwrite');
    const store = tx.objectStore(storeName);
    return new Promise((resolve, reject) => {
      const request = store.put(data);
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async get(storeName, key) {
    const tx = this.db.transaction(storeName, 'readonly');
    const store = tx.objectStore(storeName);
    return new Promise((resolve, reject) => {
      const request = store.get(key);
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async getAll(storeName) {
    const tx = this.db.transaction(storeName, 'readonly');
    const store = tx.objectStore(storeName);
    return new Promise((resolve, reject) => {
      const request = store.getAll();
      request.onsuccess = () => resolve(request.result);
      request.onerror = () => reject(request.error);
    });
  }

  async delete(storeName, key) {
    const tx = this.db.transaction(storeName, 'readwrite');
    const store = tx.objectStore(storeName);
    return new Promise((resolve, reject) => {
      const request = store.delete(key);
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  async clear(storeName) {
    const tx = this.db.transaction(storeName, 'readwrite');
    const store = tx.objectStore(storeName);
    return new Promise((resolve, reject) => {
      const request = store.clear();
      request.onsuccess = () => resolve();
      request.onerror = () => reject(request.error);
    });
  }

  // Méthodes spécifiques pour les PDV
  async savePDVList(pdvList) {
    const tx = this.db.transaction(STORES.PDV, 'readwrite');
    const store = tx.objectStore(STORES.PDV);
    
    // Effacer les anciennes données
    await this.clear(STORES.PDV);
    
    // Ajouter les nouvelles
    for (const pdv of pdvList) {
      store.put(pdv);
    }
    
    return new Promise((resolve, reject) => {
      tx.oncomplete = () => resolve();
      tx.onerror = () => reject(tx.error);
    });
  }

  async getPDVList() {
    return this.getAll(STORES.PDV);
  }

  async searchPDV(query) {
    const allPDV = await this.getPDVList();
    const lowerQuery = query.toLowerCase();
    
    return allPDV.filter(pdv => 
      pdv.nom_pdv?.toLowerCase().includes(lowerQuery) ||
      pdv.numero_flooz?.toLowerCase().includes(lowerQuery) ||
      pdv.region?.toLowerCase().includes(lowerQuery)
    );
  }

  // Méthodes pour les actions en attente
  async addPendingAction(action) {
    return this.add(STORES.PENDING_ACTIONS, {
      ...action,
      timestamp: Date.now(),
      status: 'pending'
    });
  }

  async getPendingActions() {
    return this.getAll(STORES.PENDING_ACTIONS);
  }

  async deletePendingAction(id) {
    return this.delete(STORES.PENDING_ACTIONS, id);
  }

  // Méthodes pour le cache de données
  async cacheData(key, data, expiryMinutes = 60) {
    const expiry = Date.now() + (expiryMinutes * 60 * 1000);
    return this.put(STORES.CACHED_DATA, {
      key,
      data,
      expiry,
      timestamp: Date.now()
    });
  }

  async getCachedData(key) {
    const cached = await this.get(STORES.CACHED_DATA, key);
    
    if (!cached) return null;
    
    // Vérifier l'expiration
    if (cached.expiry < Date.now()) {
      await this.delete(STORES.CACHED_DATA, key);
      return null;
    }
    
    return cached.data;
  }

  async clearExpiredCache() {
    const allCached = await this.getAll(STORES.CACHED_DATA);
    const now = Date.now();
    
    for (const item of allCached) {
      if (item.expiry < now) {
        await this.delete(STORES.CACHED_DATA, item.key);
      }
    }
  }

  // File de synchronisation
  async addToSyncQueue(data) {
    return this.add(STORES.SYNC_QUEUE, {
      ...data,
      timestamp: Date.now(),
      status: 'pending',
      retries: 0
    });
  }

  async getSyncQueue() {
    return this.getAll(STORES.SYNC_QUEUE);
  }

  async updateSyncItem(id, updates) {
    const item = await this.get(STORES.SYNC_QUEUE, id);
    if (item) {
      return this.put(STORES.SYNC_QUEUE, { ...item, ...updates });
    }
  }

  async deleteSyncItem(id) {
    return this.delete(STORES.SYNC_QUEUE, id);
  }
}

// Instance singleton
const offlineDB = new OfflineDB();

export { offlineDB, STORES };
