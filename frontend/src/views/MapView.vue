<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
      <!-- Header -->
      <div class="mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1 sm:mb-2">Carte Interactive</h1>
        <p class="text-sm sm:text-base text-gray-600">Visualisez tous les points de vente sur la carte</p>
      </div>

      <!-- Tabs -->
      <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-xl rounded-2xl p-2 mb-6">
        <div class="flex gap-2">
          <button
            @click="activeTab = 'basic'"
            :class="[
              'flex-1 px-4 py-3 rounded-xl font-semibold transition-all',
              activeTab === 'basic'
                ? 'bg-gradient-to-r from-moov-orange to-orange-600 text-white shadow-lg'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            📍 Carte Basique
          </button>
          <button
            v-if="authStore.isAdmin"
            @click="activeTab = 'advanced'"
            :class="[
              'flex-1 px-4 py-3 rounded-xl font-semibold transition-all',
              activeTab === 'advanced'
                ? 'bg-gradient-to-r from-green-500 to-green-600 text-white shadow-lg'
                : 'bg-gray-100 text-gray-700 hover:bg-gray-200'
            ]"
          >
            🔥 Géolocalisation Avancée
          </button>
        </div>
      </div>

      <!-- Basic Map Tab -->
      <div v-show="activeTab === 'basic'">
      <!-- Proximity Alerts Loading -->
      <div v-if="loadingProximityAlerts" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6 mb-4 sm:mb-6 border-2 border-orange-300">
        <div class="flex items-center gap-3">
          <svg class="w-5 h-5 sm:w-6 sm:h-6 text-orange-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
          </svg>
          <div class="flex-1">
            <h3 class="text-base sm:text-lg font-bold text-orange-700">Détection des alertes de proximité...</h3>
            <p class="text-xs sm:text-sm text-gray-600">Analyse des distances entre points de vente en cours</p>
          </div>
        </div>
      </div>

      <!-- Proximity Alerts -->
      <div v-else-if="proximityAlerts.length > 0" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6 mb-4 sm:mb-6 border-2 border-orange-500">
        <div class="flex flex-col sm:flex-row sm:items-start gap-3 mb-4">
          <svg class="w-5 h-5 sm:w-6 sm:h-6 text-orange-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
          <div class="flex-1">
            <h3 class="text-base sm:text-lg font-bold text-orange-700 mb-1">⚠️ Alertes de Proximité</h3>
            <p class="text-xs sm:text-sm text-gray-600 mb-3">{{ proximityAlerts.length }} paire(s) de PDV à moins de {{ proximityThreshold }}m détectée(s)</p>
            <div class="space-y-2 max-h-48 sm:max-h-60 overflow-y-auto">
              <div v-for="(alert, index) in proximityAlerts" :key="index" class="bg-orange-50 border border-orange-200 rounded-lg p-3">
                <div class="flex items-center justify-between">
                  <div class="flex-1">
                    <p class="text-sm font-bold text-gray-900">{{ alert.pdv1.nom_point }} ↔ {{ alert.pdv2.nom_point }}</p>
                    <p class="text-xs text-gray-600">Distance: <span class="font-bold text-orange-600">{{ Math.round(alert.distance) }}m</span></p>
                    <p class="text-xs text-gray-500">{{ alert.pdv1.ville }}, {{ alert.pdv1.quartier }}</p>
                  </div>
                  <button
                    @click="focusOnAlert(alert)"
                    class="px-3 py-1 rounded-lg bg-orange-600 text-white text-xs font-bold hover:bg-orange-700 transition-colors"
                  >
                    Voir
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-3 pt-3 border-t border-orange-200">
          <label class="text-sm font-semibold text-gray-700">Seuil de proximité:</label>
          <input
            v-model.number="proximityThreshold"
            type="number"
            step="50"
            min="50"
            max="1000"
            class="px-3 py-1 rounded-lg border-2 border-gray-300 focus:border-orange-500 focus:outline-none w-24"
            @change="detectProximityAlerts"
          />
          <span class="text-sm text-gray-600">mètres</span>
        </div>
      </div>

      <!-- Map Controls -->
      <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6 mb-4 sm:mb-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 sm:gap-4 mb-4">
          <FormInput
            v-model="filters.search"
            label="Rechercher un PDV"
            type="text"
            placeholder="Nom du point de vente..."
            @input="filterMarkers"
          >
            <template #iconLeft>
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
              </svg>
            </template>
          </FormInput>

          <FormSelect
            v-model="filters.status"
            label="Filtrer par statut"
            :options="[
              { label: 'Tous les statuts', value: '' },
              { label: 'Validés', value: 'validated' },
              { label: 'En attente', value: 'pending' },
              { label: 'Rejetés', value: 'rejected' }
            ]"
            option-label="label"
            option-value="value"
            @change="filterMarkers"
          />
          
          <FormSelect
            v-model="filters.region"
            label="Région"
            :options="[
              { label: 'Toutes les régions', value: '' },
              ...regions.map(r => ({ label: r, value: r }))
            ]"
            option-label="label"
            option-value="value"
            @change="filterMarkers"
          />
          
          <FormSelect
            v-if="authStore.isAdmin"
            v-model="filters.dealer"
            label="Dealer"
            :options="[
              { label: 'Tous les dealers', value: '' },
              ...dealers.map(d => ({ label: d.name, value: d.id }))
            ]"
            option-label="label"
            option-value="value"
            @change="filterMarkers"
          />
        </div>

        <div class="flex justify-start mt-4">
          <button
            @click="resetFilters"
            class="px-6 py-3 rounded-xl bg-white border-2 border-gray-200 text-gray-700 font-bold hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 whitespace-nowrap"
          >
            Réinitialiser les filtres
          </button>
        </div>
      </div>

      <!-- Map Container -->
      <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6">
        <div class="relative rounded-xl overflow-hidden" style="height: 700px;">
          <!-- Native Leaflet Map Container -->
          <div ref="mapContainer" class="h-full w-full rounded-xl"></div>

          <!-- Loading overlay -->
          <div
            v-if="loading"
            class="absolute inset-0 bg-white/80 backdrop-blur-sm flex items-center justify-center z-[1000]"
          >
            <div class="text-center">
              <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-moov-orange mb-4"></div>
              <p class="text-gray-600 font-semibold">Chargement de la carte...</p>
              <p v-if="loadingProgress > 0" class="text-sm text-gray-500 mt-2">{{ loadingProgress }}% des points chargés</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistics Cards - Dealers -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mt-8">
        <div
          v-for="dealer in dealerStats"
          :key="dealer.id"
          class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 rounded-xl hover:shadow-lg transition-shadow"
        >
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-xl flex items-center justify-center shadow-md"
              :style="{ backgroundColor: getDealerColor(dealer.id) }"
            >
              <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
              </svg>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-xs font-medium text-gray-600 truncate">{{ dealer.name }}</p>
              <p class="text-xl font-bold text-gray-900">{{ dealer.count }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Legend -->
      <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 mt-6">
        <h3 class="text-lg font-bold text-gray-900 mb-4">Légende</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Dealers Legend -->
          <div>
            <p class="text-sm font-semibold text-gray-700 mb-3">Couleurs par Dealer</p>
            <div class="grid grid-cols-2 gap-2">
              <div
                v-for="dealer in dealers"
                :key="dealer.id"
                class="flex items-center gap-2"
              >
                <div
                  class="w-6 h-8 rounded-t-full rounded-b-sm shadow-sm"
                  :style="{ backgroundColor: getDealerColor(dealer.id) }"
                ></div>
                <span class="text-xs font-medium text-gray-700 truncate">{{ dealer.name }}</span>
              </div>
            </div>
          </div>
          
          <!-- Status Legend -->
          <div>
            <p class="text-sm font-semibold text-gray-700 mb-3">Statuts (point central)</p>
            <div class="space-y-2">
              <div class="flex items-center gap-3">
                <div class="w-4 h-4 rounded-full bg-green-500"></div>
                <span class="text-sm font-medium text-gray-700">Validé</span>
              </div>
              <div class="flex items-center gap-3">
                <div class="w-4 h-4 rounded-full bg-yellow-500"></div>
                <span class="text-sm font-medium text-gray-700">En attente</span>
              </div>
              <div class="flex items-center gap-3">
                <div class="w-4 h-4 rounded-full bg-red-500"></div>
                <span class="text-sm font-medium text-gray-700">Rejeté</span>
              </div>
              <div class="flex items-center gap-3">
                <div class="w-5 h-5 rounded-full bg-orange-500 flex items-center justify-center text-white text-xs font-bold">⚠</div>
                <span class="text-sm font-medium text-gray-700">Alerte proximité</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      </div>

      <!-- Advanced Geolocation Tab -->
      <div v-show="activeTab === 'advanced'">
        <GeolocationWidget :isActive="activeTab === 'advanced'" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import 'leaflet.markercluster';
import 'leaflet.markercluster/dist/MarkerCluster.css';
import 'leaflet.markercluster/dist/MarkerCluster.Default.css';
import Navbar from '../components/Navbar.vue';
import FormInput from '../components/FormInput.vue';
import FormSelect from '../components/FormSelect.vue';
import GeolocationWidget from '../components/GeolocationWidget.vue';
import { useAuthStore } from '../stores/auth';
import { useOrganizationStore } from '../stores/organization';
import PointOfSaleService from '../services/PointOfSaleService';
import SystemSettingService from '../services/systemSettingService';

const router = useRouter();
const authStore = useAuthStore();
const organizationStore = useOrganizationStore();

// Tab state
const activeTab = ref('basic');

// Map refs
const mapContainer = ref(null);
let leafletMap = null;
let markerClusterGroup = null;
let allMarkersMap = new Map(); // Store marker references by PDV id

const pointsOfSale = ref([]);
const loading = ref(true);
const loadingProgress = ref(0);
const zoom = ref(7);
const center = ref([8.6195, 0.8248]); // Togo center coordinates

const filters = ref({
  search: '',
  status: '',
  region: '',
  dealer: ''
});

const regions = ref([
  'SAVANES',
  'KARA',
  'CENTRALE',
  'PLATEAUX',
  'MARITIME'
]);

const dealers = computed(() => organizationStore.organizations);

// Palette de couleurs pour les dealers
const dealerColors = [
  '#FF6B6B', // Rouge corail
  '#4ECDC4', // Turquoise
  '#45B7D1', // Bleu ciel
  '#FFA07A', // Saumon
  '#98D8C8', // Vert menthe
  '#F7DC6F', // Jaune doré
  '#BB8FCE', // Violet
  '#85C1E2', // Bleu clair
  '#F8B88B', // Pêche
  '#ABEBC6', // Vert clair
];

// Methods
const getDealerColor = (organizationId) => {
  if (!organizationId) return '#6B7280'; // Gris par défaut
  const index = organizationId % dealerColors.length;
  return dealerColors[index];
};

const getStatusDotColor = (status) => {
  const colors = {
    validated: '#10B981', // vert
    pending: '#FBBF24',   // jaune
    rejected: '#EF4444'   // rouge
  };
  return colors[status] || '#6B7280';
};

const getStatusClass = (status) => {
  const classes = {
    validated: 'bg-green-100 text-green-700',
    pending: 'bg-yellow-100 text-yellow-700',
    rejected: 'bg-red-100 text-red-700'
  };
  return classes[status] || 'bg-gray-100 text-gray-700';
};

const getStatusLabel = (status) => {
  const labels = {
    validated: 'Validé',
    pending: 'En attente',
    rejected: 'Rejeté'
  };
  return labels[status] || status;
};

const goToDetail = (id) => {
  router.push(`/pdv/${id}`);
};

// Create custom SVG icon for a marker
const createMarkerIcon = (pos) => {
  const dealerColor = getDealerColor(pos.organization_id);
  const statusColor = getStatusDotColor(pos.status);
  const hasAlert = pos.has_proximity_alert;
  
  const alertBadge = hasAlert ? `
    <circle cx="35" cy="5" r="8" fill="#F97316" stroke="white" stroke-width="2"/>
    <text x="35" y="9" text-anchor="middle" font-size="10" fill="white" font-weight="bold">!</text>
  ` : '';
  
  const svgIcon = `
    <svg width="40" height="50" viewBox="0 0 40 50" xmlns="http://www.w3.org/2000/svg">
      <ellipse cx="20" cy="47" rx="8" ry="3" fill="rgba(0,0,0,0.2)"/>
      <path d="M20 2C11.716 2 5 8.716 5 17c0 8.5 15 29 15 29s15-20.5 15-29c0-8.284-6.716-15-15-15z" fill="${dealerColor}"/>
      <circle cx="20" cy="17" r="6" fill="white" opacity="0.9"/>
      <circle cx="20" cy="17" r="4" fill="${statusColor}"/>
      ${alertBadge}
    </svg>
  `;
  
  return L.divIcon({
    html: svgIcon,
    className: 'custom-marker-icon',
    iconSize: [40, 50],
    iconAnchor: [20, 50],
    popupAnchor: [0, -50]
  });
};

// Create popup content for a marker
const createPopupContent = (pos) => {
  const statusClassMap = {
    validated: 'background-color: #D1FAE5; color: #047857;',
    pending: 'background-color: #FEF3C7; color: #B45309;',
    rejected: 'background-color: #FEE2E2; color: #DC2626;'
  };
  const statusStyle = statusClassMap[pos.status] || 'background-color: #F3F4F6; color: #4B5563;';
  
  const dealerInfo = authStore.isAdmin && pos.organization 
    ? `<p><span style="font-weight: 600;">Dealer:</span> ${pos.organization.name || 'N/A'}</p>` 
    : '';
  
  // Generate Google Maps directions link
  const mapsUrl = `https://www.google.com/maps/dir/?api=1&destination=${pos.latitude},${pos.longitude}`;
  
  return `
    <div style="padding: 8px; min-width: 250px;">
      <h3 style="font-size: 18px; font-weight: bold; color: #111827; margin-bottom: 8px;">${pos.nom_point || pos.point_name || 'Sans nom'}</h3>
      <div style="font-size: 14px; line-height: 1.6;">
        <p><span style="font-weight: 600;">Flooz:</span> ${pos.numero_flooz || pos.flooz_number || 'N/A'}</p>
        <p><span style="font-weight: 600;">Région:</span> ${pos.region || 'N/A'}</p>
        <p><span style="font-weight: 600;">Ville:</span> ${pos.ville || pos.city || 'N/A'}</p>
        <p>
          <span style="font-weight: 600;">Statut:</span>
          <span style="${statusStyle} display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 12px; font-weight: bold; margin-left: 4px;">
            ${getStatusLabel(pos.status)}
          </span>
        </p>
        ${dealerInfo}
      </div>
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 12px;">
        <a 
          href="${mapsUrl}"
          target="_blank"
          rel="noopener noreferrer"
          style="padding: 8px 12px; border-radius: 8px; background-color: #4285F4; color: white; font-size: 14px; font-weight: bold; text-align: center; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 4px; cursor: pointer;"
          onmouseover="this.style.backgroundColor='#3367D6'"
          onmouseout="this.style.backgroundColor='#4285F4'"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"></path>
            <path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0z"></path>
          </svg>
          Itinéraire
        </a>
        <button 
          onclick="window.dispatchEvent(new CustomEvent('pdv-detail', { detail: ${pos.id} }))"
          style="padding: 8px 12px; border-radius: 8px; background-color: #FF6B00; color: white; font-size: 14px; font-weight: bold; border: none; cursor: pointer;"
          onmouseover="this.style.backgroundColor='#E65C00'"
          onmouseout="this.style.backgroundColor='#FF6B00'"
        >
          Détails
        </button>
      </div>
    </div>
  `;
};

// Initialize the map
const initMap = () => {
  if (!mapContainer.value || leafletMap) return;
  
  leafletMap = L.map(mapContainer.value, {
    center: center.value,
    zoom: zoom.value,
    preferCanvas: true // Use canvas renderer for better performance
  });
  
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    maxZoom: 19
  }).addTo(leafletMap);
  
  // Create marker cluster group with optimized settings
  markerClusterGroup = L.markerClusterGroup({
    chunkedLoading: false, // Disable chunked loading to avoid double counting
    maxClusterRadius: 80,
    spiderfyOnMaxZoom: true,
    showCoverageOnHover: false,
    zoomToBoundsOnClick: true,
    disableClusteringAtZoom: 16,
    animate: false,
    removeOutsideVisibleBounds: true,
    // Custom cluster icon
    iconCreateFunction: (cluster) => {
      const count = cluster.getChildCount();
      let c = ' marker-cluster-';
      
      if (count < 10) {
        c += 'small';
      } else if (count < 100) {
        c += 'medium';
      } else {
        c += 'large';
      }
      
      return L.divIcon({
        html: `<div><span>${count}</span></div>`,
        className: 'marker-cluster' + c,
        iconSize: L.point(40, 40)
      });
    }
  });
  
  // Don't add to map here - will be added in addMarkersToMap
  
  // Listen for popup button clicks
  window.addEventListener('pdv-detail', (e) => {
    goToDetail(e.detail);
  });
};

// Add markers to cluster group
const addMarkersToMap = async (pdvList) => {
  if (!markerClusterGroup) return;
  
  // Remove cluster group from map, clear it, then re-add
  if (leafletMap.hasLayer(markerClusterGroup)) {
    leafletMap.removeLayer(markerClusterGroup);
  }
  markerClusterGroup.clearLayers();
  allMarkersMap.clear();
  
  if (pdvList.length === 0) {
    leafletMap.addLayer(markerClusterGroup);
    return;
  }
  
  const markers = [];
  const seenIds = new Set();
  
  // Create all markers with strict deduplication
  for (let i = 0; i < pdvList.length; i++) {
    const pos = pdvList[i];
    if (!pos.latitude || !pos.longitude) continue;
    
    // Skip duplicates by ID
    if (seenIds.has(pos.id)) {
      continue;
    }
    seenIds.add(pos.id);
    
    const lat = parseFloat(pos.latitude);
    const lng = parseFloat(pos.longitude);
    if (isNaN(lat) || isNaN(lng) || lat === 0 || lng === 0) continue;
    
    const marker = L.marker([lat, lng], {
      icon: createMarkerIcon(pos)
    });
    
    marker.bindPopup(createPopupContent(pos), {
      maxWidth: 300,
      className: 'custom-popup'
    });
    
    markers.push(marker);
    allMarkersMap.set(pos.id, marker);
    
    // Update progress every 500 markers
    if (i % 500 === 0) {
      loadingProgress.value = Math.round((i / pdvList.length) * 50);
      await nextTick();
    }
  }
  
  console.log(`Adding ${markers.length} markers to cluster group`);
  
  // Add ALL markers at once (this is the correct way to avoid double counting)
  markerClusterGroup.addLayers(markers);
  
  // Re-add cluster group to map
  leafletMap.addLayer(markerClusterGroup);
  
  loadingProgress.value = 100;
};

// Computed filtered points of sale
const filteredPointsOfSale = computed(() => {
  let filtered = pointsOfSale.value;

  if (filters.value.search) {
    const searchLower = filters.value.search.toLowerCase();
    filtered = filtered.filter(p => 
      (p.nom_point && p.nom_point.toLowerCase().includes(searchLower)) ||
      (p.point_name && p.point_name.toLowerCase().includes(searchLower)) ||
      (p.numero_flooz && p.numero_flooz.toLowerCase().includes(searchLower)) ||
      (p.flooz_number && p.flooz_number.toLowerCase().includes(searchLower))
    );
  }

  if (filters.value.status) {
    filtered = filtered.filter(p => p.status === filters.value.status);
  }

  if (filters.value.region) {
    filtered = filtered.filter(p => p.region === filters.value.region);
  }

  if (filters.value.dealer) {
    filtered = filtered.filter(p => p.organization_id === parseInt(filters.value.dealer));
  }

  return filtered;
});

// Watch for filter changes and update markers
let markersInitialized = false;

watch(filters, async () => {
  // Skip if markers haven't been initialized yet
  if (!markersInitialized) {
    return;
  }
  if (markerClusterGroup && leafletMap) {
    console.log('Filters changed, updating markers with', filteredPointsOfSale.value.length, 'PDVs');
    await addMarkersToMap(filteredPointsOfSale.value);
  }
}, { deep: true });

// Watch for filtered points of sale changes to recalculate proximity alerts
watch(filteredPointsOfSale, () => {
  if (markersInitialized) {
    detectProximityAlerts();
  }
});

const filterMarkers = () => {
  // Filters are applied via watch on filters ref
  console.log('Filters applied:', filters.value);
};

const resetFilters = () => {
  filters.value = {
    search: '',
    status: '',
    region: '',
    dealer: ''
  };
};

const stats = computed(() => {
  const total = filteredPointsOfSale.value.length;
  const validated = filteredPointsOfSale.value.filter(p => p.status === 'validated').length;
  const pending = filteredPointsOfSale.value.filter(p => p.status === 'pending').length;
  const rejected = filteredPointsOfSale.value.filter(p => p.status === 'rejected').length;
  
  return { total, validated, pending, rejected };
});

const dealerStats = computed(() => {
  const dealerMap = new Map();
  
  filteredPointsOfSale.value.forEach(pdv => {
    const orgId = pdv.organization_id;
    const orgName = pdv.organization?.name || 'Sans dealer';
    
    if (!dealerMap.has(orgId)) {
      dealerMap.set(orgId, {
        id: orgId,
        name: orgName,
        count: 0
      });
    }
    dealerMap.get(orgId).count++;
  });
  
  return Array.from(dealerMap.values()).sort((a, b) => b.count - a.count);
});

const normalizeThreshold = (value) => {
  const num = Number(value);
  return Number.isFinite(num) && num > 0 ? num : 300;
};

const proximityThreshold = ref(300);
const proximityAlerts = ref([]);
const loadingProximityAlerts = ref(false);

// Calculer la distance entre deux points GPS (formule de Haversine)
const calculateDistance = (lat1, lon1, lat2, lon2) => {
  const R = 6371e3;
  const φ1 = (lat1 * Math.PI) / 180;
  const φ2 = (lat2 * Math.PI) / 180;
  const Δφ = ((lat2 - lat1) * Math.PI) / 180;
  const Δλ = ((lon2 - lon1) * Math.PI) / 180;

  const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
            Math.cos(φ1) * Math.cos(φ2) *
            Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return R * c;
};

// Optimized proximity detection using spatial grid
const detectProximityAlerts = () => {
  loadingProximityAlerts.value = true;
  
  // Use setTimeout to allow UI to update
  setTimeout(() => {
    try {
      // Sécurise le seuil (API peut renvoyer string/undefined)
      proximityThreshold.value = normalizeThreshold(proximityThreshold.value);
      const alerts = [];
      const pdvs = filteredPointsOfSale.value;
      const pdvIdsWithAlert = new Set();
      
      console.log('Detecting proximity alerts:', { 
        pdvCount: pdvs.length, 
        threshold: proximityThreshold.value 
      });
      
      // Skip if too many points (would be too slow)
      if (pdvs.length > 10000) {
        console.log('Skipping proximity detection for performance (> 10000 points)');
        proximityAlerts.value = [];
        loadingProximityAlerts.value = false;
        return;
      }
  
  // Create spatial grid for O(n) average complexity instead of O(n²)
  const gridSize = proximityThreshold.value / 111000; // Convert meters to approximate degrees
  if (!Number.isFinite(gridSize) || gridSize <= 0) {
    console.warn('Proximity grid skipped: invalid threshold', proximityThreshold.value);
    proximityAlerts.value = [];
    loadingProximityAlerts.value = false;
    return;
  }
  const grid = new Map();
  
  // Place PDVs in grid cells
  pdvs.forEach((pdv, index) => {
    const lat = parseFloat(pdv.latitude);
    const lng = parseFloat(pdv.longitude);
    if (isNaN(lat) || isNaN(lng)) return;
    
    const cellX = Math.floor(lng / gridSize);
    const cellY = Math.floor(lat / gridSize);
    const cellKey = `${cellX},${cellY}`;
    
    if (!grid.has(cellKey)) {
      grid.set(cellKey, []);
    }
    grid.get(cellKey).push({ pdv, index, lat, lng });
  });
  
  // Check only neighboring cells
  const checked = new Set();
  grid.forEach((cellPdvs, cellKey) => {
    const [cellX, cellY] = cellKey.split(',').map(Number);
    
    // Check current cell and 8 neighbors
    for (let dx = -1; dx <= 1; dx++) {
      for (let dy = -1; dy <= 1; dy++) {
        const neighborKey = `${cellX + dx},${cellY + dy}`;
        const neighborPdvs = grid.get(neighborKey);
        if (!neighborPdvs) continue;
        
        cellPdvs.forEach(p1 => {
          neighborPdvs.forEach(p2 => {
            if (p1.index >= p2.index) return; // Avoid duplicates
            
            const pairKey = `${p1.index}-${p2.index}`;
            if (checked.has(pairKey)) return;
            checked.add(pairKey);
            
            const distance = calculateDistance(p1.lat, p1.lng, p2.lat, p2.lng);
            
            if (distance < proximityThreshold.value) {
              alerts.push({
                pdv1: p1.pdv,
                pdv2: p2.pdv,
                distance: distance
              });
              pdvIdsWithAlert.add(p1.pdv.id);
              pdvIdsWithAlert.add(p2.pdv.id);
            }
          });
        });
      }
    }
  });

      // Mark PDVs with proximity alerts
      filteredPointsOfSale.value.forEach(pdv => {
        pdv.has_proximity_alert = pdvIdsWithAlert.has(pdv.id);
      });

      console.log('Proximity detection complete:', { 
        alertCount: alerts.length, 
        pdvsWithAlert: pdvIdsWithAlert.size 
      });

      proximityAlerts.value = alerts.sort((a, b) => a.distance - b.distance);
    } finally {
      loadingProximityAlerts.value = false;
    }
  }, 0);
};

// Centrer la carte sur une alerte
const focusOnAlert = (alert) => {
  const lat = (parseFloat(alert.pdv1.latitude) + parseFloat(alert.pdv2.latitude)) / 2;
  const lng = (parseFloat(alert.pdv1.longitude) + parseFloat(alert.pdv2.longitude)) / 2;
  
  if (leafletMap) {
    leafletMap.setView([lat, lng], 16);
  }
};

// Detect and clear duplicate coordinates (admin only)
const duplicatesCleared = ref(false);
const duplicateClearingInProgress = ref(false);

const detectAndClearDuplicates = async (pdvList) => {
  if (!authStore.isAdmin || duplicatesCleared.value) return pdvList;
  
  // Detect duplicates locally
  const coordMap = new Map();
  const duplicateCoords = new Set();
  
  pdvList.forEach(pdv => {
    if (!pdv.latitude || !pdv.longitude) return;
    const coordKey = `${parseFloat(pdv.latitude).toFixed(6)},${parseFloat(pdv.longitude).toFixed(6)}`;
    
    if (coordMap.has(coordKey)) {
      duplicateCoords.add(coordKey);
    } else {
      coordMap.set(coordKey, pdv.id);
    }
  });
  
  if (duplicateCoords.size > 0) {
    console.log(`Found ${duplicateCoords.size} duplicate coordinate sets`);
    
    // Call backend to clear duplicates
    try {
      duplicateClearingInProgress.value = true;
      const result = await PointOfSaleService.clearDuplicateCoordinates();
      console.log(`Cleared coordinates for ${result.cleared_count} PDVs`);
      
      // Filter out affected PDVs from the local list
      const affectedIds = new Set(result.affected_ids);
      pdvList = pdvList.filter(p => !affectedIds.has(p.id));
      
      duplicatesCleared.value = true;
    } catch (error) {
      console.error('Error clearing duplicate coordinates:', error);
    } finally {
      duplicateClearingInProgress.value = false;
    }
  }
  
  return pdvList;
};

onMounted(async () => {
  try {
    loading.value = true;
    loadingProgress.value = 0;
    
    // Initialize map first
    await nextTick();
    initMap();
    
    // Load proximity threshold from system settings
    try {
      const threshold = await SystemSettingService.getProximityThreshold();
      proximityThreshold.value = normalizeThreshold(threshold);
    } catch (error) {
      console.warn('Could not load proximity threshold, using default:', error);
      proximityThreshold.value = normalizeThreshold(proximityThreshold.value);
    }
    
    // Load all PDVs
    let data = await PointOfSaleService.getForMap();
    let pdvList = (Array.isArray(data) ? data : [])
      .filter(p => p.latitude && p.longitude && p.status !== 'rejected');
    
    // Detect and clear duplicates (admin only)
    if (authStore.isAdmin) {
      pdvList = await detectAndClearDuplicates(pdvList);
    }
    
    // Deduplicate by ID before setting to state
    const uniquePdvMap = new Map();
    pdvList.forEach(p => {
      if (!uniquePdvMap.has(p.id)) {
        uniquePdvMap.set(p.id, p);
      }
    });
    pdvList = Array.from(uniquePdvMap.values());
    
    pointsOfSale.value = pdvList;
    
    console.log(`Loaded ${pointsOfSale.value.length} unique points of sale`);
    
    if (authStore.isAdmin) {
      await organizationStore.fetchOrganizations();
    }

    // Detect proximity alerts (with optimization)
    detectProximityAlerts();

    // Add markers to map (only once!)
    await addMarkersToMap(filteredPointsOfSale.value);
    
    // Now enable watch to react to filter changes
    markersInitialized = true;

    // Center map on first point if available
    if (pointsOfSale.value.length > 0 && leafletMap) {
      const firstPoint = pointsOfSale.value[0];
      leafletMap.setView([parseFloat(firstPoint.latitude), parseFloat(firstPoint.longitude)], zoom.value);
    }
  } catch (error) {
    console.error('Error loading points of sale:', error);
  } finally {
    loading.value = false;
  }
});

onUnmounted(() => {
  // Cleanup
  window.removeEventListener('pdv-detail', (e) => goToDetail(e.detail));
  
  if (markerClusterGroup) {
    markerClusterGroup.clearLayers();
    markerClusterGroup = null;
  }
  
  if (leafletMap) {
    leafletMap.remove();
    leafletMap = null;
  }
  
  allMarkersMap.clear();
});
</script>

<style scoped>
/* Custom marker icon styles */
:deep(.custom-marker-icon) {
  background: transparent !important;
  border: none !important;
}

/* Marker cluster styles */
:deep(.marker-cluster) {
  background-clip: padding-box;
  border-radius: 20px;
}

:deep(.marker-cluster div) {
  width: 30px;
  height: 30px;
  margin-left: 5px;
  margin-top: 5px;
  text-align: center;
  border-radius: 15px;
  font-weight: bold;
  font-size: 12px;
  line-height: 30px;
  color: white;
}

:deep(.marker-cluster-small) {
  background-color: rgba(255, 107, 0, 0.6);
}

:deep(.marker-cluster-small div) {
  background-color: rgba(255, 107, 0, 0.9);
}

:deep(.marker-cluster-medium) {
  background-color: rgba(241, 128, 23, 0.6);
}

:deep(.marker-cluster-medium div) {
  background-color: rgba(241, 128, 23, 0.9);
}

:deep(.marker-cluster-large) {
  background-color: rgba(229, 92, 0, 0.6);
}

:deep(.marker-cluster-large div) {
  background-color: rgba(229, 92, 0, 0.9);
}

/* Custom popup styles */
:deep(.custom-popup .leaflet-popup-content-wrapper) {
  border-radius: 12px;
  box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

:deep(.custom-popup .leaflet-popup-content) {
  margin: 0;
}

:deep(.custom-popup .leaflet-popup-tip) {
  box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
}

/* Leaflet container fix */
:deep(.leaflet-container) {
  font-family: inherit;
  z-index: 1;
}
</style>
