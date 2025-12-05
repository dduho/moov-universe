<template>
  <div class="min-h-screen">
    <!-- Navigation -->
    <Navbar />

    <!-- Main Content -->
    <div class="container mx-auto px-3 sm:px-4 py-4 sm:py-8">
      <div class="mb-4 sm:mb-8">
        <h1 class="text-xl sm:text-3xl font-bold text-gray-900 mb-1 sm:mb-2">Mes tâches</h1>
        <p class="text-sm sm:text-base text-gray-600">Gérez vos tâches assignées</p>
      </div>

    <!-- Filtres -->
    <div class="glass-card p-3 sm:p-6 mb-4 sm:mb-6">
      <div class="grid grid-cols-1 gap-3 sm:gap-4">
        <div>
          <label class="block text-xs sm:text-sm font-medium text-gray-700 mb-1 sm:mb-2">Statut</label>
          <select
            v-model="filters.status"
            @change="loadTasks"
            class="w-full px-3 sm:px-4 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-moov-orange focus:border-transparent"
          >
            <option value="">Tous les statuts</option>
            <option value="pending">En attente</option>
            <option value="in_progress">En cours</option>
            <option value="completed">Complétées</option>
            <option value="validated">Validées</option>
            <option value="revision_requested">Révision demandée</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Statistiques -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2 sm:gap-4 mb-4 sm:mb-6">
      <div class="glass-card p-3 sm:p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs sm:text-sm text-gray-600">Total</p>
            <p class="text-lg sm:text-2xl font-bold text-gray-900">{{ stats.total }}</p>
          </div>
          <svg class="w-6 h-6 sm:w-10 sm:h-10 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
      </div>

      <div class="glass-card p-3 sm:p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs sm:text-sm text-gray-600">En attente</p>
            <p class="text-lg sm:text-2xl font-bold text-gray-900">{{ stats.pending }}</p>
          </div>
          <svg class="w-6 h-6 sm:w-10 sm:h-10 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>

      <div class="glass-card p-3 sm:p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs sm:text-sm text-gray-600">En cours</p>
            <p class="text-lg sm:text-2xl font-bold text-blue-600">{{ stats.in_progress }}</p>
          </div>
          <svg class="w-6 h-6 sm:w-10 sm:h-10 text-blue-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
          </svg>
        </div>
      </div>

      <div class="glass-card p-3 sm:p-4">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs sm:text-sm text-gray-600">Complétées</p>
            <p class="text-lg sm:text-2xl font-bold text-yellow-600">{{ stats.completed }}</p>
          </div>
          <svg class="w-6 h-6 sm:w-10 sm:h-10 text-yellow-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>

      <div class="glass-card p-3 sm:p-4 col-span-2 sm:col-span-1">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-xs sm:text-sm text-gray-600">Validées</p>
            <p class="text-lg sm:text-2xl font-bold text-green-600">{{ stats.validated }}</p>
          </div>
          <svg class="w-6 h-6 sm:w-10 sm:h-10 text-green-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
        </div>
      </div>
    </div>

    <!-- Liste des tâches -->
    <div v-if="loading" class="text-center py-8 sm:py-12">
      <div class="animate-spin rounded-full h-12 w-12 sm:h-16 sm:w-16 border-b-2 border-moov-orange mx-auto"></div>
      <p class="mt-3 sm:mt-4 text-sm sm:text-base text-gray-500">Chargement des tâches...</p>
    </div>

    <div v-else-if="tasks.length === 0" class="glass-card text-center py-12">
      <svg class="w-20 h-20 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="mt-4 text-xl text-gray-500">Aucune tâche trouvée</p>
    </div>

    <div v-else class="space-y-4">
      <div
        v-for="task in tasks"
        :key="task.id"
        class="glass-card p-4 hover:shadow-lg transition cursor-pointer"
        @click="goToPdv(task.point_of_sale_id)"
      >
        <div class="flex justify-between items-start">
          <div class="flex-1">
            <div class="flex items-center gap-3 mb-1">
              <h3 class="text-lg font-semibold text-gray-900">{{ task.title }}</h3>
              <span :class="getStatusClass(task.status)" class="px-2 py-1 rounded-full text-xs font-medium">
                {{ getStatusLabel(task.status) }}
              </span>
            </div>
            <p class="text-sm text-gray-600 mb-1">{{ task.point_of_sale?.nom_point }}</p>
            <p class="text-xs text-gray-500">
              {{ task.point_of_sale?.shortcode }} | {{ task.point_of_sale?.numero_flooz }}
            </p>
            
            <!-- Feedback admin si présent -->
            <div v-if="task.admin_feedback" class="mt-2 p-2 bg-yellow-50 border-l-2 border-yellow-400 rounded text-xs">
              <p class="font-medium text-yellow-800">Retour admin:</p>
              <p class="text-yellow-700">{{ task.admin_feedback }}</p>
            </div>
          </div>
          
          <div class="flex flex-col items-end gap-2 ml-4">
            <span class="text-xs text-gray-500">{{ formatDate(task.created_at) }}</span>
            <button
              v-if="canComplete(task)"
              @click.stop="handleComplete(task)"
              class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 transition"
            >
              Compléter
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Pagination -->
    <div v-if="pagination.last_page > 1" class="mt-8 flex justify-center">
      <nav class="flex gap-2">
        <button
          v-for="page in pagination.last_page"
          :key="page"
          @click="changePage(page)"
          :class="page === pagination.current_page ? 'bg-moov-orange text-white' : 'bg-white text-gray-700 hover:bg-gray-100'"
          class="px-4 py-2 border border-gray-300 rounded-lg transition"
        >
          {{ page }}
        </button>
      </nav>
    </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';
import { useConfirm } from '../composables/useConfirm';
import TaskService from '../services/TaskService';
import Navbar from '../components/Navbar.vue';

const router = useRouter();
const authStore = useAuthStore();
const { toast } = useToast();
const { confirm } = useConfirm();

const loading = ref(false);
const tasks = ref([]);
const filters = ref({
  status: ''
});

const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
});

const stats = computed(() => {
  return {
    total: tasks.value.length,
    pending: tasks.value.filter(t => t.status === 'pending').length,
    in_progress: tasks.value.filter(t => t.status === 'in_progress').length,
    completed: tasks.value.filter(t => t.status === 'completed').length,
    validated: tasks.value.filter(t => t.status === 'validated').length
  };
});

onMounted(() => {
  loadTasks();
});

const loadTasks = async () => {
  loading.value = true;
  try {
    const params = { ...filters.value };
    const response = await TaskService.getTasks(params);
    // response est déjà response.data depuis TaskService
    // et contient { data: [...], current_page, last_page, etc }
    tasks.value = response.data || [];
    pagination.value = {
      current_page: response.current_page || 1,
      last_page: response.last_page || 1,
      total: response.total || 0
    };
  } catch (error) {
    console.error('Erreur lors du chargement des tâches:', error);
    toast.error('Erreur lors du chargement des tâches');
  } finally {
    loading.value = false;
  }
};

const canComplete = (task) => {
  return authStore.user.id === task.assigned_to && 
         ['pending', 'in_progress', 'revision_requested'].includes(task.status);
};

const handleComplete = async (task) => {
  const confirmed = await confirm({
    title: 'Compléter la tâche',
    message: 'Marquer cette tâche comme complétée ?',
    confirmText: 'Compléter',
    type: 'info'
  });
  if (!confirmed) return;
  
  try {
    await TaskService.completeTask(task.id);
    toast.success('Tâche complétée et soumise pour validation');
    loadTasks();
  } catch (error) {
    console.error('Erreur:', error);
    toast.error(error.response?.data?.error || 'Erreur lors de la completion');
  }
};

const goToPdv = (pdvId) => {
  router.push(`/pdv/${pdvId}`);
};

const changePage = (page) => {
  pagination.value.current_page = page;
  loadTasks();
};

const getStatusLabel = (status) => {
  const labels = {
    pending: 'En attente',
    in_progress: 'En cours',
    completed: 'Complétée',
    validated: 'Validée',
    revision_requested: 'Révision demandée'
  };
  return labels[status] || status;
};

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-gray-100 text-gray-800',
    in_progress: 'bg-blue-100 text-blue-800',
    completed: 'bg-yellow-100 text-yellow-800',
    validated: 'bg-green-100 text-green-800',
    revision_requested: 'bg-orange-100 text-orange-800'
  };
  return classes[status] || 'bg-gray-100 text-gray-800';
};

const formatDate = (date) => {
  if (!date) return '';
  return new Date(date).toLocaleDateString('fr-FR', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>
