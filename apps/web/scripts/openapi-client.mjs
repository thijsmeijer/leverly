import { accessSync, constants, mkdirSync, readFileSync, writeFileSync } from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'

const currentDir = path.dirname(fileURLToPath(import.meta.url))

export const generatedTypesRelativePath = 'src/shared/api/leverlyApi/openapi/generated.ts'

export function resolveSpecPath({
  currentWorkingDirectory = process.cwd(),
  environment = process.env,
  scriptDirectory = currentDir,
} = {}) {
  const configuredSpecPath = environment.OPENAPI_SPEC_PATH ?? environment.LEVERLY_OPENAPI_SPEC_PATH
  const specPath = configuredSpecPath ?? path.resolve(scriptDirectory, '../../../docs/api/openapi.yaml')

  return path.isAbsolute(specPath) ? specPath : path.resolve(currentWorkingDirectory, specPath)
}

export function resolveGeneratedTypesPath({ currentWorkingDirectory = process.cwd(), environment = process.env } = {}) {
  const configuredOutputPath = environment.LEVERLY_OPENAPI_TYPES_PATH ?? generatedTypesRelativePath

  return path.isAbsolute(configuredOutputPath)
    ? configuredOutputPath
    : path.resolve(currentWorkingDirectory, configuredOutputPath)
}

export function readOpenApiSource(specPath) {
  accessSync(specPath, constants.R_OK)

  return readFileSync(specPath, 'utf8')
}

export function findContractProblems(openApiSource) {
  const requiredFragments = [
    ['OpenAPI 3.1 metadata', 'openapi: 3.1.0'],
    ['versioned API server', 'url: /api/v1'],
    ['health path', '  /health:'],
    ['health operation ID', 'operationId: getAPIHealthMetadata'],
    ['health 200 response', '200:'],
    ['health JSON content type', 'application/json:'],
    ['health status property', 'status:'],
    ['health status enum', '- ok'],
    ['health API version metadata', 'api_version:'],
    ['health timestamp metadata', 'timestamp:'],
    ['current user path', '  /me:'],
    ['current user operation ID', 'operationId: getTheCurrentUser'],
    ['current user data wrapper', 'data:'],
    ['current user id property', 'id:'],
    ['current user name property', 'name:'],
    ['current user email property', 'email:'],
    ['athlete profile path', '  /me/profile:'],
    ['athlete profile get operation ID', 'operationId: getAthleteProfile'],
    ['athlete profile update operation ID', 'operationId: updateAthleteProfile'],
    ['athlete profile display name property', 'display_name:'],
    ['athlete profile timezone property', 'timezone:'],
    ['athlete profile unit system property', 'unit_system:'],
    ['athlete profile age property', 'age_years:'],
    ['athlete profile height property', 'height_value:'],
    ['athlete profile roadmap suggestions property', 'roadmap_suggestions:'],
    ['athlete profile primary target skill property', 'primary_target_skill:'],
    ['athlete profile baseline tests property', 'baseline_tests:'],
    ['athlete profile mobility checks property', 'mobility_checks:'],
    ['athlete profile weighted baselines property', 'weighted_baselines:'],
    ['athlete profile preferred session property', 'preferred_session_minutes:'],
    ['athlete onboarding path', '  /me/onboarding:'],
    ['athlete onboarding get operation ID', 'operationId: getOnboardingState'],
    ['athlete onboarding update operation ID', 'operationId: updateOnboardingState'],
    ['athlete onboarding age property', 'age_years:'],
    ['athlete onboarding height property', 'height_value:'],
    ['athlete onboarding roadmap suggestions property', 'roadmap_suggestions:'],
    ['athlete onboarding level tests property', 'current_level_tests:'],
    ['athlete onboarding primary target skill property', 'primary_target_skill:'],
    ['athlete onboarding mobility checks property', 'mobility_checks:'],
    ['athlete onboarding weighted baselines property', 'weighted_baselines:'],
    ['athlete onboarding completion property', 'is_complete:'],
    ['athlete onboarding missing sections property', 'missing_sections:'],
  ]

  const problems = requiredFragments
    .filter(([, fragment]) => !openApiSource.includes(fragment))
    .map(([label]) => `Missing ${label}.`)

  if (/\bexamples?:\b/i.test(openApiSource)) {
    problems.push('Generated contract source must not contain examples.')
  }

  return problems
}

export function buildGeneratedTypes(openApiSource) {
  const problems = findContractProblems(openApiSource)

  if (problems.length > 0) {
    throw new Error(problems.join('\n'))
  }

  return `// Generated from the Leverly OpenAPI contract. Do not edit manually.
// Run \`pnpm api-client:generate\` from apps/web to refresh.

export interface HealthResponse {
  readonly status: 'ok'
  readonly meta: {
    readonly api_version: 'v1'
    readonly timestamp: string
  }
}

export interface CurrentUser {
  readonly id: string
  readonly name: string
  readonly email: string
}

export interface CurrentUserResponse {
  readonly data: CurrentUser
}

export interface MovementLimitation {
  readonly area: string
  readonly severity: string
  readonly status: string
  readonly notes: string | null
}

export interface PlacementLevelTests {
  readonly push_ups: {
    readonly max_strict_reps: number | null
  }
  readonly pull_ups: {
    readonly max_strict_reps: number | null
  }
  readonly dips: {
    readonly max_strict_reps: number | null
  }
  readonly squat: {
    readonly barbell_load_value: number | null
    readonly barbell_reps: number | null
  }
  readonly hollow_hold_seconds: number | null
}

export interface SkillStatus {
  readonly status: string
  readonly max_strict_reps?: number | null
  readonly best_hold_seconds?: number | null
  readonly notes?: string | null
}

export interface WeightedBaselineMovement {
  readonly movement: string
  readonly external_load_value?: number | null
  readonly reps?: number | null
  readonly rir?: number | null
}

export interface WeightedBaselines {
  readonly experience: string
  readonly unit: string
  readonly movements: readonly WeightedBaselineMovement[]
}

export interface RoadmapTrack {
  readonly skill: string
  readonly label: string
  readonly reason: string
  readonly base_focus_areas: readonly string[]
  readonly next_gate: string
  readonly compatible_secondary_skills: readonly string[]
}

export interface RoadmapSuggestions {
  readonly level: string
  readonly summary: string
  readonly body_context: {
    readonly notes: readonly string[]
  }
  readonly base_focus_areas: readonly string[]
  readonly unlocked_tracks: readonly RoadmapTrack[]
  readonly bridge_tracks: readonly RoadmapTrack[]
  readonly long_term_tracks: readonly RoadmapTrack[]
  readonly deferred_tracks: readonly RoadmapTrack[]
}

export interface AthleteProfile {
  readonly id: string
  readonly user_id: string
  readonly display_name: string
  readonly timezone: string
  readonly unit_system: string
  readonly age_years: number | null
  readonly training_age_months: number | null
  readonly experience_level: string
  readonly current_bodyweight_value: number | null
  readonly bodyweight_unit: string
  readonly height_value: number | null
  readonly height_unit: string
  readonly prior_sport_background: readonly string[]
  readonly primary_goal: string | null
  readonly secondary_goals: readonly string[]
  readonly target_skills: readonly string[]
  readonly primary_target_skill: string | null
  readonly secondary_target_skills: readonly string[]
  readonly long_term_target_skills: readonly string[]
  readonly base_focus_areas: readonly string[]
  readonly roadmap_suggestions: RoadmapSuggestions
  readonly available_equipment: readonly string[]
  readonly training_locations: readonly string[]
  readonly movement_limitations: readonly MovementLimitation[]
  readonly baseline_tests: PlacementLevelTests
  readonly skill_statuses: Readonly<Record<string, SkillStatus>>
  readonly mobility_checks: Readonly<Record<string, string>>
  readonly weighted_baselines: WeightedBaselines
  readonly injury_notes: string | null
  readonly preferred_training_days: readonly string[]
  readonly preferred_session_minutes: number | null
  readonly weekly_session_goal: number | null
  readonly progression_pace: string
  readonly intensity_preference: string
  readonly effort_tracking_preference: string
  readonly deload_preference: string
  readonly session_structure_preferences: readonly string[]
}

export interface AthleteProfileResponse {
  readonly data: AthleteProfile
}

export interface OnboardingLevelTests {
  readonly push_ups: {
    readonly max_strict_reps: number | null
  }
  readonly pull_ups: {
    readonly max_strict_reps: number | null
  }
  readonly dips: {
    readonly max_strict_reps: number | null
  }
  readonly squat: {
    readonly barbell_load_value: number | null
    readonly barbell_reps: number | null
  }
  readonly hollow_hold_seconds: number | null
}

export interface OnboardingSkillStatus {
  readonly status: string
  readonly max_strict_reps?: number | null
  readonly best_hold_seconds?: number | null
  readonly notes?: string | null
}

export interface AthleteOnboarding {
  readonly id: string
  readonly user_id: string
  readonly age_years: number | null
  readonly training_age_months: number | null
  readonly experience_level: string
  readonly current_bodyweight_value: number | null
  readonly bodyweight_unit: string
  readonly height_value: number | null
  readonly height_unit: string
  readonly prior_sport_background: readonly string[]
  readonly primary_goal: string | null
  readonly secondary_goals: readonly string[]
  readonly target_skills: readonly string[]
  readonly primary_target_skill: string | null
  readonly secondary_target_skills: readonly string[]
  readonly long_term_target_skills: readonly string[]
  readonly base_focus_areas: readonly string[]
  readonly roadmap_suggestions: RoadmapSuggestions
  readonly available_equipment: readonly string[]
  readonly training_locations: readonly string[]
  readonly preferred_training_days: readonly string[]
  readonly preferred_session_minutes: number | null
  readonly weekly_session_goal: number | null
  readonly current_level_tests: OnboardingLevelTests
  readonly skill_statuses: Readonly<Record<string, OnboardingSkillStatus>>
  readonly mobility_checks: Readonly<Record<string, string>>
  readonly weighted_baselines: WeightedBaselines
  readonly readiness_rating: number | null
  readonly sleep_quality: number | null
  readonly soreness_level: number | null
  readonly pain_level: number | null
  readonly pain_areas: readonly string[]
  readonly pain_notes: string | null
  readonly starter_plan_key: string | null
  readonly is_complete: boolean
  readonly completed_at: string | null
  readonly missing_sections: readonly string[]
}

export interface AthleteOnboardingResponse {
  readonly data: AthleteOnboarding
}

export interface paths {
  readonly '/me': {
    readonly get: {
      readonly responses: {
        readonly 200: {
          readonly content: {
            readonly 'application/json': CurrentUserResponse
          }
        }
      }
    }
  }
  readonly '/me/onboarding': {
    readonly get: {
      readonly responses: {
        readonly 200: {
          readonly content: {
            readonly 'application/json': AthleteOnboardingResponse
          }
        }
      }
    }
    readonly patch: {
      readonly responses: {
        readonly 200: {
          readonly content: {
            readonly 'application/json': AthleteOnboardingResponse
          }
        }
      }
    }
  }
  readonly '/me/profile': {
    readonly get: {
      readonly responses: {
        readonly 200: {
          readonly content: {
            readonly 'application/json': AthleteProfileResponse
          }
        }
      }
    }
    readonly patch: {
      readonly responses: {
        readonly 200: {
          readonly content: {
            readonly 'application/json': AthleteProfileResponse
          }
        }
      }
    }
  }
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
`
}

export function writeGeneratedTypes(openApiSource, outputPath) {
  const generatedTypes = buildGeneratedTypes(openApiSource)

  mkdirSync(path.dirname(outputPath), { recursive: true })
  writeFileSync(outputPath, generatedTypes, 'utf8')

  return generatedTypes
}

export function findGeneratedTypesProblems(openApiSource, generatedTypesPath) {
  const expected = buildGeneratedTypes(openApiSource)

  try {
    accessSync(generatedTypesPath, constants.R_OK)
  } catch {
    return [`Generated OpenAPI types are missing at ${generatedTypesPath}.`]
  }

  const actual = readFileSync(generatedTypesPath, 'utf8')

  if (actual !== expected) {
    return [`Generated OpenAPI types are stale at ${generatedTypesPath}.`]
  }

  return []
}
