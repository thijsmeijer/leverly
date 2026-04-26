import type { RouteLocationNormalized } from 'vue-router'
import { describe, expect, it, vi } from 'vitest'

import { createOnboardingGuard } from './onboardingGuard'

describe('createOnboardingGuard', () => {
  it('allows public routes without loading onboarding state', async () => {
    const ensureLoaded = vi.fn()
    const guard = createOnboardingGuard({
      useOnboardingStore: () => ({ ensureLoaded, isComplete: false }),
    })

    await expect(guard(route({ requiresAuth: false }))).resolves.toBe(true)

    expect(ensureLoaded).not.toHaveBeenCalled()
  })

  it('allows protected app routes when onboarding is complete', async () => {
    const ensureLoaded = vi.fn().mockResolvedValue(undefined)
    const guard = createOnboardingGuard({
      useOnboardingStore: () => ({ ensureLoaded, isComplete: true }),
    })

    await expect(guard(route({ fullPath: '/app/dashboard', requiresAuth: true }))).resolves.toBe(true)

    expect(ensureLoaded).toHaveBeenCalledOnce()
  })

  it('redirects incomplete users away from protected app routes', async () => {
    const ensureLoaded = vi.fn().mockResolvedValue(undefined)
    const guard = createOnboardingGuard({
      useOnboardingStore: () => ({ ensureLoaded, isComplete: false }),
    })

    await expect(guard(route({ fullPath: '/app/programs', requiresAuth: true }))).resolves.toEqual({
      name: 'onboarding',
      query: {
        redirect: '/app/programs',
      },
    })
  })

  it('allows incomplete users to use the onboarding route', async () => {
    const ensureLoaded = vi.fn().mockResolvedValue(undefined)
    const guard = createOnboardingGuard({
      useOnboardingStore: () => ({ ensureLoaded, isComplete: false }),
    })

    await expect(
      guard(route({ allowIncompleteOnboarding: true, name: 'onboarding', requiresAuth: true })),
    ).resolves.toBe(true)
  })

  it('redirects completed users away from onboarding', async () => {
    const ensureLoaded = vi.fn().mockResolvedValue(undefined)
    const guard = createOnboardingGuard({
      useOnboardingStore: () => ({ ensureLoaded, isComplete: true }),
    })

    await expect(
      guard(route({ allowIncompleteOnboarding: true, name: 'onboarding', requiresAuth: true })),
    ).resolves.toEqual({ name: 'dashboard' })
  })
})

function route(options: {
  allowIncompleteOnboarding?: boolean
  fullPath?: string
  name?: string
  requiresAuth?: boolean
}): RouteLocationNormalized {
  return {
    fullPath: options.fullPath ?? '/app/dashboard',
    meta: {
      allowIncompleteOnboarding: options.allowIncompleteOnboarding ?? false,
      requiresAuth: options.requiresAuth ?? false,
    },
    name: options.name,
  } as unknown as RouteLocationNormalized
}
