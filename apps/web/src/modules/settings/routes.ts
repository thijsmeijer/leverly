import type { RouteRecordRaw } from 'vue-router'

import SettingsPage from './pages/SettingsPage.vue'

export const settingsRoutes: RouteRecordRaw[] = [
  {
    path: 'settings',
    redirect: { name: 'settings-profile' },
  },
  {
    path: 'settings/profile',
    name: 'settings-profile',
    component: SettingsPage,
    meta: {
      requiresAuth: true,
      section: 'Settings',
      title: 'Profile settings',
    },
  },
]
