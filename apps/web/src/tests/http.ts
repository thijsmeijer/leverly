import { vi, type Mock } from 'vitest'

type JsonResponseOptions = {
  headers?: HeadersInit
  status?: number
}

export function jsonResponse(body: unknown, options: JsonResponseOptions = {}) {
  return new Response(JSON.stringify(body), {
    status: options.status ?? 200,
    headers: {
      'content-type': 'application/json',
      ...options.headers,
    },
  })
}

export function createFetchMock(...responses: Response[]): Mock<typeof fetch> {
  const queue = [...responses]

  return vi.fn<typeof fetch>(() => {
    const response = queue.shift()

    if (!response) {
      throw new Error('No fetch mock response was queued.')
    }

    return Promise.resolve(response)
  })
}
