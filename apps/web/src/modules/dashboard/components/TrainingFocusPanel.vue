<script setup lang="ts">
import { dashboardCopy } from '@/shared/brand'
import { UiCard, UiSectionHeader } from '@/shared/ui'
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
  <UiCard tone="soft" padding="md">
    <UiSectionHeader :title="dashboardCopy.focus.title" title-id="focus-heading" :metric="selectedFocus.metric" />
    <div class="mt-4 grid gap-2 sm:grid-cols-3" role="group" :aria-label="dashboardCopy.focus.ariaLabel">
      <button
        v-for="option in options"
        :key="option.id"
        class="rounded-control focus-visible:ring-accent-primary focus-visible:ring-offset-surface-elevated min-h-20 border px-4 py-3 text-left text-sm font-medium transition outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
        :class="
          activeFocus === option.id
            ? 'border-accent-primary bg-accent-primary shadow-control text-white'
            : 'border-line-subtle bg-surface-elevated text-ink-secondary shadow-card-soft hover:border-line-strong hover:text-ink-primary hover:-translate-y-0.5'
        "
        type="button"
        @click="$emit('select', option.id)"
      >
        <span class="block">{{ option.label }}</span>
        <span class="mt-1 block text-xs opacity-75">{{ option.metric }}</span>
      </button>
    </div>
    <p class="text-ink-secondary mt-4 text-sm leading-6">{{ selectedFocus.summary }}</p>
  </UiCard>
</template>
