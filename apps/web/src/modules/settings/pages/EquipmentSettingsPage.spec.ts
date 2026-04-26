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

  it('loads a categorized equipment manager with selected state and coverage', async () => {
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
    expect(wrapper.text()).toContain('2 items selected')
    expect(wrapper.find<HTMLInputElement>('input[name="equipment"][value="pull_up_bar"]').element.checked).toBe(true)
    expect(wrapper.find<HTMLInputElement>('input[name="equipment"][value="low_bar"]').element.checked).toBe(false)
    expect(wrapper.text()).toContain('Vertical pulling')
    expect(wrapper.text()).toContain('Ring strength')
  })

  it('adds a preset and saves equipment through the profile API', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(jsonResponse(profileResponse()))
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(
        jsonResponse(
          profileResponse({
            available_equipment: ['pull_up_bar', 'rings', 'resistance_band', 'suspension_trainer', 'jump_rope'],
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

    await wrapper
      .findAll('button')
      .find((button) => button.text().includes('Travel kit'))
      ?.trigger('click')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(fetcher).toHaveBeenNthCalledWith(
      3,
      '/api/v1/me/profile',
      expect.objectContaining({
        body: expect.stringContaining('"suspension_trainer"'),
        method: 'PATCH',
      }),
    )
    expect(String(fetcher.mock.calls[2]?.[1]?.body)).toContain('"jump_rope"')
    expect(wrapper.text()).toContain('Equipment settings saved.')
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
      movement_limitations: [],
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
