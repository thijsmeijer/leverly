// Generated from the Leverly OpenAPI contract. Do not edit manually.
// Run `pnpm api-client:generate` from apps/web to refresh.

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

export interface AthleteProfile {
  readonly id: string
  readonly user_id: string
  readonly display_name: string
  readonly timezone: string
  readonly unit_system: string
  readonly training_age_months: number | null
  readonly experience_level: string
  readonly current_bodyweight_value: number | null
  readonly bodyweight_unit: string
  readonly primary_goal: string | null
  readonly secondary_goals: readonly string[]
  readonly target_skills: readonly string[]
  readonly available_equipment: readonly string[]
  readonly training_locations: readonly string[]
  readonly movement_limitations: readonly MovementLimitation[]
  readonly injury_notes: string | null
  readonly preferred_training_days: readonly string[]
  readonly preferred_session_minutes: number | null
  readonly weekly_session_goal: number | null
  readonly preferred_training_time: string
  readonly progression_pace: string
  readonly intensity_preference: string
  readonly effort_tracking_preference: string
  readonly deload_preference: string
  readonly session_structure_preferences: readonly string[]
}

export interface AthleteProfileResponse {
  readonly data: AthleteProfile
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
