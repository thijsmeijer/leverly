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

import {
  mobilityCheckOptions,
  skillStatusKeys,
  skillStatusMeasurements,
  skillStatusOptions,
} from '../data/onboardingOptions'
import type { OnboardingFieldErrors, OnboardingForm, OnboardingRoadmapSuggestions, OnboardingState } from '../types'

const onboardingSkillStatusKeys = new Set<string>(skillStatusKeys)
const painRegions = ['wrist', 'elbow', 'shoulder', 'low_back', 'knee', 'ankle'] as const

type ServerValidationBody = {
  readonly errors?: Record<string, string[]>
  readonly message?: string
}

type AthleteOnboardingResponse = NonNullable<ApiResponseBody<'/me/onboarding', 'get'>>
type AthleteOnboarding = AthleteOnboardingResponse['data']

type OnboardingUpdateBody = {
  readonly age_years: number | null
  readonly available_equipment: string[]
  readonly base_focus_areas: string[]
  readonly bodyweight_unit: string
  readonly complete?: boolean
  readonly current_bodyweight_value: number | null
  readonly current_level_tests: {
    readonly dips: {
      readonly fallback_reps: number | null
      readonly fallback_seconds: number | null
      readonly fallback_variant: string
      readonly max_strict_reps: number | null
    }
    readonly hollow_hold_seconds: number | null
    readonly lower_body: {
      readonly load_unit: string
      readonly load_value: number | null
      readonly reps: number | null
      readonly variant: string
    }
    readonly passive_hang_seconds: number | null
    readonly pull_ups: {
      readonly fallback_reps: number | null
      readonly fallback_seconds: number | null
      readonly fallback_variant: string
      readonly max_strict_reps: number | null
    }
    readonly push_ups: {
      readonly max_strict_reps: number | null
    }
    readonly rows: {
      readonly max_reps: number | null
      readonly variant: string
    }
    readonly squat: {
      readonly barbell_load_value: number | null
      readonly barbell_reps: number | null
    }
    readonly top_support_hold_seconds: number | null
  }
  readonly experience_level: string
  readonly goal_modules: ReturnType<typeof serializeGoalModules>
  readonly height_unit: string
  readonly height_value: number | null
  readonly long_term_target_skills: string[]
  readonly mobility_checks: Record<string, string>
  readonly pain_areas: string[]
  readonly pain_flags: Record<string, { notes: string | null; severity: string; status: string }>
  readonly pain_level: number | null
  readonly pain_notes: string | null
  readonly preferred_session_minutes: number | null
  readonly preferred_training_days: string[]
  readonly prior_sport_background: string[]
  readonly primary_goal: string | null
  readonly primary_target_skill: string | null
  readonly readiness_rating: number | null
  readonly secondary_goals: string[]
  readonly secondary_target_skills: string[]
  readonly skill_statuses: Record<
    string,
    {
      readonly best_hold_seconds?: number | null
      readonly max_strict_reps?: number | null
      readonly notes?: string | null
      readonly status: string
    }
  >
  readonly sleep_quality: number | null
  readonly soreness_level: number | null
  readonly starter_plan_key: string | null
  readonly target_skills: string[]
  readonly training_age_months: number | null
  readonly training_locations: string[]
  readonly weight_trend: string
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
  readonly weekly_session_goal: number | null
}

export class OnboardingValidationError extends Error {
  readonly errors: OnboardingFieldErrors

  constructor(errors: OnboardingFieldErrors) {
    super('Onboarding validation failed.')
    this.name = 'OnboardingValidationError'
    this.errors = errors
  }
}

export function defaultOnboardingForm(): OnboardingForm {
  return {
    ageYears: '',
    availableEquipment: [],
    baseFocusAreas: [],
    bodyweightUnit: 'kg',
    currentBodyweightValue: '',
    currentLevelTests: {
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
    experienceLevel: 'new',
    goalModules: defaultGoalModules(),
    heightUnit: 'cm',
    heightValue: '',
    longTermTargetSkills: [],
    mobilityChecks: Object.fromEntries(mobilityCheckOptions.map((option) => [option.value, 'not_tested'])),
    painAreas: [],
    painFlags: emptyPainFlags(),
    painLevel: '0',
    painNotes: '',
    preferredSessionMinutes: '45',
    preferredTrainingDays: [],
    priorSportBackground: [],
    primaryTargetSkill: '',
    primaryGoal: 'skill',
    readinessRating: '3',
    requiredGoalModules: [],
    roadmapSuggestions: emptyRoadmapSuggestions(),
    secondaryGoals: [],
    secondaryTargetSkills: [],
    skillStatuses: Object.fromEntries(
      skillStatusKeys.map((key) => [
        key,
        {
          bestHoldSeconds: '',
          maxReps: '',
          notes: '',
          status: skillStatusOptions[key][0]?.value ?? 'not_tested',
        },
      ]),
    ),
    sleepQuality: '3',
    sorenessLevel: '2',
    starterPlanKey: 'full_body_3_day',
    targetSkills: [],
    trainingAgeMonths: '',
    trainingLocations: [],
    weightTrend: 'unknown',
    weightedBaselines: {
      experience: 'none',
      movements: [],
      unit: 'kg',
    },
    weeklySessionGoal: '3',
  }
}

export async function fetchOnboarding(): Promise<OnboardingState> {
  const response = await leverlyApiRequest('/me/onboarding', 'get', {
    errorMode: 'throw',
    notFoundMode: 'throw',
  })

  if (!response) {
    throw new Error('Onboarding response was empty.')
  }

  return mapOnboardingResponse(response)
}

export async function saveOnboarding(
  form: OnboardingForm,
  options: { complete?: boolean } = {},
): Promise<OnboardingState> {
  try {
    const response = await leverlyApiRequest('/me/onboarding', 'patch', {
      body: mapFormToUpdateBody(form, options),
      errorMode: 'throw',
      notFoundMode: 'throw',
    })

    if (!response) {
      throw new Error('Onboarding response was empty.')
    }

    return mapOnboardingResponse(response)
  } catch (error) {
    if (error instanceof ApiRequestError && error.status === 422) {
      throw new OnboardingValidationError(mapServerValidationErrors(error.body))
    }

    throw error
  }
}

export function validateOnboardingStep(form: OnboardingForm, step: string): OnboardingFieldErrors {
  const errors: OnboardingFieldErrors = {}

  if (step === 'context') {
    addNumberError(errors, 'ageYears', form.ageYears, 13, 90)
    addNumberError(errors, 'trainingAgeMonths', form.trainingAgeMonths, 0, 1200)
    addNumberError(errors, 'currentBodyweightValue', form.currentBodyweightValue, 20, 400)
    addNumberError(
      errors,
      'heightValue',
      form.heightValue,
      form.heightUnit === 'in' ? 36 : 90,
      form.heightUnit === 'in' ? 100 : 250,
    )

    if (form.priorSportBackground.length === 0) {
      errors.priorSportBackground = 'Choose at least one background option, even if you are starting fresh.'
    }
  }

  if (step === 'goal') {
    const activeSkills = activeRoadmapSkills(form.roadmapSuggestions)
    const primaryTarget = form.primaryTargetSkill

    if (!form.primaryGoal) {
      errors.primaryGoal = 'Choose the main outcome for your first plan.'
    }

    if (!primaryTarget) {
      errors.primaryTargetSkill = 'Choose the one suggested target Leverly should prioritize first.'
    }

    if (primaryTarget && !activeSkills.includes(primaryTarget)) {
      errors.primaryTargetSkill = 'Primary target must come from the suggested current or bridge roadmap.'
    }

    if (form.targetSkills.length > 1 || (form.targetSkills.length === 1 && form.targetSkills[0] !== primaryTarget)) {
      errors.targetSkills = 'Active targets should contain only the primary roadmap.'
    }

    if (
      form.secondaryTargetSkills.some((skill) => skill === primaryTarget || form.longTermTargetSkills.includes(skill))
    ) {
      errors.secondaryTargetSkills = 'Secondary interests must differ from the primary and long-term targets.'
    }

    if (form.baseFocusAreas.length === 0) {
      errors.baseFocusAreas = 'Choose at least one base area from the recommendation.'
    }

    if (form.longTermTargetSkills.some((skill) => form.targetSkills.includes(skill))) {
      errors.longTermTargetSkills = 'Long-term targets should be different from the active roadmap.'
    }
  }

  if (step === 'modules') {
    for (const module of activeRequiredGoalModules(form)) {
      if (!isGoalModuleTested(form.goalModules[module])) {
        errors[`goalModules.${module}`] = 'Add the tested progression for the selected primary goal.'
      }
    }
  }

  if (step === 'equipment' && form.trainingLocations.length === 0) {
    errors.trainingLocations = 'Choose where you can train most weeks.'
  }

  if (step === 'level') {
    addNumberError(errors, 'currentLevelTests.pushUpMaxReps', form.currentLevelTests.pushUpMaxReps, 0, 200)
    addNumberError(errors, 'currentLevelTests.pullUpMaxReps', form.currentLevelTests.pullUpMaxReps, 0, 100)
    addNumberError(errors, 'currentLevelTests.dipMaxReps', form.currentLevelTests.dipMaxReps, 0, 100)
    addNumberError(errors, 'currentLevelTests.rowMaxReps', form.currentLevelTests.rowMaxReps, 0, 100)
    addNumberError(errors, 'currentLevelTests.lowerBodyReps', form.currentLevelTests.lowerBodyReps, 0, 100)
    addOptionalNumberError(
      errors,
      'currentLevelTests.lowerBodyLoadValue',
      form.currentLevelTests.lowerBodyLoadValue,
      0,
      1000,
    )
    addNumberError(
      errors,
      'currentLevelTests.squatBarbellLoadValue',
      form.currentLevelTests.squatBarbellLoadValue,
      0,
      1000,
    )
    addNumberError(errors, 'currentLevelTests.squatBarbellReps', form.currentLevelTests.squatBarbellReps, 0, 30)
    addNumberError(errors, 'currentLevelTests.hollowHoldSeconds', form.currentLevelTests.hollowHoldSeconds, 0, 600)
    addNumberError(errors, 'currentLevelTests.passiveHangSeconds', form.currentLevelTests.passiveHangSeconds, 0, 600)
    addNumberError(
      errors,
      'currentLevelTests.topSupportHoldSeconds',
      form.currentLevelTests.topSupportHoldSeconds,
      0,
      600,
    )
  }

  if (step === 'mobility') {
    const testedMobility = Object.values(form.mobilityChecks).some((value) => value !== 'not_tested')

    if (!testedMobility) {
      errors.mobilityChecks = 'Mark at least one position so the first plan can avoid obvious blockers.'
    }

    if (Number(form.painLevel) >= 4 && form.painAreas.length === 0) {
      errors.painAreas = 'Choose the painful area so recommendations can stay conservative.'
    }
  }

  if (step === 'availability') {
    if (form.preferredTrainingDays.length === 0) {
      errors.preferredTrainingDays = 'Choose at least one training day.'
    }

    addNumberError(errors, 'weeklySessionGoal', form.weeklySessionGoal, 1, 14)
  }

  return errors
}

function mapOnboardingResponse(response: AthleteOnboardingResponse): OnboardingState {
  return {
    form: mapOnboardingToForm(response.data),
    isComplete: response.data.is_complete,
    missingSections: [...response.data.missing_sections],
    onboardingId: response.data.id,
  }
}

function mapOnboardingToForm(onboarding: AthleteOnboarding): OnboardingForm {
  const defaults = defaultOnboardingForm()
  const requiredGoalModules = [
    ...(onboarding.required_goal_modules ?? requiredGoalModulesForGoal(onboarding.primary_target_skill ?? '')),
  ]

  return {
    ...defaults,
    ageYears: onboarding.age_years === null ? '' : String(onboarding.age_years),
    availableEquipment: [...onboarding.available_equipment],
    baseFocusAreas: [...onboarding.base_focus_areas],
    bodyweightUnit: onboarding.bodyweight_unit,
    currentBodyweightValue:
      onboarding.current_bodyweight_value === null ? '' : String(onboarding.current_bodyweight_value),
    currentLevelTests: {
      dipFallbackReps:
        onboarding.current_level_tests.dips.fallback_reps === null ||
        onboarding.current_level_tests.dips.fallback_reps === undefined
          ? ''
          : String(onboarding.current_level_tests.dips.fallback_reps),
      dipFallbackSeconds:
        onboarding.current_level_tests.dips.fallback_seconds === null ||
        onboarding.current_level_tests.dips.fallback_seconds === undefined
          ? ''
          : String(onboarding.current_level_tests.dips.fallback_seconds),
      dipFallbackVariant:
        onboarding.current_level_tests.dips.fallback_variant ?? defaults.currentLevelTests.dipFallbackVariant,
      dipMaxReps:
        onboarding.current_level_tests.dips.max_strict_reps === null
          ? ''
          : String(onboarding.current_level_tests.dips.max_strict_reps),
      hollowHoldSeconds:
        onboarding.current_level_tests.hollow_hold_seconds === null
          ? ''
          : String(onboarding.current_level_tests.hollow_hold_seconds),
      lowerBodyLoadUnit:
        onboarding.current_level_tests.lower_body?.load_unit ?? defaults.currentLevelTests.lowerBodyLoadUnit,
      lowerBodyLoadValue:
        onboarding.current_level_tests.lower_body?.load_value === null ||
        onboarding.current_level_tests.lower_body?.load_value === undefined
          ? ''
          : String(onboarding.current_level_tests.lower_body.load_value),
      lowerBodyReps:
        onboarding.current_level_tests.lower_body?.reps === null ||
        onboarding.current_level_tests.lower_body?.reps === undefined
          ? ''
          : String(onboarding.current_level_tests.lower_body.reps),
      lowerBodyVariant:
        onboarding.current_level_tests.lower_body?.variant ?? defaults.currentLevelTests.lowerBodyVariant,
      passiveHangSeconds:
        onboarding.current_level_tests.passive_hang_seconds === null ||
        onboarding.current_level_tests.passive_hang_seconds === undefined
          ? ''
          : String(onboarding.current_level_tests.passive_hang_seconds),
      pullUpFallbackReps:
        onboarding.current_level_tests.pull_ups.fallback_reps === null ||
        onboarding.current_level_tests.pull_ups.fallback_reps === undefined
          ? ''
          : String(onboarding.current_level_tests.pull_ups.fallback_reps),
      pullUpFallbackSeconds:
        onboarding.current_level_tests.pull_ups.fallback_seconds === null ||
        onboarding.current_level_tests.pull_ups.fallback_seconds === undefined
          ? ''
          : String(onboarding.current_level_tests.pull_ups.fallback_seconds),
      pullUpFallbackVariant:
        onboarding.current_level_tests.pull_ups.fallback_variant ?? defaults.currentLevelTests.pullUpFallbackVariant,
      pullUpMaxReps:
        onboarding.current_level_tests.pull_ups.max_strict_reps === null
          ? ''
          : String(onboarding.current_level_tests.pull_ups.max_strict_reps),
      pushUpMaxReps:
        onboarding.current_level_tests.push_ups.max_strict_reps === null
          ? ''
          : String(onboarding.current_level_tests.push_ups.max_strict_reps),
      rowMaxReps:
        onboarding.current_level_tests.rows?.max_reps === null ||
        onboarding.current_level_tests.rows?.max_reps === undefined
          ? ''
          : String(onboarding.current_level_tests.rows.max_reps),
      rowVariant: onboarding.current_level_tests.rows?.variant ?? defaults.currentLevelTests.rowVariant,
      squatBarbellLoadValue:
        onboarding.current_level_tests.squat.barbell_load_value === null
          ? ''
          : String(onboarding.current_level_tests.squat.barbell_load_value),
      squatBarbellReps:
        onboarding.current_level_tests.squat.barbell_reps === null
          ? ''
          : String(onboarding.current_level_tests.squat.barbell_reps),
      topSupportHoldSeconds:
        onboarding.current_level_tests.top_support_hold_seconds === null ||
        onboarding.current_level_tests.top_support_hold_seconds === undefined
          ? ''
          : String(onboarding.current_level_tests.top_support_hold_seconds),
    },
    experienceLevel: onboarding.experience_level,
    goalModules: mapGoalModulesToForm(onboarding.goal_modules, requiredGoalModules),
    heightUnit: onboarding.height_unit,
    heightValue: onboarding.height_value === null ? '' : String(onboarding.height_value),
    longTermTargetSkills: [...onboarding.long_term_target_skills],
    mobilityChecks: { ...defaults.mobilityChecks, ...onboarding.mobility_checks },
    painAreas: [...onboarding.pain_areas],
    painFlags: mapPainFlags(onboarding.pain_flags),
    painLevel: onboarding.pain_level === null ? defaults.painLevel : String(onboarding.pain_level),
    painNotes: onboarding.pain_notes ?? '',
    preferredSessionMinutes:
      onboarding.preferred_session_minutes === null
        ? defaults.preferredSessionMinutes
        : String(onboarding.preferred_session_minutes),
    preferredTrainingDays: [...onboarding.preferred_training_days],
    priorSportBackground: [...onboarding.prior_sport_background],
    primaryTargetSkill: onboarding.primary_target_skill ?? defaults.primaryTargetSkill,
    primaryGoal: onboarding.primary_goal ?? defaults.primaryGoal,
    readinessRating:
      onboarding.readiness_rating === null ? defaults.readinessRating : String(onboarding.readiness_rating),
    requiredGoalModules,
    roadmapSuggestions: mapRoadmapSuggestions(onboarding.roadmap_suggestions),
    secondaryGoals: [...onboarding.secondary_goals],
    secondaryTargetSkills: [...onboarding.secondary_target_skills],
    skillStatuses: {
      ...defaults.skillStatuses,
      ...Object.fromEntries(
        Object.entries(onboarding.skill_statuses)
          .filter(([key]) => onboardingSkillStatusKeys.has(key))
          .map(([key, value]) => [
            key,
            {
              bestHoldSeconds:
                value.best_hold_seconds === null || value.best_hold_seconds === undefined
                  ? ''
                  : String(value.best_hold_seconds),
              maxReps:
                value.max_strict_reps === null || value.max_strict_reps === undefined
                  ? ''
                  : String(value.max_strict_reps),
              notes: value.notes ?? '',
              status: normalizeSkillStatus(key, value.status),
            },
          ]),
      ),
    },
    sleepQuality: onboarding.sleep_quality === null ? defaults.sleepQuality : String(onboarding.sleep_quality),
    sorenessLevel: onboarding.soreness_level === null ? defaults.sorenessLevel : String(onboarding.soreness_level),
    starterPlanKey: onboarding.starter_plan_key ?? defaults.starterPlanKey,
    targetSkills: [...onboarding.target_skills],
    trainingAgeMonths: onboarding.training_age_months === null ? '' : String(onboarding.training_age_months),
    trainingLocations: [...onboarding.training_locations],
    weightTrend: onboarding.weight_trend ?? defaults.weightTrend,
    weightedBaselines: {
      experience: onboarding.weighted_baselines.experience,
      movements: onboarding.weighted_baselines.movements.map((movement) => ({
        externalLoadValue:
          movement.external_load_value === null || movement.external_load_value === undefined
            ? ''
            : String(movement.external_load_value),
        movement: movement.movement,
        reps: movement.reps === null || movement.reps === undefined ? '' : String(movement.reps),
        rir: movement.rir === null || movement.rir === undefined ? '' : String(movement.rir),
      })),
      unit: onboarding.weighted_baselines.unit,
    },
    weeklySessionGoal:
      onboarding.weekly_session_goal === null ? defaults.weeklySessionGoal : String(onboarding.weekly_session_goal),
  }
}

function mapFormToUpdateBody(form: OnboardingForm, options: { complete?: boolean }): OnboardingUpdateBody {
  const requiredGoalModules = activeRequiredGoalModules(form)

  const body: OnboardingUpdateBody = {
    age_years: nullableInteger(form.ageYears),
    available_equipment: [...form.availableEquipment],
    base_focus_areas: [...form.baseFocusAreas],
    bodyweight_unit: form.bodyweightUnit,
    current_bodyweight_value: nullableNumber(form.currentBodyweightValue),
    current_level_tests: {
      dips: {
        fallback_reps: nullableInteger(form.currentLevelTests.dipFallbackReps),
        fallback_seconds: nullableInteger(form.currentLevelTests.dipFallbackSeconds),
        fallback_variant: form.currentLevelTests.dipFallbackVariant,
        max_strict_reps: nullableInteger(form.currentLevelTests.dipMaxReps),
      },
      hollow_hold_seconds: nullableInteger(form.currentLevelTests.hollowHoldSeconds),
      lower_body: {
        load_unit: form.currentLevelTests.lowerBodyLoadUnit,
        load_value: nullableNumber(form.currentLevelTests.lowerBodyLoadValue),
        reps: nullableInteger(form.currentLevelTests.lowerBodyReps),
        variant: form.currentLevelTests.lowerBodyVariant,
      },
      passive_hang_seconds: nullableInteger(form.currentLevelTests.passiveHangSeconds),
      pull_ups: {
        fallback_reps: nullableInteger(form.currentLevelTests.pullUpFallbackReps),
        fallback_seconds: nullableInteger(form.currentLevelTests.pullUpFallbackSeconds),
        fallback_variant: form.currentLevelTests.pullUpFallbackVariant,
        max_strict_reps: nullableInteger(form.currentLevelTests.pullUpMaxReps),
      },
      push_ups: {
        max_strict_reps: nullableInteger(form.currentLevelTests.pushUpMaxReps),
      },
      rows: {
        max_reps: nullableInteger(form.currentLevelTests.rowMaxReps),
        variant: form.currentLevelTests.rowVariant,
      },
      squat: {
        barbell_load_value: nullableNumber(form.currentLevelTests.squatBarbellLoadValue),
        barbell_reps: nullableInteger(form.currentLevelTests.squatBarbellReps),
      },
      top_support_hold_seconds: nullableInteger(form.currentLevelTests.topSupportHoldSeconds),
    },
    experience_level: form.experienceLevel,
    goal_modules: serializeGoalModules(form.goalModules, requiredGoalModules),
    height_unit: form.heightUnit,
    height_value: nullableNumber(form.heightValue),
    long_term_target_skills: [...form.longTermTargetSkills],
    mobility_checks: { ...form.mobilityChecks },
    pain_areas: [...form.painAreas],
    pain_flags: serializePainFlags(form.painFlags),
    pain_level: nullableInteger(form.painLevel),
    pain_notes: form.painNotes.trim() || null,
    preferred_session_minutes: nullableInteger(form.preferredSessionMinutes),
    preferred_training_days: [...form.preferredTrainingDays],
    prior_sport_background: [...form.priorSportBackground],
    primary_goal: form.primaryGoal || null,
    primary_target_skill: form.primaryTargetSkill || null,
    readiness_rating: nullableInteger(form.readinessRating),
    secondary_goals: [...form.secondaryGoals],
    secondary_target_skills: [...form.secondaryTargetSkills],
    skill_statuses: Object.fromEntries(
      Object.entries(form.skillStatuses)
        .filter(([key]) => onboardingSkillStatusKeys.has(key))
        .map(([key, value]) => [
          key,
          {
            best_hold_seconds: skillStatusMeasurements[key as (typeof skillStatusKeys)[number]].hold
              ? nullableInteger(value.bestHoldSeconds)
              : null,
            max_strict_reps: skillStatusMeasurements[key as (typeof skillStatusKeys)[number]].reps
              ? nullableInteger(value.maxReps)
              : null,
            notes: value.notes.trim() || null,
            status: normalizeSkillStatus(key, value.status),
          },
        ]),
    ),
    sleep_quality: nullableInteger(form.sleepQuality),
    soreness_level: nullableInteger(form.sorenessLevel),
    starter_plan_key: form.starterPlanKey || null,
    target_skills: activeTargetSkills(form),
    training_age_months: nullableInteger(form.trainingAgeMonths),
    training_locations: [...form.trainingLocations],
    weight_trend: form.weightTrend,
    weighted_baselines: {
      experience: form.weightedBaselines.experience,
      movements: form.weightedBaselines.movements
        .filter((movement) => movement.movement)
        .map((movement) => ({
          external_load_value: nullableNumber(movement.externalLoadValue),
          movement: movement.movement,
          reps: nullableInteger(movement.reps),
          rir: nullableInteger(movement.rir),
        })),
      unit: form.weightedBaselines.unit,
    },
    weekly_session_goal: nullableInteger(form.weeklySessionGoal),
  }

  if (options.complete) {
    return { ...body, complete: true }
  }

  return body
}

function nullableInteger(value: number | string | null | undefined): number | null {
  const text = textValue(value)

  if (!text) {
    return null
  }

  const parsed = Number(text)

  return Number.isFinite(parsed) ? Math.trunc(parsed) : null
}

function nullableNumber(value: number | string | null | undefined): number | null {
  const text = textValue(value)

  if (!text) {
    return null
  }

  const parsed = Number(text)

  return Number.isFinite(parsed) ? parsed : null
}

function addNumberError(
  errors: OnboardingFieldErrors,
  key: string,
  value: number | string,
  min: number,
  max: number,
): void {
  const text = textValue(value)

  if (!text) {
    errors[key] = `Enter a number from ${min} to ${max}.`

    return
  }

  const parsed = Number(text)

  if (!Number.isFinite(parsed) || parsed < min || parsed > max) {
    errors[key] = `Enter a number from ${min} to ${max}.`
  }
}

function addOptionalNumberError(
  errors: OnboardingFieldErrors,
  key: string,
  value: number | string,
  min: number,
  max: number,
): void {
  const text = textValue(value)

  if (!text) {
    return
  }

  const parsed = Number(text)

  if (!Number.isFinite(parsed) || parsed < min || parsed > max) {
    errors[key] = `Enter a number from ${min} to ${max}.`
  }
}

function textValue(value: number | string | null | undefined): string {
  return String(value ?? '').trim()
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

function normalizeSkillStatus(key: string, status: string): string {
  if (!onboardingSkillStatusKeys.has(key)) {
    return status
  }

  const options = skillStatusOptions[key as (typeof skillStatusKeys)[number]]

  return options.some((option) => option.value === status) ? status : (options[0]?.value ?? 'not_tested')
}

function mapServerValidationErrors(body: unknown): OnboardingFieldErrors {
  if (!isServerValidationBody(body) || !body.errors) {
    return {}
  }

  return Object.fromEntries(
    Object.entries(body.errors).map(([key, messages]) => [serverKeyToField(key), messages[0] ?? 'Check this field.']),
  )
}

function serverKeyToField(key: string): string {
  const directMap: Record<string, string> = {
    available_equipment: 'availableEquipment',
    age_years: 'ageYears',
    base_focus_areas: 'baseFocusAreas',
    bodyweight_unit: 'bodyweightUnit',
    current_bodyweight_value: 'currentBodyweightValue',
    experience_level: 'experienceLevel',
    goal_modules: 'goalModules',
    height_unit: 'heightUnit',
    height_value: 'heightValue',
    long_term_target_skills: 'longTermTargetSkills',
    mobility_checks: 'mobilityChecks',
    pain_areas: 'painAreas',
    pain_flags: 'painFlags',
    pain_level: 'painLevel',
    pain_notes: 'painNotes',
    preferred_session_minutes: 'preferredSessionMinutes',
    preferred_training_days: 'preferredTrainingDays',
    prior_sport_background: 'priorSportBackground',
    primary_goal: 'primaryGoal',
    primary_target_skill: 'primaryTargetSkill',
    readiness_rating: 'readinessRating',
    secondary_goals: 'secondaryGoals',
    secondary_target_skills: 'secondaryTargetSkills',
    sleep_quality: 'sleepQuality',
    soreness_level: 'sorenessLevel',
    starter_plan_key: 'starterPlanKey',
    target_skills: 'targetSkills',
    training_age_months: 'trainingAgeMonths',
    training_locations: 'trainingLocations',
    weighted_baselines: 'weightedBaselines',
    weight_trend: 'weightTrend',
    weekly_session_goal: 'weeklySessionGoal',
  }

  if (directMap[key]) {
    return directMap[key]
  }

  return key
    .replace('current_level_tests.', 'currentLevelTests.')
    .replace('push_ups.max_strict_reps', 'pushUpMaxReps')
    .replace('dips.max_strict_reps', 'dipMaxReps')
    .replace('dips.fallback_variant', 'dipFallbackVariant')
    .replace('dips.fallback_reps', 'dipFallbackReps')
    .replace('dips.fallback_seconds', 'dipFallbackSeconds')
    .replace('pull_ups.max_strict_reps', 'pullUpMaxReps')
    .replace('pull_ups.fallback_variant', 'pullUpFallbackVariant')
    .replace('pull_ups.fallback_reps', 'pullUpFallbackReps')
    .replace('pull_ups.fallback_seconds', 'pullUpFallbackSeconds')
    .replace('rows.variant', 'rowVariant')
    .replace('rows.max_reps', 'rowMaxReps')
    .replace('lower_body.variant', 'lowerBodyVariant')
    .replace('lower_body.load_value', 'lowerBodyLoadValue')
    .replace('lower_body.load_unit', 'lowerBodyLoadUnit')
    .replace('lower_body.reps', 'lowerBodyReps')
    .replace('squat.barbell_load_value', 'squatBarbellLoadValue')
    .replace('squat.barbell_reps', 'squatBarbellReps')
    .replace('hollow_hold_seconds', 'hollowHoldSeconds')
    .replace('passive_hang_seconds', 'passiveHangSeconds')
    .replace('top_support_hold_seconds', 'topSupportHoldSeconds')
}

function isServerValidationBody(body: unknown): body is ServerValidationBody {
  return typeof body === 'object' && body !== null
}

function activeRoadmapSkills(suggestions: OnboardingRoadmapSuggestions): string[] {
  return [...suggestions.unlockedTracks, ...suggestions.bridgeTracks].map((track) => track.skill)
}

function activeTargetSkills(form: OnboardingForm): string[] {
  return form.primaryTargetSkill ? [form.primaryTargetSkill] : [...form.targetSkills].slice(0, 1)
}

function activeRequiredGoalModules(form: OnboardingForm): string[] {
  return form.requiredGoalModules.length > 0
    ? [...form.requiredGoalModules]
    : requiredGoalModulesForGoal(form.primaryTargetSkill)
}
