import { configureLeverlyApiClient, type ApiFetch, type ApiRequestError } from '@/shared/api/leverlyApi/runtimeClient'
import type { useSessionStore } from '@/app/stores/sessionStore'

type SessionStore = ReturnType<typeof useSessionStore>

export interface LeverlyApiRuntimeSetupOptions {
  readonly fetcher?: ApiFetch
  readonly getCsrfToken?: () => string | null | Promise<string | null>
  readonly getLocale?: () => string | null
  readonly handleNotFound?: (error: ApiRequestError) => void
  readonly handleSessionExpired?: (error: ApiRequestError) => void
  readonly sessionStore?: Pick<SessionStore, 'markLoggedOut'>
  readonly showError?: (error: ApiRequestError) => void
}

export function setupLeverlyApiRuntime(options: LeverlyApiRuntimeSetupOptions = {}): void {
  const handleSessionExpired = options.handleSessionExpired ?? ignoreApiError

  configureLeverlyApiClient({
    fetcher: options.fetcher,
    getCsrfToken: options.getCsrfToken,
    getLocale: options.getLocale ?? browserLocale,
    onError: options.showError ?? ignoreApiError,
    onNotFound: options.handleNotFound ?? ignoreApiError,
    onUnauthenticated: (error) => {
      options.sessionStore?.markLoggedOut()
      handleSessionExpired(error)
    },
  })
}

function browserLocale(): string | null {
  return navigator.language || null
}

function ignoreApiError(): void {
  // App stores replace this default hook when user-facing error surfaces exist.
}
