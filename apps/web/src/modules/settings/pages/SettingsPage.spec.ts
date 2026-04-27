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
    expect(wrapper.text()).toContain('Roadmap recalculation inputs')
    expect(wrapper.text()).toContain('8-16 weeks')
    expect(wrapper.text()).not.toContain('Leverly can adjust exercise options, but it is not medical software')

    await wrapper.find('#profile-tab-training').trigger('click')

    expect(wrapper.text()).toContain('Weight trend')
    expect(wrapper.text()).toContain('Row capacity')
    expect(wrapper.text()).toContain('Passive hang')
    expect(wrapper.text()).toContain('Top support hold')
    expect(wrapper.text()).toContain('Lower-body fallback')
    expect(wrapper.text()).toContain('Pulling skill check')
    expect(wrapper.find('a[href="/app/settings/equipment"]').text()).toContain('Review equipment')

    await wrapper.find('#profile-tab-limitations').trigger('click')

    expect(wrapper.text()).toContain('Leverly can adjust exercise options, but it is not medical software')
    expect(wrapper.text()).toContain('Pain flags by area')
    expect(wrapper.text()).toContain('Wrist')
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

  it('renders portfolio groups, skill details, schedule cards, and stress warnings', async () => {
    configureLeverlyApiClient({
      fetcher: vi.fn<typeof fetch>().mockResolvedValue(jsonResponse(profileResponse())),
    })

    const { wrapper } = await mountWithApp(SettingsPage, {
      route: '/app/settings/profile',
    })
    await flushPromises()

    await wrapper.find('#profile-tab-training').trigger('click')

    expect(wrapper.text()).toContain('Active skill portfolio')
    expect(wrapper.text()).toContain('Development focus')
    expect(wrapper.text()).toContain('Also training')
    expect(wrapper.text()).toContain('Foundation')
    expect(wrapper.text()).toContain('Maintenance')
    expect(wrapper.text()).toContain('Future queue')
    expect(wrapper.text()).toContain('Not this phase')
    expect(wrapper.text()).toContain('Role')
    expect(wrapper.text()).toContain('Frequency')
    expect(wrapper.text()).toContain('Intensity')
    expect(wrapper.text()).toContain('ETA source')
    expect(wrapper.text()).toContain('Baseline prior')
    expect(wrapper.text()).toContain('Next node')
    expect(wrapper.text()).toContain('Next milestone')
    expect(wrapper.text()).toContain('Why included')
    expect(wrapper.text()).toContain('Watch-outs')
    expect(wrapper.text()).toContain('Weekly schedule')
    expect(wrapper.text()).toContain('Monday')
    expect(wrapper.text()).toContain('Skill primer')
    expect(wrapper.text()).toContain('Pulling stress is near the daily cap.')
    expect(wrapper.text()).toContain('Stress heatmap')
    expect(wrapper.text()).toContain('Push / wrist')
    expect(wrapper.text()).toContain('Pull / elbow')
    expect(wrapper.text()).toContain('Legs')
    expect(wrapper.text()).toContain('Trunk / compression')
    expect(wrapper.text()).toContain('Recovery margin')
  })

  it('marks portfolio-affecting changes, warns on conflicting goals, and refreshes after save', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(jsonResponse(profileResponse()))
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(
        jsonResponse(
          profileResponse({
            baseline_tests: {
              ...profileResponse().data.baseline_tests,
              rows: { max_reps: 15, variant: 'ring_row' },
            },
            goal_modules: {
              inversion: {
                highest_progression: 'freestanding_handstand',
                metric_type: 'hold_seconds',
                reps: null,
                hold_seconds: 25,
                load_value: null,
                load_unit: 'kg',
                quality: 'clean',
                notes: null,
              },
            },
            long_term_target_skills: ['one_arm_pull_up'],
            roadmap_suggestions: roadmapPortfolioResponse({
              summary: 'One-arm pull-up stays in the future queue while front lever remains loaded.',
            }),
            weight_trend: 'gaining',
          }),
        ),
      )

    configureLeverlyApiClient({
      fetcher,
      getCsrfToken: () => 'csrf-token',
    })

    const { wrapper } = await mountWithApp(SettingsPage, {
      route: '/app/settings/profile',
    })
    await flushPromises()

    await wrapper.find('#profile-tab-training').trigger('click')
    await wrapper.find('#profile-row-reps').setValue('15')
    await wrapper.find('input[name="profile-weight-trend"][value="gaining"]').setValue(true)
    await wrapper.find('input[name="profile-long-term-targets"][value="one_arm_pull_up"]').setValue(true)
    await wrapper.find('#profile-goal-module-pull_skill-reps').setValue('5')

    expect(wrapper.text()).toContain('Unsaved portfolio changes')
    expect(wrapper.text()).toContain('Save profile to recalculate the portfolio.')
    expect(wrapper.text()).toContain(
      'One-arm pull-up is better kept in the future queue while Front lever is loaded as high-stress development.',
    )

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    const body = String(fetcher.mock.calls[2]?.[1]?.body)

    expect(body).toContain('"rows":{"max_reps":15,"variant":"ring_row"}')
    expect(body).toContain('"weight_trend":"gaining"')
    expect(body).toContain('"long_term_target_skills":["one_arm_pull_up"]')
    expect(body).toContain('"reps":5')
    expect(wrapper.text()).toContain('One-arm pull-up stays in the future queue while front lever remains loaded.')
    expect(wrapper.text()).toContain('Profile settings saved.')
    expect(wrapper.text()).toContain('Portfolio matches saved profile inputs')
  })

  it('shows server validation errors for roadmap module saves', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(jsonResponse(profileResponse()))
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(
        jsonResponse(
          {
            errors: {
              goal_modules: ['Add the tested progression for the selected primary goal.'],
            },
            message: 'The given data was invalid.',
          },
          { status: 422 },
        ),
      )

    configureLeverlyApiClient({
      fetcher,
      getCsrfToken: () => 'csrf-token',
    })

    const { wrapper } = await mountWithApp(SettingsPage, {
      route: '/app/settings/profile',
    })
    await flushPromises()

    await wrapper.find('#profile-tab-training').trigger('click')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.text()).toContain('Check the highlighted fields before saving.')
    expect(wrapper.text()).toContain('Add the tested progression for the selected primary goal.')
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
      required_goal_modules: ['pull_skill'],
      goal_modules: {
        pull_skill: {
          highest_progression: 'tuck_front_lever',
          metric_type: 'reps',
          reps: 4,
          hold_seconds: null,
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
      primary_target_skill: 'front_lever',
      progression_pace: 'balanced',
      roadmap_suggestions: roadmapPortfolioResponse(),
      secondary_goals: ['strength'],
      secondary_target_skills: ['handstand'],
      session_structure_preferences: ['skill_first'],
      skill_statuses: {
        handstand: {
          best_hold_seconds: 20,
          status: 'assisted',
        },
      },
      target_skills: ['front_lever'],
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

function roadmapPortfolioResponse(overrides: Partial<Record<string, unknown>> = {}) {
  return {
    version: 'roadmap.portfolio.v3',
    source_version: 'roadmap.v3',
    summary: 'Front lever is loaded as the main development track beside low-fatigue support work.',
    base_focus_areas: ['pull_capacity', 'core_bodyline'],
    body_context: { notes: [] },
    blockers: [],
    bridge_tracks: [],
    compatibility_tags: ['straight_arm_pull', 'trunk_compression'],
    compatible_secondary_goal: null,
    confidence: { level: 'medium', score: 0.72, reasons: ['Baseline tests are complete.'] },
    current_block_focus: {
      label: 'Front lever development block',
      focus_areas: ['straight_arm_pull', 'core_bodyline'],
      lanes: ['development', 'foundation'],
      retest_cadence: ['Retest lever holds in 4 weeks.'],
      should_improve: ['Lever line strength'],
      eta_range: { min_weeks: 8, max_weeks: 16, label: '8-16 weeks' },
    },
    current_progression_node: { id: 'front_lever.tuck', label: 'Tuck front lever' },
    deferred_goals: [],
    deferred_tracks: [],
    eta_range: { min_weeks: 8, max_weeks: 16, label: '8-16 weeks' },
    explanation: {
      summary: 'Front lever is loaded as the main development track.',
      why_this_goal: ['Pulling strength and bodyline evidence make this a realistic focus.'],
      watch_out_for: ['Keep elbow and lat volume inside the weekly cap.'],
      fallback: 'Use easier lever holds when pulling quality drops.',
    },
    foundation_lane: {
      slug: 'pulling_foundation',
      label: 'Pulling foundation',
      focus_areas: ['pull_capacity', 'core_bodyline'],
      current_progression_node: { id: 'foundation.current', label: 'Measured pulling base' },
      next_node: { id: 'foundation.next', label: 'Build repeatable pulling volume' },
      next_milestone: { id: 'foundation.milestone', label: 'Stable weekly pulling base' },
    },
    goal_candidates: {
      accessories: [goalCandidate('l_sit', 'L-sit', 'low_fatigue_accessory')],
      foundation: [goalCandidate('pulling_base', 'Pulling base', 'owned_foundation')],
      future: [goalCandidate('one_arm_pull_up', 'One-arm pull-up', 'long_term')],
      primary: [
        goalCandidate('front_lever', 'Front lever', 'primary_candidate'),
        goalCandidate('planche', 'Planche', 'primary_candidate'),
      ],
      secondary: [goalCandidate('handstand', 'Handstand', 'secondary_candidate')],
    },
    intermediate: {
      compatibility_costs: [],
      domain_scores: {},
      domain_uncertainty: {},
      eta_modifiers: [],
      hard_gate_results: [],
      progression_graph_placement: {},
      readiness_scores: [],
    },
    level: 'intermediate',
    long_term_tracks: [],
    next_milestone: { id: 'front_lever.advanced_tuck', label: 'Advanced tuck front lever' },
    next_node: { id: 'front_lever.advanced_tuck', label: 'Advanced tuck front lever' },
    primary_goal: {
      skill: 'front_lever',
      label: 'Front lever',
      lane: 'development',
      current_progression_node: { id: 'front_lever.tuck', label: 'Tuck front lever' },
      next_node: { id: 'front_lever.advanced_tuck', label: 'Advanced tuck front lever' },
      next_milestone: { id: 'front_lever.advanced_tuck', label: 'Advanced tuck front lever' },
      eta_range: { min_weeks: 8, max_weeks: 16, label: '8-16 weeks' },
      confidence: { level: 'medium', score: 0.72, reasons: ['Baseline tests are complete.'] },
      blockers: [],
      unlock_conditions: [],
      compatibility_tags: ['straight_arm_pull', 'trunk_compression'],
      explanation: 'Pulling strength and bodyline evidence make this a realistic focus.',
    },
    unlock_conditions: [],
    unlocked_tracks: [],
    active_skill_portfolio: {
      development_tracks: [portfolioTrack('front_lever', 'Front lever', 'development')],
      technical_practice_tracks: [portfolioTrack('handstand', 'Handstand', 'technical_practice')],
      accessory_tracks: [portfolioTrack('l_sit', 'L-sit', 'accessory')],
      foundation_tracks: [portfolioTrack('pulling_base', 'Pulling base', 'foundation')],
      maintenance_tracks: [portfolioTrack('pull_up', 'Pull-up', 'maintenance')],
      future_queue: [portfolioTrack('planche', 'Planche', 'future_queue')],
      explanation: {
        summary: 'Front lever leads the block while handstand and L-sit stay low fatigue.',
        why_this_mix: ['It balances pulling stress with low-fatigue practice.'],
        watch_out_for: ['Elbows should feel fresh before high-pull work.'],
        fallback: 'Move front lever work to easier holds when fatigue is high.',
      },
      adaptation: {
        status: 'prior_based',
        eta_basis: 'prior',
        evidence_weeks: 0,
        session_logs: 0,
        completed_module_evidence: 0,
        blend_weight: 0,
        warnings: ['No logged training evidence yet; ETA uses baseline and graph priors.'],
      },
      phase_plan: {
        phase_id: 'phase_1',
        duration_weeks: { min: 6, target: 8, max: 12 },
        duration_reason: 'A first portfolio block needs enough time to observe skill progress.',
        weekly_emphasis: ['Straight-arm pulling', 'Core compression'],
        roles: {},
        foundation_layer: [],
        retest_timing: {},
        deload_guidance: {},
        progression_rules: [],
        safety_notes: ['Do not increase hold difficulty and total volume in the same week.'],
      },
      stress_ledger: {
        axes: [
          { axis: 'straight_arm_pull', load: 8, budget: 10, status: 'watch' },
          { axis: 'wrist_extension', load: 3, budget: 8, status: 'ok' },
          { axis: 'knee', load: 2, budget: 8, status: 'ok' },
          { axis: 'trunk_compression', load: 5, budget: 8, status: 'ok' },
        ],
        notes: ['Straight-arm pulling is close to the weekly cap.'],
      },
      time_ledger: {
        estimated_minutes_per_week: 168,
        max_sessions_per_week: 3,
        remaining_minutes_per_week: 12,
        notes: ['Three sessions fit the current max schedule.'],
      },
      weekly_schedule: {
        days: [
          {
            day_index: 1,
            label: 'Monday',
            day_type: 'skill_strength',
            modules: [
              {
                module_id: 'front-lever-primer',
                skill_track_id: 'front_lever',
                title: 'Skill primer',
                purpose: 'development',
                pattern: 'isometric_skill',
                intensity_tier: 'high',
                source_mode: 'development',
                slot: 'skill',
                slot_rank: 1,
                order: 1,
                exposure_index: 1,
                estimated_minutes: 16,
                stress_vector: { straight_arm_pull: 4, trunk_compression: 2 },
              },
            ],
            stress_ledger: {
              axes: [{ axis: 'straight_arm_pull', load: 4, budget: 5, status: 'watch' }],
              warnings: ['Pulling stress is near the daily cap.'],
            },
            time_ledger: { estimated_minutes: 56, budget_minutes: 60, overflow_minutes: 0, status: 'yellow' },
            warnings: ['Keep lever work before pulling volume.'],
          },
        ],
        rest_days: [{ day_index: 2, label: 'Tuesday', day_type: 'rest' }],
        stress_ledger: {
          axes: [{ axis: 'straight_arm_pull', load: 8, budget: 10, status: 'watch' }],
          warnings: ['Weekly pull stress needs monitoring.'],
        },
        template: { sessions_per_week: 3, day_types: ['skill_strength'], slot_order: ['skill', 'strength'] },
        time_ledger: { estimated_minutes_per_week: 168, budget_minutes_per_week: 180, overflow_minutes_per_week: 0 },
        warnings: ['One high pull day needs a rest gap.'],
      },
    },
    foundation_layer: {
      summary: 'Pulling base and hollow-body strength stay in the week.',
      tracks: [portfolioTrack('pulling_base', 'Pulling base', 'foundation')],
      focus_areas: ['pull_capacity', 'core_bodyline'],
    },
    long_term_aspirations: [portfolioTrack('planche', 'Planche', 'future_queue')],
    not_recommended_now: [
      portfolioTrack('one_arm_pull_up', 'One-arm pull-up', 'not_now', {
        why_not_higher_priority: ['High elbow and pulling stress conflicts with the current front lever load.'],
      }),
    ],
    blocked: [],
    onboarding_goal_choices: {
      accessories: ['l_sit'],
      blocked: [],
      development: ['front_lever'],
      future: ['planche', 'one_arm_pull_up'],
      technical_practice: ['handstand'],
    },
    pending_tests: [],
    ...overrides,
  }
}

function portfolioTrack(
  skillTrackId: string,
  displayName: string,
  mode: string,
  overrides: Partial<Record<string, unknown>> = {},
) {
  return {
    skill_track_id: skillTrackId,
    display_name: displayName,
    current_node: { id: `${skillTrackId}.current`, label: `${displayName} current` },
    next_node: { id: `${skillTrackId}.next`, label: `${displayName} next step` },
    target_node: { id: `${skillTrackId}.target`, label: `${displayName} milestone` },
    mode,
    weekly_exposures: mode === 'future_queue' || mode === 'not_now' ? 0 : 2,
    estimated_minutes_per_week: mode === 'future_queue' || mode === 'not_now' ? 0 : 36,
    primary_stress_axes:
      skillTrackId === 'front_lever' ? ['straight_arm_pull', 'trunk_compression'] : ['wrist_extension'],
    eta_to_next_node: { min_weeks: 8, max_weeks: 16, label: '8-16 weeks' },
    confidence: { level: 'medium', score: 0.72, reasons: ['Baseline tests are complete.'] },
    adaptation: {
      status: 'prior_based',
      eta_basis: 'prior',
      evidence_weeks: 0,
      blend_weight: 0,
      next_action: 'collect_training_evidence',
      warnings: ['No logged training evidence yet; ETA uses baseline and graph priors.'],
    },
    modules: [{ title: 'Skill primer', intensity_tier: mode === 'development' ? 'high' : 'medium' }],
    why_included: [`${displayName} fits the current weekly portfolio.`],
    why_not_higher_priority: [],
    ...overrides,
  }
}

function goalCandidate(skill: string, label: string, role: string) {
  return {
    base_focus_areas: [],
    blockers: [],
    compatibility_reason: '',
    compatible_with_primary: role === 'secondary_candidate' || role === 'low_fatigue_accessory' ? true : null,
    confidence: 0.72,
    label,
    next_gate: '',
    readiness_score: 72,
    reason: `${label} fits the current roadmap mix.`,
    role,
    skill,
    status: 'ready',
    stress_class: role === 'low_fatigue_accessory' ? 'low_fatigue' : 'moderate',
    stress_tags: [],
    unlock_conditions: [],
  }
}
