import type { RouteLocationRaw } from 'vue-router'

export type PlaceholderTone = 'success' | 'warning' | 'info' | 'neutral'

export type PlaceholderAction = {
  label: string
  to: RouteLocationRaw
}

export type PlaceholderMetric = {
  label: string
  value: string
  detail: string
  tone: PlaceholderTone
}

export type PlaceholderStep = {
  label: string
  detail: string
}

export type RoutePlaceholderContent = {
  eyebrow: string
  title: string
  description: string
  status: string
  primaryAction: PlaceholderAction
  secondaryAction: PlaceholderAction
  metrics: PlaceholderMetric[]
  steps: PlaceholderStep[]
}

declare module 'vue-router' {
  interface RouteMeta {
    allowIncompleteOnboarding?: boolean
    requiresAuth?: boolean
    section?: string
    title?: string
    placeholder?: RoutePlaceholderContent
  }
}
