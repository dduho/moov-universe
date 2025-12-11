<template>
  <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Animated gradient background -->
    <div class="absolute inset-0 bg-gradient-to-br from-orange-50 via-white to-orange-50"></div>
    
    <!-- Animated background elements -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
      <!-- Large animated circles -->
      <div class="absolute -top-40 -left-40 w-96 h-96 bg-moov-orange rounded-full opacity-10 blur-3xl animate-pulse"></div>
      <div class="absolute -bottom-40 -right-40 w-96 h-96 bg-moov-orange-dark rounded-full opacity-10 blur-3xl animate-pulse" style="animation-delay: 1.5s;"></div>
      <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-moov-orange-light rounded-full opacity-5 blur-3xl animate-pulse" style="animation-delay: 0.75s;"></div>
      
      <!-- Floating geometric shapes -->
      <div class="absolute top-20 right-20 w-16 h-16 border-4 border-moov-orange/20 rounded-lg rotate-45 animate-bounce" style="animation-duration: 3s;"></div>
      <div class="absolute bottom-32 left-32 w-12 h-12 bg-moov-orange/10 rounded-full animate-bounce" style="animation-duration: 4s; animation-delay: 1s;"></div>
      <div class="absolute top-1/3 right-1/4 w-8 h-8 border-4 border-moov-orange-light/30 rounded-full animate-bounce" style="animation-duration: 5s; animation-delay: 0.5s;"></div>
    </div>
    
    <!-- Login Card -->
    <div class="max-w-md w-full space-y-8 relative z-10">
      <!-- Glass Card -->
      <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-10 rounded-3xl shadow-2xl border-2 border-white/60 backdrop-blur-xl">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
          <div class="inline-flex items-center justify-center mb-6 relative">
            <!-- Glow effect behind logo -->
            <div class="absolute inset-0 bg-gradient-moov rounded-full blur-2xl opacity-30 scale-110"></div>
            <div class="relative">
              <img src="/logo.svg" alt="Moov Logo" class="w-24 h-24 drop-shadow-2xl" />
            </div>
          </div>
          
          <h2 class="text-4xl font-bold mb-3">
            <span class="bg-gradient-moov bg-clip-text text-transparent">
              Moov Money
            </span>
          </h2>
          <h3 class="text-2xl font-semibold text-gray-800 mb-2">
            Universe
          </h3>
          <div class="flex items-center justify-center gap-2 text-gray-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            <p class="text-sm font-medium">
              Plateforme de Gestion des PDV
            </p>
          </div>
        </div>

        <!-- Form -->
        <form class="space-y-5" @submit.prevent="handleLogin">
          <div class="space-y-4">
            <!-- Email Field -->
            <FormInput
              v-model="form.email"
              label="Adresse email"
              type="email"
              placeholder="votre@email.com"
              required
              :icon-left="EmailIcon"
            />

            <!-- Password Field -->
            <FormInput
              v-model="form.password"
              label="Mot de passe"
              type="password"
              placeholder="••••••••"
              required
              :icon-left="LockIcon"
            />
          </div>

          <!-- Error Message -->
          <div v-if="error" class="rounded-xl bg-gradient-to-r from-red-50 to-red-100 border-2 border-red-200 p-4 animate-shake">
            <div class="flex items-start gap-3">
              <svg class="w-5 h-5 text-red-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <p class="text-sm text-red-800 font-semibold">{{ error }}</p>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="pt-2">
            <button
              type="submit"
              :disabled="loading"
              class="group relative w-full flex justify-center items-center gap-3 py-4 px-6 border-0 text-base font-bold rounded-xl text-white bg-gradient-to-r from-moov-orange via-moov-orange-dark to-moov-orange hover:shadow-2xl hover:shadow-moov-orange/50 hover:scale-[1.02] focus:outline-none focus:ring-4 focus:ring-moov-orange/30 disabled:opacity-50 disabled:hover:scale-100 disabled:cursor-not-allowed transition-all duration-300 overflow-hidden"
            >
              <!-- Shimmer effect -->
              <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent -translate-x-full group-hover:translate-x-full transition-transform duration-1000"></div>
              
              <span v-if="!loading" class="flex items-center gap-2 relative z-10">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                </svg>
                Se connecter
              </span>
              <span v-else class="flex items-center gap-3 relative z-10">
                <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Connexion en cours...
              </span>
            </button>
          </div>
        </form>

        <!-- Footer -->
        <div class="mt-6 text-center">
          <p class="text-xs text-gray-500">
            © {{ currentYear }} Moov Money Universe. Tous droits réservés.
          </p>
          <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
            Crafted with <span class="text-red-500">❤️</span> by David D.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, h } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import FormInput from '../components/FormInput.vue';

const router = useRouter();
const authStore = useAuthStore();

const form = ref({
  email: '',
  password: '',
});

const loading = ref(false);
const error = ref('');
const currentYear = new Date().getFullYear()

// Icon components
const EmailIcon = {
  render() {
    return h('svg', {
      class: 'h-5 w-5',
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207'
      })
    ]);
  }
};

const LockIcon = {
  render() {
    return h('svg', {
      class: 'h-5 w-5',
      fill: 'none',
      stroke: 'currentColor',
      viewBox: '0 0 24 24'
    }, [
      h('path', {
        'stroke-linecap': 'round',
        'stroke-linejoin': 'round',
        'stroke-width': '2',
        d: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'
      })
    ]);
  }
};

const handleLogin = async () => {
  loading.value = true;
  error.value = '';

  try {
    await authStore.login(form.value);
    router.push({ name: 'Dashboard' });
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur de connexion';
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-10px); }
  75% { transform: translateX(10px); }
}

.animate-shake {
  animation: shake 0.5s ease-in-out;
}
</style>


