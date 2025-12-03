import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('../views/Login.vue'),
    meta: { requiresAuth: false },
  },
  {
    path: '/',
    redirect: '/dashboard',
  },
  {
    path: '/dashboard',
    name: 'Dashboard',
    component: () => import('../views/Dashboard.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/pdv/create',
    name: 'CreatePdv',
    component: () => import('../views/PointOfSaleForm.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/pdv/list',
    name: 'PdvList',
    component: () => import('../views/PointOfSaleList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/pdv/:id',
    name: 'PdvDetail',
    component: () => import('../views/PointOfSaleDetail.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/map',
    name: 'MapView',
    component: () => import('../views/MapView.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/validation',
    name: 'ValidationQueue',
    component: () => import('../views/ValidationQueue.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/dealers',
    name: 'DealerList',
    component: () => import('../views/DealerList.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/dealers/:id',
    name: 'DealerDetail',
    component: () => import('../views/DealerDetail.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/statistics',
    name: 'Statistics',
    component: () => import('../views/Statistics.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/users',
    name: 'UserList',
    component: () => import('../views/UserList.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/notifications',
    name: 'NotificationList',
    component: () => import('../views/NotificationList.vue'),
    meta: { requiresAuth: true },
  },
  {
    path: '/activity-logs',
    name: 'ActivityLogList',
    component: () => import('../views/ActivityLogList.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
  {
    path: '/settings',
    name: 'Settings',
    component: () => import('../views/Settings.vue'),
    meta: { requiresAuth: true, requiresAdmin: true },
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next({ name: 'Login' });
  } else if (to.meta.requiresAdmin && !authStore.isAdmin) {
    next({ name: 'Dashboard' });
  } else if (to.name === 'Login' && authStore.isAuthenticated) {
    next({ name: 'Dashboard' });
  } else {
    next();
  }
});

export default router;
