<script setup lang="ts">
defineProps<{
  description: string
  eyebrow: string
  title: string
}>()
</script>

<template>
  <section class="border-line-subtle bg-surface-elevated shadow-card rounded-card border p-5 sm:p-7">
    <div class="mb-6 max-w-3xl">
      <p class="text-accent-primary text-xs font-semibold tracking-[0.18em] uppercase">{{ eyebrow }}</p>
      <h2 class="text-ink-primary mt-3 text-2xl font-semibold tracking-normal">{{ title }}</h2>
      <p class="text-ink-secondary mt-3 text-base leading-7">{{ description }}</p>
    </div>
    <slot />
  </section>
</template>
