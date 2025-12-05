<template>
  <!-- Bandeau d'installation PWA -->
  <Transition name="slide-up">
    <div 
      v-if="shouldShowPrompt" 
      class="fixed bottom-0 left-0 right-0 z-50 safe-area-bottom"
    >
      <div class="bg-gradient-to-r from-moov-orange to-orange-600 text-white p-4 shadow-lg">
        <div class="max-w-4xl mx-auto">
          <div class="flex items-center gap-4">
            <!-- Icône de l'app -->
            <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center flex-shrink-0 shadow-md">
              <img src="/icon.svg" alt="Moov Universe" class="w-8 h-8" />
            </div>
            
            <div class="flex-1 min-w-0">
              <h3 class="font-bold text-sm sm:text-base truncate">Installer Moov Universe</h3>
              <p class="text-xs sm:text-sm text-white/90 truncate">Accédez rapidement à l'app depuis votre écran d'accueil</p>
            </div>
            
            <!-- Boutons -->
            <div class="flex items-center gap-2 flex-shrink-0">
              <button 
                @click="dismiss"
                class="p-2 rounded-lg hover:bg-white/20 transition-colors"
                aria-label="Fermer"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
              
              <button 
                @click="handleInstall"
                class="px-4 py-2 bg-white text-moov-orange rounded-lg font-bold text-sm hover:bg-gray-100 transition-colors shadow-md"
              >
                Installer
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Transition>

  <!-- Modal d'instructions iOS -->
  <Teleport to="body">
    <Transition name="fade">
      <div 
        v-if="showIOSInstructions" 
        class="fixed inset-0 z-[100] flex items-end justify-center bg-black/50 backdrop-blur-sm"
        @click.self="closeIOSInstructions"
      >
        <div class="bg-white w-full max-w-md rounded-t-3xl safe-area-bottom animate-slide-up">
          <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-xl font-bold text-gray-900">Installer sur iPhone/iPad</h3>
              <button 
                @click="closeIOSInstructions"
                class="p-2 rounded-full hover:bg-gray-100 transition-colors"
              >
                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>

            <!-- Instructions -->
            <div class="space-y-4">
              <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                  <span class="text-lg font-bold text-blue-600">1</span>
                </div>
                <div class="flex-1">
                  <p class="font-medium text-gray-900">Appuyez sur le bouton Partager</p>
                  <p class="text-sm text-gray-500 mt-1">En bas de l'écran Safari</p>
                  <div class="mt-2 flex items-center gap-2 text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
                    </svg>
                    <span class="text-sm font-medium">(icône de partage)</span>
                  </div>
                </div>
              </div>

              <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0">
                  <span class="text-lg font-bold text-blue-600">2</span>
                </div>
                <div class="flex-1">
                  <p class="font-medium text-gray-900">Sélectionnez "Sur l'écran d'accueil"</p>
                  <p class="text-sm text-gray-500 mt-1">Dans le menu qui s'affiche</p>
                  <div class="mt-2 flex items-center gap-2">
                    <div class="w-6 h-6 bg-gray-200 rounded flex items-center justify-center">
                      <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                      </svg>
                    </div>
                    <span class="text-sm text-gray-600 font-medium">Sur l'écran d'accueil</span>
                  </div>
                </div>
              </div>

              <div class="flex items-start gap-4">
                <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0">
                  <span class="text-lg font-bold text-green-600">3</span>
                </div>
                <div class="flex-1">
                  <p class="font-medium text-gray-900">Appuyez sur "Ajouter"</p>
                  <p class="text-sm text-gray-500 mt-1">L'app sera disponible sur votre écran d'accueil</p>
                </div>
              </div>
            </div>

            <!-- Footer -->
            <button 
              @click="closeIOSInstructions"
              class="w-full mt-6 py-3 bg-moov-orange text-white rounded-xl font-bold hover:bg-orange-600 transition-colors"
            >
              Compris !
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { usePWA } from '../composables/usePWA';

const { 
  install, 
  shouldShowPrompt, 
  showIOSInstructions, 
  closeIOSInstructions,
  dismissInstallPrompt,
  isIOS
} = usePWA();

const handleInstall = async () => {
  if (isIOS.value) {
    // Sur iOS, montrer les instructions
    showIOSInstructions.value = true;
  } else {
    // Sur Android/Chrome, lancer l'installation
    await install();
  }
};

const dismiss = () => {
  dismissInstallPrompt();
};
</script>

<style scoped>
.slide-up-enter-active,
.slide-up-leave-active {
  transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
  transform: translateY(100%);
  opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

@keyframes slide-up {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

.animate-slide-up {
  animation: slide-up 0.3s ease-out;
}

.safe-area-bottom {
  padding-bottom: env(safe-area-inset-bottom, 0);
}
</style>
