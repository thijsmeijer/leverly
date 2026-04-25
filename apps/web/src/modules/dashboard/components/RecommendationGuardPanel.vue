<script setup lang="ts">
import { recommendationGuardCopy } from '@/shared/brand'
import { UiCard, UiProgress } from '@/shared/ui'
</script>

<template>
  <UiCard>
    <h2 class="text-ink-primary text-lg font-semibold tracking-normal">{{ recommendationGuardCopy.title }}</h2>
    <p class="text-ink-secondary mt-3 text-sm leading-6">
      {{ recommendationGuardCopy.description }}
    </p>
    <UiProgress class="mt-4" :label="recommendationGuardCopy.confidenceLabel" :value="80" show-value tone="success" />
  </UiCard>
</template>
