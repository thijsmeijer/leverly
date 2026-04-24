import { describe, expect, it, vi } from 'vitest'

import { leverlyApiRequest } from '@/shared/api/leverlyApi/runtimeClient'

import { fetchDashboardStatus } from './dashboardStatusService'

vi.mock('@/shared/api/leverlyApi/runtimeClient', () => ({
  leverlyApiRequest: vi.fn(),
}))

const mockedRequest = vi.mocked(leverlyApiRequest)

describe('fetchDashboardStatus', () => {
  it('maps the health transport response into a dashboard model', async () => {
    mockedRequest.mockResolvedValueOnce({
      status: 'ok',
      meta: {
        api_version: 'v1',
        timestamp: '2026-04-25T10:30:00.000000Z',
      },
    })

    await expect(fetchDashboardStatus()).resolves.toEqual({
      checkedAt: '2026-04-25T10:30:00.000000Z',
      detail: 'Version v1',
      label: 'API online',
      state: 'online',
    })

    expect(mockedRequest).toHaveBeenCalledWith('/health', 'get', {
      authenticated: false,
      errorMode: 'silent',
      notFoundMode: 'silent',
    })
  })

  it('returns an unavailable model when the health check has no body', async () => {
    mockedRequest.mockResolvedValueOnce(null)

    await expect(fetchDashboardStatus()).resolves.toEqual({
      checkedAt: null,
      detail: 'Status unavailable',
      label: 'API status pending',
      state: 'unavailable',
    })
  })
})
