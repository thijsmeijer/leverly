export type OnboardingStepId = 'goals' | 'equipment' | 'level' | 'availability' | 'readiness' | 'starter'

export type OnboardingFieldErrors = Partial<Record<keyof OnboardingForm | string, string>>

export interface OnboardingLevelTestsForm {
  hollowHoldSeconds: string
  pullUpMaxReps: string
  pullUpProgression: string
  pushUpMaxReps: string
  squatMaxReps: string
  squatProgression: string
}

export interface OnboardingSkillStatusForm {
  bestHoldSeconds: string
  maxStrictReps: string
  notes: string
  status: string
}

export interface OnboardingForm {
  availableEquipment: string[]
  currentLevelTests: OnboardingLevelTestsForm
  painAreas: string[]
  painLevel: string
  painNotes: string
  preferredSessionMinutes: string
  preferredTrainingDays: string[]
  preferredTrainingTime: string
  primaryGoal: string
  readinessRating: string
  secondaryGoals: string[]
  skillStatuses: Record<string, OnboardingSkillStatusForm>
  sleepQuality: string
  sorenessLevel: string
  starterPlanKey: string
  targetSkills: string[]
  trainingLocations: string[]
  weeklySessionGoal: string
}

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
