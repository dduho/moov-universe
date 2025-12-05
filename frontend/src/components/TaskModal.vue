<template>
  <div v-if="isOpen" class="fixed inset-0 backdrop-blur-md bg-white/10 flex items-center justify-center z-[60] p-4 overflow-y-auto">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full my-8">
      <!-- Header -->
      <div class="sticky top-0 bg-gradient-to-r from-moov-orange to-moov-orange-dark p-6 rounded-t-2xl">
        <div class="flex justify-between items-center">
          <h2 class="text-2xl font-bold text-white">Créer une tâche</h2>
          <button @click="close" class="text-white hover:text-gray-200 transition">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <!-- Body -->
      <div class="p-6">
        <!-- PDV Info -->
        <div class="mb-6 p-4 bg-blue-50 rounded-lg">
          <h3 class="font-semibold text-gray-700 mb-2">Point de vente</h3>
          <p class="text-sm text-gray-600">{{ pdv.nom_point }}</p>
          <p class="text-xs text-gray-500">Shortcode: {{ pdv.shortcode }} | Numéro: {{ pdv.numero_flooz }}</p>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit">
          <!-- Commercial Selection -->
          <div class="mb-6">
            <FormSelect
              v-model="formData.assigned_to"
              label="Commercial"
              :options="commercialOptions"
              :disabled="loadingCommercials"
              :loading="loadingCommercials"
              placeholder="Sélectionner un commercial..."
              help-text="Aucun commercial disponible pour ce dealer"
              required
            />
          </div>

          <!-- Title -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Titre de la tâche <span class="text-red-500">*</span>
            </label>
            <input
              v-model="formData.title"
              type="text"
              required
              maxlength="255"
              placeholder="Ex: Vérifier les documents d'identité"
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-moov-orange focus:border-transparent"
            />
          </div>

          <!-- Description -->
          <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">
              Description
            </label>
            <textarea
              v-model="formData.description"
              rows="4"
              placeholder="Décrivez les détails de la tâche..."
              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-moov-orange focus:border-transparent resize-none"
            ></textarea>
          </div>

          <!-- Actions -->
          <div class="flex gap-3 justify-end">
            <button
              type="button"
              @click="close"
              class="px-6 py-3 border border-gray-300 rounded-lg text-gray-700 font-medium hover:bg-gray-50 transition"
              :disabled="loading"
            >
              Annuler
            </button>
            <button
              type="submit"
              class="px-6 py-3 bg-gradient-to-r from-moov-orange to-moov-orange-dark text-white rounded-lg font-medium hover:shadow-lg transition disabled:opacity-50"
              :disabled="loading || !formData.assigned_to || !formData.title"
            >
              <span v-if="loading">Création...</span>
              <span v-else>Créer la tâche</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, reactive, computed } from 'vue';
import TaskService from '../services/TaskService';
import { useToast } from '../composables/useToast';
import FormSelect from './FormSelect.vue';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  pdv: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'task-created']);

const { toast } = useToast();
const loading = ref(false);
const loadingCommercials = ref(false);
const commercials = ref([]);

const formData = reactive({
  assigned_to: '',
  title: '',
  description: ''
});

// Formater les commerciaux pour FormSelect
const commercialOptions = computed(() => {
  return commercials.value.map(c => ({
    value: c.id,
    label: `${c.name} (${c.email})`
  }));
});

// Charger les commerciaux quand le modal s'ouvre
watch(() => props.isOpen, async (newValue) => {
  if (newValue) {
    await loadCommercials();
  } else {
    resetForm();
  }
});

const loadCommercials = async () => {
  loadingCommercials.value = true;
  try {
    commercials.value = await TaskService.getCommercialsByDealer(props.pdv.id);
  } catch (error) {
    console.error('Erreur lors du chargement des commerciaux:', error);
    toast.error('Erreur lors du chargement des commerciaux');
  } finally {
    loadingCommercials.value = false;
  }
};

const handleSubmit = async () => {
  loading.value = true;
  try {
    const response = await TaskService.createTask({
      point_of_sale_id: props.pdv.id,
      assigned_to: formData.assigned_to,
      title: formData.title,
      description: formData.description
    });

    toast.success('Tâche créée avec succès');
    emit('task-created', response.task);
    close();
  } catch (error) {
    console.error('Erreur lors de la création de la tâche:', error);
    toast.error(error.response?.data?.error || 'Erreur lors de la création de la tâche');
  } finally {
    loading.value = false;
  }
};

const resetForm = () => {
  formData.assigned_to = '';
  formData.title = '';
  formData.description = '';
  commercials.value = [];
};

const close = () => {
  emit('close');
};
</script>
