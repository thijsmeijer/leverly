import { flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { configureLeverlyApiClient, resetLeverlyApiClient } from '@/shared/api/leverlyApi/runtimeClient'
import { jsonResponse } from '@/tests/http'
import { mountWithApp } from '@/tests/harness'

import EquipmentSettingsPage from './EquipmentSettingsPage.vue'

describe('EquipmentSettingsPage', () => {
  afterEach(() => {
    resetLeverlyApiClient()
  })

  it('loads a categorized equipment manager with selected card states', async () => {
    configureLeverlyApiClient({
      fetcher: vi.fn<typeof fetch>().mockResolvedValue(jsonResponse(profileResponse())),
    })

    const { wrapper } = await mountWithApp(EquipmentSettingsPage, {
      route: '/app/settings/equipment',
    })
    await flushPromises()

    expect(wrapper.find('#equipment-settings-heading').text()).toContain('Tell Leverly')
    expect(wrapper.text()).toContain('Bars and stations')
    expect(wrapper.text()).toContain('Skill supports')
    expect(wrapper.find<HTMLInputElement>('input[name="equipment"][value="pull_up_bar"]').element.checked).toBe(true)
    expect(wrapper.find<HTMLInputElement>('input[name="equipment"][value="low_bar"]').element.checked).toBe(false)
    expect(wrapper.text()).not.toContain('Fast setup')
    expect(wrapper.text()).not.toContain('Recommendation coverage')
    expect(wrapper.text()).not.toContain('Selected')
  })

  it('toggles equipment and saves through the profile API', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(jsonResponse(profileResponse()))
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(
        jsonResponse(
          profileResponse({
            available_equipment: ['pull_up_bar', 'rings', 'low_bar', 'ab_wheel'],
          }),
        ),
      )

    configureLeverlyApiClient({
      fetcher,
      getCsrfToken: () => 'csrf-token',
    })

    const { wrapper } = await mountWithApp(EquipmentSettingsPage, {
      route: '/app/settings/equipment',
    })
    await flushPromises()

    await wrapper.find<HTMLInputElement>('input[name="equipment"][value="low_bar"]').setValue(true)
    await wrapper.find<HTMLInputElement>('input[name="equipment"][value="ab_wheel"]').setValue(true)
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(fetcher).toHaveBeenNthCalledWith(
      3,
      '/api/v1/me/profile',
      expect.objectContaining({
        body: expect.stringContaining('"low_bar"'),
        method: 'PATCH',
      }),
    )
    expect(String(fetcher.mock.calls[2]?.[1]?.body)).toContain('"ab_wheel"')
    expect(wrapper.text()).toContain('Equipment settings saved.')
  })
})

function profileResponse(overrides: Partial<Record<string, unknown>> = {}) {
  return {
    data: {
      available_equipment: ['pull_up_bar', 'rings'],
      base_focus_areas: ['pull_capacity', 'core_bodyline'],
      baseline_tests: {
        dips: { fallback_reps: 5, fallback_seconds: null, fallback_variant: 'assisted', max_strict_reps: 6 },
        hollow_hold_seconds: 35,
        lower_body: { load_unit: 'kg', load_value: null, reps: 12, variant: 'split_squat' },
        passive_hang_seconds: 45,
        pull_ups: { fallback_reps: null, fallback_seconds: 6, fallback_variant: 'eccentric', max_strict_reps: 4 },
        push_ups: { max_strict_reps: 18 },
        rows: { max_reps: 12, variant: 'ring_row' },
        squat: { barbell_load_value: 100, barbell_reps: 5 },
        top_support_hold_seconds: 25,
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
      movement_limitations: [],
      mobility_checks: {
        ankle_dorsiflexion: 'limited',
        pancake_compression: 'not_tested',
        shoulder_extension: 'clear',
        shoulder_flexion: 'clear',
        wrist_extension: 'limited',
      },
      pain_flags: painFlags(),
      preferred_session_minutes: 60,
      preferred_training_days: ['monday', 'wednesday'],
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
      weight_trend: 'maintaining',
      weekly_session_goal: 3,
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
    wrist: { notes: null, severity: 'none', status: 'none' },
  }
}
