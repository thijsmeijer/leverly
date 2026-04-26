import { ApiRequestError, leverlyApiRequest, type ApiResponseBody } from '@/shared/api/leverlyApi/runtimeClient'

import { skillStatusKeys } from '../data/onboardingOptions'
import type { OnboardingFieldErrors, OnboardingForm, OnboardingState } from '../types'

type ServerValidationBody = {
  readonly errors?: Record<string, string[]>
  readonly message?: string
}

type AthleteOnboardingResponse = NonNullable<ApiResponseBody<'/me/onboarding', 'get'>>
type AthleteOnboarding = AthleteOnboardingResponse['data']

type OnboardingUpdateBody = {
  readonly available_equipment: string[]
  readonly complete?: boolean
  readonly current_level_tests: {
    readonly hollow_hold_seconds: number | null
    readonly pull_ups: {
      readonly max_strict_reps: number | null
      readonly progression: string | null
    }
    readonly push_ups: {
      readonly max_strict_reps: number | null
    }
    readonly squat: {
      readonly max_reps: number | null
      readonly progression: string | null
    }
  }
  readonly pain_areas: string[]
  readonly pain_level: number | null
  readonly pain_notes: string | null
  readonly preferred_session_minutes: number | null
  readonly preferred_training_days: string[]
  readonly preferred_training_time: string
  readonly primary_goal: string | null
  readonly readiness_rating: number | null
  readonly secondary_goals: string[]
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
  readonly training_locations: string[]
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
    availableEquipment: [],
    currentLevelTests: {
      hollowHoldSeconds: '',
      pullUpMaxReps: '',
      pullUpProgression: '',
      pushUpMaxReps: '',
      squatMaxReps: '',
      squatProgression: '',
    },
    painAreas: [],
    painLevel: '0',
    painNotes: '',
    preferredSessionMinutes: '45',
    preferredTrainingDays: [],
    preferredTrainingTime: 'flexible',
    primaryGoal: 'skill',
    readinessRating: '3',
    secondaryGoals: [],
    skillStatuses: Object.fromEntries(
      skillStatusKeys.map((key) => [
        key,
        {
          bestHoldSeconds: '',
          maxStrictReps: '',
          notes: '',
          status: 'not_started',
        },
      ]),
    ),
    sleepQuality: '3',
    sorenessLevel: '2',
    starterPlanKey: 'full_body_3_day',
    targetSkills: [],
    trainingLocations: [],
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

  if (step === 'goals') {
    if (!form.primaryGoal) {
      errors.primaryGoal = 'Choose the main outcome for your first plan.'
    }

    if (form.targetSkills.length === 0) {
      errors.targetSkills = 'Choose at least one skill or strength target.'
    }
  }

  if (step === 'equipment' && form.trainingLocations.length === 0) {
    errors.trainingLocations = 'Choose where you can train most weeks.'
  }

  if (step === 'level') {
    addNumberError(errors, 'currentLevelTests.pushUpMaxReps', form.currentLevelTests.pushUpMaxReps, 0, 200)
    addNumberError(errors, 'currentLevelTests.hollowHoldSeconds', form.currentLevelTests.hollowHoldSeconds, 0, 600)

    if (!form.currentLevelTests.pullUpProgression && !form.currentLevelTests.pullUpMaxReps) {
      errors['currentLevelTests.pullUpProgression'] = 'Choose your current pulling level or add strict reps.'
    }

    if (!form.currentLevelTests.squatProgression && !form.currentLevelTests.squatMaxReps) {
      errors['currentLevelTests.squatProgression'] = 'Choose your current squat level or add reps.'
    }
  }

  if (step === 'availability') {
    if (form.preferredTrainingDays.length === 0) {
      errors.preferredTrainingDays = 'Choose at least one training day.'
    }

    addNumberError(errors, 'preferredSessionMinutes', form.preferredSessionMinutes, 10, 240)
    addNumberError(errors, 'weeklySessionGoal', form.weeklySessionGoal, 1, 14)
  }

  if (step === 'readiness') {
    if (Number(form.painLevel) >= 4 && form.painAreas.length === 0) {
      errors.painAreas = 'Choose the painful area so recommendations can stay conservative.'
    }
  }

  if (step === 'starter' && !form.starterPlanKey) {
    errors.starterPlanKey = 'Choose the starter plan shape you want to begin with.'
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

  return {
    ...defaults,
    availableEquipment: [...onboarding.available_equipment],
    currentLevelTests: {
      hollowHoldSeconds:
        onboarding.current_level_tests.hollow_hold_seconds === null
          ? ''
          : String(onboarding.current_level_tests.hollow_hold_seconds),
      pullUpMaxReps:
        onboarding.current_level_tests.pull_ups.max_strict_reps === null
          ? ''
          : String(onboarding.current_level_tests.pull_ups.max_strict_reps),
      pullUpProgression: onboarding.current_level_tests.pull_ups.progression ?? '',
      pushUpMaxReps:
        onboarding.current_level_tests.push_ups.max_strict_reps === null
          ? ''
          : String(onboarding.current_level_tests.push_ups.max_strict_reps),
      squatMaxReps:
        onboarding.current_level_tests.squat.max_reps === null
          ? ''
          : String(onboarding.current_level_tests.squat.max_reps),
      squatProgression: onboarding.current_level_tests.squat.progression ?? '',
    },
    painAreas: [...onboarding.pain_areas],
    painLevel: onboarding.pain_level === null ? defaults.painLevel : String(onboarding.pain_level),
    painNotes: onboarding.pain_notes ?? '',
    preferredSessionMinutes:
      onboarding.preferred_session_minutes === null
        ? defaults.preferredSessionMinutes
        : String(onboarding.preferred_session_minutes),
    preferredTrainingDays: [...onboarding.preferred_training_days],
    preferredTrainingTime: onboarding.preferred_training_time,
    primaryGoal: onboarding.primary_goal ?? defaults.primaryGoal,
    readinessRating:
      onboarding.readiness_rating === null ? defaults.readinessRating : String(onboarding.readiness_rating),
    secondaryGoals: [...onboarding.secondary_goals],
    skillStatuses: {
      ...defaults.skillStatuses,
      ...Object.fromEntries(
        Object.entries(onboarding.skill_statuses).map(([key, value]) => [
          key,
          {
            bestHoldSeconds:
              value.best_hold_seconds === null || value.best_hold_seconds === undefined
                ? ''
                : String(value.best_hold_seconds),
            maxStrictReps:
              value.max_strict_reps === null || value.max_strict_reps === undefined
                ? ''
                : String(value.max_strict_reps),
            notes: value.notes ?? '',
            status: value.status,
          },
        ]),
      ),
    },
    sleepQuality: onboarding.sleep_quality === null ? defaults.sleepQuality : String(onboarding.sleep_quality),
    sorenessLevel: onboarding.soreness_level === null ? defaults.sorenessLevel : String(onboarding.soreness_level),
    starterPlanKey: onboarding.starter_plan_key ?? defaults.starterPlanKey,
    targetSkills: [...onboarding.target_skills],
    trainingLocations: [...onboarding.training_locations],
    weeklySessionGoal:
      onboarding.weekly_session_goal === null ? defaults.weeklySessionGoal : String(onboarding.weekly_session_goal),
  }
}

function mapFormToUpdateBody(form: OnboardingForm, options: { complete?: boolean }): OnboardingUpdateBody {
  const body: OnboardingUpdateBody = {
    available_equipment: [...form.availableEquipment],
    current_level_tests: {
      hollow_hold_seconds: nullableInteger(form.currentLevelTests.hollowHoldSeconds),
      pull_ups: {
        max_strict_reps: nullableInteger(form.currentLevelTests.pullUpMaxReps),
        progression: form.currentLevelTests.pullUpProgression || null,
      },
      push_ups: {
        max_strict_reps: nullableInteger(form.currentLevelTests.pushUpMaxReps),
      },
      squat: {
        max_reps: nullableInteger(form.currentLevelTests.squatMaxReps),
        progression: form.currentLevelTests.squatProgression || null,
      },
    },
    pain_areas: [...form.painAreas],
    pain_level: nullableInteger(form.painLevel),
    pain_notes: form.painNotes.trim() || null,
    preferred_session_minutes: nullableInteger(form.preferredSessionMinutes),
    preferred_training_days: [...form.preferredTrainingDays],
    preferred_training_time: form.preferredTrainingTime,
    primary_goal: form.primaryGoal || null,
    readiness_rating: nullableInteger(form.readinessRating),
    secondary_goals: [...form.secondaryGoals],
    skill_statuses: Object.fromEntries(
      Object.entries(form.skillStatuses).map(([key, value]) => [
        key,
        {
          best_hold_seconds: nullableInteger(value.bestHoldSeconds),
          max_strict_reps: nullableInteger(value.maxStrictReps),
          notes: value.notes.trim() || null,
          status: value.status,
        },
      ]),
    ),
    sleep_quality: nullableInteger(form.sleepQuality),
    soreness_level: nullableInteger(form.sorenessLevel),
    starter_plan_key: form.starterPlanKey || null,
    target_skills: [...form.targetSkills],
    training_locations: [...form.trainingLocations],
    weekly_session_goal: nullableInteger(form.weeklySessionGoal),
  }

  if (options.complete) {
    return { ...body, complete: true }
  }

  return body
}

function nullableInteger(value: string): number | null {
  if (!value.trim()) {
    return null
  }

  const parsed = Number(value)

  return Number.isFinite(parsed) ? Math.trunc(parsed) : null
}

function addNumberError(errors: OnboardingFieldErrors, key: string, value: string, min: number, max: number): void {
  if (!value.trim()) {
    errors[key] = `Enter a number from ${min} to ${max}.`

    return
  }

  const parsed = Number(value)

  if (!Number.isFinite(parsed) || parsed < min || parsed > max) {
    errors[key] = `Enter a number from ${min} to ${max}.`
  }
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
    pain_areas: 'painAreas',
    pain_level: 'painLevel',
    pain_notes: 'painNotes',
    preferred_session_minutes: 'preferredSessionMinutes',
    preferred_training_days: 'preferredTrainingDays',
    preferred_training_time: 'preferredTrainingTime',
    primary_goal: 'primaryGoal',
    readiness_rating: 'readinessRating',
    secondary_goals: 'secondaryGoals',
    sleep_quality: 'sleepQuality',
    soreness_level: 'sorenessLevel',
    starter_plan_key: 'starterPlanKey',
    target_skills: 'targetSkills',
    training_locations: 'trainingLocations',
    weekly_session_goal: 'weeklySessionGoal',
  }

  if (directMap[key]) {
    return directMap[key]
  }

  return key
    .replace('current_level_tests.', 'currentLevelTests.')
    .replace('push_ups.max_strict_reps', 'pushUpMaxReps')
    .replace('pull_ups.max_strict_reps', 'pullUpMaxReps')
    .replace('pull_ups.progression', 'pullUpProgression')
    .replace('squat.max_reps', 'squatMaxReps')
    .replace('squat.progression', 'squatProgression')
    .replace('hollow_hold_seconds', 'hollowHoldSeconds')
}

function isServerValidationBody(body: unknown): body is ServerValidationBody {
  return typeof body === 'object' && body !== null
}
