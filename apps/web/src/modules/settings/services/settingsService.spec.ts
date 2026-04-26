import { afterEach, describe, expect, it, vi } from 'vitest'
import { configureLeverlyApiClient, resetLeverlyApiClient } from '@/shared/api/leverlyApi/runtimeClient'
import { jsonResponse } from '@/tests/http'

import {
  defaultProfileSettingsForm,
  fetchProfileSettings,
  ProfileSettingsValidationError,
  saveAvailableEquipmentSettings,
  saveProfileSettings,
  validateProfileSettingsForm,
} from './settingsService'

describe('settingsService', () => {
  afterEach(() => {
    resetLeverlyApiClient()
  })

  it('treats a missing profile as an editable default form', async () => {
    configureLeverlyApiClient({
      fetcher: vi.fn<typeof fetch>().mockResolvedValue(jsonResponse({ message: 'Missing' }, { status: 404 })),
    })

    await expect(fetchProfileSettings()).resolves.toMatchObject({
      form: {
        availableEquipment: [],
        unitSystem: 'metric',
      },
      profileId: null,
    })
  })

  it('maps the profile transport shape into form state', async () => {
    configureLeverlyApiClient({
      fetcher: vi.fn<typeof fetch>().mockResolvedValue(jsonResponse(profileResponse())),
    })

    await expect(fetchProfileSettings()).resolves.toMatchObject({
      form: {
        availableEquipment: ['pull_up_bar', 'rings'],
        baseFocusAreas: ['pull_capacity', 'core_bodyline'],
        baselineTests: {
          rowProgression: 'inverted_row',
        },
        currentBodyweightValue: '72.5',
        displayName: 'Ada Athlete',
        movementLimitation: {
          area: 'wrist',
          notes: 'Needs warm-up.',
          severity: 'mild',
          status: 'recurring',
        },
        primaryTargetSkill: 'handstand',
        preferredTrainingDays: ['monday', 'wednesday'],
        targetSkillsText: 'handstand\nstrict_pull_up',
      },
      profileId: '01kb0b6h4az3er8g7vnh9k5m1a',
    })
  })

  it('serializes form state to the PATCH profile endpoint', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse(profileResponse({ display_name: 'Ada Bars' })))

    configureLeverlyApiClient({
      fetcher,
      getCsrfToken: () => 'csrf-token',
    })

    const form = {
      ...defaultProfileSettingsForm(),
      availableEquipment: ['pull_up_bar', 'rings'],
      displayName: 'Ada Bars',
      injuryNotes: 'No diagnosis here.',
      primaryTargetSkill: 'handstand',
      preferredSessionMinutes: '60',
      preferredTrainingDays: ['monday', 'friday'],
      targetSkillsText: 'handstand, strict_pull_up',
      trainingAgeMonths: '18',
    }

    await expect(saveProfileSettings(form)).resolves.toMatchObject({
      form: {
        displayName: 'Ada Bars',
      },
    })

    expect(fetcher).toHaveBeenNthCalledWith(
      2,
      '/api/v1/me/profile',
      expect.objectContaining({
        body: expect.stringContaining('"target_skills":["handstand","strict_pull_up"]'),
        credentials: 'include',
        method: 'PATCH',
      }),
    )
  })

  it('can save equipment without requiring the whole editable profile form', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(
        jsonResponse(
          profileResponse({
            available_equipment: ['pull_up_bar', 'low_bar', 'rings'],
          }),
        ),
      )

    configureLeverlyApiClient({
      fetcher,
      getCsrfToken: () => 'csrf-token',
    })

    await expect(
      saveAvailableEquipmentSettings(['pull_up_bar', 'low_bar', 'unsupported_equipment', 'rings']),
    ).resolves.toMatchObject({
      form: {
        availableEquipment: ['pull_up_bar', 'low_bar', 'rings'],
      },
    })

    expect(fetcher).toHaveBeenNthCalledWith(
      2,
      '/api/v1/me/profile',
      expect.objectContaining({
        body: JSON.stringify({
          available_equipment: ['pull_up_bar', 'low_bar', 'rings'],
        }),
        method: 'PATCH',
      }),
    )
  })

  it('maps server validation errors to UI fields', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(
        jsonResponse(
          {
            errors: {
              preferred_session_minutes: ['Maximum session length is outside the supported range.'],
            },
            message: 'The given data was invalid.',
          },
          { status: 422 },
        ),
      )

    configureLeverlyApiClient({
      fetcher,
    })

    await expect(saveProfileSettings(defaultProfileSettingsForm())).rejects.toMatchObject({
      errors: {
        preferredSessionMinutes: 'Maximum session length is outside the supported range.',
      },
    } satisfies Pick<ProfileSettingsValidationError, 'errors'>)
  })

  it('validates local numeric fields before save', () => {
    expect(
      validateProfileSettingsForm({
        ...defaultProfileSettingsForm(),
        displayName: '',
        preferredSessionMinutes: '5',
        secondaryGoals: ['endurance', 'conditioning', 'general_fitness'],
        weeklySessionGoal: '15',
      }),
    ).toMatchObject({
      displayName: 'Add the name you want shown in Leverly.',
      preferredSessionMinutes: 'Maximum session length must be 10 to 240 minutes.',
      secondaryGoals: 'Choose up to two secondary goals that fit your primary goal.',
      weeklySessionGoal: 'Weekly sessions must be between 1 and 14.',
    })
  })
})

function profileResponse(overrides: Partial<Record<string, unknown>> = {}) {
  return {
    data: {
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
      mobility_checks: {
        ankle_dorsiflexion: 'limited',
        pancake_compression: 'not_tested',
        shoulder_extension: 'clear',
        shoulder_flexion: 'clear',
        wrist_extension: 'limited',
      },
      preferred_session_minutes: 60,
      preferred_training_days: ['monday', 'wednesday'],
      preferred_training_time: 'evening',
      primary_goal: 'skill',
      primary_target_skill: 'handstand',
      progression_pace: 'balanced',
      secondary_goals: ['strength'],
      secondary_target_skills: ['strict_pull_up'],
      session_structure_preferences: ['skill_first'],
      skill_statuses: {
        handstand: {
          best_hold_seconds: 20,
          status: 'assisted',
        },
      },
      target_skills: ['handstand', 'strict_pull_up'],
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
      ...overrides,
    },
  }
}
