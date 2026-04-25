export interface AuthFieldErrors {
  readonly email?: string
  readonly name?: string
  readonly password?: string
  readonly password_confirmation?: string
}

export function validateLogin(input: { email: string; password: string }): AuthFieldErrors {
  return {
    email: emailError(input.email),
    password: passwordRequiredError(input.password),
  }
}

export function validateRegister(input: {
  email: string
  name: string
  password: string
  password_confirmation: string
}): AuthFieldErrors {
  return {
    email: emailError(input.email),
    name: input.name.trim() ? undefined : 'Enter your name.',
    password: passwordRequiredError(input.password) ?? passwordLengthError(input.password),
    password_confirmation:
      input.password_confirmation === input.password ? undefined : 'Use the same password in both password fields.',
  }
}

export function firstServerErrors(errors: Record<string, string[]>): AuthFieldErrors {
  return Object.fromEntries(
    Object.entries(errors).map(([field, messages]) => [field, messages[0]]),
  ) as unknown as AuthFieldErrors
}

function emailError(email: string): string | undefined {
  if (!email.trim()) {
    return 'Enter your email address.'
  }

  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email) ? undefined : 'Enter a valid email address.'
}

function passwordRequiredError(password: string): string | undefined {
  return password ? undefined : 'Enter your password.'
}

function passwordLengthError(password: string): string | undefined {
  return password.length >= 8 ? undefined : 'Use at least 8 characters.'
}
