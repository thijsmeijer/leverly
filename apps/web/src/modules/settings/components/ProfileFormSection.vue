<script setup lang="ts">
withDefaults(
  defineProps<{
    description: string
    eyebrow?: string
    title: string
  }>(),
  {
    eyebrow: undefined,
  },
)
</script>

<template>
  <section class="border-line-subtle bg-surface-elevated shadow-card rounded-card border p-5 sm:p-6">
    <div class="grid gap-5 lg:grid-cols-[minmax(12rem,18rem)_minmax(0,1fr)]">
      <div>
        <p v-if="eyebrow" class="text-accent-primary text-xs font-semibold tracking-[0.18em] uppercase">
          {{ eyebrow }}
        </p>
        <h2 class="text-ink-primary mt-2 text-lg font-semibold tracking-normal">{{ title }}</h2>
        <p class="text-ink-secondary mt-2 text-sm leading-6">{{ description }}</p>
      </div>

      <div class="min-w-0">
        <slot />
      </div>
    </div>
  </section>
</template>
