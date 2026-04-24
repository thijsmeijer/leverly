import AxeBuilder from '@axe-core/playwright'
import { expect, test } from '@playwright/test'

test('dashboard has no critical accessibility violations @a11y', async ({ page }) => {
  await page.goto('/app/dashboard')

  const results = await new AxeBuilder({ page }).analyze()
  const criticalViolations = results.violations.filter((violation) => violation.impact === 'critical')

  expect(criticalViolations).toEqual([])
})
