import type { GoalModuleForm, RoadmapSuggestions, RoadmapTrack } from '@/modules/roadmap'

export type OnboardingStepId =
  | 'context'
  | 'equipment'
  | 'mobility'
  | 'level'
  | 'goal'
  | 'modules'
  | 'availability'
  | 'review'

export type OnboardingFieldErrors = Partial<Record<keyof OnboardingForm | string, string>>

export interface OnboardingLevelTestsForm {
  dipMaxReps: string
  dipFallbackReps: string
  dipFallbackSeconds: string
  dipFallbackVariant: string
  hollowHoldSeconds: string
  lowerBodyLoadUnit: string
  lowerBodyLoadValue: string
  lowerBodyReps: string
  lowerBodyVariant: string
  passiveHangSeconds: string
  pullUpMaxReps: string
  pullUpFallbackReps: string
  pullUpFallbackSeconds: string
  pullUpFallbackVariant: string
  pushUpMaxReps: string
  rowMaxReps: string
  rowVariant: string
  squatBarbellLoadValue: string
  squatBarbellReps: string
  topSupportHoldSeconds: string
}

export interface OnboardingSkillStatusForm {
  bestHoldSeconds: string
  maxReps: string
  notes: string
  status: string
}

export interface PainFlagForm {
  notes: string
  severity: string
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
  goalModules: Record<string, GoalModuleForm>
  heightUnit: string
  heightValue: string
  longTermTargetSkills: string[]
  mobilityChecks: Record<string, string>
  painAreas: string[]
  painFlags: Record<string, PainFlagForm>
  painLevel: string
  painNotes: string
  preferredSessionMinutes: string
  preferredTrainingDays: string[]
  priorSportBackground: string[]
  primaryTargetSkill: string
  primaryGoal: string
  readinessRating: string
  requiredGoalModules: string[]
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
  weightTrend: string
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
