import { afterEach, describe, expect, it, vi } from 'vitest'

import { ApiRequestError, configureLeverlyApiClient, leverlyApiRequest, resetLeverlyApiClient } from './runtimeClient'

describe('leverlyApiRequest', () => {
  afterEach(() => {
    resetLeverlyApiClient()
  })

  it('returns typed JSON for a successful unauthenticated request', async () => {
    const fetcher = vi
      .fn()
      .mockResolvedValue(jsonResponse({ status: 'ok', meta: { api_version: 'v1', timestamp: 'now' } }))

    configureLeverlyApiClient({
      fetcher,
      getLocale: () => 'nl-NL',
    })

    const response = await leverlyApiRequest('/health', 'get', {
      authenticated: false,
      headers: {
        'X-Trace-Id': 'trace-1',
      },
    })

    expect(response?.status).toBe('ok')
    expect(fetcher).toHaveBeenCalledWith('/api/v1/health', {
      body: undefined,
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Accept-Language': 'nl-NL',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-Trace-Id': 'trace-1',
      },
      method: 'GET',
      signal: undefined,
    })
  })

  it('prepares a csrf session and sends credentials for authenticated mutation requests', async () => {
    const fetcher = vi
      .fn()
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse({ status: 'ok', meta: { api_version: 'v1', timestamp: 'now' } }))

    configureLeverlyApiClient({
      fetcher,
      getCsrfToken: () => 'csrf-token',
    })

    await leverlyApiRequest('/health', 'post', {
      authenticated: true,
      body: { ready: true },
    })

    expect(fetcher).toHaveBeenNthCalledWith(1, '/sanctum/csrf-cookie', {
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      method: 'GET',
    })
    expect(fetcher).toHaveBeenNthCalledWith(2, '/api/v1/health', {
      body: JSON.stringify({ ready: true }),
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': 'csrf-token',
      },
      method: 'POST',
      signal: undefined,
    })
  })

  it('supports silent, handled, throw-on-error, and not-found error behavior', async () => {
    const handledError = vi.fn()
    const handledNotFound = vi.fn()
    const fetcher = vi
      .fn()
      .mockResolvedValueOnce(jsonResponse({ message: 'quiet' }, { status: 500 }))
      .mockResolvedValueOnce(jsonResponse({ message: 'handled' }, { status: 422 }))
      .mockResolvedValueOnce(jsonResponse({ message: 'missing' }, { status: 404 }))
      .mockResolvedValueOnce(jsonResponse({ message: 'throw' }, { status: 500 }))

    configureLeverlyApiClient({
      fetcher,
      onError: handledError,
      onNotFound: handledNotFound,
    })

    await expect(leverlyApiRequest('/health', 'get', { errorMode: 'silent' })).resolves.toBeNull()
    await expect(leverlyApiRequest('/health', 'get', { errorMode: 'handle' })).resolves.toBeNull()
    await expect(leverlyApiRequest('/health', 'get', { notFoundMode: 'handle' })).resolves.toBeNull()
    await expect(leverlyApiRequest('/health', 'get', { errorMode: 'throw' })).rejects.toBeInstanceOf(ApiRequestError)

    expect(handledError).toHaveBeenCalledTimes(1)
    expect(handledError.mock.calls[0]?.[0].status).toBe(422)
    expect(handledNotFound).toHaveBeenCalledTimes(1)
    expect(handledNotFound.mock.calls[0]?.[0].status).toBe(404)
  })
})

function jsonResponse(body: unknown, init: ResponseInit = {}): Response {
  return new Response(JSON.stringify(body), {
    headers: {
      'Content-Type': 'application/json',
    },
    status: init.status ?? 200,
  })
}
