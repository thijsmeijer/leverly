import { describe, expect, it } from 'vitest'

import { buildGeneratedTypes, findContractProblems, resolveSpecPath } from './openapi-client.mjs'

const validHealthSpec = `openapi: 3.1.0
info:
  title: Leverly API
  version: 0.1.0
servers:
  - url: /api/v1
paths:
  /me:
    get:
      operationId: getTheCurrentUser
      responses:
        200:
          description: Current user.
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: object
                    properties:
                      id:
                        type: string
                      name:
                        type: string
                      email:
                        type: string
  /health:
    get:
      operationId: getAPIHealthMetadata
      responses:
        200:
          description: API is reachable.
          content:
            application/json:
              schema:
                type: object
                properties:
                  status:
                    type: string
                    enum:
                      - ok
                  meta:
                    type: object
                    properties:
                      api_version:
                        type: string
                      timestamp:
                        type: string
`

describe('OpenAPI client generation', () => {
  it('resolves configured spec paths relative to the current working directory', () => {
    const specPath = resolveSpecPath({
      currentWorkingDirectory: '/repo/apps/web',
      environment: {
        OPENAPI_SPEC_PATH: '../../docs/api/openapi.yaml',
      },
    })

    expect(specPath).toBe('/repo/docs/api/openapi.yaml')
  })

  it('validates the health contract and generates stable TypeScript output', () => {
    expect(findContractProblems(validHealthSpec)).toEqual([])

    const generated = buildGeneratedTypes(validHealthSpec)

    expect(generated).toContain('export interface HealthResponse')
    expect(generated).toContain('export interface CurrentUserResponse')
    expect(generated).toContain("readonly '/me'")
    expect(generated).toContain("readonly status: 'ok'")
    expect(generated).toContain('readonly timestamp: string')
    expect(generated).not.toContain('example')
  })
})
