<template>
  <div class="fixed inset-0 z-50 overflow-y-auto" @click.self="$emit('close')">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity bg-gray-900/80 backdrop-blur-md" @click="$emit('close')"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <div class="p-6">
          <!-- Header -->
          <div class="flex items-center justify-between mb-6">
            <div>
              <h3 class="text-2xl font-bold text-gray-900">{{ pointOfSale?.point_name }}</h3>
              <div class="flex items-center gap-3 mt-2">
                <span class="px-3 py-1 rounded-xl bg-yellow-100 border border-yellow-300 text-yellow-800 text-sm font-bold">
                  En attente
                </span>
                <span v-if="pointOfSale?.proximity_alert" class="px-3 py-1 rounded-xl bg-red-100 border border-red-300 text-red-800 text-sm font-bold">
                  ⚠️ Alerte proximité
                </span>
              </div>
            </div>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-600 transition-colors"
            >
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

          <!-- Content -->
          <div class="space-y-6">
            <!-- Dealer & Creation Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                <p class="text-xs font-semibold text-gray-500 mb-1">Dealer</p>
                <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.organization?.name || 'N/A' }}</p>
              </div>
              <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                <p class="text-xs font-semibold text-gray-500 mb-1">Créé par</p>
                <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.created_by?.name || 'N/A' }}</p>
              </div>
              <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                <p class="text-xs font-semibold text-gray-500 mb-1">Date de création</p>
                <p class="text-sm font-bold text-gray-900">{{ formatDate(pointOfSale?.created_at) }}</p>
              </div>
            </div>

            <!-- Flooz Info -->
            <div>
              <h4 class="text-lg font-bold text-gray-900 mb-3">Informations Flooz</h4>
              <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                  <p class="text-xs font-semibold text-gray-500 mb-1">Numéro Flooz</p>
                  <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.flooz_number || 'N/A' }}</p>
                </div>
                <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                  <p class="text-xs font-semibold text-gray-500 mb-1">Shortcode</p>
                  <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.shortcode || 'N/A' }}</p>
                </div>
                <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                  <p class="text-xs font-semibold text-gray-500 mb-1">Profil</p>
                  <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.profile || 'N/A' }}</p>
                </div>
                <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                  <p class="text-xs font-semibold text-gray-500 mb-1">Type d'activité</p>
                  <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.activity_type || 'N/A' }}</p>
                </div>
              </div>
            </div>

            <!-- Owner Info -->
            <div>
              <h4 class="text-lg font-bold text-gray-900 mb-3">Informations Propriétaire</h4>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                  <p class="text-xs font-semibold text-gray-500 mb-1">Nom complet</p>
                  <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.owner_first_name }} {{ pointOfSale?.owner_last_name }}</p>
                </div>
                <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                  <p class="text-xs font-semibold text-gray-500 mb-1">Date de naissance</p>
                  <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.owner_date_of_birth || 'N/A' }}</p>
                </div>
                <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                  <p class="text-xs font-semibold text-gray-500 mb-1">Genre</p>
                  <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.owner_gender || 'N/A' }}</p>
                </div>
                <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                  <p class="text-xs font-semibold text-gray-500 mb-1">Type de pièce</p>
                  <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.owner_id_type || 'N/A' }}</p>
                </div>
                <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                  <p class="text-xs font-semibold text-gray-500 mb-1">Numéro pièce</p>
                  <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.owner_id_number || 'N/A' }}</p>
                </div>
                <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                  <p class="text-xs font-semibold text-gray-500 mb-1">Téléphone</p>
                  <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.owner_phone || 'N/A' }}</p>
                </div>
              </div>
            </div>

            <!-- Location Info -->
            <div>
              <h4 class="text-lg font-bold text-gray-900 mb-3">Localisation</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-4">
                  <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                    <p class="text-xs font-semibold text-gray-500 mb-1">Région</p>
                    <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.region || 'N/A' }}</p>
                  </div>
                  <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                    <p class="text-xs font-semibold text-gray-500 mb-1">Ville / Quartier</p>
                    <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.city || 'N/A' }}, {{ pointOfSale?.neighborhood || 'N/A' }}</p>
                  </div>
                  <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                    <p class="text-xs font-semibold text-gray-500 mb-1">Coordonnées GPS</p>
                    <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.latitude }}, {{ pointOfSale?.longitude }}</p>
                  </div>
                  <div class="p-4 rounded-xl bg-white/50 border border-white/60">
                    <p class="text-xs font-semibold text-gray-500 mb-1">Description</p>
                    <p class="text-sm font-bold text-gray-900">{{ pointOfSale?.location_description || 'N/A' }}</p>
                  </div>
                </div>
                <div class="bg-gray-100 rounded-xl h-full min-h-[300px] flex items-center justify-center border border-gray-200">
                  <div class="text-center text-gray-500">
                    <svg class="w-16 h-16 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                    <p class="text-sm font-semibold">Carte interactive</p>
                    <p class="text-xs">(À implémenter avec Leaflet)</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Proximity Alert -->
            <div v-if="pointOfSale?.proximity_alert && pointOfSale?.nearby_pos" class="bg-red-50 border-2 border-red-200 rounded-xl p-6">
              <h4 class="text-lg font-bold text-red-800 mb-3 flex items-center gap-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                PDV à proximité détectés
              </h4>
              <ul class="space-y-2">
                <li v-for="nearby in pointOfSale.nearby_pos" :key="nearby.id" class="p-3 rounded-xl bg-white border border-red-200">
                  <div class="flex justify-between items-center">
                    <div>
                      <p class="text-sm font-bold text-gray-900">{{ nearby.point_name }}</p>
                      <p class="text-xs text-gray-600">{{ nearby.flooz_number }}</p>
                    </div>
                    <span class="px-3 py-1 rounded-lg bg-red-100 text-red-700 text-sm font-bold">
                      {{ nearby.distance }}m
                    </span>
                  </div>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 px-6 py-4 flex justify-end">
          <button
            @click="$emit('close')"
            class="px-6 py-3 rounded-xl bg-gradient-to-r from-moov-orange via-moov-orange-dark to-moov-orange text-white font-bold hover:shadow-xl transition-all duration-200"
          >
            Fermer
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  pointOfSale: {
    type: Object,
    required: true
  }
});

defineEmits(['close']);

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', { 
    day: '2-digit', 
    month: 'long', 
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
};
</script>
