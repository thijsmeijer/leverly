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
            skill: 'handstand',
          },
          version: 'roadmap.v2',
        },
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
      roadmap_suggestions: {
        version: 'roadmap.v2',
        base_focus_areas: ['pull_capacity', 'core_bodyline'],
        body_context: { notes: [] },
        blockers: [],
        bridge_tracks: [],
        compatibility_tags: ['overhead', 'wrist_extension'],
        compatible_secondary_goal: null,
        confidence: { level: 'medium', score: 0.72, reasons: ['Baseline tests are complete.'] },
        current_progression_node: { id: 'handstand.current', label: 'Wall line and entry work' },
        deferred_goals: [],
        deferred_tracks: [],
        eta_range: { min_weeks: 8, max_weeks: 16, label: '8-16 weeks' },
        explanation: {
          summary: 'Handstand is the clearest first roadmap priority from the current assessment.',
          why_this_goal: ['Handstand pairs with the current base.'],
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
          confidence: { level: 'medium', score: 0.72, reasons: ['Baseline tests are complete.'] },
          blockers: [],
          unlock_conditions: [],
          compatibility_tags: ['overhead', 'wrist_extension'],
          explanation: 'Handstand is the clearest first roadmap priority from the current assessment.',
        },
        summary: 'You have enough base strength for a focused skill roadmap plus one light secondary exposure.',
        unlock_conditions: [],
        unlocked_tracks: [],
      },
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
