import type { RouteLocationRaw } from 'vue-router'

export type AppNavigationItem = {
  routeName: string
  label: string
  shortLabel: string
  description: string
  group: 'train' | 'library' | 'review' | 'settings'
  to: RouteLocationRaw
  matchNames: string[]
}

export const appNavigationItems: AppNavigationItem[] = [
  {
    routeName: 'dashboard',
    label: 'Dashboard',
    shortLabel: 'Home',
    description: 'Readiness, focus, and guardrails',
    group: 'train',
    to: { name: 'dashboard' },
    matchNames: ['dashboard'],
  },
  {
    routeName: 'today',
    label: 'Today',
    shortLabel: 'Today',
    description: 'Current session and logging path',
    group: 'train',
    to: { name: 'today' },
    matchNames: ['today'],
  },
  {
    routeName: 'workouts',
    label: 'Workouts',
    shortLabel: 'Logs',
    description: 'Sessions, blocks, and set history',
    group: 'train',
    to: { name: 'workouts' },
    matchNames: ['workouts', 'workout-detail'],
  },
  {
    routeName: 'programs',
    label: 'Programs',
    shortLabel: 'Plans',
    description: 'Training blocks and weekly structure',
    group: 'train',
    to: { name: 'programs' },
    matchNames: ['programs', 'program-detail'],
  },
  {
    routeName: 'exercises',
    label: 'Exercises',
    shortLabel: 'Moves',
    description: 'Calisthenics library and movement filters',
    group: 'library',
    to: { name: 'exercises' },
    matchNames: ['exercises', 'exercise-detail'],
  },
  {
    routeName: 'progressions',
    label: 'Progressions',
    shortLabel: 'Paths',
    description: 'Skill families, gates, and unlock evidence',
    group: 'library',
    to: { name: 'progressions' },
    matchNames: ['progressions', 'progression-detail'],
  },
  {
    routeName: 'insights',
    label: 'Insights',
    shortLabel: 'Review',
    description: 'Trends, readiness, and weekly review',
    group: 'review',
    to: { name: 'insights' },
    matchNames: ['insights'],
  },
  {
    routeName: 'settings-profile',
    label: 'Profile',
    shortLabel: 'Settings',
    description: 'Athlete context and preferences',
    group: 'settings',
    to: { name: 'settings-profile' },
    matchNames: ['settings-profile', 'settings-equipment', 'settings-export'],
  },
]

export const mobileNavigationItems = appNavigationItems.filter((item) =>
  ['today', 'workouts', 'dashboard', 'exercises', 'settings-profile'].includes(item.routeName),
)

export const navigationGroupLabels: Record<AppNavigationItem['group'], string> = {
  train: 'Train',
  library: 'Library',
  review: 'Review',
  settings: 'Settings',
}
