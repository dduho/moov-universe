<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
      <!-- Header -->
      <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1">Radar PDV — Ma Position</h1>
          <p class="text-sm sm:text-base text-gray-600">
            Visualisez les points de vente dans un rayon de
            <span class="font-semibold text-moov-orange">{{ radiusKm }} km</span> autour de vous
          </p>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
          <!-- Radius selector -->
          <div class="flex items-center gap-2 bg-white/90 backdrop-blur-md border border-white/50 shadow rounded-xl px-4 py-2">
            <svg class="w-4 h-4 text-moov-orange shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            <label class="text-sm font-semibold text-gray-700 whitespace-nowrap">Rayon :</label>
            <select
              v-model.number="radiusKm"
              @change="refreshRadius"
              class="text-sm font-bold text-moov-orange bg-transparent border-none outline-none cursor-pointer"
            >
              <option :value="1">1 km</option>
              <option :value="2">2 km</option>
              <option :value="5">5 km</option>
              <option :value="10">10 km</option>
              <option :value="20">20 km</option>
            </select>
          </div>

          <!-- GPS status badge -->
          <div
            class="flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-semibold"
            :class="gpsStatus === 'active'
              ? 'bg-green-100 text-green-700 border border-green-200'
              : gpsStatus === 'loading'
                ? 'bg-yellow-100 text-yellow-700 border border-yellow-200'
                : 'bg-red-100 text-red-700 border border-red-200'"
          >
            <span
              class="w-2 h-2 rounded-full"
              :class="gpsStatus === 'active' ? 'bg-green-500 animate-pulse' : gpsStatus === 'loading' ? 'bg-yellow-500 animate-pulse' : 'bg-red-500'"
            ></span>
            <span>{{
              gpsStatus === 'active' ? 'GPS actif'
              : gpsStatus === 'loading' ? 'Localisation...'
              : 'GPS indisponible'
            }}</span>
          </div>

          <!-- Recenter button -->
          <button
            v-if="userPosition"
            @click="recenterMap"
            class="flex items-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-moov-orange to-moov-orange-dark text-white text-sm font-semibold shadow hover:shadow-lg transition-all"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Me recentrer
          </button>
        </div>
      </div>

      <!-- Stats bar -->
      <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow rounded-2xl px-4 py-3 flex flex-col gap-1">
          <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">PDV à proximité</span>
          <span class="text-2xl font-bold text-moov-orange">{{ nearbyPdvs.length }}</span>
        </div>
        <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow rounded-2xl px-4 py-3 flex flex-col gap-1">
          <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Validés</span>
          <span class="text-2xl font-bold text-green-600">{{ nearbyPdvs.filter(p => p.status === 'validated').length }}</span>
        </div>
        <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow rounded-2xl px-4 py-3 flex flex-col gap-1">
          <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">En attente</span>
          <span class="text-2xl font-bold text-yellow-600">{{ nearbyPdvs.filter(p => p.status === 'pending').length }}</span>
        </div>
        <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow rounded-2xl px-4 py-3 flex flex-col gap-1">
          <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Rejetés</span>
          <span class="text-2xl font-bold text-red-600">{{ nearbyPdvs.filter(p => p.status === 'rejected').length }}</span>
        </div>
      </div>

      <!-- GPS Error -->
      <div v-if="gpsError" class="bg-red-50 border border-red-200 rounded-2xl p-4 mb-6 flex items-start gap-3">
        <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <div>
          <p class="text-sm font-semibold text-red-700">Impossible d'obtenir votre position</p>
          <p class="text-xs text-red-600 mt-0.5">{{ gpsError }}</p>
        </div>
      </div>

      <!-- Map container -->
      <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl rounded-2xl overflow-hidden relative">
        <!-- Loading overlay -->
        <div
          v-if="loading"
          class="absolute inset-0 z-[1000] flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm gap-4"
        >
          <svg class="w-10 h-10 text-moov-orange animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
          </svg>
          <p class="text-sm font-semibold text-gray-700">{{ loadingMessage }}</p>
        </div>

        <div ref="mapContainer" class="w-full" style="height: 600px;"></div>
      </div>

      <!-- PDV list below map -->
      <div v-if="nearbyPdvs.length > 0" class="mt-6">
        <h2 class="text-lg font-bold text-gray-900 mb-3">
          PDV dans un rayon de {{ radiusKm }} km
          <span class="text-sm font-normal text-gray-500">({{ nearbyPdvs.length }} résultat{{ nearbyPdvs.length > 1 ? 's' : '' }})</span>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="pdv in nearbyPdvsSortedByDistance"
            :key="pdv.id"
            class="bg-white/90 backdrop-blur-md border border-white/50 shadow rounded-2xl p-4 cursor-pointer hover:shadow-lg hover:border-moov-orange/30 transition-all group"
            @click="focusOnPdv(pdv)"
          >
            <div class="flex items-start justify-between gap-2 mb-2">
              <h3 class="font-bold text-gray-900 text-sm leading-tight group-hover:text-moov-orange transition-colors">
                {{ pdv.nom_point || pdv.name || 'PDV sans nom' }}
              </h3>
              <span
                class="shrink-0 px-2 py-0.5 rounded-full text-xs font-semibold"
                :class="statusClass(pdv.status)"
              >
                {{ statusLabel(pdv.status) }}
              </span>
            </div>
            <p class="text-xs text-gray-500 mb-1">
              <svg class="inline w-3 h-3 mr-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              {{ [pdv.quartier, pdv.ville, pdv.region].filter(Boolean).join(', ') || 'Localisation inconnue' }}
            </p>
            <p class="text-xs font-semibold text-moov-orange">
              {{ formatDistance(pdv._distance) }}
            </p>
          </div>
        </div>
      </div>

      <div v-else-if="!loading && userPosition" class="mt-6 text-center py-12 text-gray-500">
        <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
        </svg>
        <p class="font-semibold text-gray-600">Aucun PDV dans un rayon de {{ radiusKm }} km</p>
        <p class="text-sm mt-1">Essayez d'augmenter le rayon de recherche</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import Navbar from '../components/Navbar.vue';
import PointOfSaleService from '../services/PointOfSaleService';

const router = useRouter();

// --- Refs ---
const mapContainer = ref(null);
const loading = ref(true);
const loadingMessage = ref('Chargement des PDV...');
const gpsStatus = ref('loading'); // 'loading' | 'active' | 'error'
const gpsError = ref(null);
const userPosition = ref(null); // { lat, lng, accuracy }
const radiusKm = ref(1);
const allPdvs = ref([]);

// Leaflet instances (not reactive)
let leafletMap = null;
let userMarker = null;
let radiusCircle = null;
let pdvCluster = null;
let watchId = null;

// --- Computed ---
const nearbyPdvs = computed(() => {
  if (!userPosition.value) return [];
  const result = [];
  for (const pdv of allPdvs.value) {
    if (!pdv.latitude || !pdv.longitude) continue;
    const dist = haversineKm(
      userPosition.value.lat, userPosition.value.lng,
      parseFloat(pdv.latitude), parseFloat(pdv.longitude)
    );
    if (dist <= radiusKm.value) {
      result.push({ ...pdv, _distance: dist });
    }
  }
  return result;
});

const nearbyPdvsSortedByDistance = computed(() =>
  [...nearbyPdvs.value].sort((a, b) => a._distance - b._distance)
);

// --- Helpers ---
function haversineKm(lat1, lon1, lat2, lon2) {
  const R = 6371;
  const dLat = deg2rad(lat2 - lat1);
  const dLon = deg2rad(lon2 - lon1);
  const a =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(deg2rad(lat1)) * Math.cos(deg2rad(lat2)) *
    Math.sin(dLon / 2) * Math.sin(dLon / 2);
  return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

function deg2rad(deg) { return deg * (Math.PI / 180); }

function formatDistance(km) {
  if (km == null) return '';
  if (km < 1) return `${Math.round(km * 1000)} m`;
  return `${km.toFixed(2)} km`;
}

function statusClass(status) {
  const map = {
    validated: 'bg-green-100 text-green-700',
    pending: 'bg-yellow-100 text-yellow-700',
    rejected: 'bg-red-100 text-red-700',
  };
  return map[status] || 'bg-gray-100 text-gray-600';
}

function statusLabel(status) {
  const map = { validated: 'Validé', pending: 'En attente', rejected: 'Rejeté' };
  return map[status] || status;
}

function statusColor(status) {
  const map = { validated: '#10B981', pending: '#FBBF24', rejected: '#EF4444' };
  return map[status] || '#6B7280';
}

// --- Map initialisation ---
async function initMap() {
  await nextTick();
  if (!mapContainer.value) return;

  leafletMap = L.map(mapContainer.value, {
    center: [8.6195, 0.8248],
    zoom: 12,
    zoomControl: true,
    tap: false,           // évite le double-event touch→click qui ferme le popup sur mobile
    closePopupOnClick: false, // empêche la carte de fermer le popup au clic
  });

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
    maxZoom: 19,
  }).addTo(leafletMap);

  pdvCluster = L.layerGroup().addTo(leafletMap);
}

// --- User position marker ---
function createUserIcon() {
  return L.divIcon({
    className: '',
    html: `
      <div style="
        width: 24px; height: 24px;
        background: #FF6B00;
        border: 3px solid white;
        border-radius: 50%;
        box-shadow: 0 0 0 3px #FF6B0044, 0 2px 8px rgba(0,0,0,0.3);
        position: relative;
      ">
        <div style="
          position: absolute; top: 50%; left: 50%;
          transform: translate(-50%,-50%);
          width: 8px; height: 8px;
          background: white; border-radius: 50%;
        "></div>
      </div>`,
    iconSize: [24, 24],
    iconAnchor: [12, 12],
  });
}

function createPdvIcon(status) {
  const color = statusColor(status);
  return L.divIcon({
    className: '',
    html: `
      <div style="
        width: 14px; height: 14px;
        background: ${color};
        border: 2px solid white;
        border-radius: 50%;
        box-shadow: 0 1px 4px rgba(0,0,0,0.35);
      "></div>`,
    iconSize: [14, 14],
    iconAnchor: [7, 7],
  });
}

function updateUserOnMap(lat, lng) {
  if (!leafletMap) return;

  if (userMarker) {
    userMarker.setLatLng([lat, lng]);
  } else {
    userMarker = L.marker([lat, lng], {
      icon: createUserIcon(),
      zIndexOffset: 1000,
    })
      .addTo(leafletMap)
      .bindTooltip('Votre position', { permanent: false, direction: 'top' });
  }

  if (radiusCircle) {
    radiusCircle.setLatLng([lat, lng]);
    radiusCircle.setRadius(radiusKm.value * 1000);
  } else {
    radiusCircle = L.circle([lat, lng], {
      radius: radiusKm.value * 1000,
      color: '#FF6B00',
      fillColor: '#FF6B00',
      fillOpacity: 0.05,
      weight: 2,
      dashArray: '6 4',
    }).addTo(leafletMap);
  }
}

function refreshPdvMarkers() {
  if (!leafletMap || !pdvCluster) return;
  pdvCluster.clearLayers();

  nearbyPdvs.value.forEach(pdv => {
    const lat = parseFloat(pdv.latitude);
    const lng = parseFloat(pdv.longitude);
    if (isNaN(lat) || isNaN(lng)) return;

    const marker = L.marker([lat, lng], { icon: createPdvIcon(pdv.status) });
    marker.bindPopup(buildPopupHtml(pdv), {
      maxWidth: 320,
      className: 'pdv-popup',
      autoClose: false,   // ne pas fermer quand un autre marker est ouvert
      closeOnClick: false, // ne pas fermer au clic sur la carte
    });
    pdvCluster.addLayer(marker);
  });
}

function buildPopupHtml(pdv) {
  const statusBadge = `<span style="
    display:inline-block; padding:2px 8px; border-radius:9999px; font-size:11px; font-weight:700;
    background:${statusColor(pdv.status)}22; color:${statusColor(pdv.status)};
    border:1px solid ${statusColor(pdv.status)}44;
  ">${statusLabel(pdv.status)}</span>`;

  const row = (label, value) => value
    ? `<tr><td style="color:#6B7280;font-size:12px;padding:2px 6px 2px 0;white-space:nowrap;font-weight:600;">${label}</td><td style="font-size:12px;color:#111827;">${value}</td></tr>`
    : '';

  return `
    <div style="font-family:system-ui,sans-serif;min-width:240px;max-width:300px;">
      <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:8px;">
        <strong style="font-size:14px;color:#111827;line-height:1.3;">${pdv.nom_point || pdv.name || 'PDV'}</strong>
        ${statusBadge}
      </div>
      <table style="width:100%;border-collapse:collapse;">
        ${row('N° Flooz', pdv.numero_flooz)}
        ${row('Shortcode', pdv.shortcode)}
        ${row('Quartier', pdv.quartier)}
        ${row('Ville', pdv.ville)}
        ${row('Région', pdv.region)}
        ${row('Dealer', pdv.organization?.name || pdv.dealer_name)}
        ${row('Distance', formatDistance(pdv._distance))}
      </table>
      <div style="margin-top:10px;text-align:right;">
        <a href="/pdv/${pdv.id}"
          style="
            display:inline-block;padding:5px 14px;background:#FF6B00;color:white;
            border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;
          "
          onclick="event.stopPropagation();"
        >Voir le détail →</a>
      </div>
    </div>`;
}

function recenterMap() {
  if (!leafletMap || !userPosition.value) return;
  leafletMap.setView([userPosition.value.lat, userPosition.value.lng], 14, { animate: true });
}

function focusOnPdv(pdv) {
  if (!leafletMap || !pdv.latitude || !pdv.longitude) return;
  leafletMap.setView([parseFloat(pdv.latitude), parseFloat(pdv.longitude)], 16, { animate: true });
}

function refreshRadius() {
  if (!leafletMap) return;
  if (radiusCircle) {
    radiusCircle.setRadius(radiusKm.value * 1000);
  }
  refreshPdvMarkers();
}

// --- GPS ---
function startGPS() {
  if (!('geolocation' in navigator)) {
    gpsStatus.value = 'error';
    gpsError.value = 'La géolocalisation n\'est pas supportée par votre navigateur.';
    loading.value = false;
    return;
  }

  gpsStatus.value = 'loading';
  loadingMessage.value = 'Obtention de votre position GPS...';

  const options = { enableHighAccuracy: true, maximumAge: 10000, timeout: 15000 };

  // Dernière position ayant déclenché un refreshPdvMarkers
  let lastRefreshPos = null;

  watchId = navigator.geolocation.watchPosition(
    (position) => {
      const { latitude: lat, longitude: lng, accuracy } = position.coords;
      const isFirstFix = !userPosition.value;
      userPosition.value = { lat, lng, accuracy };
      gpsStatus.value = 'active';
      gpsError.value = null;

      updateUserOnMap(lat, lng);

      // Ne rafraîchir les markers PDV que si c'est le premier fix
      // ou si l'utilisateur s'est déplacé de plus de 100 m
      // (évite de détruire/recréer les markers à chaque tick GPS et donc de fermer les popups)
      const movedEnough = !lastRefreshPos ||
        haversineKm(lastRefreshPos.lat, lastRefreshPos.lng, lat, lng) * 1000 > 100;

      if (isFirstFix || movedEnough) {
        lastRefreshPos = { lat, lng };
        refreshPdvMarkers();
      }

      if (loading.value) {
        // First fix — center map
        leafletMap?.setView([lat, lng], 14);
        loading.value = false;
      }
    },
    (err) => {
      gpsStatus.value = 'error';
      const messages = {
        1: 'Accès à la localisation refusé. Veuillez autoriser l\'accès dans les paramètres du navigateur.',
        2: 'Position indisponible. Vérifiez votre connexion GPS.',
        3: 'Délai d\'attente dépassé. Réessayez.',
      };
      gpsError.value = messages[err.code] || err.message;
      loading.value = false;
    },
    options
  );
}

// --- Data loading ---
async function loadPdvs() {
  try {
    loadingMessage.value = 'Chargement des points de vente...';
    const data = await PointOfSaleService.getForMap();
    allPdvs.value = Array.isArray(data) ? data : (data?.data ?? []);
  } catch (err) {
    console.error('[AdminRadarMap] Failed to load PDVs:', err);
  }
}

// --- Lifecycle ---
onMounted(async () => {
  await initMap();
  await loadPdvs();
  startGPS();
});

onUnmounted(() => {
  if (watchId != null) navigator.geolocation.clearWatch(watchId);
  if (leafletMap) { leafletMap.remove(); leafletMap = null; }
});
</script>

<style>
.pdv-popup .leaflet-popup-content-wrapper {
  border-radius: 12px;
  padding: 0;
  overflow: hidden;
  box-shadow: 0 10px 40px rgba(0,0,0,0.15);
}
.pdv-popup .leaflet-popup-content {
  margin: 14px 16px;
}
.pdv-popup .leaflet-popup-tip-container {
  display: none;
}
</style>
