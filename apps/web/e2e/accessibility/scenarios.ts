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
  },
  {
    name: 'profile settings',
    path: '/app/settings/profile',
    setup: setupAuthenticatedProfile,
  },
  {
    name: 'equipment settings',
    path: '/app/settings/equipment',
    setup: setupAuthenticatedProfile,
  },
]

async function setupAuthenticatedProfile(page: Page): Promise<void> {
  await page.route('**/api/v1/me/profile', async (route) => {
    await route.fulfill({
      contentType: 'application/json',
      json: profileResponse(),
    })
  })
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

function profileResponse() {
  return {
    data: {
      available_equipment: ['pull_up_bar', 'rings'],
      bodyweight_unit: 'kg',
      current_bodyweight_value: 72.5,
      deload_preference: 'auto',
      display_name: 'Ada Athlete',
      effort_tracking_preference: 'rir',
      experience_level: 'intermediate',
      id: '01kb0b6h4az3er8g7vnh9k5m1a',
      injury_notes: 'No sharp pain.',
      intensity_preference: 'auto',
      movement_limitations: [
        {
          area: 'wrist',
          notes: 'Needs warm-up.',
          severity: 'mild',
          status: 'recurring',
        },
      ],
      preferred_session_minutes: 60,
      preferred_training_days: ['monday', 'wednesday'],
      preferred_training_time: 'evening',
      primary_goal: 'skill',
      progression_pace: 'balanced',
      secondary_goals: ['strength'],
      session_structure_preferences: ['skill_first'],
      target_skills: ['freestanding handstand', 'strict muscle-up'],
      timezone: 'Europe/Amsterdam',
      training_age_months: 18,
      training_locations: ['home'],
      unit_system: 'metric',
      user_id: '01kaw4k7q6v7m9r6rddm4xyf2p',
      weekly_session_goal: 3,
    },
  }
}
