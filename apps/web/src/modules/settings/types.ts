export type UnitSystem = 'imperial' | 'metric'
export type BodyweightUnit = 'kg' | 'lb'

export type ChoiceOption = {
  readonly description?: string
  readonly label: string
  readonly value: string
}

export type ProfileFieldErrors = Partial<Record<keyof ProfileSettingsForm, string>>

export interface MovementLimitationForm {
  area: string
  notes: string
  severity: string
  status: string
}

export interface ProfileBaselineTestsForm {
  dipMaxReps: string
  hollowHoldSeconds: string
  pullUpMaxReps: string
  pushUpMaxReps: string
  squatBarbellLoadValue: string
  squatBarbellReps: string
}

export interface ProfileSettingsForm {
  ageYears: string
  availableEquipment: string[]
  baseFocusAreas: string[]
  baselineTests: ProfileBaselineTestsForm
  bodyweightUnit: BodyweightUnit
  currentBodyweightValue: string
  deloadPreference: string
  displayName: string
  effortTrackingPreference: string
  experienceLevel: string
  injuryNotes: string
  intensityPreference: string
  heightUnit: string
  heightValue: string
  longTermTargetSkills: string[]
  movementLimitation: MovementLimitationForm
  mobilityChecks: Record<string, string>
  preferredSessionMinutes: string
  preferredTrainingDays: string[]
  priorSportBackground: string[]
  primaryGoal: string
  primaryTargetSkill: string
  progressionPace: string
  secondaryGoals: string[]
  secondaryTargetSkills: string[]
  sessionStructurePreferences: string[]
  skillStatuses: Record<string, { bestHoldSeconds: string; maxStrictReps: string; notes: string; status: string }>
  targetSkillsText: string
  timezone: string
  trainingAgeMonths: string
  trainingLocations: string[]
  unitSystem: UnitSystem
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

export interface ProfileSettingsState {
  readonly form: ProfileSettingsForm
  readonly profileId: string | null
}
