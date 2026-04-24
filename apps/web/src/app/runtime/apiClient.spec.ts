import { describe, expect, it, vi } from 'vitest'

import { resetLeverlyApiClient } from '@/shared/api/leverlyApi/runtimeClient'

import { setupLeverlyApiRuntime } from './apiClient'

describe('setupLeverlyApiRuntime', () => {
  it('wires app-owned runtime dependencies into the shared API client', async () => {
    const handleSessionExpired = vi.fn()
    const showError = vi.fn()
    const handleNotFound = vi.fn()
    const fetcher = vi.fn().mockResolvedValue(new Response(JSON.stringify({ message: 'expired' }), { status: 401 }))

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

    resetLeverlyApiClient()
    expect(handleNotFound).not.toHaveBeenCalled()
  })
})
