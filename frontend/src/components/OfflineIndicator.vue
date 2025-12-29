<template>
  <div class="fixed top-0 left-0 right-0 z-50">
    <!-- Indicateur En ligne -->
    <transition name="slide-down">
      <div
        v-if="isOnline && showOnlineIndicator"
        class="bg-green-500 text-white px-4 py-2 text-center text-sm font-semibold shadow-lg flex items-center justify-center gap-2"
      >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
        <span>Connexion rétablie</span>
        <span v-if="pendingActionsCount > 0" class="ml-2 px-2 py-0.5 bg-white/20 rounded-full text-xs">
          Synchronisation de {{ pendingActionsCount }} action(s)...
        </span>
      </div>
    </transition>

    <!-- Indicateur Hors ligne -->
    <transition name="slide-down">
      <div
        v-if="!isOnline"
        class="bg-orange-500 text-white px-4 py-3 text-center shadow-lg"
      >
        <div class="flex items-center justify-center gap-2 mb-1">
          <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414" />
          </svg>
          <span class="font-bold text-sm">Mode hors ligne</span>
        </div>
        <p class="text-xs opacity-90">
          Fonctionnalités limitées. Vos actions seront synchronisées au retour de la connexion.
        </p>
        <div v-if="pendingActionsCount > 0" class="mt-2 text-xs bg-white/10 rounded-lg px-3 py-1 inline-block">
          {{ pendingActionsCount }} action(s) en attente
        </div>
      </div>
    </transition>

    <!-- Indicateur de synchronisation -->
    <transition name="slide-down">
      <div
        v-if="syncInProgress"
        class="bg-blue-500 text-white px-4 py-2 text-center text-sm shadow-lg flex items-center justify-center gap-2"
      >
        <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
        </svg>
        <span class="font-semibold">Synchronisation en cours...</span>
      </div>
    </transition>
  </div>
</template>

<script>
import { ref, watch } from 'vue';
import { useOffline } from '../composables/useOffline';

export default {
  name: 'OfflineIndicator',
  setup() {
    const { isOnline, syncInProgress, pendingActionsCount } = useOffline();
    const showOnlineIndicator = ref(false);
    let hideTimeout = null;

    // Afficher temporairement l'indicateur "En ligne" quand la connexion revient
    watch(isOnline, (newValue, oldValue) => {
      if (newValue && !oldValue) {
        // Connexion rétablie
        showOnlineIndicator.value = true;
        
        // Masquer après 5 secondes
        if (hideTimeout) clearTimeout(hideTimeout);
        hideTimeout = setTimeout(() => {
          showOnlineIndicator.value = false;
        }, 5000);
      }
    });

    return {
      isOnline,
      syncInProgress,
      pendingActionsCount,
      showOnlineIndicator
    };
  }
};
</script>

<style scoped>
.slide-down-enter-active,
.slide-down-leave-active {
  transition: all 0.3s ease;
}

.slide-down-enter-from {
  transform: translateY(-100%);
  opacity: 0;
}

.slide-down-leave-to {
  transform: translateY(-100%);
  opacity: 0;
}
</style>
