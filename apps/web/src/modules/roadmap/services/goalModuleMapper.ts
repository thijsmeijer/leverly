export interface GoalModuleForm {
  readonly highestProgression: string
  readonly holdSeconds: string
  readonly loadUnit: string
  readonly loadValue: string
  readonly metricType: string
  readonly notes: string
  readonly quality: string
  readonly reps: string
}

type GoalModuleTransport = {
  readonly highest_progression?: string | null
  readonly hold_seconds?: number | null
  readonly load_unit?: string | null
  readonly load_value?: number | null
  readonly metric_type?: string | null
  readonly notes?: string | null
  readonly quality?: string | null
  readonly reps?: number | null
}

export const goalModuleKeys = ['inversion', 'pull_skill', 'push_compression', 'lower_body', 'lateral_chain'] as const

const goalModulesByGoal: Record<string, string[]> = {
  back_lever: ['pull_skill'],
  front_lever: ['pull_skill'],
  handstand: ['inversion'],
  handstand_push_up: ['inversion'],
  human_flag: ['lateral_chain', 'pull_skill'],
  l_sit: ['push_compression'],
  muscle_up: ['pull_skill'],
  nordic_curl: ['lower_body'],
  one_arm_pull_up: ['pull_skill'],
  pistol_squat: ['lower_body'],
  planche: ['push_compression'],
  press_to_handstand: ['push_compression'],
  v_sit: ['push_compression'],
  weighted_muscle_up: ['pull_skill'],
  weighted_pull_up: ['pull_skill'],
}

const defaultMetricTypes: Record<string, string> = {
  inversion: 'hold_seconds',
  lateral_chain: 'hold_seconds',
  lower_body: 'reps',
  pull_skill: 'reps',
  push_compression: 'hold_seconds',
}

export function requiredGoalModulesForGoal(goal: string): string[] {
  return [...(goalModulesByGoal[goal] ?? [])]
}

export function defaultGoalModules(): Record<string, GoalModuleForm> {
  return Object.fromEntries(goalModuleKeys.map((key) => [key, defaultGoalModule(key)]))
}

export function mapGoalModulesToForm(
  modules: Record<string, GoalModuleTransport> | undefined,
  requiredModules: readonly string[],
): Record<string, GoalModuleForm> {
  const defaults = defaultGoalModules()

  return Object.fromEntries(
    goalModuleKeys.map((key) => {
      const value = modules?.[key]

      if (!value && !requiredModules.includes(key)) {
        return [key, defaults[key]]
      }

      return [
        key,
        {
          highestProgression: value?.highest_progression ?? defaults[key].highestProgression,
          holdSeconds:
            value?.hold_seconds === null || value?.hold_seconds === undefined ? '' : String(value.hold_seconds),
          loadUnit: value?.load_unit ?? defaults[key].loadUnit,
          loadValue: value?.load_value === null || value?.load_value === undefined ? '' : String(value.load_value),
          metricType: value?.metric_type ?? defaults[key].metricType,
          notes: value?.notes ?? '',
          quality: value?.quality ?? defaults[key].quality,
          reps: value?.reps === null || value?.reps === undefined ? '' : String(value.reps),
        },
      ]
    }),
  )
}

export function serializeGoalModules(
  modules: Record<string, GoalModuleForm>,
  requiredModules: readonly string[],
): Record<string, GoalModuleTransport> {
  return Object.fromEntries(
    requiredModules.map((key) => {
      const value = modules[key] ?? defaultGoalModule(key)

      return [
        key,
        {
          highest_progression: value.highestProgression,
          metric_type: value.metricType,
          reps: nullableInteger(value.reps),
          hold_seconds: nullableInteger(value.holdSeconds),
          load_value: nullableNumber(value.loadValue),
          load_unit: value.loadUnit,
          quality: value.quality,
          notes: value.notes.trim() || null,
        },
      ]
    }),
  )
}

export function isGoalModuleTested(module: GoalModuleForm): boolean {
  if (!module.highestProgression || module.highestProgression === 'not_tested') {
    return false
  }

  if (module.metricType === 'reps') {
    return positiveNumber(module.reps)
  }

  if (module.metricType === 'hold_seconds') {
    return positiveNumber(module.holdSeconds)
  }

  if (module.metricType === 'load') {
    return positiveNumber(module.loadValue)
  }

  if (module.metricType === 'quality') {
    return module.quality !== 'unknown'
  }

  return false
}

function defaultGoalModule(key: string): GoalModuleForm {
  return {
    highestProgression: 'not_tested',
    holdSeconds: '',
    loadUnit: 'kg',
    loadValue: '',
    metricType: defaultMetricTypes[key] ?? 'quality',
    notes: '',
    quality: 'unknown',
    reps: '',
  }
}

function nullableInteger(value: string | number): number | null {
  const text = String(value).trim()
  const parsed = Number(text)

  return text === '' || !Number.isFinite(parsed) ? null : Math.trunc(parsed)
}

function nullableNumber(value: string | number): number | null {
  const text = String(value).trim()
  const parsed = Number(text)

  return text === '' || !Number.isFinite(parsed) ? null : parsed
}

function positiveNumber(value: string | number): boolean {
  const parsed = Number(String(value).trim())

  return Number.isFinite(parsed) && parsed > 0
}
