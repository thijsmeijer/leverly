import { defineStore } from 'pinia'

import type { ApiRequestError } from '@/shared/api/leverlyApi/runtimeClient'

export interface AuthorizationDenial {
  readonly message: string
  readonly occurredAt: number
  readonly status: number
}

export const useAuthorizationStore = defineStore('authorization', {
  state: () => ({
    lastDenied: null as AuthorizationDenial | null,
  }),
  getters: {
    hasDeniedAccess: (state) => state.lastDenied !== null,
  },
  actions: {
    markForbidden(error: ApiRequestError): void {
      this.lastDenied = {
        message: authorizationMessage(error),
        occurredAt: Date.now(),
        status: error.status,
      }
    },
    clearDenied(): void {
      this.lastDenied = null
    },
  },
})

function authorizationMessage(error: ApiRequestError): string {
  if (hasMessage(error.body)) {
    return error.body.message
  }

  return 'You do not have access to that resource.'
}

function hasMessage(body: unknown): body is { message: string } {
  return typeof body === 'object' && body !== null && 'message' in body && typeof body.message === 'string'
}
