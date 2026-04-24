// Generated from the Leverly OpenAPI contract. Do not edit manually.
// Run `pnpm api-client:generate` from apps/web to refresh.

export interface HealthResponse {
  readonly status: 'ok'
  readonly meta: {
    readonly api_version: 'v1'
    readonly timestamp: string
  }
}

export interface paths {
  readonly '/health': {
    readonly get: {
      readonly responses: {
        readonly 200: {
          readonly content: {
            readonly 'application/json': HealthResponse
          }
        }
      }
    }
  }
}
