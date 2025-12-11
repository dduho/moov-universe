<template>
  <div class="min-h-screen relative">
    <!-- Animated Background -->
    <div class="fixed inset-0 bg-gradient-to-br from-orange-50 via-white to-orange-50 -z-10">
      <!-- Animated shapes -->
      <div class="absolute top-20 left-10 w-72 h-72 bg-moov-orange/10 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-pulse"></div>
      <div class="absolute top-40 right-10 w-72 h-72 bg-moov-orange-dark/10 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-pulse" style="animation-delay: 1s;"></div>
      <div class="absolute -bottom-8 left-1/2 w-72 h-72 bg-orange-200/10 rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-pulse" style="animation-delay: 2s;"></div>
      
      <!-- Floating geometric shapes -->
      <div class="absolute top-1/4 left-1/4 w-16 h-16 border-2 border-moov-orange/20 rounded-lg animate-bounce" style="animation-duration: 3s;"></div>
      <div class="absolute top-3/4 right-1/4 w-12 h-12 border-2 border-moov-orange-dark/20 rotate-45 animate-bounce" style="animation-duration: 4s; animation-delay: 1s;"></div>
      <div class="absolute top-1/2 right-1/3 w-8 h-8 bg-moov-orange/10 rounded-full animate-bounce" style="animation-duration: 5s; animation-delay: 2s;"></div>
    </div>

    <Navbar />
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Mon Profil</h1>
        <p class="text-gray-600 mt-2">Gérez vos informations personnelles et votre sécurité</p>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sidebar -->
        <div class="lg:col-span-1">
          <div class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 rounded-2xl">
            <div class="flex flex-col items-center text-center">
              <div class="w-24 h-24 rounded-full bg-gradient-to-br from-moov-orange to-moov-orange-dark flex items-center justify-center text-white text-3xl font-bold mb-4 shadow-lg">
                {{ userInitials }}
              </div>
              <h2 class="text-xl font-bold text-gray-900">{{ user?.name }}</h2>
              <p class="text-sm text-gray-600 mt-1">{{ user?.email }}</p>
              <span class="mt-3 px-3 py-1 rounded-full text-xs font-semibold" :class="getRoleBadgeClass(user?.role?.name || user?.role)">
                {{ getRoleLabel(user?.role?.name || user?.role) }}
              </span>
            </div>

            <div class="mt-6 space-y-2">
              <button
                @click="activeTab = 'info'"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all"
                :class="activeTab === 'info' ? 'bg-moov-orange text-white shadow-lg' : 'text-gray-700 hover:bg-gray-100'"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span class="font-semibold">Informations</span>
              </button>
              <button
                @click="activeTab = 'security'"
                class="w-full flex items-center gap-3 px-4 py-3 rounded-xl transition-all"
                :class="activeTab === 'security' ? 'bg-moov-orange text-white shadow-lg' : 'text-gray-700 hover:bg-gray-100'"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <span class="font-semibold">Sécurité</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Main Content -->
        <div class="lg:col-span-2">
          <!-- Informations Tab -->
          <div v-show="activeTab === 'info'" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 sm:p-8 rounded-2xl">
            <h3 class="text-xl font-bold text-gray-900 mb-6">Informations personnelles</h3>
            
            <form @submit.prevent="updateProfile" class="space-y-6">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nom complet</label>
                <input
                  type="text"
                  v-model="profileForm.name"
                  class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-moov-orange focus:ring-4 focus:ring-moov-orange/20 transition-all"
                  required
                />
                <p v-if="profileErrors.name" class="mt-1 text-sm text-red-600">{{ profileErrors.name }}</p>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                <input
                  type="email"
                  v-model="profileForm.email"
                  class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-moov-orange focus:ring-4 focus:ring-moov-orange/20 transition-all"
                  required
                />
                <p v-if="profileErrors.email" class="mt-1 text-sm text-red-600">{{ profileErrors.email }}</p>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Rôle</label>
                <input
                  type="text"
                  :value="getRoleLabel(user?.role?.name || user?.role)"
                  class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 bg-gray-50 cursor-not-allowed"
                  disabled
                />
                <p class="mt-1 text-xs text-gray-500">Le rôle ne peut être modifié que par un administrateur</p>
              </div>

              <div class="flex gap-3">
                <button
                  type="submit"
                  :disabled="profileLoading"
                  class="flex-1 px-6 py-3 rounded-xl bg-gradient-to-r from-moov-orange to-moov-orange-dark text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="profileLoading" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Enregistrement...
                  </span>
                  <span v-else>Enregistrer les modifications</span>
                </button>
              </div>
            </form>
          </div>

          <!-- Security Tab -->
          <div v-show="activeTab === 'security'" class="bg-white/90 backdrop-blur-md border border-white/50 shadow-2xl p-6 sm:p-8 rounded-2xl">
            <h3 class="text-xl font-bold text-gray-900 mb-6">Modifier le mot de passe</h3>
            
            <form @submit.prevent="updatePassword" class="space-y-6">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Mot de passe actuel</label>
                <div class="relative">
                  <input
                    :type="showCurrentPassword ? 'text' : 'password'"
                    v-model="passwordForm.current_password"
                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-moov-orange focus:ring-4 focus:ring-moov-orange/20 transition-all pr-12"
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
                <p v-if="passwordErrors.current_password" class="mt-1 text-sm text-red-600">{{ passwordErrors.current_password }}</p>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Nouveau mot de passe</label>
                <div class="relative">
                  <input
                    :type="showNewPassword ? 'text' : 'password'"
                    v-model="passwordForm.new_password"
                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-moov-orange focus:ring-4 focus:ring-moov-orange/20 transition-all pr-12"
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
                <p v-if="passwordErrors.new_password" class="mt-1 text-sm text-red-600">{{ passwordErrors.new_password }}</p>

                <!-- Password Strength Indicator -->
                <div v-if="passwordForm.new_password" class="mt-3">
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
                      <span>{{ rule.text }}</span>
                    </li>
                  </ul>
                </div>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Confirmer le nouveau mot de passe</label>
                <div class="relative">
                  <input
                    :type="showConfirmPassword ? 'text' : 'password'"
                    v-model="passwordForm.new_password_confirmation"
                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-moov-orange focus:ring-4 focus:ring-moov-orange/20 transition-all pr-12"
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
                <p v-if="passwordErrors.new_password_confirmation" class="mt-1 text-sm text-red-600">{{ passwordErrors.new_password_confirmation }}</p>
              </div>

              <div class="flex gap-3">
                <button
                  type="submit"
                  :disabled="passwordLoading"
                  class="flex-1 px-6 py-3 rounded-xl bg-gradient-to-r from-moov-orange to-moov-orange-dark text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  <span v-if="passwordLoading" class="flex items-center justify-center gap-2">
                    <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Modification...
                  </span>
                  <span v-else>Modifier le mot de passe</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useToast } from '../composables/useToast';
import Navbar from '../components/Navbar.vue';
import AuthService from '../services/AuthService';

const authStore = useAuthStore();
const { toast } = useToast();

const activeTab = ref('info');
const user = computed(() => authStore.user);

const userInitials = computed(() => {
  if (!user.value?.name) return '?';
  const names = user.value.name.split(' ');
  if (names.length >= 2) {
    return (names[0][0] + names[1][0]).toUpperCase();
  }
  return user.value.name.substring(0, 2).toUpperCase();
});

// Profile form
const profileForm = ref({
  name: '',
  email: ''
});
const profileErrors = ref({});
const profileLoading = ref(false);

// Password form
const passwordForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
});
const passwordErrors = ref({});
const passwordLoading = ref(false);
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);

// Password strength calculation
const passwordStrength = computed(() => {
  const password = passwordForm.value.new_password;
  if (!password) return { score: 0, label: '' };

  let score = 0;
  
  // Length check
  if (password.length >= 8) score++;
  if (password.length >= 12) score++;
  
  // Character variety checks
  if (/[a-z]/.test(password)) score++;
  if (/[A-Z]/.test(password)) score++;
  if (/[0-9]/.test(password)) score++;
  if (/[^A-Za-z0-9]/.test(password)) score++;

  const labels = {
    0: '',
    1: 'Très faible',
    2: 'Faible',
    3: 'Moyen',
    4: 'Fort',
    5: 'Très fort',
    6: 'Très fort'
  };

  return {
    score: Math.min(score, 5),
    label: labels[Math.min(score, 6)]
  };
});

const strengthBarClass = computed(() => {
  const score = passwordStrength.value.score;
  if (score <= 2) return 'bg-red-500';
  if (score === 3) return 'bg-yellow-500';
  if (score === 4) return 'bg-blue-500';
  return 'bg-green-500';
});

const strengthTextClass = computed(() => {
  const score = passwordStrength.value.score;
  if (score <= 2) return 'text-red-600';
  if (score === 3) return 'text-yellow-600';
  if (score === 4) return 'text-blue-600';
  return 'text-green-600';
});

const passwordRules = computed(() => {
  const password = passwordForm.value.new_password;
  return [
    {
      id: 'length',
      text: 'Au moins 8 caractères',
      valid: password.length >= 8
    },
    {
      id: 'lowercase',
      text: 'Une lettre minuscule',
      valid: /[a-z]/.test(password)
    },
    {
      id: 'uppercase',
      text: 'Une lettre majuscule',
      valid: /[A-Z]/.test(password)
    },
    {
      id: 'number',
      text: 'Un chiffre',
      valid: /[0-9]/.test(password)
    },
    {
      id: 'special',
      text: 'Un caractère spécial',
      valid: /[^A-Za-z0-9]/.test(password)
    }
  ];
});

// Initialize form with user data
onMounted(() => {
  if (user.value) {
    profileForm.value = {
      name: user.value.name || '',
      email: user.value.email || ''
    };
  }
});

const getRoleLabel = (role) => {
  const labels = {
    admin: 'Administrateur',
    commercial: 'Commercial',
    dealer: 'Dealer',
    dealer_owner: 'Propriétaire Dealer',
    dealer_agent: 'Agent Dealer',
    validator: 'Validateur'
  };
  return labels[role] || role || 'Utilisateur';
};

const getRoleBadgeClass = (role) => {
  const classes = {
    admin: 'bg-red-100 text-red-700',
    commercial: 'bg-blue-100 text-blue-700',
    dealer: 'bg-green-100 text-green-700',
    dealer_owner: 'bg-green-100 text-green-700',
    dealer_agent: 'bg-teal-100 text-teal-700',
    validator: 'bg-purple-100 text-purple-700'
  };
  return classes[role] || 'bg-gray-100 text-gray-700';
};

const updateProfile = async () => {
  try {
    profileLoading.value = true;
    profileErrors.value = {};

    const response = await AuthService.updateProfile(profileForm.value);
    
    // Update user in store
    authStore.user = { ...authStore.user, ...response.data.user };
    
    toast.success('Profil mis à jour avec succès');
  } catch (error) {
    if (error.response?.data?.errors) {
      profileErrors.value = error.response.data.errors;
    } else {
      toast.error(error.response?.data?.message || 'Erreur lors de la mise à jour du profil');
    }
  } finally {
    profileLoading.value = false;
  }
};

const updatePassword = async () => {
  try {
    passwordLoading.value = true;
    passwordErrors.value = {};

    if (passwordForm.value.new_password !== passwordForm.value.new_password_confirmation) {
      passwordErrors.value.new_password_confirmation = 'Les mots de passe ne correspondent pas';
      return;
    }

    await AuthService.updatePassword({
      current_password: passwordForm.value.current_password,
      new_password: passwordForm.value.new_password,
      new_password_confirmation: passwordForm.value.new_password_confirmation
    });
    
    toast.success('Mot de passe modifié avec succès');
    
    // Reset form
    passwordForm.value = {
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    };
  } catch (error) {
    if (error.response?.data?.errors) {
      passwordErrors.value = error.response.data.errors;
    } else {
      toast.error(error.response?.data?.message || 'Erreur lors de la modification du mot de passe');
    }
  } finally {
    passwordLoading.value = false;
  }
};
</script>


