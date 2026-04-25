import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { ApiRequestError } from '@/shared/api/leverlyApi/runtimeClient'

import { useAuthorizationStore } from './authorizationStore'

describe('authorizationStore', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.useFakeTimers()
    vi.setSystemTime(new Date('2026-04-25T12:00:00.000Z'))
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('records forbidden API responses as authorization denials', () => {
    const store = useAuthorizationStore()
    const error = new ApiRequestError('API request failed with status 403.', 403, {
      message: 'This resource is not available.',
    })

    store.markForbidden(error)

    expect(store.lastDenied).toEqual({
      message: 'This resource is not available.',
      occurredAt: Date.parse('2026-04-25T12:00:00.000Z'),
      status: 403,
    })
    expect(store.hasDeniedAccess).toBe(true)
  })

  it('can clear the latest authorization denial', () => {
    const store = useAuthorizationStore()

    store.markForbidden(new ApiRequestError('Forbidden.', 403, null))
    store.clearDenied()

    expect(store.lastDenied).toBeNull()
    expect(store.hasDeniedAccess).toBe(false)
  })
})
