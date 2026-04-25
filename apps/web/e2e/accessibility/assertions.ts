import AxeBuilder from '@axe-core/playwright'
import { expect, type Locator, type Page } from '@playwright/test'

type AxeImpact = 'minor' | 'moderate' | 'serious' | 'critical'

type AccessibilityScanOptions = {
  impacts?: AxeImpact[]
  root?: string
}

export async function expectNoAccessibilityViolations(page: Page, options: AccessibilityScanOptions = {}) {
  const impacts = options.impacts ?? ['critical']
  const builder = new AxeBuilder({ page })

  if (options.root) {
    builder.include(options.root)
  }

  const results = await builder.analyze()
  const violations = results.violations.filter((violation) => {
    return violation.impact !== null && impacts.includes(violation.impact as AxeImpact)
  })

  expect(violations).toEqual([])
}

export async function expectFocusVisible(locator: Locator) {
  await locator.focus()
  await expect(locator).toBeFocused()

  const hasVisibleFocus = await locator.evaluate((element) => {
    return element.matches(':focus-visible') || getComputedStyle(element).outlineStyle !== 'none'
  })

  expect(hasVisibleFocus).toBe(true)
}

export async function expectKeyboardCanReach(page: Page, locator: Locator, maxTabs = 20) {
  for (let index = 0; index < maxTabs; index += 1) {
    await page.keyboard.press('Tab')

    const isFocused = await locator.evaluate((element) => {
      return element === document.activeElement || element.contains(document.activeElement)
    })

    if (isFocused) {
      return
    }
  }

  throw new Error(`Expected keyboard focus to reach locator within ${maxTabs} Tab presses.`)
}
