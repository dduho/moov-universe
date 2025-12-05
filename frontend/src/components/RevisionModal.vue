<template>
  <div v-if="isOpen" class="fixed inset-0 backdrop-blur-md bg-white/10 flex items-center justify-center z-[60] p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full">
      <!-- Header -->
      <div class="bg-gradient-to-r from-yellow-500 to-orange-500 p-6 rounded-t-2xl">
        <h2 class="text-2xl font-bold text-white">Demander une révision</h2>
      </div>

      <!-- Body -->
      <div class="p-6">
        <p class="text-gray-600 mb-4">Expliquez ce qui doit être révisé :</p>

        <form @submit.prevent="handleSubmit">
          <textarea
            v-model="feedback"
            rows="5"
            required
            placeholder="Décrivez les modifications nécessaires..."
            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-transparent resize-none mb-4"
          ></textarea>

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
              class="px-6 py-3 bg-gradient-to-r from-yellow-500 to-orange-500 text-white rounded-lg font-medium hover:shadow-lg transition disabled:opacity-50"
              :disabled="loading || !feedback.trim()"
            >
              <span v-if="loading">Envoi...</span>
              <span v-else>Envoyer</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import TaskService from '../services/TaskService';
import { useToast } from '../composables/useToast';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true
  },
  task: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'revision-requested']);

const { toast } = useToast();
const loading = ref(false);
const feedback = ref('');

watch(() => props.isOpen, (newValue) => {
  if (!newValue) {
    feedback.value = '';
  }
});

const handleSubmit = async () => {
  if (!props.task) return;
  
  loading.value = true;
  try {
    await TaskService.requestRevision(props.task.id, feedback.value);
    toast.success('Révision demandée avec succès');
    emit('revision-requested');
    close();
  } catch (error) {
    console.error('Erreur:', error);
    toast.error(error.response?.data?.error || 'Erreur lors de la demande de révision');
  } finally {
    loading.value = false;
  }
};

const close = () => {
  emit('close');
};
</script>
