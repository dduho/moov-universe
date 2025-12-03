<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Carte Interactive</h1>
        <p class="text-gray-600">Visualisez tous les points de vente sur la carte du Togo</p>
      </div>

      <!-- Proximity Alerts -->
      <div v-if="proximityAlerts.length > 0" class="glass-card p-6 mb-6 border-2 border-orange-500">
        <div class="flex items-start gap-3 mb-4">
          <svg class="w-6 h-6 text-orange-500 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
          </svg>
          <div class="flex-1">
            <h3 class="text-lg font-bold text-orange-700 mb-1">⚠️ Alertes de Proximité</h3>
            <p class="text-sm text-gray-600 mb-3">{{ proximityAlerts.length }} paire(s) de PDV à moins de {{ proximityThreshold }}m détectée(s)</p>
            <div class="space-y-2 max-h-60 overflow-y-auto">
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
      <div class="glass-card p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
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

        <div class="flex justify-end mt-4">
          <button
            @click="resetFilters"
            class="px-6 py-3 rounded-xl bg-white border-2 border-gray-200 text-gray-700 font-bold hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 whitespace-nowrap"
          >
            Réinitialiser les filtres
          </button>
        </div>
      </div>

      <!-- Map Container -->
      <div class="glass-card p-6">
        <div class="relative rounded-xl overflow-hidden" style="height: 700px;">
          <l-map
            ref="map"
            v-model:zoom="zoom"
            v-model:center="center"
            :use-global-leaflet="false"
            class="h-full w-full rounded-xl"
          >
            <l-tile-layer
              url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
              attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
              layer-type="base"
              name="OpenStreetMap"
            />

            <!-- Markers for each point of sale -->
            <l-marker
              v-for="pos in filteredPointsOfSale"
              :key="pos.id"
              :lat-lng="[parseFloat(pos.latitude), parseFloat(pos.longitude)]"
              @click="showPopup(pos)"
            >
              <l-icon
                :icon-size="[40, 50]"
                :icon-anchor="[20, 50]"
                :popup-anchor="[0, -50]"
              >
                <div class="relative">
                  <!-- Modern pin icon -->
                  <svg width="40" height="50" viewBox="0 0 40 50" xmlns="http://www.w3.org/2000/svg">
                    <!-- Shadow -->
                    <ellipse cx="20" cy="47" rx="8" ry="3" fill="rgba(0,0,0,0.2)"/>
                    <!-- Pin body -->
                    <path
                      d="M20 2C11.716 2 5 8.716 5 17c0 8.5 15 29 15 29s15-20.5 15-29c0-8.284-6.716-15-15-15z"
                      :fill="getDealerColor(pos.organization_id)"
                      stroke="none"
                      filter="drop-shadow(0 2px 4px rgba(0,0,0,0.3))"
                    />
                    <!-- Inner circle -->
                    <circle cx="20" cy="17" r="6" fill="white" opacity="0.9"/>
                    <!-- Status dot -->
                    <circle cx="20" cy="17" r="4" :fill="getStatusDotColor(pos.status)"/>
                  </svg>
                  <!-- Proximity alert badge -->
                  <div
                    v-if="pos.has_proximity_alert"
                    class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-orange-500 border-2 border-white flex items-center justify-center"
                  >
                    <span class="text-white text-xs font-bold">⚠</span>
                  </div>
                </div>
              </l-icon>

              <l-popup>
                <div class="p-2 min-w-[250px]">
                  <h3 class="text-lg font-bold text-gray-900 mb-2">{{ pos.point_name }}</h3>
                  <div class="space-y-1 text-sm">
                    <p><span class="font-semibold">Flooz:</span> {{ pos.flooz_number }}</p>
                    <p><span class="font-semibold">Région:</span> {{ pos.region }}</p>
                    <p><span class="font-semibold">Ville:</span> {{ pos.city }}</p>
                    <p>
                      <span class="font-semibold">Statut:</span>
                      <span
                        class="inline-block px-2 py-0.5 rounded-full text-xs font-bold ml-1"
                        :class="getStatusClass(pos.status)"
                      >
                        {{ getStatusLabel(pos.status) }}
                      </span>
                    </p>
                    <p v-if="authStore.isAdmin">
                      <span class="font-semibold">Dealer:</span> {{ pos.organization?.name }}
                    </p>
                  </div>
                  <button
                    @click="goToDetail(pos.id)"
                    class="mt-3 w-full px-3 py-2 rounded-lg bg-moov-orange text-white text-sm font-bold hover:bg-moov-orange-dark transition-colors"
                  >
                    Voir les détails
                  </button>
                </div>
              </l-popup>
            </l-marker>
          </l-map>

          <!-- Loading overlay -->
          <div
            v-if="loading"
            class="absolute inset-0 bg-white/80 backdrop-blur-sm flex items-center justify-center z-[1000]"
          >
            <div class="text-center">
              <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-moov-orange mb-4"></div>
              <p class="text-gray-600 font-semibold">Chargement de la carte...</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Statistics Cards - Dealers -->
      <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 mt-8">
        <div
          v-for="dealer in dealerStats"
          :key="dealer.id"
          class="glass-card p-4 rounded-xl hover:shadow-lg transition-shadow"
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
      <div class="glass-card p-6 mt-6">
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Navbar from '../components/Navbar.vue';
import FormInput from '../components/FormInput.vue';
import FormSelect from '../components/FormSelect.vue';
import { useAuthStore } from '../stores/auth';
import { useOrganizationStore } from '../stores/organization';
import PointOfSaleService from '../services/PointOfSaleService';
import SystemSettingService from '../services/systemSettingService';
import { LMap, LTileLayer, LMarker, LPopup, LIcon } from '@vue-leaflet/vue-leaflet';
import 'leaflet/dist/leaflet.css';

const router = useRouter();
const authStore = useAuthStore();
const organizationStore = useOrganizationStore();

const map = ref(null);
const pointsOfSale = ref([]);
const loading = ref(true);
const zoom = ref(7);
const center = ref([8.6195, 0.8248]); // Togo center coordinates

const filters = ref({
  search: '',
  status: '',
  region: '',
  dealer: ''
});

const regions = ref([
  'Savanes',
  'Kara',
  'Centrale',
  'Plateaux',
  'Maritime'
]);

const dealers = computed(() => organizationStore.organizations);

const clusterOptions = {
  chunkedLoading: true,
  maxClusterRadius: 60,
  spiderfyOnMaxZoom: true,
  showCoverageOnHover: false,
  zoomToBoundsOnClick: true
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
    const orgName = pdv.organization?.name || pdv.dealer_name || 'Sans dealer';
    
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

const getMarkerColor = (status) => {
  const colors = {
    validated: '#10B981', // green
    pending: '#FBBF24',   // yellow
    rejected: '#EF4444'   // red
  };
  return colors[status] || '#6B7280'; // gray as default
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

const showPopup = (pos) => {
  console.log('Showing popup for:', pos.point_name);
};

const goToDetail = (id) => {
  router.push(`/pdv/${id}`);
};

const filterMarkers = () => {
  // Filters are applied via computed property
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

const proximityThreshold = ref(300); // 300m par défaut
const proximityAlerts = ref([]);

// Calculer la distance entre deux points GPS (formule de Haversine)
const calculateDistance = (lat1, lon1, lat2, lon2) => {
  const R = 6371e3; // Rayon de la Terre en mètres
  const φ1 = (lat1 * Math.PI) / 180;
  const φ2 = (lat2 * Math.PI) / 180;
  const Δφ = ((lat2 - lat1) * Math.PI) / 180;
  const Δλ = ((lon2 - lon1) * Math.PI) / 180;

  const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
            Math.cos(φ1) * Math.cos(φ2) *
            Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
  const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

  return R * c; // Distance en mètres
};

// Détecter les PDV trop proches
const detectProximityAlerts = () => {
  const alerts = [];
  const pdvs = pointsOfSale.value;
  const pdvIdsWithAlert = new Set();

  for (let i = 0; i < pdvs.length; i++) {
    for (let j = i + 1; j < pdvs.length; j++) {
      const distance = calculateDistance(
        parseFloat(pdvs[i].latitude),
        parseFloat(pdvs[i].longitude),
        parseFloat(pdvs[j].latitude),
        parseFloat(pdvs[j].longitude)
      );

      if (distance < proximityThreshold.value) {
        alerts.push({
          pdv1: pdvs[i],
          pdv2: pdvs[j],
          distance: distance
        });
        
        // Marquer les deux PDV comme ayant une alerte
        pdvIdsWithAlert.add(pdvs[i].id);
        pdvIdsWithAlert.add(pdvs[j].id);
      }
    }
  }

  // Ajouter la propriété has_proximity_alert à tous les PDV concernés
  pointsOfSale.value.forEach(pdv => {
    pdv.has_proximity_alert = pdvIdsWithAlert.has(pdv.id);
  });

  proximityAlerts.value = alerts.sort((a, b) => a.distance - b.distance);
};

// Centrer la carte sur une alerte
const focusOnAlert = (alert) => {
  const lat = (parseFloat(alert.pdv1.latitude) + parseFloat(alert.pdv2.latitude)) / 2;
  const lng = (parseFloat(alert.pdv1.longitude) + parseFloat(alert.pdv2.longitude)) / 2;
  center.value = [lat, lng];
  zoom.value = 16;
};

onMounted(async () => {
  try {
    loading.value = true;
    
    // Load proximity threshold from system settings
    try {
      const threshold = await SystemSettingService.getProximityThreshold();
      proximityThreshold.value = threshold;
    } catch (error) {
      console.warn('Could not load proximity threshold, using default:', error);
    }
    
    const response = await PointOfSaleService.getAll();
    // Handle both paginated and non-paginated responses
    const data = response.data || response;
    pointsOfSale.value = (Array.isArray(data) ? data : [])
      .filter(p => p.latitude && p.longitude && p.status !== 'rejected');
    
    if (authStore.isAdmin) {
      await organizationStore.fetchOrganizations();
    }

    // Detect proximity alerts
    detectProximityAlerts();

    // Center map on first point if available
    if (pointsOfSale.value.length > 0) {
      const firstPoint = pointsOfSale.value[0];
      center.value = [parseFloat(firstPoint.latitude), parseFloat(firstPoint.longitude)];
    }
  } catch (error) {
    console.error('Error loading points of sale:', error);
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
/* Remove default Leaflet marker outline */
:deep(.leaflet-marker-icon) {
  border: none !important;
  outline: none !important;
  background: transparent !important;
}

:deep(.leaflet-marker-icon):focus {
  outline: none !important;
}
</style>
