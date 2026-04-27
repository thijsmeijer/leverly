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
  RoadmapPortfolio,
  RoadmapPortfolioDayTimeLedger,
  RoadmapPortfolioExplanation,
  RoadmapPortfolioFoundationLayer,
  RoadmapPortfolioGoalChoices,
  RoadmapPortfolioPhasePlan,
  RoadmapPortfolioProgressionRule,
  RoadmapPortfolioRestDay,
  RoadmapPortfolioScheduledDay,
  RoadmapPortfolioScheduledModule,
  RoadmapPortfolioScheduleStressLedger,
  RoadmapPortfolioScheduleTemplate,
  RoadmapPortfolioStressAxis,
  RoadmapPortfolioStressLedger,
  RoadmapPortfolioTimeLedger,
  RoadmapPortfolioTrack,
  RoadmapPortfolioWeeklyTimeLedger,
  RoadmapPortfolioWeeklySchedule,
  RoadmapSuggestions,
  RoadmapTrack,
  RoadmapUnlockCondition,
} from '../types'

type UnknownRecord = Record<string, unknown>

export function emptyRoadmapSuggestions(): RoadmapSuggestions {
  return mapRoadmapSuggestions(null)
}

export function emptyRoadmapPortfolio(): RoadmapPortfolio {
  return mapRoadmapPortfolio(null)
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

export function mapRoadmapPortfolio(value: unknown): RoadmapPortfolio {
  const source = recordValue(value)
  const activePortfolio = recordValue(source.active_skill_portfolio ?? source.activeSkillPortfolio)

  return {
    activeSkillPortfolio: {
      accessoryTracks: recordArray(activePortfolio.accessory_tracks ?? activePortfolio.accessoryTracks).map(
        mapPortfolioTrack,
      ),
      developmentTracks: recordArray(activePortfolio.development_tracks ?? activePortfolio.developmentTracks).map(
        mapPortfolioTrack,
      ),
      explanation: mapPortfolioExplanation(activePortfolio.explanation),
      foundationTracks: recordArray(activePortfolio.foundation_tracks ?? activePortfolio.foundationTracks).map(
        mapPortfolioTrack,
      ),
      futureQueue: recordArray(activePortfolio.future_queue ?? activePortfolio.futureQueue).map(mapPortfolioTrack),
      maintenanceTracks: recordArray(activePortfolio.maintenance_tracks ?? activePortfolio.maintenanceTracks).map(
        mapPortfolioTrack,
      ),
      phasePlan: mapPortfolioPhasePlan(activePortfolio.phase_plan ?? activePortfolio.phasePlan),
      stressLedger: mapPortfolioStressLedger(activePortfolio.stress_ledger ?? activePortfolio.stressLedger),
      technicalPracticeTracks: recordArray(
        activePortfolio.technical_practice_tracks ?? activePortfolio.technicalPracticeTracks,
      ).map(mapPortfolioTrack),
      timeLedger: mapPortfolioTimeLedger(activePortfolio.time_ledger ?? activePortfolio.timeLedger),
      weeklySchedule: mapPortfolioWeeklySchedule(activePortfolio.weekly_schedule ?? activePortfolio.weeklySchedule),
    },
    blocked: recordArray(source.blocked).map(mapPortfolioTrack),
    foundationLayer: mapPortfolioFoundationLayer(source.foundation_layer ?? source.foundationLayer),
    goalCandidates: mapGoalCandidates(source.goal_candidates ?? source.goalCandidates),
    longTermAspirations: recordArray(source.long_term_aspirations ?? source.longTermAspirations).map(mapPortfolioTrack),
    notRecommendedNow: recordArray(source.not_recommended_now ?? source.notRecommendedNow).map(mapPortfolioTrack),
    onboardingGoalChoices: mapPortfolioGoalChoices(source.onboarding_goal_choices ?? source.onboardingGoalChoices),
    pendingTests: recordArray(source.pending_tests ?? source.pendingTests),
    sourceVersion: stringValue(source.source_version ?? source.sourceVersion, 'roadmap.v2'),
    summary: stringValue(source.summary, 'Complete the baseline tests to unlock a useful roadmap.'),
    version: stringValue(source.version, 'roadmap.portfolio.v3'),
  }
}

function mapPortfolioTrack(value: UnknownRecord): RoadmapPortfolioTrack {
  const skillTrackId = stringValue(value.skill_track_id ?? value.skillTrackId, '')

  return {
    confidence: mapConfidence(value.confidence),
    currentNode: mapNode(value.current_node ?? value.currentNode, `${skillTrackId}.current`, 'Current placement'),
    displayName: stringValue(value.display_name ?? value.displayName, stringValue(value.label, 'Roadmap target')),
    estimatedMinutesPerWeek: numberValue(value.estimated_minutes_per_week ?? value.estimatedMinutesPerWeek, 0),
    etaToNextNode: mapEtaRange(value.eta_to_next_node ?? value.etaToNextNode),
    mode: stringValue(value.mode, 'future_queue'),
    modules: recordArray(value.modules),
    nextNode: mapNode(value.next_node ?? value.nextNode, `${skillTrackId}.next`, 'Next progression'),
    primaryStressAxes: stringArray(value.primary_stress_axes ?? value.primaryStressAxes),
    skillTrackId,
    targetNode: mapNode(value.target_node ?? value.targetNode, `${skillTrackId}.target`, 'Target progression'),
    weeklyExposures: numberValue(value.weekly_exposures ?? value.weeklyExposures, 0),
    whyIncluded: stringArray(value.why_included ?? value.whyIncluded),
    whyNotHigherPriority: stringArray(value.why_not_higher_priority ?? value.whyNotHigherPriority),
  }
}

function mapPortfolioWeeklySchedule(value: unknown): RoadmapPortfolioWeeklySchedule {
  const source = recordValue(value)

  return {
    days: recordArray(source.days).map(mapPortfolioScheduledDay),
    restDays: recordArray(source.rest_days ?? source.restDays).map(mapPortfolioRestDay),
    stressLedger: mapPortfolioScheduleStressLedger(source.stress_ledger ?? source.stressLedger),
    template: mapPortfolioScheduleTemplate(source.template),
    timeLedger: mapPortfolioWeeklyTimeLedger(source.time_ledger ?? source.timeLedger),
    warnings: stringArray(source.warnings),
  }
}

function mapPortfolioScheduledDay(value: UnknownRecord): RoadmapPortfolioScheduledDay {
  return {
    dayIndex: numberValue(value.day_index ?? value.dayIndex, 0),
    dayType: stringValue(value.day_type ?? value.dayType, 'rest'),
    label: stringValue(value.label, 'Day'),
    modules: recordArray(value.modules).map(mapPortfolioScheduledModule),
    stressLedger: mapPortfolioScheduleStressLedger(value.stress_ledger ?? value.stressLedger),
    timeLedger: mapPortfolioDayTimeLedger(value.time_ledger ?? value.timeLedger),
    warnings: stringArray(value.warnings),
  }
}

function mapPortfolioScheduledModule(value: UnknownRecord): RoadmapPortfolioScheduledModule {
  return {
    estimatedMinutes: numberValue(value.estimated_minutes ?? value.estimatedMinutes, 0),
    exposureIndex: numberValue(value.exposure_index ?? value.exposureIndex, 0),
    intensityTier: stringValue(value.intensity_tier ?? value.intensityTier, 'medium'),
    moduleId: stringValue(value.module_id ?? value.moduleId, ''),
    order: numberValue(value.order, 0),
    pattern: stringValue(value.pattern, 'general'),
    purpose: stringValue(value.purpose, 'training'),
    skillTrackId: stringValue(value.skill_track_id ?? value.skillTrackId, ''),
    slot: stringValue(value.slot, 'accessory'),
    slotRank: numberValue(value.slot_rank ?? value.slotRank, 0),
    sourceMode: stringValue(value.source_mode ?? value.sourceMode, ''),
    stressVector: numberRecord(value.stress_vector ?? value.stressVector),
    title: stringValue(value.title, 'Training module'),
  }
}

function mapPortfolioRestDay(value: UnknownRecord): RoadmapPortfolioRestDay {
  return {
    dayIndex: numberValue(value.day_index ?? value.dayIndex, 0),
    dayType: stringValue(value.day_type ?? value.dayType, 'rest'),
    label: stringValue(value.label, 'Rest day'),
  }
}

function mapPortfolioScheduleTemplate(value: unknown): RoadmapPortfolioScheduleTemplate {
  const source = recordValue(value)

  return {
    dayTypes: stringArray(source.day_types ?? source.dayTypes),
    sessionsPerWeek: numberValue(source.sessions_per_week ?? source.sessionsPerWeek, 0),
    slotOrder: stringArray(source.slot_order ?? source.slotOrder),
  }
}

function mapPortfolioScheduleStressLedger(value: unknown): RoadmapPortfolioScheduleStressLedger {
  const source = recordValue(value)

  return {
    axes: recordArray(source.axes).map(mapPortfolioStressAxis),
    warnings: stringArray(source.warnings),
  }
}

function mapPortfolioDayTimeLedger(value: unknown): RoadmapPortfolioDayTimeLedger {
  const source = recordValue(value)

  return {
    budgetMinutes: numberValue(source.budget_minutes ?? source.budgetMinutes, 0),
    estimatedMinutes: numberValue(source.estimated_minutes ?? source.estimatedMinutes, 0),
    overflowMinutes: numberValue(source.overflow_minutes ?? source.overflowMinutes, 0),
    status: stringValue(source.status, 'green'),
  }
}

function mapPortfolioWeeklyTimeLedger(value: unknown): RoadmapPortfolioWeeklyTimeLedger {
  const source = recordValue(value)

  return {
    budgetMinutesPerWeek: numberValue(source.budget_minutes_per_week ?? source.budgetMinutesPerWeek, 0),
    estimatedMinutesPerWeek: numberValue(source.estimated_minutes_per_week ?? source.estimatedMinutesPerWeek, 0),
    overflowMinutesPerWeek: numberValue(source.overflow_minutes_per_week ?? source.overflowMinutesPerWeek, 0),
  }
}

function mapPortfolioStressLedger(value: unknown): RoadmapPortfolioStressLedger {
  const source = recordValue(value)

  return {
    axes: recordArray(source.axes).map(mapPortfolioStressAxis),
    notes: stringArray(source.notes),
  }
}

function mapPortfolioPhasePlan(value: unknown): RoadmapPortfolioPhasePlan {
  const source = recordValue(value)
  const durationWeeks = recordValue(source.duration_weeks ?? source.durationWeeks)

  return {
    deloadGuidance: recordValue(source.deload_guidance ?? source.deloadGuidance),
    durationReason: stringValue(source.duration_reason ?? source.durationReason, ''),
    durationWeeks: {
      max: numberValue(durationWeeks.max, 0),
      min: numberValue(durationWeeks.min, 0),
      target: numberValue(durationWeeks.target, 0),
    },
    foundationLayer: recordArray(source.foundation_layer ?? source.foundationLayer),
    phaseId: stringValue(source.phase_id ?? source.phaseId, 'current_block'),
    progressionRules: recordArray(source.progression_rules ?? source.progressionRules).map(mapPortfolioProgressionRule),
    retestTiming: recordValue(source.retest_timing ?? source.retestTiming),
    roles: mapRecordArrayMap(source.roles),
    safetyNotes: stringArray(source.safety_notes ?? source.safetyNotes),
    weeklyEmphasis: stringArray(source.weekly_emphasis ?? source.weeklyEmphasis),
  }
}

function mapPortfolioProgressionRule(value: UnknownRecord): RoadmapPortfolioProgressionRule {
  return {
    allowedLevers: stringArray(value.allowed_levers ?? value.allowedLevers),
    deloadTriggers: stringArray(value.deload_triggers ?? value.deloadTriggers),
    metric: stringValue(value.metric, ''),
    moduleId: stringValue(value.module_id ?? value.moduleId, ''),
    nextAction: stringValue(value.next_action ?? value.nextAction, ''),
    nextAdjustment: recordValue(value.next_adjustment ?? value.nextAdjustment),
    onlyOneMajorLever: booleanValue(value.only_one_major_lever ?? value.onlyOneMajorLever, true),
    painRule: stringValue(value.pain_rule ?? value.painRule, ''),
    progressionAllowed: booleanValue(value.progression_allowed ?? value.progressionAllowed, false),
    ruleType: stringValue(value.rule_type ?? value.ruleType, ''),
    skillTrackId: stringValue(value.skill_track_id ?? value.skillTrackId, ''),
    successRequirements: stringArray(value.success_requirements ?? value.successRequirements),
    title: stringValue(value.title, ''),
  }
}

function mapPortfolioStressAxis(value: UnknownRecord): RoadmapPortfolioStressAxis {
  return {
    axis: stringValue(value.axis, ''),
    budget: numberValue(value.budget, 0),
    load: numberValue(value.load, 0),
    status: stringValue(value.status, 'green'),
  }
}

function mapPortfolioTimeLedger(value: unknown): RoadmapPortfolioTimeLedger {
  const source = recordValue(value)

  return {
    estimatedMinutesPerWeek: numberValue(source.estimated_minutes_per_week ?? source.estimatedMinutesPerWeek, 0),
    maxSessionsPerWeek: numberValue(source.max_sessions_per_week ?? source.maxSessionsPerWeek, 0),
    notes: stringArray(source.notes),
    remainingMinutesPerWeek: numberValue(source.remaining_minutes_per_week ?? source.remainingMinutesPerWeek, 0),
  }
}

function mapPortfolioExplanation(value: unknown): RoadmapPortfolioExplanation {
  const source = recordValue(value)

  return {
    fallback: stringValue(source.fallback, ''),
    summary: stringValue(source.summary, ''),
    watchOutFor: stringArray(source.watch_out_for ?? source.watchOutFor),
    whyThisMix: stringArray(source.why_this_mix ?? source.whyThisMix),
  }
}

function mapPortfolioGoalChoices(value: unknown): RoadmapPortfolioGoalChoices {
  const source = recordValue(value)

  return {
    accessories: stringArray(source.accessories),
    blocked: stringArray(source.blocked),
    development: stringArray(source.development),
    future: stringArray(source.future),
    technicalPractice: stringArray(source.technical_practice ?? source.technicalPractice),
  }
}

function mapPortfolioFoundationLayer(value: unknown): RoadmapPortfolioFoundationLayer {
  const source = recordValue(value)

  return {
    focusAreas: stringArray(source.focus_areas ?? source.focusAreas),
    summary: stringValue(source.summary, ''),
    tracks: recordArray(source.tracks).map(mapPortfolioTrack),
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

function mapRecordArrayMap(value: unknown): Readonly<Record<string, readonly Record<string, unknown>[]>> {
  const source = recordValue(value)
  const mapped: Record<string, readonly Record<string, unknown>[]> = {}

  Object.entries(source).forEach(([key, nestedValue]) => {
    mapped[key] = recordArray(nestedValue)
  })

  return mapped
}

function numberRecord(value: unknown): Readonly<Record<string, number>> {
  const source = recordValue(value)
  const mapped: Record<string, number> = {}

  Object.entries(source).forEach(([key, nestedValue]) => {
    const parsed = nullableNumber(nestedValue)

    if (parsed !== null) {
      mapped[key] = parsed
    }
  })

  return mapped
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

function booleanValue(value: unknown, fallback: boolean): boolean {
  return typeof value === 'boolean' ? value : fallback
}
