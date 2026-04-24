import type { RouteLocationNormalized } from 'vue-router'
import { describe, expect, it, vi } from 'vitest'

import { createSessionGuard } from './sessionGuard'

describe('createSessionGuard', () => {
  it('allows public routes without validating the session', async () => {
    const validateSession = vi.fn()
    const guard = createSessionGuard({
      useSessionStore: () => ({ validateSession }),
    })

    await expect(guard(route({ requiresAuth: false }))).resolves.toBe(true)

    expect(validateSession).not.toHaveBeenCalled()
  })

  it('allows protected routes after session validation succeeds', async () => {
    const validateSession = vi.fn().mockResolvedValue(true)
    const guard = createSessionGuard({
      useSessionStore: () => ({ validateSession }),
    })

    await expect(guard(route({ requiresAuth: true }))).resolves.toBe(true)

    expect(validateSession).toHaveBeenCalledOnce()
  })

  it('redirects protected routes to login when validation fails', async () => {
    const validateSession = vi.fn().mockResolvedValue(false)
    const guard = createSessionGuard({
      useSessionStore: () => ({ validateSession }),
    })

    await expect(guard(route({ fullPath: '/app/dashboard?tab=today', requiresAuth: true }))).resolves.toEqual({
      name: 'login',
      query: {
        redirect: '/app/dashboard?tab=today',
      },
    })
  })
})

function route(options: { fullPath?: string; requiresAuth?: boolean }): RouteLocationNormalized {
  return {
    fullPath: options.fullPath ?? '/app/dashboard',
    meta: {
      requiresAuth: options.requiresAuth ?? false,
    },
  } as unknown as RouteLocationNormalized
}
