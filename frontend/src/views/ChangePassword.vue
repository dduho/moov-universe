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

    <div class="w-full max-w-md relative z-10">
      <!-- Logo -->
      <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center mb-6 relative">
          <!-- Glow effect behind logo -->
          <div class="absolute inset-0 bg-gradient-moov rounded-full blur-2xl opacity-30 scale-110"></div>
          <div class="relative">
            <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-moov-orange to-moov-orange-dark shadow-2xl flex items-center justify-center">
              <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
              </svg>
            </div>
          </div>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">Changement de mot de passe</h1>
        <p class="text-gray-600 mt-2">
          {{ isFirstLogin ? 'Pour des raisons de sécurité, vous devez changer votre mot de passe lors de votre première connexion.' : 'Créez un nouveau mot de passe sécurisé.' }}
        </p>
      </div>

      <!-- Form Card -->
      <div class="glass-card p-6 sm:p-8 rounded-3xl shadow-2xl border-2 border-white/60 backdrop-blur-xl">
        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Current Password -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Mot de passe actuel
            </label>
            <div class="relative">
              <input
                :type="showCurrentPassword ? 'text' : 'password'"
                v-model="form.current_password"
                class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-moov-orange focus:ring-4 focus:ring-moov-orange/20 transition-all duration-200 pr-12"
                placeholder="Votre mot de passe actuel"
                required
              />
              <button
                type="button"
                @click="showCurrentPassword = !showCurrentPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <svg v-if="showCurrentPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
              </button>
            </div>
            <p v-if="errors.current_password" class="mt-1 text-sm text-red-600">{{ errors.current_password }}</p>
          </div>

          <!-- New Password -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Nouveau mot de passe
            </label>
            <div class="relative">
              <input
                :type="showNewPassword ? 'text' : 'password'"
                v-model="form.new_password"
                @input="checkPasswordStrength"
                class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-200 pr-12"
                :class="form.new_password ? (passwordStrength.score >= 4 ? 'border-green-400 focus:border-green-500 focus:ring-green-200' : 'border-orange-400 focus:border-orange-500 focus:ring-orange-200') : 'border-gray-200 focus:border-moov-orange focus:ring-moov-orange/20'"
                placeholder="Votre nouveau mot de passe"
                required
              />
              <button
                type="button"
                @click="showNewPassword = !showNewPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <svg v-if="showNewPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
              </button>
            </div>
            <p v-if="errors.new_password" class="mt-1 text-sm text-red-600">{{ errors.new_password }}</p>

            <!-- Password Strength Indicator -->
            <div v-if="form.new_password" class="mt-3">
              <div class="flex items-center gap-2 mb-2">
                <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                  <div 
                    class="h-full transition-all duration-300 rounded-full"
                    :class="strengthBarClass"
                    :style="{ width: `${(passwordStrength.score / 5) * 100}%` }"
                  ></div>
                </div>
                <span class="text-xs font-semibold" :class="strengthTextClass">
                  {{ passwordStrength.label }}
                </span>
              </div>
              
              <!-- Password Rules -->
              <ul class="space-y-1">
                <li 
                  v-for="rule in passwordRules" 
                  :key="rule.id"
                  class="flex items-center gap-2 text-xs"
                  :class="rule.valid ? 'text-green-600' : 'text-gray-500'"
                >
                  <svg v-if="rule.valid" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                  </svg>
                  <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01"></path>
                  </svg>
                  {{ rule.description }}
                </li>
              </ul>
            </div>
          </div>

          <!-- Confirm Password -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">
              Confirmer le nouveau mot de passe
            </label>
            <div class="relative">
              <input
                :type="showConfirmPassword ? 'text' : 'password'"
                v-model="form.new_password_confirmation"
                class="w-full px-4 py-3 rounded-xl border-2 transition-all duration-200 pr-12"
                :class="form.new_password_confirmation ? (passwordsMatch ? 'border-green-400 focus:border-green-500 focus:ring-green-200' : 'border-red-400 focus:border-red-500 focus:ring-red-200') : 'border-gray-200 focus:border-moov-orange focus:ring-moov-orange/20'"
                placeholder="Confirmez votre nouveau mot de passe"
                required
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600"
              >
                <svg v-if="showConfirmPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                </svg>
                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
              </button>
            </div>
            <p v-if="form.new_password_confirmation && !passwordsMatch" class="mt-1 text-sm text-red-600">
              Les mots de passe ne correspondent pas
            </p>
          </div>

          <!-- Error Message -->
          <div v-if="errorMessage" class="p-4 rounded-xl bg-red-50 border border-red-200">
            <p class="text-sm text-red-700">{{ errorMessage }}</p>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="loading || !canSubmit"
            class="w-full px-6 py-4 rounded-xl bg-gradient-to-r from-moov-orange to-moov-orange-dark text-white font-bold text-lg hover:shadow-xl hover:shadow-moov-orange/40 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
          >
            <span v-if="loading" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></span>
            {{ loading ? 'Modification en cours...' : 'Changer le mot de passe' }}
          </button>

          <!-- Skip Button (only if not first login) -->
          <button
            v-if="!isFirstLogin"
            type="button"
            @click="goBack"
            class="w-full px-6 py-3 rounded-xl bg-gray-100 text-gray-700 font-semibold hover:bg-gray-200 transition-all duration-200"
          >
            Annuler
          </button>
        </form>
      </div>

      <!-- Security Tips -->
      <div class="mt-6 p-4 rounded-xl bg-blue-50 border border-blue-200">
        <h3 class="text-sm font-semibold text-blue-800 mb-2 flex items-center gap-2">
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          Conseils de sécurité
        </h3>
        <ul class="text-xs text-blue-700 space-y-1">
          <li>• N'utilisez jamais le même mot de passe que sur d'autres sites</li>
          <li>• Évitez les informations personnelles (nom, date de naissance...)</li>
          <li>• Utilisez un gestionnaire de mots de passe si possible</li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';

const router = useRouter();
const authStore = useAuthStore();
const { toast } = useToast();

const form = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
});

const loading = ref(false);
const errorMessage = ref('');
const errors = ref({});
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

const isFirstLogin = computed(() => authStore.requiresPasswordChange);

const passwordRules = ref([
  { id: 'length', description: 'Au moins 8 caractères', valid: false, regex: /.{8,}/ },
  { id: 'lowercase', description: 'Au moins une lettre minuscule', valid: false, regex: /[a-z]/ },
  { id: 'uppercase', description: 'Au moins une lettre majuscule', valid: false, regex: /[A-Z]/ },
  { id: 'number', description: 'Au moins un chiffre', valid: false, regex: /[0-9]/ },
  { id: 'special', description: 'Au moins un caractère spécial (!@#$%^&*...)', valid: false, regex: /[!@#$%^&*(),.?":{}|<>_\-+=\[\]\\;'`~]/ },
]);

const passwordStrength = ref({
  score: 0,
  label: 'Très faible',
});

const passwordsMatch = computed(() => {
  return form.value.new_password === form.value.new_password_confirmation && form.value.new_password_confirmation.length > 0;
});

const canSubmit = computed(() => {
  return passwordStrength.value.score >= 5 && passwordsMatch.value && form.value.current_password.length > 0;
});

const strengthBarClass = computed(() => {
  const score = passwordStrength.value.score;
  if (score <= 1) return 'bg-red-500';
  if (score <= 2) return 'bg-orange-500';
  if (score <= 3) return 'bg-yellow-500';
  if (score <= 4) return 'bg-lime-500';
  return 'bg-green-500';
});

const strengthTextClass = computed(() => {
  const score = passwordStrength.value.score;
  if (score <= 1) return 'text-red-600';
  if (score <= 2) return 'text-orange-600';
  if (score <= 3) return 'text-yellow-600';
  if (score <= 4) return 'text-lime-600';
  return 'text-green-600';
});

const checkPasswordStrength = () => {
  const password = form.value.new_password;
  let score = 0;
  
  passwordRules.value.forEach(rule => {
    rule.valid = rule.regex.test(password);
    if (rule.valid) score++;
  });
  
  passwordStrength.value.score = score;
  
  if (score <= 1) passwordStrength.value.label = 'Très faible';
  else if (score <= 2) passwordStrength.value.label = 'Faible';
  else if (score <= 3) passwordStrength.value.label = 'Moyen';
  else if (score <= 4) passwordStrength.value.label = 'Fort';
  else passwordStrength.value.label = 'Très fort';
};

const handleSubmit = async () => {
  if (!canSubmit.value) return;
  
  loading.value = true;
  errorMessage.value = '';
  errors.value = {};
  
  try {
    await authStore.changePassword({
      current_password: form.value.current_password,
      new_password: form.value.new_password,
      new_password_confirmation: form.value.new_password_confirmation,
    });
    
    toast.success('Mot de passe modifié avec succès !');
    router.push('/');
  } catch (error) {
    console.error('Change password error:', error);
    
    if (error.response?.data?.errors) {
      errors.value = {};
      const backendErrors = error.response.data.errors;
      for (const key in backendErrors) {
        errors.value[key] = backendErrors[key][0];
      }
    } else if (error.response?.data?.message) {
      errorMessage.value = error.response.data.message;
    } else {
      errorMessage.value = 'Une erreur est survenue. Veuillez réessayer.';
    }
  } finally {
    loading.value = false;
  }
};

const goBack = () => {
  router.back();
};

onMounted(() => {
  // Si l'utilisateur n'est pas connecté, rediriger vers login
  if (!authStore.isAuthenticated) {
    router.push('/login');
  }
});
</script>
