import { flushPromises } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { configureLeverlyApiClient, resetLeverlyApiClient } from '@/shared/api/leverlyApi/runtimeClient'
import { jsonResponse } from '@/tests/http'
import { mountWithApp } from '@/tests/harness'

import OnboardingPage from './OnboardingPage.vue'

describe('OnboardingPage', () => {
  afterEach(() => {
    resetLeverlyApiClient()
  })

  it('loads a polished resumable onboarding flow', async () => {
    configureLeverlyApiClient({
      fetcher: vi.fn<typeof fetch>().mockResolvedValue(jsonResponse(onboardingResponse())),
    })

    const { wrapper } = await mountWithApp(OnboardingPage, {
      route: '/onboarding',
    })
    await flushPromises()

    expect(wrapper.find('h1').text()).toContain('Build the signal')
    expect(wrapper.text()).toContain('Strict pull-up')
    expect(wrapper.text()).toContain('Handstand')
    expect(wrapper.text()).toContain('Goals')
    expect(wrapper.text()).toContain('Equipment')
    expect(wrapper.text()).toContain('Positions')
    expect(wrapper.text()).toContain('Starter plan')
  })

  it('saves drafts when moving between steps', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(jsonResponse(onboardingResponse()))
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse(onboardingResponse()))

    configureLeverlyApiClient({
      fetcher,
      getCsrfToken: () => 'csrf-token',
    })

    const { wrapper } = await mountWithApp(OnboardingPage, {
      route: '/onboarding',
    })
    await flushPromises()

    await wrapper
      .findAll('button')
      .find((button) => button.text() === 'Save and continue')
      ?.trigger('click')
    await flushPromises()

    expect(fetcher).toHaveBeenNthCalledWith(
      3,
      '/api/v1/me/onboarding',
      expect.objectContaining({
        body: expect.stringContaining('"target_skills":["strict_pull_up","handstand"]'),
        method: 'PATCH',
      }),
    )
    expect(wrapper.text()).toContain('Map the places and tools')
  })

  it('completes onboarding from the starter step', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(jsonResponse(onboardingResponse()))
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse(onboardingResponse({ is_complete: true })))

    configureLeverlyApiClient({
      fetcher,
      getCsrfToken: () => 'csrf-token',
    })

    const { router, wrapper } = await mountWithApp(OnboardingPage, {
      route: '/onboarding',
    })
    await flushPromises()

    await wrapper
      .findAll('button')
      .find((button) => button.text() === 'Starter plan')
      ?.trigger('click')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(fetcher).toHaveBeenNthCalledWith(
      3,
      '/api/v1/me/onboarding',
      expect.objectContaining({
        body: expect.stringContaining('"complete":true'),
        method: 'PATCH',
      }),
    )
    expect(router.currentRoute.value.name).toBe('dashboard')
  })
})

function onboardingResponse(overrides: Partial<Record<string, unknown>> = {}) {
  return {
    data: {
      available_equipment: ['pull_up_bar', 'rings'],
      base_focus_areas: ['pull_capacity', 'core_bodyline'],
      completed_at: null,
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
      id: '01kb0b6h4az3er8g7vnh9k5m1a',
      is_complete: false,
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
      primary_goal: 'skill',
      primary_target_skill: 'handstand',
      readiness_rating: 4,
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
