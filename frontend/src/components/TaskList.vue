<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex justify-between items-center">
      <h3 class="text-lg font-semibold text-gray-900">Tâches ({{ tasks.length }})</h3>
      <button
        v-if="authStore.isAdmin"
        @click="showCreateModal = true"
        class="px-4 py-2 bg-gradient-to-r from-moov-orange to-moov-orange-dark text-white rounded-lg hover:shadow-lg transition"
      >
        <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Créer une tâche
      </button>
    </div>

    <!-- Tags du PDV -->
    <div v-if="pdvTags.length > 0" class="flex gap-2">
      <span
        v-for="tag in pdvTags"
        :key="tag"
        :class="getTagClass(tag)"
        class="px-3 py-1 rounded-full text-sm font-medium"
      >
        {{ getTagLabel(tag) }}
      </span>
    </div>

    <!-- Liste des tâches -->
    <div v-if="loading" class="text-center py-8">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-moov-orange mx-auto"></div>
      <p class="mt-4 text-gray-500">Chargement des tâches...</p>
    </div>

    <div v-else-if="tasks.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
      <svg class="w-16 h-16 mx-auto text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="mt-4 text-gray-500">Aucune tâche</p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="task in tasks"
        :key="task.id"
        class="border rounded-lg p-4 hover:shadow-md transition"
        :class="getTaskBorderClass(task.status)"
      >
        <!-- Header de la tâche -->
        <div class="flex justify-between items-start mb-3">
          <div class="flex-1">
            <h4 class="font-semibold text-gray-900">{{ task.title }}</h4>
            <p class="text-sm text-gray-500 mt-1">
              Assignée à: <span class="font-medium">{{ task.assigned_user?.name }}</span>
            </p>
          </div>
          <span :class="getStatusClass(task.status)" class="px-3 py-1 rounded-full text-xs font-medium whitespace-nowrap">
            {{ getStatusLabel(task.status) }}
          </span>
        </div>

        <!-- Description -->
        <p v-if="task.description" class="text-sm text-gray-600 mb-3">{{ task.description }}</p>

        <!-- Feedback admin -->
        <div v-if="task.admin_feedback" class="mb-3 p-3 bg-yellow-50 border-l-4 border-yellow-400 rounded">
          <p class="text-sm font-medium text-yellow-800 mb-1">Retour de l'administrateur :</p>
          <p class="text-sm text-yellow-700">{{ task.admin_feedback }}</p>
        </div>

        <!-- Dates -->
        <div class="flex gap-4 text-xs text-gray-500 mb-3">
          <span>Créée: {{ formatDate(task.created_at) }}</span>
          <span v-if="task.completed_at">Complétée: {{ formatDate(task.completed_at) }}</span>
          <span v-if="task.validated_at">Validée: {{ formatDate(task.validated_at) }}</span>
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
          <!-- Commercial peut compléter -->
          <button
            v-if="canComplete(task)"
            @click="handleComplete(task)"
            class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm hover:bg-blue-700 transition"
          >
            Marquer comme complétée
          </button>

          <!-- Admin peut valider ou demander révision -->
          <template v-if="authStore.isAdmin && task.status === 'completed'">
            <button
              @click="handleValidate(task)"
              class="px-3 py-1.5 bg-green-600 text-white rounded text-sm hover:bg-green-700 transition"
            >
              Valider
            </button>
            <button
              @click="handleRequestRevision(task)"
              class="px-3 py-1.5 bg-yellow-600 text-white rounded text-sm hover:bg-yellow-700 transition"
            >
              Demander révision
            </button>
          </template>

          <!-- Admin peut supprimer -->
          <button
            v-if="authStore.isAdmin"
            @click="handleDelete(task)"
            class="px-3 py-1.5 bg-red-600 text-white rounded text-sm hover:bg-red-700 transition"
          >
            Supprimer
          </button>
        </div>
      </div>
    </div>

    <!-- Modal de création -->
    <Teleport to="body">
      <TaskModal
        :is-open="showCreateModal"
        :pdv="pdv"
        @close="showCreateModal = false"
        @task-created="handleTaskCreated"
      />
    </Teleport>

    <!-- Modal de révision -->
    <Teleport to="body">
      <RevisionModal
        :is-open="showRevisionModal"
        :task="selectedTask"
        @close="showRevisionModal = false"
        @revision-requested="handleRevisionRequested"
      />
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';
import { useConfirm } from '../composables/useConfirm';
import TaskService from '../services/TaskService';
import TaskModal from './TaskModal.vue';
import RevisionModal from './RevisionModal.vue';

const props = defineProps({
  pdv: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['tasks-updated']);

const authStore = useAuthStore();
const { toast } = useToast();
const { confirm } = useConfirm();

const loading = ref(false);
const tasks = ref([]);
const showCreateModal = ref(false);
const showRevisionModal = ref(false);
const selectedTask = ref(null);

const pdvTags = computed(() => {
  // Détermine les tags basés sur les statuts des tâches
  // Flux: pending -> completed (soumis) -> validated (admin valide) OU revision_requested (admin demande révision)
  if (tasks.value.length === 0) return [];
  
  const tags = [];
  const hasRevisionRequested = tasks.value.some(t => t.status === 'revision_requested');
  const hasPending = tasks.value.some(t => t.status === 'pending');
  const hasCompleted = tasks.value.some(t => t.status === 'completed'); // Soumis pour validation
  const allValidated = tasks.value.every(t => t.status === 'validated');
  
  if (allValidated) {
    // Toutes les tâches sont validées par l'admin - Terminé !
    tags.push('taches_completes');
  } else if (hasRevisionRequested) {
    // Admin a demandé une révision - le commercial doit corriger
    tags.push('revision_demandee');
  } else if (hasCompleted) {
    // Le commercial a soumis, en attente de validation admin
    tags.push('taches_a_valider');
  } else if (hasPending) {
    // Tâches en cours de travail par le commercial
    tags.push('en_revision');
  }
  
  return tags;
});

onMounted(() => {
  loadTasks();
});

const loadTasks = async () => {
  loading.value = true;
  try {
    const response = await TaskService.getTasks({ point_of_sale_id: props.pdv.id });
    tasks.value = response.data || [];
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
    emit('tasks-updated');
  } catch (error) {
    console.error('Erreur:', error);
    toast.error(error.response?.data?.error || 'Erreur lors de la completion de la tâche');
  }
};

const handleValidate = async (task) => {
  const confirmed = await confirm({
    title: 'Valider la tâche',
    message: 'Valider cette tâche ?',
    confirmText: 'Valider',
    type: 'info'
  });
  if (!confirmed) return;
  
  try {
    await TaskService.validateTask(task.id);
    toast.success('Tâche validée avec succès');
    loadTasks();
    emit('tasks-updated');
  } catch (error) {
    console.error('Erreur:', error);
    toast.error(error.response?.data?.error || 'Erreur lors de la validation');
  }
};

const handleRequestRevision = (task) => {
  selectedTask.value = task;
  showRevisionModal.value = true;
};

const handleRevisionRequested = () => {
  loadTasks();
  emit('tasks-updated');
};

const handleDelete = async (task) => {
  const confirmed = await confirm({
    title: 'Supprimer la tâche',
    message: 'Supprimer cette tâche ?',
    confirmText: 'Supprimer',
    type: 'danger'
  });
  if (!confirmed) return;
  
  try {
    await TaskService.deleteTask(task.id);
    toast.success('Tâche supprimée');
    loadTasks();
    emit('tasks-updated');
  } catch (error) {
    console.error('Erreur:', error);
    toast.error('Erreur lors de la suppression');
  }
};

const handleTaskCreated = () => {
  loadTasks();
  emit('tasks-updated');
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

const getTaskBorderClass = (status) => {
  const classes = {
    pending: 'border-gray-200',
    in_progress: 'border-blue-200',
    completed: 'border-yellow-200',
    validated: 'border-green-200',
    revision_requested: 'border-orange-200'
  };
  return classes[status] || 'border-gray-200';
};

const getTagLabel = (tag) => {
  const labels = {
    en_revision: 'En cours de traitement',
    revision_demandee: 'Révision demandée',
    taches_a_valider: 'En attente de validation',
    taches_completes: 'Tâches validées'
  };
  return labels[tag] || tag;
};

const getTagClass = (tag) => {
  const classes = {
    en_revision: 'bg-blue-100 text-blue-800',
    revision_demandee: 'bg-orange-100 text-orange-800',
    taches_a_valider: 'bg-yellow-100 text-yellow-800',
    taches_completes: 'bg-green-100 text-green-800'
  };
  return classes[tag] || 'bg-gray-100 text-gray-800';
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
