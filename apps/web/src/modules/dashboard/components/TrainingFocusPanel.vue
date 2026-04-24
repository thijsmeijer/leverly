<script setup lang="ts">
import type { TrainingFocus, TrainingFocusOption } from '../data/dashboardPreview'

defineProps<{
  activeFocus: TrainingFocus
  options: TrainingFocusOption[]
  selectedFocus: TrainingFocusOption
}>()

defineEmits<{
  select: [focus: TrainingFocus]
}>()
</script>

<template>
  <div class="bg-lab-card/90 shadow-lab-panel rounded-lg border border-white/60 p-4 backdrop-blur sm:p-5">
    <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
      <h2 id="focus-heading" class="text-lg font-semibold tracking-normal">Training focus</h2>
      <p class="text-lab-soft text-sm font-medium">{{ selectedFocus.metric }}</p>
    </div>
    <div class="mt-4 grid gap-2 sm:grid-cols-3" role="group" aria-label="Training focus">
      <button
        v-for="option in options"
        :key="option.id"
        class="focus-visible:ring-offset-lab-card rounded-lg border px-4 py-3 text-left text-sm font-medium transition outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2"
        :class="
          activeFocus === option.id
            ? 'bg-lab-emerald shadow-lab-control border-emerald-800 text-white'
            : 'bg-lab-card-high/55 text-lab-copy shadow-lab-panel-soft hover:bg-lab-card-high border-white/70 hover:-translate-y-0.5 hover:border-emerald-800/30'
        "
        type="button"
        @click="$emit('select', option.id)"
      >
        <span class="block">{{ option.label }}</span>
        <span class="mt-1 block text-xs opacity-75">{{ option.metric }}</span>
      </button>
    </div>
    <p class="text-lab-muted mt-4 text-sm leading-6">{{ selectedFocus.summary }}</p>
  </div>
</template>
