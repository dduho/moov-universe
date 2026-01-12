<script setup>
import { RouterView } from 'vue-router';
import { useAuthStore } from './stores/auth';
import { onMounted, ref } from 'vue';
import { setActivePinia } from 'pinia';
import { useToast } from './composables/useToast';
import Toast from './components/Toast.vue';
import GlobalSearch from './components/GlobalSearch.vue';
import ConfirmModal from './components/ConfirmModal.vue';
import PWAInstallPrompt from './components/PWAInstallPrompt.vue';
import OfflineIndicator from './components/OfflineIndicator.vue';
import ScrollToTop from './components/ScrollToTop.vue';

// Inject explicit pinia instance to avoid getActivePinia issues on reloads
import pinia from './plugins/pinia';
setActivePinia(pinia);
const authStore = useAuthStore(pinia);
const { setToastComponent } = useToast();
const toastRef = ref(null);

onMounted(() => {
  // Set toast component reference
  if (toastRef.value) {
    setToastComponent(toastRef.value);
  }

  // Fetch user data if authenticated
  if (authStore.isAuthenticated) {
    authStore.fetchUser().catch(() => {
      // Handle error silently, auth interceptor will redirect to login
    });
  }
});
</script>

<template>
  <div id="app" class="min-h-screen gradient-mesh">
    <OfflineIndicator />
    <RouterView v-slot="{ Component, route }">
      <Transition name="page" mode="out-in">
        <component :is="Component" :key="route.path" />
      </Transition>
    </RouterView>
    <Toast ref="toastRef" />
    <GlobalSearch v-if="authStore.isAuthenticated" />
    <ConfirmModal />
    <PWAInstallPrompt />
    <ScrollToTop />
  </div>
</template>

<style scoped>
</style>


