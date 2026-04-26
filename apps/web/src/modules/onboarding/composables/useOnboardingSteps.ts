import { computed, type Ref } from 'vue'

import type { OnboardingStepId } from '../types'

export const onboardingSteps: Array<{ id: OnboardingStepId; label: string; short: string }> = [
  { id: 'goals', label: 'Goals', short: 'Targets' },
  { id: 'equipment', label: 'Equipment', short: 'Tools' },
  { id: 'level', label: 'Level tests', short: 'Tests' },
  { id: 'mobility', label: 'Positions', short: 'Mobility' },
  { id: 'availability', label: 'Availability', short: 'Schedule' },
  { id: 'readiness', label: 'Readiness', short: 'Safety' },
  { id: 'starter', label: 'Starter plan', short: 'Plan' },
]

export function useOnboardingSteps(activeStep: Ref<OnboardingStepId>) {
  const activeIndex = computed(() => onboardingSteps.findIndex((step) => step.id === activeStep.value))
  const progressPercent = computed(() => Math.round(((activeIndex.value + 1) / onboardingSteps.length) * 100))
  const canGoBack = computed(() => activeIndex.value > 0)
  const canContinue = computed(() => activeIndex.value < onboardingSteps.length - 1)

  return {
    activeIndex,
    canContinue,
    canGoBack,
    progressPercent,
    steps: onboardingSteps,
  }
}
