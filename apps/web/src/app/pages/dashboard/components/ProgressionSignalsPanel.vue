<script setup lang="ts">
import type { ProgressionSignal } from '../data/dashboardPreview'

defineProps<{
  signals: ProgressionSignal[]
}>()
</script>

<template>
  <div class="bg-lab-shell/95 shadow-lab-shell rounded-lg border border-white/10 p-5 text-stone-100">
    <h2 id="signals-heading" class="text-lg font-semibold tracking-normal">Progression signals</h2>
    <dl class="mt-4 space-y-3">
      <div
        v-for="signal in signals"
        :key="signal.label"
        class="flex items-center justify-between gap-4 rounded-lg border border-white/10 bg-white/5 px-3 py-3 shadow-sm shadow-black/15"
      >
        <dt class="text-sm text-stone-300">{{ signal.label }}</dt>
        <dd class="text-right">
          <span class="block text-sm font-semibold text-stone-50">{{ signal.value }}</span>
          <span class="block text-xs" :class="signal.color">{{ signal.tone }}</span>
        </dd>
      </div>
    </dl>
  </div>
</template>
