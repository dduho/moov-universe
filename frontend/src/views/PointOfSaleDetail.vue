<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Loading State -->
      <div v-if="loading" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-12 text-center">
        <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-moov-orange mx-auto mb-4"></div>
        <p class="text-gray-600 font-semibold">Chargement des détails...</p>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-12 text-center">
        <div class="text-6xl mb-4">❌</div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Erreur de chargement</h3>
        <p class="text-gray-600 mb-6">{{ error }}</p>
        <router-link
          to="/pdv/list"
          class="px-6 py-3 rounded-xl bg-moov-orange text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 inline-block"
        >
          Retour à la liste
        </router-link>
      </div>

      <!-- Content -->
      <div v-else-if="pos">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4 mb-6 sm:mb-8">
          <div class="flex items-center gap-3 sm:gap-4">
            <button
              @click="$router.push('/pdv/list')"
              class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-white/50 hover:bg-white flex items-center justify-center transition-all duration-200 flex-shrink-0"
            >
              <svg class="w-5 h-5 sm:w-6 sm:h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
              </svg>
            </button>
            <div class="min-w-0">
              <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 truncate">{{ pos.nom_point || pos.point_name }}</h1>
              <p class="text-sm sm:text-base text-gray-600">{{ formatPhone(pos.numero_flooz || pos.flooz_number) }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <!-- Bouton Statistiques -->
            <button
              @click="showStatsModal = true"
              class="inline-flex items-center gap-2 px-4 py-2 sm:px-5 sm:py-3 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 text-sm sm:text-base"
            >
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z" />
              </svg>
              Statistiques
            </button>
            
            <!-- Bouton Modifier (visible si pending ou tâche active) -->
            <router-link
              v-if="canEdit"
              :to="`/pdv/${pos.id}/edit`"
              class="inline-flex items-center gap-2 px-4 py-2 sm:px-5 sm:py-3 rounded-xl bg-moov-orange text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 text-sm sm:text-base"
            >
              <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
              </svg>
              Modifier
            </router-link>
            <span
              class="self-start sm:self-auto px-4 sm:px-6 py-2 sm:py-3 rounded-xl text-xs sm:text-sm font-bold"
              :class="getStatusClass(pos.status)"
            >
              {{ getStatusLabel(pos.status) }}
            </span>
          </div>
        </div>

        <!-- GPS Missing Warning -->
        <div v-if="!hasValidCoordinates" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6 mb-6 sm:mb-8 border-2 border-red-400 bg-red-50/50">
          <h3 class="text-base sm:text-lg font-bold text-red-800 mb-2 sm:mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Coordonnées GPS manquantes
          </h3>
          <p class="text-xs sm:text-sm text-red-700 mb-2">
            Ce point de vente n'a pas de coordonnées GPS valides. Il ne sera pas affiché sur la carte.
          </p>
          <p class="text-xs text-red-600">
            <strong>Raison possible :</strong> Les coordonnées ont été supprimées car elles étaient identiques à celles d'un autre PDV (doublon détecté).
          </p>
          <router-link
            v-show="false"
            :to="`/pdv/${pos.id}/edit`"
            class="mt-3 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-bold hover:bg-red-700 transition-colors"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
            Corriger les coordonnées
          </router-link>
        </div>

        <!-- Proximity Alert -->
        <div v-if="proximityAlert?.has_nearby" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6 mb-6 sm:mb-8 border-2 border-orange-300 bg-orange-50/50">
          <h3 class="text-base sm:text-lg font-bold text-orange-800 mb-2 sm:mb-3 flex items-center gap-2">
            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            Alerte de proximité
          </h3>
          <p class="text-xs sm:text-sm text-orange-700 mb-1">
            <span class="font-bold">{{ proximityAlert.count }}</span> PDV {{ proximityAlert.count > 1 ? 'se trouvent' : 'se trouve' }} à moins de <span class="font-bold">{{ proximityAlert.alert_distance }}m</span> de ce point :
          </p>
          <p class="text-xs text-orange-600 mb-2 sm:mb-3">Seuil de distance configuré dans les paramètres système</p>
          <div class="space-y-2 max-h-64 overflow-y-auto pr-2">
            <div
              v-for="nearby in proximityAlert.nearby_pdvs"
              :key="nearby.id"
              class="p-2 sm:p-3 rounded-xl bg-white border border-orange-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 hover:shadow-md transition-shadow"
            >
              <div class="flex-1 min-w-0">
                <p class="font-bold text-gray-900 text-sm sm:text-base truncate">{{ nearby.nom_point }}</p>
                <p class="text-xs sm:text-sm text-gray-600">{{ nearby.numero_flooz }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ nearby.ville }}, {{ nearby.quartier }}</p>
              </div>
              <div class="flex items-center justify-between sm:block sm:text-right">
                <span class="px-2 sm:px-3 py-1 rounded-lg bg-orange-100 text-orange-700 text-xs sm:text-sm font-bold">
                  {{ nearby.distance }}m
                </span>
                <router-link
                  :to="`/pdv/${nearby.id}`"
                  class="text-xs text-moov-orange hover:text-moov-orange-dark font-semibold sm:mt-1 sm:block"
                >
                  Voir détails →
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- Geographic Inconsistency Alert -->
        <div v-if="geoValidation.hasAlert" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6 mb-6 sm:mb-8 border-2" :class="geoValidation.alertType === 'error' ? 'border-red-400 bg-red-50/50' : 'border-purple-400 bg-purple-50/50'">
          <h3 class="text-base sm:text-lg font-bold mb-2 sm:mb-3 flex items-center gap-2" :class="geoValidation.alertType === 'error' ? 'text-red-800' : 'text-purple-800'">
            <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ geoValidation.alertType === 'error' ? 'Erreur de localisation' : 'Incohérence géographique détectée' }}
          </h3>
          <p class="text-xs sm:text-sm mb-2" :class="geoValidation.alertType === 'error' ? 'text-red-700' : 'text-purple-700'">
            {{ geoValidation.message }}
          </p>
          <div v-if="geoValidation.actualRegion" class="mt-3 p-3 rounded-lg bg-white/50 border" :class="geoValidation.alertType === 'error' ? 'border-red-200' : 'border-purple-200'">
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4 text-sm">
              <div>
                <span class="font-semibold" :class="geoValidation.alertType === 'error' ? 'text-red-600' : 'text-purple-600'">Région déclarée:</span>
                <span class="ml-1 font-bold">{{ geoValidation.declaredRegion }}</span>
              </div>
              <svg class="hidden sm:block w-4 h-4" :class="geoValidation.alertType === 'error' ? 'text-red-400' : 'text-purple-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
              </svg>
              <div>
                <span class="font-semibold" :class="geoValidation.alertType === 'error' ? 'text-red-600' : 'text-purple-600'">Région GPS:</span>
                <span class="ml-1 font-bold">{{ geoValidation.actualRegionName || geoValidation.actualRegion }}</span>
              </div>
            </div>
          </div>
          <p class="text-xs mt-3" :class="geoValidation.alertType === 'error' ? 'text-red-600' : 'text-purple-600'">
            <strong>Action recommandée :</strong> Vérifiez les coordonnées GPS ou la région déclarée de ce point de vente.
          </p>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
          <!-- Left Column -->
          <div class="lg:col-span-2 space-y-4 sm:space-y-6 lg:space-y-8">
            <!-- Flooz Info -->
            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-4 sm:p-6">
              <h2 class="text-lg sm:text-xl font-bold text-gray-900 mb-4 sm:mb-6 flex items-center gap-2">
                <svg class="w-5 h-5 sm:w-6 sm:h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Informations Flooz
              </h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Dealer</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.dealer_name || pos.organization?.name || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Numéro Flooz</p>
                  <p class="text-lg font-bold text-gray-900">{{ formatPhone(pos.numero_flooz || pos.flooz_number) || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Shortcode</p>
                  <div class="flex items-center gap-2">
                    <p class="text-lg font-bold text-gray-900">{{ formatShortcode(pos.shortcode) || 'N/A' }}</p>
                    <div v-if="!pos.shortcode || pos.shortcode === 'N/A'" class="group relative">
                      <div class="flex items-center justify-center w-6 h-6 rounded-full bg-red-100 cursor-help">
                        <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                      </div>
                      <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block z-10">
                        <div class="bg-gray-900 text-white text-xs rounded-lg px-3 py-2 whitespace-nowrap shadow-lg">
                          Shortcode manquant
                          <div class="absolute top-full left-1/2 -translate-x-1/2 -mt-1">
                            <div class="border-4 border-transparent border-t-gray-900"></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Profil</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.profil || pos.profile || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Type d'activité</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.type_activite || pos.activity_type || 'N/A' }}</p>
                </div>
              </div>
            </div>

            <!-- Owner Info -->
            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6">
              <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Informations Propriétaire
              </h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Nom complet</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.firstname || pos.owner_first_name }} {{ pos.lastname || pos.owner_last_name }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Date de naissance</p>
                  <p class="text-lg font-bold text-gray-900">{{ formatDate(pos.date_of_birth || pos.owner_date_of_birth) }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Genre</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.gender || pos.sexe_gerant || pos.owner_gender || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Nationalité</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.nationality || pos.owner_nationality || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Type de pièce</p>
                  <p class="text-lg font-bold text-gray-900">{{ getIdTypeLabel(pos.id_description || pos.owner_id_type) }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Numéro de pièce</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.id_number || pos.owner_id_number || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Date d'expiration</p>
                  <p class="text-lg font-bold text-gray-900">{{ formatDate(pos.id_expiry_date || pos.owner_id_expiry_date) }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Profession</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.profession || pos.owner_profession || 'N/A' }}</p>
                </div>
              </div>
            </div>

            <!-- Location Info -->
            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6">
              <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Localisation
              </h2>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 mb-6">
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Région</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.region || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Préfecture</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.prefecture || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Commune</p>
                  <p class="text-lg font-bold text-gray-900">{{ formatCommune(pos.commune) || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Canton</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.canton || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Ville</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.ville || pos.city || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Quartier</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.quartier || pos.neighborhood || 'N/A' }}</p>
                </div>
                <div class="col-span-2">
                  <p class="text-sm font-semibold text-gray-500 mb-1">Description de la localisation</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.localisation || pos.location_description || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Latitude</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.latitude || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Longitude</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.longitude || 'N/A' }}</p>
                </div>
                
                <!-- Indicateur de précision GPS -->
                <div v-if="gpsAccuracyWarning?.show" class="col-span-2">
                  <div 
                    class="flex items-center gap-2 px-4 py-3 rounded-lg"
                    :class="gpsAccuracyWarning.isWarning 
                      ? 'bg-orange-100 border-2 border-orange-300 text-orange-800' 
                      : 'bg-green-100 border-2 border-green-300 text-green-800'"
                  >
                    <svg v-if="gpsAccuracyWarning.isWarning" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <svg v-else class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="font-semibold text-sm">{{ gpsAccuracyWarning.message }}</span>
                    <span v-if="gpsAccuracyWarning.isWarning" class="text-xs ml-auto">
                      La précision lors de la capture était inférieure au seuil requis
                    </span>
                  </div>
                </div>
                <div v-else-if="!pos.gps_accuracy" class="col-span-2">
                  <div class="flex items-center gap-2 px-4 py-3 rounded-lg bg-gray-100 border-2 border-gray-200 text-gray-600">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-sm">Aucune donnée de précision GPS disponible</span>
                  </div>
                </div>
              </div>

              <!-- Leaflet Map -->
              <div id="map" class="rounded-xl h-64 border-2 border-gray-200 overflow-hidden"></div>
            </div>

            <!-- Contact & Fiscal Info -->
            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6">
              <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
                Contacts & Fiscalité
              </h2>

              <div v-if="pos.missing_required_fields && pos.missing_required_fields.length" class="mb-4 p-4 rounded-xl border-2 border-yellow-300 bg-yellow-50/60 text-sm text-yellow-800">
                <div class="font-bold mb-2">Informations obligatoires manquantes</div>
                <ul class="list-disc list-inside space-y-1">
                  <li v-for="(field, idx) in pos.missing_required_fields" :key="idx">{{ field }}</li>
                </ul>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Téléphone principal</p>
                  <p class="text-lg font-bold text-gray-900">{{ formatPhone(pos.numero_proprietaire || pos.owner_phone) || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Contact alternatif</p>
                  <p class="text-lg font-bold text-gray-900">{{ formatPhone(pos.autre_contact || pos.alternative_contact) || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">NIF</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.nif || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Numéro CAGNT</p>
                  <p class="text-lg font-bold text-gray-900">{{ formatPhone(pos.numero_cagnt || pos.cagnt_number) || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Régime fiscal</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.regime_fiscal || pos.tax_regime || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">Support de visibilité</p>
                  <p class="text-lg font-bold text-gray-900">{{ formatVisibilitySupport(pos.support_visibilite || pos.visibility_support) || 'N/A' }}</p>
                </div>
                <div>
                  <p class="text-sm font-semibold text-gray-500 mb-1">État du support</p>
                  <p class="text-lg font-bold text-gray-900">{{ pos.etat_support || pos.support_state || 'N/A' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Right Column -->
          <div class="space-y-8">
            <!-- Quick Info Card -->
            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6">
              <h3 class="text-lg font-bold text-gray-900 mb-4">Informations rapides</h3>
              <div class="space-y-4">
                <div class="flex items-center gap-3 pb-4 border-b border-gray-200">
                  <svg class="w-5 h-5 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                  </svg>
                  <div class="flex-1">
                    <p class="text-xs font-semibold text-gray-500">Dealer</p>
                    <p class="text-sm font-bold text-gray-900">{{ pos.dealer_name || pos.organization?.name || 'N/A' }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-3 pb-4 border-b border-gray-200">
                  <svg class="w-5 h-5 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                  <div class="flex-1">
                    <p class="text-xs font-semibold text-gray-500">Créé par</p>
                    <p class="text-sm font-bold text-gray-900">{{ pos.creator?.name || pos.created_by?.name || 'N/A' }}</p>
                  </div>
                </div>
                <div class="flex items-center gap-3 pb-4 border-b border-gray-200">
                  <svg class="w-5 h-5 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                  </svg>
                  <div class="flex-1">
                    <p class="text-xs font-semibold text-gray-500">Date de création</p>
                    <p class="text-sm font-bold text-gray-900">{{ formatDate(pos.created_at) }}</p>
                  </div>
                </div>
                <div v-if="pos.validated_at" class="flex items-center gap-3">
                  <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  <div class="flex-1">
                    <p class="text-xs font-semibold text-gray-500">Date de validation</p>
                    <p class="text-sm font-bold text-gray-900">{{ formatDate(pos.validated_at) }}</p>
                  </div>
                </div>
                <div v-if="pos.rejected_at" class="flex items-center gap-3">
                  <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  <div class="flex-1">
                    <p class="text-xs font-semibold text-gray-500">Date de rejet</p>
                    <p class="text-sm font-bold text-gray-900">{{ formatDate(pos.rejected_at) }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Rejection Reason -->
            <div v-if="pos.status === 'rejected' && pos.rejection_reason" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 border-2 border-red-300 bg-red-50/50">
              <h3 class="text-lg font-bold text-red-800 mb-3 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Motif du rejet
              </h3>
              <p class="text-sm text-red-700">{{ pos.rejection_reason }}</p>
            </div>

            <!-- Actions (Admin Only) -->
            <div v-if="authStore.isAdmin && pos.status === 'pending'" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6">
              <h3 class="text-lg font-bold text-gray-900 mb-4">Actions</h3>
              <div class="space-y-3">
                <button
                  @click="validatePOS"
                  class="w-full px-4 py-3 rounded-xl bg-gradient-to-r from-green-500 to-green-600 text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  Valider
                </button>
                <button
                  @click="showRejectModal = true"
                  class="w-full px-4 py-3 rounded-xl bg-gradient-to-r from-red-500 to-red-600 text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 flex items-center justify-center gap-2"
                >
                  <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  Rejeter
                </button>
              </div>
            </div>

            <!-- Tâches -->
            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6">
              <TaskList :pdv="pos" @tasks-updated="fetchPOSData" />
            </div>

            <!-- Documents -->
            <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6">
              <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
                Documents
              </h3>
              
              <!-- Pièces d'identité -->
              <div v-if="pos.idDocuments?.length" class="mb-4">
                <p class="text-sm font-semibold text-gray-700 mb-2">Pièces d'identité</p>
                <div class="space-y-2">
                  <div
                    v-for="doc in pos.idDocuments"
                    :key="doc.id"
                    class="flex items-center justify-between p-3 rounded-lg bg-blue-50 border border-blue-200 hover:bg-blue-100 transition-colors"
                  >
                    <div class="flex items-center gap-3 flex-1">
                      <svg class="w-5 h-5 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                      </svg>
                      <div class="flex-1">
                        <div class="flex items-center gap-2">
                          <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-blue-600 text-white">Document d'identité</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900 block mt-1">{{ doc.name }}</span>
                      </div>
                    </div>
                    <button
                      @click="viewFile(doc)"
                      class="px-3 py-1 rounded-lg bg-blue-600 text-white text-sm font-bold hover:bg-blue-700 transition-colors"
                    >
                      Voir
                    </button>
                  </div>
                </div>
              </div>

              <!-- Photos -->
              <div v-if="pos.photos?.length" class="mb-4">
                <p class="text-sm font-semibold text-gray-700 mb-2">Photos</p>
                <div class="space-y-2">
                  <div
                    v-for="photo in pos.photos"
                    :key="photo.id"
                    class="flex items-center justify-between p-3 rounded-lg bg-green-50 border border-green-200 hover:bg-green-100 transition-colors"
                  >
                    <div class="flex items-center gap-3 flex-1">
                      <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                      </svg>
                      <div class="flex-1">
                        <div class="flex items-center gap-2">
                          <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-green-600 text-white">Photo du point</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900 block mt-1">{{ photo.name }}</span>
                      </div>
                    </div>
                    <button
                      @click="viewFile(photo)"
                      class="px-3 py-1 rounded-lg bg-green-600 text-white text-sm font-bold hover:bg-green-700 transition-colors"
                    >
                      Voir
                    </button>
                  </div>
                </div>
              </div>

              <!-- Documents fiscaux -->
              <div v-if="pos.fiscalDocuments?.length" class="mb-4">
                <p class="text-sm font-semibold text-gray-700 mb-2">Documents fiscaux</p>
                <div class="space-y-2">
                  <div
                    v-for="doc in pos.fiscalDocuments"
                    :key="doc.id"
                    class="flex items-center justify-between p-3 rounded-lg bg-purple-50 border border-purple-200 hover:bg-purple-100 transition-colors"
                  >
                    <div class="flex items-center gap-3 flex-1">
                      <svg class="w-5 h-5 text-purple-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                      </svg>
                      <div class="flex-1">
                        <div class="flex items-center gap-2">
                          <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-purple-600 text-white">Document fiscal</span>
                        </div>
                        <span class="text-sm font-medium text-gray-900 block mt-1">{{ doc.name }}</span>
                      </div>
                    </div>
                    <button
                      @click="viewFile(doc)"
                      class="px-3 py-1 rounded-lg bg-purple-600 text-white text-sm font-bold hover:bg-purple-700 transition-colors"
                    >
                      Voir
                    </button>
                  </div>
                </div>
              </div>

              <p v-if="!pos.idDocuments?.length && !pos.photos?.length && !pos.fiscalDocuments?.length" class="text-sm text-gray-500 text-center py-4">
                Aucun document disponible
              </p>
            </div>

            <!-- Notes -->
            <NotesSection
              :point-of-sale-id="pos.id"
              :current-user-id="authStore.user?.id"
              :is-admin="authStore.isAdmin"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Rejection Modal -->
    <RejectionModal
      v-if="showRejectModal"
      :point-of-sale="pos"
      @close="showRejectModal = false"
      @reject="handleReject"
    />

    <!-- File Viewer Modal -->
    <Teleport to="body">
      <Transition
        enter-active-class="transition ease-out duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition ease-in duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
      >
        <div
          v-if="showFileModal && selectedFile"
          class="fixed inset-0 z-50 overflow-y-auto"
          @click="showFileModal = false"
        >
          <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-gray-900/75 backdrop-blur-sm transition-opacity"></div>

            <!-- Modal Content -->
            <div
              class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full"
              @click.stop
            >
              <!-- Header -->
              <div class="bg-gradient-to-r from-moov-orange to-moov-orange-dark px-6 py-4">
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-bold text-white">{{ selectedFile.name }}</h3>
                  <button
                    @click="showFileModal = false"
                    class="w-10 h-10 rounded-xl bg-white/20 hover:bg-white/30 flex items-center justify-center transition-colors"
                  >
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                  </button>
                </div>
              </div>

              <!-- File Content -->
              <div class="p-6 max-h-[70vh] overflow-auto relative">
                <!-- Loader -->
                <div v-if="fileLoading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-90 rounded-xl">
                  <div class="text-center">
                    <div class="inline-block w-12 h-12 border-4 border-moov-orange border-t-transparent rounded-full animate-spin mb-3"></div>
                    <p class="text-gray-600 font-medium">Chargement...</p>
                  </div>
                </div>

                <img
                  v-if="selectedFile.type?.startsWith('image/')"
                  :src="selectedFile.url"
                  :alt="selectedFile.name"
                  class="w-full h-auto rounded-xl"
                  @load="fileLoading = false"
                  @error="fileLoading = false"
                />
                <iframe
                  v-else-if="selectedFile.type === 'application/pdf'"
                  :src="selectedFile.url"
                  class="w-full h-[60vh] rounded-xl border-2 border-gray-200"
                  @load="fileLoading = false"
                ></iframe>
                <div v-else class="text-center py-12">
                  <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                  </svg>
                  <p class="text-gray-600 mb-4">Aperçu non disponible pour ce type de fichier</p>
                  <a
                    :href="selectedFile.url"
                    target="_blank"
                    download
                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-moov-orange text-white font-bold hover:shadow-xl transition-all"
                  >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Télécharger
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </Transition>
    </Teleport>

    <!-- Stats Modal -->
    <PdvStatsModal
      :is-open="showStatsModal"
      :pdv-id="Number(route.params.id)"
      @close="showStatsModal = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, nextTick, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import Navbar from '../components/Navbar.vue';
import PdvStatsModal from '../components/PdvStatsModal.vue';
import RejectionModal from '../components/RejectionModal.vue';
import TaskList from '../components/TaskList.vue';
import NotesSection from '../components/NotesSection.vue';
import PointOfSaleService from '../services/PointOfSaleService';
import SystemSettingService from '../services/systemSettingService';
import { validateRegionCoordinates } from '../data/regionBoundaries';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';
import { useConfirm } from '../composables/useConfirm';
import { formatPhone, formatShortcode } from '../utils/formatters';

// Fix Leaflet default marker icon
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
  iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon-2x.png',
  iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-icon.png',
  shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
});

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { toast } = useToast();
const { confirm } = useConfirm();

const loading = ref(true);
const error = ref(null);
const pos = ref(null);
const proximityAlert = ref(null);
const showRejectModal = ref(false);
const mapInstance = ref(null);
const showFileModal = ref(false);
const showStatsModal = ref(false);
const selectedFile = ref(null);
const fileLoading = ref(true);
const gpsAccuracyThreshold = ref(30); // Valeur par défaut 30m

// Computed pour vérifier si la précision GPS dépasse le seuil
const gpsAccuracyWarning = computed(() => {
  if (!pos.value?.gps_accuracy) return null;
  const accuracy = parseFloat(pos.value.gps_accuracy);
  if (accuracy > gpsAccuracyThreshold.value) {
    return {
      show: true,
      message: `Précision GPS: ${Math.round(accuracy)}m (seuil: ${gpsAccuracyThreshold.value}m)`,
      isWarning: true
    };
  }
  return {
    show: true,
    message: `Précision GPS: ${Math.round(accuracy)}m`,
    isWarning: false
  };
});

// Computed pour vérifier si les coordonnées GPS sont valides
const hasValidCoordinates = computed(() => {
  if (!pos.value) return true;
  const lat = parseFloat(pos.value.latitude);
  const lng = parseFloat(pos.value.longitude);
  return !isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0 && pos.value.latitude !== null && pos.value.longitude !== null;
});

// Computed pour la validation géographique (cohérence région/GPS)
const geoValidation = computed(() => {
  if (!pos.value || !hasValidCoordinates.value || !pos.value.region) {
    return { hasAlert: false };
  }
  
  // Utiliser la validation du backend si disponible (normaliser snake_case vers camelCase)
  if (pos.value.geo_validation) {
    const gv = pos.value.geo_validation;
    return {
      isValid: gv.is_valid ?? gv.isValid ?? true,
      hasAlert: gv.has_alert ?? gv.hasAlert ?? false,
      alertType: gv.alert_type ?? gv.alertType ?? 'warning',
      message: gv.message ?? null,
      declaredRegion: gv.declared_region ?? gv.declaredRegion ?? null,
      actualRegion: gv.actual_region ?? gv.actualRegion ?? null,
      actualRegionName: gv.actual_region_name ?? gv.actualRegionName ?? null
    };
  }
  
  // Sinon, calculer côté frontend
  const lat = parseFloat(pos.value.latitude);
  const lng = parseFloat(pos.value.longitude);
  return validateRegionCoordinates(lat, lng, pos.value.region);
});

const hasAnyDocuments = computed(() => {
  return (pos.value?.idDocuments?.length || 
          pos.value?.photos?.length || 
          pos.value?.fiscalDocuments?.length) > 0;
});

// Vérifier si le PDV a une tâche en révision demandée (permet la modification pour les commerciaux)
const hasTaskInRevision = computed(() => {
  return pos.value?.has_task_in_revision || false;
});

// Le PDV peut être modifié si:
// - Admin: toujours (sauf si aucune raison de modifier)
// - PDV en status pending
// - Il y a une tâche avec révision demandée (pour les commerciaux)
const canEdit = computed(() => {
  // Les admins peuvent toujours modifier
  if (authStore.isAdmin) {
    return pos.value?.status === 'pending' || pos.value?.has_active_task || hasTaskInRevision.value;
  }
  // Les commerciaux peuvent modifier si pending OU si une tâche est en révision demandée
  return pos.value?.status === 'pending' || hasTaskInRevision.value;
});

const getStatusClass = (status) => {
  const classes = {
    pending: 'bg-yellow-100 border-2 border-yellow-300 text-yellow-800',
    validated: 'bg-green-100 border-2 border-green-300 text-green-800',
    rejected: 'bg-red-100 border-2 border-red-300 text-red-800'
  };
  return classes[status] || 'bg-gray-100 border-2 border-gray-300 text-gray-800';
};

const getStatusLabel = (status) => {
  const labels = {
    pending: 'En attente',
    validated: 'Validé',
    rejected: 'Rejeté'
  };
  return labels[status] || status;
};

const formatVisibilitySupport = (value) => {
  if (!value) return '';
  const items = Array.isArray(value)
    ? value
    : `${value}`
        .split(/[,+;|\/]+|\bet\b|\band\b/i)
        .map(part => part.trim())
        .filter(Boolean);
  return items.join(', ');
};

const formatDate = (dateString) => {
  if (!dateString) return 'N/A';
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', { 
    day: '2-digit', 
    month: 'long', 
    year: 'numeric'
  });
};

const getIdTypeLabel = (code) => {
  if (!code) return 'N/A';
  
  const idTypes = {
    'cni': "Carte Nationale d'Identité",
    'passport': 'Passeport',
    'residence': 'Carte de séjour',
    'elector': "Carte d'électeur",
    'driving_license': 'Permis de conduire',
    'foreign_id': "Carte d'identité étrangère",
    'anid_card': 'Carte ANID'
  };
  
  return idTypes[code] || code;
};

const formatCommune = (commune) => {
  if (!commune) return 'N/A';
  // Replace underscore with space and capitalize: "Avé_1" -> "Avé 1"
  return commune.replace(/_/g, ' ');
};

const initMap = () => {
  if (!pos.value?.latitude || !pos.value?.longitude) {
    console.warn('Pas de coordonnées GPS disponibles');
    return;
  }
  
  // Utiliser setTimeout pour s'assurer que le DOM est prêt
  setTimeout(() => {
    const mapElement = document.getElementById('map');
    if (!mapElement) {
      console.error('Élément map non trouvé dans le DOM');
      return;
    }
    
    console.log('Initialisation de la carte...', { lat: pos.value.latitude, lng: pos.value.longitude });
    
    // Destroy existing map if any
    if (mapInstance.value) {
      mapInstance.value.remove();
    }
    
    try {
      // Create map
      mapInstance.value = L.map('map').setView(
        [parseFloat(pos.value.latitude), parseFloat(pos.value.longitude)], 
        15
      );
      
      // Add tile layer
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 19
      }).addTo(mapInstance.value);
      
      // Create blue icon for main PDV
      const blueIcon = L.divIcon({
        className: 'custom-marker',
        html: `
          <div style="position: relative;">
            <svg width="32" height="42" viewBox="0 0 32 42" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M16 0C7.163 0 0 7.163 0 16c0 12 16 26 16 26s16-14 16-26c0-8.837-7.163-16-16-16z" fill="#3B82F6"/>
              <circle cx="16" cy="16" r="6" fill="white"/>
            </svg>
          </div>
        `,
        iconSize: [32, 42],
        iconAnchor: [16, 42],
        popupAnchor: [0, -42]
      });
      
      // Add main marker with blue icon
      const mainMarker = L.marker([parseFloat(pos.value.latitude), parseFloat(pos.value.longitude)], { icon: blueIcon })
        .addTo(mapInstance.value);
      
      // Add popup for main marker
      mainMarker.bindPopup(`
        <div class="text-center">
          <strong class="text-moov-orange">${pos.value.nom_point || pos.value.point_name}</strong><br/>
          ${pos.value.ville || pos.value.city}, ${pos.value.quartier || pos.value.neighborhood || ''}
        </div>
      `).openPopup();
      
      // Add nearby PDVs if they exist and within threshold
      if (proximityAlert.value?.has_nearby && proximityAlert.value?.nearby_pdvs) {
        const bounds = [[parseFloat(pos.value.latitude), parseFloat(pos.value.longitude)]];
        const alertDistance = proximityAlert.value.alert_distance || 300;
        
        proximityAlert.value.nearby_pdvs.forEach(nearby => {
          if (nearby.latitude && nearby.longitude && nearby.distance <= alertDistance) {
            // Create red icon for nearby PDV
            const redIcon = L.divIcon({
              className: 'custom-marker',
              html: `
                <div style="position: relative;">
                  <svg width="32" height="42" viewBox="0 0 32 42" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M16 0C7.163 0 0 7.163 0 16c0 12 16 26 16 26s16-14 16-26c0-8.837-7.163-16-16-16z" fill="#EF4444"/>
                    <circle cx="16" cy="16" r="6" fill="white"/>
                  </svg>
                </div>
              `,
              iconSize: [32, 42],
              iconAnchor: [16, 42],
              popupAnchor: [0, -42]
            });
            
            // Add red marker for nearby PDV
            L.marker([parseFloat(nearby.latitude), parseFloat(nearby.longitude)], { icon: redIcon })
              .bindPopup(`
                <div class="text-center">
                  <strong style="color: #EF4444;">⚠️ ${nearby.nom_point}</strong><br/>
                  ${nearby.numero_flooz}<br/>
                  <strong>Distance: ${nearby.distance}m</strong>
                </div>
              `)
              .addTo(mapInstance.value);
            
            bounds.push([parseFloat(nearby.latitude), parseFloat(nearby.longitude)]);
            
            // Draw line between PDVs
            L.polyline([
              [parseFloat(pos.value.latitude), parseFloat(pos.value.longitude)],
              [parseFloat(nearby.latitude), parseFloat(nearby.longitude)]
            ], {
              color: '#EF4444',
              weight: 2,
              opacity: 0.7,
              dashArray: '5, 10'
            }).addTo(mapInstance.value);
          }
        });
        
        // Fit map to show all markers if there are nearby PDVs
        if (bounds.length > 1) {
          mapInstance.value.fitBounds(bounds, { padding: [50, 50] });
        }
      }
      
      console.log('Carte initialisée avec succès');
    } catch (err) {
      console.error('Erreur lors de l\'initialisation de la carte:', err);
    }
  }, 100);
};

const viewFile = (file) => {
  // Ajouter le type mime basé sur l'extension
  const extension = file.name?.split('.').pop()?.toLowerCase();
  const mimeTypes = {
    'jpg': 'image/jpeg',
    'jpeg': 'image/jpeg',
    'png': 'image/png',
    'gif': 'image/gif',
    'webp': 'image/webp',
    'pdf': 'application/pdf'
  };
  
  selectedFile.value = {
    ...file,
    type: mimeTypes[extension] || 'application/octet-stream'
  };
  fileLoading.value = true;
  showFileModal.value = true;
};

const validatePOS = async () => {
  const confirmed = await confirm({
    title: 'Valider le PDV',
    message: `Êtes-vous sûr de vouloir valider le PDV "${pos.value.point_name}" ?`,
    confirmText: 'Valider',
    type: 'info'
  });
  if (!confirmed) return;
  
  try {
    await PointOfSaleService.validate(pos.value.id);
    router.push('/validation');
  } catch (err) {
    console.error('Error validating POS:', err);
    toast.error('Erreur lors de la validation du PDV');
  }
};

const handleReject = async ({ id, reason }) => {
  try {
    await PointOfSaleService.reject(id, reason);
    showRejectModal.value = false;
    router.push('/validation');
  } catch (err) {
    console.error('Error rejecting POS:', err);
    toast.error('Erreur lors du rejet du PDV');
  }
};

const fetchPOSData = async () => {
  try {
    const data = await PointOfSaleService.getById(route.params.id);
    if (data.pdv) {
      pos.value = data.pdv;
      proximityAlert.value = data.proximity_alert;
    } else {
      pos.value = data;
    }
  } catch (err) {
    console.error('Error loading POS:', err);
  }
};

// Charger le seuil de précision GPS
const loadGpsAccuracyThreshold = async () => {
  try {
    const setting = await SystemSettingService.getSetting('gps_accuracy_max');
    if (setting?.value) {
      gpsAccuracyThreshold.value = parseInt(setting.value) || 30;
    }
  } catch (err) {
    console.error('Error loading GPS accuracy threshold:', err);
  }
};

onMounted(async () => {
  loading.value = true;
  try {
    // Charger les données en parallèle
    const [data] = await Promise.all([
      PointOfSaleService.getById(route.params.id),
      loadGpsAccuracyThreshold()
    ]);
    // Backend returns { pdv, proximity_alert }
    if (data.pdv) {
      pos.value = data.pdv;
      proximityAlert.value = data.proximity_alert;
    } else {
      // Fallback if backend returns PDV directly
      pos.value = data;
    }
    
    // Initialize map after data is loaded
    initMap();
  } catch (err) {
    console.error('Error loading POS:', err);
    error.value = err.message || 'Impossible de charger les détails du PDV';
  } finally {
    loading.value = false;
  }
});
</script>


