import { test } from '@playwright/test'

import { runAccessibilityScenario } from '../accessibility/scenarioRunner'
import { accessibilityScenarios } from '../accessibility/scenarios'

for (const scenario of accessibilityScenarios) {
  test(`${scenario.name} has no critical accessibility violations @a11y`, async ({ page }) => {
    await runAccessibilityScenario(page, scenario)
  })
}
