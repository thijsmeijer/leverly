import { afterEach, describe, expect, it, vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

import { resetLeverlyApiClient } from '@/shared/api/leverlyApi/runtimeClient'
import { useSessionStore } from '@/app/stores/sessionStore'
import { jsonResponse } from '@/tests/http'

import { setupLeverlyApiRuntime } from './apiClient'

describe('setupLeverlyApiRuntime', () => {
  afterEach(() => {
    resetLeverlyApiClient()
  })

  it('wires app-owned runtime dependencies into the shared API client', async () => {
    const handleForbidden = vi.fn()
    const handleSessionExpired = vi.fn()
    const showError = vi.fn()
    const handleNotFound = vi.fn()
    const fetcher = vi
      .fn()
      .mockResolvedValueOnce(jsonResponse({ message: 'forbidden' }, { status: 403 }))
      .mockResolvedValueOnce(jsonResponse({ message: 'expired' }, { status: 401 }))

    setupLeverlyApiRuntime({
      fetcher,
      getLocale: () => 'en-US',
      getCsrfToken: () => 'token',
      handleForbidden,
      handleSessionExpired,
      showError,
      handleNotFound,
    })

    const { leverlyApiRequest } = await import('@/shared/api/leverlyApi/runtimeClient')

    await leverlyApiRequest('/health', 'get', { authenticated: true })
    await leverlyApiRequest('/health', 'get', { authenticated: true })

    expect(handleForbidden).toHaveBeenCalledOnce()
    expect(handleSessionExpired).toHaveBeenCalledOnce()
    expect(showError).toHaveBeenCalledOnce()
    expect(handleNotFound).not.toHaveBeenCalled()
  })

  it('connects forbidden API responses to the authorization store without logging the user out', async () => {
    setActivePinia(createPinia())
    const session = useSessionStore()
    const authorizationStore = {
      markForbidden: vi.fn(),
    }
    const fetcher = vi.fn().mockResolvedValue(jsonResponse({ message: 'not yours' }, { status: 403 }))

    session.markLoggedIn()

    setupLeverlyApiRuntime({
      authorizationStore,
      fetcher,
      sessionStore: session,
    })

    const { leverlyApiRequest } = await import('@/shared/api/leverlyApi/runtimeClient')

    await leverlyApiRequest('/health', 'get', { authenticated: true })

    expect(session.status).toBe('authenticated')
    expect(authorizationStore.markForbidden).toHaveBeenCalledOnce()
    expect(authorizationStore.markForbidden.mock.calls[0]?.[0].status).toBe(403)
  })

  it('connects unauthenticated API responses to the session store', async () => {
    setActivePinia(createPinia())
    const session = useSessionStore()
    const fetcher = vi.fn().mockResolvedValue(jsonResponse({ message: 'expired' }, { status: 401 }))

    session.markLoggedIn()

    setupLeverlyApiRuntime({
      fetcher,
      sessionStore: session,
    })

    const { leverlyApiRequest } = await import('@/shared/api/leverlyApi/runtimeClient')

    await leverlyApiRequest('/health', 'get', { authenticated: true, errorMode: 'silent' })

    expect(session.status).toBe('guest')
  })
})
