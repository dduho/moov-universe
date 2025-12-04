<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-gray-900">Journal d'activité</h1>
          <p class="text-gray-600 mt-2">Traçabilité complète des actions utilisateurs</p>
        </div>
        <div class="flex items-center gap-3">
          <ExportButton
            @export="handleExport"
            label="Exporter les logs"
          />
          <button
            @click="refreshLogs"
            class="px-4 py-2 rounded-xl bg-white border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 transition-all flex items-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
            </svg>
            Actualiser
          </button>
        </div>
      </div>

      <!-- Statistics Cards -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="glass-card p-6 rounded-2xl">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
              </svg>
            </div>
            <div>
              <p class="text-sm text-gray-600">Total logs</p>
              <p class="text-2xl font-bold text-gray-900">{{ activityLogStore.total }}</p>
            </div>
          </div>
        </div>

        <div class="glass-card p-6 rounded-2xl">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-green-500 flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
              </svg>
            </div>
            <div>
              <p class="text-sm text-gray-600">Créations</p>
              <p class="text-2xl font-bold text-gray-900">{{ actionCount('create') }}</p>
            </div>
          </div>
        </div>

        <div class="glass-card p-6 rounded-2xl">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-500 flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
            </div>
            <div>
              <p class="text-sm text-gray-600">Modifications</p>
              <p class="text-2xl font-bold text-gray-900">{{ actionCount('update') }}</p>
            </div>
          </div>
        </div>

        <div class="glass-card p-6 rounded-2xl">
          <div class="flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-red-500 flex items-center justify-center">
              <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
              </svg>
            </div>
            <div>
              <p class="text-sm text-gray-600">Suppressions</p>
              <p class="text-2xl font-bold text-gray-900">{{ actionCount('delete') }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="glass-card p-6 mb-8 rounded-2xl">
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-4">
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Recherche</label>
            <SearchBar
              v-model="filters.search"
              placeholder="Rechercher..."
              @search="handleSearch"
            />
          </div>

          <div v-if="authStore.isAdmin">
            <label class="block text-sm font-semibold text-gray-700 mb-2">Utilisateur</label>
            <FormSelect
              v-model="filters.user_id"
              :options="userOptions"
              placeholder="Tous les utilisateurs"
              option-label="label"
              option-value="value"
              :searchable="true"
              @update:modelValue="applyFilters"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Action</label>
            <FormSelect
              v-model="filters.action"
              :options="actionOptions"
              placeholder="Toutes les actions"
              option-label="label"
              option-value="value"
              :searchable="false"
              @change="applyFilters"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Ressource</label>
            <FormSelect
              v-model="filters.resource_type"
              :options="resourceOptions"
              placeholder="Toutes les ressources"
              option-label="label"
              option-value="value"
              :searchable="false"
              @change="applyFilters"
            />
          </div>

          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">Période</label>
            <DateRangePicker
              v-model:start-date="filters.start_date"
              v-model:end-date="filters.end_date"
              placeholder="Sélectionner une période"
              :presets="true"
              @change="applyFilters"
            />
          </div>
        </div>

        <div class="flex justify-end">
          <button
            v-if="activityLogStore.hasFilters"
            @click="clearFilters"
            class="px-4 py-2 rounded-xl bg-white border-2 border-gray-200 text-gray-700 font-bold hover:bg-gray-50 hover:border-gray-300 transition-all whitespace-nowrap"
          >
            Réinitialiser les filtres
          </button>
        </div>
      </div>

      <!-- Activity Logs List -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-moov-orange"></div>
      </div>

      <div v-else-if="logs.length === 0" class="glass-card p-12 text-center rounded-2xl">
        <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <p class="text-gray-600 text-lg font-semibold">Aucun log d'activité trouvé</p>
        <p class="text-gray-500 text-sm mt-2">Essayez de modifier vos filtres</p>
      </div>

      <div v-else class="space-y-4">
        <div
          v-for="log in logs"
          :key="log.id"
          class="glass-card p-6 rounded-2xl hover:shadow-lg transition-all"
        >
          <div class="flex items-start gap-4">
            <!-- Icon -->
            <div
              class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
              :class="getActionColorClass(log.action)"
            >
              <component :is="getActionIcon(log.action)" class="w-6 h-6 text-white" />
            </div>

            <!-- Content -->
            <div class="flex-1 min-w-0">
              <div class="flex items-start justify-between gap-4 mb-2">
                <div class="flex-1">
                  <div class="flex items-center gap-3 mb-1">
                    <h3 class="text-lg font-bold text-gray-900">
                      {{ getActionLabel(log.action) }}
                    </h3>
                    <StatusBadge
                      :status="log.resource_type"
                      type="default"
                      :class="getResourceColorClass(log.resource_type)"
                    >
                      {{ getResourceLabel(log.resource_type) }}
                    </StatusBadge>
                  </div>
                  
                  <p class="text-gray-700">
                    <span class="font-semibold">{{ log.user?.name || 'Utilisateur inconnu' }}</span>
                    <span class="text-gray-500"> a {{ getActionVerb(log.action) }} </span>
                    <span v-if="log.resource_name" class="font-semibold">{{ log.resource_name }}</span>
                    <span v-else class="text-gray-500">{{ getResourceLabel(log.resource_type) }} #{{ log.resource_id }}</span>
                  </p>

                  <!-- Changes Details (expandable) -->
                  <div v-if="log.changes && Object.keys(log.changes).length > 0" class="mt-3">
                    <button
                      @click="toggleChanges(log.id)"
                      class="text-sm text-moov-orange hover:text-moov-orange-dark font-semibold flex items-center gap-1"
                    >
                      <svg
                        class="w-4 h-4 transition-transform"
                        :class="{ 'rotate-90': expandedLogs.includes(log.id) }"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                      >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                      </svg>
                      Voir les modifications
                    </button>
                    
                    <div v-if="expandedLogs.includes(log.id)" class="mt-3 p-4 bg-gray-50 rounded-xl">
                      <div class="space-y-2">
                        <div
                          v-for="(change, field) in log.changes"
                          :key="field"
                          class="text-sm"
                        >
                          <span class="font-semibold text-gray-700">{{ formatFieldName(field) }}:</span>
                          <div class="flex items-center gap-2 mt-1">
                            <span class="text-red-600 line-through">{{ change.old || 'N/A' }}</span>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                            </svg>
                            <span class="text-green-600 font-semibold">{{ change.new || 'N/A' }}</span>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Timestamp and IP -->
                <div class="text-right text-sm shrink-0">
                  <p class="text-gray-900 font-semibold">{{ formatDate(log.created_at) }}</p>
                  <p class="text-gray-500">{{ formatTime(log.created_at) }}</p>
                  <p v-if="log.ip_address" class="text-xs text-gray-400 mt-1">
                    IP: {{ log.ip_address }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="totalPages > 1" class="flex items-center justify-center gap-2 mt-8">
        <button
          @click="previousPage"
          :disabled="currentPage === 1"
          class="px-4 py-2 rounded-xl bg-white/50 text-gray-700 font-bold hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed transition-all"
        >
          ← Précédent
        </button>
        
        <div class="flex gap-2">
          <button
            v-for="page in visiblePages"
            :key="page"
            @click="goToPage(page)"
            class="px-4 py-2 rounded-xl font-bold transition-all"
            :class="page === currentPage ? 'bg-moov-orange text-white' : 'bg-white/50 text-gray-700 hover:bg-white'"
          >
            {{ page }}
          </button>
        </div>
        
        <button
          @click="nextPage"
          :disabled="currentPage === totalPages"
          class="px-4 py-2 rounded-xl bg-white/50 text-gray-700 font-bold hover:bg-white disabled:opacity-50 disabled:cursor-not-allowed transition-all"
        >
          Suivant →
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, h } from 'vue';
import { useActivityLogStore } from '../stores/activityLog';
import { useAuthStore } from '../stores/auth';
import { useUserStore } from '../stores/user';
import Navbar from '../components/Navbar.vue';
import ExportButton from '../components/ExportButton.vue';
import SearchBar from '../components/SearchBar.vue';
import DateRangePicker from '../components/DateRangePicker.vue';
import StatusBadge from '../components/StatusBadge.vue';
import FormInput from '../components/FormInput.vue';
import FormSelect from '../components/FormSelect.vue';
import ActivityLogService from '../services/ActivityLogService';
import ExportService from '../services/ExportService';

const activityLogStore = useActivityLogStore();
const authStore = useAuthStore();
const userStore = useUserStore();

const expandedLogs = ref([]);

const filters = ref({
  search: '',
  user_id: null,
  action: '',
  resource_type: '',
  start_date: null,
  end_date: null
});

const loading = computed(() => activityLogStore.loading);
const logs = computed(() => activityLogStore.logs);
const currentPage = computed(() => activityLogStore.currentPage);
const totalPages = computed(() => activityLogStore.totalPages);

const userOptions = computed(() => {
  return userStore.users.map(user => ({
    value: user.id,
    label: user.name,
    description: user.email
  }));
});

const actionOptions = [
  { value: '', label: 'Toutes les actions' },
  { value: 'create', label: 'Création' },
  { value: 'update', label: 'Modification' },
  { value: 'delete', label: 'Suppression' },
  { value: 'validate', label: 'Validation' },
  { value: 'reject', label: 'Rejet' },
  { value: 'login', label: 'Connexion' },
  { value: 'logout', label: 'Déconnexion' },
  { value: 'export', label: 'Export' },
  { value: 'import', label: 'Import' }
];

const resourceOptions = [
  { value: '', label: 'Toutes les ressources' },
  { value: 'pdv', label: 'Points de Vente' },
  { value: 'user', label: 'Utilisateurs' },
  { value: 'organization', label: 'Organisations' }
];

const visiblePages = computed(() => {
  const pages = [];
  const maxVisible = 5;
  let start = Math.max(1, currentPage.value - Math.floor(maxVisible / 2));
  let end = Math.min(totalPages.value, start + maxVisible - 1);
  
  if (end - start < maxVisible - 1) {
    start = Math.max(1, end - maxVisible + 1);
  }
  
  for (let i = start; i <= end; i++) {
    pages.push(i);
  }
  
  return pages;
});

const actionCount = (action) => {
  return activityLogStore.logsByAction(action).length;
};

const getActionIcon = (action) => {
  return h('svg', {
    class: 'w-6 h-6',
    fill: 'none',
    stroke: 'currentColor',
    viewBox: '0 0 24 24'
  }, [
    h('path', {
      'stroke-linecap': 'round',
      'stroke-linejoin': 'round',
      'stroke-width': '2',
      d: getActionIconPath(action)
    })
  ]);
};

const getActionIconPath = (action) => {
  const paths = {
    create: 'M12 6v6m0 0v6m0-6h6m-6 0H6',
    update: 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
    delete: 'M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16',
    validate: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    reject: 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
    login: 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1',
    logout: 'M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1',
    export: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
    import: 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12'
  };
  return paths[action] || 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
};

const getActionColorClass = (action) => {
  const classes = {
    create: 'bg-green-500',
    update: 'bg-blue-500',
    delete: 'bg-red-500',
    validate: 'bg-green-500',
    reject: 'bg-red-500',
    login: 'bg-purple-500',
    logout: 'bg-gray-500',
    export: 'bg-blue-500',
    import: 'bg-blue-500'
  };
  return classes[action] || 'bg-gray-500';
};

const getActionLabel = (action) => {
  return ActivityLogService.getActionConfig(action).label;
};

const getActionVerb = (action) => {
  const verbs = {
    create: 'créé',
    update: 'modifié',
    delete: 'supprimé',
    validate: 'validé',
    reject: 'rejeté',
    login: 'connecté à',
    logout: 'déconnecté de',
    export: 'exporté',
    import: 'importé'
  };
  return verbs[action] || action;
};

const getResourceLabel = (resourceType) => {
  return ActivityLogService.getResourceConfig(resourceType).label;
};

const getResourceColorClass = (resourceType) => {
  const classes = {
    pdv: 'bg-orange-100 text-orange-800',
    user: 'bg-purple-100 text-purple-800',
    organization: 'bg-blue-100 text-blue-800',
    dealer: 'bg-green-100 text-green-800'
  };
  return classes[resourceType] || 'bg-gray-100 text-gray-800';
};

const formatFieldName = (field) => {
  const names = {
    point_name: 'Nom du PDV',
    flooz_number: 'Numéro Flooz',
    status: 'Statut',
    region: 'Région',
    city: 'Ville',
    name: 'Nom',
    email: 'Email',
    phone: 'Téléphone',
    is_active: 'Actif'
  };
  return names[field] || field;
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
};

const formatTime = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleTimeString('fr-FR', {
    hour: '2-digit',
    minute: '2-digit'
  });
};

const toggleChanges = (logId) => {
  const index = expandedLogs.value.indexOf(logId);
  if (index > -1) {
    expandedLogs.value.splice(index, 1);
  } else {
    expandedLogs.value.push(logId);
  }
};

const handleSearch = () => {
  applyFilters();
};

const applyFilters = () => {
  activityLogStore.updateFilters(filters.value);
  activityLogStore.fetchLogs();
};

const clearFilters = () => {
  filters.value = {
    search: '',
    user_id: null,
    action: '',
    resource_type: '',
    start_date: null,
    end_date: null
  };
  activityLogStore.clearFilters();
  activityLogStore.fetchLogs();
};

const refreshLogs = () => {
  activityLogStore.fetchLogs();
};

const previousPage = () => {
  if (currentPage.value > 1) {
    activityLogStore.setPage(currentPage.value - 1);
  }
};

const nextPage = () => {
  if (currentPage.value < totalPages.value) {
    activityLogStore.setPage(currentPage.value + 1);
  }
};

const goToPage = (page) => {
  activityLogStore.setPage(page);
};

const handleExport = (format) => {
  const dataToExport = logs.value.map(log => ({
    'Date': formatDate(log.created_at),
    'Heure': formatTime(log.created_at),
    'Utilisateur': log.user?.name || 'Inconnu',
    'Action': getActionLabel(log.action),
    'Ressource': getResourceLabel(log.resource_type),
    'Nom de la ressource': log.resource_name || `#${log.resource_id}`,
    'IP': log.ip_address || 'N/A'
  }));

  if (format === 'excel') {
    ExportService.exportToExcel(dataToExport, `activity_logs_${new Date().toISOString().split('T')[0]}`, 'Logs');
  } else {
    ExportService.exportToCSV(dataToExport, `activity_logs_${new Date().toISOString().split('T')[0]}`);
  }
};

onMounted(async () => {
  await activityLogStore.fetchLogs();
  if (authStore.isAdmin) {
    await userStore.fetchUsers();
  }
});
</script>
