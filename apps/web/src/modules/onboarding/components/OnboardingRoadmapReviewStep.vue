<script setup lang="ts">
import { computed } from 'vue'

import { baseFocusOptions, mobilityCheckOptions } from '../data/onboardingOptions'
import { useOnboardingStore } from '../stores/onboardingStore'
import OnboardingPortfolioPreview from './OnboardingPortfolioPreview.vue'

const onboarding = useOnboardingStore()

const placementPreview = computed(() => {
  const push = `${onboarding.form.currentLevelTests.pushUpMaxReps || 0} push-ups`
  const pull = `${onboarding.form.currentLevelTests.pullUpMaxReps || 0} pull-ups`
  const dip = `${onboarding.form.currentLevelTests.dipMaxReps || 0} dips`
  const mobilityFlags = Object.entries(onboarding.form.mobilityChecks)
    .filter(([, status]) => ['limited', 'blocked', 'painful'].includes(status))
    .map(([key]) => mobilityCheckOptions.find((option) => option.value === key)?.label ?? key)

  return {
    focus: onboarding.form.baseFocusAreas.length
      ? onboarding.form.baseFocusAreas
          .map((value) => baseFocusOptions.find((option) => option.value === value)?.label ?? value)
          .slice(0, 3)
          .join(', ')
      : 'Base focus not selected',
    mobility: mobilityFlags.length ? mobilityFlags.slice(0, 3).join(', ') : 'No major position blockers marked',
    tests: `${push}, ${pull}, ${dip}`,
  }
})
const reviewBlockers = computed(() => {
  const blockers = onboarding.form.roadmapSuggestions.blockers.map((blocker) => ({
    key: blocker.key,
    label: blocker.label,
    message: blocker.message,
  }))
  const bottlenecks = onboarding.form.roadmapSuggestions.domainBottlenecks.map((bottleneck) => ({
    key: bottleneck.domain,
    label: bottleneck.label,
    message: bottleneck.reason,
  }))

  return [...blockers, ...bottlenecks].slice(0, 4)
})
</script>

<template>
  <div class="space-y-6">
    <OnboardingPortfolioPreview :portfolio="onboarding.form.roadmapPortfolio" />

    <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)]">
      <div class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4">
        <h3 class="text-ink-primary text-base font-semibold">Top blockers</h3>
        <ul v-if="reviewBlockers.length" class="mt-3 grid gap-3">
          <li
            v-for="blocker in reviewBlockers"
            :key="blocker.key"
            class="rounded-control bg-surface-elevated border-line-subtle border p-3"
          >
            <p class="text-ink-primary text-sm font-semibold">{{ blocker.label }}</p>
            <p class="text-ink-secondary mt-1 text-sm leading-5">{{ blocker.message }}</p>
          </li>
        </ul>
        <p v-else class="text-ink-secondary mt-2 text-sm leading-6">No major blockers are marked right now.</p>
      </div>

      <div class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4">
        <h3 class="text-ink-primary text-base font-semibold">Baseline summary</h3>
        <dl class="mt-3 grid gap-3">
          <div class="rounded-control bg-surface-elevated border-line-subtle border p-3">
            <dt class="text-ink-muted text-xs font-semibold">Baseline signal</dt>
            <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ placementPreview.tests }}</dd>
          </div>
          <div class="rounded-control bg-surface-elevated border-line-subtle border p-3">
            <dt class="text-ink-muted text-xs font-semibold">Base support</dt>
            <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ placementPreview.focus }}</dd>
          </div>
          <div class="rounded-control bg-surface-elevated border-line-subtle border p-3">
            <dt class="text-ink-muted text-xs font-semibold">Safety limiters</dt>
            <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ placementPreview.mobility }}</dd>
          </div>
        </dl>
      </div>
    </section>
  </div>
</template>
