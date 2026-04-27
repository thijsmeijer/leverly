import { describe, expect, it } from 'vitest'

import { mapRoadmapPortfolio, mapRoadmapSuggestions } from './roadmapMapper'

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
              modules: [],
              warnings: [],
            },
          ],
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
    expect(portfolio.activeSkillPortfolio.stressLedger.axes[0]).toMatchObject({
      axis: 'straight_arm_pull',
      status: 'green',
    })
    expect(portfolio.goalCandidates.primary[0]).toMatchObject({
      skill: 'front_lever',
      role: 'primary_candidate',
    })
  })
})
