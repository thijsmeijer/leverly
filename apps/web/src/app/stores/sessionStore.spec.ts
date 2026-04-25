import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

const authService = vi.hoisted(() => ({
  fetchCurrentUser: vi.fn(),
  login: vi.fn(),
  logout: vi.fn(),
  register: vi.fn(),
}))

vi.mock('@/app/services/authService', () => authService)

import { useSessionStore } from './sessionStore'

describe('useSessionStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.clearAllMocks()
  })

  it('owns login and logout state transitions', () => {
    const session = useSessionStore()

    expect(session.status).toBe('unknown')
    expect(session.isAuthenticated).toBe(false)

    session.markLoggedIn()

    expect(session.status).toBe('authenticated')
    expect(session.isAuthenticated).toBe(true)

    session.markLoggedOut()

    expect(session.status).toBe('guest')
    expect(session.isAuthenticated).toBe(false)
  })

  it('validates the session with an injected validator and records validation state', async () => {
    const session = useSessionStore()
    const validator = vi.fn().mockResolvedValue(true)

    await expect(session.validateSession(validator)).resolves.toBe(true)

    expect(validator).toHaveBeenCalledOnce()
    expect(session.status).toBe('authenticated')
    expect(session.validationState).toBe('validated')
    expect(session.lastValidatedAt).toEqual(expect.any(Number))
  })

  it('validates the session through the current-user endpoint by default', async () => {
    const session = useSessionStore()
    authService.fetchCurrentUser.mockResolvedValue(user())

    await expect(session.validateSession()).resolves.toBe(true)

    expect(authService.fetchCurrentUser).toHaveBeenCalledOnce()
    expect(session.status).toBe('authenticated')
    expect(session.user).toEqual(user())
  })

  it('logs in, registers, and logs out through the auth service', async () => {
    const session = useSessionStore()
    authService.login.mockResolvedValue(user({ email: 'login@example.com' }))
    authService.register.mockResolvedValue(user({ email: 'register@example.com' }))
    authService.logout.mockResolvedValue(undefined)

    await expect(
      session.login({
        email: 'login@example.com',
        password: 'strong-password',
        remember: true,
      }),
    ).resolves.toEqual(user({ email: 'login@example.com' }))
    expect(session.user?.email).toBe('login@example.com')

    await expect(
      session.register({
        email: 'register@example.com',
        name: 'Ada Athlete',
        password: 'strong-password',
        password_confirmation: 'strong-password',
        remember: true,
      }),
    ).resolves.toEqual(user({ email: 'register@example.com' }))
    expect(session.user?.email).toBe('register@example.com')

    await session.logout()

    expect(authService.logout).toHaveBeenCalledOnce()
    expect(session.status).toBe('guest')
    expect(session.user).toBeNull()
  })

  it('marks the user as logged out when validation fails', async () => {
    const session = useSessionStore()
    const validator = vi.fn().mockResolvedValue(false)

    await expect(session.validateSession(validator)).resolves.toBe(false)

    expect(session.status).toBe('guest')
    expect(session.validationState).toBe('failed')
  })
})

function user(overrides: Partial<{ email: string; id: string; name: string }> = {}) {
  return {
    email: 'ada@example.com',
    id: '01kaw4k7q6v7m9r6rddm4xyf2p',
    name: 'Ada Athlete',
    ...overrides,
  }
}
