import { leverlyBrand, leverlyTonePrinciples } from './leverlyBrand'

export const copyTone = {
  principles: leverlyTonePrinciples,
  summary:
    'Leverly copy should stay precise about training signals, calm in risk states, and actionable when the athlete needs a next step.',
} as const

export const sharedCopy = {
  actions: {
    startWorkout: 'Start workout',
    refresh: 'Refresh',
    refreshing: 'Refreshing',
  },
  emptyStates: {
    recommendation:
      "No harder progression yet. Log today's work and keep form, pain, and readiness evidence visible before changing leverage.",
    trainingHistory: "No logged sessions yet. Start with today's plan so Leverly can build a useful progression trail.",
  },
  errors: {
    apiUnavailable: 'Connection is not ready. Refresh when local services are running.',
    sessionExpired: 'Your session expired. Sign in again to continue with your training workspace.',
    notFound: 'That screen is not available. Return to the dashboard and choose the next training step from there.',
  },
} as const

export const authCopy = {
  login: {
    eyebrow: leverlyBrand.productName,
    title: 'Sign in to Leverly',
    description: 'Continue to your training workspace with logging, readiness, and progression guidance kept together.',
    redirectPrefix: 'After signing in, continue to',
  },
} as const

export const dashboardCopy = {
  brand: {
    name: leverlyBrand.productName,
    tagline: leverlyBrand.tagline,
  },
  hero: {
    eyebrow: leverlyBrand.tagline,
    title: "Today's training is ready to log.",
    description: 'Track holds, reps, readiness, and progression gates in one focused workspace.',
    action: sharedCopy.actions.startWorkout,
  },
  navigation: {
    ariaLabel: 'Application navigation',
    primaryAriaLabel: 'Primary',
    dashboard: 'Dashboard',
    today: 'Today',
    progressions: 'Progressions',
    startShort: 'Start',
  },
  nextTarget: {
    label: 'Next target',
    description: 'Collect form and readiness evidence before increasing leverage.',
  },
  focus: {
    title: 'Training focus',
    ariaLabel: 'Training focus',
    options: {
      today: {
        label: 'Today',
        summary: 'Four focused blocks with one primary pull target.',
        metric: '42 min',
      },
      progressions: {
        label: 'Progressions',
        summary: 'Front lever and dip paths are ready for an evidence check.',
        metric: '2 gates',
      },
      recovery: {
        label: 'Recovery',
        summary: 'Readiness is steady; keep pain and form gates visible before adding difficulty.',
        metric: '86%',
      },
    },
  },
  trainingBlocks: {
    skill: {
      name: 'Skill',
      target: 'Tuck front lever holds',
      detail: '5 x 12s with clean scapular position',
      status: 'Gate: form first',
      load: 'RPE 7',
    },
    strength: {
      name: 'Strength',
      target: 'Weighted pull-ups',
      detail: '4 x 5 at controlled tempo',
      status: 'Ready to log',
      load: '+12.5 kg',
    },
    accessory: {
      name: 'Accessory',
      target: 'Ring rows',
      detail: '3 x 10 with full range',
      status: 'Balance pull volume',
      load: 'Volume',
    },
  },
  progressionSignals: {
    title: 'Progression signals',
    readiness: {
      label: 'Readiness',
      value: 'Good',
      tone: 'Stable',
    },
    formTrend: {
      label: 'Form trend',
      value: '4/5',
      tone: 'Progressing',
    },
    painSignal: {
      label: 'Pain signal',
      value: '0/10',
      tone: 'Clear',
    },
  },
  readinessTrend: {
    title: 'Readiness trend',
    summary: 'Readiness stayed within a productive range this week, with Friday currently strongest at 86.',
    valueLabel: 'Readiness score',
  },
  connectionStatus: {
    onlineLabel: 'Training API online',
    onlineDetail: (version: string) => `Connected to ${version}. Live training data can sync when flows are added.`,
    unavailableLabel: 'Connection not ready',
    unavailableDetail: sharedCopy.errors.apiUnavailable,
  },
} as const

export const recommendationGuardCopy = {
  title: 'Progression guard',
  description:
    'Leverly waits for hold quality, pain, and readiness to stay inside safe bounds before suggesting a harder lever.',
  confidenceLabel: 'Guard confidence',
} as const
