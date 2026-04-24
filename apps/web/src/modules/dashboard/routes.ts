import type { RouteRecordRaw } from 'vue-router'

import DashboardPage from './pages/DashboardPage.vue'

export const dashboardRoutes: RouteRecordRaw[] = [
  {
    path: '/app/dashboard',
    name: 'dashboard',
    component: DashboardPage,
    meta: {
      requiresAuth: true,
      title: 'Dashboard',
    },
  },
]
