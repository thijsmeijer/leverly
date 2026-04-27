import { describe, expect, it } from 'vitest'

import { emptyRoadmapPortfolio, mapRoadmapPortfolio, mapRoadmapSuggestions } from './roadmapMapper'

describe('roadmapMapper', () => {
  it('maps level-aware goal candidates from the API payload', () => {
    const suggestions = mapRoadmapSuggestions({
      goal_candidates: {
        primary: [
          {
            skill: 'front_lever',
            label: 'Front lever',
            role: 'primary_candidate',
            status: 'ready',
            readiness_score: 82,
            confidence: 0.78,
            stress_class: 'high',
            stress_tags: ['pull', 'straight_arm_pull'],
            reason: 'Your pulling and hollow base can start lever-specific rows and tuck work.',
            blockers: [],
            unlock_conditions: ['Own tuck lever rows and 10 to 15 second tuck holds.'],
            base_focus_areas: ['pull_capacity', 'core_bodyline'],
            next_gate: 'Own tuck lever rows and 10 to 15 second tuck holds.',
            compatible_with_primary: null,
            compatibility_reason: '',
          },
        ],
        secondary: [
          {
            skill: 'l_sit',
            label: 'L-sit',
            role: 'secondary_candidate',
            status: 'ready',
            readiness_score: 76,
            confidence: 0.74,
            stress_class: 'low_fatigue',
            stress_tags: ['compression', 'support'],
            reason: 'L-sit fits as lower-fatigue supporting work.',
            blockers: [],
            unlock_conditions: [],
            base_focus_areas: ['compression'],
            next_gate: '',
            compatible_with_primary: true,
            compatibility_reason: '',
          },
        ],
        accessories: [],
        future: [],
        foundation: [
          {
            skill: 'strict_pull_up',
            label: 'Pull-up',
            role: 'owned_foundation',
            status: 'ready',
            readiness_score: 90,
            confidence: 0.82,
            stress_class: 'foundation',
            stress_tags: ['pull'],
            reason: 'Your baseline is high enough that this stays as support work.',
            blockers: [],
            unlock_conditions: [],
            base_focus_areas: ['pull_capacity'],
            next_gate: '',
            compatible_with_primary: null,
            compatibility_reason: '',
          },
        ],
      },
    })

    expect(suggestions.goalCandidates.primary[0]).toMatchObject({
      skill: 'front_lever',
      label: 'Front lever',
      role: 'primary_candidate',
      readinessScore: 82,
      stressTags: ['pull', 'straight_arm_pull'],
    })
    expect(suggestions.goalCandidates.secondary[0]?.compatibleWithPrimary).toBe(true)
    expect(suggestions.goalCandidates.foundation[0]).toMatchObject({
      role: 'owned_foundation',
      skill: 'strict_pull_up',
    })
  })

  it('maps Roadmap V3 portfolio output through a feature-local contract', () => {
    const portfolio = mapRoadmapPortfolio({
      version: 'roadmap.portfolio.v3',
      source_version: 'roadmap.v2',
      active_skill_portfolio: {
        development_tracks: [
          {
            skill_track_id: 'front_lever',
            display_name: 'Front lever',
            current_node: { id: 'front_lever.tuck', label: 'Tuck front lever' },
            next_node: { id: 'front_lever.advanced_tuck', label: 'Advanced tuck front lever' },
            target_node: { id: 'front_lever.full', label: 'Full front lever' },
            mode: 'development',
            weekly_exposures: 2,
            estimated_minutes_per_week: 34,
            primary_stress_axes: ['straight_arm_pull', 'trunk_rigidity'],
            eta_to_next_node: { min_weeks: 6, max_weeks: 12, label: '6-12 weeks' },
            confidence: { level: 'medium', score: 0.72, reasons: ['Pulling baseline is current.'] },
            modules: [],
            why_included: ['This is the clearest next pulling skill.'],
            why_not_higher_priority: [],
          },
        ],
        technical_practice_tracks: [],
        accessory_tracks: [],
        maintenance_tracks: [],
        foundation_tracks: [],
        future_queue: [],
        weekly_schedule: {
          days: [
            {
              day_index: 1,
              label: 'Day 1',
              day_type: 'pull_strength',
              modules: [
                {
                  module_id: 'front_lever.advanced_tuck.development',
                  skill_track_id: 'front_lever',
                  title: 'Advanced tuck front lever exposure',
                  purpose: 'development',
                  pattern: 'straight_arm_pull',
                  intensity_tier: 'high',
                  source_mode: 'development',
                  slot: 'skill_a',
                  slot_rank: 20,
                  order: 1,
                  exposure_index: 1,
                  estimated_minutes: 12,
                  stress_vector: { straight_arm_pull: 2 },
                },
              ],
              stress_ledger: {
                axes: [{ axis: 'straight_arm_pull', load: 2, budget: 4, status: 'green' }],
                warnings: [],
              },
              time_ledger: {
                estimated_minutes: 12,
                budget_minutes: 60,
                overflow_minutes: 0,
                status: 'green',
              },
              warnings: [],
            },
          ],
          rest_days: [{ day_index: 2, label: 'Rest day 1', day_type: 'rest' }],
          template: {
            sessions_per_week: 4,
            day_types: ['general_skills', 'pull_strength', 'push_strength', 'legs'],
            slot_order: ['warmup_prep', 'skill_a', 'primary_strength'],
          },
          stress_ledger: {
            axes: [{ axis: 'straight_arm_pull', load: 2, budget: 4, status: 'green' }],
            warnings: [],
          },
          time_ledger: {
            estimated_minutes_per_week: 34,
            budget_minutes_per_week: 240,
            overflow_minutes_per_week: 0,
          },
          warnings: [],
        },
        stress_ledger: {
          axes: [{ axis: 'straight_arm_pull', load: 2, budget: 4, status: 'green' }],
          notes: ['Stress is inside the current budget.'],
        },
        time_ledger: {
          max_sessions_per_week: 4,
          estimated_minutes_per_week: 34,
          remaining_minutes_per_week: 146,
          notes: [],
        },
        explanation: {
          summary: 'Front lever can be developed while foundations stay warm.',
          why_this_mix: ['It fits the current pull budget.'],
          watch_out_for: ['Keep elbows calm.'],
          fallback: 'Reduce lever intensity if pulling joints complain.',
        },
        phase_plan: {
          phase_id: 'current_block',
          duration_weeks: { min: 4, target: 4, max: 4 },
          duration_reason: 'First blocks use a 4-week phase.',
          weekly_emphasis: ['Prioritize development exposures while quality is freshest.'],
          roles: {
            development: [
              {
                skill_track_id: 'front_lever',
                display_name: 'Front lever',
                module_ids: ['front_lever.advanced_tuck.development'],
              },
            ],
            technical_practice: [],
            accessory: [],
            maintenance: [],
            foundation: [],
          },
          foundation_layer: [],
          retest_timing: {
            session_update: 'Log pain, readiness, and quality after each exposure.',
            weekly_review: 'Review quality, pain, and completed exposures every week.',
            block_retest_week: 4,
            seasonal_goal_review_weeks: [12, 24],
          },
          deload_guidance: {
            planned_week: 4,
            triggers: ['Planned deload/retest at the end of the phase.'],
            retest_guidance: 'Retest after the deload/retest week.',
          },
          progression_rules: [
            {
              module_id: 'front_lever.advanced_tuck.development',
              skill_track_id: 'front_lever',
              title: 'Advanced tuck front lever exposure',
              rule_type: 'static_hold',
              metric: 'hold_seconds',
              progression_allowed: true,
              next_action: 'Add a few seconds before changing leverage.',
              success_requirements: ['Own all target holds for two exposures.'],
              allowed_levers: ['hold_seconds'],
              only_one_major_lever: true,
              pain_rule: 'Pain 4/10 or higher blocks progression.',
              next_adjustment: { hold_seconds: 2 },
              deload_triggers: ['Pain reaches 4/10 or higher.'],
            },
          ],
          safety_notes: ['Progression changes only one major lever at a time by default.'],
        },
      },
      onboarding_goal_choices: {
        development: ['front_lever'],
        technical_practice: [],
        accessories: [],
        future: [],
        blocked: [],
      },
      foundation_layer: {
        tracks: [],
        summary: 'Maintain the base.',
      },
      long_term_aspirations: [],
      not_recommended_now: [],
      blocked: [],
      pending_tests: [],
      goal_candidates: {
        primary: [{ skill: 'front_lever', label: 'Front lever', role: 'primary_candidate' }],
        secondary: [],
        accessories: [],
        future: [],
        foundation: [],
      },
    })

    expect(portfolio.version).toBe('roadmap.portfolio.v3')
    expect(portfolio.sourceVersion).toBe('roadmap.v2')
    expect(portfolio.activeSkillPortfolio.developmentTracks[0]).toMatchObject({
      skillTrackId: 'front_lever',
      displayName: 'Front lever',
      mode: 'development',
      weeklyExposures: 2,
      primaryStressAxes: ['straight_arm_pull', 'trunk_rigidity'],
    })
    expect(portfolio.activeSkillPortfolio.weeklySchedule.days[0]).toMatchObject({
      dayIndex: 1,
      dayType: 'pull_strength',
    })
    expect(portfolio.activeSkillPortfolio.weeklySchedule.days[0]?.modules[0]).toMatchObject({
      moduleId: 'front_lever.advanced_tuck.development',
      slot: 'skill_a',
      stressVector: { straight_arm_pull: 2 },
    })
    expect(portfolio.activeSkillPortfolio.weeklySchedule.restDays[0]).toMatchObject({
      dayIndex: 2,
      dayType: 'rest',
    })
    expect(portfolio.activeSkillPortfolio.weeklySchedule.template.sessionsPerWeek).toBe(4)
    expect(portfolio.activeSkillPortfolio.weeklySchedule.stressLedger.axes[0]?.axis).toBe('straight_arm_pull')
    expect(portfolio.activeSkillPortfolio.weeklySchedule.timeLedger.budgetMinutesPerWeek).toBe(240)
    expect(portfolio.activeSkillPortfolio.phasePlan).toMatchObject({
      phaseId: 'current_block',
      durationWeeks: { target: 4 },
    })
    expect(portfolio.activeSkillPortfolio.phasePlan.progressionRules[0]).toMatchObject({
      moduleId: 'front_lever.advanced_tuck.development',
      ruleType: 'static_hold',
      progressionAllowed: true,
      onlyOneMajorLever: true,
    })
    expect(portfolio.activeSkillPortfolio.stressLedger.axes[0]).toMatchObject({
      axis: 'straight_arm_pull',
      status: 'green',
    })
    expect(portfolio.goalCandidates.primary[0]).toMatchObject({
      skill: 'front_lever',
      role: 'primary_candidate',
    })
  })

  it('returns stable empty portfolio defaults for V3 fallback states', () => {
    const portfolio = emptyRoadmapPortfolio()

    expect(portfolio.activeSkillPortfolio.phasePlan.durationWeeks.target).toBe(0)
    expect(portfolio.activeSkillPortfolio.phasePlan.progressionRules).toEqual([])
    expect(portfolio.activeSkillPortfolio.weeklySchedule.days).toEqual([])
    expect(portfolio.activeSkillPortfolio.weeklySchedule.restDays).toEqual([])
    expect(portfolio.activeSkillPortfolio.weeklySchedule.template.sessionsPerWeek).toBe(0)
    expect(portfolio.activeSkillPortfolio.weeklySchedule.stressLedger.axes).toEqual([])
    expect(portfolio.activeSkillPortfolio.weeklySchedule.timeLedger.estimatedMinutesPerWeek).toBe(0)
  })
})
