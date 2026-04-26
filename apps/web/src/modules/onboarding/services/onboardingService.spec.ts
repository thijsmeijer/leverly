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
        ageYears: '29',
        availableEquipment: ['pull_up_bar', 'rings'],
        baseFocusAreas: ['pull_capacity', 'core_bodyline'],
        currentBodyweightValue: '72.5',
        currentLevelTests: {
          hollowHoldSeconds: '35',
          pullUpMaxReps: '4',
          pullUpProgression: 'strict_pull_up',
          pushUpMaxReps: '18',
          rowProgression: 'inverted_row',
          squatMaxReps: '20',
          squatProgression: 'split_squat',
        },
        heightValue: '178',
        longTermTargetSkills: ['planche'],
        primaryTargetSkill: 'handstand',
        roadmapSuggestions: {
          level: 'intermediate',
        },
        targetSkills: ['strict_pull_up', 'handstand'],
        trainingAgeMonths: '18',
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
      ageYears: '29',
      baseFocusAreas: ['pull_capacity', 'core_bodyline'],
      currentBodyweightValue: '72.5',
      currentLevelTests: {
        ...defaultOnboardingForm().currentLevelTests,
        hollowHoldSeconds: '35',
        pullUpMaxReps: '4',
        pullUpProgression: 'strict_pull_up',
        pushUpProgression: 'strict_push_up',
        pushUpMaxReps: '18',
        rowProgression: 'inverted_row',
        squatMaxReps: '20',
        squatProgression: 'split_squat',
      },
      heightValue: '178',
      longTermTargetSkills: ['planche'],
      mobilityChecks: {
        ...defaultOnboardingForm().mobilityChecks,
        wrist_extension: 'limited',
      },
      preferredSessionMinutes: '60',
      preferredTrainingDays: ['monday', 'wednesday', 'friday'],
      priorSportBackground: ['strength_training'],
      primaryTargetSkill: 'handstand',
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
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"age_years":29')
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
            pushUpProgression: '',
            rowProgression: '',
            squatProgression: '',
          },
          targetSkills: [],
        },
        'level',
      ),
    ).toMatchObject({
      'currentLevelTests.hollowHoldSeconds': 'Enter a number from 0 to 600.',
      'currentLevelTests.pullUpProgression': 'Choose your current pulling level or add strict reps.',
      'currentLevelTests.pushUpProgression': 'Choose your current push-up level or add strict reps.',
      'currentLevelTests.rowProgression': 'Choose your current row level or add row reps.',
      'currentLevelTests.squatProgression': 'Choose your current squat level or add reps.',
    })
  })
})

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
        arch_hold_seconds: 25,
        dead_hang_seconds: 30,
        dips: {
          max_strict_reps: 6,
          progression: 'bar_dip',
          support_hold_seconds: 25,
        },
        hollow_hold_seconds: 35,
        l_sit_hold_seconds: 8,
        pull_ups: {
          assistance: null,
          form_quality: 4,
          max_strict_reps: 4,
          progression: 'strict_pull_up',
        },
        push_ups: {
          form_quality: 4,
          max_strict_reps: 18,
          progression: 'strict_push_up',
        },
        rows: {
          max_strict_reps: 12,
          progression: 'inverted_row',
        },
        squat: {
          max_reps: 20,
          progression: 'split_squat',
        },
        support_hold_seconds: 25,
        wall_handstand_seconds: 20,
      },
      experience_level: 'intermediate',
      height_unit: 'cm',
      height_value: 178,
      id: '01kb0b6h4az3er8g7vnh9k5m1a',
      is_complete: false,
      long_term_target_skills: ['planche'],
      missing_sections: [],
      mobility_checks: {
        ankle_dorsiflexion: 'limited',
        pancake_compression: 'not_tested',
        shoulder_extension: 'clear',
        shoulder_flexion: 'clear',
        wrist_extension: 'limited',
      },
      pain_areas: ['wrist'],
      pain_level: 2,
      pain_notes: 'Wrists need warm-up.',
      preferred_session_minutes: 60,
      preferred_training_days: ['monday', 'wednesday', 'friday'],
      preferred_training_time: 'evening',
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
          status: 'assisted',
        },
      },
      sleep_quality: 4,
      soreness_level: 2,
      starter_plan_key: 'skill_strength_split',
      target_skills: ['strict_pull_up', 'handstand'],
      training_age_months: 18,
      training_locations: ['home'],
      user_id: '01kaw4k7q6v7m9r6rddm4xyf2p',
      weighted_baselines: {
        experience: 'repetition_work',
        movements: [{ external_load_value: 10, movement: 'weighted_pull_up', reps: 5, rir: 2 }],
        unit: 'kg',
      },
      weekly_session_goal: 3,
      ...overrides,
    },
  }
}

function roadmapSuggestions() {
  return {
    base_focus_areas: ['pull_capacity', 'core_bodyline'],
    body_context: {
      notes: [],
    },
    bridge_tracks: [
      {
        base_focus_areas: ['pull_capacity', 'row_volume'],
        compatible_secondary_skills: ['l_sit'],
        label: 'Strict pull-up',
        next_gate: 'Build toward 3 clean sets of 6 to 8.',
        reason: 'Strict pull-ups are already in range for direct progression.',
        skill: 'strict_pull_up',
      },
    ],
    deferred_tracks: [],
    level: 'intermediate',
    long_term_tracks: [
      {
        base_focus_areas: ['push_capacity', 'straight_arm_tolerance'],
        compatible_secondary_skills: ['strict_push_up'],
        label: 'Planche',
        next_gate: 'Reach reliable push-up volume and pain-free straight-arm loading.',
        reason: 'Planche is a long-term strength skill.',
        skill: 'planche',
      },
    ],
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
