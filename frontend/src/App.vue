<script setup>
import { RouterView } from 'vue-router';
import { useAuthStore } from './stores/auth';
import { onMounted, ref } from 'vue';
import { useToast } from './composables/useToast';
import Toast from './components/Toast.vue';
import GlobalSearch from './components/GlobalSearch.vue';
import ConfirmModal from './components/ConfirmModal.vue';
import PWAInstallPrompt from './components/PWAInstallPrompt.vue';

const authStore = useAuthStore();
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
    <RouterView />
    <Toast ref="toastRef" />
    <GlobalSearch v-if="authStore.isAuthenticated" />
    <ConfirmModal />
    <PWAInstallPrompt />
  </div>
</template>

<style scoped>
</style>
