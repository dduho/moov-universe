<template>
  <nav class="glass-strong sticky top-0 z-50 border-b border-white/20">
    <div class="w-full px-4 sm:px-6">
      <div class="flex justify-between items-center h-16">
        <div class="flex items-center gap-4 lg:gap-8 flex-1">
          <!-- Logo -->
          <div class="shrink-0 flex items-center gap-2 sm:gap-3">
            <div class="inline-flex items-center justify-center w-8 h-8 sm:w-10 sm:h-10">
              <img src="/icon.svg" alt="Moov Logo" class="w-8 h-8 sm:w-10 sm:h-10" />
            </div>
            <div class="hidden sm:block whitespace-nowrap">
              <h1 class="text-lg sm:text-xl font-bold bg-gradient-to-r from-moov-orange via-moov-orange-dark to-moov-orange bg-clip-text text-transparent leading-tight">
                Moov Money
              </h1>
              <p class="text-xs font-semibold text-gray-600 leading-tight">Universe</p>
            </div>
          </div>

          <!-- Mobile Menu Button -->
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="lg:hidden ml-auto p-2 rounded-xl text-gray-700 hover:bg-white/30 transition-all"
          >
            <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>

          <!-- Navigation Links (Desktop) -->
          <div class="hidden lg:flex items-center gap-2">
            <!-- Main Navigation -->
            <router-link
              v-for="item in mainNavigation"
              :key="item.name"
              :to="item.path"
              class="px-3 py-2 rounded-xl text-sm font-bold transition-all duration-200 whitespace-nowrap"
              :class="isActive(item.path) 
                ? 'bg-gradient-to-r from-moov-orange to-moov-orange-dark text-white shadow-lg' 
                : 'text-gray-700 hover:bg-white/30 hover:text-gray-900'"
            >
              <div class="flex items-center gap-2">
                <component :is="item.icon" class="w-5 h-5 shrink-0" />
                <span>{{ item.name }}</span>
              </div>
            </router-link>

            <!-- Admin Dropdown -->
            <div v-if="authStore.isAdmin" class="relative" @click.stop>
              <button
                @click="showAdminMenu = !showAdminMenu"
                class="px-3 py-2 rounded-xl text-sm font-bold transition-all duration-200 whitespace-nowrap flex items-center gap-2"
                :class="isAdminRouteActive 
                  ? 'bg-gradient-to-r from-moov-orange to-moov-orange-dark text-white shadow-lg' 
                  : 'text-gray-700 hover:bg-white/30 hover:text-gray-900'"
              >
                <component :is="SettingsIcon" class="w-5 h-5 shrink-0" />
                <span>Administration</span>
                <svg
                  class="w-4 h-4 transition-transform"
                  :class="{ 'rotate-180': showAdminMenu }"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>

              <!-- Dropdown Menu -->
              <div
                v-if="showAdminMenu"
                class="absolute top-full mt-2 w-56 bg-white/95 backdrop-blur-md rounded-xl shadow-2xl border border-white/40 overflow-hidden z-50"
              >
                <router-link
                  v-for="item in adminNavigation"
                  :key="item.name"
                  :to="item.path"
                  @click="showAdminMenu = false"
                  class="flex items-center gap-3 px-4 py-3 text-sm font-semibold transition-all border-b border-gray-100 last:border-0"
                  :class="isActive(item.path)
                    ? 'bg-moov-orange text-white'
                    : 'text-gray-700 hover:bg-moov-orange/10 hover:text-moov-orange'"
                >
                  <component :is="item.icon" class="w-5 h-5 shrink-0" />
                  <span>{{ item.name }}</span>
                </router-link>
              </div>
            </div>
          </div>
        </div>

        <!-- User Menu (Desktop) -->
        <div class="hidden lg:flex items-center gap-2 xl:gap-4">
          <!-- Global Search Button -->
          <button
            @click="openGlobalSearch"
            class="flex items-center gap-2 px-3 xl:px-4 py-2 rounded-xl bg-white/30 border border-white/40 text-gray-700 hover:bg-white/50 transition-all"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <span class="hidden xl:inline text-sm font-semibold">Rechercher</span>
            <kbd class="hidden 2xl:inline-block px-2 py-0.5 rounded bg-gray-100 text-xs font-mono text-gray-600">Ctrl+K</kbd>
          </button>

          <!-- Notification Badge -->
          <NotificationBadge />
          
          <div class="hidden xl:block text-right px-4 py-2 rounded-xl bg-white/30 border border-white/40">
            <p class="text-sm font-bold text-gray-900 whitespace-nowrap">{{ authStore.user?.name }}</p>
            <p class="text-xs font-semibold text-gray-600 whitespace-nowrap">{{ authStore.user?.role?.display_name || 'Utilisateur' }}</p>
          </div>
          <button
            @click="handleLogout"
            class="px-3 xl:px-4 py-2 rounded-xl bg-gradient-to-r from-moov-orange via-moov-orange-dark to-moov-orange text-white text-sm font-bold hover:shadow-xl hover:shadow-moov-orange/40 hover:scale-105 transition-all duration-200 flex items-center gap-2 cursor-pointer"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            <span class="hidden sm:inline">Déconnexion</span>
          </button>
        </div>

        <!-- Mobile User Menu -->
        <div class="lg:hidden flex items-center gap-2">
          <NotificationBadge />
        </div>
      </div>

      <!-- Mobile Menu -->
      <div
        v-if="mobileMenuOpen"
        class="lg:hidden border-t border-white/20 py-4 space-y-2"
      >
        <!-- Mobile Main Navigation -->
        <router-link
          v-for="item in mainNavigation"
          :key="item.name"
          :to="item.path"
          @click="mobileMenuOpen = false"
          class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all"
          :class="isActive(item.path) 
            ? 'bg-gradient-to-r from-moov-orange to-moov-orange-dark text-white shadow-lg' 
            : 'text-gray-700 hover:bg-white/30'"
        >
          <component :is="item.icon" class="w-5 h-5 shrink-0" />
          <span>{{ item.name }}</span>
        </router-link>

        <!-- Mobile Admin Navigation -->
        <div v-if="authStore.isAdmin" class="pt-2 border-t border-white/20 mt-2">
          <p class="px-4 py-2 text-xs font-bold text-gray-500 uppercase tracking-wider">Administration</p>
          <router-link
            v-for="item in adminNavigation"
            :key="item.name"
            :to="item.path"
            @click="mobileMenuOpen = false"
            class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold transition-all"
            :class="isActive(item.path)
              ? 'bg-moov-orange text-white'
              : 'text-gray-700 hover:bg-moov-orange/10 hover:text-moov-orange'"
          >
            <component :is="item.icon" class="w-5 h-5 shrink-0" />
            <span>{{ item.name }}</span>
          </router-link>
        </div>

        <!-- Mobile User Info & Actions -->
        <div class="pt-4 border-t border-white/20 mt-4 space-y-3">
          <div class="px-4 py-3 rounded-xl bg-white/30 border border-white/40">
            <p class="text-sm font-bold text-gray-900">{{ authStore.user?.name }}</p>
            <p class="text-xs font-semibold text-gray-600">{{ authStore.user?.role?.display_name || 'Utilisateur' }}</p>
          </div>
          
          <button
            @click="openGlobalSearch"
            class="w-full flex items-center justify-center gap-2 px-4 py-3 rounded-xl bg-white/30 border border-white/40 text-gray-700 hover:bg-white/50 transition-all"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <span class="text-sm font-semibold">Recherche globale</span>
          </button>

          <button
            @click="handleLogout"
            class="w-full px-4 py-3 rounded-xl bg-gradient-to-r from-moov-orange via-moov-orange-dark to-moov-orange text-white text-sm font-bold hover:shadow-xl hover:shadow-moov-orange/40 transition-all duration-200 flex items-center justify-center gap-2"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
            </svg>
            <span>Déconnexion</span>
          </button>
        </div>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { ref, computed, h, onMounted, onUnmounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import NotificationBadge from './NotificationBadge.vue';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const showAdminMenu = ref(false);
const mobileMenuOpen = ref(false);

// Global search
const openGlobalSearch = () => {
  // Create a custom event to open search from any component
  window.dispatchEvent(new CustomEvent('open-global-search'));
};

// Keyboard shortcut
const handleKeydown = (event) => {
  if ((event.ctrlKey || event.metaKey) && event.key === 'k') {
    event.preventDefault();
    openGlobalSearch();
  }
};

// Close admin menu when clicking outside
const closeAdminMenu = (event) => {
  if (showAdminMenu.value && !event.target.closest('.relative')) {
    showAdminMenu.value = false;
  }
};

onMounted(() => {
  document.addEventListener('keydown', handleKeydown);
  document.addEventListener('click', closeAdminMenu);
});

onUnmounted(() => {
  document.removeEventListener('keydown', handleKeydown);
  document.removeEventListener('click', closeAdminMenu);
});

// Icon components using h() render function
const DashboardIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6' })
    ]);
  }
};

const ListIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M4 6h16M4 10h16M4 14h16M4 18h16' })
    ]);
  }
};

const MapIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7' })
    ]);
  }
};

const CheckIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' })
    ]);
  }
};

const OrganizationIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4' })
    ]);
  }
};

const UserIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z' })
    ]);
  }
};

const ActivityLogIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01' })
    ]);
  }
};

const SettingsIcon = {
  render() {
    return h('svg', { fill: 'none', stroke: 'currentColor', viewBox: '0 0 24 24' }, [
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z' }), 
      h('path', { 'stroke-linecap': 'round', 'stroke-linejoin': 'round', 'stroke-width': '2', d: 'M15 12a3 3 0 11-6 0 3 3 0 016 0z' })
    ]);
  }
};

// Main navigation (always visible)
const mainNavigation = [
  {
    name: 'Tableau de bord',
    path: '/dashboard',
    icon: DashboardIcon,
  },
  {
    name: 'Liste PDV',
    path: '/pdv/list',
    icon: ListIcon,
  },
  {
    name: 'Carte',
    path: '/map',
    icon: MapIcon,
  },
];

// Admin navigation (in dropdown)
const adminNavigation = [
  {
    name: 'Validation',
    path: '/validation',
    icon: CheckIcon,
  },
  {
    name: 'Dealers',
    path: '/dealers',
    icon: OrganizationIcon,
  },
  {
    name: 'Utilisateurs',
    path: '/users',
    icon: UserIcon,
  },
  {
    name: 'Logs d\'activité',
    path: '/activity-logs',
    icon: ActivityLogIcon,
  },
  {
    name: 'Paramètres',
    path: '/settings',
    icon: SettingsIcon,
  },
];

// Check if any admin route is active
const isAdminRouteActive = computed(() => {
  return adminNavigation.some(item => route.path.startsWith(item.path));
});

const isActive = (path) => {
  return route.path.startsWith(path);
};

const handleLogout = async () => {
  await authStore.logout();
  router.push({ name: 'Login' });
};
</script>
