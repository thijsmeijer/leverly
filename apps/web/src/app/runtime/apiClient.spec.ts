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
    const handleSessionExpired = vi.fn()
    const showError = vi.fn()
    const handleNotFound = vi.fn()
    const fetcher = vi.fn().mockResolvedValue(jsonResponse({ message: 'expired' }, { status: 401 }))

    setupLeverlyApiRuntime({
      fetcher,
      getLocale: () => 'en-US',
      getCsrfToken: () => 'token',
      handleSessionExpired,
      showError,
      handleNotFound,
    })

    const { leverlyApiRequest } = await import('@/shared/api/leverlyApi/runtimeClient')

    await leverlyApiRequest('/health', 'get', { authenticated: true })

    expect(handleSessionExpired).toHaveBeenCalledOnce()
    expect(showError).toHaveBeenCalledOnce()
    expect(handleNotFound).not.toHaveBeenCalled()
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
