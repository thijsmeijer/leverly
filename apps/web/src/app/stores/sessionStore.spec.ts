import { createPinia, setActivePinia } from 'pinia'
import { beforeEach, describe, expect, it, vi } from 'vitest'

import { useSessionStore } from './sessionStore'

describe('useSessionStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
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

  it('marks the user as logged out when validation fails', async () => {
    const session = useSessionStore()
    const validator = vi.fn().mockResolvedValue(false)

    await expect(session.validateSession(validator)).resolves.toBe(false)

    expect(session.status).toBe('guest')
    expect(session.validationState).toBe('failed')
  })
})
