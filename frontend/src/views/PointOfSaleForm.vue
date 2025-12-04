<template>
  <div class="min-h-screen bg-gradient-mesh">
    <Navbar />
    
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
          <button
            @click="handleCancel"
            class="w-12 h-12 rounded-xl bg-white/50 hover:bg-white flex items-center justify-center transition-all duration-200"
          >
            <svg class="w-6 h-6 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
          </button>
          <h1 class="text-3xl font-bold text-gray-900">Nouveau point de vente</h1>
        </div>
      </div>

      <!-- Progress Indicator -->
      <div class="glass-card p-6 mb-8">
        <div class="flex items-center justify-between mb-4">
          <div
            v-for="step in steps"
            :key="step.number"
            class="flex-1 flex items-center"
          >
            <div class="flex items-center gap-3 flex-1">
              <div
                class="w-10 h-10 rounded-full flex items-center justify-center font-bold transition-all duration-200"
                :class="getStepClass(step.number)"
              >
                <span v-if="step.number < currentStep">✓</span>
                <span v-else>{{ step.number }}</span>
              </div>
              <span
                class="text-sm font-semibold hidden sm:block"
                :class="step.number <= currentStep ? 'text-gray-900' : 'text-gray-400'"
              >
                {{ step.name }}
              </span>
            </div>
            <div
              v-if="step.number < steps.length"
              class="h-1 w-full mx-2"
              :class="step.number < currentStep ? 'bg-moov-orange' : 'bg-gray-200'"
            ></div>
          </div>
        </div>
      </div>

      <!-- Forms -->
      <div class="glass-card p-8">
        <!-- Step 1: Dealer Information -->
        <div v-if="currentStep === 1">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">Informations du dealer</h2>
          <div class="space-y-6">
            <FormSelect
              v-model="formData.organization_id"
              label="Dealer"
              :options="organizationOptions"
              option-label="label"
              option-value="value"
              placeholder="Sélectionnez un dealer"
              :error="errors.organization_id"
              :loading="loadingOrganizations"
              :disabled="!authStore.isAdmin"
              required
            />

            <FormInput
              v-model="formData.point_name"
              label="Nom du point de vente"
              type="text"
              placeholder="Ex: Boutique du Marché"
              :error="errors.point_name"
              required
            />

            <div class="grid grid-cols-2 gap-6">
              <MaskedInput
                v-model="formData.flooz_number"
                label="Numéro Flooz"
                mask="phone"
                placeholder="228 XX XX XX XX"
                :error="errors.flooz_number"
                required
              />
              <MaskedInput
                v-model="formData.shortcode"
                label="Shortcode"
                mask="shortcode"
                placeholder="XXX XXXX"
                :error="errors.shortcode"
                required
              />
            </div>

            <div class="grid grid-cols-2 gap-6">
              <FormSelect
                v-model="formData.profile"
                label="Profil"
                :options="profileOptions"
                option-label="label"
                option-value="value"
                placeholder="Sélectionnez un profil"
                :error="errors.profile"
                required
              />
              <FormInput
                v-model="formData.activity_type"
                label="Type d'activité"
                type="text"
                placeholder="Ex: Commerce de détail"
                :error="errors.activity_type"
              />
            </div>
          </div>
        </div>

        <!-- Step 2: Owner Information -->
        <div v-if="currentStep === 2">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">Informations du propriétaire</h2>
          <div class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
              <FormInput
                v-model="formData.owner_first_name"
                label="Prénom"
                type="text"
                placeholder="Prénom du propriétaire"
                :error="errors.owner_first_name"
              />
              <FormInput
                v-model="formData.owner_last_name"
                label="Nom"
                type="text"
                placeholder="Nom du propriétaire"
                :error="errors.owner_last_name"
              />
            </div>

            <div class="grid grid-cols-2 gap-6">
              <FormInput
                v-model="formData.owner_date_of_birth"
                label="Date de naissance"
                type="date"
                :error="errors.owner_date_of_birth"
              />
              <FormSelect
                v-model="formData.owner_gender"
                label="Genre"
                :options="genderOptions"
                option-label="label"
                option-value="value"
                :error="errors.owner_gender"
              />
            </div>

            <div class="grid grid-cols-2 gap-6">
              <MaskedInput
                v-model="formData.owner_phone"
                label="Téléphone"
                mask="phone"
                placeholder="228 XX XX XX XX"
                :error="errors.owner_phone"
                required
              />
              <MaskedInput
                v-model="formData.alternative_contact"
                label="Contact alternatif"
                mask="phone"
                placeholder="228 XX XX XX XX"
                :error="errors.alternative_contact"
              />
            </div>

            <div class="grid grid-cols-2 gap-6">
              <FormSelect
                v-model="formData.owner_id_type"
                label="Type de pièce d'identité"
                :options="idTypeOptions"
                option-label="label"
                option-value="value"
                :error="errors.owner_id_type"
              />
              <FormInput
                v-model="formData.owner_id_number"
                label="Numéro de pièce"
                type="text"
                :placeholder="currentIdMask ? currentIdMask.display : 'Sélectionnez d\'abord un type de pièce'"
                :disabled="!formData.owner_id_type"
                :maxlength="currentIdMask?.maxLength"
                :error="errors.owner_id_number"
                @input="handleIdNumberInput"
              />
            </div>

            <div class="grid grid-cols-2 gap-6">
              <FormInput
                v-model="formData.owner_id_expiry_date"
                label="Date d'expiration de la pièce"
                type="date"
                :error="errors.owner_id_expiry_date"
              />
              <FormSelect
                v-model="formData.owner_nationality"
                label="Nationalité"
                :options="nationalityOptions"
                option-label="label"
                option-value="value"
                placeholder="Sélectionnez une nationalité"
                :error="errors.owner_nationality"
              />
            </div>

            <FormInput
              v-model="formData.owner_profession"
              label="Profession"
              type="text"
              placeholder="Ex: Commerçant"
              :error="errors.owner_profession"
            />

            <!-- ID Document Upload -->
            <div>
              <label class="block text-sm font-bold text-gray-700 mb-4">Pièce d'identité (scan) </label>
              <FileUploader
                :multiple="true"
                :max-files="2"
                accepted-types="image/jpeg,image/png,image/jpg,application/pdf"
                :max-size="5 * 1024 * 1024"
                type="id_document"
                :metadata="{ point_of_sale_id: null, document_type: 'owner_id' }"
                @uploaded="handleIDUpload"
              />
              <FileGallery
                v-if="uploadedIDDocument.length > 0"
                :files="uploadedIDDocument"
                :allow-delete="true"
                @delete="handleDeleteIDDocument"
                class="mt-4"
              />
            </div>
          </div>
        </div>

        <!-- Step 3: Location with GPS -->
        <div v-if="currentStep === 3">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">Localisation</h2>
          <div class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
              <FormSelect
                v-model="formData.region"
                label="Région"
                :options="regionOptions"
                option-label="label"
                option-value="value"
                placeholder="Sélectionnez une région"
                :error="errors.region"
                required
                @update:modelValue="onRegionChange"
              />
              <FormSelect
                v-model="formData.prefecture"
                label="Préfecture"
                :options="prefectureOptions"
                option-label="label"
                option-value="value"
                placeholder="Sélectionnez une préfecture"
                :disabled="!formData.region"
                :error="errors.prefecture"
                required
                @update:modelValue="onPrefectureChange"
              />
            </div>

            <div class="grid grid-cols-2 gap-6">
              <FormSelect
                v-model="formData.commune"
                label="Commune"
                :options="communeOptions"
                option-label="label"
                option-value="value"
                placeholder="Sélectionnez une commune"
                :disabled="!formData.prefecture"
                :error="errors.commune"
                required
                @update:modelValue="onCommuneChange"
              />
              <FormSelect
                v-model="formData.canton"
                label="Canton"
                :options="cantonOptions"
                option-label="label"
                option-value="value"
                placeholder="Sélectionnez un canton"
                :disabled="!formData.commune"
                :error="errors.canton"
              />
            </div>

            <div class="grid grid-cols-2 gap-6">
              <AutocompleteInput
                v-model="formData.city"
                label="Ville"
                placeholder="Tapez pour rechercher une ville..."
                :suggestions="citySuggestions"
                :error="errors.city"
                required
                @update:modelValue="onCityChange"
              />
              <AutocompleteInput
                v-model="formData.neighborhood"
                label="Quartier"
                placeholder="Tapez pour rechercher un quartier..."
                :suggestions="neighborhoodSuggestions"
                :error="errors.neighborhood"
                required
              />
            </div>

            <FormTextarea
              v-model="formData.location_description"
              label="Description de la localisation"
              :rows="3"
              placeholder="Ex: À côté du marché central, près de la pharmacie..."
              :error="errors.location_description"
            />

            <!-- GPS Capture -->
            <div class="p-6 rounded-xl bg-gradient-to-br from-moov-orange/10 to-moov-orange-light/10 border-2 border-moov-orange/30">
              <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Coordonnées GPS
              </h3>
              <div class="grid grid-cols-2 gap-4 mb-4">
                <FormInput
                  v-model="formData.latitude"
                  label="Latitude"
                  type="number"
                  step="any"
                  placeholder="Ex: 6.1256"
                  :disabled="loadingGPS"
                  :error="errors.latitude"
                  required
                />
                <FormInput
                  v-model="formData.longitude"
                  label="Longitude"
                  type="number"
                  step="any"
                  placeholder="Ex: 1.2254"
                  :disabled="loadingGPS"
                  :error="errors.longitude"
                  required
                />
              </div>
              <button
                @click="captureGPS"
                :disabled="loadingGPS"
                type="button"
                class="w-full px-6 py-3 rounded-xl bg-moov-orange text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
              >
                <svg v-if="!loadingGPS" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <span v-if="loadingGPS" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></span>
                {{ loadingGPS ? 'Capture en cours...' : 'Capturer ma position GPS' }}
              </button>
              <p v-if="proximityAlert" class="mt-4 p-3 rounded-xl bg-red-100 border border-red-300 text-sm text-red-700 font-semibold">
                ⚠️ {{ proximityAlert }}
              </p>
            </div>
          </div>
        </div>

        <!-- Step 4: Fiscalité -->
        <div v-if="currentStep === 4">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">Fiscalité & Documents</h2>
          <div class="space-y-6">
            <!-- Section Fiscalité -->
            <div class="p-6 rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100/50 border-2 border-emerald-200">
              <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Informations fiscales
              </h3>
              <div class="space-y-6">
                <!-- Radio: A-t-il un NIF? -->
                <div>
                  <label class="block text-sm font-bold text-gray-700 mb-3">Le point de vente a-t-il un NIF ? *</label>
                  <div class="flex items-center gap-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input
                        v-model="formData.has_nif"
                        type="radio"
                        value="oui"
                        class="w-5 h-5 text-moov-orange focus:ring-2 focus:ring-moov-orange/20"
                      >
                      <span class="text-sm font-semibold text-gray-700">Oui</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input
                        v-model="formData.has_nif"
                        type="radio"
                        value="non"
                        class="w-5 h-5 text-moov-orange focus:ring-2 focus:ring-moov-orange/20"
                      >
                      <span class="text-sm font-semibold text-gray-700">Non</span>
                    </label>
                  </div>
                  <p v-if="errors.has_nif" class="mt-2 text-sm text-red-600 font-semibold">{{ errors.has_nif }}</p>
                  <p v-else-if="shouldShowNifField" class="mt-2 text-xs text-gray-500">
                    Le NIF est obligatoire pour ce profil ou car vous avez indiqué en avoir un.
                  </p>
                </div>

                <!-- Champ NIF (conditionnel) -->
                <div v-if="shouldShowNifField" class="grid grid-cols-2 gap-6">
                  <FormInput
                    v-model="formData.nif"
                    label="NIF"
                    type="text"
                    placeholder="Numéro d'Identification Fiscale"
                    :error="errors.nif"
                    :required="shouldShowNifField"
                  />
                  <FormSelect
                    v-model="formData.tax_regime"
                    label="Régime fiscal"
                    :options="taxRegimeOptions"
                    option-label="label"
                    option-value="value"
                    placeholder="Sélectionnez un régime"
                    :error="errors.tax_regime"
                    :required="shouldShowNifField"
                  />
                </div>
              </div>
            </div>

            <!-- Section Visibilité & Branding -->
            <div class="p-6 rounded-xl bg-gradient-to-br from-amber-50 to-amber-100/50 border-2 border-amber-200">
              <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                Support de visibilité
              </h3>
              <div class="grid grid-cols-2 gap-6">
                <FormSelect
                  v-model="formData.visibility_support"
                  label="Support de visibilité"
                  :options="visibilitySupportOptions"
                  option-label="label"
                  option-value="value"
                  placeholder="Type de support"
                  required
                  :error="errors.visibility_support"
                />
                <FormSelect
                  v-model="formData.support_state"
                  label="État du support"
                  :options="supportStateOptions"
                  option-label="label"
                  option-value="value"
                  placeholder="Condition du support"
                  :disabled="!formData.visibility_support || formData.visibility_support === 'Aucun'"
                />
              </div>
            </div>

            <!-- Section Numéro CAGNT -->
            <div class="p-6 rounded-xl bg-gradient-to-br from-rose-50 to-rose-100/50 border-2 border-rose-200">
              <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path>
                </svg>
                Code d'agent
              </h3>
              <MaskedInput
                v-model="formData.cagnt_number"
                label="Numéro CAGNT"
                mask="phone"
                placeholder="228 XX XX XX XX"
                :error="errors.cagnt_number"
                required
              />
            </div>

            <!-- Fiscal Documents Upload -->
            <div class="p-6 rounded-xl bg-gradient-to-br from-blue-50 to-blue-100/50 border-2 border-blue-200">
              <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Documents fiscaux
              </h3>
              <p class="text-sm text-gray-600 mb-4">Attestation NIF, preuve de régime fiscal, etc.</p>
              
              <FileUploader
                :multiple="true"
                :max-files="4"
                accepted-types="application/pdf,image/jpeg,image/png"
                :max-size="5 * 1024 * 1024"
                type="fiscal_document"
                :metadata="{ document_type: 'fiscal' }"
                @uploaded="handleFiscalUpload"
              />

              <FileGallery
                v-if="uploadedFiscalDocuments.length > 0"
                :files="uploadedFiscalDocuments"
                :allow-delete="true"
                @delete="handleDeleteFiscalDocument"
                class="mt-4"
              />
            </div>

            <!-- Photos PDV Upload -->
            <div class="p-6 rounded-xl bg-gradient-to-br from-purple-50 to-purple-100/50 border-2 border-purple-200">
              <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Photos du point de vente *
              </h3>
              <p class="text-sm text-gray-600 mb-4">Ajoutez au moins 1 photo du PDV (devanture, intérieur, environnement) - Maximum 4 photos</p>
              
              <FileUploader
                :multiple="true"
                :max-files="4"
                accepted-types="image/jpeg,image/png"
                :max-size="5 * 1024 * 1024"
                type="photo"
                :metadata="{ document_type: 'photo' }"
                @uploaded="handlePhotoUpload"
              />

              <FileGallery
                v-if="uploadedPhotos.length > 0"
                :files="uploadedPhotos"
                :allow-delete="true"
                @delete="handleDeletePhoto"
                class="mt-4"
              />
            </div>
          </div>
        </div>

        <!-- Step 5: Summary -->
        <div v-if="currentStep === 5">
          <h2 class="text-2xl font-bold text-gray-900 mb-6">Récapitulatif</h2>
          <div class="space-y-6">
            <!-- Dealer Info Summary -->
            <div class="p-6 rounded-xl bg-gray-50 border border-gray-200">
              <h3 class="text-lg font-bold text-gray-900 mb-4">Informations du dealer</h3>
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="font-semibold text-gray-600">Organisation:</span> {{ getOrganizationName() }}</div>
                <div><span class="font-semibold text-gray-600">Nom du PDV:</span> {{ formData.point_name }}</div>
                <div><span class="font-semibold text-gray-600">Numéro Flooz:</span> {{ formatPhone(formData.flooz_number) }}</div>
                <div><span class="font-semibold text-gray-600">Shortcode:</span> {{ formatShortcode(formData.shortcode) || 'N/A' }}</div>
                <div><span class="font-semibold text-gray-600">Profil:</span> {{ formData.profile || 'N/A' }}</div>
                <div><span class="font-semibold text-gray-600">Type d'activité:</span> {{ formData.activity_type || 'N/A' }}</div>
              </div>
            </div>

            <!-- Owner Info Summary -->
            <div class="p-6 rounded-xl bg-gray-50 border border-gray-200">
              <h3 class="text-lg font-bold text-gray-900 mb-4">Informations du propriétaire</h3>
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="font-semibold text-gray-600">Nom complet:</span> {{ formData.owner_first_name }} {{ formData.owner_last_name }}</div>
                <div><span class="font-semibold text-gray-600">Date de naissance:</span> {{ formData.owner_date_of_birth }}</div>
                <div><span class="font-semibold text-gray-600">Genre:</span> {{ formData.owner_gender === 'M' ? 'Masculin' : 'Féminin' }}</div>
                <div><span class="font-semibold text-gray-600">Téléphone:</span> {{ formatPhone(formData.owner_phone) }}</div>
                <div><span class="font-semibold text-gray-600">Contact alternatif:</span> {{ formatPhone(formData.alternative_contact) || 'N/A' }}</div>
                <div><span class="font-semibold text-gray-600">Pièce d'identité:</span> {{ getIdTypeLabel }} - {{ formData.owner_id_number || 'N/A' }}</div>
                <div><span class="font-semibold text-gray-600">Date d'expiration:</span> {{ formData.owner_id_expiry_date || 'N/A' }}</div>
                <div><span class="font-semibold text-gray-600">Nationalité:</span> {{ formData.owner_nationality || 'N/A' }}</div>
                <div><span class="font-semibold text-gray-600">Profession:</span> {{ formData.owner_profession || 'N/A' }}</div>
              </div>
            </div>

            <!-- Location Summary -->
            <div class="p-6 rounded-xl bg-gray-50 border border-gray-200">
              <h3 class="text-lg font-bold text-gray-900 mb-4">Localisation</h3>
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="font-semibold text-gray-600">Région:</span> {{ formData.region }}</div>
                <div><span class="font-semibold text-gray-600">Préfecture:</span> {{ formData.prefecture ? formData.prefecture.replace(/_/g, ' ') : 'N/A' }}</div>
                <div><span class="font-semibold text-gray-600">Commune:</span> {{ formData.commune ? formData.commune.replace(/_/g, ' ') : 'N/A' }}</div>
                <div><span class="font-semibold text-gray-600">Canton:</span> {{ formData.canton || 'N/A' }}</div>
                <div><span class="font-semibold text-gray-600">Ville:</span> {{ formData.city }}</div>
                <div><span class="font-semibold text-gray-600">Quartier:</span> {{ formData.neighborhood }}</div>
                <div class="col-span-2"><span class="font-semibold text-gray-600">Coordonnées GPS:</span> {{ formData.latitude }}, {{ formData.longitude }}</div>
              </div>
            </div>

            <!-- Fiscal Summary -->
            <div class="p-6 rounded-xl bg-gray-50 border border-gray-200">
              <h3 class="text-lg font-bold text-gray-900 mb-4">Fiscalité & Visibilité</h3>
              <div class="grid grid-cols-2 gap-4 text-sm">
                <div><span class="font-semibold text-gray-600">NIF:</span> {{ formData.nif || 'N/A' }}</div>
                <div><span class="font-semibold text-gray-600">Régime fiscal:</span> {{ formData.tax_regime || 'N/A' }}</div>
                <div><span class="font-semibold text-gray-600">Support de visibilité:</span> {{ formData.visibility_support || 'N/A' }}</div>
                <div><span class="font-semibold text-gray-600">État du support:</span> {{ formData.support_state || 'N/A' }}</div>
                <div><span class="font-semibold text-gray-600">Numéro CAGNT:</span> {{ formatPhone(formData.cagnt_number) || 'N/A' }}</div>
              </div>
            </div>

            <!-- Uploaded Documents Summary -->
            <div class="p-6 rounded-xl bg-gray-50 border border-gray-200">
              <h3 class="text-lg font-bold text-gray-900 mb-4">Documents téléchargés</h3>
              <div class="space-y-3 text-sm">
                <div class="flex items-center gap-2">
                  <svg class="w-5 h-5" :class="uploadedIDDocument.length > 0 ? 'text-green-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  <span class="font-semibold text-gray-600">Pièce d'identité:</span>
                  <span>{{ uploadedIDDocument.length > 0 ? `${uploadedIDDocument.length} document(s)` : 'Non fourni' }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <svg class="w-5 h-5" :class="uploadedPhotos.length > 0 ? 'text-green-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  <span class="font-semibold text-gray-600">Photos du PDV:</span>
                  <span>{{ uploadedPhotos.length > 0 ? `${uploadedPhotos.length} photo(s)` : 'Non fourni' }}</span>
                </div>
                <div class="flex items-center gap-2">
                  <svg class="w-5 h-5" :class="uploadedFiscalDocuments.length > 0 ? 'text-green-600' : 'text-gray-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                  </svg>
                  <span class="font-semibold text-gray-600">Documents fiscaux:</span>
                  <span>{{ uploadedFiscalDocuments.length > 0 ? `${uploadedFiscalDocuments.length} document(s)` : 'Non fourni' }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex items-center justify-between mt-8 pt-6 border-t border-gray-200">
          <button
            v-if="currentStep > 1"
            @click="previousStep"
            type="button"
            class="px-6 py-3 rounded-xl bg-white border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 transition-all duration-200"
          >
            ← Précédent
          </button>
          <div v-else></div>

          <button
            v-if="currentStep < 5"
            @click="nextStep"
            :disabled="validating"
            type="button"
            class="px-6 py-3 rounded-xl bg-moov-orange text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <span v-if="validating" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></span>
            {{ validating ? 'Vérification...' : 'Suivant →' }}
          </button>
          <button
            v-else
            @click="submitForm"
            :disabled="submitting"
            type="button"
            class="px-6 py-3 rounded-xl bg-gradient-to-r from-green-500 to-green-600 text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <span v-if="submitting" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></span>
            {{ submitting ? 'Enregistrement...' : 'Créer le PDV' }}
          </button>
        </div>
      </div>
    </div>

    <!-- Floating Clear Button -->
    <button
      @click="clearAllFields"
      class="fixed bottom-8 right-8 w-14 h-14 rounded-full bg-red-500 text-white shadow-2xl hover:bg-red-600 hover:scale-110 transition-all duration-200 flex items-center justify-center z-50 group"
      title="Vider le formulaire"
    >
      <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
      </svg>
      <span class="absolute right-16 top-1/2 -translate-y-1/2 bg-gray-900 text-white text-sm px-3 py-2 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap">
        Vider le formulaire
      </span>
    </button>

    <!-- Confirmation Dialogs -->
    <ConfirmDialog
      v-model:isOpen="showCancelDialog"
      title="Annuler la création"
      message="Êtes-vous sûr de vouloir annuler ? Toutes les données seront perdues."
      confirm-text="Oui, annuler"
      cancel-text="Non, continuer"
      type="warning"
      @confirm="confirmCancel"
    />

    <ConfirmDialog
      v-model:isOpen="showClearDialog"
      title="Vider le formulaire"
      message="Êtes-vous sûr de vouloir vider tous les champs du formulaire ? Cette action est irréversible."
      confirm-text="Oui, vider"
      cancel-text="Annuler"
      type="danger"
      @confirm="confirmClearFields"
    />
  </div>
</template>

<script setup>
import { ref, onMounted, computed, h, watch, nextTick } from 'vue';
import { useRouter } from 'vue-router';
import Navbar from '../components/Navbar.vue';
import FileUploader from '../components/FileUploader.vue';
import FileGallery from '../components/FileGallery.vue';
import FormInput from '../components/FormInput.vue';
import FormSelect from '../components/FormSelect.vue';
import MaskedInput from '../components/MaskedInput.vue';
import AutocompleteInput from '../components/AutocompleteInput.vue';
import ConfirmDialog from '../components/ConfirmDialog.vue';
import { useToast } from '../composables/useToast';
import FormTextarea from '../components/FormTextarea.vue';
import PointOfSaleService from '../services/PointOfSaleService';
import UploadService from '../services/UploadService';
import { useAuthStore } from '../stores/auth';
import { useOrganizationStore } from '../stores/organization';
import { geographicHierarchy } from '../data/geographicHierarchy';
import { getCitiesByCommune } from '../data/citiesAndVillages';
import { getNeighborhoodsByCity } from '../data/neighborhoods';
import { formatPhone, formatShortcode } from '../utils/formatters';

const router = useRouter();
const authStore = useAuthStore();
const organizationStore = useOrganizationStore();
const { toast } = useToast();

const STORAGE_KEY = 'pdv_form_draft';

const currentStep = ref(1);
const loadingGPS = ref(false);
const submitting = ref(false);
const validating = ref(false);
const proximityAlert = ref(null);
const organizations = ref([]);
const errors = ref({});
const loadingOrganizations = ref(false);

// Dialog states
const showCancelDialog = ref(false);
const showClearDialog = ref(false);

// File uploads
const uploadedIDDocument = ref([]);
const uploadedPhotos = ref([]);
const uploadedFiscalDocuments = ref([]);

const steps = [
  { number: 1, name: 'Dealer' },
  { number: 2, name: 'Propriétaire' },
  { number: 3, name: 'Localisation' },
  { number: 4, name: 'Fiscalité' },
  { number: 5, name: 'Récapitulatif' }
];

// Computed options for selects
const organizationOptions = computed(() => {
  return organizations.value.map(org => ({
    label: org.name,
    value: org.id
  }));
});

const profileOptions = [
  { label: 'Sélectionnez un profil', value: '' },
  { label: 'DISTRO', value: 'DISTRO' },
  { label: 'AGNT', value: 'AGNT' },
  { label: 'DISTROWNIF', value: 'DISTROWNIF' },
  { label: 'DISTROTC', value: 'DISTROTC' },
  { label: 'BANKAGNT', value: 'BANKAGNT' },
  { label: 'FTAGNT', value: 'FTAGNT' }
];

const genderOptions = [
  { label: 'Sélectionnez', value: '' },
  { label: 'Masculin', value: 'M' },
  { label: 'Féminin', value: 'F' }
];

const idTypeOptions = [
  { label: 'Sélectionnez', value: '' },
  { label: "Carte Nationale d'Identité", value: 'cni' },
  { label: 'Passeport', value: 'passport' },
  { label: 'Carte de séjour', value: 'residence' },
  { label: "Carte d'électeur", value: 'elector' },
  { label: 'Permis de conduire', value: 'driving_license' },
  { label: "Carte d'identité étrangère", value: 'foreign_id' },
  { label: 'Carte ANID', value: 'anid_card' }
];

const nationalityOptions = [
  { label: 'Sélectionnez', value: '' },
  { label: 'Afghane', value: 'Afghane' },
  { label: 'Albanaise', value: 'Albanaise' },
  { label: 'Algérienne', value: 'Algérienne' },
  { label: 'Allemande', value: 'Allemande' },
  { label: 'Américaine', value: 'Américaine' },
  { label: 'Andorrane', value: 'Andorrane' },
  { label: 'Angolaise', value: 'Angolaise' },
  { label: 'Antiguaise et barbudienne', value: 'Antiguaise et barbudienne' },
  { label: 'Argentine', value: 'Argentine' },
  { label: 'Arménienne', value: 'Arménienne' },
  { label: 'Australienne', value: 'Australienne' },
  { label: 'Autrichienne', value: 'Autrichienne' },
  { label: 'Azerbaïdjanaise', value: 'Azerbaïdjanaise' },
  { label: 'Bahamienne', value: 'Bahamienne' },
  { label: 'Bahreïnienne', value: 'Bahreïnienne' },
  { label: 'Bangladaise', value: 'Bangladaise' },
  { label: 'Barbadienne', value: 'Barbadienne' },
  { label: 'Belge', value: 'Belge' },
  { label: 'Bélizienne', value: 'Bélizienne' },
  { label: 'Béninoise', value: 'Béninoise' },
  { label: 'Bhoutanaise', value: 'Bhoutanaise' },
  { label: 'Biélorusse', value: 'Biélorusse' },
  { label: 'Birmane', value: 'Birmane' },
  { label: 'Bissau-Guinéenne', value: 'Bissau-Guinéenne' },
  { label: 'Bolivienne', value: 'Bolivienne' },
  { label: 'Bosnienne', value: 'Bosnienne' },
  { label: 'Botswanaise', value: 'Botswanaise' },
  { label: 'Brésilienne', value: 'Brésilienne' },
  { label: 'Britannique', value: 'Britannique' },
  { label: 'Brunéienne', value: 'Brunéienne' },
  { label: 'Bulgare', value: 'Bulgare' },
  { label: 'Burkinabé', value: 'Burkinabé' },
  { label: 'Burundaise', value: 'Burundaise' },
  { label: 'Cambodgienne', value: 'Cambodgienne' },
  { label: 'Camerounaise', value: 'Camerounaise' },
  { label: 'Canadienne', value: 'Canadienne' },
  { label: 'Cap-verdienne', value: 'Cap-verdienne' },
  { label: 'Centrafricaine', value: 'Centrafricaine' },
  { label: 'Chilienne', value: 'Chilienne' },
  { label: 'Chinoise', value: 'Chinoise' },
  { label: 'Chypriote', value: 'Chypriote' },
  { label: 'Colombienne', value: 'Colombienne' },
  { label: 'Comorienne', value: 'Comorienne' },
  { label: 'Congolaise', value: 'Congolaise' },
  { label: 'Congolaise (RDC)', value: 'Congolaise (RDC)' },
  { label: 'Nord-coréenne', value: 'Nord-coréenne' },
  { label: 'Sud-coréenne', value: 'Sud-coréenne' },
  { label: 'Costaricaine', value: 'Costaricaine' },
  { label: 'Croate', value: 'Croate' },
  { label: 'Cubaine', value: 'Cubaine' },
  { label: 'Danoise', value: 'Danoise' },
  { label: 'Djiboutienne', value: 'Djiboutienne' },
  { label: 'Dominicaine', value: 'Dominicaine' },
  { label: 'Dominiquaise', value: 'Dominiquaise' },
  { label: 'Égyptienne', value: 'Égyptienne' },
  { label: 'Émirienne', value: 'Émirienne' },
  { label: 'Équato-guinéenne', value: 'Équato-guinéenne' },
  { label: 'Équatorienne', value: 'Équatorienne' },
  { label: 'Érythréenne', value: 'Érythréenne' },
  { label: 'Espagnole', value: 'Espagnole' },
  { label: 'Estonienne', value: 'Estonienne' },
  { label: 'Éthiopienne', value: 'Éthiopienne' },
  { label: 'Fidjienne', value: 'Fidjienne' },
  { label: 'Finlandaise', value: 'Finlandaise' },
  { label: 'Française', value: 'Française' },
  { label: 'Gabonaise', value: 'Gabonaise' },
  { label: 'Gambienne', value: 'Gambienne' },
  { label: 'Géorgienne', value: 'Géorgienne' },
  { label: 'Ghanéenne', value: 'Ghanéenne' },
  { label: 'Grecque', value: 'Grecque' },
  { label: 'Grenadienne', value: 'Grenadienne' },
  { label: 'Guatémaltèque', value: 'Guatémaltèque' },
  { label: 'Guinéenne', value: 'Guinéenne' },
  { label: 'Guyanienne', value: 'Guyanienne' },
  { label: 'Haïtienne', value: 'Haïtienne' },
  { label: 'Hellénique', value: 'Hellénique' },
  { label: 'Hondurienne', value: 'Hondurienne' },
  { label: 'Hongroise', value: 'Hongroise' },
  { label: 'Indienne', value: 'Indienne' },
  { label: 'Indonésienne', value: 'Indonésienne' },
  { label: 'Irakienne', value: 'Irakienne' },
  { label: 'Iranienne', value: 'Iranienne' },
  { label: 'Irlandaise', value: 'Irlandaise' },
  { label: 'Islandaise', value: 'Islandaise' },
  { label: 'Israélienne', value: 'Israélienne' },
  { label: 'Italienne', value: 'Italienne' },
  { label: 'Ivoirienne', value: 'Ivoirienne' },
  { label: 'Jamaïcaine', value: 'Jamaïcaine' },
  { label: 'Japonaise', value: 'Japonaise' },
  { label: 'Jordanienne', value: 'Jordanienne' },
  { label: 'Kazakhstanaise', value: 'Kazakhstanaise' },
  { label: 'Kényane', value: 'Kényane' },
  { label: 'Kirghize', value: 'Kirghize' },
  { label: 'Kiribatienne', value: 'Kiribatienne' },
  { label: 'Koweïtienne', value: 'Koweïtienne' },
  { label: 'Laotienne', value: 'Laotienne' },
  { label: 'Lesothane', value: 'Lesothane' },
  { label: 'Lettone', value: 'Lettone' },
  { label: 'Libanaise', value: 'Libanaise' },
  { label: 'Libérienne', value: 'Libérienne' },
  { label: 'Libyenne', value: 'Libyenne' },
  { label: 'Liechtensteinoise', value: 'Liechtensteinoise' },
  { label: 'Lituanienne', value: 'Lituanienne' },
  { label: 'Luxembourgeoise', value: 'Luxembourgeoise' },
  { label: 'Macédonienne', value: 'Macédonienne' },
  { label: 'Malgache', value: 'Malgache' },
  { label: 'Malaisienne', value: 'Malaisienne' },
  { label: 'Malawienne', value: 'Malawienne' },
  { label: 'Maldivienne', value: 'Maldivienne' },
  { label: 'Malienne', value: 'Malienne' },
  { label: 'Maltaise', value: 'Maltaise' },
  { label: 'Marocaine', value: 'Marocaine' },
  { label: 'Marshallaise', value: 'Marshallaise' },
  { label: 'Mauricienne', value: 'Mauricienne' },
  { label: 'Mauritanienne', value: 'Mauritanienne' },
  { label: 'Mexicaine', value: 'Mexicaine' },
  { label: 'Micronésienne', value: 'Micronésienne' },
  { label: 'Moldave', value: 'Moldave' },
  { label: 'Monégasque', value: 'Monégasque' },
  { label: 'Mongole', value: 'Mongole' },
  { label: 'Monténégrine', value: 'Monténégrine' },
  { label: 'Mozambicaine', value: 'Mozambicaine' },
  { label: 'Namibienne', value: 'Namibienne' },
  { label: 'Nauruane', value: 'Nauruane' },
  { label: 'Néerlandaise', value: 'Néerlandaise' },
  { label: 'Néo-zélandaise', value: 'Néo-zélandaise' },
  { label: 'Népalaise', value: 'Népalaise' },
  { label: 'Nicaraguayenne', value: 'Nicaraguayenne' },
  { label: 'Nigériane', value: 'Nigériane' },
  { label: 'Nigérienne', value: 'Nigérienne' },
  { label: 'Norvégienne', value: 'Norvégienne' },
  { label: 'Omanaise', value: 'Omanaise' },
  { label: 'Ougandaise', value: 'Ougandaise' },
  { label: 'Ouzbèke', value: 'Ouzbèke' },
  { label: 'Pakistanaise', value: 'Pakistanaise' },
  { label: 'Palaosienne', value: 'Palaosienne' },
  { label: 'Palestinienne', value: 'Palestinienne' },
  { label: 'Panaméenne', value: 'Panaméenne' },
  { label: 'Papouane-Néo-Guinéenne', value: 'Papouane-Néo-Guinéenne' },
  { label: 'Paraguayenne', value: 'Paraguayenne' },
  { label: 'Péruvienne', value: 'Péruvienne' },
  { label: 'Philippine', value: 'Philippine' },
  { label: 'Polonaise', value: 'Polonaise' },
  { label: 'Portugaise', value: 'Portugaise' },
  { label: 'Qatarienne', value: 'Qatarienne' },
  { label: 'Roumaine', value: 'Roumaine' },
  { label: 'Russe', value: 'Russe' },
  { label: 'Rwandaise', value: 'Rwandaise' },
  { label: 'Saint-Lucienne', value: 'Saint-Lucienne' },
  { label: 'Saint-Marinaise', value: 'Saint-Marinaise' },
  { label: 'Saint-Vincentaise', value: 'Saint-Vincentaise' },
  { label: 'Salomonaise', value: 'Salomonaise' },
  { label: 'Salvadorienne', value: 'Salvadorienne' },
  { label: 'Samoane', value: 'Samoane' },
  { label: 'Santoméenne', value: 'Santoméenne' },
  { label: 'Saoudienne', value: 'Saoudienne' },
  { label: 'Sénégalaise', value: 'Sénégalaise' },
  { label: 'Serbe', value: 'Serbe' },
  { label: 'Seychelloise', value: 'Seychelloise' },
  { label: 'Sierra-Léonaise', value: 'Sierra-Léonaise' },
  { label: 'Singapourienne', value: 'Singapourienne' },
  { label: 'Slovaque', value: 'Slovaque' },
  { label: 'Slovène', value: 'Slovène' },
  { label: 'Somalienne', value: 'Somalienne' },
  { label: 'Soudanaise', value: 'Soudanaise' },
  { label: 'Sri-Lankaise', value: 'Sri-Lankaise' },
  { label: 'Sud-Africaine', value: 'Sud-Africaine' },
  { label: 'Sud-Soudanaise', value: 'Sud-Soudanaise' },
  { label: 'Suédoise', value: 'Suédoise' },
  { label: 'Suisse', value: 'Suisse' },
  { label: 'Surinamaise', value: 'Surinamaise' },
  { label: 'Swazie', value: 'Swazie' },
  { label: 'Syrienne', value: 'Syrienne' },
  { label: 'Tadjike', value: 'Tadjike' },
  { label: 'Tanzanienne', value: 'Tanzanienne' },
  { label: 'Tchadienne', value: 'Tchadienne' },
  { label: 'Tchèque', value: 'Tchèque' },
  { label: 'Thaïlandaise', value: 'Thaïlandaise' },
  { label: 'Timoraise', value: 'Timoraise' },
  { label: 'Togolaise', value: 'Togolaise' },
  { label: 'Tonguienne', value: 'Tonguienne' },
  { label: 'Trinidadienne', value: 'Trinidadienne' },
  { label: 'Tunisienne', value: 'Tunisienne' },
  { label: 'Turkmène', value: 'Turkmène' },
  { label: 'Turque', value: 'Turque' },
  { label: 'Tuvaluane', value: 'Tuvaluane' },
  { label: 'Ukrainienne', value: 'Ukrainienne' },
  { label: 'Uruguayenne', value: 'Uruguayenne' },
  { label: 'Vanuatuane', value: 'Vanuatuane' },
  { label: 'Vaticane', value: 'Vaticane' },
  { label: 'Vénézuélienne', value: 'Vénézuélienne' },
  { label: 'Vietnamienne', value: 'Vietnamienne' },
  { label: 'Yéménite', value: 'Yéménite' },
  { label: 'Zambienne', value: 'Zambienne' },
  { label: 'Zimbabwéenne', value: 'Zimbabwéenne' }
];

// Configuration des masques pour chaque type de pièce d'identité
const idMasks = {
  cni: {
    pattern: /^[0-9]{4}-[0-9]{3}-[0-9]{4}$/,
    display: 'XXXX-XXX-XXXX',
    maxLength: 14,
    format: value => {
      const digits = value.replace(/\D/g, '').slice(0, 11);
      const parts = [];
      if (digits.length > 0) parts.push(digits.slice(0, 4));
      if (digits.length > 4) parts.push(digits.slice(4, 7));
      if (digits.length > 7) parts.push(digits.slice(7, 11));
      return parts.join('-');
    },
    clean: value => value.replace(/\D/g, '')
  },
  passport: {
    pattern: /^[A-Z0-9]{8}$/,
    display: 'XXXXXXXX',
    maxLength: 8,
    format: value => {
      return value.replace(/[^A-Z0-9]/g, '').toUpperCase().slice(0, 8);
    },
    clean: value => value.replace(/[^A-Z0-9]/g, '').toUpperCase()
  },
  elector: {
    pattern: /^[0-9]{2}-[0-9]{2}-[0-9]{3}-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]{2}-[0-9]{5}$/,
    display: 'XX-XX-XXX-XX-XX-XX-XX-XXXXX',
    maxLength: 27,
    format: value => {
      const digits = value.replace(/\D/g, '').slice(0, 20);
      const parts = [];
      if (digits.length > 0) parts.push(digits.slice(0, 2));
      if (digits.length > 2) parts.push(digits.slice(2, 4));
      if (digits.length > 4) parts.push(digits.slice(4, 7));
      if (digits.length > 7) parts.push(digits.slice(7, 9));
      if (digits.length > 9) parts.push(digits.slice(9, 11));
      if (digits.length > 11) parts.push(digits.slice(11, 13));
      if (digits.length > 13) parts.push(digits.slice(13, 15));
      if (digits.length > 15) parts.push(digits.slice(15, 20));
      return parts.join('-');
    },
    clean: value => value.replace(/\D/g, '')
  },
  residence: {
    pattern: /^[A-Z0-9]{1,20}$/,
    display: 'Numéro de carte de séjour (max 20 caractères)',
    maxLength: 20,
    format: value => {
      return value.replace(/[^A-Z0-9]/g, '').toUpperCase().slice(0, 20);
    },
    clean: value => value.replace(/[^A-Z0-9]/g, '').toUpperCase()
  },
  driving_license: {
    pattern: /^[A-Z0-9]{3} [A-Z0-9]{3} [A-Z0-9]{3}$/,
    display: 'XXX XXX XXX',
    maxLength: 11,
    format: value => {
      const chars = value.replace(/[^A-Z0-9]/g, '').toUpperCase().slice(0, 9);
      const parts = [];
      if (chars.length > 0) parts.push(chars.slice(0, 3));
      if (chars.length > 3) parts.push(chars.slice(3, 6));
      if (chars.length > 6) parts.push(chars.slice(6, 9));
      return parts.join(' ');
    },
    clean: value => value.replace(/[^A-Z0-9]/g, '').toUpperCase()
  },
  anid_card: {
    pattern: /^[0-9]{4} [0-9]{4} [0-9]{4}$/,
    display: 'XXXX XXXX XXXX',
    maxLength: 14,
    format: value => {
      const digits = value.replace(/\D/g, '').slice(0, 12);
      const parts = [];
      if (digits.length > 0) parts.push(digits.slice(0, 4));
      if (digits.length > 4) parts.push(digits.slice(4, 8));
      if (digits.length > 8) parts.push(digits.slice(8, 12));
      return parts.join(' ');
    },
    clean: value => value.replace(/\D/g, '')
  },
  foreign_id: {
    pattern: /^.+$/,
    display: "Numéro carte d'identité étrangère (max 20 caractères)",
    maxLength: 20,
    format: value => {
      return value.slice(0, 20);
    },
    clean: value => value
  }
};

// Computed pour obtenir le placeholder et le masque du type de pièce sélectionné
const currentIdMask = computed(() => {
  const idType = formData.value.owner_id_type;
  return idMasks[idType] || null;
});

// Computed pour obtenir le label du type de pièce d'identité
const getIdTypeLabel = computed(() => {
  const option = idTypeOptions.find(opt => opt.value === formData.value.owner_id_type);
  return option ? option.label : formData.value.owner_id_type;
});

const regionOptions = [
  { label: 'Sélectionnez une région', value: '' },
  { label: 'Maritime', value: 'MARITIME' },
  { label: 'Plateaux', value: 'PLATEAUX' },
  { label: 'Centrale', value: 'CENTRALE' },
  { label: 'Kara', value: 'KARA' },
  { label: 'Savanes', value: 'SAVANES' }
];

// Computed options pour la hiérarchie géographique
const prefectureOptions = computed(() => {
  if (!formData.value.region) return [{ label: 'Sélectionnez d\'abord une région', value: '' }];
  
  const prefectures = Object.keys(geographicHierarchy[formData.value.region]?.prefectures || {});
  return [
    { label: 'Sélectionnez une préfecture', value: '' },
    ...prefectures.map(pref => ({ label: pref.replace(/_/g, ' '), value: pref }))
  ];
});

const communeOptions = computed(() => {
  if (!formData.value.region || !formData.value.prefecture) {
    return [{ label: 'Sélectionnez d\'abord une préfecture', value: '' }];
  }
  
  const communes = Object.keys(
    geographicHierarchy[formData.value.region]?.prefectures[formData.value.prefecture]?.communes || {}
  );
  return [
    { label: 'Sélectionnez une commune', value: '' },
    ...communes.map(comm => ({ label: comm.replace(/_/g, ' '), value: comm }))
  ];
});

const cantonOptions = computed(() => {
  if (!formData.value.region || !formData.value.prefecture || !formData.value.commune) {
    return [{ label: 'Sélectionnez d\'abord une commune', value: '' }];
  }
  
  const cantons = geographicHierarchy[formData.value.region]
    ?.prefectures[formData.value.prefecture]
    ?.communes[formData.value.commune]?.cantons || [];
  
  if (cantons.length === 0) {
    return [{ label: 'Aucun canton disponible', value: '' }];
  }
  
  return [
    { label: 'Sélectionnez un canton', value: '' },
    ...cantons.map(cant => ({ label: cant, value: cant }))
  ];
});

// Suggestions pour les villes basées sur la commune sélectionnée
const citySuggestions = computed(() => {
  if (!formData.value.region || !formData.value.prefecture || !formData.value.commune) {
    return [];
  }
  
  const cities = getCitiesByCommune(
    formData.value.region,
    formData.value.prefecture,
    formData.value.commune
  );
  
  return cities || [];
});

// Suggestions pour les quartiers basées sur la ville sélectionnée
const neighborhoodSuggestions = computed(() => {
  if (!formData.value.region || !formData.value.prefecture || !formData.value.commune || !formData.value.city) {
    return [];
  }
  
  const neighborhoods = getNeighborhoodsByCity(
    formData.value.region,
    formData.value.prefecture,
    formData.value.commune,
    formData.value.city
  );
  
  return neighborhoods || [];
});

const taxRegimeOptions = [
  { label: 'Sélectionnez', value: '' },
  { label: 'Réel sans TVA', value: 'Réel sans TVA' },
  { label: 'Réel avec TVA', value: 'Réel avec TVA' },
  { label: 'TPU', value: 'TPU' }
];

const visibilitySupportOptions = [
  { label: 'Sélectionnez', value: '' },
  { label: 'Autocollant', value: 'Autocollant' },
  { label: 'Potence', value: 'Potence' },
  { label: 'Chevalet', value: 'Chevalet' },
  { label: 'Buget Flags', value: 'Buget Flags' },
  { label: 'Parasols', value: 'Parasols' },
  { label: 'Beach Flags', value: 'Beach Flags' },
  { label: 'Enseignes Lumineuses', value: 'Enseignes Lumineuses' }
];

const supportStateOptions = [
  { label: 'Sélectionnez', value: '' },
  { label: 'Bon', value: 'BON' },
  { label: 'Mauvais', value: 'MAUVAIS' }
];

// Computed: Afficher le champ NIF si oui sélectionné OU si profil = DISTROWNIF ou BANKAGNT
const shouldShowNifField = computed(() => {
  // Si "Oui" est sélectionné dans le radio
  if (formData.value.has_nif === 'oui') {
    return true;
  }
  // Si le profil est DISTROWNIF ou BANKAGNT, cacher le NIF (pas obligatoire)
  if (formData.value.profile === 'DISTROWNIF' || formData.value.profile === 'BANKAGNT') {
    return false;
  }
  // Sinon, afficher si "Oui" est sélectionné
  return formData.value.has_nif === 'oui';
});

const formData = ref({
  organization_id: authStore.organizationId || null,
  point_name: '',
  flooz_number: '',
  shortcode: '',
  profile: '',
  activity_type: '',
  owner_first_name: '',
  owner_last_name: '',
  owner_date_of_birth: '',
  owner_gender: '',
  owner_phone: '',
  alternative_contact: '',
  owner_id_type: '',
  owner_id_number: '',
  owner_id_expiry_date: '',
  owner_nationality: '',
  owner_profession: '',
  region: '',
  prefecture: '',
  commune: '',
  canton: '',
  city: '',
  neighborhood: '',
  location_description: '',
  latitude: '',
  longitude: '',
  has_nif: '',
  nif: '',
  tax_regime: '',
  visibility_support: '',
  support_state: '',
  cagnt_number: ''
});

const getStepClass = (stepNumber) => {
  if (stepNumber < currentStep.value) {
    return 'bg-green-500 text-white';
  } else if (stepNumber === currentStep.value) {
    return 'bg-moov-orange text-white';
  }
  return 'bg-gray-200 text-gray-500';
};

const getOrganizationName = () => {
  if (!authStore.isAdmin) {
    return authStore.user?.organization?.name || 'N/A';
  }
  const org = organizations.value.find(o => o.id === formData.value.organization_id);
  return org?.name || 'N/A';
};

// Gestion des changements hiérarchiques
const onRegionChange = () => {
  // Réinitialiser les champs dépendants
  formData.value.prefecture = '';
  formData.value.commune = '';
  formData.value.canton = '';
};

const onPrefectureChange = () => {
  // Réinitialiser les champs dépendants
  formData.value.commune = '';
  formData.value.canton = '';
};

const onCommuneChange = () => {
  // Réinitialiser le canton et la ville
  formData.value.canton = '';
  formData.value.city = '';
  formData.value.neighborhood = '';
};

const onCityChange = () => {
  // Réinitialiser le quartier
  formData.value.neighborhood = '';
};

const validateStep = async () => {
  errors.value = {};
  validating.value = true;
  const step = currentStep.value;
  
  try {
    if (step === 1) {
    // Validation étape 1: Dealer
    if (authStore.isAdmin && !formData.value.organization_id) {
      errors.value.organization_id = 'Veuillez sélectionner une organisation';
    }
    
    if (!formData.value.point_name) {
      errors.value.point_name = 'Le nom du point de vente est obligatoire';
    }
    
    if (!formData.value.flooz_number) {
      errors.value.flooz_number = 'Le numéro Flooz est obligatoire';
    } else if (formData.value.flooz_number.length !== 11) {
      errors.value.flooz_number = 'Le numéro doit contenir 11 chiffres (228XXXXXXXX)';
    } else {
      // Vérifier l'unicité du numéro Flooz
      try {
        const result = await PointOfSaleService.checkUniqueness('numero_flooz', formData.value.flooz_number);
        if (result.exists) {
          errors.value.flooz_number = 'Ce numéro Flooz est déjà utilisé par un autre point de vente';
        }
      } catch (error) {
        console.error('Error checking flooz number uniqueness:', error);
      }
    }
    
    if (!formData.value.shortcode) {
      errors.value.shortcode = 'Le shortcode est obligatoire';
    } else if (formData.value.shortcode.length !== 7) {
      errors.value.shortcode = 'Le shortcode doit contenir exactement 7 chiffres';
    } else {
      // Vérifier l'unicité du shortcode
      try {
        const result = await PointOfSaleService.checkUniqueness('shortcode', formData.value.shortcode);
        if (result.exists) {
          errors.value.shortcode = 'Ce shortcode est déjà utilisé par un autre point de vente';
        }
      } catch (error) {
        console.error('Error checking shortcode uniqueness:', error);
      }
    }
    
    if (!formData.value.profile) {
      errors.value.profile = 'Le profil est obligatoire';
    }
    
    if (Object.keys(errors.value).length > 0) {
      toast.error('Veuillez corriger les erreurs avant de continuer', 'Validation échouée');
      return false;
    }
  }
  
  if (step === 2) {
    // Validation étape 2: Propriétaire
    
    if (!formData.value.owner_phone) {
      errors.value.owner_phone = 'Le numéro propriétaire est obligatoire';
    } else if (formData.value.owner_phone.length !== 11) {
      errors.value.owner_phone = 'Le numéro doit contenir 11 chiffres (228XXXXXXXX)';
    }
    
    // Traiter '228' seul comme vide
    if (formData.value.alternative_contact && formData.value.alternative_contact !== '228' && formData.value.alternative_contact.length !== 11) {
      errors.value.alternative_contact = 'Le numéro doit contenir 11 chiffres (228XXXXXXXX)';
    }
    
    // Validation de la date de naissance (au moins 18 ans)
    if (formData.value.owner_date_of_birth) {
      const birthDate = new Date(formData.value.owner_date_of_birth);
      const today = new Date();
      const age = today.getFullYear() - birthDate.getFullYear();
      const monthDiff = today.getMonth() - birthDate.getMonth();
      const dayDiff = today.getDate() - birthDate.getDate();
      
      const actualAge = monthDiff < 0 || (monthDiff === 0 && dayDiff < 0) ? age - 1 : age;
      
      if (actualAge < 18) {
        errors.value.owner_date_of_birth = 'Le propriétaire doit avoir au moins 18 ans';
      }
    }
    
    // Validation de la date d'expiration de la pièce (ne doit pas être expirée)
    if (formData.value.owner_id_expiry_date) {
      const expiryDate = new Date(formData.value.owner_id_expiry_date);
      const today = new Date();
      today.setHours(0, 0, 0, 0); // Réinitialiser l'heure pour comparer uniquement les dates
      
      if (expiryDate < today) {
        errors.value.owner_id_expiry_date = 'La pièce d\'identité est expirée';
      }
    }
    
    // Validation du numéro de pièce d'identité
    if (formData.value.owner_id_type && formData.value.owner_id_number) {
      if (!validateIdNumber()) {
        const mask = idMasks[formData.value.owner_id_type];
        errors.value.owner_id_number = `Format invalide. Format attendu: ${mask?.display || 'invalide'}`;
      }
    }
    
    if (Object.keys(errors.value).length > 0) {
      toast.error('Veuillez corriger les erreurs avant de continuer', 'Validation échouée');
      return false;
    }
  }
  
  if (step === 3) {
    // Validation étape 3: Localisation
    if (!formData.value.region) {
      errors.value.region = 'La région est obligatoire';
    }
    
    if (!formData.value.prefecture) {
      errors.value.prefecture = 'La préfecture est obligatoire';
    }
    
    if (!formData.value.commune) {
      errors.value.commune = 'La commune est obligatoire';
    }
    
    if (!formData.value.city) {
      errors.value.city = 'La ville est obligatoire';
    }
    
    if (!formData.value.neighborhood) {
      errors.value.neighborhood = 'Le quartier est obligatoire';
    }
    
    if (!formData.value.latitude || !formData.value.longitude) {
      if (!formData.value.latitude) {
        errors.value.latitude = 'La latitude est obligatoire';
      }
      if (!formData.value.longitude) {
        errors.value.longitude = 'La longitude est obligatoire';
      }
      toast.warning('Veuillez capturer la position GPS du point de vente', 'GPS requis');
      return false;
    }
    
    if (Object.keys(errors.value).length > 0) {
      toast.error('Veuillez corriger les erreurs avant de continuer', 'Validation échouée');
      return false;
    }
  }
  
  if (step === 4) {
    // Validation étape 4: Fiscalité & Documents
    if (!formData.value.has_nif) {
      errors.value.has_nif = 'Veuillez indiquer si le PDV a un NIF';
      toast.error('Veuillez indiquer si le point de vente a un NIF', 'Validation échouée');
      return false;
    }
    
    // Si le NIF doit être affiché, il est obligatoire
    if (shouldShowNifField.value && !formData.value.nif) {
      errors.value.nif = 'Le NIF est obligatoire';
    }
    
    // Si le NIF est oui, le régime fiscal est obligatoire
    if (formData.value.has_nif === 'oui' && !formData.value.tax_regime) {
      errors.value.tax_regime = 'Le régime fiscal est obligatoire si le PDV a un NIF';
    }
    
    if (!formData.value.visibility_support) {
      errors.value.visibility_support = 'Le support de visibilité est obligatoire';
    }
    
    if (!formData.value.cagnt_number) {
      errors.value.cagnt_number = 'Le numéro CAGNT est obligatoire';
    } else if (formData.value.cagnt_number.length !== 11) {
      errors.value.cagnt_number = 'Le numéro doit contenir 11 chiffres (228XXXXXXXX)';
    }
    
    // Vérifier qu'il y a au moins une photo
    if (uploadedPhotos.value.length === 0) {
      toast.error('Vous devez ajouter au moins une photo du point de vente', 'Photos requises');
      return false;
    }
    
    if (Object.keys(errors.value).length > 0) {
      toast.error('Veuillez corriger les erreurs avant de continuer', 'Validation échouée');
      return false;
    }
  }
  
  return true;
} finally {
    validating.value = false;
  }
};

// Gestion du formatage du numéro de pièce d'identité
const handleIdNumberInput = (event) => {
  if (!currentIdMask.value) return;
  
  const input = event.target;
  const cursorPosition = input.selectionStart;
  const oldValue = formData.value.owner_id_number;
  const newValue = input.value;
  
  // Appliquer le formatage
  const formatted = currentIdMask.value.format(newValue);
  formData.value.owner_id_number = formatted;
  
  // Ajuster la position du curseur après formatage
  nextTick(() => {
    const lengthDiff = formatted.length - oldValue.length;
    const newCursorPos = cursorPosition + lengthDiff;
    input.setSelectionRange(newCursorPos, newCursorPos);
  });
};

// Valider le format du numéro de pièce d'identité
const validateIdNumber = () => {
  if (!formData.value.owner_id_number || !formData.value.owner_id_type) {
    return true; // Pas de validation si vide (géré par required)
  }
  
  const mask = idMasks[formData.value.owner_id_type];
  if (!mask) return true;
  
  // Nettoyer la valeur et vérifier le pattern
  const cleanValue = mask.clean(formData.value.owner_id_number);
  
  // Pour foreign_id, on accepte n'importe quel format non vide
  if (formData.value.owner_id_type === 'foreign_id') {
    return cleanValue.length > 0 && cleanValue.length <= 20;
  }
  
  // Pour les autres, vérifier le pattern
  return mask.pattern.test(formData.value.owner_id_number);
};

const nextStep = async () => {
  if (await validateStep()) {
    errors.value = {};
    currentStep.value++;
  }
};

const previousStep = () => {
  errors.value = {};
  currentStep.value--;
};

const captureGPS = async () => {
  loadingGPS.value = true;
  proximityAlert.value = null;
  
  try {
    if (!navigator.geolocation) {
      toast.error('La géolocalisation n\'est pas supportée par votre navigateur', 'Erreur GPS');
      return;
    }

    const position = await new Promise((resolve, reject) => {
      navigator.geolocation.getCurrentPosition(resolve, reject, {
        enableHighAccuracy: true,
        timeout: 10000,
        maximumAge: 0
      });
    });

    formData.value.latitude = position.coords.latitude.toFixed(6);
    formData.value.longitude = position.coords.longitude.toFixed(6);

    // Check proximity
    try {
      const proximityData = await PointOfSaleService.checkProximity(
        formData.value.latitude,
        formData.value.longitude
      );
      
      if (proximityData.has_nearby && proximityData.nearby_pos?.length > 0) {
        const count = proximityData.nearby_pos.length;
        proximityAlert.value = `${count} PDV trouvé${count > 1 ? 's' : ''} à proximité. Vérifiez avant de continuer.`;
      }
    } catch (err) {
      console.error('Proximity check error:', err);
    }
  } catch (err) {
    console.error('GPS capture error:', err);
    toast.error('Impossible de capturer la position GPS. Vérifiez vos autorisations.', 'Erreur GPS');
  } finally {
    loadingGPS.value = false;
  }
};

const submitForm = async () => {
  submitting.value = true;
  
  try {
    // Validate organization_id
    if (!formData.value.organization_id) {
      toast.error('Veuillez sélectionner une organisation');
      submitting.value = false;
      return;
    }
    
    // Debug log
    console.log('Organization ID avant envoi:', formData.value.organization_id);
    console.log('Type:', typeof formData.value.organization_id);
    
    // Map frontend field names to backend field names
    const dataToSubmit = {
      // Organization
      organization_id: parseInt(formData.value.organization_id) || formData.value.organization_id,
      
      // Dealer info
      dealer_name: getOrganizationName(), // Organization name as dealer_name
      numero_flooz: formData.value.flooz_number, // flooz_number -> numero_flooz
      shortcode: formData.value.shortcode,
      
      // Point of sale info
      nom_point: formData.value.point_name, // point_name -> nom_point
      profil: formData.value.profile, // profile -> profil
      type_activite: formData.value.activity_type, // activity_type -> type_activite
      
      // Owner info
      firstname: formData.value.owner_first_name, // owner_first_name -> firstname
      lastname: formData.value.owner_last_name, // owner_last_name -> lastname
      date_of_birth: formData.value.owner_date_of_birth,
      gender: formData.value.owner_gender,
      sexe_gerant: formData.value.owner_gender, // Added for backward compatibility
      id_description: formData.value.owner_id_type, // owner_id_type -> id_description
      id_number: formData.value.owner_id_number,
      id_expiry_date: formData.value.owner_id_expiry_date,
      nationality: formData.value.owner_nationality,
      profession: formData.value.owner_profession,
      numero_proprietaire: formData.value.owner_phone, // owner_phone -> numero_proprietaire
      autre_contact: formData.value.alternative_contact === '228' ? '' : formData.value.alternative_contact, // alternative_contact -> autre_contact
      
      // Location
      region: formData.value.region,
      prefecture: formData.value.prefecture,
      commune: formData.value.commune,
      canton: formData.value.canton,
      ville: formData.value.city, // city -> ville
      quartier: formData.value.neighborhood, // neighborhood -> quartier
      localisation: formData.value.location_description, // location_description -> localisation
      latitude: formData.value.latitude,
      longitude: formData.value.longitude,
      
      // Fiscal
      nif: formData.value.nif,
      regime_fiscal: formData.value.tax_regime, // tax_regime -> regime_fiscal
      support_visibilite: formData.value.visibility_support, // visibility_support -> support_visibilite
      etat_support: formData.value.support_state, // support_state -> etat_support
      numero_cagnt: formData.value.cagnt_number, // cagnt_number -> numero_cagnt
      
      // Uploaded files
      owner_id_document_ids: uploadedIDDocument.value.map(doc => doc.id),
      photo_ids: uploadedPhotos.value.map(photo => photo.id),
      fiscal_document_ids: uploadedFiscalDocuments.value.map(doc => doc.id)
    };

    await PointOfSaleService.create(dataToSubmit);
    
    // Effacer le brouillon après soumission réussie
    clearFormStorage();
    
    toast.success('Le point de vente a été créé avec succès !', 'Succès');
    router.push('/pdv/list');
  } catch (err) {
    console.error('Error creating POS:', err);
    const errorMessage = err.response?.data?.message || 'Une erreur est survenue lors de la création du PDV';
    toast.error(errorMessage, 'Erreur');
  } finally {
    submitting.value = false;
  }
};

const handleCancel = () => {
  showCancelDialog.value = true;
};

const confirmCancel = async () => {
  try {
    // Supprimer les fichiers uploadés du serveur
    const filesToDelete = [
      ...uploadedIDDocument.value,
      ...uploadedPhotos.value,
      ...uploadedFiscalDocuments.value
    ];
    
    for (const file of filesToDelete) {
      if (file.path) {
        try {
          await UploadService.delete(file.path);
        } catch (error) {
          console.error('Erreur lors de la suppression du fichier:', file.name, error);
        }
      }
    }
    
    // Effacer le brouillon
    clearFormStorage();
    
    router.push('/dashboard');
  } catch (error) {
    console.error('Erreur lors de l\'annulation:', error);
    // Rediriger quand même même en cas d'erreur de suppression
    clearFormStorage();
    router.push('/dashboard');
  }
};

const clearAllFields = () => {
  showClearDialog.value = true;
};

const confirmClearFields = async () => {
  try {
    // Supprimer les fichiers uploadés du serveur
    const filesToDelete = [
      ...uploadedIDDocument.value,
      ...uploadedPhotos.value,
      ...uploadedFiscalDocuments.value
    ];
    
    // Supprimer chaque fichier du serveur
    for (const file of filesToDelete) {
      if (file.path) {
        try {
          await UploadService.delete(file.path);
        } catch (error) {
          console.error('Erreur lors de la suppression du fichier:', file.name, error);
        }
      }
    }
    
    // Réinitialiser toutes les données du formulaire
    Object.keys(formData.value).forEach(key => {
      if (key === 'organization_id' && !authStore.isAdmin) {
        // Garder l'organization_id pour les non-admins
        return;
      }
      formData.value[key] = '';
    });
    
    // Vider les fichiers uploadés
    uploadedIDDocument.value = [];
    uploadedPhotos.value = [];
    uploadedFiscalDocuments.value = [];
    
    // Revenir à la première étape
    currentStep.value = 1;
    
    // Vider les erreurs
    errors.value = {};
    
    // Effacer le brouillon du localStorage
    clearFormStorage();
    
    toast.success('Formulaire vidé', 'Tous les champs et fichiers ont été supprimés');
  } catch (error) {
    console.error('Erreur lors du vidage du formulaire:', error);
    toast.error('Erreur', 'Une erreur est survenue lors du vidage du formulaire');
  }
};

const handleIDUpload = (files) => {
  console.log('handleIDUpload received:', files);
  if (Array.isArray(files)) {
    uploadedIDDocument.value.push(...files);
  } else {
    uploadedIDDocument.value.push(files);
  }
  console.log('uploadedIDDocument after push:', uploadedIDDocument.value);
};

const handlePhotoUpload = (files) => {
  console.log('handlePhotoUpload received:', files);
  if (Array.isArray(files)) {
    uploadedPhotos.value.push(...files);
  } else {
    uploadedPhotos.value.push(files);
  }
  console.log('uploadedPhotos after push:', uploadedPhotos.value);
};

const handleFiscalUpload = (files) => {
  console.log('handleFiscalUpload received:', files);
  if (Array.isArray(files)) {
    uploadedFiscalDocuments.value.push(...files);
  } else {
    uploadedFiscalDocuments.value.push(files);
  }
  console.log('uploadedFiscalDocuments after push:', uploadedFiscalDocuments.value);
};

// Handlers de suppression
const handleDeleteIDDocument = ({ file, index }) => {
  console.log('Deleting ID document at index:', index);
  uploadedIDDocument.value.splice(index, 1);
};

const handleDeletePhoto = ({ file, index }) => {
  console.log('Deleting photo at index:', index);
  uploadedPhotos.value.splice(index, 1);
};

const handleDeleteFiscalDocument = ({ file, index }) => {
  console.log('Deleting fiscal document at index:', index);
  uploadedFiscalDocuments.value.splice(index, 1);
};

// Sauvegarder les données du formulaire dans localStorage
const saveFormToStorage = () => {
  try {
    const formDraft = {
      formData: formData.value,
      currentStep: currentStep.value,
      uploadedIDDocument: uploadedIDDocument.value,
      uploadedPhotos: uploadedPhotos.value,
      uploadedFiscalDocuments: uploadedFiscalDocuments.value,
      timestamp: new Date().toISOString()
    };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(formDraft));
  } catch (error) {
    console.error('Erreur lors de la sauvegarde du brouillon:', error);
  }
};

// Charger les données du formulaire depuis localStorage
const loadFormFromStorage = () => {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    if (saved) {
      const formDraft = JSON.parse(saved);
      
      // Restaurer les données du formulaire
      if (formDraft.formData) {
        // Sauvegarder l'organization_id actuel pour les dealers
        const currentOrgId = formData.value.organization_id;
        Object.assign(formData.value, formDraft.formData);
        
        // Si l'utilisateur n'est pas admin, restaurer son organization_id
        if (!authStore.isAdmin && currentOrgId) {
          formData.value.organization_id = currentOrgId;
        }
      }
      
      // Restaurer l'étape actuelle
      if (formDraft.currentStep) {
        currentStep.value = formDraft.currentStep;
      }
      
      // Restaurer les fichiers uploadés
      if (formDraft.uploadedIDDocument) {
        uploadedIDDocument.value = formDraft.uploadedIDDocument;
      }
      if (formDraft.uploadedPhotos) {
        uploadedPhotos.value = formDraft.uploadedPhotos;
      }
      if (formDraft.uploadedFiscalDocuments) {
        uploadedFiscalDocuments.value = formDraft.uploadedFiscalDocuments;
      }
      
      toast.info('Brouillon restauré', 'Vos données ont été récupérées');
    }
  } catch (error) {
    console.error('Erreur lors du chargement du brouillon:', error);
  }
};

// Effacer le brouillon du localStorage
const clearFormStorage = () => {
  try {
    localStorage.removeItem(STORAGE_KEY);
  } catch (error) {
    console.error('Erreur lors de la suppression du brouillon:', error);
  }
};

// Watcher pour sauvegarder automatiquement les données du formulaire
watch(formData, () => {
  saveFormToStorage();
}, { deep: true });

// Watcher pour sauvegarder l'étape actuelle
watch(currentStep, () => {
  saveFormToStorage();
});

// Watcher pour sauvegarder les fichiers uploadés
watch([uploadedIDDocument, uploadedPhotos, uploadedFiscalDocuments], () => {
  saveFormToStorage();
}, { deep: true });

// Réinitialiser le numéro de pièce quand le type change
watch(() => formData.value.owner_id_type, () => {
  formData.value.owner_id_number = '';
  if (errors.value.owner_id_number) {
    delete errors.value.owner_id_number;
  }
});

// Validation en temps réel pour le numéro Flooz
let floozCheckTimeout = null;
watch(() => formData.value.flooz_number, async (newValue) => {
  if (floozCheckTimeout) clearTimeout(floozCheckTimeout);
  
  if (newValue && newValue.length === 11) {
    floozCheckTimeout = setTimeout(async () => {
      try {
        const result = await PointOfSaleService.checkUniqueness('numero_flooz', newValue);
        if (result.exists) {
          errors.value.flooz_number = 'Ce numéro Flooz est déjà utilisé par un autre point de vente';
        } else if (errors.value.flooz_number === 'Ce numéro Flooz est déjà utilisé par un autre point de vente') {
          delete errors.value.flooz_number;
        }
      } catch (error) {
        console.error('Error checking flooz number:', error);
      }
    }, 500);
  }
});

// Validation en temps réel pour le shortcode
let shortcodeCheckTimeout = null;
watch(() => formData.value.shortcode, async (newValue) => {
  if (shortcodeCheckTimeout) clearTimeout(shortcodeCheckTimeout);
  
  if (newValue && newValue.length === 7) {
    shortcodeCheckTimeout = setTimeout(async () => {
      try {
        const result = await PointOfSaleService.checkUniqueness('shortcode', newValue);
        if (result.exists) {
          errors.value.shortcode = 'Ce shortcode est déjà utilisé par un autre point de vente';
        } else if (errors.value.shortcode === 'Ce shortcode est déjà utilisé par un autre point de vente') {
          delete errors.value.shortcode;
        }
      } catch (error) {
        console.error('Error checking shortcode:', error);
      }
    }, 500);
  }
});

onMounted(async () => {
  // Pré-remplir l'organisation pour les non-admins AVANT de charger le brouillon
  if (!authStore.isAdmin && authStore.organizationId) {
    formData.value.organization_id = authStore.organizationId;
  }
  
  if (authStore.isAdmin) {
    loadingOrganizations.value = true;
    await organizationStore.fetchOrganizations();
    organizations.value = organizationStore.organizations;
    loadingOrganizations.value = false;
  }
  
  // Charger le brouillon sauvegardé (après avoir défini l'organisation par défaut)
  loadFormFromStorage();
});
</script>
