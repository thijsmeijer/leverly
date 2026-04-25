import { describe, expect, it } from 'vitest'

import { hasMedicalDiagnosisLanguage, leverlyBrand, leverlyTonePrinciples } from './leverlyBrand'
import { authCopy, copyTone, dashboardCopy, recommendationGuardCopy, sharedCopy } from './leverlyCopy'

const allCopy = JSON.stringify({
  authCopy,
  copyTone,
  dashboardCopy,
  recommendationGuardCopy,
  sharedCopy,
})

describe('Leverly brand copy', () => {
  it('keeps the approved product name and tagline available from one source', () => {
    expect(leverlyBrand.productName).toBe('Leverly')
    expect(leverlyBrand.tagline).toBe('intelligent progression for bodyweight strength.')
    expect(dashboardCopy.brand.name).toBe(leverlyBrand.productName)
    expect(dashboardCopy.hero.eyebrow).toBe(leverlyBrand.tagline)
  })

  it('defines a precise, calm, and actionable copy tone', () => {
    expect(leverlyTonePrinciples.map((principle) => principle.name)).toEqual([
      'Precise',
      'Calm',
      'Actionable',
      'Careful',
    ])
    expect(copyTone.summary).toContain('precise')
    expect(copyTone.summary).toContain('calm')
    expect(copyTone.summary).toContain('actionable')
  })

  it('keeps coaching and empty-state copy away from medical labels', () => {
    expect(hasMedicalDiagnosisLanguage(allCopy)).toBe(false)
    expect(recommendationGuardCopy.description).toContain('before suggesting a harder lever')
    expect(sharedCopy.emptyStates.recommendation).toContain("Log today's work")
  })

  it('uses calm action-oriented error copy', () => {
    expect(sharedCopy.errors.apiUnavailable).toContain('Refresh')
    expect(sharedCopy.errors.sessionExpired).toContain('Sign in again')
    expect(sharedCopy.errors.notFound).toContain('Return to the dashboard')
  })
})
