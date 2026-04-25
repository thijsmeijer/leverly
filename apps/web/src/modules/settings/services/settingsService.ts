import { ApiRequestError, leverlyApiRequest, type ApiResponseBody } from '@/shared/api/leverlyApi/runtimeClient'

import type { ProfileFieldErrors, ProfileSettingsForm, ProfileSettingsState } from '../types'

type ServerValidationBody = {
  readonly errors?: Record<string, string[]>
  readonly message?: string
}

type AthleteProfileResponse = NonNullable<ApiResponseBody<'/me/profile', 'get'>>
type AthleteProfile = AthleteProfileResponse['data']

type ProfileUpdateBody = {
  readonly available_equipment: string[]
  readonly bodyweight_unit: string
  readonly current_bodyweight_value: number | null
  readonly deload_preference: string
  readonly display_name: string
  readonly effort_tracking_preference: string
  readonly experience_level: string
  readonly injury_notes: string | null
  readonly intensity_preference: string
  readonly movement_limitations: Array<{
    readonly area: string
    readonly notes: string | null
    readonly severity: string
    readonly status: string
  }>
  readonly preferred_session_minutes: number | null
  readonly preferred_training_days: string[]
  readonly preferred_training_time: string
  readonly primary_goal: string | null
  readonly progression_pace: string
  readonly secondary_goals: string[]
  readonly session_structure_preferences: string[]
  readonly target_skills: string[]
  readonly timezone: string
  readonly training_age_months: number | null
  readonly training_locations: string[]
  readonly unit_system: string
  readonly weekly_session_goal: number | null
}

export class ProfileSettingsValidationError extends Error {
  readonly errors: ProfileFieldErrors

  constructor(errors: ProfileFieldErrors) {
    super('Profile settings validation failed.')
    this.name = 'ProfileSettingsValidationError'
    this.errors = errors
  }
}

export function defaultProfileSettingsForm(): ProfileSettingsForm {
  return {
    availableEquipment: ['floor', 'wall'],
    bodyweightUnit: 'kg',
    currentBodyweightValue: '',
    deloadPreference: 'auto',
    displayName: '',
    effortTrackingPreference: 'simple',
    experienceLevel: 'new',
    injuryNotes: '',
    intensityPreference: 'auto',
    movementLimitation: {
      area: 'wrist',
      notes: '',
      severity: 'mild',
      status: 'past',
    },
    preferredSessionMinutes: '45',
    preferredTrainingDays: [],
    preferredTrainingTime: 'flexible',
    primaryGoal: 'strength',
    progressionPace: 'balanced',
    secondaryGoals: [],
    sessionStructurePreferences: [],
    targetSkillsText: '',
    timezone: browserTimezone(),
    trainingAgeMonths: '',
    trainingLocations: [],
    unitSystem: 'metric',
    weeklySessionGoal: '3',
  }
}

export async function fetchProfileSettings(): Promise<ProfileSettingsState> {
  const response = await leverlyApiRequest('/me/profile', 'get', {
    errorMode: 'throw',
    notFoundMode: 'silent',
  })

  if (!response) {
    return {
      form: defaultProfileSettingsForm(),
      profileId: null,
    }
  }

  return mapProfileResponse(response)
}

export async function saveProfileSettings(form: ProfileSettingsForm): Promise<ProfileSettingsState> {
  try {
    const response = await leverlyApiRequest('/me/profile', 'patch', {
      body: mapFormToUpdateBody(form),
      errorMode: 'throw',
      notFoundMode: 'throw',
    })

    if (!response) {
      throw new Error('Profile settings response was empty.')
    }

    return mapProfileResponse(response)
  } catch (error) {
    if (error instanceof ApiRequestError && error.status === 422) {
      throw new ProfileSettingsValidationError(mapServerValidationErrors(error.body))
    }

    throw error
  }
}

export function validateProfileSettingsForm(form: ProfileSettingsForm): ProfileFieldErrors {
  const errors: ProfileFieldErrors = {}

  if (!form.displayName.trim()) {
    errors.displayName = 'Add the name you want shown in Leverly.'
  }

  if (!form.timezone.trim()) {
    errors.timezone = 'Choose the timezone your training schedule should use.'
  }

  addNumberError(errors, 'trainingAgeMonths', form.trainingAgeMonths, 0, 1200, 'Training age must be 0 to 1200 months.')
  addNumberError(
    errors,
    'currentBodyweightValue',
    form.currentBodyweightValue,
    20,
    400,
    'Bodyweight must be between 20 and 400.',
  )
  addNumberError(
    errors,
    'preferredSessionMinutes',
    form.preferredSessionMinutes,
    10,
    240,
    'Session length must be 10 to 240 minutes.',
  )
  addNumberError(
    errors,
    'weeklySessionGoal',
    form.weeklySessionGoal,
    1,
    14,
    'Weekly sessions must be between 1 and 14.',
  )

  return errors
}

function mapProfileResponse(response: AthleteProfileResponse): ProfileSettingsState {
  return {
    form: mapProfileToForm(response.data),
    profileId: response.data.id,
  }
}

function mapProfileToForm(profile: AthleteProfile): ProfileSettingsForm {
  const limitation = profile.movement_limitations[0]

  return {
    availableEquipment: [...profile.available_equipment],
    bodyweightUnit: profile.bodyweight_unit === 'lb' ? 'lb' : 'kg',
    currentBodyweightValue: profile.current_bodyweight_value === null ? '' : String(profile.current_bodyweight_value),
    deloadPreference: profile.deload_preference,
    displayName: profile.display_name,
    effortTrackingPreference: profile.effort_tracking_preference,
    experienceLevel: profile.experience_level,
    injuryNotes: profile.injury_notes ?? '',
    intensityPreference: profile.intensity_preference,
    movementLimitation: {
      area: limitation?.area ?? 'wrist',
      notes: limitation?.notes ?? '',
      severity: limitation?.severity ?? 'mild',
      status: limitation?.status ?? 'past',
    },
    preferredSessionMinutes:
      profile.preferred_session_minutes === null ? '' : String(profile.preferred_session_minutes),
    preferredTrainingDays: [...profile.preferred_training_days],
    preferredTrainingTime: profile.preferred_training_time,
    primaryGoal: profile.primary_goal ?? 'strength',
    progressionPace: profile.progression_pace,
    secondaryGoals: [...profile.secondary_goals],
    sessionStructurePreferences: [...profile.session_structure_preferences],
    targetSkillsText: profile.target_skills.join('\n'),
    timezone: profile.timezone,
    trainingAgeMonths: profile.training_age_months === null ? '' : String(profile.training_age_months),
    trainingLocations: [...profile.training_locations],
    unitSystem: profile.unit_system === 'imperial' ? 'imperial' : 'metric',
    weeklySessionGoal: profile.weekly_session_goal === null ? '' : String(profile.weekly_session_goal),
  }
}

function mapFormToUpdateBody(form: ProfileSettingsForm): ProfileUpdateBody {
  const limitationNotes = form.movementLimitation.notes.trim()
  const movementLimitations =
    form.injuryNotes.trim() || limitationNotes
      ? [
          {
            area: form.movementLimitation.area,
            notes: limitationNotes || null,
            severity: form.movementLimitation.severity,
            status: form.movementLimitation.status,
          },
        ]
      : []

  return {
    available_equipment: form.availableEquipment,
    bodyweight_unit: form.bodyweightUnit,
    current_bodyweight_value: nullableNumber(form.currentBodyweightValue),
    deload_preference: form.deloadPreference,
    display_name: form.displayName.trim(),
    effort_tracking_preference: form.effortTrackingPreference,
    experience_level: form.experienceLevel,
    injury_notes: form.injuryNotes.trim() || null,
    intensity_preference: form.intensityPreference,
    movement_limitations: movementLimitations,
    preferred_session_minutes: nullableNumber(form.preferredSessionMinutes),
    preferred_training_days: form.preferredTrainingDays,
    preferred_training_time: form.preferredTrainingTime,
    primary_goal: form.primaryGoal || null,
    progression_pace: form.progressionPace,
    secondary_goals: form.secondaryGoals,
    session_structure_preferences: form.sessionStructurePreferences,
    target_skills: splitTargetSkills(form.targetSkillsText),
    timezone: form.timezone.trim(),
    training_age_months: nullableNumber(form.trainingAgeMonths),
    training_locations: form.trainingLocations,
    unit_system: form.unitSystem,
    weekly_session_goal: nullableNumber(form.weeklySessionGoal),
  }
}

function addNumberError(
  errors: ProfileFieldErrors,
  field: keyof ProfileSettingsForm,
  value: string | number,
  min: number,
  max: number,
  message: string,
): void {
  const text = String(value).trim()

  if (text === '') {
    return
  }

  const numericValue = Number(text)

  if (!Number.isFinite(numericValue) || numericValue < min || numericValue > max) {
    errors[field] = message
  }
}

function nullableNumber(value: string | number): number | null {
  const trimmed = String(value).trim()

  return trimmed === '' ? null : Number(trimmed)
}

function splitTargetSkills(value: string): string[] {
  return value
    .split(/[\n,]/)
    .map((item) => item.trim())
    .filter(Boolean)
}

function mapServerValidationErrors(body: unknown): ProfileFieldErrors {
  if (!isServerValidationBody(body) || !body.errors) {
    return {}
  }

  return Object.entries(body.errors).reduce<ProfileFieldErrors>((errors, [field, messages]) => {
    const key = mapServerField(field)

    if (key && messages[0]) {
      errors[key] = messages[0]
    }

    return errors
  }, {})
}

function mapServerField(field: string): keyof ProfileSettingsForm | null {
  const base = field.split('.')[0]
  const map: Record<string, keyof ProfileSettingsForm> = {
    available_equipment: 'availableEquipment',
    bodyweight_unit: 'bodyweightUnit',
    current_bodyweight_value: 'currentBodyweightValue',
    deload_preference: 'deloadPreference',
    display_name: 'displayName',
    effort_tracking_preference: 'effortTrackingPreference',
    experience_level: 'experienceLevel',
    injury_notes: 'injuryNotes',
    intensity_preference: 'intensityPreference',
    movement_limitations: 'movementLimitation',
    preferred_session_minutes: 'preferredSessionMinutes',
    preferred_training_days: 'preferredTrainingDays',
    preferred_training_time: 'preferredTrainingTime',
    primary_goal: 'primaryGoal',
    progression_pace: 'progressionPace',
    secondary_goals: 'secondaryGoals',
    session_structure_preferences: 'sessionStructurePreferences',
    target_skills: 'targetSkillsText',
    timezone: 'timezone',
    training_age_months: 'trainingAgeMonths',
    training_locations: 'trainingLocations',
    unit_system: 'unitSystem',
    weekly_session_goal: 'weeklySessionGoal',
  }

  return map[base] ?? null
}

function isServerValidationBody(value: unknown): value is ServerValidationBody {
  return typeof value === 'object' && value !== null
}

function browserTimezone(): string {
  return Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC'
}
