import type {
  RoadmapBlocker,
  RoadmapConfidence,
  RoadmapEtaRange,
  RoadmapExplanation,
  RoadmapFoundationLane,
  RoadmapGoal,
  RoadmapIntermediate,
  RoadmapNode,
  RoadmapSuggestions,
  RoadmapTrack,
  RoadmapUnlockCondition,
} from '../types'

type UnknownRecord = Record<string, unknown>

export function emptyRoadmapSuggestions(): RoadmapSuggestions {
  return mapRoadmapSuggestions(null)
}

export function mapRoadmapSuggestions(value: unknown): RoadmapSuggestions {
  const source = recordValue(value)

  return {
    baseFocusAreas: stringArray(source.base_focus_areas ?? source.baseFocusAreas),
    blockers: recordArray(source.blockers).map(mapBlocker),
    bodyContext: {
      notes: stringArray(recordValue(source.body_context ?? source.bodyContext).notes),
    },
    bridgeTracks: recordArray(source.bridge_tracks ?? source.bridgeTracks).map(mapTrack),
    compatibilityTags: stringArray(source.compatibility_tags ?? source.compatibilityTags),
    compatibleSecondaryGoal: mapNullableGoal(source.compatible_secondary_goal ?? source.compatibleSecondaryGoal),
    confidence: mapConfidence(source.confidence),
    currentProgressionNode: mapNode(
      source.current_progression_node ?? source.currentProgressionNode,
      'foundation.current',
      'Baseline not placed yet',
    ),
    deferredGoals: recordArray(source.deferred_goals ?? source.deferredGoals).map(mapGoal),
    deferredTracks: recordArray(source.deferred_tracks ?? source.deferredTracks).map(mapTrack),
    etaRange: mapEtaRange(source.eta_range ?? source.etaRange),
    explanation: mapExplanation(source.explanation),
    foundationLane: mapFoundationLane(source.foundation_lane ?? source.foundationLane),
    intermediate: mapIntermediate(source.intermediate),
    level: stringValue(source.level, 'foundation'),
    longTermTracks: recordArray(source.long_term_tracks ?? source.longTermTracks).map(mapTrack),
    nextMilestone: mapNode(source.next_milestone ?? source.nextMilestone, 'foundation.milestone', 'First roadmap'),
    nextNode: mapNode(source.next_node ?? source.nextNode, 'foundation.next', 'Complete the assessment'),
    primaryGoal: mapNullableGoal(source.primary_goal ?? source.primaryGoal),
    summary: stringValue(source.summary, 'Complete the baseline tests to unlock a useful roadmap.'),
    unlockConditions: recordArray(source.unlock_conditions ?? source.unlockConditions).map(mapUnlockCondition),
    unlockedTracks: recordArray(source.unlocked_tracks ?? source.unlockedTracks).map(mapTrack),
    version: stringValue(source.version, 'roadmap.v2'),
  }
}

function mapTrack(value: UnknownRecord): RoadmapTrack {
  return {
    baseFocusAreas: stringArray(value.base_focus_areas ?? value.baseFocusAreas),
    compatibleSecondarySkills: stringArray(value.compatible_secondary_skills ?? value.compatibleSecondarySkills),
    label: stringValue(value.label, stringValue(value.skill, 'Roadmap target')),
    nextGate: stringValue(value.next_gate ?? value.nextGate, ''),
    reason: stringValue(value.reason, ''),
    skill: stringValue(value.skill, ''),
  }
}

function mapNullableGoal(value: unknown): RoadmapGoal | null {
  if (typeof value !== 'object' || value === null) {
    return null
  }

  return mapGoal(recordValue(value))
}

function mapGoal(value: UnknownRecord): RoadmapGoal {
  return {
    blockers: recordArray(value.blockers).map(mapBlocker),
    compatibilityTags: stringArray(value.compatibility_tags ?? value.compatibilityTags),
    confidence: mapConfidence(value.confidence),
    currentProgressionNode: mapNode(
      value.current_progression_node ?? value.currentProgressionNode,
      `${stringValue(value.skill, 'roadmap')}.current`,
      'Current placement',
    ),
    etaRange: mapEtaRange(value.eta_range ?? value.etaRange),
    explanation: stringValue(value.explanation, ''),
    label: stringValue(value.label, stringValue(value.skill, 'Roadmap target')),
    lane: stringValue(value.lane, 'primary'),
    nextMilestone: mapNode(
      value.next_milestone ?? value.nextMilestone,
      `${stringValue(value.skill, 'roadmap')}.milestone`,
      'Next milestone',
    ),
    nextNode: mapNode(
      value.next_node ?? value.nextNode,
      `${stringValue(value.skill, 'roadmap')}.next`,
      'Next progression',
    ),
    skill: stringValue(value.skill, ''),
    unlockConditions: recordArray(value.unlock_conditions ?? value.unlockConditions).map(mapUnlockCondition),
  }
}

function mapFoundationLane(value: unknown): RoadmapFoundationLane {
  const source = recordValue(value)

  return {
    currentProgressionNode: mapNode(
      source.current_progression_node ?? source.currentProgressionNode,
      'foundation.current',
      'Measured foundation',
    ),
    focusAreas: stringArray(source.focus_areas ?? source.focusAreas),
    label: stringValue(source.label, 'Foundation strength'),
    nextMilestone: mapNode(source.next_milestone ?? source.nextMilestone, 'foundation.milestone', 'Stable weekly base'),
    nextNode: mapNode(source.next_node ?? source.nextNode, 'foundation.next', 'Build repeatable base volume'),
    slug: stringValue(source.slug, 'foundation_strength'),
  }
}

function mapNode(value: unknown, fallbackId: string, fallbackLabel: string): RoadmapNode {
  const source = recordValue(value)

  return {
    id: stringValue(source.id, fallbackId),
    label: stringValue(source.label, fallbackLabel),
  }
}

function mapEtaRange(value: unknown): RoadmapEtaRange {
  const source = recordValue(value)

  return {
    label: stringValue(source.label, 'Complete assessment'),
    maxWeeks: nullableNumber(source.max_weeks ?? source.maxWeeks),
    minWeeks: nullableNumber(source.min_weeks ?? source.minWeeks),
  }
}

function mapConfidence(value: unknown): RoadmapConfidence {
  const source = recordValue(value)

  return {
    level: stringValue(source.level, 'low'),
    reasons: stringArray(source.reasons),
    score: nullableNumber(source.score),
  }
}

function mapBlocker(value: UnknownRecord): RoadmapBlocker {
  return {
    key: stringValue(value.key, ''),
    label: stringValue(value.label, ''),
    message: stringValue(value.message, ''),
    severity: stringValue(value.severity, 'watch'),
  }
}

function mapUnlockCondition(value: UnknownRecord): RoadmapUnlockCondition {
  return {
    label: stringValue(value.label, ''),
    skill: stringValue(value.skill, ''),
    status: stringValue(value.status, 'next'),
  }
}

function mapExplanation(value: unknown): RoadmapExplanation {
  const source = recordValue(value)

  return {
    fallback: stringValue(source.fallback, ''),
    summary: stringValue(source.summary, ''),
    watchOutFor: stringArray(source.watch_out_for ?? source.watchOutFor),
    whyThisGoal: stringArray(source.why_this_goal ?? source.whyThisGoal),
  }
}

function mapIntermediate(value: unknown): RoadmapIntermediate {
  const source = recordValue(value)

  return {
    compatibilityCosts: recordArray(source.compatibility_costs ?? source.compatibilityCosts),
    domainScores: recordValue(source.domain_scores ?? source.domainScores),
    domainUncertainty: recordValue(source.domain_uncertainty ?? source.domainUncertainty),
    etaModifiers: recordArray(source.eta_modifiers ?? source.etaModifiers),
    hardGateResults: recordArray(source.hard_gate_results ?? source.hardGateResults),
    progressionGraphPlacement: recordValue(source.progression_graph_placement ?? source.progressionGraphPlacement),
    readinessScores: recordArray(source.readiness_scores ?? source.readinessScores),
  }
}

function recordValue(value: unknown): UnknownRecord {
  return typeof value === 'object' && value !== null && !Array.isArray(value) ? (value as UnknownRecord) : {}
}

function recordArray(value: unknown): UnknownRecord[] {
  return Array.isArray(value) ? value.map(recordValue) : []
}

function stringArray(value: unknown): string[] {
  return Array.isArray(value) ? value.filter((item): item is string => typeof item === 'string') : []
}

function stringValue(value: unknown, fallback: string): string {
  return typeof value === 'string' && value !== '' ? value : fallback
}

function nullableNumber(value: unknown): number | null {
  if (value === null || value === undefined || value === '') {
    return null
  }

  const parsed = Number(value)

  return Number.isFinite(parsed) ? parsed : null
}
