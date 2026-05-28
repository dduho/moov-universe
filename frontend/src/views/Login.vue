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
            <span class="bg-clip-text font-oughter bg-gradient-to-r from-moov-orange via-moov-orange-dark to-moov-orange bg-clip-text text-transparent leading-tight">
              Moov Money
            </span>
          </h2>
          <h3 class="text-2xl font-oughter text-gray-800 mb-2">
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

        <!-- ── STEP 1 : Credentials ── -->
        <form v-if="step === 'credentials'" class="space-y-5" @submit.prevent="handleLogin">
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

        <!-- ── STEP 2 : OTP verification ── -->
        <form v-else-if="step === 'otp'" class="space-y-5" @submit.prevent="handleVerifyOtp">
          <!-- Info banner -->
          <div class="rounded-xl bg-orange-50 border border-orange-200 p-4 text-center">
            <div class="flex justify-center mb-2">
              <svg class="w-8 h-8 text-moov-orange" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
              </svg>
            </div>
            <p class="text-sm font-semibold text-gray-800">Code envoyé par SMS</p>
            <p class="text-xs text-gray-500 mt-1">
              Numéro se terminant par <span class="font-bold text-moov-orange">{{ phoneHint }}</span>
            </p>
            <p class="text-xs text-gray-400 mt-1">Valable 5 minutes</p>
          </div>

          <!-- 6-digit OTP input -->
          <div>
            <label class="block text-sm font-semibold text-gray-700 mb-3 text-center">
              Entrez votre code à 6 chiffres
            </label>
            <div class="flex justify-center gap-2">
              <input
                v-for="(_, i) in otpDigits"
                :key="i"
                :ref="el => { if (el) otpRefs[i] = el }"
                v-model="otpDigits[i]"
                type="text"
                inputmode="numeric"
                maxlength="1"
                class="w-11 h-14 text-center text-xl font-bold border-2 rounded-xl focus:outline-none focus:border-moov-orange transition-colors"
                :class="otpDigits[i] ? 'border-moov-orange bg-orange-50' : 'border-gray-300'"
                @input="onOtpInput(i, $event)"
                @keydown="onOtpKeydown(i, $event)"
                @paste.prevent="onOtpPaste($event)"
              />
            </div>
          </div>

          <!-- Error -->
          <div v-if="error" class="rounded-xl bg-red-50 border-2 border-red-200 p-3 animate-shake">
            <p class="text-sm text-red-800 font-semibold text-center">{{ error }}</p>
          </div>

          <!-- Verify button -->
          <button
            type="submit"
            :disabled="loading || otpCode.length < 6"
            class="group relative w-full flex justify-center items-center gap-3 py-4 px-6 border-0 text-base font-bold rounded-xl text-white bg-gradient-to-r from-moov-orange via-moov-orange-dark to-moov-orange hover:shadow-2xl hover:shadow-moov-orange/50 hover:scale-[1.02] focus:outline-none focus:ring-4 focus:ring-moov-orange/30 disabled:opacity-50 disabled:hover:scale-100 disabled:cursor-not-allowed transition-all duration-300"
          >
            <span v-if="!loading">Vérifier le code</span>
            <span v-else class="flex items-center gap-2">
              <svg class="animate-spin h-5 w-5" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Vérification...
            </span>
          </button>

          <!-- Resend + back -->
          <div class="flex items-center justify-between text-sm pt-1">
            <button
              type="button"
              class="text-gray-500 hover:text-gray-700 underline"
              @click="backToCredentials"
            >
              ← Retour
            </button>
            <button
              type="button"
              :disabled="resendCooldown > 0"
              class="text-moov-orange font-semibold disabled:opacity-40 disabled:cursor-not-allowed"
              @click="handleResend"
            >
              {{ resendCooldown > 0 ? `Renvoyer (${resendCooldown}s)` : 'Renvoyer le code' }}
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
import { ref, computed, nextTick, h } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import AuthService from '../services/AuthService';
import FormInput from '../components/FormInput.vue';

const router = useRouter();
const authStore = useAuthStore();

// ── Credentials step ──────────────────────────────────────────────
const form = ref({ email: '', password: '' });

// ── OTP step ──────────────────────────────────────────────────────
const step        = ref('credentials'); // 'credentials' | 'otp'
const otpToken    = ref('');
const phoneHint   = ref('');
const otpDigits   = ref(['', '', '', '', '', '']);
const otpRefs     = ref([]);
const resendCooldown = ref(0);
let   cooldownTimer  = null;

const otpCode = computed(() => otpDigits.value.join(''));

// ── Shared ────────────────────────────────────────────────────────
const loading = ref(false);
const error   = ref('');
const currentYear = new Date().getFullYear();

// ── Credentials handler ───────────────────────────────────────────
const handleLogin = async () => {
  loading.value = true;
  error.value   = '';

  try {
    const data = await authStore.login(form.value);

    if (data.otp_required) {
      otpToken.value  = data.otp_token;
      phoneHint.value = data.phone_hint;
      step.value      = 'otp';
      startResendCooldown();
      await nextTick();
      otpRefs.value[0]?.focus();
      return;
    }

    router.push({ name: 'Dashboard' });
  } catch (err) {
    error.value = err.response?.data?.message || 'Erreur de connexion';
  } finally {
    loading.value = false;
  }
};

// ── OTP handler ───────────────────────────────────────────────────
const handleVerifyOtp = async () => {
  if (otpCode.value.length < 6) return;

  loading.value = true;
  error.value   = '';

  try {
    await authStore.verifyOtp({ otpToken: otpToken.value, code: otpCode.value });
    router.push({ name: 'Dashboard' });
  } catch (err) {
    error.value = err.response?.data?.errors?.code?.[0]
      || err.response?.data?.message
      || 'Code incorrect';
    // Clear digits on error
    otpDigits.value = ['', '', '', '', '', ''];
    await nextTick();
    otpRefs.value[0]?.focus();
  } finally {
    loading.value = false;
  }
};

const handleResend = async () => {
  if (resendCooldown.value > 0) return;
  try {
    const data = await AuthService.resendOtp(otpToken.value);
    otpToken.value = data.otp_token;
    otpDigits.value = ['', '', '', '', '', ''];
    error.value = '';
    startResendCooldown();
    await nextTick();
    otpRefs.value[0]?.focus();
  } catch (err) {
    error.value = err.response?.data?.message || 'Impossible de renvoyer le code';
  }
};

const backToCredentials = () => {
  step.value      = 'credentials';
  otpToken.value  = '';
  otpDigits.value = ['', '', '', '', '', ''];
  error.value     = '';
  clearInterval(cooldownTimer);
  resendCooldown.value = 0;
};

// ── OTP input helpers ─────────────────────────────────────────────
const onOtpInput = (index, event) => {
  const val = event.target.value.replace(/\D/g, '');
  otpDigits.value[index] = val.slice(-1);
  if (val && index < 5) {
    otpRefs.value[index + 1]?.focus();
  }
};

const onOtpKeydown = (index, event) => {
  if (event.key === 'Backspace' && !otpDigits.value[index] && index > 0) {
    otpRefs.value[index - 1]?.focus();
  }
};

const onOtpPaste = async (event) => {
  const text = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
  if (!text) return;
  const chars = text.slice(0, 6).split('');
  chars.forEach((c, i) => { otpDigits.value[i] = c; });
  await nextTick();
  const nextEmpty = chars.length < 6 ? chars.length : 5;
  otpRefs.value[nextEmpty]?.focus();
};

// ── Cooldown timer ────────────────────────────────────────────────
const startResendCooldown = () => {
  resendCooldown.value = 30;
  clearInterval(cooldownTimer);
  cooldownTimer = setInterval(() => {
    resendCooldown.value--;
    if (resendCooldown.value <= 0) clearInterval(cooldownTimer);
  }, 1000);
};

// ── Icon components ───────────────────────────────────────────────
const EmailIcon = {
  render() {
    return h('svg', { class: 'h-5 w-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207' })
    ]);
  }
};

const LockIcon = {
  render() {
    return h('svg', { class: 'h-5 w-5', fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z' })
    ]);
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


