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
  /me/profile:
    get:
      operationId: getAthleteProfile
      responses:
        200:
          description: Athlete profile.
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: object
                    properties:
                      display_name:
                        type: string
                      timezone:
                        type: string
                      unit_system:
                        type: string
                      age_years:
                        type: integer
                      height_value:
                        type: number
                      weight_trend:
                        type: string
                      pain_flags:
                        type: object
                      roadmap_suggestions:
                        type: object
                        properties:
                          version:
                            type: string
                          primary_goal:
                            type: object
                          eta_range:
                            type: object
                          intermediate:
                            type: object
                      preferred_session_minutes:
                        type: integer
                      primary_target_skill:
                        type: string
                      required_goal_modules:
                        type: array
                      goal_modules:
                        type: object
                      baseline_tests:
                        type: object
                      mobility_checks:
                        type: object
                      weighted_baselines:
                        type: object
    patch:
      operationId: updateAthleteProfile
      responses:
        200:
          description: Athlete profile.
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: object
                    properties:
                      display_name:
                        type: string
                      timezone:
                        type: string
                      unit_system:
                        type: string
                      age_years:
                        type: integer
                      height_value:
                        type: number
                      weight_trend:
                        type: string
                      pain_flags:
                        type: object
                      roadmap_suggestions:
                        type: object
                        properties:
                          version:
                            type: string
                          primary_goal:
                            type: object
                          eta_range:
                            type: object
                          intermediate:
                            type: object
                      preferred_session_minutes:
                        type: integer
                      primary_target_skill:
                        type: string
                      required_goal_modules:
                        type: array
                      goal_modules:
                        type: object
                      baseline_tests:
                        type: object
                      mobility_checks:
                        type: object
                      weighted_baselines:
                        type: object
  /me/onboarding:
    get:
      operationId: getOnboardingState
      responses:
        200:
          description: Athlete onboarding.
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: object
                    properties:
                      current_level_tests:
                        type: object
                      age_years:
                        type: integer
                      height_value:
                        type: number
                      weight_trend:
                        type: string
                      pain_flags:
                        type: object
                      roadmap_suggestions:
                        type: object
                        properties:
                          version:
                            type: string
                          primary_goal:
                            type: object
                          eta_range:
                            type: object
                          intermediate:
                            type: object
                      primary_target_skill:
                        type: string
                      required_goal_modules:
                        type: array
                      goal_modules:
                        type: object
                      mobility_checks:
                        type: object
                      weighted_baselines:
                        type: object
                      is_complete:
                        type: boolean
                      missing_sections:
                        type: array
    patch:
      operationId: updateOnboardingState
      responses:
        200:
          description: Athlete onboarding.
          content:
            application/json:
              schema:
                type: object
                properties:
                  data:
                    type: object
                    properties:
                      current_level_tests:
                        type: object
                      age_years:
                        type: integer
                      height_value:
                        type: number
                      weight_trend:
                        type: string
                      pain_flags:
                        type: object
                      roadmap_suggestions:
                        type: object
                        properties:
                          version:
                            type: string
                          primary_goal:
                            type: object
                          eta_range:
                            type: object
                          intermediate:
                            type: object
                      primary_target_skill:
                        type: string
                      required_goal_modules:
                        type: array
                      goal_modules:
                        type: object
                      mobility_checks:
                        type: object
                      weighted_baselines:
                        type: object
                      is_complete:
                        type: boolean
                      missing_sections:
                        type: array
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
    expect(generated).toContain('export interface AthleteProfileResponse')
    expect(generated).toContain('export interface AthleteOnboardingResponse')
    expect(generated).toContain('export interface PainFlag')
    expect(generated).toContain('export interface GoalModule')
    expect(generated).toContain('readonly required_goal_modules: readonly string[]')
    expect(generated).toContain('readonly goal_modules: GoalModules')
    expect(generated).toContain('readonly weight_trend: string')
    expect(generated).toContain('readonly pain_flags: PainFlags')
    expect(generated).toContain('readonly rows:')
    expect(generated).toContain('readonly lower_body:')
    expect(generated).toContain('readonly passive_hang_seconds: number | null')
    expect(generated).toContain('readonly top_support_hold_seconds: number | null')
    expect(generated).toContain('readonly version: string')
    expect(generated).toContain('readonly eta_range: RoadmapEtaRange')
    expect(generated).toContain("readonly '/me'")
    expect(generated).toContain("readonly '/me/profile'")
    expect(generated).toContain("readonly '/me/onboarding'")
    expect(generated).toContain('readonly patch')
    expect(generated).toContain('readonly current_level_tests: OnboardingLevelTests')
    expect(generated).toContain('readonly is_complete: boolean')
    expect(generated).toContain("readonly status: 'ok'")
    expect(generated).toContain('readonly timestamp: string')
    expect(generated).not.toContain('example')
  })
})
