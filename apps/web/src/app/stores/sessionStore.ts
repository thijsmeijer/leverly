import { defineStore } from 'pinia'

export type SessionStatus = 'authenticated' | 'guest' | 'unknown'
export type SessionValidationState = 'failed' | 'idle' | 'validated' | 'validating'
export type SessionValidator = () => boolean | Promise<boolean>

export const useSessionStore = defineStore('session', {
  state: () => ({
    lastValidatedAt: null as number | null,
    status: 'unknown' as SessionStatus,
    validationState: 'idle' as SessionValidationState,
  }),
  getters: {
    isAuthenticated: (state) => state.status === 'authenticated',
  },
  actions: {
    markLoggedIn(): void {
      this.status = 'authenticated'
    },
    markLoggedOut(): void {
      this.status = 'guest'
    },
    async validateSession(validator?: SessionValidator): Promise<boolean> {
      this.validationState = 'validating'

      try {
        const isValid = validator ? await validator() : this.status !== 'guest'
        this.lastValidatedAt = Date.now()

        if (isValid) {
          this.markLoggedIn()
          this.validationState = 'validated'

          return true
        }

        this.markLoggedOut()
        this.validationState = 'failed'

        return false
      } catch {
        this.markLoggedOut()
        this.validationState = 'failed'

        return false
      }
    },
  },
})
