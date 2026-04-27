import type {
  RoadmapBlocker,
  RoadmapConfidence,
  RoadmapCurrentBlockFocus,
  RoadmapDomainBottleneck,
  RoadmapEtaRange,
  RoadmapExplanation,
  RoadmapFoundationLane,
  RoadmapGoal,
  RoadmapGoalCandidate,
  RoadmapGoalCandidates,
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
    currentBlockFocus: mapCurrentBlockFocus(source.current_block_focus ?? source.currentBlockFocus),
    currentProgressionNode: mapNode(
      source.current_progression_node ?? source.currentProgressionNode,
      'foundation.current',
      'Baseline not placed yet',
    ),
    domainBottlenecks: recordArray(source.domain_bottlenecks ?? source.domainBottlenecks).map(mapDomainBottleneck),
    deferredGoals: recordArray(source.deferred_goals ?? source.deferredGoals).map(mapGoal),
    deferredTracks: recordArray(source.deferred_tracks ?? source.deferredTracks).map(mapTrack),
    etaRange: mapEtaRange(source.eta_range ?? source.etaRange),
    explanation: mapExplanation(source.explanation),
    foundationLane: mapFoundationLane(source.foundation_lane ?? source.foundationLane),
    goalCandidates: mapGoalCandidates(source.goal_candidates ?? source.goalCandidates),
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

function mapGoalCandidates(value: unknown): RoadmapGoalCandidates {
  const source = recordValue(value)

  return {
    accessories: recordArray(source.accessories).map(mapGoalCandidate),
    foundation: recordArray(source.foundation).map(mapGoalCandidate),
    future: recordArray(source.future).map(mapGoalCandidate),
    primary: recordArray(source.primary).map(mapGoalCandidate),
    secondary: recordArray(source.secondary).map(mapGoalCandidate),
  }
}

function mapGoalCandidate(value: UnknownRecord): RoadmapGoalCandidate {
  return {
    baseFocusAreas: stringArray(value.base_focus_areas ?? value.baseFocusAreas),
    blockers: stringArray(value.blockers),
    compatibilityReason: stringValue(value.compatibility_reason ?? value.compatibilityReason, ''),
    compatibleWithPrimary: nullableBoolean(value.compatible_with_primary ?? value.compatibleWithPrimary),
    confidence: nullableNumber(value.confidence),
    label: stringValue(value.label, stringValue(value.skill, 'Roadmap target')),
    nextGate: stringValue(value.next_gate ?? value.nextGate, ''),
    readinessScore: numberValue(value.readiness_score ?? value.readinessScore, 0),
    reason: stringValue(value.reason, ''),
    role: stringValue(value.role, 'long_term'),
    skill: stringValue(value.skill, ''),
    status: stringValue(value.status, 'deferred'),
    stressClass: stringValue(value.stress_class ?? value.stressClass, 'moderate'),
    stressTags: stringArray(value.stress_tags ?? value.stressTags),
    unlockConditions: stringArray(value.unlock_conditions ?? value.unlockConditions),
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
    confidence: nullableNumber(source.confidence),
    label: stringValue(source.label, 'Complete assessment'),
    maxWeeks: nullableNumber(source.max_weeks ?? source.maxWeeks),
    minWeeks: nullableNumber(source.min_weeks ?? source.minWeeks),
    modifiers: stringArray(source.modifiers),
    p50Weeks: nullableNumber(source.p50_weeks ?? source.p50Weeks),
    p80Weeks: nullableNumber(source.p80_weeks ?? source.p80Weeks),
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
    notTrainedYet: stringArray(source.not_trained_yet ?? source.notTrainedYet),
    primaryNow: stringValue(source.primary_now ?? source.primaryNow, ''),
    summary: stringValue(source.summary, ''),
    thisBlockShouldImprove: stringArray(source.this_block_should_improve ?? source.thisBlockShouldImprove),
    whatIsMissing: stringArray(source.what_is_missing ?? source.whatIsMissing),
    whatWouldChangeRecommendation: stringArray(
      source.what_would_change_recommendation ?? source.whatWouldChangeRecommendation,
    ),
    watchOutFor: stringArray(source.watch_out_for ?? source.watchOutFor),
    whyThisGoal: stringArray(source.why_this_goal ?? source.whyThisGoal),
  }
}

function mapDomainBottleneck(value: UnknownRecord): RoadmapDomainBottleneck {
  return {
    confidence: nullableNumber(value.confidence),
    domain: stringValue(value.domain, ''),
    label: stringValue(value.label, ''),
    missingInputs: stringArray(value.missing_inputs ?? value.missingInputs),
    reason: stringValue(value.reason, ''),
    score: nullableNumber(value.score),
  }
}

function mapCurrentBlockFocus(value: unknown): RoadmapCurrentBlockFocus {
  const source = recordValue(value)

  return {
    etaRange: mapEtaRange(source.eta_range ?? source.etaRange),
    focusAreas: stringArray(source.focus_areas ?? source.focusAreas),
    label: stringValue(source.label, 'Current block'),
    lanes: stringArray(source.lanes),
    retestCadence: stringArray(source.retest_cadence ?? source.retestCadence),
    shouldImprove: stringArray(source.should_improve ?? source.shouldImprove),
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

function numberValue(value: unknown, fallback: number): number {
  const parsed = nullableNumber(value)

  return parsed === null ? fallback : parsed
}

function nullableBoolean(value: unknown): boolean | null {
  return typeof value === 'boolean' ? value : null
}
