<script setup lang="ts">
import { UiBadge, UiCard } from '@/shared/ui'
import type { TrainingBlock } from '../data/dashboardPreview'

defineProps<{
  blocks: TrainingBlock[]
}>()
</script>

<template>
  <div class="grid gap-4 md:grid-cols-3">
    <UiCard v-for="block in blocks" :key="block.name" as="article" class="transition hover:-translate-y-0.5">
      <div class="flex items-start justify-between gap-3">
        <p class="text-accent-primary text-sm font-semibold">{{ block.name }}</p>
        <UiBadge tone="info">{{ block.load }}</UiBadge>
      </div>
      <h3 class="text-ink-primary mt-3 text-lg font-semibold tracking-normal">{{ block.target }}</h3>
      <p class="text-ink-secondary mt-3 text-sm leading-6">{{ block.detail }}</p>
      <UiBadge class="mt-4" tone="warning">{{ block.status }}</UiBadge>
    </UiCard>
  </div>
</template>
