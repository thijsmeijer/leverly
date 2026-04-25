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

export interface ProfileSettingsForm {
  availableEquipment: string[]
  bodyweightUnit: BodyweightUnit
  currentBodyweightValue: string
  deloadPreference: string
  displayName: string
  effortTrackingPreference: string
  experienceLevel: string
  injuryNotes: string
  intensityPreference: string
  movementLimitation: MovementLimitationForm
  preferredSessionMinutes: string
  preferredTrainingDays: string[]
  preferredTrainingTime: string
  primaryGoal: string
  progressionPace: string
  secondaryGoals: string[]
  sessionStructurePreferences: string[]
  targetSkillsText: string
  timezone: string
  trainingAgeMonths: string
  trainingLocations: string[]
  unitSystem: UnitSystem
  weeklySessionGoal: string
}

export interface ProfileSettingsState {
  readonly form: ProfileSettingsForm
  readonly profileId: string | null
}
