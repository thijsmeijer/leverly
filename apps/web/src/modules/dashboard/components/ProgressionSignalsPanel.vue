<script setup lang="ts">
import { UiBadge, UiCard, UiSectionHeader } from '@/shared/ui'
import type { ProgressionSignal } from '../data/dashboardPreview'

defineProps<{
  signals: ProgressionSignal[]
}>()
</script>

<template>
  <UiCard tone="inverse">
    <UiSectionHeader title="Progression signals" title-id="signals-heading" tone="inverse" />
    <dl class="mt-4 space-y-3">
      <div
        v-for="signal in signals"
        :key="signal.label"
        class="rounded-control flex items-center justify-between gap-4 border border-white/10 bg-white/5 px-3 py-3 shadow-sm shadow-black/15"
      >
        <dt class="text-ink-inverse/75 text-sm">{{ signal.label }}</dt>
        <dd class="text-right">
          <span class="text-ink-inverse block text-sm font-semibold">{{ signal.value }}</span>
          <UiBadge :tone="signal.toneVariant">{{ signal.tone }}</UiBadge>
        </dd>
      </div>
    </dl>
  </UiCard>
</template>
