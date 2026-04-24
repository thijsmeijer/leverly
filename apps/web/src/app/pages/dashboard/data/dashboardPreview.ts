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
  color: string
}

export type ReadinessPoint = {
  label: string
  value: number
}

export const focusOptions: TrainingFocusOption[] = [
  {
    id: 'today',
    label: 'Today',
    summary: 'Four focused blocks with one primary pull target.',
    metric: '42 min',
  },
  {
    id: 'progressions',
    label: 'Progressions',
    summary: 'Front lever and dip paths are ready for the next evidence check.',
    metric: '2 gates',
  },
  {
    id: 'recovery',
    label: 'Recovery',
    summary: 'Readiness is steady; keep pain and form gates visible.',
    metric: '86%',
  },
]

export const trainingBlocks: TrainingBlock[] = [
  {
    name: 'Skill',
    target: 'Tuck front lever holds',
    detail: '5 x 12s with clean scapular position',
    status: 'Gate: form first',
    load: 'RPE 7',
  },
  {
    name: 'Strength',
    target: 'Weighted pull-ups',
    detail: '4 x 5 at controlled tempo',
    status: 'Ready to log',
    load: '+12.5 kg',
  },
  {
    name: 'Accessory',
    target: 'Ring rows',
    detail: '3 x 10 with full range',
    status: 'Balance pull volume',
    load: 'Volume',
  },
]

export const progressionSignals: ProgressionSignal[] = [
  { label: 'Readiness', value: 'Good', tone: 'Stable', color: 'text-emerald-300' },
  { label: 'Form trend', value: '4/5', tone: 'Progressing', color: 'text-amber-300' },
  { label: 'Pain signal', value: '0/10', tone: 'Clear', color: 'text-emerald-300' },
]

export const readinessTrend: ReadinessPoint[] = [
  { label: 'Mon', value: 78 },
  { label: 'Tue', value: 82 },
  { label: 'Wed', value: 76 },
  { label: 'Thu', value: 84 },
  { label: 'Fri', value: 86 },
]
