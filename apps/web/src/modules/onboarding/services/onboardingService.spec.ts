import { afterEach, describe, expect, it, vi } from 'vitest'
import { configureLeverlyApiClient, resetLeverlyApiClient } from '@/shared/api/leverlyApi/runtimeClient'
import { jsonResponse } from '@/tests/http'

import {
  defaultOnboardingForm,
  fetchOnboarding,
  OnboardingValidationError,
  saveOnboarding,
  validateOnboardingStep,
} from './onboardingService'

describe('onboardingService', () => {
  afterEach(() => {
    resetLeverlyApiClient()
  })

  it('maps onboarding API state into a resumable form', async () => {
    configureLeverlyApiClient({
      fetcher: vi.fn<typeof fetch>().mockResolvedValue(jsonResponse(onboardingResponse())),
    })

    await expect(fetchOnboarding()).resolves.toMatchObject({
      form: {
        availableEquipment: ['pull_up_bar', 'rings'],
        currentLevelTests: {
          hollowHoldSeconds: '35',
          pullUpMaxReps: '4',
          pullUpProgression: 'strict_pull_up',
          pushUpMaxReps: '18',
          squatMaxReps: '20',
          squatProgression: 'split_squat',
        },
        targetSkills: ['strict_pull_up', 'handstand'],
      },
      isComplete: false,
      onboardingId: '01kb0b6h4az3er8g7vnh9k5m1a',
    })
  })

  it('serializes draft saves and completion to the onboarding endpoint', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse(onboardingResponse({ is_complete: true })))

    configureLeverlyApiClient({
      fetcher,
      getCsrfToken: () => 'csrf-token',
    })

    const form = {
      ...defaultOnboardingForm(),
      availableEquipment: ['pull_up_bar', 'rings'],
      currentLevelTests: {
        hollowHoldSeconds: '35',
        pullUpMaxReps: '4',
        pullUpProgression: 'strict_pull_up',
        pushUpMaxReps: '18',
        squatMaxReps: '20',
        squatProgression: 'split_squat',
      },
      preferredSessionMinutes: '60',
      preferredTrainingDays: ['monday', 'wednesday', 'friday'],
      targetSkills: ['strict_pull_up', 'handstand'],
      trainingLocations: ['home'],
    }

    await expect(saveOnboarding(form, { complete: true })).resolves.toMatchObject({
      isComplete: true,
    })

    expect(fetcher).toHaveBeenNthCalledWith(
      2,
      '/api/v1/me/onboarding',
      expect.objectContaining({
        body: expect.stringContaining('"complete":true'),
        method: 'PATCH',
      }),
    )
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"target_skills":["strict_pull_up","handstand"]')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"hollow_hold_seconds":35')
  })

  it('maps server validation errors to onboarding fields', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(
        jsonResponse(
          {
            errors: {
              target_skills: ['Choose a target skill.'],
            },
            message: 'The given data was invalid.',
          },
          { status: 422 },
        ),
      )

    configureLeverlyApiClient({
      fetcher,
    })

    await expect(saveOnboarding(defaultOnboardingForm(), { complete: true })).rejects.toMatchObject({
      errors: {
        targetSkills: 'Choose a target skill.',
      },
    } satisfies Pick<OnboardingValidationError, 'errors'>)
  })

  it('validates required step inputs locally', () => {
    expect(
      validateOnboardingStep(
        {
          ...defaultOnboardingForm(),
          currentLevelTests: {
            ...defaultOnboardingForm().currentLevelTests,
            hollowHoldSeconds: '',
            pullUpProgression: '',
            pushUpMaxReps: '',
            squatProgression: '',
          },
          targetSkills: [],
        },
        'level',
      ),
    ).toMatchObject({
      'currentLevelTests.hollowHoldSeconds': 'Enter a number from 0 to 600.',
      'currentLevelTests.pullUpProgression': 'Choose your current pulling level or add strict reps.',
      'currentLevelTests.pushUpMaxReps': 'Enter a number from 0 to 200.',
      'currentLevelTests.squatProgression': 'Choose your current squat level or add reps.',
    })
  })
})

function onboardingResponse(overrides: Partial<Record<string, unknown>> = {}) {
  return {
    data: {
      available_equipment: ['pull_up_bar', 'rings'],
      completed_at: null,
      current_level_tests: {
        hollow_hold_seconds: 35,
        pull_ups: {
          max_strict_reps: 4,
          progression: 'strict_pull_up',
        },
        push_ups: {
          max_strict_reps: 18,
        },
        squat: {
          max_reps: 20,
          progression: 'split_squat',
        },
      },
      id: '01kb0b6h4az3er8g7vnh9k5m1a',
      is_complete: false,
      missing_sections: [],
      pain_areas: ['wrist'],
      pain_level: 2,
      pain_notes: 'Wrists need warm-up.',
      preferred_session_minutes: 60,
      preferred_training_days: ['monday', 'wednesday', 'friday'],
      preferred_training_time: 'evening',
      primary_goal: 'skill',
      readiness_rating: 4,
      secondary_goals: ['strength'],
      skill_statuses: {
        handstand: {
          best_hold_seconds: 20,
          status: 'assisted',
        },
      },
      sleep_quality: 4,
      soreness_level: 2,
      starter_plan_key: 'skill_strength_split',
      target_skills: ['strict_pull_up', 'handstand'],
      training_locations: ['home'],
      user_id: '01kaw4k7q6v7m9r6rddm4xyf2p',
      weekly_session_goal: 3,
      ...overrides,
    },
  }
}
