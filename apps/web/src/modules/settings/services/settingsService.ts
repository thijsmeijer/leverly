import { ApiRequestError, leverlyApiRequest, type ApiResponseBody } from '@/shared/api/leverlyApi/runtimeClient'
import {
  defaultGoalModules,
  emptyRoadmapSuggestions,
  isGoalModuleTested,
  mapGoalModulesToForm,
  mapRoadmapSuggestions,
  requiredGoalModulesForGoal,
  serializeGoalModules,
} from '@/modules/roadmap'

import { compatibleSecondaryGoals, equipmentOptions, mobilityCheckOptions } from '../data/profileOptions'
import type { ProfileFieldErrors, ProfileSettingsForm, ProfileSettingsState } from '../types'

const painRegions = ['wrist', 'elbow', 'shoulder', 'low_back', 'knee', 'ankle'] as const

type ServerValidationBody = {
  readonly errors?: Record<string, string[]>
  readonly message?: string
}

type AthleteProfileResponse = NonNullable<ApiResponseBody<'/me/profile', 'get'>>
type AthleteProfile = AthleteProfileResponse['data']

type ProfileUpdateBody = {
  readonly age_years: number | null
  readonly available_equipment: string[]
  readonly base_focus_areas: string[]
  readonly baseline_tests: Record<string, unknown>
  readonly bodyweight_unit: string
  readonly current_bodyweight_value: number | null
  readonly deload_preference: string
  readonly display_name: string
  readonly effort_tracking_preference: string
  readonly experience_level: string
  readonly goal_modules: ReturnType<typeof serializeGoalModules>
  readonly injury_notes: string | null
  readonly intensity_preference: string
  readonly height_unit: string
  readonly height_value: number | null
  readonly long_term_target_skills: string[]
  readonly movement_limitations: Array<{
    readonly area: string
    readonly notes: string | null
    readonly severity: string
    readonly status: string
  }>
  readonly mobility_checks: Record<string, string>
  readonly pain_flags: Record<string, { notes: string | null; severity: string; status: string }>
  readonly preferred_session_minutes: number | null
  readonly preferred_training_days: string[]
  readonly prior_sport_background: string[]
  readonly primary_goal: string | null
  readonly primary_target_skill: string | null
  readonly progression_pace: string
  readonly secondary_goals: string[]
  readonly secondary_target_skills: string[]
  readonly session_structure_preferences: string[]
  readonly skill_statuses: Record<string, unknown>
  readonly target_skills: string[]
  readonly timezone: string
  readonly training_age_months: number | null
  readonly training_locations: string[]
  readonly unit_system: string
  readonly weighted_baselines: {
    readonly experience: string
    readonly movements: Array<{
      readonly external_load_value: number | null
      readonly movement: string
      readonly reps: number | null
      readonly rir: number | null
    }>
    readonly unit: string
  }
  readonly weight_trend: string
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
    availableEquipment: [],
    ageYears: '',
    baseFocusAreas: [],
    baselineTests: {
      dipMaxReps: '',
      dipFallbackReps: '',
      dipFallbackSeconds: '',
      dipFallbackVariant: 'none',
      hollowHoldSeconds: '',
      lowerBodyLoadUnit: 'kg',
      lowerBodyLoadValue: '',
      lowerBodyReps: '',
      lowerBodyVariant: 'bodyweight_squat',
      passiveHangSeconds: '',
      pullUpMaxReps: '',
      pullUpFallbackReps: '',
      pullUpFallbackSeconds: '',
      pullUpFallbackVariant: 'none',
      pushUpMaxReps: '',
      rowMaxReps: '',
      rowVariant: 'bodyweight_row',
      squatBarbellLoadValue: '',
      squatBarbellReps: '',
      topSupportHoldSeconds: '',
    },
    bodyweightUnit: 'kg',
    currentBodyweightValue: '',
    deloadPreference: 'auto',
    displayName: '',
    effortTrackingPreference: 'simple',
    experienceLevel: 'new',
    goalModules: defaultGoalModules(),
    injuryNotes: '',
    intensityPreference: 'auto',
    heightUnit: 'cm',
    heightValue: '',
    longTermTargetSkills: [],
    movementLimitation: {
      area: 'wrist',
      notes: '',
      severity: 'mild',
      status: 'past',
    },
    mobilityChecks: Object.fromEntries(mobilityCheckOptions.map((option) => [option.value, 'not_tested'])),
    painFlags: emptyPainFlags(),
    preferredSessionMinutes: '45',
    preferredTrainingDays: [],
    priorSportBackground: [],
    primaryGoal: 'strength',
    primaryTargetSkill: '',
    progressionPace: 'balanced',
    requiredGoalModules: [],
    roadmapSuggestions: emptyRoadmapSuggestions(),
    secondaryGoals: [],
    secondaryTargetSkills: [],
    sessionStructurePreferences: [],
    skillStatuses: {},
    targetSkillsText: '',
    timezone: browserTimezone(),
    trainingAgeMonths: '',
    trainingLocations: [],
    unitSystem: 'metric',
    weightedBaselines: {
      experience: 'none',
      movements: [],
      unit: 'kg',
    },
    weightTrend: 'unknown',
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

export async function saveAvailableEquipmentSettings(availableEquipment: string[]): Promise<ProfileSettingsState> {
  try {
    const response = await leverlyApiRequest('/me/profile', 'patch', {
      body: {
        available_equipment: availableEquipment.filter(isSupportedEquipment),
      },
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
  addNumberError(errors, 'ageYears', form.ageYears, 13, 90, 'Age must be between 13 and 90.')
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
    'heightValue',
    form.heightValue,
    form.heightUnit === 'in' ? 36 : 90,
    form.heightUnit === 'in' ? 100 : 250,
    form.heightUnit === 'in' ? 'Height must be 36 to 100 inches.' : 'Height must be 90 to 250 cm.',
  )
  addNumberError(
    errors,
    'preferredSessionMinutes',
    form.preferredSessionMinutes,
    10,
    240,
    'Maximum session length must be 10 to 240 minutes.',
  )
  addNumberError(
    errors,
    'weeklySessionGoal',
    form.weeklySessionGoal,
    1,
    14,
    'Max sessions per week must be between 1 and 14.',
  )

  const compatibleGoals = compatibleSecondaryGoals[form.primaryGoal] ?? []
  const hasIncompatibleGoal = form.secondaryGoals.some(
    (goal) => goal === form.primaryGoal || !compatibleGoals.includes(goal),
  )

  if (form.secondaryGoals.length > 2 || hasIncompatibleGoal) {
    errors.secondaryGoals = 'Choose up to two secondary goals that fit your primary goal.'
  }

  const targetSkills = splitTargetSkills(form.targetSkillsText)

  if (form.primaryTargetSkill && targetSkills.length > 0 && targetSkills[0] !== form.primaryTargetSkill) {
    errors.primaryTargetSkill = 'Primary target must match the active target skill.'
  }

  if (targetSkills.length > 1) {
    errors.targetSkillsText =
      'Keep exactly one active target; put other skills in secondary interests or long-term targets.'
  }

  if (
    form.secondaryTargetSkills.some(
      (skill) => skill === form.primaryTargetSkill || form.longTermTargetSkills.includes(skill),
    )
  ) {
    errors.secondaryTargetSkills = 'Secondary interests must differ from the primary and long-term targets.'
  }

  if (form.longTermTargetSkills.some((skill) => targetSkills.includes(skill))) {
    errors.longTermTargetSkills = 'Long-term targets should be different from active targets.'
  }

  for (const module of activeRequiredGoalModules(form)) {
    if (!isGoalModuleTested(form.goalModules[module])) {
      errors.goalModules = 'Add the tested progression for the selected primary goal.'
    }
  }

  if (form.baseFocusAreas.length > 4) {
    errors.baseFocusAreas = 'Choose up to four base focus areas.'
  }

  if (form.sessionStructurePreferences.length > 3) {
    errors.sessionStructurePreferences = 'Choose up to three session structure preferences.'
  }

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
  const requiredGoalModules = [
    ...(profile.required_goal_modules ?? requiredGoalModulesForGoal(profile.primary_target_skill ?? '')),
  ]

  return {
    availableEquipment: profile.available_equipment.filter(isSupportedEquipment),
    ageYears: profile.age_years === null || profile.age_years === undefined ? '' : String(profile.age_years),
    baseFocusAreas: [...profile.base_focus_areas],
    baselineTests: {
      dipFallbackReps:
        profile.baseline_tests.dips?.fallback_reps === null || profile.baseline_tests.dips?.fallback_reps === undefined
          ? ''
          : String(profile.baseline_tests.dips.fallback_reps),
      dipFallbackSeconds:
        profile.baseline_tests.dips?.fallback_seconds === null ||
        profile.baseline_tests.dips?.fallback_seconds === undefined
          ? ''
          : String(profile.baseline_tests.dips.fallback_seconds),
      dipFallbackVariant: profile.baseline_tests.dips?.fallback_variant ?? 'none',
      dipMaxReps:
        profile.baseline_tests.dips?.max_strict_reps === null ||
        profile.baseline_tests.dips?.max_strict_reps === undefined
          ? ''
          : String(profile.baseline_tests.dips.max_strict_reps),
      hollowHoldSeconds:
        profile.baseline_tests.hollow_hold_seconds === null || profile.baseline_tests.hollow_hold_seconds === undefined
          ? ''
          : String(profile.baseline_tests.hollow_hold_seconds),
      lowerBodyLoadUnit: profile.baseline_tests.lower_body?.load_unit ?? 'kg',
      lowerBodyLoadValue:
        profile.baseline_tests.lower_body?.load_value === null ||
        profile.baseline_tests.lower_body?.load_value === undefined
          ? ''
          : String(profile.baseline_tests.lower_body.load_value),
      lowerBodyReps:
        profile.baseline_tests.lower_body?.reps === null || profile.baseline_tests.lower_body?.reps === undefined
          ? ''
          : String(profile.baseline_tests.lower_body.reps),
      lowerBodyVariant: profile.baseline_tests.lower_body?.variant ?? 'bodyweight_squat',
      passiveHangSeconds:
        profile.baseline_tests.passive_hang_seconds === null ||
        profile.baseline_tests.passive_hang_seconds === undefined
          ? ''
          : String(profile.baseline_tests.passive_hang_seconds),
      pullUpFallbackReps:
        profile.baseline_tests.pull_ups?.fallback_reps === null ||
        profile.baseline_tests.pull_ups?.fallback_reps === undefined
          ? ''
          : String(profile.baseline_tests.pull_ups.fallback_reps),
      pullUpFallbackSeconds:
        profile.baseline_tests.pull_ups?.fallback_seconds === null ||
        profile.baseline_tests.pull_ups?.fallback_seconds === undefined
          ? ''
          : String(profile.baseline_tests.pull_ups.fallback_seconds),
      pullUpFallbackVariant: profile.baseline_tests.pull_ups?.fallback_variant ?? 'none',
      pullUpMaxReps:
        profile.baseline_tests.pull_ups?.max_strict_reps === null ||
        profile.baseline_tests.pull_ups?.max_strict_reps === undefined
          ? ''
          : String(profile.baseline_tests.pull_ups.max_strict_reps),
      pushUpMaxReps:
        profile.baseline_tests.push_ups?.max_strict_reps === null ||
        profile.baseline_tests.push_ups?.max_strict_reps === undefined
          ? ''
          : String(profile.baseline_tests.push_ups.max_strict_reps),
      rowMaxReps:
        profile.baseline_tests.rows?.max_reps === null || profile.baseline_tests.rows?.max_reps === undefined
          ? ''
          : String(profile.baseline_tests.rows.max_reps),
      rowVariant: profile.baseline_tests.rows?.variant ?? 'bodyweight_row',
      squatBarbellLoadValue:
        profile.baseline_tests.squat?.barbell_load_value === null ||
        profile.baseline_tests.squat?.barbell_load_value === undefined
          ? ''
          : String(profile.baseline_tests.squat.barbell_load_value),
      squatBarbellReps:
        profile.baseline_tests.squat?.barbell_reps === null || profile.baseline_tests.squat?.barbell_reps === undefined
          ? ''
          : String(profile.baseline_tests.squat.barbell_reps),
      topSupportHoldSeconds:
        profile.baseline_tests.top_support_hold_seconds === null ||
        profile.baseline_tests.top_support_hold_seconds === undefined
          ? ''
          : String(profile.baseline_tests.top_support_hold_seconds),
    },
    bodyweightUnit: profile.bodyweight_unit === 'lb' ? 'lb' : 'kg',
    currentBodyweightValue: profile.current_bodyweight_value === null ? '' : String(profile.current_bodyweight_value),
    deloadPreference: profile.deload_preference,
    displayName: profile.display_name,
    effortTrackingPreference: profile.effort_tracking_preference,
    experienceLevel: profile.experience_level,
    goalModules: mapGoalModulesToForm(profile.goal_modules, requiredGoalModules),
    injuryNotes: profile.injury_notes ?? '',
    intensityPreference: profile.intensity_preference,
    heightUnit: profile.height_unit ?? 'cm',
    heightValue:
      profile.height_value === null || profile.height_value === undefined ? '' : String(profile.height_value),
    longTermTargetSkills: [...(profile.long_term_target_skills ?? [])],
    movementLimitation: {
      area: limitation?.area ?? 'wrist',
      notes: limitation?.notes ?? '',
      severity: limitation?.severity ?? 'mild',
      status: limitation?.status ?? 'past',
    },
    mobilityChecks: { ...defaultProfileSettingsForm().mobilityChecks, ...profile.mobility_checks },
    painFlags: mapPainFlags(profile.pain_flags),
    preferredSessionMinutes:
      profile.preferred_session_minutes === null ? '' : String(profile.preferred_session_minutes),
    preferredTrainingDays: [...profile.preferred_training_days],
    priorSportBackground: [...(profile.prior_sport_background ?? [])],
    primaryGoal: profile.primary_goal ?? 'strength',
    primaryTargetSkill: profile.primary_target_skill ?? '',
    progressionPace: profile.progression_pace,
    requiredGoalModules,
    roadmapSuggestions: mapRoadmapSuggestions(profile.roadmap_suggestions),
    secondaryGoals: profile.secondary_goals.filter((goal) =>
      (compatibleSecondaryGoals[profile.primary_goal ?? 'strength'] ?? []).includes(goal),
    ),
    secondaryTargetSkills: [...profile.secondary_target_skills],
    sessionStructurePreferences: [...profile.session_structure_preferences],
    skillStatuses: Object.fromEntries(
      Object.entries(profile.skill_statuses).map(([key, value]) => [
        key,
        {
          bestHoldSeconds:
            value.best_hold_seconds === null || value.best_hold_seconds === undefined
              ? ''
              : String(value.best_hold_seconds),
          maxReps:
            value.max_strict_reps === null || value.max_strict_reps === undefined ? '' : String(value.max_strict_reps),
          notes: value.notes ?? '',
          status: value.status,
        },
      ]),
    ),
    targetSkillsText: profile.target_skills.join('\n'),
    timezone: profile.timezone,
    trainingAgeMonths: profile.training_age_months === null ? '' : String(profile.training_age_months),
    trainingLocations: [...profile.training_locations],
    unitSystem: profile.unit_system === 'imperial' ? 'imperial' : 'metric',
    weightedBaselines: {
      experience: profile.weighted_baselines.experience,
      movements: profile.weighted_baselines.movements.map((movement) => ({
        externalLoadValue:
          movement.external_load_value === null || movement.external_load_value === undefined
            ? ''
            : String(movement.external_load_value),
        movement: movement.movement,
        reps: movement.reps === null || movement.reps === undefined ? '' : String(movement.reps),
        rir: movement.rir === null || movement.rir === undefined ? '' : String(movement.rir),
      })),
      unit: profile.weighted_baselines.unit,
    },
    weightTrend: profile.weight_trend ?? 'unknown',
    weeklySessionGoal: profile.weekly_session_goal === null ? '' : String(profile.weekly_session_goal),
  }
}

function mapFormToUpdateBody(form: ProfileSettingsForm): ProfileUpdateBody {
  const requiredGoalModules = activeRequiredGoalModules(form)
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
    age_years: nullableNumber(form.ageYears),
    available_equipment: form.availableEquipment.filter(isSupportedEquipment),
    base_focus_areas: [...form.baseFocusAreas],
    baseline_tests: {
      dips: {
        fallback_reps: nullableNumber(form.baselineTests.dipFallbackReps),
        fallback_seconds: nullableNumber(form.baselineTests.dipFallbackSeconds),
        fallback_variant: form.baselineTests.dipFallbackVariant,
        max_strict_reps: nullableNumber(form.baselineTests.dipMaxReps),
      },
      hollow_hold_seconds: nullableNumber(form.baselineTests.hollowHoldSeconds),
      lower_body: {
        load_unit: form.baselineTests.lowerBodyLoadUnit,
        load_value: nullableNumber(form.baselineTests.lowerBodyLoadValue),
        reps: nullableNumber(form.baselineTests.lowerBodyReps),
        variant: form.baselineTests.lowerBodyVariant,
      },
      passive_hang_seconds: nullableNumber(form.baselineTests.passiveHangSeconds),
      pull_ups: {
        fallback_reps: nullableNumber(form.baselineTests.pullUpFallbackReps),
        fallback_seconds: nullableNumber(form.baselineTests.pullUpFallbackSeconds),
        fallback_variant: form.baselineTests.pullUpFallbackVariant,
        max_strict_reps: nullableNumber(form.baselineTests.pullUpMaxReps),
      },
      push_ups: {
        max_strict_reps: nullableNumber(form.baselineTests.pushUpMaxReps),
      },
      rows: {
        max_reps: nullableNumber(form.baselineTests.rowMaxReps),
        variant: form.baselineTests.rowVariant,
      },
      squat: {
        barbell_load_value: nullableNumber(form.baselineTests.squatBarbellLoadValue),
        barbell_reps: nullableNumber(form.baselineTests.squatBarbellReps),
      },
      top_support_hold_seconds: nullableNumber(form.baselineTests.topSupportHoldSeconds),
    },
    bodyweight_unit: form.bodyweightUnit,
    current_bodyweight_value: nullableNumber(form.currentBodyweightValue),
    deload_preference: form.deloadPreference,
    display_name: form.displayName.trim(),
    effort_tracking_preference: form.effortTrackingPreference,
    experience_level: form.experienceLevel,
    goal_modules: serializeGoalModules(form.goalModules, requiredGoalModules),
    injury_notes: form.injuryNotes.trim() || null,
    intensity_preference: form.intensityPreference,
    height_unit: form.heightUnit,
    height_value: nullableNumber(form.heightValue),
    long_term_target_skills: [...form.longTermTargetSkills],
    movement_limitations: movementLimitations,
    mobility_checks: { ...form.mobilityChecks },
    pain_flags: serializePainFlags(form.painFlags),
    preferred_session_minutes: nullableNumber(form.preferredSessionMinutes),
    preferred_training_days: form.preferredTrainingDays,
    prior_sport_background: [...form.priorSportBackground],
    primary_goal: form.primaryGoal || null,
    primary_target_skill: form.primaryTargetSkill || null,
    progression_pace: form.progressionPace,
    secondary_goals: form.secondaryGoals.filter((goal) =>
      (compatibleSecondaryGoals[form.primaryGoal] ?? []).includes(goal),
    ),
    secondary_target_skills: [...form.secondaryTargetSkills],
    session_structure_preferences: form.sessionStructurePreferences.slice(0, 3),
    skill_statuses: { ...form.skillStatuses },
    target_skills: activeTargetSkills(form),
    timezone: form.timezone.trim(),
    training_age_months: nullableNumber(form.trainingAgeMonths),
    training_locations: form.trainingLocations,
    unit_system: form.unitSystem,
    weighted_baselines: {
      experience: form.weightedBaselines.experience,
      movements: form.weightedBaselines.movements
        .filter((movement) => movement.movement)
        .map((movement) => ({
          external_load_value: nullableNumber(movement.externalLoadValue),
          movement: movement.movement,
          reps: nullableNumber(movement.reps),
          rir: nullableNumber(movement.rir),
        })),
      unit: form.weightedBaselines.unit,
    },
    weight_trend: form.weightTrend,
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

function activeTargetSkills(form: ProfileSettingsForm): string[] {
  return form.primaryTargetSkill ? [form.primaryTargetSkill] : splitTargetSkills(form.targetSkillsText).slice(0, 1)
}

function activeRequiredGoalModules(form: ProfileSettingsForm): string[] {
  return form.requiredGoalModules.length > 0
    ? [...form.requiredGoalModules]
    : requiredGoalModulesForGoal(form.primaryTargetSkill)
}

function isSupportedEquipment(value: string): boolean {
  return equipmentOptions.some((option) => option.value === value)
}

function emptyPainFlags(): Record<string, { notes: string; severity: string; status: string }> {
  return Object.fromEntries(
    painRegions.map((region) => [
      region,
      {
        notes: '',
        severity: 'none',
        status: 'none',
      },
    ]),
  )
}

function mapPainFlags(flags: Record<string, { notes: string | null; severity: string; status: string }>) {
  return Object.fromEntries(
    painRegions.map((region) => {
      const flag = flags[region]

      return [
        region,
        {
          notes: flag?.notes ?? '',
          severity: flag?.severity ?? 'none',
          status: flag?.status ?? 'none',
        },
      ]
    }),
  )
}

function serializePainFlags(flags: Record<string, { notes: string; severity: string; status: string }>) {
  return Object.fromEntries(
    painRegions.map((region) => {
      const flag = flags[region] ?? { notes: '', severity: 'none', status: 'none' }

      return [
        region,
        {
          notes: flag.notes.trim() || null,
          severity: flag.severity,
          status: flag.status,
        },
      ]
    }),
  )
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
    age_years: 'ageYears',
    base_focus_areas: 'baseFocusAreas',
    baseline_tests: 'baselineTests',
    bodyweight_unit: 'bodyweightUnit',
    current_bodyweight_value: 'currentBodyweightValue',
    deload_preference: 'deloadPreference',
    display_name: 'displayName',
    effort_tracking_preference: 'effortTrackingPreference',
    experience_level: 'experienceLevel',
    goal_modules: 'goalModules',
    injury_notes: 'injuryNotes',
    intensity_preference: 'intensityPreference',
    height_unit: 'heightUnit',
    height_value: 'heightValue',
    long_term_target_skills: 'longTermTargetSkills',
    movement_limitations: 'movementLimitation',
    mobility_checks: 'mobilityChecks',
    pain_flags: 'painFlags',
    preferred_session_minutes: 'preferredSessionMinutes',
    preferred_training_days: 'preferredTrainingDays',
    prior_sport_background: 'priorSportBackground',
    primary_goal: 'primaryGoal',
    primary_target_skill: 'primaryTargetSkill',
    progression_pace: 'progressionPace',
    secondary_goals: 'secondaryGoals',
    secondary_target_skills: 'secondaryTargetSkills',
    session_structure_preferences: 'sessionStructurePreferences',
    skill_statuses: 'skillStatuses',
    target_skills: 'targetSkillsText',
    timezone: 'timezone',
    training_age_months: 'trainingAgeMonths',
    training_locations: 'trainingLocations',
    unit_system: 'unitSystem',
    weighted_baselines: 'weightedBaselines',
    weight_trend: 'weightTrend',
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
