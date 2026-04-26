import type { RoadmapSuggestions, RoadmapTrack } from '@/modules/roadmap'

export type OnboardingStepId =
  | 'context'
  | 'equipment'
  | 'mobility'
  | 'level'
  | 'availability'
  | 'roadmap'
  | 'readiness'
  | 'starter'

export type OnboardingFieldErrors = Partial<Record<keyof OnboardingForm | string, string>>

export interface OnboardingLevelTestsForm {
  dipMaxReps: string
  hollowHoldSeconds: string
  pullUpMaxReps: string
  pushUpMaxReps: string
  squatBarbellLoadValue: string
  squatBarbellReps: string
}

export interface OnboardingSkillStatusForm {
  bestHoldSeconds: string
  maxReps: string
  notes: string
  status: string
}

export interface OnboardingForm {
  ageYears: string
  availableEquipment: string[]
  baseFocusAreas: string[]
  bodyweightUnit: string
  currentBodyweightValue: string
  currentLevelTests: OnboardingLevelTestsForm
  experienceLevel: string
  heightUnit: string
  heightValue: string
  longTermTargetSkills: string[]
  mobilityChecks: Record<string, string>
  painAreas: string[]
  painLevel: string
  painNotes: string
  preferredSessionMinutes: string
  preferredTrainingDays: string[]
  priorSportBackground: string[]
  primaryTargetSkill: string
  primaryGoal: string
  readinessRating: string
  roadmapSuggestions: OnboardingRoadmapSuggestions
  secondaryGoals: string[]
  secondaryTargetSkills: string[]
  skillStatuses: Record<string, OnboardingSkillStatusForm>
  sleepQuality: string
  sorenessLevel: string
  starterPlanKey: string
  targetSkills: string[]
  trainingAgeMonths: string
  trainingLocations: string[]
  weightedBaselines: {
    experience: string
    movements: Array<{
      externalLoadValue: string
      movement: string
      reps: string
      rir: string
    }>
    unit: string
  }
  weeklySessionGoal: string
}

export type OnboardingRoadmapTrack = RoadmapTrack

export type OnboardingRoadmapSuggestions = RoadmapSuggestions

export interface OnboardingState {
  readonly form: OnboardingForm
  readonly isComplete: boolean
  readonly missingSections: string[]
  readonly onboardingId: string | null
}

export type ChoiceOption = {
  readonly description?: string
  readonly label: string
  readonly meta?: string
  readonly value: string
}
