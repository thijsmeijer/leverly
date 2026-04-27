import { flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { configureLeverlyApiClient, resetLeverlyApiClient } from '@/shared/api/leverlyApi/runtimeClient'
import { jsonResponse } from '@/tests/http'
import { mountWithApp } from '@/tests/harness'

import SettingsPage from './SettingsPage.vue'

describe('SettingsPage', () => {
  afterEach(() => {
    resetLeverlyApiClient()
  })

  it('loads profile settings into an accessible responsive form', async () => {
    configureLeverlyApiClient({
      fetcher: vi.fn<typeof fetch>().mockResolvedValue(jsonResponse(profileResponse())),
    })

    const { wrapper } = await mountWithApp(SettingsPage, {
      route: '/app/settings/profile',
    })
    await flushPromises()

    expect(wrapper.find('#profile-settings-heading').text()).toContain('Tune the inputs')
    expect(wrapper.find('label[for="profile-display-name"]').text()).toBe('Display name')
    expect(wrapper.find<HTMLInputElement>('#profile-display-name').element.value).toBe('Ada Athlete')
    expect(wrapper.text()).toContain('2 days selected')
    expect(wrapper.text()).not.toContain('Leverly can adjust exercise options, but it is not medical software')

    await wrapper.find('#profile-tab-limitations').trigger('click')

    expect(wrapper.text()).toContain('Leverly can adjust exercise options, but it is not medical software')
  })

  it('shows local validation errors before saving', async () => {
    configureLeverlyApiClient({
      fetcher: vi.fn<typeof fetch>().mockResolvedValue(jsonResponse(profileResponse())),
    })

    const { wrapper } = await mountWithApp(SettingsPage, {
      route: '/app/settings/profile',
    })
    await flushPromises()

    await wrapper.find('#profile-display-name').setValue('')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Add the name you want shown in Leverly.')
    expect(wrapper.find('#profile-display-name').attributes('aria-invalid')).toBe('true')

    await wrapper.find('#profile-display-name').setValue('Ada Athlete')
    await wrapper.find('#profile-tab-setup').trigger('click')
    await wrapper.find('#preferred-session-minutes').setValue('5')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Maximum session length must be 10 to 240 minutes.')
    expect(wrapper.find('#profile-panel-setup').exists()).toBe(true)
  })

  it('keeps equipment context with training while setup stays schedule focused', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(jsonResponse(profileResponse()))
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse(profileResponse({ preferred_session_minutes: 45 })))

    configureLeverlyApiClient({
      fetcher,
      getCsrfToken: () => 'csrf-token',
    })

    const { wrapper } = await mountWithApp(SettingsPage, {
      route: '/app/settings/profile',
    })
    await flushPromises()

    await wrapper.find('#profile-tab-training').trigger('click')

    expect(wrapper.text()).toContain('Placement snapshot')
    expect(wrapper.text()).toContain('2 tools selected')
    expect(wrapper.find('a[href="/app/settings/equipment"]').text()).toContain('Review equipment')

    await wrapper.find('#profile-tab-setup').trigger('click')

    expect(wrapper.text()).toContain('Schedule and session shape')
    expect(wrapper.text()).not.toContain('Placement snapshot')
    expect(wrapper.find('input[name="available-equipment"]').exists()).toBe(false)

    await wrapper.find('input[name="preferred-training-days"][value="friday"]').setValue(true)
    await wrapper.find('#preferred-session-minutes').setValue('45')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(fetcher).toHaveBeenNthCalledWith(
      3,
      '/api/v1/me/profile',
      expect.objectContaining({
        body: expect.stringContaining('"preferred_session_minutes":45'),
        method: 'PATCH',
      }),
    )
    expect(String(fetcher.mock.calls[2]?.[1]?.body)).toContain('"friday"')
    expect(wrapper.text()).toContain('Profile settings saved.')
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
    wrist: { notes: 'Needs warm-up.', severity: 'mild', status: 'recurring' },
  }
}
