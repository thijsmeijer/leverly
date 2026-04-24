import type { NavigationGuardReturn, RouteLocationNormalized } from 'vue-router'

import { useSessionStore } from '@/app/stores/sessionStore'

export interface SessionGuardOptions {
  readonly loginRouteName?: string
  readonly useSessionStore?: () => Pick<ReturnType<typeof useSessionStore>, 'validateSession'>
}

export function createSessionGuard(options: SessionGuardOptions = {}) {
  const loginRouteName = options.loginRouteName ?? 'login'
  const sessionStoreFactory = options.useSessionStore ?? useSessionStore

  return async (to: RouteLocationNormalized): Promise<NavigationGuardReturn> => {
    if (!to.meta.requiresAuth) {
      return true
    }

    const session = sessionStoreFactory()
    const isValid = await session.validateSession()

    if (isValid) {
      return true
    }

    return {
      name: loginRouteName,
      query: {
        redirect: to.fullPath,
      },
    }
  }
}
