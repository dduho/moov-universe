<template>
  <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
      <div class="flex items-center gap-3">
        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center">
          <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
        </div>
        <div>
          <h3 class="text-lg font-bold text-gray-900">Géolocalisation Avancée</h3>
          <p class="text-sm text-gray-600">Heatmap, clustering et zones à potentiel</p>
        </div>
      </div>

      <!-- Summary -->
      <div v-if="!loading && summary" class="flex items-center gap-2">
        <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-semibold">
          {{ summary.total_pdv }} PDV
        </span>
        <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">
          {{ formatAmount(summary.total_ca) }} FCFA CA
        </span>
      </div>
    </div>

    <!-- Filters -->
    <div v-if="!loading" class="grid grid-cols-1 md:grid-cols-5 gap-3 mb-4">
      <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1">Région</label>
        <select v-model="filters.region" @change="loadGeoData" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
          <option value="">Toutes</option>
          <option v-for="region in availableRegions" :key="region" :value="region">{{ region }}</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1">Statut</label>
        <select v-model="filters.status" @change="loadGeoData" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
          <option value="">Tous</option>
          <option value="validated">Validé</option>
          <option value="pending">En attente</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1">CA Min (FCFA)</label>
        <input v-model.number="filters.min_ca" @change="loadGeoData" type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="0" />
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1">CA Max (FCFA)</label>
        <input v-model.number="filters.max_ca" @change="loadGeoData" type="number" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Illimité" />
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-700 mb-1">Période (jours)</label>
        <select v-model.number="filters.days" @change="updateDates" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
          <option :value="7">7 jours</option>
          <option :value="30">30 jours</option>
          <option :value="90">90 jours</option>
        </select>
      </div>
    </div>

    <!-- Layer Controls -->
    <div v-if="!loading" class="flex flex-wrap gap-2 mb-4">
      <button
        @click="toggleLayer('heatmap')"
        :class="[
          'px-3 py-1 rounded-lg text-sm font-semibold transition-all',
          layers.heatmap ? 'bg-red-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
        ]"
      >
        🔥 Heatmap CA
      </button>
      <button
        @click="toggleLayer('clusters')"
        :class="[
          'px-3 py-1 rounded-lg text-sm font-semibold transition-all',
          layers.clusters ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
        ]"
      >
        📍 Clusters PDV
      </button>
      <button
        @click="toggleLayer('potential')"
        :class="[
          'px-3 py-1 rounded-lg text-sm font-semibold transition-all',
          layers.potential ? 'bg-yellow-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
        ]"
      >
        ⭐ Zones à Potentiel
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-12">
      <div class="animate-spin rounded-full h-12 w-12 border-b-4 border-green-500 mx-auto mb-4"></div>
      <p class="text-sm text-gray-600 font-semibold">Chargement des données géographiques...</p>
    </div>

    <!-- Error -->
    <div v-else-if="error" class="text-center py-12">
      <svg class="w-12 h-12 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      <p class="text-sm text-red-600 font-semibold">{{ error }}</p>
      <button @click="loadGeoData" class="mt-4 px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
        Réessayer
      </button>
    </div>

    <!-- Map Container -->
    <div v-show="!loading && !error && isInitialized">
      <div id="pdv-map" class="w-full h-[600px] rounded-xl border-2 border-gray-200 mb-4"></div>

      <!-- Legend -->
      <div class="flex items-center justify-between text-xs text-gray-600">
        <div class="flex items-center gap-4">
          <div v-if="layers.heatmap" class="flex items-center gap-2">
            <div class="w-20 h-4 rounded" style="background: linear-gradient(to right, blue, cyan, lime, yellow, red)"></div>
            <span>CA: Faible → Élevé</span>
          </div>
          <div v-if="layers.potential" class="flex items-center gap-2">
            <div class="w-4 h-4 rounded-full bg-yellow-400"></div>
            <span>Zones à fort potentiel</span>
          </div>
        </div>
        <button @click="loadGeoData" class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 font-semibold transition-colors">
          Actualiser
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.heat';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import geolocationService from '../services/geolocationService';

// Fix Leaflet default icon paths
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

export default {
  name: 'GeolocationWidget',
  props: {
    isActive: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      loading: false,
      error: null,
      map: null,
      pdvData: [],
      potentialZones: [],
      summary: null,
      availableRegions: [],
      filters: {
        region: '',
        status: 'validated',
        min_ca: 0,
        max_ca: null,
        days: 30,
        start_date: null,
        end_date: null,
      },
      layers: {
        heatmap: true,
        clusters: true,
        potential: false,
      },
      mapLayers: {
        heatmap: null,
        clusters: null,
        potential: null,
      },
      isInitialized: false,
    };
  },
  mounted() {
    // Initialize only if active
    if (this.isActive) {
      this.initialize();
    }
  },
  beforeUnmount() {
    if (this.map) {
      this.map.remove();
    }
  },
  watch: {
    isActive(newValue) {
      if (newValue && !this.isInitialized) {
        // Use nextTick to ensure DOM is ready
        this.$nextTick(() => {
          this.initialize();
        });
      }
    },
    'filters.days'() {
      if (this.isInitialized) {
        this.updateDates();
      }
    },
    'filters.region'() {
      if (this.isInitialized) {
        this.loadGeoData();
      }
    },
    'filters.status'() {
      if (this.isInitialized) {
        this.loadGeoData();
      }
    },
    'filters.min_ca'() {
      if (this.isInitialized) {
        this.loadGeoData();
      }
    },
    'filters.max_ca'() {
      if (this.isInitialized) {
        this.loadGeoData();
      }
    },
  },
  methods: {
    initialize() {
      if (this.isInitialized) return;
      
      this.isInitialized = true;
      this.updateDates();
      
      // Wait for next tick to ensure DOM is ready
      this.$nextTick(() => {
        // Double check the container exists
        const container = document.getElementById('pdv-map');
        if (container) {
          this.initMap();
          this.loadGeoData();
        } else {
          console.error('Map container not found');
          this.error = 'Impossible d\'initialiser la carte';
        }
      });
    },
    initMap() {
      // Don't initialize if map already exists
      if (this.map) {
        return;
      }

      // Initialize Leaflet map centered on Benin
      try {
        this.map = L.map('pdv-map', {
          preferCanvas: true, // Use Canvas renderer for better performance
        }).setView([9.30769, 2.315834], 7);
      } catch (e) {
        console.error('Error initializing map:', e);
        this.error = 'Erreur lors de l\'initialisation de la carte';
        return;
      }

      // Optimize canvas for frequent pixel reads (fixes willReadFrequently warning)
      setTimeout(() => {
        const canvasContainer = this.map?.getContainer()?.querySelector('.leaflet-zoom-animated');
        if (canvasContainer) {
          const canvases = canvasContainer.querySelectorAll('canvas');
          canvases.forEach(canvas => {
            try {
              const ctx = canvas.getContext('2d', { willReadFrequently: true });
            } catch (e) {
              console.warn('Could not set willReadFrequently on canvas:', e);
            }
          });
        }
      }, 100);

      // Add OpenStreetMap tile layer
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 18,
      }).addTo(this.map);

      // Force map to resize after initialization
      setTimeout(() => {
        if (this.map) {
          this.map.invalidateSize();
        }
      }, 200);
    },
    updateDates() {
      const endDate = new Date();
      const startDate = new Date();
      startDate.setDate(startDate.getDate() - this.filters.days);
      
      this.filters.end_date = endDate.toISOString().split('T')[0];
      this.filters.start_date = startDate.toISOString().split('T')[0];
      
      this.loadGeoData();
    },
    async loadGeoData() {
      this.loading = true;
      this.error = null;

      try {
        const params = {
          start_date: this.filters.start_date,
          end_date: this.filters.end_date,
          region: this.filters.region || undefined,
          status: this.filters.status || undefined,
          min_ca: this.filters.min_ca || undefined,
          max_ca: this.filters.max_ca || undefined,
        };

        const data = await geolocationService.getPdvGeoData(params);
        this.pdvData = data.pdvs || [];
        this.summary = data.summary;
        this.availableRegions = data.summary?.regions || [];

        // Always load potential zones (they'll only be displayed if toggle is active)
        const zonesData = await geolocationService.getPotentialZones({
          start_date: this.filters.start_date,
          end_date: this.filters.end_date,
        });
        this.potentialZones = zonesData.potential_zones || [];

        this.updateMapLayers();
      } catch (err) {
        console.error('Error loading geolocation data:', err);
        this.error = err.response?.data?.message || 'Erreur lors du chargement des données';
      } finally {
        this.loading = false;
      }
    },
    updateMapLayers() {
      if (!this.map) return;

      // Wait a bit to ensure canvas is ready (especially for heatmap)
      setTimeout(() => {
        // First, ensure map is properly sized
        this.map.invalidateSize();

        // Wait another moment after resize
        setTimeout(() => {
          // Clear existing layers properly
          Object.keys(this.mapLayers).forEach(key => {
            const layer = this.mapLayers[key];
            if (layer) {
              try {
                if (this.map.hasLayer(layer)) {
                  this.map.removeLayer(layer);
                }
                // Clear the reference
                this.mapLayers[key] = null;
              } catch (e) {
                console.warn('Error removing layer:', key, e);
              }
            }
          });

          // Add heatmap layer
          if (this.layers.heatmap && this.pdvData.length > 0) {
            try {
              // Verify map container has valid dimensions
              const container = this.map.getContainer();
              if (container && container.offsetWidth > 0 && container.offsetHeight > 0) {
                const heatData = this.pdvData
                  .filter(pdv => pdv.total_ca > 0)
                  .map(pdv => [pdv.latitude, pdv.longitude, pdv.total_ca / 100000]); // Normalize CA for intensity
                
                if (heatData.length > 0) {
                  this.mapLayers.heatmap = L.heatLayer(heatData, {
                    radius: 25,
                    blur: 35,
                    maxZoom: 12,
                    gradient: {0.0: 'blue', 0.3: 'cyan', 0.5: 'lime', 0.7: 'yellow', 1.0: 'red'}
                  }).addTo(this.map);
                }
              } else {
                console.warn('Map container not ready, skipping heatmap');
              }
            } catch (e) {
              console.warn('Could not create heatmap layer:', e);
            }
          }

        // Add cluster layer
        if (this.layers.clusters && this.pdvData.length > 0) {
          const clusters = L.markerClusterGroup({
          showCoverageOnHover: false,
          maxClusterRadius: 80,
          iconCreateFunction: (cluster) => {
            const count = cluster.getChildCount();
            let size = 'small';
            if (count >= 50) size = 'large';
            else if (count >= 10) size = 'medium';

            return L.divIcon({
              html: `<div><span>${count}</span></div>`,
              className: `marker-cluster marker-cluster-${size}`,
              iconSize: L.point(40, 40)
            });
          }
        });

        this.pdvData.forEach(pdv => {
          const marker = L.marker([pdv.latitude, pdv.longitude]);
          
          // Generate Google Maps directions link
          const mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${pdv.latitude},${pdv.longitude}`;
          
          const popupContent = `
            <div style="padding: 8px; min-width: 250px;">
              <h4 style="font-size: 16px; font-weight: bold; color: #111827; margin-bottom: 8px;">${pdv.nom}</h4>
              <p style="font-size: 12px; color: #6B7280; margin-bottom: 8px;">${pdv.pdv_numero}</p>
              <div style="font-size: 13px; line-height: 1.6; border-top: 1px solid #E5E7EB; padding-top: 8px; margin-bottom: 12px;">
                <p><strong>Région:</strong> ${pdv.region}</p>
                <p><strong>Dealer:</strong> ${pdv.dealer_name}</p>
                <p><strong>CA:</strong> ${this.formatAmount(pdv.total_ca)} FCFA</p>
                <p><strong>Transactions:</strong> ${pdv.total_transactions}</p>
                <p><strong>Jours actifs:</strong> ${pdv.active_days}</p>
              </div>
              <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                <a 
                  href="${mapsUrl}"
                  target="_blank"
                  rel="noopener noreferrer"
                  style="padding: 8px 12px; border-radius: 8px; background-color: #4285F4; color: white; font-size: 13px; font-weight: bold; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 4px; cursor: pointer;"
                  onmouseover="this.style.backgroundColor='#3367D6'"
                  onmouseout="this.style.backgroundColor='#4285F4'"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
                    <path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z"></path>
                  </svg>
                  Itinéraire
                </a>
                <button 
                  onclick="window.dispatchEvent(new CustomEvent('pdv-detail', { detail: ${pdv.id} }))"
                  style="padding: 8px 12px; border-radius: 8px; background-color: #FF6B00; color: white; font-size: 13px; font-weight: bold; border: none; cursor: pointer;"
                  onmouseover="this.style.backgroundColor='#E65C00'"
                  onmouseout="this.style.backgroundColor='#FF6B00'"
                >
                  Détails
                </button>
              </div>
            </div>
          `;
          marker.bindPopup(popupContent);
          clusters.addLayer(marker);
        });

        this.mapLayers.clusters = clusters;
        this.map.addLayer(clusters);
      }

      // Add potential zones layer
      if (this.layers.potential && this.potentialZones.length > 0) {
        const potentialGroup = L.layerGroup();
        
        this.potentialZones.forEach(zone => {
          const circle = L.circle([zone.center_lat, zone.center_lng], {
            color: '#fbbf24',
            fillColor: '#fef3c7',
            fillOpacity: 0.4,
            radius: 5000, // 5km radius
          });

          const popupContent = `
            <div class="p-2">
              <h4 class="font-bold text-sm">⭐ ${zone.region}</h4>
              <hr class="my-1" />
              <p class="text-xs"><strong>PDV:</strong> ${zone.pdv_count}</p>
              <p class="text-xs"><strong>CA/PDV:</strong> ${this.formatAmount(zone.ca_per_pdv)} FCFA</p>
              <p class="text-xs"><strong>Performance:</strong> ${zone.performance_ratio}% de la moyenne</p>
              <p class="text-xs"><strong>Score potentiel:</strong> ${zone.potential_score}</p>
              <p class="text-xs text-yellow-700 mt-1"><em>Zone sous-performante avec forte densité</em></p>
            </div>
          `;
          circle.bindPopup(popupContent);
          potentialGroup.addLayer(circle);
        });

        this.mapLayers.potential = potentialGroup;
        this.map.addLayer(potentialGroup);
        // Note: Les cercles jaunes sont vides par design - ils montrent les régions à potentiel, pas les PDV individuels
      }

          // Fit map bounds to show all PDV
          if (this.pdvData.length > 0) {
            const bounds = L.latLngBounds(this.pdvData.map(pdv => [pdv.latitude, pdv.longitude]));
            this.map.fitBounds(bounds, { padding: [50, 50] });
          }
        }, 100); // Second delay after resize
      }, 150); // First delay to ensure DOM is ready
    },
    toggleLayer(layerName) {
      this.layers[layerName] = !this.layers[layerName];
      
      // Just update the map layers to show/hide
      this.updateMapLayers();
    },
    formatAmount(amount) {
      return new Intl.NumberFormat('fr-FR').format(amount);
    }
  }
};
</script>

<style scoped>
#pdv-map {
  z-index: 0;
}

:deep(.marker-cluster-small) {
  background-color: rgba(59, 130, 246, 0.6);
}

:deep(.marker-cluster-small div) {
  background-color: rgba(59, 130, 246, 0.8);
}

:deep(.marker-cluster-medium) {
  background-color: rgba(251, 146, 60, 0.6);
}

:deep(.marker-cluster-medium div) {
  background-color: rgba(251, 146, 60, 0.8);
}

:deep(.marker-cluster-large) {
  background-color: rgba(239, 68, 68, 0.6);
}

:deep(.marker-cluster-large div) {
  background-color: rgba(239, 68, 68, 0.8);
}

:deep(.marker-cluster) {
  color: white;
  font-weight: bold;
  border-radius: 50%;
  text-align: center;
}

:deep(.marker-cluster div) {
  width: 30px;
  height: 30px;
  margin-left: 5px;
  margin-top: 5px;
  border-radius: 50%;
  font-size: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}

:deep(.leaflet-popup-content) {
  margin: 0;
  min-width: 200px;
}
</style>
