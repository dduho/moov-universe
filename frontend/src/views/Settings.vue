<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8">
      <!-- Header -->
      <div class="mb-6 sm:mb-8">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-1 sm:mb-2">Paramètres Système</h1>
        <p class="text-sm sm:text-base text-gray-600">Configurer les paramètres globaux de l'application</p>
      </div>

      <!-- Loading state -->
      <div v-if="loading" class="glass-card p-6 sm:p-8 text-center">
        <div class="animate-spin rounded-full h-10 w-10 sm:h-12 sm:w-12 border-b-2 border-primary-600 mx-auto"></div>
        <p class="mt-3 sm:mt-4 text-sm sm:text-base text-gray-600">Chargement des paramètres...</p>
      </div>

      <!-- Settings list -->
      <div v-else class="space-y-4 sm:space-y-6">
        <!-- Proximity Threshold Setting -->
        <div class="glass-card p-4 sm:p-6">
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 sm:gap-4 mb-4">
            <div class="flex-1">
              <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1">Distance Minimale entre PDV</h3>
              <p class="text-xs sm:text-sm text-gray-600">{{ proximitySetting?.description }}</p>
            </div>
            <span class="self-start px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">
              Obligatoire
            </span>
          </div>

          <div class="space-y-3 sm:space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
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

        <!-- GPS Accuracy Threshold Setting -->
        <div class="glass-card p-4 sm:p-6">
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 sm:gap-4 mb-4">
            <div class="flex-1">
              <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1">Seuil de Précision GPS</h3>
              <p class="text-xs sm:text-sm text-gray-600">{{ gpsAccuracySetting?.description || 'Précision GPS maximale acceptée en mètres' }}</p>
            </div>
            <span class="self-start px-3 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700">
              Qualité GPS
            </span>
          </div>

          <div class="space-y-3 sm:space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-start gap-3 sm:gap-4">
              <div class="flex-1">
                <div class="relative">
                  <input
                    v-model.number="gpsAccuracyValue"
                    type="number"
                    min="5"
                    max="100"
                    step="5"
                    class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all"
                    :disabled="savingGpsAccuracy"
                  />
                  <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 font-medium">mètres</span>
                </div>
              </div>

              <button
                @click="saveGpsAccuracy"
                :disabled="savingGpsAccuracy || gpsAccuracyValue === parseInt(gpsAccuracySetting?.value)"
                class="px-6 py-3 rounded-lg font-bold transition-all whitespace-nowrap"
                :class="[
                  gpsAccuracyValue === parseInt(gpsAccuracySetting?.value)
                    ? 'bg-gray-200 text-gray-700' 
                    : 'bg-primary-600 text-white hover:bg-primary-700 shadow-lg hover:shadow-xl',
                  (savingGpsAccuracy || gpsAccuracyValue === parseInt(gpsAccuracySetting?.value)) && 'opacity-50 cursor-not-allowed'
                ]"
              >
                <span v-if="savingGpsAccuracy" class="flex items-center gap-2">
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
              Valeur recommandée : entre 20m et 50m. Une valeur plus basse exige une meilleure précision GPS.
            </p>
          </div>

          <!-- Visual indicator -->
          <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <div class="flex items-center gap-3 mb-3">
              <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
              </svg>
              <span class="text-sm font-bold text-gray-900">Impact de ce paramètre</span>
            </div>
            <ul class="space-y-2 text-sm text-gray-600">
              <li class="flex items-start gap-2">
                <svg class="w-4 h-4 text-orange-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                Alerte visuelle sur la fiche PDV si la précision dépasse ce seuil
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-4 h-4 text-orange-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                Indicateur lors de la capture GPS si la précision est insuffisante
              </li>
              <li class="flex items-start gap-2">
                <svg class="w-4 h-4 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                </svg>
                Assure la qualité des données de géolocalisation
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
import { useToast } from '../composables/useToast';

const router = useRouter();
const authStore = useAuthStore();
const { toast } = useToast();
const loading = ref(true);
const saving = ref(false);
const showSuccess = ref(false);
const proximitySetting = ref(null);
const proximityValue = ref(300);
const gpsAccuracySetting = ref(null);
const gpsAccuracyValue = ref(30);
const savingGpsAccuracy = ref(false);

const loadSettings = async () => {
  try {
    loading.value = true;
    const [proximitySett, gpsAccuracySett] = await Promise.all([
      SystemSettingService.getSetting('pdv_proximity_threshold'),
      SystemSettingService.getSetting('gps_accuracy_max').catch(() => ({ value: 30, description: 'Précision GPS maximale acceptée en mètres' }))
    ]);
    proximitySetting.value = proximitySett;
    proximityValue.value = proximitySett.value;
    gpsAccuracySetting.value = gpsAccuracySett;
    gpsAccuracyValue.value = parseInt(gpsAccuracySett.value) || 30;
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
    toast.error('Erreur lors de la sauvegarde du paramètre');
  } finally {
    saving.value = false;
  }
};

const saveGpsAccuracy = async () => {
  try {
    savingGpsAccuracy.value = true;
    await SystemSettingService.updateSetting('gps_accuracy_max', gpsAccuracyValue.value);
    gpsAccuracySetting.value.value = gpsAccuracyValue.value;
    
    // Show success message
    showSuccess.value = true;
    setTimeout(() => {
      showSuccess.value = false;
    }, 3000);
  } catch (error) {
    console.error('Error saving GPS accuracy setting:', error);
    toast.error('Erreur lors de la sauvegarde du paramètre');
  } finally {
    savingGpsAccuracy.value = false;
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
