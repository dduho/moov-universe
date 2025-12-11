<template>
  <div class="min-h-screen">
    <Navbar />

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
          <h1 class="text-4xl font-bold text-gray-900">Importer des PDV</h1>
          <p class="mt-2 text-gray-600">Importez plusieurs PDV en masse via un fichier Excel</p>
        </div>

        <!-- Steps Indicator -->
        <div class="mb-8">
          <div class="flex items-center justify-between">
            <div 
              v-for="(step, index) in steps" 
              :key="index"
              class="flex items-center"
              :class="{ 'flex-1': index < steps.length - 1 }"
            >
              <div class="flex items-center gap-3">
                <div 
                  class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all"
                  :class="currentStep >= index + 1 
                    ? 'bg-moov-orange text-white' 
                    : 'bg-gray-200 text-gray-500'"
                >
                  {{ index + 1 }}
                </div>
                <span 
                  class="font-semibold"
                  :class="currentStep >= index + 1 ? 'text-gray-900' : 'text-gray-400'"
                >
                  {{ step }}
                </span>
              </div>
              <div 
                v-if="index < steps.length - 1" 
                class="flex-1 h-1 mx-4"
                :class="currentStep > index + 1 ? 'bg-moov-orange' : 'bg-gray-200'"
              ></div>
            </div>
          </div>
        </div>

        <!-- Step 1: Upload File & Select Dealer -->
        <div v-if="currentStep === 1" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-8 rounded-2xl">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">1. Sélectionner le fichier et le dealer</h2>

          <!-- Download Template -->
          <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-xl">
            <div class="flex items-start gap-3">
              <svg class="w-6 h-6 text-blue-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <div class="flex-1">
                <p class="font-semibold text-blue-900 mb-2">Besoin d'un modèle ?</p>
                <p class="text-sm text-blue-700 mb-3">Téléchargez notre modèle Excel avec les en-têtes et exemples requis.</p>
                <button
                  @click="downloadTemplate"
                  class="px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors flex items-center gap-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  Télécharger le modèle
                </button>
              </div>
            </div>
          </div>

          <!-- Select Dealer -->
          <div class="mb-6">
            <FormSelect
              v-model="selectedDealerId"
              label="Dealer *"
              :options="dealers"
              option-label="name"
              option-value="id"
              placeholder="Sélectionnez un dealer"
              :error="errors.dealer"
            />
          </div>

          <!-- File Upload -->
          <div class="mb-6">
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Fichier Excel/CSV *
            </label>
            <div
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="handleFileDrop"
              class="relative border-2 border-dashed rounded-xl p-8 text-center transition-colors"
              :class="isDragging 
                ? 'border-moov-orange bg-orange-50' 
                : 'border-gray-300 hover:border-gray-400'"
            >
              <input
                ref="fileInput"
                type="file"
                accept=".xlsx,.xls,.csv"
                @change="handleFileSelect"
                class="hidden"
              />

              <div v-if="!selectedFile">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <p class="text-lg font-semibold text-gray-700 mb-2">
                  Glissez-déposez votre fichier ici
                </p>
                <p class="text-sm text-gray-500 mb-4">ou</p>
                <button
                  @click="$refs.fileInput.click()"
                  type="button"
                  class="px-6 py-3 bg-moov-orange text-white rounded-xl font-bold hover:bg-moov-orange-dark transition-all"
                >
                  Parcourir les fichiers
                </button>
                <p class="mt-4 text-xs text-gray-500">Formats acceptés: .xlsx, .xls, .csv (max 10MB)</p>
              </div>

              <div v-else class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-lg bg-green-100 flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                  </div>
                  <div class="text-left">
                    <p class="font-semibold text-gray-900">{{ selectedFile.name }}</p>
                    <p class="text-sm text-gray-500">{{ formatFileSize(selectedFile.size) }}</p>
                  </div>
                </div>
                <button
                  @click="removeFile"
                  type="button"
                  class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                  </svg>
                </button>
              </div>
            </div>
            <p v-if="errors.file" class="mt-2 text-sm text-red-600">{{ errors.file }}</p>
          </div>

          <!-- Actions -->
          <div class="flex justify-end gap-4">
            <button
              @click="$router.push('/pdv/list')"
              type="button"
              class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-bold hover:bg-gray-50 transition-all"
            >
              Annuler
            </button>
            <button
              @click="previewImport"
              :disabled="!selectedFile || !selectedDealerId || loading"
              class="px-6 py-3 bg-moov-orange text-white rounded-xl font-bold hover:bg-moov-orange-dark transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
            >
              <span v-if="loading" class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full"></span>
              Valider et prévisualiser
            </button>
          </div>
        </div>

        <!-- Step 2: Preview & Validate -->
        <div v-if="currentStep === 2" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-8 rounded-2xl">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">2. Vérification des données</h2>

          <!-- Summary Cards -->
          <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
            <div class="p-4 rounded-xl bg-blue-50 border border-blue-200">
              <p class="text-sm font-semibold text-blue-700 mb-1">Total lignes</p>
              <p class="text-3xl font-bold text-blue-900">{{ previewData.summary?.total_lines || 0 }}</p>
            </div>
            <div class="p-4 rounded-xl bg-green-50 border border-green-200">
              <p class="text-sm font-semibold text-green-700 mb-1">Nouveaux</p>
              <p class="text-3xl font-bold text-green-900">{{ previewData.summary?.valid || 0 }}</p>
            </div>
            <div class="p-4 rounded-xl bg-orange-50 border border-orange-200">
              <p class="text-sm font-semibold text-orange-700 mb-1">À mettre à jour</p>
              <p class="text-3xl font-bold text-orange-900">{{ previewData.summary?.to_update || 0 }}</p>
            </div>
            <div class="p-4 rounded-xl bg-red-50 border border-red-200">
              <p class="text-sm font-semibold text-red-700 mb-1">Erreurs</p>
              <p class="text-3xl font-bold text-red-900">{{ previewData.summary?.invalid || 0 }}</p>
            </div>
            <div class="p-4 rounded-xl bg-yellow-50 border border-yellow-200">
              <p class="text-sm font-semibold text-yellow-700 mb-1">Doublons</p>
              <p class="text-3xl font-bold text-yellow-900">{{ previewData.summary?.duplicates || 0 }}</p>
            </div>
          </div>

          <!-- Tabs -->
          <div class="mb-6">
            <div class="flex gap-2 border-b border-gray-200">
              <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key"
                class="px-4 py-3 font-semibold transition-colors border-b-2"
                :class="activeTab === tab.key
                  ? 'border-moov-orange text-moov-orange'
                  : 'border-transparent text-gray-600 hover:text-gray-900'"
              >
                {{ tab.label }} ({{ tab.count }})
              </button>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex justify-between gap-4 mb-6">
            <button
              @click="currentStep = 1"
              type="button"
              class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-bold hover:bg-gray-50 transition-all"
            >
              Retour
            </button>
            <div class="flex gap-4">
              <button
                v-if="(previewData.summary?.valid > 0 || previewData.summary?.to_update > 0) && previewData.summary?.invalid === 0"
                @click="proceedToImport"
                :disabled="loading"
                class="px-6 py-3 bg-moov-orange text-white rounded-xl font-bold hover:bg-moov-orange-dark transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
              >
                <span v-if="loading" class="animate-spin w-5 h-5 border-2 border-white border-t-transparent rounded-full"></span>
                Importer {{ (previewData.summary?.valid || 0) + (previewData.summary?.to_update || 0) }} PDV
              </button>
            </div>
          </div>

          <!-- Valid Rows -->
          <div v-if="activeTab === 'valid' && previewData.valid_rows?.length > 0" class="overflow-x-auto">
            <table class="w-full">
              <thead>
                <tr class="border-b border-gray-200">
                  <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Ligne</th>
                  <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Nom PDV</th>
                  <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Numéro Flooz</th>
                  <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Ville</th>
                  <th class="px-4 py-3 text-left text-sm font-bold text-gray-700">Profil</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="row in previewData.valid_rows"
                  :key="row.line"
                  class="border-b border-gray-100 hover:bg-gray-50"
                >
                  <td class="px-4 py-3 text-sm text-gray-700">{{ row.line }}</td>
                  <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ row.data.nom_point }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ row.data.numero_flooz }}</td>
                  <td class="px-4 py-3 text-sm text-gray-700">{{ row.data.ville }}</td>
                  <td class="px-4 py-3 text-sm">
                    <span class="px-2 py-1 rounded-lg bg-blue-100 text-blue-800 text-xs font-semibold">
                      {{ row.data.profil }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- To Update -->
          <div v-if="activeTab === 'to_update' && previewData.to_update?.length > 0">
            <div
              v-for="item in previewData.to_update"
              :key="item.line"
              class="p-4 mb-3 rounded-xl bg-orange-50 border border-orange-200"
            >
              <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-2">
                  <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                  </svg>
                  <span class="text-sm font-semibold text-orange-700">Ligne {{ item.line }}</span>
                </div>
                <span class="text-xs text-orange-600 bg-orange-100 px-2 py-1 rounded-full">
                  {{ item.message }}
                </span>
              </div>
              <div class="grid grid-cols-2 gap-2 text-sm">
                <div><span class="font-semibold text-gray-600">Nom:</span> {{ item.data.nom_point }}</div>
                <div><span class="font-semibold text-gray-600">Flooz:</span> {{ item.data.numero_flooz }}</div>
                <div><span class="font-semibold text-gray-600">Shortcode:</span> {{ item.data.shortcode || 'N/A' }}</div>
                <div><span class="font-semibold text-gray-600">Région:</span> {{ item.data.region }}</div>
              </div>
            </div>
          </div>

          <!-- Invalid Rows -->
          <div v-if="activeTab === 'invalid' && previewData.invalid_rows?.length > 0">
            <div
              v-for="row in previewData.invalid_rows"
              :key="row.line"
              class="mb-4 p-4 rounded-xl bg-red-50 border border-red-200"
            >
              <div class="flex items-start justify-between mb-2">
                <span class="font-bold text-red-900">Ligne {{ row.line }}</span>
              </div>
              <div class="text-sm text-gray-700 mb-2">
                <strong>PDV:</strong> {{ row.data.nom_point || 'N/A' }} • 
                <strong>Flooz:</strong> {{ row.data.numero_flooz || 'N/A' }}
              </div>
              <div class="space-y-1">
                <p
                  v-for="(error, index) in row.errors"
                  :key="index"
                  class="text-sm text-red-700 flex items-start gap-2"
                >
                  <svg class="w-4 h-4 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                  </svg>
                  {{ error }}
                </p>
              </div>
            </div>
          </div>

          <!-- Duplicates -->
          <div v-if="activeTab === 'duplicates' && previewData.duplicates?.length > 0">
            <div
              v-for="dup in previewData.duplicates"
              :key="dup.line"
              class="mb-4 p-4 rounded-xl bg-yellow-50 border border-yellow-200"
            >
              <div class="flex items-start justify-between mb-2">
                <span class="font-bold text-yellow-900">Ligne {{ dup.line }}</span>
                <span class="text-xs text-yellow-700">Existe déjà (ID: {{ dup.existing_id }})</span>
              </div>
              <div class="text-sm text-gray-700">
                <strong>PDV:</strong> {{ dup.data.nom_point }} • 
                <strong>Flooz:</strong> {{ dup.data.numero_flooz }}
              </div>
              <p class="text-sm text-yellow-700 mt-2">{{ dup.message }}</p>
            </div>
          </div>
        </div>

        <!-- Step 3: Import Complete -->
        <div v-if="currentStep === 3" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-8 rounded-2xl text-center">
          <div class="w-20 h-20 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-6">
            <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
          </div>
          
          <h2 class="text-3xl font-bold text-gray-900 mb-4">Import réussi !</h2>
          
          <div class="mb-8 space-y-2">
            <p v-if="importResult.summary?.imported > 0" class="text-lg text-green-600 font-semibold">
              {{ importResult.summary.imported }} PDV ont été créés avec succès.
            </p>
            <p v-if="importResult.summary?.updated > 0" class="text-lg text-orange-600 font-semibold">
              {{ importResult.summary.updated }} PDV ont été mis à jour avec succès.
            </p>
          </div>

          <div v-if="importResult.summary?.skipped > 0" class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-xl">
            <p class="text-sm text-yellow-800">
              {{ importResult.summary.skipped }} doublons ont été ignorés.
            </p>
          </div>

          <div class="flex justify-center gap-4">
            <button
              @click="reset"
              class="px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-bold hover:bg-gray-50 transition-all"
            >
              Nouvel import
            </button>
            <button
              @click="$router.push('/pdv/list')"
              class="px-6 py-3 bg-moov-orange text-white rounded-xl font-bold hover:bg-moov-orange-dark transition-all"
            >
              Voir la liste des PDV
            </button>
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
import FormSelect from '../components/FormSelect.vue';
import ImportService from '../services/ImportService';
import OrganizationService from '../services/OrganizationService';
import { useToast } from '../composables/useToast';

const router = useRouter();
const { toast } = useToast();

const steps = ['Sélection', 'Vérification', 'Importation'];
const currentStep = ref(1);

const selectedFile = ref(null);
const selectedDealerId = ref('');
const dealers = ref([]);
const isDragging = ref(false);
const loading = ref(false);
const errors = ref({});

const previewData = ref({});
const importResult = ref({});

const activeTab = ref('valid');
const tabs = computed(() => [
  { key: 'valid', label: 'Nouveaux PDV', count: previewData.value.summary?.valid || 0 },
  { key: 'to_update', label: 'À mettre à jour', count: previewData.value.summary?.to_update || 0 },
  { key: 'invalid', label: 'Erreurs', count: previewData.value.summary?.invalid || 0 },
  { key: 'duplicates', label: 'Doublons', count: previewData.value.summary?.duplicates || 0 },
]);

const fileInput = ref(null);

const handleFileSelect = (event) => {
  const file = event.target.files[0];
  if (file) {
    validateAndSetFile(file);
  }
};

const handleFileDrop = (event) => {
  isDragging.value = false;
  const file = event.dataTransfer.files[0];
  if (file) {
    validateAndSetFile(file);
  }
};

const validateAndSetFile = (file) => {
  errors.value.file = '';
  
  // Vérifier l'extension
  const allowedExtensions = ['.xlsx', '.xls', '.csv'];
  const fileExtension = '.' + file.name.split('.').pop().toLowerCase();
  
  if (!allowedExtensions.includes(fileExtension)) {
    errors.value.file = 'Format de fichier non supporté. Utilisez .xlsx, .xls ou .csv';
    return;
  }

  // Vérifier la taille (10MB max)
  if (file.size > 10 * 1024 * 1024) {
    errors.value.file = 'Le fichier est trop volumineux (max 10MB)';
    return;
  }

  selectedFile.value = file;
};

const removeFile = () => {
  selectedFile.value = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const downloadTemplate = async () => {
  try {
    await ImportService.downloadTemplate();
    toast.success('Modèle téléchargé avec succès');
  } catch (error) {
    console.error('Erreur téléchargement:', error);
    toast.error('Erreur lors du téléchargement du modèle');
  }
};

const previewImport = async () => {
  errors.value = {};
  
  if (!selectedFile.value) {
    errors.value.file = 'Veuillez sélectionner un fichier';
    return;
  }

  if (!selectedDealerId.value) {
    errors.value.dealer = 'Veuillez sélectionner un dealer';
    return;
  }

  loading.value = true;
  try {
    previewData.value = await ImportService.previewImport(selectedFile.value, selectedDealerId.value);
    currentStep.value = 2;
    
    if (previewData.value.summary.invalid > 0 || previewData.value.summary.duplicates > 0) {
      activeTab.value = previewData.value.summary.invalid > 0 ? 'invalid' : 'duplicates';
    }
  } catch (error) {
    console.error('Erreur prévisualisation:', error);
    const errorMessage = error.response?.data?.error || error.response?.data?.message || 'Erreur lors de la prévisualisation';
    toast.error(errorMessage);
  } finally {
    loading.value = false;
  }
};

const proceedToImport = async () => {
  loading.value = true;
  try {
    importResult.value = await ImportService.importPDV(selectedFile.value, selectedDealerId.value, true);
    currentStep.value = 3;
    toast.success('Import réussi !');
  } catch (error) {
    console.error('Erreur import:', error);
    const errorMessage = error.response?.data?.error || error.response?.data?.message || 'Erreur lors de l\'import';
    toast.error(errorMessage);
  } finally {
    loading.value = false;
  }
};

const reset = () => {
  currentStep.value = 1;
  selectedFile.value = null;
  selectedDealerId.value = '';
  previewData.value = {};
  importResult.value = {};
  errors.value = {};
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const loadDealers = async () => {
  try {
    const response = await OrganizationService.getAll();
    dealers.value = response.data || response;
  } catch (error) {
    console.error('Erreur chargement dealers:', error);
    toast.error('Erreur lors du chargement des dealers');
  }
};

onMounted(() => {
  loadDealers();
});
</script>


