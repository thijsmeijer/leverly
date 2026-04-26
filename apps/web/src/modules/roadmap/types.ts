export interface RoadmapNode {
  readonly id: string
  readonly label: string
}

export interface RoadmapEtaRange {
  readonly label: string
  readonly maxWeeks: number | null
  readonly minWeeks: number | null
}

export interface RoadmapConfidence {
  readonly level: string
  readonly reasons: readonly string[]
  readonly score: number | null
}

export interface RoadmapBlocker {
  readonly key: string
  readonly label: string
  readonly message: string
  readonly severity: string
}

export interface RoadmapUnlockCondition {
  readonly label: string
  readonly skill: string
  readonly status: string
}

export interface RoadmapTrack {
  readonly baseFocusAreas: readonly string[]
  readonly compatibleSecondarySkills: readonly string[]
  readonly label: string
  readonly nextGate: string
  readonly reason: string
  readonly skill: string
}

export interface RoadmapGoal {
  readonly blockers: readonly RoadmapBlocker[]
  readonly compatibilityTags: readonly string[]
  readonly confidence: RoadmapConfidence
  readonly currentProgressionNode: RoadmapNode
  readonly etaRange: RoadmapEtaRange
  readonly explanation: string
  readonly label: string
  readonly lane: string
  readonly nextMilestone: RoadmapNode
  readonly nextNode: RoadmapNode
  readonly skill: string
  readonly unlockConditions: readonly RoadmapUnlockCondition[]
}

export interface RoadmapFoundationLane {
  readonly currentProgressionNode: RoadmapNode
  readonly focusAreas: readonly string[]
  readonly label: string
  readonly nextMilestone: RoadmapNode
  readonly nextNode: RoadmapNode
  readonly slug: string
}

export interface RoadmapExplanation {
  readonly fallback: string
  readonly summary: string
  readonly watchOutFor: readonly string[]
  readonly whyThisGoal: readonly string[]
}

export interface RoadmapIntermediate {
  readonly compatibilityCosts: readonly Record<string, unknown>[]
  readonly domainScores: Record<string, unknown>
  readonly domainUncertainty: Record<string, unknown>
  readonly etaModifiers: readonly Record<string, unknown>[]
  readonly hardGateResults: readonly Record<string, unknown>[]
  readonly progressionGraphPlacement: Record<string, unknown>
  readonly readinessScores: readonly Record<string, unknown>[]
}

export interface RoadmapSuggestions {
  readonly baseFocusAreas: readonly string[]
  readonly blockers: readonly RoadmapBlocker[]
  readonly bodyContext: {
    readonly notes: readonly string[]
  }
  readonly bridgeTracks: readonly RoadmapTrack[]
  readonly compatibilityTags: readonly string[]
  readonly compatibleSecondaryGoal: RoadmapGoal | null
  readonly confidence: RoadmapConfidence
  readonly currentProgressionNode: RoadmapNode
  readonly deferredGoals: readonly RoadmapGoal[]
  readonly deferredTracks: readonly RoadmapTrack[]
  readonly etaRange: RoadmapEtaRange
  readonly explanation: RoadmapExplanation
  readonly foundationLane: RoadmapFoundationLane
  readonly intermediate: RoadmapIntermediate
  readonly level: string
  readonly longTermTracks: readonly RoadmapTrack[]
  readonly nextMilestone: RoadmapNode
  readonly nextNode: RoadmapNode
  readonly primaryGoal: RoadmapGoal | null
  readonly summary: string
  readonly unlockConditions: readonly RoadmapUnlockCondition[]
  readonly unlockedTracks: readonly RoadmapTrack[]
  readonly version: string
}
