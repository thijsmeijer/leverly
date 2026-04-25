import type { Page } from '@playwright/test'

export type AccessibilityScenario = {
  name: string
  path: string
  root?: string
  setup?: (page: Page) => Promise<void>
}

export const accessibilityScenarios: AccessibilityScenario[] = [
  {
    name: 'dashboard',
    path: '/app/dashboard',
  },
]
