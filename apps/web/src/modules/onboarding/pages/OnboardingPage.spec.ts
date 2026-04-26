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

    expect(wrapper.find('h1').text()).toContain('Find the strongest path')
    expect(wrapper.text()).toContain('Strict pull-up')
    expect(wrapper.text()).toContain('Handstand')
    expect(wrapper.text()).toContain('Context')
    expect(wrapper.text()).toContain('Equipment')
    expect(wrapper.text()).toContain('Positions')
    expect(wrapper.text()).toContain('Roadmap')
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
