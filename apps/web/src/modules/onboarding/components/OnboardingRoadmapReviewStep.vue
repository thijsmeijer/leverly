<script setup lang="ts">
import { computed } from 'vue'

import { baseFocusOptions, mobilityCheckOptions, targetSkillOptions } from '../data/onboardingOptions'
import { useOnboardingStore } from '../stores/onboardingStore'

const onboarding = useOnboardingStore()

const primaryTargetLabel = computed(
  () =>
    targetSkillOptions.find((option) => option.value === onboarding.form.primaryTargetSkill)?.label ??
    'No primary roadmap yet',
)
const confidenceLabel = computed(() => `${capitalize(onboarding.form.roadmapSuggestions.confidence.level)} confidence`)
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
    primary: primaryTargetLabel.value,
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
const deferredReviewGoals = computed(() => {
  if (onboarding.form.roadmapSuggestions.deferredGoals.length) {
    return onboarding.form.roadmapSuggestions.deferredGoals
  }

  return onboarding.form.roadmapSuggestions.longTermTracks.map((track) => ({
    explanation: track.reason,
    label: track.label,
  }))
})

function humanizeProgression(value: string): string {
  const labels: Record<string, string> = {
    core_bodyline: 'Core bodyline',
    handstand_line: 'Handstand line',
    pull_capacity: 'Pull capacity',
    push_capacity: 'Push capacity',
    row_volume: 'Row volume',
    straight_arm_tolerance: 'Straight-arm tolerance',
  }

  return (
    labels[value] ??
    value
      .split('_')
      .filter(Boolean)
      .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
      .join(' ')
  )
}

function capitalize(value: string): string {
  return value ? value.charAt(0).toUpperCase() + value.slice(1) : 'Low'
}
</script>

<template>
  <div class="space-y-6">
    <section
      class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft grid gap-5 border p-5 lg:grid-cols-[minmax(0,1.4fr)_minmax(18rem,0.8fr)]"
    >
      <div>
        <p class="text-accent-primary text-xs font-semibold tracking-[0.16em] uppercase">Primary roadmap</p>
        <h3 class="text-ink-primary mt-2 text-2xl font-semibold">
          {{ onboarding.form.roadmapSuggestions.primaryGoal?.label ?? placementPreview.primary }}
        </h3>
        <p class="text-ink-secondary mt-3 max-w-2xl text-sm leading-6">
          {{
            onboarding.form.roadmapSuggestions.explanation.primaryNow ||
            onboarding.form.roadmapSuggestions.explanation.summary ||
            onboarding.form.roadmapSuggestions.summary
          }}
        </p>
      </div>
      <dl class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
        <div class="rounded-control bg-surface-elevated border-line-subtle border p-3">
          <dt class="text-ink-muted text-xs font-semibold">ETA range</dt>
          <dd class="text-ink-primary mt-1 text-sm font-semibold">
            {{ onboarding.form.roadmapSuggestions.etaRange.label }}
          </dd>
        </div>
        <div class="rounded-control bg-surface-elevated border-line-subtle border p-3">
          <dt class="text-ink-muted text-xs font-semibold">Confidence</dt>
          <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ confidenceLabel }}</dd>
        </div>
        <div class="rounded-control bg-surface-elevated border-line-subtle border p-3">
          <dt class="text-ink-muted text-xs font-semibold">Current node</dt>
          <dd class="text-ink-primary mt-1 text-sm font-semibold">
            {{ onboarding.form.roadmapSuggestions.currentProgressionNode.label }}
          </dd>
        </div>
      </dl>
    </section>

    <div class="grid gap-4 lg:grid-cols-3">
      <section class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4">
        <p class="text-ink-muted text-xs font-semibold">Secondary lane</p>
        <h3 class="text-ink-primary mt-2 text-base font-semibold">
          {{ onboarding.form.roadmapSuggestions.compatibleSecondaryGoal?.label ?? 'No secondary lane yet' }}
        </h3>
        <p class="text-ink-secondary mt-2 text-sm leading-6">
          {{
            onboarding.form.roadmapSuggestions.compatibleSecondaryGoal?.explanation ||
            'The first block can stay focused if there is no compatible support lane.'
          }}
        </p>
      </section>

      <section class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4">
        <p class="text-ink-muted text-xs font-semibold">Deferred for later</p>
        <h3 class="text-ink-primary mt-2 text-base font-semibold">
          {{ deferredReviewGoals[0]?.label ?? 'No deferred goals' }}
        </h3>
        <p class="text-ink-secondary mt-2 text-sm leading-6">
          {{ deferredReviewGoals[0]?.explanation || 'Nothing needs to be deferred from the current selection.' }}
        </p>
      </section>

      <section class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4">
        <p class="text-ink-muted text-xs font-semibold">First block</p>
        <h3 class="text-ink-primary mt-2 text-base font-semibold">
          {{ onboarding.form.roadmapSuggestions.currentBlockFocus.label }}
        </h3>
        <p class="text-ink-secondary mt-2 text-sm leading-6">
          {{
            onboarding.form.roadmapSuggestions.currentBlockFocus.shouldImprove.slice(0, 2).join(', ') ||
            placementPreview.focus
          }}
        </p>
      </section>
    </div>

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
        <h3 class="text-ink-primary text-base font-semibold">Current block focus</h3>
        <ul class="text-ink-secondary mt-3 grid gap-2 text-sm leading-6">
          <li v-for="item in onboarding.form.roadmapSuggestions.currentBlockFocus.focusAreas" :key="item">
            {{ humanizeProgression(item) }}
          </li>
        </ul>
        <p class="text-ink-muted mt-3 text-xs leading-5">
          {{ onboarding.form.roadmapSuggestions.currentBlockFocus.retestCadence.join(' ') }}
        </p>
      </div>
    </section>

    <div class="grid gap-3 lg:grid-cols-3">
      <div class="border-line-subtle bg-surface-primary rounded-card border p-4">
        <p class="text-ink-muted text-xs font-semibold">Baseline signal</p>
        <p class="text-ink-primary mt-2 text-sm font-semibold">{{ placementPreview.tests }}</p>
      </div>
      <div class="border-line-subtle bg-surface-primary rounded-card border p-4">
        <p class="text-ink-muted text-xs font-semibold">Base support</p>
        <p class="text-ink-primary mt-2 text-sm font-semibold">{{ placementPreview.focus }}</p>
      </div>
      <div class="border-line-subtle bg-surface-primary rounded-card border p-4">
        <p class="text-ink-muted text-xs font-semibold">Safety limiters</p>
        <p class="text-ink-primary mt-2 text-sm font-semibold">{{ placementPreview.mobility }}</p>
      </div>
    </div>
  </div>
</template>
