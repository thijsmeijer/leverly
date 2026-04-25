import { afterEach, describe, expect, it, vi } from 'vitest'
import { jsonResponse } from '@/tests/http'

import { login, logout, register } from './authService'

describe('authService', () => {
  afterEach(() => {
    vi.unstubAllEnvs()
    document.cookie = 'XSRF-TOKEN=; Max-Age=0'
  })

  it('prepares csrf before logging in through the API origin', async () => {
    vi.stubEnv('VITE_API_BASE_URL', 'http://api.leverly.local')
    document.cookie = 'XSRF-TOKEN=csrf-token'
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse({ data: user() }))

    await expect(
      login(
        {
          email: 'ada@example.com',
          password: 'strong-password',
          remember: true,
        },
        { fetcher },
      ),
    ).resolves.toEqual(user())

    expect(fetcher).toHaveBeenNthCalledWith(
      1,
      'http://api.leverly.local/sanctum/csrf-cookie',
      expect.objectContaining({
        credentials: 'include',
        method: 'GET',
      }),
    )
    expect(fetcher).toHaveBeenNthCalledWith(
      2,
      'http://api.leverly.local/login',
      expect.objectContaining({
        credentials: 'include',
        headers: expect.objectContaining({
          'X-XSRF-TOKEN': 'csrf-token',
        }),
        method: 'POST',
      }),
    )
  })

  it('registers and returns the current user payload', async () => {
    vi.stubEnv('VITE_API_BASE_URL', 'http://api.leverly.local/api/v1')
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse({ data: user({ name: 'New Athlete' }) }, { status: 201 }))

    await expect(
      register(
        {
          email: 'new@example.com',
          name: 'New Athlete',
          password: 'strong-password',
          password_confirmation: 'strong-password',
          remember: true,
        },
        { fetcher },
      ),
    ).resolves.toEqual(user({ name: 'New Athlete' }))

    expect(fetcher.mock.calls[1]?.[0]).toBe('http://api.leverly.local/register')
  })

  it('logs out through the csrf-protected endpoint', async () => {
    vi.stubEnv('VITE_API_BASE_URL', 'http://api.leverly.local')
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(new Response(null, { status: 204 }))

    await expect(logout({ fetcher })).resolves.toBeUndefined()

    expect(fetcher.mock.calls[1]?.[0]).toBe('http://api.leverly.local/logout')
  })

  it('maps validation errors from auth responses', async () => {
    vi.stubEnv('VITE_API_BASE_URL', 'http://api.leverly.local')
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(
        jsonResponse(
          {
            errors: {
              email: ['These credentials do not match our records.'],
            },
            message: 'The given data was invalid.',
          },
          { status: 422 },
        ),
      )

    await expect(login({ email: 'ada@example.com', password: 'wrong-password' }, { fetcher })).rejects.toMatchObject({
      errors: {
        email: ['These credentials do not match our records.'],
      },
      status: 422,
    })
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
