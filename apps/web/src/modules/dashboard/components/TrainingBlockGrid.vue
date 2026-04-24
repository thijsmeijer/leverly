<script setup lang="ts">
import type { TrainingBlock } from '../data/dashboardPreview'

defineProps<{
  blocks: TrainingBlock[]
}>()
</script>

<template>
  <div class="grid gap-4 md:grid-cols-3">
    <article
      v-for="block in blocks"
      :key="block.name"
      class="bg-lab-card/90 shadow-lab-panel hover:bg-lab-card-high rounded-lg border border-white/60 p-5 transition hover:-translate-y-0.5"
    >
      <div class="flex items-start justify-between gap-3">
        <p class="text-sm font-semibold text-emerald-800">{{ block.name }}</p>
        <span class="bg-lab-surface rounded-lg px-2.5 py-1 text-xs font-semibold text-stone-100 shadow-sm">
          {{ block.load }}
        </span>
      </div>
      <h3 class="text-lab-shell mt-3 text-lg font-semibold tracking-normal">{{ block.target }}</h3>
      <p class="text-lab-muted mt-3 text-sm leading-6">{{ block.detail }}</p>
      <p
        class="text-lab-amber mt-4 rounded-lg border border-amber-700/15 bg-amber-100/70 px-3 py-2 text-sm font-medium shadow-sm shadow-amber-950/5"
      >
        {{ block.status }}
      </p>
    </article>
  </div>
</template>
