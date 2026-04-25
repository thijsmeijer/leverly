<script setup lang="ts">
withDefaults(
  defineProps<{
    description?: string
    eyebrow?: string
    metric?: string
    tone?: 'default' | 'inverse'
    title: string
    titleId?: string
  }>(),
  {
    description: undefined,
    eyebrow: undefined,
    metric: undefined,
    tone: 'default',
    titleId: undefined,
  },
)
</script>

<template>
  <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
    <div>
      <p
        v-if="eyebrow"
        class="text-sm font-semibold uppercase"
        :class="tone === 'inverse' ? 'text-accent-primary-strong' : 'text-accent-primary'"
      >
        {{ eyebrow }}
      </p>
      <h2
        :id="titleId"
        class="mt-1 text-xl leading-tight font-semibold tracking-normal"
        :class="tone === 'inverse' ? 'text-ink-inverse' : 'text-ink-primary'"
      >
        {{ title }}
      </h2>
      <p
        v-if="description"
        class="mt-2 max-w-2xl text-sm leading-6"
        :class="tone === 'inverse' ? 'text-ink-inverse/75' : 'text-ink-secondary'"
      >
        {{ description }}
      </p>
    </div>
    <p
      v-if="metric"
      class="text-sm font-semibold"
      :class="tone === 'inverse' ? 'text-ink-inverse/70' : 'text-ink-muted'"
    >
      {{ metric }}
    </p>
  </div>
</template>
