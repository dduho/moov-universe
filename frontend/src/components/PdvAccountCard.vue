<template>
  <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6">
    <div class="flex items-start justify-between gap-3 mb-4">
      <div>
        <h3 class="text-lg font-bold text-gray-900">Compte Flooz</h3>
        <p class="text-xs text-gray-500">Données en direct · shortcode {{ shortcode }}</p>
      </div>
      <button
        type="button"
        @click="load(true)"
        :disabled="loading"
        class="px-3 py-1.5 rounded-lg text-sm font-semibold border border-gray-200 text-gray-700 hover:border-moov-orange hover:text-moov-orange transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-1.5"
        title="Interroger à nouveau le compte"
      >
        <svg class="w-4 h-4" :class="loading ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        Actualiser
      </button>
    </div>

    <!-- Chargement initial -->
    <div v-if="loading && !account" class="space-y-3 animate-pulse" aria-busy="true">
      <div class="h-10 bg-gray-100 rounded-lg"></div>
      <div class="h-4 bg-gray-100 rounded w-2/3"></div>
      <div class="h-4 bg-gray-100 rounded w-1/2"></div>
    </div>

    <!-- Erreur -->
    <div v-else-if="error" class="p-3 rounded-lg border text-sm"
         :class="error.reason === 'unknown_shortcode' ? 'bg-gray-50 border-gray-200 text-gray-700' : 'bg-amber-50 border-amber-200 text-amber-900'">
      <p class="font-semibold">{{ error.reason === 'unknown_shortcode' ? 'Compte introuvable' : 'Solde indisponible' }}</p>
      <p class="mt-0.5">{{ error.message }}</p>
    </div>

    <template v-else-if="account">
      <!-- Solde -->
      <div class="rounded-xl bg-gradient-to-br from-orange-50 to-amber-50 border border-orange-100 p-4">
        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Solde disponible</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ formatAmount(account.balance.available) }} <span class="text-base font-semibold text-gray-500">{{ account.currency }}</span></p>
        <p v-if="account.holder_name" class="text-sm text-gray-600 mt-1">
          {{ account.holder_name }}
          <span v-if="account.status && account.status !== 'Active'" class="ml-1 px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">{{ account.status }}</span>
        </p>
        <p v-if="account.balance.uncleared > 0 || account.balance.reserved > 0" class="text-xs text-gray-500 mt-1">
          <span v-if="account.balance.reserved > 0">Réservé : {{ formatAmount(account.balance.reserved) }}</span>
          <span v-if="account.balance.uncleared > 0"> · En attente : {{ formatAmount(account.balance.uncleared) }}</span>
        </p>
      </div>

      <!-- Transactions -->
      <div class="mt-5">
        <div class="flex items-center justify-between mb-2">
          <h4 class="text-sm font-bold text-gray-900">Dernières transactions</h4>
          <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-xs font-semibold" role="group" aria-label="Période">
            <button v-for="option in periods" :key="option.days" type="button"
                    @click="changePeriod(option.days)" :disabled="loading"
                    class="px-2.5 py-1 transition-colors"
                    :class="days === option.days ? 'bg-moov-orange text-white' : 'bg-white text-gray-600 hover:bg-gray-50'">
              {{ option.label }}
            </button>
          </div>
        </div>

        <p v-if="account.transactions.length === 0" class="text-sm text-gray-500 py-3">
          Aucune transaction sur les {{ account.period.days }} derniers jours.
        </p>

        <ul v-else class="divide-y divide-gray-100">
          <li v-for="tx in account.transactions" :key="tx.receipt" class="py-2.5">
            <div class="flex items-start justify-between gap-3">
              <div class="min-w-0">
                <p class="text-sm font-semibold text-gray-900">{{ typeLabel(tx.type) }}</p>
                <p class="text-xs text-gray-500 line-clamp-2 break-words" :title="tx.description">{{ tx.description }}</p>
                <p class="text-xs text-gray-400 mt-0.5">{{ formatDateTime(tx.completed_at) }} · Réf. {{ tx.receipt }}</p>
              </div>
              <p class="text-sm font-bold text-gray-900 whitespace-nowrap">{{ formatAmount(tx.amount) }}</p>
            </div>
          </li>
        </ul>

        <p v-if="account.transactions_total > account.transactions.length" class="text-xs text-gray-500 mt-2">
          {{ account.transactions.length }} plus récentes affichées sur {{ account.transactions_total }}.
        </p>
      </div>

      <p class="text-xs text-gray-400 mt-4">
        Mis à jour à {{ formatTime(account.fetched_at) }}<span v-if="account.cached"> (résultat conservé 1 minute)</span>
      </p>
    </template>
  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import PointOfSaleService from '../services/PointOfSaleService';

const props = defineProps({
  pdvId: { type: [Number, String], required: true },
  shortcode: { type: String, required: true },
});

const periods = [
  { days: 7, label: '7 jours' },
  { days: 30, label: '30 jours' },
];

const account = ref(null);
const error = ref(null);
const loading = ref(false);
const days = ref(7);
let requestId = 0;

const TYPE_LABELS = {
  CashIn: 'Dépôt client (Cash In)',
  CashOut: 'Retrait client (Cash Out)',
  COMT: 'Transfert de commission',
  GIVE: 'Transfert reçu (Give)',
};
const typeLabel = (type) => TYPE_LABELS[type] || type || 'Transaction';

const formatAmount = (value) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(value ?? 0);

const formatDateTime = (value) => {
  if (!value) return '—';
  const [date, time] = value.split(' ');
  const [y, m, d] = date.split('-');
  return `${d}/${m}/${y} ${time?.slice(0, 5) ?? ''}`.trim();
};

const formatTime = (iso) => {
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? '' : d.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
};

const load = async (refresh = false) => {
  const current = ++requestId;
  loading.value = true;
  if (refresh) error.value = null;

  try {
    const data = await PointOfSaleService.getAccount(props.pdvId, { days: days.value, refresh });
    if (current !== requestId) return; // une requête plus récente a pris le relais
    account.value = data;
    error.value = null;
  } catch (err) {
    if (current !== requestId) return;
    account.value = null;
    error.value = {
      reason: err.response?.data?.reason || 'error',
      message: err.response?.data?.message
        || (err.code === 'ECONNABORTED' ? 'Le service met trop de temps à répondre.' : 'Impossible de récupérer le solde pour le moment.'),
    };
  } finally {
    if (current === requestId) loading.value = false;
  }
};

const changePeriod = (value) => {
  if (days.value === value) return;
  days.value = value;
  load();
};

onMounted(load);
watch(() => [props.pdvId, props.shortcode], () => {
  account.value = null;
  load();
});
</script>
