import { describe, expect, it } from 'vitest'

import { buildGeneratedTypes, findContractProblems, resolveSpecPath } from './openapi-client.mjs'

const validHealthSpec = `openapi: 3.1.0
info:
  title: Leverly API
  version: 0.1.0
servers:
  - url: /api/v1
paths:
  /health:
    get:
      operationId: getHealth
      responses:
        '200':
          description: API is reachable.
          content:
            application/json:
              schema:
                $ref: '#/components/schemas/HealthResponse'
components:
  schemas:
    HealthResponse:
      type: object
      required:
        - status
        - meta
      properties:
        status:
          type: string
          enum:
            - ok
        meta:
          type: object
          required:
            - api_version
            - timestamp
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
    expect(generated).toContain("readonly status: 'ok'")
    expect(generated).toContain('readonly timestamp: string')
    expect(generated).not.toContain('example')
  })
})
