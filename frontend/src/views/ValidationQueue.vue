<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Validation des PDV</h1>
        <p class="text-gray-600">Gérez les demandes de création de points de vente en attente</p>
      </div>

      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <StatsCard
          label="En attente"
          :value="stats.pending"
          :icon="ClockIcon"
          color="yellow"
        />
        <StatsCard
          label="Validés aujourd'hui"
          :value="stats.validatedToday"
          :icon="CheckIcon"
          color="green"
        />
        <StatsCard
          label="Rejetés aujourd'hui"
          :value="stats.rejectedToday"
          :icon="XIcon"
          color="red"
        />
        <StatsCard
          label="Temps moyen"
          :value="stats.averageTime"
          :icon="TimerIcon"
          color="blue"
        />
      </div>

      <!-- Filters -->
      <div class="glass-card p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
          <FormInput
            v-model="filters.search"
            label="Recherche"
            type="text"
            placeholder="Nom PDV, Dealer, Flooz..."
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
          />
          
          <FormSelect
            v-model="filters.dealer"
            label="Dealer"
            :options="[
              { label: 'Tous les dealers', value: '' },
              ...dealers.map(d => ({ label: d.name, value: d.id }))
            ]"
            option-label="label"
            option-value="value"
          />
          
          <FormSelect
            v-model="filters.sortBy"
            label="Tri"
            :options="[
              { label: 'Date de création', value: 'created_at' },
              { label: 'Alerte proximité', value: 'proximity' },
              { label: 'Dealer', value: 'dealer' }
            ]"
            option-label="label"
            option-value="value"
          />
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="glass-card p-12 text-center">
        <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-moov-orange mx-auto mb-4"></div>
        <p class="text-gray-600 font-semibold">Chargement des PDV en attente...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredPendingPOS.length === 0" class="glass-card p-12 text-center">
        <div class="text-6xl mb-4">🎉</div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Aucun PDV en attente</h3>
        <p class="text-gray-600">Tous les points de vente ont été traités !</p>
      </div>

      <!-- PDV List -->
      <div v-else class="space-y-4">
        <div
          v-for="pos in filteredPendingPOS"
          :key="pos.id"
          class="glass-card p-6 hover:shadow-xl transition-all duration-200"
        >
          <div class="space-y-6">
            <!-- Info Section -->
            <div class="space-y-4">
              <div class="flex items-start justify-between">
                <div>
                  <h3 class="text-xl font-bold text-gray-900 mb-1">{{ pos.point_name }}</h3>
                  <div class="flex items-center gap-3 text-sm text-gray-600">
                    <span class="flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                      </svg>
                      {{ pos.organization?.name || 'N/A' }}
                    </span>
                    <span class="flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                      </svg>
                      {{ formatDate(pos.created_at) }}
                    </span>
                    <span class="flex items-center gap-1">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                      </svg>
                      {{ pos.created_by?.name || 'N/A' }}
                    </span>
                  </div>
                </div>
                
                <!-- Proximity Alert -->
                <div v-if="pos.proximity_alert" class="shrink-0">
                  <div class="flex items-center gap-2 px-3 py-2 rounded-xl bg-red-50 border border-red-200">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <span class="text-sm font-bold text-red-700">Alerte proximité</span>
                  </div>
                </div>
              </div>

              <!-- Details Grid -->
              <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                  <p class="text-xs font-semibold text-gray-500 mb-1">Numéro Flooz</p>
                  <p class="text-sm font-bold text-gray-900">{{ formatPhone(pos.numero_flooz) || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold text-gray-500 mb-1">Shortcode</p>
                  <p class="text-sm font-bold text-gray-900">{{ formatShortcode(pos.shortcode) }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold text-gray-500 mb-1">Profil</p>
                  <p class="text-sm font-bold text-gray-900">{{ pos.profil || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold text-gray-500 mb-1">Propriétaire</p>
                  <p class="text-sm font-bold text-gray-900">{{ pos.firstname }} {{ pos.lastname }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold text-gray-500 mb-1">Téléphone</p>
                  <p class="text-sm font-bold text-gray-900">{{ formatPhone(pos.numero_proprietaire) || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-xs font-semibold text-gray-500 mb-1">Localisation</p>
                  <p class="text-sm font-bold text-gray-900">{{ pos.ville || 'N/A' }}, {{ pos.quartier || 'N/A' }}</p>
                </div>
              </div>

              <!-- Proximity Details -->
              <div v-if="pos.proximity_alert && pos.nearby_pos" class="bg-red-50 border border-red-200 rounded-xl p-4">
                <p class="text-sm font-bold text-red-800 mb-2">⚠️ PDV à proximité détectés :</p>
                <ul class="space-y-1">
                  <li v-for="nearby in pos.nearby_pos" :key="nearby.id" class="text-sm text-red-700">
                    • <strong>{{ nearby.point_name }}</strong> - {{ nearby.distance }}m - {{ formatPhone(nearby.flooz_number) }}
                  </li>
                </ul>
              </div>
            </div>

            <!-- Map & Actions Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
              <!-- Map -->
              <div class="lg:col-span-2">
                <div class="bg-gray-100 rounded-xl h-64 border border-gray-200 overflow-hidden relative">
                  <iframe
                    :src="`https://www.openstreetmap.org/export/embed.html?bbox=${parseFloat(pos.longitude)-0.01},${parseFloat(pos.latitude)-0.01},${parseFloat(pos.longitude)+0.01},${parseFloat(pos.latitude)+0.01}&layer=mapnik&marker=${pos.latitude},${pos.longitude}`"
                    class="w-full h-full"
                    frameborder="0"
                  ></iframe>
                  <div class="absolute bottom-2 right-2 bg-white px-3 py-1 rounded-lg shadow-lg text-xs font-mono text-gray-600">
                    {{ parseFloat(pos.latitude).toFixed(7) }}, {{ parseFloat(pos.longitude).toFixed(7) }}
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="space-y-2">
                <button
                  @click="viewDetails(pos)"
                  class="w-full px-4 py-3 rounded-xl bg-blue-50 border border-blue-200 text-blue-700 font-bold hover:bg-blue-100 transition-all duration-200 flex items-center justify-center gap-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                  </svg>
                  Voir les détails
                </button>
                
                <button
                  @click="validatePOS(pos)"
                  class="w-full px-4 py-3 rounded-xl bg-green-500 text-white font-bold hover:bg-green-600 hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  Valider
                </button>
                
                <button
                  @click="openRejectModal(pos)"
                  class="w-full px-4 py-3 rounded-xl bg-red-500 text-white font-bold hover:bg-red-600 hover:shadow-lg transition-all duration-200 flex items-center justify-center gap-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  Rejeter
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Rejection Modal -->
    <RejectionModal
      v-if="showRejectModal"
      :point-of-sale="selectedPOS"
      @close="closeRejectModal"
      @reject="handleReject"
    />

    <!-- Details Modal -->
    <DetailsModal
      v-if="showDetailsModal"
      :point-of-sale="selectedPOS"
      @close="closeDetailsModal"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue';
import { useRouter } from 'vue-router';
import Navbar from '../components/Navbar.vue';
import StatsCard from '../components/StatsCard.vue';
import RejectionModal from '../components/RejectionModal.vue';
import DetailsModal from '../components/DetailsModal.vue';
import FormInput from '../components/FormInput.vue';
import FormSelect from '../components/FormSelect.vue';
import { useValidationStore } from '../stores/validation';
import { formatPhone, formatShortcode } from '../utils/formatters';
import { useOrganizationStore } from '../stores/organization';

const router = useRouter();
const validationStore = useValidationStore();
const organizationStore = useOrganizationStore();

const loading = ref(true);
const showRejectModal = ref(false);
const showDetailsModal = ref(false);
const selectedPOS = ref(null);

// Icon components
const ClockIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' })
    ]);
  }
};

const CheckIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M5 13l4 4L19 7' })
    ]);
  }
};

const XIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M6 18L18 6M6 6l12 12' })
    ]);
  }
};

const TimerIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' })
    ]);
  }
};

const filters = ref({
  search: '',
  region: '',
  dealer: '',
  sortBy: 'created_at'
});

const regions = ref([
  'Savanes',
  'Kara',
  'Centrale',
  'Plateaux',
  'Maritime'
]);

const dealers = computed(() => organizationStore.organizations);

const stats = computed(() => ({
  pending: validationStore.pendingPOS?.length || 0,
  validatedToday: validationStore.stats?.validatedToday || 0,
  rejectedToday: validationStore.stats?.rejectedToday || 0,
  averageTime: validationStore.stats?.averageTime || '0h'
}));

const filteredPendingPOS = computed(() => {
  let filtered = validationStore.pendingPOS || [];

  // Search filter
  if (filters.value.search) {
    const searchLower = filters.value.search.toLowerCase();
    filtered = filtered.filter(pos =>
      pos.point_name?.toLowerCase().includes(searchLower) ||
      pos.flooz_number?.toLowerCase().includes(searchLower) ||
      pos.organization?.name?.toLowerCase().includes(searchLower)
    );
  }

  // Region filter
  if (filters.value.region) {
    filtered = filtered.filter(pos => pos.region === filters.value.region);
  }

  // Dealer filter
  if (filters.value.dealer) {
    filtered = filtered.filter(pos => pos.organization_id === filters.value.dealer);
  }

  // Sort
  if (filters.value.sortBy === 'created_at') {
    filtered.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
  } else if (filters.value.sortBy === 'proximity') {
    filtered.sort((a, b) => (b.proximity_alert ? 1 : 0) - (a.proximity_alert ? 1 : 0));
  } else if (filters.value.sortBy === 'dealer') {
    filtered.sort((a, b) => (a.organization?.name || '').localeCompare(b.organization?.name || ''));
  }

  return filtered;
});

const formatDate = (dateString) => {
  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 60) return `Il y a ${diffMins} min`;
  if (diffHours < 24) return `Il y a ${diffHours}h`;
  if (diffDays < 7) return `Il y a ${diffDays}j`;
  
  return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
};

const openRejectModal = (pos) => {
  selectedPOS.value = pos;
  showRejectModal.value = true;
};

const closeRejectModal = () => {
  showRejectModal.value = false;
  selectedPOS.value = null;
};

const viewDetails = (pos) => {
  router.push(`/pdv/${pos.id}`);
};

const closeDetailsModal = () => {
  showDetailsModal.value = false;
  selectedPOS.value = null;
};

const validatePOS = async (pos) => {
  if (confirm(`Êtes-vous sûr de vouloir valider le PDV "${pos.point_name}" ?`)) {
    await validationStore.validatePOS(pos.id);
  }
};

const handleReject = async ({ id, reason }) => {
  await validationStore.rejectPOS(id, reason);
  closeRejectModal();
};

onMounted(async () => {
  loading.value = true;
  await Promise.all([
    validationStore.fetchPendingPOS(),
    validationStore.fetchStats(),
    organizationStore.fetchOrganizations()
  ]);
  loading.value = false;
});
</script>
