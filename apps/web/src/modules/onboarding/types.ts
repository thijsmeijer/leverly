export type OnboardingStepId = 'goals' | 'equipment' | 'level' | 'mobility' | 'availability' | 'readiness' | 'starter'

export type OnboardingFieldErrors = Partial<Record<keyof OnboardingForm | string, string>>

export interface OnboardingLevelTestsForm {
  archHoldSeconds: string
  deadHangSeconds: string
  dipMaxReps: string
  dipProgression: string
  dipSupportHoldSeconds: string
  hollowHoldSeconds: string
  lSitHoldSeconds: string
  pullUpMaxReps: string
  pullUpProgression: string
  pullUpAssistance: string
  pullUpFormQuality: string
  pushUpFormQuality: string
  pushUpMaxReps: string
  pushUpProgression: string
  rowMaxReps: string
  rowProgression: string
  squatMaxReps: string
  squatProgression: string
  supportHoldSeconds: string
  wallHandstandSeconds: string
}

export interface OnboardingSkillStatusForm {
  bestHoldSeconds: string
  maxStrictReps: string
  notes: string
  status: string
}

export interface OnboardingForm {
  availableEquipment: string[]
  baseFocusAreas: string[]
  currentLevelTests: OnboardingLevelTestsForm
  mobilityChecks: Record<string, string>
  painAreas: string[]
  painLevel: string
  painNotes: string
  preferredSessionMinutes: string
  preferredTrainingDays: string[]
  preferredTrainingTime: string
  primaryTargetSkill: string
  primaryGoal: string
  readinessRating: string
  secondaryGoals: string[]
  secondaryTargetSkills: string[]
  skillStatuses: Record<string, OnboardingSkillStatusForm>
  sleepQuality: string
  sorenessLevel: string
  starterPlanKey: string
  targetSkills: string[]
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
