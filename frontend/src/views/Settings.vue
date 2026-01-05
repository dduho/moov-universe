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
      <div v-if="loading" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 sm:p-8 text-center">
        <div class="animate-spin rounded-full h-10 w-10 sm:h-12 sm:w-12 border-b-2 border-moov-orange mx-auto"></div>
        <p class="mt-3 sm:mt-4 text-sm sm:text-base text-gray-600">Chargement des paramètres...</p>
      </div>

      <!-- Settings list -->
      <div v-else class="space-y-4 sm:space-y-6">
        <!-- Proximity Threshold Setting -->
        <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6">
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
                    class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-moov-orange focus:ring-2 focus:ring-moov-orange/20 transition-all"
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
                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed' 
                    : 'bg-moov-orange text-white hover:bg-moov-orange-dark shadow-lg hover:shadow-xl',
                  saving && 'opacity-50 cursor-not-allowed'
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
              <svg class="w-5 h-5 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6">
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
                    class="w-full px-4 py-3 rounded-lg border-2 border-gray-200 focus:border-moov-orange focus:ring-2 focus:ring-moov-orange/20 transition-all"
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
                    ? 'bg-gray-200 text-gray-500 cursor-not-allowed' 
                    : 'bg-moov-orange text-white hover:bg-moov-orange-dark shadow-lg hover:shadow-xl',
                  savingGpsAccuracy && 'opacity-50 cursor-not-allowed'
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

        <!-- Transaction Import Setting -->
        <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6">
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 sm:gap-4 mb-4">
            <div class="flex-1">
              <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1">Import de Données Transactionnelles</h3>
              <p class="text-xs sm:text-sm text-gray-600">Charger les fichiers Excel (.xls, .xlsx) contenant les données de transactions des PDV</p>
            </div>
            <span class="self-start px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">
              Analytique
            </span>
          </div>

          <div class="space-y-4">
            <!-- File Upload Area -->
            <div 
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="handleFileDrop"
              class="border-2 border-dashed rounded-lg p-6 transition-all"
              :class="isDragging ? 'border-moov-orange bg-orange-50' : 'border-gray-300 hover:border-moov-orange'"
            >
              <input
                ref="fileInput"
                type="file"
                multiple
                accept=".xls,.xlsx"
                @change="handleFileSelect"
                class="hidden"
              />
              
              <div class="text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                  <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <p class="mt-2 text-sm text-gray-600">
                  <button @click="$refs.fileInput.click()" type="button" class="font-semibold text-moov-orange hover:text-orange-600">
                    Cliquez pour sélectionner
                  </button>
                  ou glissez-déposez vos fichiers
                </p>
                <p class="mt-1 text-xs text-gray-500">Fichiers Excel (.xls, .xlsx) jusqu'à 500MB chacun</p>
              </div>
            </div>

            <!-- Selected Files List -->
            <div v-if="selectedFiles.length > 0" class="space-y-2">
              <h4 class="text-sm font-semibold text-gray-900">Fichiers sélectionnés ({{ selectedFiles.length }})</h4>
              <ul class="space-y-2">
                <li v-for="(file, index) in selectedFiles" :key="index" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div class="flex items-center gap-3 flex-1 min-w-0">
                    <svg class="w-8 h-8 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                    </svg>
                    <div class="flex-1 min-w-0">
                      <p class="text-sm font-medium text-gray-900 truncate">{{ file.name }}</p>
                      <p class="text-xs text-gray-500">{{ formatFileSize(file.size) }}</p>
                    </div>
                  </div>
                  <button 
                    @click="removeFile(index)"
                    class="ml-2 text-red-600 hover:text-red-800 flex-shrink-0"
                    :disabled="uploading"
                  >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                  </button>
                </li>
              </ul>
            </div>

            <!-- Upload Button -->
            <button
              @click="uploadTransactionFiles"
              :disabled="selectedFiles.length === 0 || uploading"
              class="w-full py-3 px-4 rounded-lg font-semibold text-white transition-all"
              :class="selectedFiles.length === 0 || uploading 
                ? 'bg-gray-300 cursor-not-allowed' 
                : 'bg-moov-orange hover:bg-orange-600 shadow-lg hover:shadow-xl'"
            >
              <span v-if="uploading" class="flex flex-col items-center justify-center gap-1">
                <div class="flex items-center gap-2">
                  <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span v-if="uploadStage === 'uploading'">Envoi du fichier... {{ uploadProgress }}%</span>
                  <span v-else-if="uploadStage === 'processing'">Traitement en cours... {{ uploadProgress }}%</span>
                  <span v-else>Finalisation...</span>
                </div>
                <span class="text-xs opacity-90">
                  <span v-if="uploadStage === 'uploading'">Envoi de {{ formatFileSize(getTotalFileSize()) }} vers le serveur</span>
                  <span v-else-if="uploadStage === 'processing'">Traitement des données (peut prendre plusieurs minutes pour de gros fichiers)</span>
                </span>
              </span>
              <span v-else>
                Importer {{ selectedFiles.length }} fichier(s)
              </span>
            </button>

            <!-- Progress Bar with Stage Colors -->
            <div v-if="uploading" class="space-y-2">
              <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                <div 
                  class="h-2.5 rounded-full transition-all duration-300"
                  :class="{
                    'bg-blue-500': uploadStage === 'uploading',
                    'bg-moov-orange': uploadStage === 'processing',
                    'bg-green-500': uploadStage === 'complete'
                  }"
                  :style="{ width: uploadProgress + '%' }"
                ></div>
              </div>
              <div class="flex justify-between text-xs text-gray-600">
                <span :class="uploadStage === 'uploading' ? 'font-semibold text-blue-600' : ''">📤 Upload</span>
                <span :class="uploadStage === 'processing' ? 'font-semibold text-orange-600' : ''">⚙️ Traitement</span>
                <span :class="uploadStage === 'complete' ? 'font-semibold text-green-600' : ''">✅ Terminé</span>
              </div>
            </div>

            <!-- Import Results -->
            <div v-if="importResults" class="space-y-3">
              <!-- Success -->
              <div v-if="importResults.success.length > 0" class="p-4 bg-green-50 rounded-lg border border-green-200">
                <h4 class="text-sm font-semibold text-green-900 mb-2">✅ Import réussi ({{ importResults.success.length }} fichier(s))</h4>
                <ul class="space-y-1 text-sm text-green-800">
                  <li v-for="(result, index) in importResults.success" :key="index">
                    <strong>{{ result.filename }}</strong>: {{ result.imported }} nouvelles entrées, {{ result.updated }} mises à jour (Date: {{ result.date }})
                  </li>
                </ul>
                <p class="mt-2 text-sm font-semibold text-green-900">
                  Total: {{ importResults.total_imported }} nouvelles entrées, {{ importResults.total_updated }} mises à jour
                </p>
              </div>

              <!-- Errors -->
              <div v-if="importResults.errors.length > 0" class="p-4 bg-red-50 rounded-lg border border-red-200">
                <h4 class="text-sm font-semibold text-red-900 mb-2">❌ Erreurs ({{ importResults.errors.length }} fichier(s))</h4>
                <ul class="space-y-1 text-sm text-red-800">
                  <li v-for="(error, index) in importResults.errors" :key="index">
                    <strong>{{ error.filename }}</strong>: {{ error.error }}
                  </li>
                </ul>
              </div>
            </div>

            <!-- Information box -->
            <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
              <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                  <p class="text-sm font-semibold text-blue-900 mb-2">Format attendu des fichiers :</p>
                  <ul class="space-y-1 text-sm text-blue-800">
                    <li class="flex items-start gap-2">
                      <span class="text-moov-orange">•</span>
                      <span>Date de la période dans la cellule A6 (ex: "Start Date: 15/12/2025")</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <span class="text-moov-orange">•</span>
                      <span>En-têtes des colonnes à la ligne 11</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <span class="text-moov-orange">•</span>
                      <span>Données à partir de la ligne 12</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <span class="text-moov-orange">•</span>
                      <span>Colonne PDV_NUMERO obligatoire (numéro Flooz du PDV)</span>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Email Notifications Setting -->
        <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6">
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 sm:gap-4 mb-4">
            <div class="flex-1">
              <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1">Notifications par Email</h3>
              <p class="text-xs sm:text-sm text-gray-600">Activer ou désactiver l'envoi de toutes les notifications par email</p>
            </div>
            <span class="self-start px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-700">
              Communication
            </span>
          </div>

          <div class="space-y-4">
            <!-- SMTP Status Warning -->
            <div v-if="!smtpConfigured && !checkingSmtp" class="p-4 bg-red-50 rounded-lg border border-red-200">
              <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                  <p class="text-sm font-semibold text-red-900">⚠️ Serveur SMTP non configuré</p>
                  <p class="text-xs text-red-800 mt-1">
                    Les notifications par email ne peuvent pas être activées tant que le serveur SMTP n'est pas configuré dans le fichier .env
                  </p>
                </div>
              </div>
            </div>

            <!-- Toggle Switch -->
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg"
                 :class="!smtpConfigured ? 'opacity-50' : ''">
              <div class="flex-1">
                <p class="font-semibold text-gray-900">Envoi d'emails</p>
                <p class="text-sm text-gray-600 mt-1">
                  {{ mailNotificationsEnabled ? 'Les emails sont actuellement activés' : 'Les emails sont actuellement désactivés' }}
                </p>
                <p v-if="smtpConfigured" class="text-xs text-green-600 mt-1 flex items-center gap-1">
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                  </svg>
                  SMTP configuré et actif
                </p>
              </div>
              
              <button
                @click="toggleEmailNotifications"
                :disabled="savingMailNotifications || (!smtpConfigured && !mailNotificationsEnabled)"
                class="relative inline-flex h-8 w-14 items-center rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-moov-orange focus:ring-offset-2"
                :class="[
                  mailNotificationsEnabled ? 'bg-moov-orange' : 'bg-gray-300',
                  (!smtpConfigured && !mailNotificationsEnabled) ? 'cursor-not-allowed opacity-50' : ''
                ]"
              >
                <span
                  class="inline-block h-6 w-6 transform rounded-full bg-white transition-transform shadow-lg"
                  :class="mailNotificationsEnabled ? 'translate-x-7' : 'translate-x-1'"
                />
              </button>
            </div>

            <!-- Information box -->
            <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
              <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div class="flex-1">
                  <p class="text-sm font-semibold text-blue-900 mb-2">Types d'emails concernés :</p>
                  <ul class="space-y-1 text-sm text-blue-800">
                    <li class="flex items-start gap-2">
                      <span class="text-moov-orange">•</span>
                      <span>Assignation de tâches aux commerciaux</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <span class="text-moov-orange">•</span>
                      <span>Notification de complétion de tâches (admins)</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <span class="text-moov-orange">•</span>
                      <span>Validation et révision de tâches</span>
                    </li>
                    <li class="flex items-start gap-2">
                      <span class="text-moov-orange">•</span>
                      <span>Création et modification d'utilisateurs</span>
                    </li>
                  </ul>
                  <p class="text-xs text-blue-700 mt-3 italic">
                    Note : Les notifications dans l'application restent actives même si les emails sont désactivés.
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Advanced Cache Management Section -->
        <div v-if="authStore.isAdmin && !cacheSettingsError" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6 mt-10">
          <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 sm:gap-4 mb-4">
            <div class="flex-1">
              <h3 class="text-base sm:text-lg font-bold text-gray-900 mb-1">Gestion avancée du cache</h3>
              <p class="text-xs sm:text-sm text-gray-600">Activez, désactivez, ajustez la durée ou videz le cache pour chaque widget avancé.</p>
            </div>
            <button @click="clearAllCaches" class="px-4 py-2 rounded-lg font-bold bg-red-600 text-white hover:bg-red-700 shadow-lg">Vider tous les caches</button>
          </div>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Widget</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cache activé</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Durée (minutes)</th>
                  <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr v-for="widget in cacheWidgets" :key="widget.key">
                  <td class="px-4 py-2 font-semibold text-gray-900">{{ widget.label }}</td>
                  <td class="px-4 py-2">
                    <input type="checkbox" v-model="widget.enabled" @change="updateCacheSetting(widget)" class="form-checkbox h-5 w-5 text-moov-orange" />
                  </td>
                  <td class="px-4 py-2">
                    <input type="number" min="1" max="1440" v-model.number="widget.ttl" @change="updateCacheSetting(widget)" class="w-24 px-2 py-1 border rounded-lg focus:ring-moov-orange focus:border-moov-orange" />
                  </td>
                  <td class="px-4 py-2">
                    <button @click="clearWidgetCache(widget)" class="px-3 py-1 rounded bg-red-100 text-red-700 hover:bg-red-200 font-semibold">Vider le cache</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        <div v-if="cacheSettingsError" class="bg-red-100 border border-red-300 text-red-700 rounded-lg p-4 mt-10">
          <strong>Erreur lors du chargement de la gestion du cache :</strong>
          <span>{{ cacheSettingsError }}</span>
        </div>

        <!-- Future settings placeholder -->
        <!-- <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 border-2 border-dashed border-gray-300">
          <div class="text-center py-8">
            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <p class="text-gray-500 font-medium">D'autres paramètres seront ajoutés ici</p>
            <p class="text-sm text-gray-400 mt-1">Personnalisation, notifications, exports, etc.</p>
          </div>
        </div> -->
      </div>

      <!-- Success message -->
      <Transition name="slide-up">
        <div v-if="showSuccess" class="fixed bottom-6 right-6 bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 shadow-xl border-2 border-green-500">
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
import { ref, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import Navbar from '../components/Navbar.vue';
import SystemSettingService from '../services/systemSettingService';
import TransactionService from '../services/transactionService';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';
import SettingService from '../services/SettingService';

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
const mailNotificationsEnabled = ref(true);
const savingMailNotifications = ref(false);
const smtpConfigured = ref(false);
const checkingSmtp = ref(false);

// Transaction import
const fileInput = ref(null);
const selectedFiles = ref([]);
const isDragging = ref(false);
const uploading = ref(false);
const uploadProgress = ref(0);
const uploadStage = ref(''); // 'uploading', 'processing', 'complete'
const importResults = ref(null);

// Advanced Cache Management
const cacheWidgets = ref([
  { key: 'network_optimization', label: 'Optimisation Réseau', enabled: true, ttl: 60 },
  { key: 'risk_compliance', label: 'Risques & Conformité', enabled: true, ttl: 60 },
  { key: 'advanced_geospatial', label: 'Analyse Géospatiale', enabled: true, ttl: 60 },
  { key: 'offline_dashboard', label: 'Tableau de bord hors ligne', enabled: true, ttl: 60 },
  { key: 'dealer_analytics', label: 'Analytics Dealer', enabled: true, ttl: 60 },
]);
const cacheSettingsError = ref(null);

const loadSettings = async () => {
  try {
    loading.value = true;
    const [proximitySett, gpsAccuracySett, mailNotifSett] = await Promise.all([
      SystemSettingService.getSetting('pdv_proximity_threshold'),
      SystemSettingService.getSetting('gps_accuracy_max').catch(() => ({ value: 30, description: 'Précision GPS maximale acceptée en mètres' })),
      SystemSettingService.getSetting('mail_notifications_enabled').catch(() => ({ value: true }))
    ]);
    proximitySetting.value = proximitySett;
    proximityValue.value = proximitySett.value;
    gpsAccuracySetting.value = gpsAccuracySett;
    gpsAccuracyValue.value = parseInt(gpsAccuracySett.value) || 30;
    mailNotificationsEnabled.value = mailNotifSett.value;
    
    // Vérifier la configuration SMTP
    await checkSmtpConfiguration();
    
    // Charger les paramètres de cache
    cacheSettingsError.value = null;
    try {
      await fetchCacheSettings();
    } catch (err) {
      cacheSettingsError.value = err?.response?.data?.error || err.message || 'Erreur inconnue';
    }
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

const toggleEmailNotifications = async () => {
  // Vérifier SMTP avant d'activer
  if (!mailNotificationsEnabled.value && !smtpConfigured.value) {
    toast.error('Serveur SMTP non configuré. Veuillez configurer SMTP dans le fichier .env');
    return;
  }

  try {
    savingMailNotifications.value = true;
    const newValue = !mailNotificationsEnabled.value;
    
    // Si on active, re-vérifier SMTP
    if (newValue) {
      const smtpCheck = await checkSmtpConfiguration();
      if (!smtpCheck) {
        toast.error('Impossible d\'activer : serveur SMTP non accessible');
        return;
      }
    }
    
    await SystemSettingService.updateSetting('mail_notifications_enabled', newValue.toString());
    mailNotificationsEnabled.value = newValue;
    
    // Show success message
    showSuccess.value = true;
    setTimeout(() => {
      showSuccess.value = false;
    }, 3000);
    
    toast.success(
      newValue 
        ? 'Notifications par email activées' 
        : 'Notifications par email désactivées'
    );
  } catch (error) {
    console.error('Error toggling email notifications:', error);
    toast.error('Erreur lors de la modification du paramètre');
  } finally {
    savingMailNotifications.value = false;
  }
};

// Transaction import functions
const handleFileSelect = (event) => {
  const files = Array.from(event.target.files);
  addFiles(files);
};

const handleFileDrop = (event) => {
  isDragging.value = false;
  const files = Array.from(event.dataTransfer.files);
  addFiles(files);
};

const addFiles = (files) => {
  const xlsFiles = files.filter(file => 
    file.name.endsWith('.xls') || file.name.endsWith('.xlsx')
  );
  
  if (xlsFiles.length < files.length) {
    toast.warning('Seuls les fichiers .xls et .xlsx sont acceptés');
  }
  
  selectedFiles.value.push(...xlsFiles);
};

const removeFile = (index) => {
  selectedFiles.value.splice(index, 1);
};

const formatFileSize = (bytes) => {
  if (bytes === 0) return '0 Bytes';
  const k = 1024;
  const sizes = ['Bytes', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
};

const getTotalFileSize = () => {
  return selectedFiles.value.reduce((total, file) => total + file.size, 0);
};

const uploadTransactionFiles = async () => {
  if (selectedFiles.value.length === 0) return;
  
  try {
    uploading.value = true;
    uploadProgress.value = 0;
    uploadStage.value = 'uploading';
    importResults.value = null;
    
    let progressInterval = null;
    let uploadStarted = false;
    let lastProgress = 0;
    const totalSize = getTotalFileSize();
    const estimatedUploadTimeMs = (totalSize / (1024 * 1024)) * 1000; // ~1 sec par MB
    const uploadProgressStep = 50 / (estimatedUploadTimeMs / 500); // 50% en estimatedUploadTimeMs
    
    // Démarrer l'animation de progression immédiatement
    progressInterval = setInterval(() => {
      if (uploadStage.value === 'uploading' && uploadProgress.value < 50) {
        uploadProgress.value = Math.min(50, uploadProgress.value + uploadProgressStep);
      } else if (uploadStage.value === 'processing' && uploadProgress.value < 95) {
        uploadProgress.value = Math.min(95, uploadProgress.value + 0.3);
      }
    }, 500);
    
    const response = await TransactionService.uploadFiles(
      selectedFiles.value,
      (progressEvent) => {
        if (!uploadStarted) {
          uploadStarted = true;
          console.log('Upload started:', progressEvent);
        }
        
        // Phase 1: Upload du fichier (0-50%)
        if (progressEvent.loaded && progressEvent.total) {
          const percentCompleted = Math.round((progressEvent.loaded * 50) / progressEvent.total);
          
          // Utiliser le vrai pourcentage si disponible
          if (percentCompleted > lastProgress) {
            uploadProgress.value = percentCompleted;
            lastProgress = percentCompleted;
            console.log(`Upload progress: ${progressEvent.loaded}/${progressEvent.total} = ${percentCompleted}%`);
          }
          
          // Quand upload terminé, passer au traitement
          if (percentCompleted >= 50 && uploadStage.value === 'uploading') {
            uploadStage.value = 'processing';
            console.log('Switching to processing stage');
          }
        }
      }
    );
    
    // Si on n'a pas reçu de vrais événements de progression, basculer manuellement
    if (!uploadStarted) {
      console.log('No upload progress events received, switching to processing manually');
      uploadProgress.value = 50;
      uploadStage.value = 'processing';
    }
    
    // Nettoyer l'intervalle si présent
    if (progressInterval) {
      clearInterval(progressInterval);
    }
    
    // Phase 3: Terminé
    uploadStage.value = 'complete';
    uploadProgress.value = 100;
    
    importResults.value = response.data;
    
    // Clear selected files if all were successful
    if (response.data.errors.length === 0) {
      selectedFiles.value = [];
      const totalImported = response.data.total_imported || 0;
      const totalUpdated = response.data.total_updated || 0;
      toast.success(`Import réussi: ${totalImported} nouvelles entrées, ${totalUpdated} mises à jour!`);
    } else {
      toast.warning('Import partiellement réussi. Consultez les détails ci-dessous.');
    }
  } catch (error) {
    console.error('Error uploading files:', error);
    if (error.code === 'ERR_NETWORK' || error.message === 'Network Error') {
      toast.error('Erreur réseau: Le fichier est peut-être trop volumineux ou le serveur ne répond pas. Vérifiez que le backend est en cours d\'exécution.');
    } else if (error.code === 'ECONNABORTED') {
      toast.error('Timeout: Le traitement prend trop de temps. Essayez avec des fichiers plus petits.');
    } else {
      toast.error('Erreur lors de l\'importation des fichiers: ' + (error.response?.data?.message || error.message));
    }
  } finally {
    uploading.value = false;
    uploadProgress.value = 0;
    uploadStage.value = '';
  }
};

const checkSmtpConfiguration = async () => {
  try {
    checkingSmtp.value = true;
    const result = await SystemSettingService.testSmtpConnection();
    smtpConfigured.value = result.success;
    return result.success;
  } catch (error) {
    console.error('SMTP check error:', error);
    smtpConfigured.value = false;
    return false;
  } finally {
    checkingSmtp.value = false;
  }
};

async function fetchCacheSettings() {
  const settings = await SettingService.getCacheSettings();
  cacheWidgets.value.forEach(w => {
    if (settings[w.key]) {
      w.enabled = settings[w.key].enabled;
      w.ttl = settings[w.key].ttl;
    }
  });
}

async function updateCacheSetting(widget) {
  await SettingService.updateCacheSetting(widget.key, { enabled: widget.enabled, ttl: widget.ttl });
}

async function clearWidgetCache(widget) {
  await SettingService.clearCache(widget.key);
  // Optionally show a toast
}

async function clearAllCaches() {
  await SettingService.clearAllCaches();
  // Optionally show a toast
}


onMounted(() => {
  if (!authStore.isAdmin) {
    router.push({ name: 'Dashboard' });
    return;
  }
  loadSettings();
});

// Watcher pour recharger les paramètres si l'utilisateur ou le rôle change
watch(
  () => [authStore.user, authStore.isAdmin],
  ([user, isAdmin], [oldUser, oldIsAdmin]) => {
    if (isAdmin && (!oldIsAdmin || user?.id !== oldUser?.id)) {
      loadSettings();
    }
  },
  { deep: true }
);
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


