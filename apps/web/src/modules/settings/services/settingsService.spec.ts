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
        currentBodyweightValue: '72.5',
        displayName: 'Ada Athlete',
        movementLimitation: {
          area: 'wrist',
          notes: 'Needs warm-up.',
          severity: 'mild',
          status: 'recurring',
        },
        preferredTrainingDays: ['monday', 'wednesday'],
        targetSkillsText: 'freestanding handstand\nstrict muscle-up',
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
      preferredSessionMinutes: '60',
      preferredTrainingDays: ['monday', 'friday'],
      targetSkillsText: 'freestanding handstand, strict muscle-up',
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
        body: expect.stringContaining('"target_skills":["freestanding handstand","strict muscle-up"]'),
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
      ...overrides,
    },
  }
}
