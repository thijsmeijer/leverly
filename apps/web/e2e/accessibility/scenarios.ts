import type { Page } from '@playwright/test'

export type AccessibilityScenario = {
  name: string
  path: string
  root?: string
  setup?: (page: Page) => Promise<void>
}

export const accessibilityScenarios: AccessibilityScenario[] = [
  {
    name: 'dashboard',
    path: '/app/dashboard',
    setup: setupAuthenticatedCompletedOnboarding,
  },
  {
    name: 'onboarding',
    path: '/onboarding',
    setup: setupAuthenticatedOnboardingDraft,
  },
  {
    name: 'profile settings',
    path: '/app/settings/profile',
    setup: setupAuthenticatedProfileWithCompletedOnboarding,
  },
  {
    name: 'equipment settings',
    path: '/app/settings/equipment',
    setup: setupAuthenticatedProfileWithCompletedOnboarding,
  },
]

async function setupAuthenticatedCompletedOnboarding(page: Page): Promise<void> {
  await setupAuthenticatedUser(page)
  await page.route('**/api/v1/me/onboarding', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      json: onboardingResponse({ is_complete: true }),
    })
  })
}

async function setupAuthenticatedOnboardingDraft(page: Page): Promise<void> {
  await setupAuthenticatedUser(page)
  await page.route('**/api/v1/me/onboarding', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      json: onboardingResponse(),
    })
  })
}

async function setupAuthenticatedProfileWithCompletedOnboarding(page: Page): Promise<void> {
  await setupAuthenticatedCompletedOnboarding(page)
  await page.route('**/api/v1/me/profile', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      json: profileResponse(),
    })
  })
}

async function setupAuthenticatedUser(page: Page): Promise<void> {
  await page.route('**/api/v1/me', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      json: {
        data: {
          email: 'ada@example.com',
          id: '01kaw4k7q6v7m9r6rddm4xyf2p',
          name: 'Ada Athlete',
        },
      },
    })
  })
}

function onboardingResponse(overrides: Partial<Record<string, unknown>> = {}) {
  return {
    data: {
      age_years: 29,
      available_equipment: ['pull_up_bar', 'rings'],
      base_focus_areas: ['pull_capacity', 'core_bodyline'],
      bodyweight_unit: 'kg',
      completed_at: null,
      current_bodyweight_value: 72.5,
      current_level_tests: {
        dips: {
          fallback_reps: 5,
          fallback_seconds: null,
          fallback_variant: 'assisted',
          max_strict_reps: 6,
        },
        hollow_hold_seconds: 35,
        lower_body: {
          load_unit: 'kg',
          load_value: null,
          reps: 12,
          variant: 'split_squat',
        },
        passive_hang_seconds: 45,
        pull_ups: {
          fallback_reps: null,
          fallback_seconds: 6,
          fallback_variant: 'eccentric',
          max_strict_reps: 4,
        },
        push_ups: {
          max_strict_reps: 18,
        },
        rows: {
          max_reps: 12,
          variant: 'ring_row',
        },
        squat: {
          barbell_load_value: 100,
          barbell_reps: 5,
        },
        top_support_hold_seconds: 25,
      },
      experience_level: 'intermediate',
      height_unit: 'cm',
      height_value: 178,
      id: '01kb0b6h4az3er8g7vnh9k5m1a',
      is_complete: false,
      long_term_target_skills: ['planche'],
      required_goal_modules: ['inversion'],
      goal_modules: {
        inversion: {
          highest_progression: 'freestanding_kick_up',
          metric_type: 'hold_seconds',
          reps: null,
          hold_seconds: 20,
          load_value: null,
          load_unit: 'kg',
          quality: 'solid',
          notes: null,
        },
      },
      missing_sections: [],
      mobility_checks: {
        ankle_dorsiflexion: 'limited',
        pancake_compression: 'not_tested',
        shoulder_extension: 'clear',
        shoulder_flexion: 'clear',
        wrist_extension: 'limited',
      },
      pain_areas: ['wrist'],
      pain_flags: painFlags(),
      pain_level: 2,
      pain_notes: 'Wrists need warm-up.',
      preferred_session_minutes: 60,
      preferred_training_days: ['monday', 'wednesday', 'friday'],
      prior_sport_background: ['strength_training'],
      primary_goal: 'skill',
      primary_target_skill: 'handstand',
      readiness_rating: 4,
      roadmap_suggestions: roadmapSuggestions(),
      secondary_goals: ['strength'],
      secondary_target_skills: ['strict_pull_up'],
      skill_statuses: {
        handstand: {
          best_hold_seconds: 20,
          status: 'freestanding_kick_up',
        },
      },
      sleep_quality: 4,
      soreness_level: 2,
      starter_plan_key: 'skill_strength_split',
      target_skills: ['handstand'],
      training_age_months: 18,
      training_locations: ['home'],
      user_id: '01kaw4k7q6v7m9r6rddm4xyf2p',
      weighted_baselines: {
        experience: 'repetition_work',
        movements: [{ external_load_value: 10, movement: 'weighted_pull_up', reps: 5, rir: 2 }],
        unit: 'kg',
      },
      weekly_session_goal: 3,
      weight_trend: 'maintaining',
      ...overrides,
    },
  }
}

function painFlags() {
  return {
    ankle: { notes: null, severity: 'none', status: 'none' },
    elbow: { notes: null, severity: 'none', status: 'none' },
    knee: { notes: null, severity: 'none', status: 'none' },
    low_back: { notes: null, severity: 'none', status: 'none' },
    shoulder: { notes: null, severity: 'none', status: 'none' },
    wrist: { notes: 'Wrists need warm-up.', severity: 'mild', status: 'recurring' },
  }
}

function profileResponse() {
  return {
    data: {
      age_years: 29,
      available_equipment: ['pull_up_bar', 'rings'],
      base_focus_areas: ['pull_capacity', 'core_bodyline'],
      baseline_tests: {
        arch_hold_seconds: 25,
        dead_hang_seconds: 30,
        dips: { max_strict_reps: 6, progression: 'bar_dip', support_hold_seconds: 25 },
        hollow_hold_seconds: 35,
        l_sit_hold_seconds: 8,
        pull_ups: { assistance: null, form_quality: 4, max_strict_reps: 4, progression: 'strict_pull_up' },
        push_ups: { form_quality: 4, max_strict_reps: 18, progression: 'strict_push_up' },
        rows: { max_strict_reps: 12, progression: 'inverted_row' },
        squat: { max_reps: 20, progression: 'split_squat' },
        support_hold_seconds: 25,
        wall_handstand_seconds: 20,
      },
      bodyweight_unit: 'kg',
      current_bodyweight_value: 72.5,
      deload_preference: 'auto',
      display_name: 'Ada Athlete',
      effort_tracking_preference: 'rir',
      experience_level: 'intermediate',
      height_unit: 'cm',
      height_value: 178,
      id: '01kb0b6h4az3er8g7vnh9k5m1a',
      injury_notes: 'No sharp pain.',
      intensity_preference: 'auto',
      long_term_target_skills: ['planche'],
      required_goal_modules: ['inversion'],
      goal_modules: {
        inversion: {
          highest_progression: 'freestanding_kick_up',
          metric_type: 'hold_seconds',
          reps: null,
          hold_seconds: 20,
          load_value: null,
          load_unit: 'kg',
          quality: 'solid',
          notes: null,
        },
      },
      movement_limitations: [
        {
          area: 'wrist',
          notes: 'Needs warm-up.',
          severity: 'mild',
          status: 'recurring',
        },
      ],
      mobility_checks: {
        ankle_dorsiflexion: 'limited',
        pancake_compression: 'not_tested',
        shoulder_extension: 'clear',
        shoulder_flexion: 'clear',
        wrist_extension: 'limited',
      },
      preferred_session_minutes: 60,
      preferred_training_days: ['monday', 'wednesday'],
      prior_sport_background: ['strength_training'],
      primary_goal: 'skill',
      primary_target_skill: 'handstand',
      progression_pace: 'balanced',
      roadmap_suggestions: roadmapSuggestions(),
      secondary_goals: ['strength'],
      secondary_target_skills: ['strict_pull_up'],
      session_structure_preferences: ['skill_first'],
      skill_statuses: {
        handstand: {
          best_hold_seconds: 20,
          status: 'assisted',
        },
      },
      target_skills: ['handstand'],
      timezone: 'Europe/Amsterdam',
      training_age_months: 18,
      training_locations: ['home'],
      unit_system: 'metric',
      user_id: '01kaw4k7q6v7m9r6rddm4xyf2p',
      weighted_baselines: {
        experience: 'repetition_work',
        movements: [{ external_load_value: 10, movement: 'weighted_pull_up', reps: 5, rir: 2 }],
        unit: 'kg',
      },
      weekly_session_goal: 3,
    },
  }
}

function roadmapSuggestions() {
  return {
    base_focus_areas: ['pull_capacity', 'core_bodyline'],
    body_context: { notes: [] },
    bridge_tracks: [
      {
        base_focus_areas: ['pull_capacity', 'row_volume'],
        compatible_secondary_skills: ['l_sit'],
        label: 'Pull-up',
        next_gate: 'Build toward 3 clean sets of 6 to 8.',
        reason: 'Pull-ups are already in range for direct progression.',
        skill: 'strict_pull_up',
      },
    ],
    deferred_tracks: [],
    level: 'intermediate',
    long_term_tracks: [],
    summary: 'You have enough base strength for a focused skill roadmap plus one light secondary exposure.',
    unlocked_tracks: [
      {
        base_focus_areas: ['handstand_line', 'core_bodyline'],
        compatible_secondary_skills: ['l_sit'],
        label: 'Handstand',
        next_gate: 'Build a clean wall line and controlled balance entries.',
        reason: 'Your wall handstand is ready for regular handstand practice.',
        skill: 'handstand',
      },
    ],
  }
}
