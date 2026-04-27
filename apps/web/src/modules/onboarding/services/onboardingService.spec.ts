import { afterEach, describe, expect, it, vi } from 'vitest'
import { mapRoadmapSuggestions } from '@/modules/roadmap'
import { configureLeverlyApiClient, resetLeverlyApiClient } from '@/shared/api/leverlyApi/runtimeClient'
import { jsonResponse } from '@/tests/http'

import {
  defaultOnboardingForm,
  fetchOnboarding,
  OnboardingValidationError,
  saveOnboarding,
  validateOnboardingStep,
} from './onboardingService'

describe('onboardingService', () => {
  afterEach(() => {
    resetLeverlyApiClient()
  })

  it('maps onboarding API state into a resumable form', async () => {
    configureLeverlyApiClient({
      fetcher: vi.fn<typeof fetch>().mockResolvedValue(jsonResponse(onboardingResponse())),
    })

    await expect(fetchOnboarding()).resolves.toMatchObject({
      form: {
        ageYears: '29',
        availableEquipment: ['pull_up_bar', 'rings'],
        baseFocusAreas: ['pull_capacity', 'core_bodyline'],
        currentBodyweightValue: '72.5',
        currentLevelTests: {
          dipMaxReps: '6',
          dipFallbackReps: '5',
          dipFallbackSeconds: '',
          dipFallbackVariant: 'assisted',
          hollowHoldSeconds: '35',
          lowerBodyLoadUnit: 'kg',
          lowerBodyLoadValue: '',
          lowerBodyReps: '12',
          lowerBodyVariant: 'split_squat',
          passiveHangSeconds: '45',
          pullUpMaxReps: '4',
          pullUpFallbackReps: '',
          pullUpFallbackSeconds: '6',
          pullUpFallbackVariant: 'eccentric',
          pushUpMaxReps: '18',
          rowMaxReps: '12',
          rowVariant: 'ring_row',
          squatBarbellLoadValue: '100',
          squatBarbellReps: '5',
          topSupportHoldSeconds: '25',
        },
        heightValue: '178',
        longTermTargetSkills: ['planche'],
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
        requiredGoalModules: ['inversion'],
        roadmapSuggestions: {
          baseFocusAreas: ['pull_capacity', 'core_bodyline'],
          blockers: [{ key: 'wrist_extension' }],
          compatibleSecondaryGoal: {
            skill: 'strict_pull_up',
          },
          confidence: {
            level: 'medium',
            score: 0.72,
          },
          etaRange: {
            label: '8-16 weeks',
            maxWeeks: 16,
            minWeeks: 8,
          },
          level: 'intermediate',
          primaryGoal: {
            skill: 'handstand',
          },
          version: 'roadmap.v2',
        },
        targetSkills: ['handstand'],
        trainingAgeMonths: '18',
        weightTrend: 'maintaining',
      },
      isComplete: false,
      onboardingId: '01kb0b6h4az3er8g7vnh9k5m1a',
    })
  })

  it('serializes draft saves and completion to the onboarding endpoint', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse(onboardingResponse({ is_complete: true })))

    configureLeverlyApiClient({
      fetcher,
      getCsrfToken: () => 'csrf-token',
    })

    const form = {
      ...defaultOnboardingForm(),
      availableEquipment: ['pull_up_bar', 'rings'],
      ageYears: '29',
      baseFocusAreas: ['pull_capacity', 'core_bodyline'],
      currentBodyweightValue: '72.5',
      currentLevelTests: {
        ...defaultOnboardingForm().currentLevelTests,
        dipMaxReps: '6',
        dipFallbackReps: '5',
        dipFallbackVariant: 'assisted',
        hollowHoldSeconds: '35',
        lowerBodyReps: '12',
        lowerBodyVariant: 'split_squat',
        passiveHangSeconds: '45',
        pullUpMaxReps: '4',
        pullUpFallbackSeconds: '6',
        pullUpFallbackVariant: 'eccentric',
        pushUpMaxReps: '18',
        rowMaxReps: '12',
        rowVariant: 'ring_row',
        squatBarbellLoadValue: '100',
        squatBarbellReps: '5',
        topSupportHoldSeconds: '25',
      },
      heightValue: '178',
      longTermTargetSkills: ['planche'],
      mobilityChecks: {
        ...defaultOnboardingForm().mobilityChecks,
        wrist_extension: 'limited',
      },
      preferredSessionMinutes: '60',
      preferredTrainingDays: ['monday', 'wednesday', 'friday'],
      priorSportBackground: ['strength_training'],
      goalModules: {
        ...defaultOnboardingForm().goalModules,
        inversion: {
          ...defaultOnboardingForm().goalModules.inversion,
          highestProgression: 'freestanding_kick_up',
          holdSeconds: '20',
          metricType: 'hold_seconds',
          quality: 'solid',
        },
      },
      primaryTargetSkill: 'handstand',
      requiredGoalModules: ['inversion'],
      weightTrend: 'maintaining',
      targetSkills: ['handstand'],
      secondaryTargetSkills: ['strict_pull_up'],
      trainingLocations: ['home'],
    }

    await expect(saveOnboarding(form, { complete: true })).resolves.toMatchObject({
      isComplete: true,
    })

    expect(fetcher).toHaveBeenNthCalledWith(
      2,
      '/api/v1/me/onboarding',
      expect.objectContaining({
        body: expect.stringContaining('"complete":true'),
        method: 'PATCH',
      }),
    )
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"target_skills":["handstand"]')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"secondary_target_skills":["strict_pull_up"]')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain(
      '"goal_modules":{"inversion":{"highest_progression":"freestanding_kick_up","metric_type":"hold_seconds","reps":null,"hold_seconds":20,"load_value":null,"load_unit":"kg","quality":"solid","notes":null}}',
    )
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"age_years":29')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"hollow_hold_seconds":35')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"passive_hang_seconds":45')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"top_support_hold_seconds":25')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"rows":{"max_reps":12,"variant":"ring_row"}')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"fallback_variant":"eccentric"')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain(
      '"lower_body":{"load_unit":"kg","load_value":null,"reps":null,"variant":"bodyweight_squat"}',
    )
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"barbell_load_value":100')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"weight_trend":"maintaining"')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"pain_flags":{"wrist"')
  })

  it('maps server validation errors to onboarding fields', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(
        jsonResponse(
          {
            errors: {
              target_skills: ['Choose a target skill.'],
            },
            message: 'The given data was invalid.',
          },
          { status: 422 },
        ),
      )

    configureLeverlyApiClient({
      fetcher,
    })

    await expect(saveOnboarding(defaultOnboardingForm(), { complete: true })).rejects.toMatchObject({
      errors: {
        targetSkills: 'Choose a target skill.',
      },
    } satisfies Pick<OnboardingValidationError, 'errors'>)
  })

  it('serializes numeric values emitted by browser number inputs', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse(onboardingResponse()))

    configureLeverlyApiClient({
      fetcher,
      getCsrfToken: () => 'csrf-token',
    })

    const form = {
      ...defaultOnboardingForm(),
      priorSportBackground: ['none'],
    }

    Object.assign(form, {
      ageYears: 29,
      currentBodyweightValue: 72.5,
      heightValue: 178,
      trainingAgeMonths: 18,
    })

    await expect(saveOnboarding(form)).resolves.toMatchObject({
      onboardingId: '01kb0b6h4az3er8g7vnh9k5m1a',
    })

    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"age_years":29')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"current_bodyweight_value":72.5')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"height_value":178')
    expect(String(fetcher.mock.calls[1]?.[1]?.body)).toContain('"training_age_months":18')
  })

  it('validates required step inputs locally', () => {
    expect(
      validateOnboardingStep(
        {
          ...defaultOnboardingForm(),
          currentLevelTests: {
            ...defaultOnboardingForm().currentLevelTests,
            dipMaxReps: '',
            dipFallbackReps: '',
            dipFallbackSeconds: '',
            dipFallbackVariant: 'none',
            hollowHoldSeconds: '',
            lowerBodyLoadUnit: 'kg',
            lowerBodyLoadValue: '',
            lowerBodyReps: '',
            lowerBodyVariant: 'bodyweight_squat',
            passiveHangSeconds: '',
            pullUpMaxReps: '',
            pullUpFallbackReps: '',
            pullUpFallbackSeconds: '',
            pullUpFallbackVariant: 'none',
            pushUpMaxReps: '',
            rowMaxReps: '',
            rowVariant: 'bodyweight_row',
            squatBarbellLoadValue: '',
            squatBarbellReps: '',
            topSupportHoldSeconds: '',
          },
          targetSkills: [],
        },
        'level',
      ),
    ).toMatchObject({
      'currentLevelTests.dipMaxReps': 'Enter a number from 0 to 100.',
      'currentLevelTests.lowerBodyReps': 'Enter a number from 0 to 100.',
      'currentLevelTests.passiveHangSeconds': 'Enter a number from 0 to 600.',
      'currentLevelTests.hollowHoldSeconds': 'Enter a number from 0 to 600.',
      'currentLevelTests.pullUpMaxReps': 'Enter a number from 0 to 100.',
      'currentLevelTests.pushUpMaxReps': 'Enter a number from 0 to 200.',
      'currentLevelTests.rowMaxReps': 'Enter a number from 0 to 100.',
      'currentLevelTests.topSupportHoldSeconds': 'Enter a number from 0 to 600.',
    })
  })

  it('uses lower-body fallback only when complete barbell squat data is missing', async () => {
    const fetcher = vi
      .fn<typeof fetch>()
      .mockResolvedValueOnce(new Response(null, { status: 204 }))
      .mockResolvedValueOnce(jsonResponse(onboardingResponse()))

    configureLeverlyApiClient({
      fetcher,
      getCsrfToken: () => 'csrf-token',
    })

    const currentLevelTests = {
      ...defaultOnboardingForm().currentLevelTests,
      dipMaxReps: '6',
      hollowHoldSeconds: '35',
      lowerBodyReps: '',
      passiveHangSeconds: '45',
      pullUpMaxReps: '4',
      pushUpMaxReps: '18',
      rowMaxReps: '12',
      squatBarbellLoadValue: '100',
      squatBarbellReps: '5',
      topSupportHoldSeconds: '25',
    }

    expect(
      validateOnboardingStep(
        {
          ...defaultOnboardingForm(),
          currentLevelTests,
        },
        'level',
      ),
    ).not.toHaveProperty('currentLevelTests.lowerBodyReps')

    expect(
      validateOnboardingStep(
        {
          ...defaultOnboardingForm(),
          currentLevelTests: {
            ...currentLevelTests,
            squatBarbellLoadValue: '',
            squatBarbellReps: '',
          },
        },
        'level',
      ),
    ).toMatchObject({
      'currentLevelTests.lowerBodyReps': 'Enter a number from 0 to 100.',
    })

    await expect(
      saveOnboarding({
        ...defaultOnboardingForm(),
        currentLevelTests: {
          ...currentLevelTests,
          lowerBodyLoadValue: '20',
          lowerBodyReps: '12',
          lowerBodyVariant: 'split_squat',
        },
        priorSportBackground: ['none'],
      }),
    ).resolves.toMatchObject({
      onboardingId: '01kb0b6h4az3er8g7vnh9k5m1a',
    })

    const body = JSON.parse(String(fetcher.mock.calls[1]?.[1]?.body)) as {
      current_level_tests: {
        lower_body: Record<string, unknown>
      }
    }

    expect(body.current_level_tests.lower_body).toEqual({
      load_unit: 'kg',
      load_value: null,
      reps: null,
      variant: 'bodyweight_squat',
    })
  })

  it('validates Roadmap V2 goal and module steps separately', () => {
    const form = {
      ...defaultOnboardingForm(),
      baseFocusAreas: ['core_bodyline'],
      goalModules: {
        ...defaultOnboardingForm().goalModules,
        inversion: {
          ...defaultOnboardingForm().goalModules.inversion,
          highestProgression: 'not_tested',
          holdSeconds: '',
          metricType: 'hold_seconds',
        },
      },
      primaryGoal: 'skill',
      primaryTargetSkill: 'handstand',
      requiredGoalModules: ['inversion'],
      roadmapSuggestions: mapRoadmapSuggestions(roadmapSuggestions()),
      targetSkills: ['handstand'],
    }

    expect(validateOnboardingStep({ ...form, primaryTargetSkill: '', targetSkills: [] }, 'goal')).toMatchObject({
      primaryTargetSkill: 'Choose the one suggested target Leverly should prioritize first.',
    })
    expect(validateOnboardingStep(form, 'goal')).not.toHaveProperty('goalModules.inversion')
    expect(validateOnboardingStep(form, 'modules')).toMatchObject({
      'goalModules.inversion': 'Add the tested progression for the selected primary goal.',
    })
  })

  it('validates goal selections against level-aware roadmap candidates when present', () => {
    const suggestions = mapRoadmapSuggestions({
      base_focus_areas: ['pull_capacity'],
      goal_candidates: {
        accessories: [goalCandidate('l_sit', 'L-sit', 'low_fatigue_accessory')],
        foundation: [goalCandidate('strict_pull_up', 'Pull-up', 'owned_foundation')],
        future: [goalCandidate('one_arm_pull_up', 'One-arm pull-up', 'long_term')],
        primary: [goalCandidate('front_lever', 'Front lever', 'primary_candidate')],
        secondary: [goalCandidate('handstand', 'Handstand', 'secondary_candidate')],
      },
    })
    const form = {
      ...defaultOnboardingForm(),
      baseFocusAreas: ['pull_capacity'],
      primaryGoal: 'skill',
      primaryTargetSkill: 'front_lever',
      roadmapSuggestions: suggestions,
      secondaryTargetSkills: ['handstand', 'l_sit'],
      targetSkills: ['front_lever'],
    }

    expect(validateOnboardingStep(form, 'goal')).toEqual({})
    expect(
      validateOnboardingStep(
        {
          ...form,
          primaryTargetSkill: 'strict_pull_up',
          targetSkills: ['strict_pull_up'],
        },
        'goal',
      ),
    ).toMatchObject({
      primaryTargetSkill: 'Primary target must come from the recommended roadmap candidates.',
    })
    expect(
      validateOnboardingStep(
        {
          ...form,
          secondaryTargetSkills: ['one_arm_pull_up'],
        },
        'goal',
      ),
    ).toMatchObject({
      secondaryTargetSkills: 'Secondary interests must come from compatible or foundation support candidates.',
    })
  })

  it('validates pain flags inside the pain and mobility step', () => {
    expect(
      validateOnboardingStep(
        {
          ...defaultOnboardingForm(),
          painLevel: '5',
        },
        'mobility',
      ),
    ).toMatchObject({
      mobilityChecks: 'Mark at least one position so the first plan can avoid obvious blockers.',
      painAreas: 'Choose the painful area so recommendations can stay conservative.',
    })
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
          max_strict_reps: 6,
          fallback_variant: 'assisted',
          fallback_reps: 5,
          fallback_seconds: null,
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
          max_strict_reps: 4,
          fallback_variant: 'eccentric',
          fallback_reps: null,
          fallback_seconds: 6,
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
      pain_flags: {
        ankle: { severity: 'none', status: 'none', notes: null },
        elbow: { severity: 'none', status: 'none', notes: null },
        knee: { severity: 'none', status: 'none', notes: null },
        low_back: { severity: 'none', status: 'none', notes: null },
        shoulder: { severity: 'none', status: 'none', notes: null },
        wrist: { severity: 'mild', status: 'recurring', notes: 'Wrists need warm-up.' },
      },
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

function roadmapSuggestions() {
  return {
    version: 'roadmap.v2',
    base_focus_areas: ['pull_capacity', 'core_bodyline'],
    body_context: {
      notes: [],
    },
    blockers: [
      {
        key: 'wrist_extension',
        label: 'Wrist extension',
        severity: 'watch',
        message: 'Wrist extension is limited, so keep handstand volume progressive.',
      },
    ],
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
    compatibility_tags: ['overhead', 'wrist_extension', 'skill_practice'],
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
    current_progression_node: { id: 'handstand.current', label: 'Wall line and entry work' },
    eta_range: { min_weeks: 8, max_weeks: 16, label: '8-16 weeks' },
    explanation: {
      summary: 'Handstand is the clearest first roadmap priority from the current assessment.',
      why_this_goal: ['Handstand pairs with the current pressing and bodyline base.'],
      watch_out_for: ['Keep wrist loading progressive.'],
      fallback: 'Keep handstand as line practice if wrists feel irritated.',
    },
    foundation_lane: {
      slug: 'foundation_strength',
      label: 'Foundation strength',
      focus_areas: ['pull_capacity', 'core_bodyline'],
      current_progression_node: { id: 'foundation.current', label: 'Measured foundation' },
      next_node: { id: 'foundation.next', label: 'Build repeatable base volume' },
      next_milestone: { id: 'foundation.milestone', label: 'Stable weekly base' },
    },
    intermediate: {
      compatibility_costs: [{ skill: 'strict_pull_up', cost: 0.12, reasons: ['Low overlap with handstand.'] }],
      domain_scores: {
        vertical_pull: { score: 0.4, inputs: ['pull_ups'], label: 'Vertical pull' },
      },
      domain_uncertainty: {
        vertical_pull: { score: 0.2, missing_inputs: [] },
      },
      eta_modifiers: [{ key: 'training_age', multiplier: 1, reason: 'Training age supports normal ramp.' }],
      hard_gate_results: [{ key: 'pain', passed: true, severity: 'watch', message: 'Pain is low.' }],
      progression_graph_placement: {
        primary: { skill: 'handstand', node: 'handstand.current', completion: 0.45 },
      },
      readiness_scores: [{ skill: 'handstand', score: 0.68, reasons: ['Pressing and hollow data are present.'] }],
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
    next_milestone: { id: 'handstand.milestone', label: '30-second wall line' },
    next_node: { id: 'handstand.next', label: 'Build wall line consistency' },
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
        reason: 'Your wall handstand is ready for regular handstand practice.',
        skill: 'handstand',
      },
    ],
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
    reason: `${label} candidate.`,
    role,
    skill,
    status: 'ready',
    stress_class: role === 'low_fatigue_accessory' ? 'low_fatigue' : 'moderate',
    stress_tags: [],
    unlock_conditions: [],
  }
}
