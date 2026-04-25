<script setup lang="ts">
import { UiButton } from '@/shared/ui'

defineEmits<{
  start: []
}>()
</script>

<template>
  <header class="border-line-subtle flex flex-col gap-6 border-b pb-6 md:flex-row md:items-center md:justify-between">
    <div>
      <p class="text-accent-primary text-sm font-semibold tracking-normal uppercase">Premium training lab</p>
      <h1
        class="text-ink-primary mt-2 max-w-3xl text-3xl leading-tight font-semibold tracking-normal sm:text-4xl lg:text-5xl"
      >
        Today&apos;s work is ready to log.
      </h1>
      <p class="text-ink-secondary mt-3 max-w-2xl text-base leading-7">
        Calisthenics progress, readiness, and recommendation gates in one focused workspace.
      </p>
    </div>
    <UiButton size="lg" @click="$emit('start')"> Start workout </UiButton>
  </header>
</template>
