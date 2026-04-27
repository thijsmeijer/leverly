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

    const state = await fetchProfileSettings()

    expect(state).toMatchObject({
      form: {
        availableEquipment: ['pull_up_bar', 'rings'],
        baseFocusAreas: ['pull_capacity', 'core_bodyline'],
        baselineTests: {
          passiveHangSeconds: '45',
          rowMaxReps: '12',
          squatBarbellLoadValue: '100',
          topSupportHoldSeconds: '25',
        },
        currentBodyweightValue: '72.5',
        displayName: 'Ada Athlete',
        movementLimitation: {
          area: 'wrist',
          notes: 'Needs warm-up.',
          severity: 'mild',
          status: 'recurring',
        },
        painFlags: {
          wrist: {
            severity: 'mild',
            status: 'recurring',
          },
        },
        goalModules: {
          inversion: {
            highestProgression: 'freestanding_kick_up',
            holdSeconds: '20',
            metricType: 'hold_seconds',
            quality: 'solid',
          },
        },
        primaryTargetSkill: 'handstand',
        preferredTrainingDays: ['monday', 'wednesday'],
        requiredGoalModules: ['inversion'],
        secondaryTargetSkills: ['strict_pull_up'],
        targetSkillsText: 'handstand',
        weightTrend: 'maintaining',
        roadmapSuggestions: {
          confidence: {
            level: 'medium',
          },
          etaRange: {
            label: '8-16 weeks',
          },
          primaryGoal: {
            skill: 'front_lever',
          },
          version: 'roadmap.portfolio.v3',
        },
      },
      profileId: '01kb0b6h4az3er8g7vnh9k5m1a',
    })

    expect(state.form.roadmapPortfolio.version).toBe('roadmap.portfolio.v3')
    expect(state.form.roadmapPortfolio.activeSkillPortfolio.developmentTracks[0]?.skillTrackId).toBe('front_lever')
    expect(state.form.roadmapPortfolio.activeSkillPortfolio.adaptation).toMatchObject({
      status: 'prior_based',
      etaBasis: 'prior',
    })
    expect(state.form.roadmapPortfolio.activeSkillPortfolio.developmentTracks[0]?.adaptation).toMatchObject({
      nextAction: 'collect_training_evidence',
    })
    expect(state.form.roadmapPortfolio.activeSkillPortfolio.stressLedger.axes).toEqual(
      expect.arrayContaining([expect.objectContaining({ axis: 'straight_arm_pull', status: 'watch' })]),
    )
    expect(state.form.roadmapPortfolio.activeSkillPortfolio.weeklySchedule.days[0]?.modules[0]?.title).toBe(
      'Skill primer',
    )
    expect(state.form.roadmapPortfolio.notRecommendedNow[0]?.skillTrackId).toBe('one_arm_pull_up')
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
      painFlags: {
        ...defaultProfileSettingsForm().painFlags,
        wrist: {
          notes: 'Needs warm-up.',
          severity: 'mild',
          status: 'recurring',
        },
      },
      goalModules: {
        ...defaultProfileSettingsForm().goalModules,
        inversion: {
          ...defaultProfileSettingsForm().goalModules.inversion,
          highestProgression: 'freestanding_kick_up',
          holdSeconds: '20',
          metricType: 'hold_seconds',
          quality: 'solid',
        },
      },
      primaryTargetSkill: 'handstand',
      preferredSessionMinutes: '60',
      preferredTrainingDays: ['monday', 'friday'],
      requiredGoalModules: ['inversion'],
      secondaryTargetSkills: ['strict_pull_up'],
      targetSkillsText: 'handstand',
      trainingAgeMonths: '18',
      weightTrend: 'maintaining',
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
        body: expect.stringContaining('"target_skills":["handstand"]'),
        credentials: 'include',
        method: 'PATCH',
      }),
    )
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"secondary_target_skills":["strict_pull_up"]')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain(
      '"goal_modules":{"inversion":{"highest_progression":"freestanding_kick_up","metric_type":"hold_seconds","reps":null,"hold_seconds":20,"load_value":null,"load_unit":"kg","quality":"solid","notes":null}}',
    )
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"weight_trend":"maintaining"')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"pain_flags":{"wrist"')
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
      weeklySessionGoal: 'Max sessions per week must be between 1 and 14.',
    })
  })
})

function profileResponse(overrides: Partial<Record<string, unknown>> = {}) {
  return {
    data: {
      available_equipment: ['pull_up_bar', 'rings'],
      base_focus_areas: ['pull_capacity', 'core_bodyline'],
      baseline_tests: {
        dips: { max_strict_reps: 6, fallback_variant: 'assisted', fallback_reps: 5, fallback_seconds: null },
        hollow_hold_seconds: 35,
        lower_body: { variant: 'split_squat', load_value: null, load_unit: 'kg', reps: 12 },
        passive_hang_seconds: 45,
        pull_ups: { max_strict_reps: 4, fallback_variant: 'eccentric', fallback_reps: null, fallback_seconds: 6 },
        push_ups: { max_strict_reps: 18 },
        rows: { variant: 'ring_row', max_reps: 12 },
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
      pain_flags: {
        ankle: { severity: 'none', status: 'none', notes: null },
        elbow: { severity: 'none', status: 'none', notes: null },
        knee: { severity: 'none', status: 'none', notes: null },
        low_back: { severity: 'none', status: 'none', notes: null },
        shoulder: { severity: 'none', status: 'none', notes: null },
        wrist: { severity: 'mild', status: 'recurring', notes: 'Needs warm-up.' },
      },
      mobility_checks: {
        ankle_dorsiflexion: 'limited',
        pancake_compression: 'not_tested',
        shoulder_extension: 'clear',
        shoulder_flexion: 'clear',
        wrist_extension: 'limited',
      },
      preferred_session_minutes: 60,
      preferred_training_days: ['monday', 'wednesday'],
      primary_goal: 'skill',
      primary_target_skill: 'handstand',
      progression_pace: 'balanced',
      roadmap_suggestions: roadmapPortfolioResponse(),
      secondary_goals: ['strength'],
      secondary_target_skills: ['strict_pull_up'],
      session_structure_preferences: ['skill_first'],
      skill_statuses: {
        handstand: {
          best_hold_seconds: 20,
          status: 'freestanding_kick_up',
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
      accessories: [],
      foundation: [],
      future: [],
      primary: [],
      secondary: [],
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
