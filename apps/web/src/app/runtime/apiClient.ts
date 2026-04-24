import { configureLeverlyApiClient, type ApiFetch, type ApiRequestError } from '@/shared/api/leverlyApi/runtimeClient'

export interface LeverlyApiRuntimeSetupOptions {
  readonly fetcher?: ApiFetch
  readonly getCsrfToken?: () => string | null | Promise<string | null>
  readonly getLocale?: () => string | null
  readonly handleNotFound?: (error: ApiRequestError) => void
  readonly handleSessionExpired?: (error: ApiRequestError) => void
  readonly showError?: (error: ApiRequestError) => void
}

export function setupLeverlyApiRuntime(options: LeverlyApiRuntimeSetupOptions = {}): void {
  configureLeverlyApiClient({
    fetcher: options.fetcher,
    getCsrfToken: options.getCsrfToken,
    getLocale: options.getLocale ?? browserLocale,
    onError: options.showError ?? ignoreApiError,
    onNotFound: options.handleNotFound ?? ignoreApiError,
    onUnauthenticated: options.handleSessionExpired ?? ignoreApiError,
  })
}

function browserLocale(): string | null {
  return navigator.language || null
}

function ignoreApiError(): void {
  // App stores replace this default hook when user-facing error surfaces exist.
}
