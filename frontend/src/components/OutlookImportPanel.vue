<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
          <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
          </svg>
          Import Outlook
        </h3>
        <p class="text-sm text-gray-600 mt-1">Gérer l'import automatique des fichiers Excel depuis Outlook</p>
      </div>
      <span class="px-3 py-1 rounded-full text-xs font-bold" :class="isEnabled ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
        {{ isEnabled ? 'Activé' : 'Désactivé' }}
      </span>
    </div>

    <!-- Status Card -->
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Status -->
        <div class="flex items-start gap-3">
          <div class="p-3 bg-blue-100 rounded-lg">
            <svg class="w-6 h-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
              <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
            </svg>
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-600">Statut</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">
              <span v-if="isEnabled" class="text-green-600">Actif</span>
              <span v-else class="text-gray-500">Inactif</span>
            </p>
          </div>
        </div>

        <!-- Last Import -->
        <div class="flex items-start gap-3">
          <div class="p-3 bg-indigo-100 rounded-lg">
            <svg class="w-6 h-6 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
              <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.3A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z"></path>
            </svg>
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-600">Dernier import</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">
              {{ lastImport ? formatDate(lastImport) : 'N/A' }}
            </p>
          </div>
        </div>

        <!-- Next scheduled -->
        <div class="flex items-start gap-3">
          <div class="p-3 bg-purple-100 rounded-lg">
            <svg class="w-6 h-6 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v2h16V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h12a1 1 0 100-2H6z" clip-rule="evenodd"></path>
            </svg>
          </div>
          <div>
            <p class="text-sm font-semibold text-gray-600">Prochain cron</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ nextScheduled }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="flex flex-col sm:flex-row gap-3">
      <button
        @click="runImportManually"
        :disabled="isLoading"
        class="flex-1 px-6 py-3 rounded-lg font-bold transition-all flex items-center justify-center gap-2"
        :class="[
          isLoading ? 'bg-gray-300 text-gray-600 cursor-not-allowed' : 'bg-moov-orange text-white hover:bg-moov-orange-dark shadow-lg hover:shadow-xl'
        ]"
      >
        <svg v-if="!isLoading" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
        <svg v-else class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        {{ isLoading ? 'Import en cours...' : 'Exécuter maintenant' }}
      </button>

      <a
        href="/oauth/authorize"
        class="px-6 py-3 rounded-lg font-bold transition-all bg-blue-600 text-white hover:bg-blue-700 shadow-lg hover:shadow-xl flex items-center justify-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
        </svg>
        Autoriser Outlook
      </a>
    </div>

    <!-- Import History -->
    <div class="border-t pt-6">
      <h4 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
        <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
          <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z"></path>
          <path fill-rule="evenodd" d="M4 5a2 2 0 012-2 1 1 0 000-2H2a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V5a1 1 0 100 2 1 1 0 000-2h-2.732A2 2 0 007.732 5H4z" clip-rule="evenodd"></path>
        </svg>
        Historique des 10 derniers imports
      </h4>

      <div v-if="loadingHistory" class="text-center py-8">
        <div class="animate-spin rounded-full h-10 w-10 border-b-2 border-moov-orange mx-auto"></div>
        <p class="mt-3 text-sm text-gray-600">Chargement de l'historique...</p>
      </div>

      <div v-else-if="importHistory.length === 0" class="text-center py-8 bg-gray-50 rounded-lg">
        <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
        </svg>
        <p class="text-gray-600 font-medium">Aucun historique d'import</p>
      </div>

      <div v-else class="space-y-2 max-h-96 overflow-y-auto">
        <div
          v-for="record in importHistory"
          :key="record.id"
          class="p-4 bg-gray-50 rounded-lg border border-gray-200 hover:border-gray-300 transition-all"
        >
          <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
              <div class="flex items-center gap-2 mb-2">
                <span class="font-semibold text-gray-900">{{ record.filename }}</span>
                <span
                  class="px-2 py-1 rounded text-xs font-bold"
                  :class="getStatusBadgeClass(record.status)"
                >
                  {{ getStatusLabel(record.status) }}
                </span>
              </div>
              <p class="text-sm text-gray-600 mb-2">
                <strong>Sujet:</strong> {{ record.subject }}
              </p>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs text-gray-600">
                <div>
                  <span class="font-semibold">Importés:</span> {{ record.transactions_imported }}
                </div>
                <div>
                  <span class="font-semibold">Mis à jour:</span> {{ record.transactions_updated }}
                </div>
                <div>
                  <span class="font-semibold">Ignorés:</span> {{ record.transactions_skipped }}
                </div>
                <div>
                  <span class="font-semibold">Taille:</span> {{ formatFileSize(record.file_size_bytes) }}
                </div>
              </div>
              <p class="text-xs text-gray-500 mt-2">
                {{ formatDateTime(record.processed_at || record.created_at) }}
              </p>
            </div>
            <div v-if="record.error_message" class="p-2 bg-red-100 rounded flex-shrink-0">
              <svg class="w-5 h-5 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zM9 13a1 1 0 11-2 0 1 1 0 012 0zm0-8a1 1 0 10-2 0v3a1 1 0 102 0V5zm4 0a1 1 0 10-2 0v3a1 1 0 102 0V5z" clip-rule="evenodd"></path>
              </svg>
            </div>
          </div>
          <div v-if="record.error_message" class="mt-3 p-3 bg-red-50 border border-red-200 rounded text-xs text-red-700">
            <strong>Erreur:</strong> {{ record.error_message }}
          </div>
        </div>
      </div>
    </div>

    <!-- Configuration Info -->
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
      <div class="flex gap-3">
        <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M18 5v8a2 2 0 01-2 2h-5l-5 4v-4H4a2 2 0 01-2-2V5a2 2 0 012-2h12a2 2 0 012 2zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 100-2 1 1 0 000 2zm5 0a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"></path>
        </svg>
        <div class="text-sm">
          <p class="font-semibold text-blue-900 mb-1">Configuration active</p>
          <ul class="text-blue-800 space-y-1">
            <li>📧 Boîte: {{ mailbox }}</li>
            <li>📁 Dossier: {{ mailFolder }}</li>
            <li>🏷️ Filtre sujet: <code class="bg-white px-2 py-1 rounded text-xs">{{ subjectFilter }}</code></li>
            <li>📄 Pattern fichier: <code class="bg-white px-2 py-1 rounded text-xs">{{ filenamePattern }}</code></li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Success Toast -->
    <Transition>
      <div
        v-if="showSuccess"
        class="fixed bottom-6 right-6 bg-green-600 text-white px-6 py-4 rounded-lg shadow-lg flex items-center gap-3"
      >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
        </svg>
        {{ successMessage }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'
import api from '@/services/api'

const authStore = useAuthStore()
const isLoading = ref(false)
const loadingHistory = ref(false)
const showSuccess = ref(false)
const successMessage = ref('')
const importHistory = ref([])

const isEnabled = ref(false)
const mailbox = ref('')
const mailFolder = ref('Inbox')
const subjectFilter = ref('N/A')
const filenamePattern = ref('*.xlsx')
const lastImport = ref(null)
const nextScheduledTime = ref('08:30')

const nextScheduled = computed(() => {
  const now = new Date()
  const nextTime = new Date(now)
  const [hours, minutes] = nextScheduledTime.value.split(':').map(Number)
  nextTime.setHours(hours, minutes, 0, 0)
  if (nextTime <= now) nextTime.setDate(nextTime.getDate() + 1)
  return nextTime.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })
})

onMounted(async () => {
  await loadConfig()
  await loadHistory()
})

const loadConfig = async () => {
  try {
    const response = await api.get('/import/outlook/status')
    const config = response.data.data
    isEnabled.value = config.enabled || false
    mailbox.value = config.mailbox || 'Non configuré'
    mailFolder.value = config.mail_folder || 'Inbox'
    subjectFilter.value = config.subject_filter || 'N/A'
    filenamePattern.value = config.filename_pattern || '*.xlsx'
    nextScheduledTime.value = config.scheduled_time || '08:30'
  } catch (err) {
    console.error('Error loading config:', err)
    isEnabled.value = false
    mailbox.value = 'Non configuré'
  }
}

const loadHistory = async () => {
  loadingHistory.value = true
  try {
    const response = await api.get('/import/outlook/history?limit=10')
    importHistory.value = response.data.data || []
    if (importHistory.value.length > 0) {
      const successful = importHistory.value.find(r => r.status === 'success')
      if (successful) {
        lastImport.value = successful.processed_at || successful.created_at
      }
    }
  } catch (err) {
    console.error('Error loading history:', err)
  } finally {
    loadingHistory.value = false
  }
}

const runImportManually = async () => {
  isLoading.value = true
  try {
    const response = await api.post('/import/outlook/run')
    
    successMessage.value = `Import lancé! ${response.data.message || ''}`
    showSuccess.value = true
    
    setTimeout(() => {
      showSuccess.value = false
      loadHistory()
    }, 3000)
  } catch (err) {
    successMessage.value = `Erreur: ${err.response?.data?.message || 'Impossible de lancer l\'import'}`
  } finally {
    isLoading.value = false
  }
}

const formatDate = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' })
}

const formatDateTime = (dateString) => {
  if (!dateString) return 'N/A'
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', { 
    year: 'numeric', 
    month: 'short', 
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i]
}

const getStatusLabel = (status) => {
  const labels = {
    'success': '✓ Succès',
    'failed': '✕ Erreur',
    'partial': '⚠ Partiel',
    'pending': '⏳ En attente'
  }
  return labels[status] || status
}

const getStatusBadgeClass = (status) => {
  const classes = {
    'success': 'bg-green-100 text-green-700',
    'failed': 'bg-red-100 text-red-700',
    'partial': 'bg-yellow-100 text-yellow-700',
    'pending': 'bg-gray-100 text-gray-700'
  }
  return classes[status] || 'bg-gray-100 text-gray-700'
}
</script>

<style scoped>
.transition-enter-active,
.transition-leave-active {
  transition: all 0.3s ease;
}

.transition-enter-from,
.transition-leave-to {
  opacity: 0;
  transform: translateY(10px);
}
</style>
