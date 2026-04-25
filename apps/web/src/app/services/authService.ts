import { apiOriginUrl, csrfCookieUrl } from '@/app/runtime/apiClient'
import { leverlyApiRequest } from '@/shared/api/leverlyApi/runtimeClient'

export interface CurrentUser {
  readonly email: string
  readonly id: string
  readonly name: string
}

export interface LoginPayload {
  readonly email: string
  readonly password: string
  readonly remember?: boolean
}

export interface RegisterPayload extends LoginPayload {
  readonly name: string
  readonly password_confirmation: string
}

export type AuthValidationErrors = Record<string, string[]>

export class AuthRequestError extends Error {
  readonly errors: AuthValidationErrors
  readonly status: number

  constructor(message: string, status: number, errors: AuthValidationErrors = {}) {
    super(message)
    this.name = 'AuthRequestError'
    this.status = status
    this.errors = errors
  }
}

interface AuthServiceOptions {
  readonly fetcher?: typeof fetch
}

interface UserResponse {
  readonly data: CurrentUser
}

export async function fetchCurrentUser(): Promise<CurrentUser> {
  const response = await leverlyApiRequest('/me', 'get', {
    authenticated: true,
    csrf: false,
    errorMode: 'throw',
  })

  if (!response) {
    throw new AuthRequestError('Could not load the current user.', 0)
  }

  return response.data
}

export async function login(payload: LoginPayload, options: AuthServiceOptions = {}): Promise<CurrentUser> {
  return authJsonRequest('/login', payload, options)
}

export async function register(payload: RegisterPayload, options: AuthServiceOptions = {}): Promise<CurrentUser> {
  return authJsonRequest('/register', payload, options)
}

export async function logout(options: AuthServiceOptions = {}): Promise<void> {
  const fetcher = options.fetcher ?? fetch

  await prepareCsrf(fetcher)

  const response = await fetcher(`${apiOriginUrl()}/logout`, {
    credentials: 'include',
    headers: await authHeaders(),
    method: 'POST',
  })

  if (!response.ok && response.status !== 401) {
    throw await authError(response)
  }
}

async function authJsonRequest(
  path: '/login' | '/register',
  payload: LoginPayload | RegisterPayload,
  options: AuthServiceOptions,
): Promise<CurrentUser> {
  const fetcher = options.fetcher ?? fetch

  await prepareCsrf(fetcher)

  const response = await fetcher(`${apiOriginUrl()}${path}`, {
    body: JSON.stringify(payload),
    credentials: 'include',
    headers: await authHeaders(),
    method: 'POST',
  })

  if (!response.ok) {
    throw await authError(response)
  }

  return ((await response.json()) as UserResponse).data
}

async function prepareCsrf(fetcher: typeof fetch): Promise<void> {
  const response = await fetcher(csrfCookieUrl(), {
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    method: 'GET',
  })

  if (!response.ok) {
    throw new AuthRequestError('Could not prepare a secure browser session.', response.status)
  }
}

async function authHeaders(): Promise<Record<string, string>> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  }
  const token = cookieValue('XSRF-TOKEN')

  if (token) {
    headers['X-XSRF-TOKEN'] = token
  }

  return headers
}

async function authError(response: Response): Promise<AuthRequestError> {
  const body = await parseJson(response)
  const message = typeof body?.message === 'string' ? body.message : 'Account request failed.'
  const errors = isValidationErrors(body?.errors) ? body.errors : {}

  return new AuthRequestError(message, response.status, errors)
}

async function parseJson(response: Response): Promise<Record<string, unknown> | null> {
  const text = await response.text()

  if (!text) {
    return null
  }

  try {
    return JSON.parse(text) as Record<string, unknown>
  } catch {
    return null
  }
}

function isValidationErrors(value: unknown): value is AuthValidationErrors {
  return (
    typeof value === 'object' &&
    value !== null &&
    Object.values(value).every(
      (messages) => Array.isArray(messages) && messages.every((message) => typeof message === 'string'),
    )
  )
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
