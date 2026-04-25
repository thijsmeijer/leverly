import type { Page } from '@playwright/test'

import { expectNoAccessibilityViolations } from './assertions'
import type { AccessibilityScenario } from './scenarios'

export async function runAccessibilityScenario(page: Page, scenario: AccessibilityScenario) {
  if (scenario.setup) {
    await scenario.setup(page)
  }

  await page.goto(scenario.path)

  await expectNoAccessibilityViolations(page, {
    root: scenario.root,
  })
}
