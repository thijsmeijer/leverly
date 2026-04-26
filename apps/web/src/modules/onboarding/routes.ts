import type { RouteRecordRaw } from 'vue-router'

import OnboardingPage from './pages/OnboardingPage.vue'

export const onboardingRoutes: RouteRecordRaw[] = [
  {
    path: '/onboarding',
    name: 'onboarding',
    component: OnboardingPage,
    meta: {
      allowIncompleteOnboarding: true,
      requiresAuth: true,
      title: 'Onboarding',
    },
  },
]
