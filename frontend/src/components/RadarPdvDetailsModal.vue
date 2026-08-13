<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 z-[2000] flex items-end sm:items-center justify-center bg-gray-950/65 p-0 sm:p-4 backdrop-blur-sm"
      role="dialog"
      aria-modal="true"
      aria-labelledby="radar-pdv-modal-title"
      @click.self="emit('close')"
    >
      <section ref="modalPanel" class="flex max-h-[94vh] w-full max-w-4xl flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl">
        <header class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 sm:px-6">
          <div class="min-w-0">
            <div class="mb-2 flex flex-wrap items-center gap-2">
              <span class="rounded-full px-2.5 py-1 text-xs font-bold" :class="statusClass(pointOfSale?.status)">
                {{ statusLabel(pointOfSale?.status) }}
              </span>
              <span v-if="distance != null" class="text-xs font-semibold text-moov-orange">À {{ formatDistance(distance) }}</span>
            </div>
            <h2 id="radar-pdv-modal-title" class="truncate text-xl font-bold text-gray-900 sm:text-2xl">
              {{ pointOfSale?.nom_point || pointOfSale?.name || 'Détails du PDV' }}
            </h2>
          </div>
          <button ref="closeButton" type="button" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl text-gray-600 transition-colors hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-moov-orange" aria-label="Fermer la fenêtre" @click="emit('close')">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
        </header>

        <div class="overflow-y-auto px-5 py-5 sm:px-6">
          <div v-if="loading" class="flex min-h-72 flex-col items-center justify-center gap-3" aria-live="polite">
            <svg class="h-9 w-9 animate-spin text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" /></svg>
            <p class="font-semibold text-gray-600">Chargement de la fiche complète…</p>
          </div>

          <div v-else-if="error" class="flex min-h-72 flex-col items-center justify-center gap-4 text-center" role="alert">
            <p class="font-semibold text-red-700">{{ error }}</p>
            <button type="button" class="min-h-11 rounded-xl bg-moov-orange px-5 py-2 font-bold text-white focus:outline-none focus:ring-2 focus:ring-moov-orange focus:ring-offset-2" @click="emit('retry')">Réessayer</button>
          </div>

          <div v-else class="space-y-6">
            <DetailSection title="Informations Flooz" :items="floozDetails" />
            <DetailSection title="Propriétaire" :items="ownerDetails" />
            <DetailSection title="Localisation" :items="locationDetails" />
            <DetailSection title="Contacts et fiscalité" :items="contactDetails" />
            <DetailSection title="Suivi" :items="trackingDetails" />
          </div>
        </div>

        <footer class="flex shrink-0 flex-row gap-2 border-t border-gray-200 bg-gray-50 px-5 py-3 sm:justify-end sm:px-6">
          <button type="button" class="min-h-11 flex-1 rounded-xl border border-gray-300 bg-white px-4 py-2.5 font-bold text-gray-700 transition-colors hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 sm:flex-none sm:px-6" @click="emit('close')">Fermer</button>
          <RouterLink
            v-if="pointOfSale?.id"
            :to="`/pdv/${pointOfSale.id}/edit`"
            class="flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-moov-orange to-moov-orange-dark px-4 py-2.5 font-bold text-white shadow transition-shadow hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-moov-orange focus:ring-offset-2 sm:flex-none sm:px-6"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Modifier
          </RouterLink>
        </footer>
      </section>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, h, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

const DetailSection = (props) => h('section', {}, [
  h('h3', { class: 'mb-3 text-base font-bold text-gray-900 sm:text-lg' }, props.title),
  h('dl', { class: 'grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3' }, props.items.map(item =>
    h('div', { class: 'rounded-xl border border-gray-200 bg-gray-50 p-3' }, [
      h('dt', { class: 'mb-1 text-xs font-semibold uppercase tracking-wide text-gray-500' }, item.label),
      h('dd', { class: 'break-words text-sm font-bold text-gray-900' }, item.value || 'N/A'),
    ])
  )),
]);

const props = defineProps({
  pointOfSale: { type: Object, default: null },
  loading: { type: Boolean, default: false },
  error: { type: String, default: '' },
  distance: { type: Number, default: null },
});
const emit = defineEmits(['close', 'retry']);
const closeButton = ref(null);
const previousOverflow = document.body.style.overflow;

const value = (...keys) => keys.map(key => props.pointOfSale?.[key]).find(item => item !== null && item !== undefined && item !== '') || 'N/A';
const phone = (...keys) => formatPhone(value(...keys));
const fullName = computed(() => [value('firstname', 'owner_first_name'), value('lastname', 'owner_last_name')].filter(item => item !== 'N/A').join(' ') || 'N/A');
const dealer = computed(() => props.pointOfSale?.organization?.name || value('dealer_name'));
const creator = computed(() => props.pointOfSale?.creator?.name || props.pointOfSale?.created_by?.name || 'N/A');

const floozDetails = computed(() => [
  { label: 'Dealer', value: dealer.value }, { label: 'Numéro Flooz', value: phone('numero_flooz', 'flooz_number') },
  { label: 'Shortcode', value: value('shortcode') }, { label: 'Profil', value: value('profil', 'profile') },
  { label: "Type d’activité", value: value('type_activite', 'activity_type') },
]);
const ownerDetails = computed(() => [
  { label: 'Nom complet', value: fullName.value }, { label: 'Date de naissance', value: formatDate(value('date_of_birth', 'owner_date_of_birth')) },
  { label: 'Genre', value: value('gender', 'sexe_gerant', 'owner_gender') }, { label: 'Nationalité', value: value('nationality', 'owner_nationality') },
  { label: 'Profession', value: value('profession', 'owner_profession') }, { label: 'Type de pièce', value: value('id_description', 'owner_id_type') },
  { label: 'Numéro de pièce', value: value('id_number', 'owner_id_number') }, { label: "Expiration de la pièce", value: formatDate(value('id_expiry_date', 'owner_id_expiry_date')) },
]);
const locationDetails = computed(() => [
  { label: 'Région', value: value('region') }, { label: 'Préfecture', value: value('prefecture') }, { label: 'Commune', value: value('commune') },
  { label: 'Canton', value: value('canton') }, { label: 'Ville', value: value('ville', 'city') }, { label: 'Quartier', value: value('quartier', 'neighborhood') },
  { label: 'Description', value: value('localisation', 'location_description') }, { label: 'Latitude', value: value('latitude') },
  { label: 'Longitude', value: value('longitude') }, { label: 'Précision GPS', value: value('gps_accuracy') === 'N/A' ? 'N/A' : `${value('gps_accuracy')} m` },
]);
const contactDetails = computed(() => [
  { label: 'Téléphone principal', value: phone('numero_proprietaire', 'owner_phone') }, { label: 'Contact alternatif', value: phone('autre_contact', 'alternative_contact') },
  { label: 'NIF', value: value('nif') }, { label: 'Numéro CAGNT', value: phone('numero_cagnt', 'cagnt_number') },
  { label: 'Régime fiscal', value: value('regime_fiscal', 'tax_regime') }, { label: 'Support de visibilité', value: value('support_visibilite', 'visibility_support') },
  { label: 'État du support', value: value('etat_support', 'support_state') },
]);
const trackingDetails = computed(() => [
  { label: 'Statut', value: statusLabel(props.pointOfSale?.status) }, { label: 'Créé par', value: creator.value },
  { label: 'Créé le', value: formatDate(value('created_at')) }, { label: 'Modifié le', value: formatDate(value('updated_at')) },
  { label: 'Validé le', value: formatDate(value('validated_at')) },
]);

function statusLabel(status) { return ({ validated: 'Validé', pending: 'En attente', rejected: 'Rejeté' })[status] || status || 'N/A'; }
function statusClass(status) { return ({ validated: 'bg-green-100 text-green-700', pending: 'bg-yellow-100 text-yellow-700', rejected: 'bg-red-100 text-red-700' })[status] || 'bg-gray-100 text-gray-700'; }
function formatDistance(km) { return km < 1 ? `${Math.round(km * 1000)} m` : `${km.toFixed(2)} km`; }
function formatPhone(raw) {
  if (!raw || raw === 'N/A') return 'N/A';
  const number = String(raw).replace(/\D/g, '');
  if (number.length === 11) return `${number.slice(0, 3)} ${number.slice(3, 5)} ${number.slice(5, 7)} ${number.slice(7, 9)} ${number.slice(9, 11)}`;
  if (number.length === 8) return number.match(/.{1,2}/g).join(' ');
  return String(raw);
}
function formatDate(raw) {
  if (!raw || raw === 'N/A') return 'N/A';
  const date = new Date(raw);
  return Number.isNaN(date.getTime()) ? String(raw) : new Intl.DateTimeFormat('fr-FR', { dateStyle: 'medium', timeStyle: raw.includes?.('T') ? 'short' : undefined }).format(date);
}
function onKeydown(event) { if (event.key === 'Escape') emit('close'); }

onMounted(async () => {
  document.body.style.overflow = 'hidden';
  document.addEventListener('keydown', onKeydown);
  await nextTick();
  closeButton.value?.focus();
});
onBeforeUnmount(() => {
  document.body.style.overflow = previousOverflow;
  document.removeEventListener('keydown', onKeydown);
});
</script>
