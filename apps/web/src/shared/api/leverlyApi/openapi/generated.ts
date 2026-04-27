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

export interface PainFlag {
  readonly severity: string
  readonly status: string
  readonly notes: string | null
}

export type PainFlags = Readonly<Record<string, PainFlag>>

export interface GoalModule {
  readonly highest_progression: string
  readonly metric_type: string
  readonly reps: number | null
  readonly hold_seconds: number | null
  readonly load_value: number | null
  readonly load_unit: string
  readonly quality: string
  readonly notes: string | null
}

export type GoalModules = Readonly<Record<string, GoalModule>>

export interface PlacementLevelTests {
  readonly push_ups: {
    readonly max_strict_reps: number | null
  }
  readonly pull_ups: {
    readonly max_strict_reps: number | null
    readonly fallback_variant: string
    readonly fallback_reps: number | null
    readonly fallback_seconds: number | null
  }
  readonly dips: {
    readonly max_strict_reps: number | null
    readonly fallback_variant: string
    readonly fallback_reps: number | null
    readonly fallback_seconds: number | null
  }
  readonly squat: {
    readonly barbell_load_value: number | null
    readonly barbell_reps: number | null
  }
  readonly rows: {
    readonly variant: string
    readonly max_reps: number | null
  }
  readonly lower_body: {
    readonly variant: string
    readonly load_value: number | null
    readonly load_unit: string
    readonly reps: number | null
  }
  readonly hollow_hold_seconds: number | null
  readonly passive_hang_seconds: number | null
  readonly top_support_hold_seconds: number | null
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

export interface RoadmapNode {
  readonly id: string
  readonly label: string
}

export interface RoadmapEtaRange {
  readonly min_weeks: number | null
  readonly max_weeks: number | null
  readonly p50_weeks?: number | null
  readonly p80_weeks?: number | null
  readonly label: string
  readonly confidence?: number | null
  readonly modifiers?: readonly string[]
}

export interface RoadmapConfidence {
  readonly level: string
  readonly score: number | null
  readonly reasons: readonly string[]
}

export interface RoadmapBlocker {
  readonly key: string
  readonly label: string
  readonly severity: string
  readonly message: string
}

export interface RoadmapUnlockCondition {
  readonly skill: string
  readonly label: string
  readonly status: string
}

export interface RoadmapGoal {
  readonly skill: string
  readonly label: string
  readonly lane: string
  readonly current_progression_node: RoadmapNode
  readonly next_node: RoadmapNode
  readonly next_milestone: RoadmapNode
  readonly eta_range: RoadmapEtaRange
  readonly confidence: RoadmapConfidence
  readonly blockers: readonly RoadmapBlocker[]
  readonly unlock_conditions: readonly RoadmapUnlockCondition[]
  readonly compatibility_tags: readonly string[]
  readonly explanation: string
}

export interface RoadmapFoundationLane {
  readonly slug: string
  readonly label: string
  readonly focus_areas: readonly string[]
  readonly current_progression_node: RoadmapNode
  readonly next_node: RoadmapNode
  readonly next_milestone: RoadmapNode
}

export interface RoadmapExplanation {
  readonly summary: string
  readonly primary_now: string
  readonly why_this_goal: readonly string[]
  readonly what_is_missing: readonly string[]
  readonly this_block_should_improve: readonly string[]
  readonly not_trained_yet: readonly string[]
  readonly what_would_change_recommendation: readonly string[]
  readonly watch_out_for: readonly string[]
  readonly fallback: string
}

export interface RoadmapDomainBottleneck {
  readonly domain: string
  readonly label: string
  readonly score: number
  readonly confidence: number | null
  readonly reason: string
  readonly missing_inputs: readonly string[]
}

export interface RoadmapCurrentBlockFocus {
  readonly label: string
  readonly eta_range: RoadmapEtaRange
  readonly lanes: readonly string[]
  readonly focus_areas: readonly string[]
  readonly should_improve: readonly string[]
  readonly retest_cadence: readonly string[]
}

export interface RoadmapIntermediate {
  readonly progression_graph_placement: Readonly<Record<string, unknown>>
  readonly placements?: Readonly<Record<string, unknown>>
  readonly domain_scores: Readonly<Record<string, unknown>>
  readonly domain_uncertainty: Readonly<Record<string, unknown>>
  readonly hard_gate_results: readonly Readonly<Record<string, unknown>>[]
  readonly readiness_scores: readonly Readonly<Record<string, unknown>>[]
  readonly compatibility_costs: readonly Readonly<Record<string, unknown>>[]
  readonly lane_selection?: Readonly<Record<string, unknown>>
  readonly roadmap_layers?: Readonly<Record<string, unknown>>
  readonly eta_modifiers: readonly Readonly<Record<string, unknown>>[]
}

export interface RoadmapGoalCandidate {
  readonly skill: string
  readonly label: string
  readonly role: string
  readonly status: string
  readonly readiness_score: number
  readonly confidence: number | null
  readonly stress_class: string
  readonly stress_tags: readonly string[]
  readonly reason: string
  readonly blockers: readonly string[]
  readonly unlock_conditions: readonly string[]
  readonly base_focus_areas: readonly string[]
  readonly next_gate: string
  readonly compatible_with_primary: boolean | null
  readonly compatibility_reason: string
}

export interface RoadmapGoalCandidates {
  readonly primary: readonly RoadmapGoalCandidate[]
  readonly secondary: readonly RoadmapGoalCandidate[]
  readonly accessories: readonly RoadmapGoalCandidate[]
  readonly future: readonly RoadmapGoalCandidate[]
  readonly foundation: readonly RoadmapGoalCandidate[]
}

export interface RoadmapPortfolioTrack {
  readonly skill_track_id: string
  readonly display_name: string
  readonly current_node: RoadmapNode
  readonly next_node: RoadmapNode
  readonly target_node: RoadmapNode
  readonly mode: string
  readonly weekly_exposures: number
  readonly estimated_minutes_per_week: number
  readonly primary_stress_axes: readonly string[]
  readonly eta_to_next_node: RoadmapEtaRange
  readonly confidence: RoadmapConfidence
  readonly modules: readonly Readonly<Record<string, unknown>>[]
  readonly why_included: readonly string[]
  readonly why_not_higher_priority: readonly string[]
}

export interface RoadmapPortfolioScheduledModule {
  readonly module_id: string
  readonly skill_track_id: string
  readonly title: string
  readonly purpose: string
  readonly pattern: string
  readonly intensity_tier: string
  readonly source_mode: string
  readonly slot: string
  readonly slot_rank: number
  readonly order: number
  readonly exposure_index: number
  readonly estimated_minutes: number
  readonly stress_vector: Readonly<Record<string, number>>
}

export interface RoadmapPortfolioStressAxis {
  readonly axis: string
  readonly load: number
  readonly budget: number
  readonly status: string
}

export interface RoadmapPortfolioScheduleStressLedger {
  readonly axes: readonly RoadmapPortfolioStressAxis[]
  readonly warnings: readonly string[]
}

export interface RoadmapPortfolioDayTimeLedger {
  readonly estimated_minutes: number
  readonly budget_minutes: number
  readonly overflow_minutes: number
  readonly status: string
}

export interface RoadmapPortfolioScheduledDay {
  readonly day_index: number
  readonly label: string
  readonly day_type: string
  readonly modules: readonly RoadmapPortfolioScheduledModule[]
  readonly stress_ledger: RoadmapPortfolioScheduleStressLedger
  readonly time_ledger: RoadmapPortfolioDayTimeLedger
  readonly warnings: readonly string[]
}

export interface RoadmapPortfolioRestDay {
  readonly day_index: number
  readonly label: string
  readonly day_type: string
}

export interface RoadmapPortfolioScheduleTemplate {
  readonly sessions_per_week: number
  readonly day_types: readonly string[]
  readonly slot_order: readonly string[]
}

export interface RoadmapPortfolioWeeklyTimeLedger {
  readonly estimated_minutes_per_week: number
  readonly budget_minutes_per_week: number
  readonly overflow_minutes_per_week: number
}

export interface RoadmapPortfolioWeeklySchedule {
  readonly days: readonly RoadmapPortfolioScheduledDay[]
  readonly rest_days: readonly RoadmapPortfolioRestDay[]
  readonly template: RoadmapPortfolioScheduleTemplate
  readonly stress_ledger: RoadmapPortfolioScheduleStressLedger
  readonly time_ledger: RoadmapPortfolioWeeklyTimeLedger
  readonly warnings: readonly string[]
}

export interface RoadmapPortfolioStressLedger {
  readonly axes: readonly RoadmapPortfolioStressAxis[]
  readonly notes: readonly string[]
}

export interface RoadmapPortfolioTimeLedger {
  readonly max_sessions_per_week: number
  readonly estimated_minutes_per_week: number
  readonly remaining_minutes_per_week: number
  readonly notes: readonly string[]
}

export interface RoadmapPortfolioProgressionRule {
  readonly module_id: string
  readonly skill_track_id: string
  readonly title: string
  readonly rule_type: string
  readonly metric: string
  readonly progression_allowed: boolean
  readonly next_action: string
  readonly success_requirements: readonly string[]
  readonly allowed_levers: readonly string[]
  readonly only_one_major_lever: boolean
  readonly pain_rule: string
  readonly next_adjustment: Readonly<Record<string, unknown>>
  readonly deload_triggers: readonly string[]
}

export interface RoadmapPortfolioPhasePlan {
  readonly phase_id: string
  readonly duration_weeks: {
    readonly min: number
    readonly target: number
    readonly max: number
  }
  readonly duration_reason: string
  readonly weekly_emphasis: readonly string[]
  readonly roles: Readonly<Record<string, readonly Readonly<Record<string, unknown>>[]>>
  readonly foundation_layer: readonly Readonly<Record<string, unknown>>[]
  readonly retest_timing: Readonly<Record<string, unknown>>
  readonly deload_guidance: Readonly<Record<string, unknown>>
  readonly progression_rules: readonly RoadmapPortfolioProgressionRule[]
  readonly safety_notes: readonly string[]
}

export interface RoadmapPortfolioExplanation {
  readonly summary: string
  readonly why_this_mix: readonly string[]
  readonly watch_out_for: readonly string[]
  readonly fallback: string
}

export interface RoadmapActiveSkillPortfolio {
  readonly development_tracks: readonly RoadmapPortfolioTrack[]
  readonly technical_practice_tracks: readonly RoadmapPortfolioTrack[]
  readonly accessory_tracks: readonly RoadmapPortfolioTrack[]
  readonly maintenance_tracks: readonly RoadmapPortfolioTrack[]
  readonly foundation_tracks: readonly RoadmapPortfolioTrack[]
  readonly foundation_modules: readonly Readonly<Record<string, unknown>>[]
  readonly future_queue: readonly RoadmapPortfolioTrack[]
  readonly weekly_schedule: RoadmapPortfolioWeeklySchedule
  readonly stress_ledger: RoadmapPortfolioStressLedger
  readonly stress_budget: Readonly<Record<string, unknown>>
  readonly module_compatibility: readonly Readonly<Record<string, unknown>>[]
  readonly optimizer: Readonly<Record<string, unknown>>
  readonly phase_plan: RoadmapPortfolioPhasePlan
  readonly time_ledger: RoadmapPortfolioTimeLedger
  readonly explanation: RoadmapPortfolioExplanation
}

export interface RoadmapPortfolioGoalChoices {
  readonly development: readonly string[]
  readonly technical_practice: readonly string[]
  readonly accessories: readonly string[]
  readonly future: readonly string[]
  readonly blocked: readonly string[]
}

export interface RoadmapPortfolioFoundationLayer {
  readonly summary: string
  readonly focus_areas: readonly string[]
  readonly tracks: readonly RoadmapPortfolioTrack[]
  readonly modules: readonly Readonly<Record<string, unknown>>[]
}

export interface RoadmapSuggestions {
  readonly version: string
  readonly source_version: string
  readonly level: string
  readonly summary: string
  readonly active_skill_portfolio: RoadmapActiveSkillPortfolio
  readonly onboarding_goal_choices: RoadmapPortfolioGoalChoices
  readonly foundation_layer: RoadmapPortfolioFoundationLayer
  readonly long_term_aspirations: readonly RoadmapPortfolioTrack[]
  readonly not_recommended_now: readonly RoadmapPortfolioTrack[]
  readonly blocked: readonly RoadmapPortfolioTrack[]
  readonly pending_tests: readonly Readonly<Record<string, unknown>>[]
  readonly goal_candidates: RoadmapGoalCandidates
  readonly primary_goal: RoadmapGoal | null
  readonly compatible_secondary_goal: RoadmapGoal | null
  readonly foundation_lane: RoadmapFoundationLane
  readonly deferred_goals: readonly RoadmapGoal[]
  readonly current_progression_node: RoadmapNode
  readonly next_node: RoadmapNode
  readonly next_milestone: RoadmapNode
  readonly eta_range: RoadmapEtaRange
  readonly confidence: RoadmapConfidence
  readonly blockers: readonly RoadmapBlocker[]
  readonly unlock_conditions: readonly RoadmapUnlockCondition[]
  readonly compatibility_tags: readonly string[]
  readonly domain_bottlenecks: readonly RoadmapDomainBottleneck[]
  readonly current_block_focus: RoadmapCurrentBlockFocus
  readonly explanation: RoadmapExplanation
  readonly intermediate?: RoadmapIntermediate
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
  readonly weight_trend: string
  readonly prior_sport_background: readonly string[]
  readonly primary_goal: string | null
  readonly secondary_goals: readonly string[]
  readonly target_skills: readonly string[]
  readonly primary_target_skill: string | null
  readonly secondary_target_skills: readonly string[]
  readonly long_term_target_skills: readonly string[]
  readonly base_focus_areas: readonly string[]
  readonly required_goal_modules: readonly string[]
  readonly goal_modules: GoalModules
  readonly roadmap_suggestions: RoadmapSuggestions
  readonly available_equipment: readonly string[]
  readonly training_locations: readonly string[]
  readonly movement_limitations: readonly MovementLimitation[]
  readonly pain_flags: PainFlags
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
    readonly fallback_variant: string
    readonly fallback_reps: number | null
    readonly fallback_seconds: number | null
  }
  readonly dips: {
    readonly max_strict_reps: number | null
    readonly fallback_variant: string
    readonly fallback_reps: number | null
    readonly fallback_seconds: number | null
  }
  readonly squat: {
    readonly barbell_load_value: number | null
    readonly barbell_reps: number | null
  }
  readonly rows: {
    readonly variant: string
    readonly max_reps: number | null
  }
  readonly lower_body: {
    readonly variant: string
    readonly load_value: number | null
    readonly load_unit: string
    readonly reps: number | null
  }
  readonly hollow_hold_seconds: number | null
  readonly passive_hang_seconds: number | null
  readonly top_support_hold_seconds: number | null
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
  readonly weight_trend: string
  readonly prior_sport_background: readonly string[]
  readonly primary_goal: string | null
  readonly secondary_goals: readonly string[]
  readonly target_skills: readonly string[]
  readonly primary_target_skill: string | null
  readonly secondary_target_skills: readonly string[]
  readonly long_term_target_skills: readonly string[]
  readonly base_focus_areas: readonly string[]
  readonly required_goal_modules: readonly string[]
  readonly goal_modules: GoalModules
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
  readonly pain_flags: PainFlags
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
