<template>
  <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6">
    <div class="flex items-start justify-between gap-3 mb-4">
      <div>
        <h3 class="text-lg font-bold text-gray-900">Compte Flooz</h3>
        <p class="text-xs text-gray-500">Données en direct · shortcode {{ shortcode }}</p>
      </div>
      <button
        type="button"
        @click="refresh"
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

      <!-- Activité du PDV, d'après son historique -->
      <div v-if="account.activity" class="mt-4 rounded-xl border p-3" :class="levelStyle.box" data-testid="activity">
        <div class="flex items-center justify-between gap-2">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Activité</p>
          <span class="px-2.5 py-0.5 rounded-full text-xs font-bold" :class="levelStyle.badge" :title="levelHint">{{ levelStyle.label }}</span>
        </div>
        <p class="text-sm font-semibold text-gray-900 mt-1.5">{{ lastActivityText }}</p>
        <p v-if="account.activity.customer_transactions > 0" class="text-xs text-gray-600 mt-1">
          {{ account.activity.customer_transactions }} transaction{{ account.activity.customer_transactions > 1 ? 's' : '' }} client
          ({{ formatAmount(account.activity.customer_volume) }}) sur {{ account.activity.window_days }} jours
          · {{ account.activity.cash_in.count }} dépôt{{ account.activity.cash_in.count > 1 ? 's' : '' }} ({{ formatAmount(account.activity.cash_in.amount) }})
          · {{ account.activity.cash_out.count }} retrait{{ account.activity.cash_out.count > 1 ? 's' : '' }} ({{ formatAmount(account.activity.cash_out.amount) }})
        </p>
      </div>

      <!-- Historique -->
      <div class="mt-5">
        <div class="flex items-center justify-between mb-2">
          <h4 class="text-sm font-bold text-gray-900">Historique des transactions</h4>
          <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-xs font-semibold" role="group" aria-label="Période">
            <button v-for="option in periods" :key="option.days" type="button"
                    @click="changePeriod(option.days)" :disabled="loading"
                    class="px-2.5 py-1 transition-colors"
                    :class="days === option.days ? 'bg-moov-orange text-white' : 'bg-white text-gray-600 hover:bg-gray-50'">
              {{ option.label }}
            </button>
          </div>
        </div>

        <div v-if="account.transactions_error" class="p-3 rounded-lg border bg-amber-50 border-amber-200 text-sm text-amber-900">
          <p>{{ account.transactions_error }}</p>
          <button type="button" class="mt-1 font-semibold underline" @click="refresh">Réessayer</button>
        </div>

        <p v-else-if="account.pagination.total === 0" class="text-sm text-gray-500 py-3">
          Aucune transaction sur les {{ account.period.days }} derniers jours.
        </p>

        <template v-else>
          <ul class="divide-y divide-gray-100 transition-opacity max-h-96 overflow-y-auto overscroll-contain pr-1" :class="loading ? 'opacity-50' : ''" data-testid="transactions">
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

          <!-- Pagination : 10 transactions par page -->
          <nav class="flex items-center justify-between gap-2 mt-3" aria-label="Pagination de l'historique" data-testid="pagination">
            <p class="text-xs text-gray-500">
              {{ account.pagination.from }}–{{ account.pagination.to }} sur {{ account.pagination.total }}<span v-if="account.pagination.truncated">+</span>
            </p>
            <div class="flex items-center gap-1.5">
              <button type="button" @click="goToPage(page - 1)" :disabled="loading || page <= 1"
                      class="px-2.5 py-1 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:border-moov-orange hover:text-moov-orange disabled:opacity-40 disabled:cursor-not-allowed"
                      aria-label="Page précédente">‹ Précédent</button>
              <span class="text-xs text-gray-600 px-1">Page {{ account.pagination.page }} / {{ account.pagination.last_page }}</span>
              <button type="button" @click="goToPage(page + 1)" :disabled="loading || page >= account.pagination.last_page"
                      class="px-2.5 py-1 rounded-lg border border-gray-200 text-xs font-semibold text-gray-700 hover:border-moov-orange hover:text-moov-orange disabled:opacity-40 disabled:cursor-not-allowed"
                      aria-label="Page suivante">Suivant ›</button>
            </div>
          </nav>
          <p v-if="account.pagination.truncated" class="text-xs text-gray-500 mt-1">
            Compte très actif : seules les {{ account.pagination.total }} transactions les plus récentes sont conservées.
          </p>
        </template>
      </div>

      <p class="text-xs text-gray-400 mt-4">
        Mis à jour à {{ formatTime(account.fetched_at) }}<span v-if="account.cached"> (résultat conservé 1 minute)</span>
      </p>
    </template>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue';
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
const page = ref(1);
let requestId = 0;

const TYPE_LABELS = {
  CashIn: 'Dépôt client (Cash In)',
  CashOut: 'Retrait client (Cash Out)',
  COMT: 'Transfert de commission',
  GIVE: 'Transfert reçu (Give)',
};
const typeLabel = (type) => TYPE_LABELS[type] || type || 'Transaction';

const LEVELS = {
  active: { label: 'Actif', box: 'bg-green-50 border-green-200', badge: 'bg-green-100 text-green-700' },
  low: { label: 'Peu actif', box: 'bg-amber-50 border-amber-200', badge: 'bg-amber-100 text-amber-800' },
  inactive: { label: 'Inactif', box: 'bg-red-50 border-red-200', badge: 'bg-red-100 text-red-700' },
};
const levelStyle = computed(() => LEVELS[account.value?.activity?.level] || LEVELS.inactive);

const levelHint = computed(() => {
  const t = account.value?.activity?.thresholds;
  return t
    ? `Actif : transaction client dans les ${t.active} derniers jours · Peu actif : jusqu'à ${t.low} jours · Inactif : au-delà. Les approvisionnements (Give) et commissions ne comptent pas.`
    : '';
});

const lastActivityText = computed(() => {
  const a = account.value?.activity;
  if (!a) return '';
  if (!a.last_transaction_at) return `Aucune transaction client sur les ${a.window_days} derniers jours`;
  const when = a.days_since_last === 0 ? "aujourd'hui" : a.days_since_last === 1 ? 'hier' : `il y a ${a.days_since_last} jours`;
  return `Dernière transaction client : ${when} (${formatDateTime(a.last_transaction_at)})`;
});

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

const load = async ({ refresh = false } = {}) => {
  const current = ++requestId;
  loading.value = true;
  if (refresh) error.value = null;

  try {
    const data = await PointOfSaleService.getAccount(props.pdvId, { days: days.value, page: page.value, refresh });
    if (current !== requestId) return; // une requête plus récente a pris le relais
    account.value = data;
    page.value = data.pagination?.page ?? 1; // le serveur ramène une page hors limites à la dernière page
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

const refresh = () => {
  page.value = 1;
  load({ refresh: true });
};

const goToPage = (target) => {
  const last = account.value?.pagination?.last_page ?? 1;
  const next = Math.max(1, Math.min(last, target));
  if (next === page.value) return;
  page.value = next;
  load();
};

const changePeriod = (value) => {
  if (days.value === value) return;
  days.value = value;
  page.value = 1;
  load();
};

onMounted(load);
watch(() => [props.pdvId, props.shortcode], () => {
  account.value = null;
  page.value = 1;
  load();
});
</script>
