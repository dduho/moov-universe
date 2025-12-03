<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Paramètres Système</h1>
        <p class="text-gray-600">Configurer les paramètres globaux de l'application</p>
      </div>

      <!-- Loading state -->
      <div v-if="loading" class="glass-card p-8 text-center">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto"></div>
        <p class="mt-4 text-gray-600">Chargement des paramètres...</p>
      </div>

      <!-- Settings list -->
      <div v-else class="space-y-6">
        <!-- Proximity Threshold Setting -->
        <div class="glass-card p-6">
          <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
              <h3 class="text-lg font-bold text-gray-900 mb-1">Distance Minimale entre PDV</h3>
              <p class="text-sm text-gray-600">{{ proximitySetting?.description }}</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
              Obligatoire
            </span>
          </div>

          <div class="space-y-4">
            <div class="flex items-start gap-4">
              <div class="flex-1">
                <div class="relative">
                  <input
                    v-model.number="proximityValue"
                    type="number"
                    min="50"
                    max="5000"
                    step="10"
                    class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all"
                    :disabled="saving"
                  />
                  <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">mètres</span>
                </div>
              </div>

              <button
                @click="saveProximityThreshold"
                :disabled="saving || proximityValue === proximitySetting?.value"
                class="px-6 py-3 rounded-lg font-bold transition-all whitespace-nowrap"
                :class="[
                  proximityValue === proximitySetting?.value 
                    ? 'bg-gray-200 text-gray-700' 
                    : 'bg-primary-600 text-white hover:bg-primary-700 shadow-lg hover:shadow-xl',
                  (saving || proximityValue === proximitySetting?.value) && 'opacity-50 cursor-not-allowed'
                ]"
              >
                <span v-if="saving" class="flex items-center gap-2">
                  <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  Enregistrement...
                </span>
                <span v-else>Enregistrer</span>
              </button>
            </div>
            
            <p class="text-xs text-gray-500">
              Valeur recommandée : entre 100m et 1000m
            </p>
          </div>

          <!-- Visual indicator -->
          <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center gap-3 mb-3">
              <svg class="w-5 h-5 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <span class="text-sm font-bold text-gray-900">Impact de ce paramètre</span>
            </div>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-start gap-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                Alertes de proximité sur la carte interactive
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                Validation des nouveaux points de vente
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                Contrôles de qualité des données
              </li>
            </ul>
          </div>
        </div>

        <!-- Future settings placeholder -->
        <div class="glass-card p-6 border-2 border-dashed border-gray-300">
          <div class="text-center py-8">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <p class="text-gray-500 font-medium">D'autres paramètres seront ajoutés ici</p>
            <p class="text-sm text-gray-400 mt-1">Personnalisation, notifications, exports, etc.</p>
          </div>
        </div>
      </div>

      <!-- Success message -->
      <Transition name="slide-up">
        <div v-if="showSuccess" class="fixed bottom-6 right-6 glass-card p-4 shadow-xl border-2 border-green-500">
          <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span class="font-bold text-gray-900">Paramètre mis à jour avec succès !</span>
          </div>
        </div>
      </Transition>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import Navbar from '../components/Navbar.vue';
import SystemSettingService from '../services/systemSettingService';
import { useAuthStore } from '../stores/auth';

const router = useRouter();
const authStore = useAuthStore();
const loading = ref(true);
const saving = ref(false);
const showSuccess = ref(false);
const proximitySetting = ref(null);
const proximityValue = ref(300);

const loadSettings = async () => {
  try {
    loading.value = true;
    const setting = await SystemSettingService.getSetting('pdv_proximity_threshold');
    proximitySetting.value = setting;
    proximityValue.value = setting.value;
  } catch (error) {
    console.error('Error loading settings:', error);
  } finally {
    loading.value = false;
  }
};

const saveProximityThreshold = async () => {
  try {
    saving.value = true;
    await SystemSettingService.updateSetting('pdv_proximity_threshold', proximityValue.value);
    proximitySetting.value.value = proximityValue.value;
    
    // Show success message
    showSuccess.value = true;
    setTimeout(() => {
      showSuccess.value = false;
    }, 3000);
  } catch (error) {
    console.error('Error saving setting:', error);
    alert('Erreur lors de la sauvegarde du paramètre');
  } finally {
    saving.value = false;
  }
};

onMounted(() => {
  // Vérifier que l'utilisateur est admin
  if (!authStore.isAdmin) {
    router.push({ name: 'Dashboard' });
    return;
  }
  
  loadSettings();
});
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: all 0.3s ease;
}

.slide-up-enter-from {
  transform: translateY(20px);
  opacity: 0;
}

.slide-up-leave-to {
  transform: translateY(20px);
  opacity: 0;
}
</style>
