import type { NavigationGuardReturn, RouteLocationNormalized } from 'vue-router'

import { useOnboardingStore } from '@/modules/onboarding'

export interface OnboardingGuardOptions {
  readonly dashboardRouteName?: string
  readonly onboardingRouteName?: string
  readonly useOnboardingStore?: () => Pick<ReturnType<typeof useOnboardingStore>, 'ensureLoaded' | 'isComplete'>
}

export function createOnboardingGuard(options: OnboardingGuardOptions = {}) {
  const onboardingRouteName = options.onboardingRouteName ?? 'onboarding'
  const dashboardRouteName = options.dashboardRouteName ?? 'dashboard'
  const onboardingStoreFactory = options.useOnboardingStore ?? useOnboardingStore

  return async (to: RouteLocationNormalized): Promise<NavigationGuardReturn> => {
    if (!to.meta.requiresAuth) {
      return true
    }

    const onboarding = onboardingStoreFactory()
    await onboarding.ensureLoaded()

    if (to.meta.allowIncompleteOnboarding) {
      if (onboarding.isComplete && to.name === onboardingRouteName) {
        return { name: dashboardRouteName }
      }

      return true
    }

    if (onboarding.isComplete) {
      return true
    }

    return {
      name: onboardingRouteName,
      query: {
        redirect: to.fullPath,
      },
    }
  }
}
