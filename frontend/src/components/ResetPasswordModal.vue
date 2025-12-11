<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center z-50 p-4"
    @click.self="$emit('close')"
  >
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-md w-full">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900">Réinitialiser le mot de passe</h2>
        <button
          @click="$emit('close')"
          class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-all"
        >
          <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200">
        <p class="text-sm text-blue-700">
          <span class="font-bold">Utilisateur :</span> {{ user.name }}
        </p>
        <p class="text-sm text-blue-700 mt-1">
          <span class="font-bold">Email :</span> {{ user.email }}
        </p>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- New Password -->
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Nouveau mot de passe *</label>
          <input
            v-model="formData.password"
            type="password"
            required
            placeholder="Minimum 8 caractères"
            class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-moov-orange focus:ring-2 focus:ring-moov-orange/20 outline-none transition-all"
          >
        </div>

        <!-- Confirm Password -->
        <div>
          <label class="block text-sm font-bold text-gray-700 mb-2">Confirmer le mot de passe *</label>
          <input
            v-model="formData.password_confirmation"
            type="password"
            required
            placeholder="Retapez le mot de passe"
            class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:border-moov-orange focus:ring-2 focus:ring-moov-orange/20 outline-none transition-all"
          >
        </div>

        <!-- Password Strength Indicator -->
        <div v-if="formData.password" class="space-y-2">
          <div class="flex items-center justify-between text-xs">
            <span class="text-gray-600">Force du mot de passe</span>
            <span :class="passwordStrengthColor" class="font-bold">{{ passwordStrengthText }}</span>
          </div>
          <div class="w-full bg-gray-200 rounded-full h-2">
            <div
              class="h-2 rounded-full transition-all duration-300"
              :class="passwordStrengthColor"
              :style="{ width: passwordStrength + '%' }"
            ></div>
          </div>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200">
          <button
            type="button"
            @click="$emit('close')"
            class="px-6 py-3 rounded-xl bg-white border-2 border-gray-300 text-gray-700 font-bold hover:bg-gray-50 transition-all"
          >
            Annuler
          </button>
          <button
            type="submit"
            :disabled="submitting || passwordStrength < 50"
            class="px-6 py-3 rounded-xl bg-moov-orange text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <span v-if="submitting" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></span>
            {{ submitting ? 'Réinitialisation...' : 'Réinitialiser' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useToast } from '../composables/useToast';

const props = defineProps({
  user: {
    type: Object,
    required: true
  }
});

const emit = defineEmits(['close', 'reset']);
const { toast } = useToast();

const isOpen = ref(true);
const submitting = ref(false);

const formData = ref({
  password: '',
  password_confirmation: ''
});

const passwordStrength = computed(() => {
  const pwd = formData.value.password;
  if (!pwd) return 0;
  
  let strength = 0;
  
  // Length
  if (pwd.length >= 8) strength += 25;
  if (pwd.length >= 12) strength += 15;
  
  // Has lowercase
  if (/[a-z]/.test(pwd)) strength += 15;
  
  // Has uppercase
  if (/[A-Z]/.test(pwd)) strength += 15;
  
  // Has numbers
  if (/\d/.test(pwd)) strength += 15;
  
  // Has special chars
  if (/[^a-zA-Z0-9]/.test(pwd)) strength += 15;
  
  return Math.min(strength, 100);
});

const passwordStrengthText = computed(() => {
  const strength = passwordStrength.value;
  if (strength < 30) return 'Très faible';
  if (strength < 50) return 'Faible';
  if (strength < 70) return 'Moyen';
  if (strength < 90) return 'Fort';
  return 'Très fort';
});

const passwordStrengthColor = computed(() => {
  const strength = passwordStrength.value;
  if (strength < 30) return 'bg-red-500 text-red-700';
  if (strength < 50) return 'bg-orange-500 text-orange-700';
  if (strength < 70) return 'bg-yellow-500 text-yellow-700';
  if (strength < 90) return 'bg-blue-500 text-blue-700';
  return 'bg-green-500 text-green-700';
});

const handleSubmit = async () => {
  // Validate passwords match
  if (formData.value.password !== formData.value.password_confirmation) {
    toast.warning('Les mots de passe ne correspondent pas');
    return;
  }

  // Validate password length
  if (formData.value.password.length < 8) {
    toast.warning('Le mot de passe doit contenir au moins 8 caractères');
    return;
  }

  submitting.value = true;
  try {
    emit('reset', {
      userId: props.user.id,
      password: formData.value.password
    });
  } finally {
    submitting.value = false;
  }
};
</script>


