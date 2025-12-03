<script setup>
import { RouterView } from 'vue-router';
import { useAuthStore } from './stores/auth';
import { onMounted } from 'vue';

const authStore = useAuthStore();

onMounted(() => {
  // Fetch user data if authenticated
  if (authStore.isAuthenticated) {
    authStore.fetchUser().catch(() => {
      // Handle error silently, auth interceptor will redirect to login
    });
  }
});
</script>

<template>
  <div id="app" class="min-h-screen bg-gray-50">
    <RouterView />
  </div>
</template>

<style scoped>
</style>
