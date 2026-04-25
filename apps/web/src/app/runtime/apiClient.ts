import { configureLeverlyApiClient, type ApiFetch, type ApiRequestError } from '@/shared/api/leverlyApi/runtimeClient'
import type { useAuthorizationStore } from '@/app/stores/authorizationStore'
import type { useSessionStore } from '@/app/stores/sessionStore'

type AuthorizationStore = ReturnType<typeof useAuthorizationStore>
type SessionStore = ReturnType<typeof useSessionStore>

export interface LeverlyApiRuntimeSetupOptions {
  readonly authorizationStore?: Pick<AuthorizationStore, 'markForbidden'>
  readonly fetcher?: ApiFetch
  readonly getCsrfToken?: () => string | null | Promise<string | null>
  readonly getLocale?: () => string | null
  readonly handleForbidden?: (error: ApiRequestError) => void
  readonly handleNotFound?: (error: ApiRequestError) => void
  readonly handleSessionExpired?: (error: ApiRequestError) => void
  readonly sessionStore?: Pick<SessionStore, 'markLoggedOut'>
  readonly showError?: (error: ApiRequestError) => void
}

export function setupLeverlyApiRuntime(options: LeverlyApiRuntimeSetupOptions = {}): void {
  const handleForbidden = options.handleForbidden ?? ignoreApiError
  const handleSessionExpired = options.handleSessionExpired ?? ignoreApiError

  configureLeverlyApiClient({
    baseUrl: apiBaseUrl(),
    fetcher: options.fetcher,
    getCsrfToken: options.getCsrfToken,
    getLocale: options.getLocale ?? browserLocale,
    onError: options.showError ?? ignoreApiError,
    onForbidden: (error) => {
      options.authorizationStore?.markForbidden(error)
      handleForbidden(error)
    },
    onNotFound: options.handleNotFound ?? ignoreApiError,
    onUnauthenticated: (error) => {
      options.sessionStore?.markLoggedOut()
      handleSessionExpired(error)
    },
  })
}

function apiBaseUrl(): string {
  const configuredBaseUrl = import.meta.env.VITE_API_BASE_URL

  if (!configuredBaseUrl) {
    return '/api/v1'
  }

  const trimmedBaseUrl = configuredBaseUrl.replace(/\/+$/, '')

  return trimmedBaseUrl.endsWith('/api/v1') ? trimmedBaseUrl : `${trimmedBaseUrl}/api/v1`
}

function browserLocale(): string | null {
  return navigator.language || null
}

function ignoreApiError(): void {
  // App stores replace this default hook when user-facing error surfaces exist.
}
