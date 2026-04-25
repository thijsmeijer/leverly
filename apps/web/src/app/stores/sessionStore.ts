import { defineStore } from 'pinia'
import {
  fetchCurrentUser,
  login as loginUser,
  logout as logoutUser,
  register as registerUser,
  type CurrentUser,
  type LoginPayload,
  type RegisterPayload,
} from '@/app/services/authService'

export type SessionStatus = 'authenticated' | 'guest' | 'unknown'
export type SessionValidationState = 'failed' | 'idle' | 'validated' | 'validating'
export type SessionValidator = () => boolean | Promise<boolean>

export const useSessionStore = defineStore('session', {
  state: () => ({
    lastValidatedAt: null as number | null,
    status: 'unknown' as SessionStatus,
    user: null as CurrentUser | null,
    validationState: 'idle' as SessionValidationState,
  }),
  getters: {
    isAuthenticated: (state) => state.status === 'authenticated',
  },
  actions: {
    markLoggedIn(user?: CurrentUser): void {
      this.status = 'authenticated'

      if (user) {
        this.user = user
      }
    },
    markLoggedOut(): void {
      this.status = 'guest'
      this.user = null
    },
    async login(payload: LoginPayload): Promise<CurrentUser> {
      const user = await loginUser(payload)

      this.markLoggedIn(user)
      this.lastValidatedAt = Date.now()
      this.validationState = 'validated'

      return user
    },
    async register(payload: RegisterPayload): Promise<CurrentUser> {
      const user = await registerUser(payload)

      this.markLoggedIn(user)
      this.lastValidatedAt = Date.now()
      this.validationState = 'validated'

      return user
    },
    async logout(): Promise<void> {
      await logoutUser()
      this.markLoggedOut()
      this.validationState = 'idle'
    },
    async validateSession(validator?: SessionValidator): Promise<boolean> {
      this.validationState = 'validating'

      try {
        const isValid = validator ? await validator() : await this.refreshCurrentUser()
        this.lastValidatedAt = Date.now()

        if (isValid) {
          if (validator) {
            this.markLoggedIn()
          }
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
    async refreshCurrentUser(): Promise<boolean> {
      const user = await fetchCurrentUser()

      this.markLoggedIn(user)

      return true
    },
  },
})
