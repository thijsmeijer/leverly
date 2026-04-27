import type { RoadmapSuggestions } from '@/modules/roadmap'

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

export interface PainFlagForm {
  notes: string
  severity: string
  status: string
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
  painFlags: Record<string, PainFlagForm>
  preferredSessionMinutes: string
  preferredTrainingDays: string[]
  priorSportBackground: string[]
  primaryGoal: string
  primaryTargetSkill: string
  progressionPace: string
  roadmapSuggestions: RoadmapSuggestions
  secondaryGoals: string[]
  secondaryTargetSkills: string[]
  sessionStructurePreferences: string[]
  skillStatuses: Record<string, { bestHoldSeconds: string; maxReps: string; notes: string; status: string }>
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
  weightTrend: string
  weeklySessionGoal: string
}

export interface ProfileSettingsState {
  readonly form: ProfileSettingsForm
  readonly profileId: string | null
}
