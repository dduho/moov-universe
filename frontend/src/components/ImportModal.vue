<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 backdrop-blur-md"
      @click.self="close"
    >
      <div class="w-full max-w-3xl mx-4 bg-white rounded-2xl shadow-2xl overflow-hidden">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200">
          <div class="flex items-center justify-between">
            <h2 class="text-2xl font-bold text-gray-900">Importer des données</h2>
            <button
              @click="close"
              class="w-10 h-10 rounded-xl hover:bg-gray-100 flex items-center justify-center transition-colors"
            >
              <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
        </div>

        <!-- Content -->
        <div class="p-6">
          <!-- Step 1: Upload File -->
          <div v-if="step === 1">
            <div class="mb-6">
              <h3 class="text-lg font-bold text-gray-900 mb-2">Étape 1 : Sélectionner le fichier</h3>
              <p class="text-sm text-gray-600">Téléchargez un fichier Excel (.xlsx) ou CSV (.csv)</p>
            </div>

            <!-- File Upload Zone -->
            <div
              @drop.prevent="handleDrop"
              @dragover.prevent="isDragging = true"
              @dragleave="isDragging = false"
              class="border-2 border-dashed rounded-xl p-8 text-center transition-all"
              :class="isDragging ? 'border-moov-orange bg-moov-orange/10' : 'border-gray-300 hover:border-gray-400'"
            >
              <input
                ref="fileInput"
                type="file"
                accept=".xlsx,.csv"
                @change="handleFileSelect"
                class="hidden"
              />

              <div v-if="!selectedFile">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                </svg>
                <p class="text-gray-700 font-semibold mb-2">Glissez-déposez votre fichier ici</p>
                <p class="text-gray-500 text-sm mb-4">ou</p>
                <button
                  @click="$refs.fileInput.click()"
                  class="px-6 py-2 rounded-xl bg-moov-orange text-white font-bold hover:bg-moov-orange-dark transition-all"
                >
                  Parcourir les fichiers
                </button>
                <p class="text-xs text-gray-400 mt-4">Formats acceptés : .xlsx, .csv (max 10 MB)</p>
              </div>

              <div v-else class="flex items-center justify-between bg-gray-50 rounded-xl p-4">
                <div class="flex items-center gap-3">
                  <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  <div>
                    <p class="text-sm font-bold text-gray-900">{{ selectedFile.name }}</p>
                    <p class="text-xs text-gray-600">{{ formatFileSize(selectedFile.size) }}</p>
                  </div>
                </div>
                <button
                  @click="removeFile"
                  class="w-8 h-8 rounded-lg hover:bg-red-50 flex items-center justify-center transition-colors"
                >
                  <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                  </svg>
                </button>
              </div>
            </div>

            <!-- Template Download -->
            <div class="mt-6 p-4 bg-blue-50 rounded-xl border border-blue-200">
              <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="flex-1">
                  <p class="text-sm font-semibold text-blue-900 mb-1">Besoin d'un modèle ?</p>
                  <p class="text-sm text-blue-700 mb-3">Téléchargez notre template Excel pré-formaté pour faciliter l'import</p>
                  <button
                    @click="downloadTemplate"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-bold hover:bg-blue-700 transition-all"
                  >
                    Télécharger le template
                  </button>
                </div>
              </div>
            </div>

            <!-- Error -->
            <div v-if="error" class="mt-4 p-4 bg-red-50 rounded-xl border border-red-200">
              <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <p class="text-sm text-red-700">{{ error }}</p>
              </div>
            </div>
          </div>

          <!-- Step 2: Validation Results -->
          <div v-if="step === 2">
            <div class="mb-6">
              <h3 class="text-lg font-bold text-gray-900 mb-2">Étape 2 : Validation des données</h3>
              <p class="text-sm text-gray-600">Vérification de {{ totalRows }} ligne(s)</p>
            </div>

            <!-- Loading -->
            <div v-if="validating" class="py-12 flex flex-col items-center">
              <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-moov-orange mb-4"></div>
              <p class="text-gray-600 font-semibold">Validation en cours...</p>
            </div>

            <!-- Results -->
            <div v-else>
              <!-- Stats -->
              <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="p-4 rounded-xl bg-green-50 border border-green-200">
                  <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-green-500 flex items-center justify-center">
                      <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                      </svg>
                    </div>
                    <div>
                      <p class="text-sm text-gray-600">Lignes valides</p>
                      <p class="text-2xl font-bold text-green-700">{{ validationResult.valid.length }}</p>
                    </div>
                  </div>
                </div>

                <div class="p-4 rounded-xl bg-red-50 border border-red-200">
                  <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-red-500 flex items-center justify-center">
                      <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                      </svg>
                    </div>
                    <div>
                      <p class="text-sm text-gray-600">Lignes avec erreurs</p>
                      <p class="text-2xl font-bold text-red-700">{{ validationResult.errors.length }}</p>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Errors List -->
              <div v-if="validationResult.errors.length > 0" class="mb-6">
                <h4 class="text-sm font-bold text-gray-900 mb-3">Erreurs détectées :</h4>
                <div class="max-h-64 overflow-y-auto space-y-2 custom-scrollbar">
                  <div
                    v-for="(errorRow, index) in validationResult.errors.slice(0, 10)"
                    :key="index"
                    class="p-3 bg-red-50 rounded-lg border border-red-200"
                  >
                    <p class="text-sm font-semibold text-red-900 mb-1">Ligne {{ errorRow.line }}</p>
                    <ul class="text-sm text-red-700 list-disc list-inside">
                      <li v-for="(err, i) in errorRow.errors" :key="i">{{ err }}</li>
                    </ul>
                  </div>
                </div>
                <p v-if="validationResult.errors.length > 10" class="text-xs text-gray-500 mt-2">
                  ... et {{ validationResult.errors.length - 10 }} autre(s) erreur(s)
                </p>
              </div>

              <!-- Warning -->
              <div v-if="validationResult.errors.length > 0" class="p-4 bg-yellow-50 rounded-xl border border-yellow-200">
                <div class="flex items-start gap-3">
                  <svg class="w-6 h-6 text-yellow-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                  </svg>
                  <div>
                    <p class="text-sm font-semibold text-yellow-900">Attention</p>
                    <p class="text-sm text-yellow-700">Seules les lignes valides seront importées. Corrigez les erreurs et réessayez pour importer toutes les données.</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 3: Import Progress -->
          <div v-if="step === 3">
            <div class="py-12 flex flex-col items-center">
              <div class="animate-spin rounded-full h-16 w-16 border-b-2 border-moov-orange mb-4"></div>
              <p class="text-gray-900 font-bold text-lg mb-2">Import en cours...</p>
              <p class="text-gray-600">{{ importProgress }} / {{ validationResult.valid.length }} enregistrements</p>
              <div class="w-full max-w-md mt-4">
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div
                    class="h-full bg-moov-orange transition-all duration-300"
                    :style="{ width: `${(importProgress / validationResult.valid.length) * 100}%` }"
                  ></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Step 4: Success -->
          <div v-if="step === 4">
            <div class="py-12 flex flex-col items-center">
              <div class="w-20 h-20 rounded-full bg-green-500 flex items-center justify-center mb-4">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
              </div>
              <h3 class="text-2xl font-bold text-gray-900 mb-2">Import réussi !</h3>
              <p class="text-gray-600">{{ importedCount }} enregistrement(s) importé(s) avec succès</p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-6 bg-gray-50 border-t border-gray-200 flex justify-between">
          <button
            v-if="step > 1 && step < 4"
            @click="previousStep"
            class="px-6 py-2 rounded-xl bg-white border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 transition-all"
          >
            ← Retour
          </button>
          <div v-else></div>

          <div class="flex gap-3">
            <button
              @click="close"
              class="px-6 py-2 rounded-xl bg-white border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 transition-all"
            >
              {{ step === 4 ? 'Fermer' : 'Annuler' }}
            </button>
            
            <button
              v-if="step === 1"
              @click="validateFile"
              :disabled="!selectedFile"
              class="px-6 py-2 rounded-xl bg-moov-orange text-white font-bold hover:bg-moov-orange-dark transition-all disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Valider le fichier
            </button>
            
            <button
              v-if="step === 2 && validationResult.valid.length > 0"
              @click="startImport"
              class="px-6 py-2 rounded-xl bg-green-600 text-white font-bold hover:bg-green-700 transition-all"
            >
              Importer {{ validationResult.valid.length }} ligne(s)
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed } from 'vue';
import ExportService from '../services/ExportService';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  }
});

const emit = defineEmits(['close', 'import-success']);

const step = ref(1);
const selectedFile = ref(null);
const isDragging = ref(false);
const error = ref('');
const validating = ref(false);
const validationResult = ref({ valid: [], errors: [] });
const importProgress = ref(0);
const importedCount = ref(0);

const fileInput = ref(null);

const totalRows = computed(() => {
  return validationResult.value.valid.length + validationResult.value.errors.length;
});

const handleDrop = (e) => {
  isDragging.value = false;
  const file = e.dataTransfer.files[0];
  if (file) {
    validateFileType(file);
  }
};

const handleFileSelect = (e) => {
  const file = e.target.files[0];
  if (file) {
    validateFileType(file);
  }
};

const validateFileType = (file) => {
  const validTypes = [
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-excel',
    'text/csv'
  ];
  
  const validExtensions = ['.xlsx', '.csv'];
  const fileExtension = file.name.toLowerCase().substring(file.name.lastIndexOf('.'));
  
  if (!validTypes.includes(file.type) && !validExtensions.includes(fileExtension)) {
    error.value = 'Format de fichier non supporté. Veuillez utiliser .xlsx ou .csv';
    return;
  }
  
  if (file.size > 10 * 1024 * 1024) {
    error.value = 'Le fichier est trop volumineux (max 10 MB)';
    return;
  }
  
  selectedFile.value = file;
  error.value = '';
};

const removeFile = () => {
  selectedFile.value = null;
  if (fileInput.value) {
    fileInput.value.value = '';
  }
};

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const downloadTemplate = () => {
  ExportService.generatePDVTemplate();
};

const validateFile = async () => {
  if (!selectedFile.value) return;
  
  step.value = 2;
  validating.value = true;
  error.value = '';
  
  try {
    const data = await ExportService.importFile(selectedFile.value);
    validationResult.value = ExportService.validatePDVImport(data);
  } catch (err) {
    error.value = err.message;
    step.value = 1;
  } finally {
    validating.value = false;
  }
};

const startImport = async () => {
  step.value = 3;
  importProgress.value = 0;
  
  // Simulate import progress (replace with actual API calls)
  const total = validationResult.value.valid.length;
  for (let i = 0; i < total; i++) {
    await new Promise(resolve => setTimeout(resolve, 100));
    importProgress.value = i + 1;
  }
  
  importedCount.value = total;
  step.value = 4;
  
  // Emit success event
  setTimeout(() => {
    emit('import-success', validationResult.value.valid);
  }, 1000);
};

const previousStep = () => {
  if (step.value > 1) {
    step.value--;
  }
};

const close = () => {
  // Reset state
  step.value = 1;
  selectedFile.value = null;
  error.value = '';
  validationResult.value = { valid: [], errors: [] };
  importProgress.value = 0;
  importedCount.value = 0;
  
  emit('close');
};
</script>

<style scoped>
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(255, 107, 0, 0.3) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(255, 107, 0, 0.3);
  border-radius: 3px;
}
</style>
