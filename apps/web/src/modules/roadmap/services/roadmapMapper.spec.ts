import { describe, expect, it } from 'vitest'

import { mapRoadmapSuggestions } from './roadmapMapper'

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
})
