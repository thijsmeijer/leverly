import { describe, expect, it } from 'vitest'
import { createMemoryHistory } from 'vue-router'

import { appNavigationItems, mobileNavigationItems } from '@/app/shell/appNavigation'
import { createLeverlyRouter } from './index'

describe('SPA route shell', () => {
  it('registers every planned shell route', () => {
    const router = createLeverlyRouter(createMemoryHistory())
    const paths = router.getRoutes().map((route) => route.path)

    expect(paths).toEqual(
      expect.arrayContaining([
        '/login',
        '/register',
        '/onboarding',
        '/app/dashboard',
        '/app/today',
        '/app/workouts',
        '/app/workouts/:id',
        '/app/programs',
        '/app/programs/:id',
        '/app/exercises',
        '/app/exercises/:slug',
        '/app/progressions',
        '/app/progressions/:familySlug',
        '/app/insights',
        '/app/settings/profile',
        '/app/settings/equipment',
        '/app/settings/export',
      ]),
    )
  })

  it('marks app routes as protected shell routes with titles', () => {
    const router = createLeverlyRouter(createMemoryHistory())
    const shellRoutes = router.getRoutes().filter((route) => route.path.startsWith('/app/') && route.name !== undefined)

    expect(shellRoutes.length).toBeGreaterThan(8)
    expect(shellRoutes.every((route) => route.meta.requiresAuth === true)).toBe(true)
    expect(shellRoutes.every((route) => typeof route.meta.title === 'string')).toBe(true)
  })

  it('protects onboarding while allowing incomplete athletes to finish it', () => {
    const router = createLeverlyRouter(createMemoryHistory())
    const onboardingRoute = router.getRoutes().find((route) => route.name === 'onboarding')

    expect(onboardingRoute?.meta.requiresAuth).toBe(true)
    expect(onboardingRoute?.meta.allowIncompleteOnboarding).toBe(true)
  })

  it('keeps mobile navigation compact and route-backed', () => {
    const router = createLeverlyRouter(createMemoryHistory())
    const routeNames = new Set(router.getRoutes().map((route) => String(route.name)))

    expect(appNavigationItems.length).toBeGreaterThan(mobileNavigationItems.length)
    expect(mobileNavigationItems).toHaveLength(5)
    expect(mobileNavigationItems.every((item) => routeNames.has(item.routeName))).toBe(true)
  })
})
