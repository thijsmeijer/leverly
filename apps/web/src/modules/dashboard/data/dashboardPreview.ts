import { dashboardCopy } from '@/shared/brand'

export type TrainingFocus = 'today' | 'progressions' | 'recovery'

export type TrainingFocusOption = {
  id: TrainingFocus
  label: string
  summary: string
  metric: string
}

export type TrainingBlock = {
  name: string
  target: string
  detail: string
  status: string
  load: string
}

export type ProgressionSignal = {
  label: string
  value: string
  tone: string
  toneVariant: 'success' | 'warning' | 'danger' | 'info' | 'neutral'
}

export type ReadinessPoint = {
  label: string
  value: number
}

export const focusOptions: TrainingFocusOption[] = [
  {
    id: 'today',
    ...dashboardCopy.focus.options.today,
  },
  {
    id: 'progressions',
    ...dashboardCopy.focus.options.progressions,
  },
  {
    id: 'recovery',
    ...dashboardCopy.focus.options.recovery,
  },
]

export const trainingBlocks: TrainingBlock[] = [
  dashboardCopy.trainingBlocks.skill,
  dashboardCopy.trainingBlocks.strength,
  dashboardCopy.trainingBlocks.accessory,
]

export const progressionSignals: ProgressionSignal[] = [
  { ...dashboardCopy.progressionSignals.readiness, toneVariant: 'success' },
  { ...dashboardCopy.progressionSignals.formTrend, toneVariant: 'warning' },
  { ...dashboardCopy.progressionSignals.painSignal, toneVariant: 'success' },
]

export const readinessTrend: ReadinessPoint[] = [
  { label: 'Mon', value: 78 },
  { label: 'Tue', value: 82 },
  { label: 'Wed', value: 76 },
  { label: 'Thu', value: 84 },
  { label: 'Fri', value: 86 },
]
