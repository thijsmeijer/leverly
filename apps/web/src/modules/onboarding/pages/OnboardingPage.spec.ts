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

    expect(wrapper.find('h1').text()).toContain('Level up your skills')
    expect(wrapper.text()).toContain('Handstand')
    expect(wrapper.text()).toContain('Personal')
    expect(wrapper.text()).toContain('Equipment')
    expect(wrapper.text()).toContain('Pain + mobility')
    expect(wrapper.text()).toContain('Baseline')
    expect(wrapper.text()).toContain('Goal')
    expect(wrapper.text()).toContain('Skill detail')
    expect(wrapper.text()).toContain('Availability')
    expect(wrapper.text()).toContain('Review')
    expect(wrapper.text()).not.toContain('Starter plan')
  })

  it('keeps later steps locked until required earlier data is complete', async () => {
    configureLeverlyApiClient({
      fetcher: vi.fn<typeof fetch>().mockResolvedValue(
        jsonResponse(
          onboardingResponse({
            age_years: null,
            current_bodyweight_value: null,
            height_value: null,
            prior_sport_background: [],
            training_age_months: null,
          }),
        ),
      ),
    })

    const { wrapper } = await mountWithApp(OnboardingPage, {
      route: '/onboarding',
    })
    await flushPromises()

    const reviewStep = wrapper.findAll('button').find((button) => button.text().includes('Review'))

    expect(reviewStep?.attributes('disabled')).toBeDefined()

    await wrapper
      .findAll('button')
      .find((button) => button.text() === 'Save and continue')
      ?.trigger('click')

    expect(wrapper.text()).toContain('Enter a number from 13 to 90.')
    expect(wrapper.text()).toContain('Choose at least one background option')
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
        body: expect.stringContaining('"target_skills":["handstand"]'),
        method: 'PATCH',
      }),
    )
    expect(wrapper.text()).toContain('Map the places and tools')
  })

  it('shows relevant conditional goal modules after goal selection', async () => {
    configureLeverlyApiClient({
      fetcher: vi.fn<typeof fetch>().mockResolvedValue(jsonResponse(onboardingResponse())),
    })

    const { wrapper } = await mountWithApp(OnboardingPage, {
      route: '/onboarding',
    })
    await flushPromises()

    await wrapper
      .findAll('button')
      .find((button) => button.text().includes('Skill detail'))
      ?.trigger('click')

    expect(wrapper.text()).toContain('Inversion skill check')
    expect(wrapper.text()).toContain('Freestanding kick-up')
    expect(wrapper.text()).not.toContain('Lower-body skill check')
  })

  it('uses roomy option cards for pain and recovery controls', async () => {
    configureLeverlyApiClient({
      fetcher: vi.fn<typeof fetch>().mockResolvedValue(jsonResponse(onboardingResponse())),
    })

    const { wrapper } = await mountWithApp(OnboardingPage, {
      route: '/onboarding',
    })
    await flushPromises()

    await wrapper
      .findAll('button')
      .find((button) => button.text().includes('Pain + mobility'))
      ?.trigger('click')

    for (const label of ['Readiness', 'Sleep quality', 'Soreness', 'Pain right now']) {
      const fieldset = wrapper.findAll('fieldset').find((candidate) => candidate.find('legend').text() === label)

      expect(fieldset, `${label} fieldset should exist`).toBeDefined()
      expect(fieldset?.find('.grid').classes().join(' ')).toContain('minmax(7.5rem,1fr)')
      expect(fieldset?.find('label').classes().join(' ')).toContain('min-h-20')
    }
  })

  it('labels the support baseline as a dip support hold', async () => {
    configureLeverlyApiClient({
      fetcher: vi.fn<typeof fetch>().mockResolvedValue(jsonResponse(onboardingResponse())),
    })

    const { wrapper } = await mountWithApp(OnboardingPage, {
      route: '/onboarding',
    })
    await flushPromises()

    await wrapper
      .findAll('button')
      .find((button) => button.text().includes('Baseline'))
      ?.trigger('click')

    expect(wrapper.find('label[for="onboarding-top-support"]').text()).toContain('Dip support hold')
    expect(wrapper.text()).not.toContain('Top support hold')
  })

  it('shows the Roadmap V2 review and completes onboarding from the review step', async () => {
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
      .find((button) => button.text().includes('Review'))
      ?.trigger('click')

    expect(wrapper.text()).toContain('Primary roadmap')
    expect(wrapper.text()).toContain('Handstand')
    expect(wrapper.text()).toContain('Secondary lane')
    expect(wrapper.text()).toContain('Pull-up')
    expect(wrapper.text()).toContain('Deferred for later')
    expect(wrapper.text()).toContain('One-arm pull-up')
    expect(wrapper.text()).toContain('8-16 weeks')
    expect(wrapper.text()).toContain('Medium confidence')
    expect(wrapper.text()).toContain('First block')
    expect(wrapper.text()).toContain('Wrist extension')

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
        dips: {
          fallback_reps: 5,
          fallback_seconds: null,
          fallback_variant: 'assisted',
          max_strict_reps: 6,
        },
        hollow_hold_seconds: 35,
        lower_body: {
          load_unit: 'kg',
          load_value: null,
          reps: 12,
          variant: 'split_squat',
        },
        passive_hang_seconds: 45,
        pull_ups: {
          fallback_reps: null,
          fallback_seconds: 6,
          fallback_variant: 'eccentric',
          max_strict_reps: 4,
        },
        push_ups: {
          max_strict_reps: 18,
        },
        rows: {
          max_reps: 12,
          variant: 'ring_row',
        },
        squat: {
          barbell_load_value: 100,
          barbell_reps: 5,
        },
        top_support_hold_seconds: 25,
      },
      experience_level: 'intermediate',
      height_unit: 'cm',
      height_value: 178,
      id: '01kb0b6h4az3er8g7vnh9k5m1a',
      is_complete: false,
      long_term_target_skills: ['planche'],
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
      missing_sections: [],
      mobility_checks: {
        ankle_dorsiflexion: 'limited',
        pancake_compression: 'not_tested',
        shoulder_extension: 'clear',
        shoulder_flexion: 'clear',
        wrist_extension: 'limited',
      },
      pain_areas: ['wrist'],
      pain_flags: painFlags(),
      pain_level: 2,
      pain_notes: 'Wrists need warm-up.',
      preferred_session_minutes: 60,
      preferred_training_days: ['monday', 'wednesday', 'friday'],
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
          status: 'freestanding_kick_up',
        },
      },
      sleep_quality: 4,
      soreness_level: 2,
      starter_plan_key: 'skill_strength_split',
      target_skills: ['handstand'],
      training_age_months: 18,
      training_locations: ['home'],
      user_id: '01kaw4k7q6v7m9r6rddm4xyf2p',
      weighted_baselines: {
        experience: 'repetition_work',
        movements: [{ external_load_value: 10, movement: 'weighted_pull_up', reps: 5, rir: 2 }],
        unit: 'kg',
      },
      weekly_session_goal: 3,
      weight_trend: 'maintaining',
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
    wrist: { notes: 'Wrists need warm-up.', severity: 'mild', status: 'recurring' },
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
        label: 'Pull-up',
        next_gate: 'Build toward 3 clean sets of 6 to 8.',
        reason: 'Pull-ups are already in range for direct progression.',
        skill: 'strict_pull_up',
      },
    ],
    blockers: [
      {
        key: 'wrist_extension',
        label: 'Wrist extension',
        message: 'Wrist extension is limited, so keep handstand volume progressive.',
        severity: 'watch',
      },
    ],
    compatible_secondary_goal: {
      skill: 'strict_pull_up',
      label: 'Pull-up',
      lane: 'secondary',
      current_progression_node: { id: 'strict_pull_up.current', label: 'Current pull-up base' },
      next_node: { id: 'strict_pull_up.next', label: 'Build repeatable sets' },
      next_milestone: { id: 'strict_pull_up.milestone', label: '3 sets of 6 to 8' },
      eta_range: { min_weeks: 6, max_weeks: 12, label: '6-12 weeks' },
      confidence: { level: 'medium', score: 0.7, reasons: ['Pull-up reps are measured.'] },
      blockers: [],
      unlock_conditions: [],
      compatibility_tags: ['pull', 'foundation'],
      explanation: 'Pairs well as a strength support lane.',
    },
    confidence: {
      level: 'medium',
      score: 0.72,
      reasons: ['Baseline tests are complete enough for a first block.'],
    },
    current_block_focus: {
      eta_range: { min_weeks: 8, max_weeks: 16, label: '8-16 weeks' },
      focus_areas: ['handstand_line', 'core_bodyline'],
      label: 'First block',
      lanes: ['primary', 'secondary'],
      retest_cadence: ['Retest handstand and hollow hold in 4 weeks.'],
      should_improve: ['Freestanding balance entries', 'Hollow body line'],
    },
    deferred_goals: [
      {
        skill: 'one_arm_pull_up',
        label: 'One-arm pull-up',
        lane: 'deferred',
        current_progression_node: { id: 'one_arm_pull_up.locked', label: 'Locked' },
        next_node: { id: 'weighted_pull_up.base', label: 'Build weighted pull-up base' },
        next_milestone: { id: 'weighted_pull_up.strong_base', label: 'Heavy weighted pull-up base' },
        eta_range: { min_weeks: 52, max_weeks: 104, label: '12-24+ months' },
        confidence: { level: 'low', score: 0.42, reasons: ['Advanced pulling metrics are incomplete.'] },
        blockers: [],
        unlock_conditions: [],
        compatibility_tags: ['pull', 'elbow_flexor_load'],
        explanation: 'Deferred until weighted pulling is stronger.',
      },
    ],
    deferred_tracks: [],
    domain_bottlenecks: [
      {
        confidence: 0.72,
        domain: 'inversion_balance',
        label: 'Inversion balance',
        missing_inputs: [],
        reason: 'Wall line is present, but freestanding control still needs exposure.',
        score: 0.58,
      },
    ],
    eta_range: { min_weeks: 8, max_weeks: 16, label: '8-16 weeks' },
    explanation: {
      summary: 'Handstand is the clearest first roadmap priority from the current assessment.',
      why_this_goal: ['Handstand pairs with the current pressing and bodyline base.'],
      watch_out_for: ['Keep wrist loading progressive.'],
      fallback: 'Keep handstand as line practice if wrists feel irritated.',
    },
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
    primary_goal: {
      skill: 'handstand',
      label: 'Handstand',
      lane: 'primary',
      current_progression_node: { id: 'handstand.current', label: 'Wall line and entry work' },
      next_node: { id: 'handstand.next', label: 'Build wall line consistency' },
      next_milestone: { id: 'handstand.milestone', label: '30-second wall line' },
      eta_range: { min_weeks: 8, max_weeks: 16, label: '8-16 weeks' },
      confidence: { level: 'medium', score: 0.72, reasons: ['Baseline tests are complete enough for a first block.'] },
      blockers: [],
      unlock_conditions: [{ skill: 'handstand', label: 'Pain-free wrist loading' }],
      compatibility_tags: ['overhead', 'wrist_extension', 'skill_practice'],
      explanation: 'Handstand is the clearest first roadmap priority from the current assessment.',
    },
    summary: 'You have enough base strength for a focused skill roadmap plus one light secondary exposure.',
    unlocked_tracks: [
      {
        base_focus_areas: ['handstand_line', 'core_bodyline'],
        compatible_secondary_skills: ['l_sit'],
        label: 'Handstand',
        next_gate: 'Build a clean wall line and controlled balance entries.',
        reason: 'Your pressing, bodyline, shoulder, and wrist signals are ready for regular handstand practice.',
        skill: 'handstand',
      },
    ],
  }
}
