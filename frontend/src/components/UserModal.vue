<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center z-50 p-4"
  >
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-2xl w-full max-h-[90vh] overflow-y-auto" @click.stop>
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900">
          {{ user ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' }}
        </h2>
        <button
          @click="$emit('close')"
          class="w-10 h-10 rounded-xl bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-all"
        >
          <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <form @submit.prevent="handleSubmit" class="space-y-6">
        <!-- Name -->
        <FormInput
          v-model="formData.name"
          label="Nom complet"
          type="text"
          placeholder="Ex: Jean Dupont"
          required
        />

        <!-- Email -->
        <FormInput
          v-model="formData.email"
          label="Email"
          type="email"
          placeholder="email@exemple.com"
          required
        />

        <!-- Phone -->
        <FormInput
          v-model="formData.phone"
          label="Téléphone"
          type="tel"
          placeholder="+228 90 00 00 00"
        />

        <!-- Role -->
        <FormSelect
          v-model="formData.role"
          label="Rôle"
          placeholder="Sélectionnez un rôle"
          :options="roleOptions"
          option-label="label"
          option-value="value"
          required
          :searchable="false"
        />

        <!-- Organization -->
        <FormSelect
          v-model="formData.organization_id"
          label="Organisation"
          :placeholder="formData.role === 'admin' ? 'Non applicable' : 'Sélectionnez une organisation'"
          :options="organizationOptions"
          option-label="name"
          option-value="id"
          :disabled="formData.role === 'admin'"
          :required="formData.role !== 'admin'"
          :help-text="getRoleHelpText(formData.role)"
        />

        <!-- Password (only for new users) -->
        <FormInput
          v-if="!user"
          v-model="formData.password"
          label="Mot de passe"
          type="password"
          placeholder="Minimum 8 caractères"
          required
        />

        <!-- Password Confirmation (only for new users) -->
        <FormInput
          v-if="!user"
          v-model="formData.password_confirmation"
          label="Confirmer le mot de passe"
          type="password"
          placeholder="Retapez le mot de passe"
          required
        />

        <!-- Active Status -->
        <div class="flex items-center gap-3 p-4 rounded-xl bg-gray-50 border border-gray-200">
          <input
            v-model="formData.is_active"
            type="checkbox"
            id="is_active"
            class="w-5 h-5 rounded text-moov-orange focus:ring-2 focus:ring-moov-orange/20"
          >
          <label for="is_active" class="text-sm font-semibold text-gray-700 cursor-pointer">
            Utilisateur actif
          </label>
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
            :disabled="submitting"
            class="px-6 py-3 rounded-xl bg-moov-orange text-white font-bold hover:shadow-xl hover:scale-105 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <span v-if="submitting" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></span>
            {{ submitting ? 'Enregistrement...' : (user ? 'Mettre à jour' : 'Créer') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import { useToast } from '../composables/useToast';
import FormInput from './FormInput.vue';
import FormSelect from './FormSelect.vue';

const { toast } = useToast();

const props = defineProps({
  user: {
    type: Object,
    default: null
  },
  organizations: {
    type: Array,
    default: () => []
  }
});

const emit = defineEmits(['close', 'save']);

const isOpen = ref(true);
const submitting = ref(false);

const roleOptions = [
  { value: 'admin', label: 'Administrateur' },
  { value: 'dealer_owner', label: 'Propriétaire Dealer' },
  { value: 'dealer_agent', label: 'Commercial Dealer' }
];

const organizationOptions = computed(() => props.organizations);

const formData = ref({
  name: '',
  email: '',
  phone: '',
  role: '',
  organization_id: '',
  password: '',
  password_confirmation: '',
  is_active: true
});

// Populate form if editing
if (props.user) {
  formData.value = {
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone || '',
    role: props.user.role,
    organization_id: props.user.organization_id || '',
    is_active: props.user.is_active ?? true
  };
}

// Auto-clear organization for admin role
watch(() => formData.value.role, (newRole) => {
  if (newRole === 'admin') {
    formData.value.organization_id = '';
  }
});

const getRoleHelpText = (role) => {
  switch (role) {
    case 'admin':
      return 'Les administrateurs ne sont pas liés à une organisation';
    case 'dealer_owner':
      return 'Le propriétaire peut voir et gérer tous les PDV de son organisation';
    case 'dealer_agent':
      return 'Le commercial ne peut voir que les PDV qu\'il a créés lui-même';
    default:
      return '';
  }
};

const handleSubmit = async () => {
  // Validate passwords match for new users
  if (!props.user && formData.value.password !== formData.value.password_confirmation) {
    toast.error('Les mots de passe ne correspondent pas');
    return;
  }

  // Validate password length
  if (!props.user && formData.value.password.length < 6) {
    toast.error('Le mot de passe doit contenir au moins 6 caractères');
    return;
  }

  submitting.value = true;
  try {
    const dataToSend = { ...formData.value };
    
    // Remove password fields if editing
    if (props.user) {
      delete dataToSend.password;
      delete dataToSend.password_confirmation;
    }

    // Remove organization_id for admins
    if (dataToSend.role === 'admin') {
      delete dataToSend.organization_id;
    }

    emit('save', dataToSend);
  } finally {
    submitting.value = false;
  }
};
</script>
