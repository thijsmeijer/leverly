import { computed, type Ref } from 'vue'

import type { OnboardingStepId } from '../types'

export const onboardingSteps: Array<{ id: OnboardingStepId; label: string; short: string }> = [
  { id: 'context', label: 'Personal', short: 'You' },
  { id: 'equipment', label: 'Equipment', short: 'Tools' },
  { id: 'mobility', label: 'Pain + mobility', short: 'Limits' },
  { id: 'level', label: 'Baseline', short: 'Tests' },
  { id: 'goal', label: 'Goal', short: 'Goal' },
  { id: 'modules', label: 'Skill detail', short: 'Skill' },
  { id: 'availability', label: 'Availability', short: 'Week' },
  { id: 'review', label: 'Review', short: 'Map' },
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
