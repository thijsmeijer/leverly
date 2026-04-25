import type { paths } from './openapi/generated'

export type ApiPath = keyof paths
export type ApiHttpMethod = 'delete' | 'get' | 'patch' | 'post' | 'put'
export type ApiErrorMode = 'handle' | 'silent' | 'throw'
export type ApiForbiddenMode = 'handle' | 'silent' | 'throw'
export type ApiNotFoundMode = 'handle' | 'silent' | 'throw'
export type ApiFetch = (input: string, init?: RequestInit) => Promise<Response>

type ApiResponseMap<Path extends ApiPath, Method extends ApiHttpMethod> = Method extends keyof paths[Path]
  ? paths[Path][Method] extends { responses: infer Responses }
    ? Responses
    : never
  : never

export type ApiResponseBody<Path extends ApiPath, Method extends ApiHttpMethod> =
  ApiResponseMap<Path, Method> extends {
    200: { content: { 'application/json': infer Body } }
  }
    ? Body
    : unknown

export interface ApiRequestOptions {
  readonly authenticated?: boolean
  readonly body?: unknown
  readonly csrf?: boolean | 'auto'
  readonly errorMode?: ApiErrorMode
  readonly forbiddenMode?: ApiForbiddenMode
  readonly headers?: Record<string, string>
  readonly notFoundMode?: ApiNotFoundMode
  readonly signal?: AbortSignal
}

export interface ApiRuntimeDependencies {
  readonly baseUrl?: string
  readonly csrfUrl?: string
  readonly fetcher?: ApiFetch
  readonly getCsrfToken?: () => string | null | Promise<string | null>
  readonly getLocale?: () => string | null
  readonly onError?: (error: ApiRequestError) => void
  readonly onForbidden?: (error: ApiRequestError) => void
  readonly onNotFound?: (error: ApiRequestError) => void
  readonly onUnauthenticated?: (error: ApiRequestError) => void
}

export class ApiRequestError extends Error {
  readonly body: unknown
  readonly status: number

  constructor(message: string, status: number, body: unknown) {
    super(message)
    this.name = 'ApiRequestError'
    this.body = body
    this.status = status
  }
}

let runtimeDependencies: ApiRuntimeDependencies = {}

export function configureLeverlyApiClient(dependencies: ApiRuntimeDependencies): void {
  runtimeDependencies = {
    ...runtimeDependencies,
    ...dependencies,
  }
}

export function resetLeverlyApiClient(): void {
  runtimeDependencies = {}
}

export async function leverlyApiRequest<Path extends ApiPath, Method extends ApiHttpMethod>(
  path: Path,
  method: Method,
  options: ApiRequestOptions = {},
): Promise<ApiResponseBody<Path, Method> | null> {
  const fetcher = resolveFetcher()
  const authenticated = options.authenticated ?? true
  const normalizedMethod = method.toUpperCase()

  if (shouldPrepareCsrf(normalizedMethod, options.csrf)) {
    await prepareCsrfSession(fetcher)
  }

  const response = await fetcher(`${baseUrl()}${path}`, {
    body: options.body === undefined ? undefined : JSON.stringify(options.body),
    credentials: authenticated ? 'include' : 'same-origin',
    headers: await requestHeaders(options),
    method: normalizedMethod,
    signal: options.signal,
  })

  if (response.ok) {
    return parseResponseBody<ApiResponseBody<Path, Method>>(response)
  }

  return handleErrorResponse(response, options)
}

function resolveFetcher(): ApiFetch {
  if (runtimeDependencies.fetcher) {
    return runtimeDependencies.fetcher
  }

  if (typeof globalThis.fetch === 'function') {
    return globalThis.fetch.bind(globalThis)
  }

  throw new Error('No API fetch implementation is available.')
}

function baseUrl(): string {
  return runtimeDependencies.baseUrl ?? '/api/v1'
}

function csrfUrl(): string {
  return runtimeDependencies.csrfUrl ?? '/sanctum/csrf-cookie'
}

function shouldPrepareCsrf(method: string, csrf: ApiRequestOptions['csrf']): boolean {
  if (csrf === true) {
    return true
  }

  if (csrf === false) {
    return false
  }

  return !['GET', 'HEAD', 'OPTIONS'].includes(method)
}

async function prepareCsrfSession(fetcher: ApiFetch): Promise<void> {
  await fetcher(csrfUrl(), {
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    method: 'GET',
  })
}

async function requestHeaders(options: ApiRequestOptions): Promise<Record<string, string>> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  }
  const locale = runtimeDependencies.getLocale?.()

  if (locale) {
    headers['Accept-Language'] = locale
  }

  const csrfToken = await resolveCsrfToken()

  if (csrfToken) {
    headers['X-XSRF-TOKEN'] = csrfToken
  }

  return {
    ...headers,
    ...options.headers,
  }
}

async function resolveCsrfToken(): Promise<string | null> {
  const dependencyToken = await runtimeDependencies.getCsrfToken?.()

  if (dependencyToken) {
    return dependencyToken
  }

  return cookieValue('XSRF-TOKEN')
}

function cookieValue(name: string): string | null {
  if (typeof document === 'undefined') {
    return null
  }

  const match = document.cookie
    .split(';')
    .map((cookie) => cookie.trim())
    .find((cookie) => cookie.startsWith(`${name}=`))

  if (!match) {
    return null
  }

  return decodeURIComponent(match.slice(name.length + 1))
}

async function parseResponseBody<Body>(response: Response): Promise<Body | null> {
  if (response.status === 204) {
    return null
  }

  const text = await response.text()

  if (!text) {
    return null
  }

  return JSON.parse(text) as Body
}

async function handleErrorResponse<Path extends ApiPath, Method extends ApiHttpMethod>(
  response: Response,
  options: ApiRequestOptions,
): Promise<ApiResponseBody<Path, Method> | null> {
  const error = new ApiRequestError(
    `API request failed with status ${response.status}.`,
    response.status,
    await errorBody(response),
  )

  if (response.status === 401) {
    runtimeDependencies.onUnauthenticated?.(error)
  }

  if (response.status === 404) {
    return handleNotFound(error, options.notFoundMode ?? 'handle')
  }

  if (response.status === 403) {
    return handleForbidden(error, options.forbiddenMode ?? 'handle')
  }

  return handleGeneralError(error, options.errorMode ?? 'handle')
}

async function errorBody(response: Response): Promise<unknown> {
  const text = await response.text()

  if (!text) {
    return null
  }

  try {
    return JSON.parse(text)
  } catch {
    return text
  }
}

function handleNotFound<Path extends ApiPath, Method extends ApiHttpMethod>(
  error: ApiRequestError,
  mode: ApiNotFoundMode,
): ApiResponseBody<Path, Method> | null {
  if (mode === 'throw') {
    throw error
  }

  if (mode === 'handle') {
    runtimeDependencies.onNotFound?.(error)
  }

  return null
}

function handleForbidden<Path extends ApiPath, Method extends ApiHttpMethod>(
  error: ApiRequestError,
  mode: ApiForbiddenMode,
): ApiResponseBody<Path, Method> | null {
  if (mode === 'throw') {
    throw error
  }

  if (mode === 'handle') {
    runtimeDependencies.onForbidden?.(error)
  }

  return null
}

function handleGeneralError<Path extends ApiPath, Method extends ApiHttpMethod>(
  error: ApiRequestError,
  mode: ApiErrorMode,
): ApiResponseBody<Path, Method> | null {
  if (mode === 'throw') {
    throw error
  }

  if (mode === 'handle') {
    runtimeDependencies.onError?.(error)
  }

  return null
}
