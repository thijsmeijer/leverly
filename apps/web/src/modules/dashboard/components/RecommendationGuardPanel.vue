<script setup lang="ts">
import { UiCard, UiProgress } from '@/shared/ui'
</script>

<template>
  <UiCard>
    <h2 class="text-ink-primary text-lg font-semibold tracking-normal">Recommendation guard</h2>
    <p class="text-ink-secondary mt-3 text-sm leading-6">
      No progression is suggested until hold quality, pain, and readiness stay inside safe bounds.
    </p>
    <UiProgress class="mt-4" label="Guard confidence" :value="80" show-value tone="success" />
  </UiCard>
</template>
