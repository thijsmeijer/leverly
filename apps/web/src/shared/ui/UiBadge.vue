<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    tone?: 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'inverse'
  }>(),
  {
    tone: 'neutral',
  },
)

const badgeClass = computed(() => [
  'inline-flex min-h-7 items-center rounded-control border px-2.5 py-1 text-xs font-semibold tracking-normal',
  {
    neutral: 'border-line-subtle bg-surface-muted text-ink-secondary',
    success: 'border-status-success/25 bg-status-success-soft text-status-success',
    warning: 'border-status-warning/25 bg-status-warning-soft text-status-warning',
    danger: 'border-status-danger/25 bg-status-danger-soft text-status-danger',
    info: 'border-accent-secondary/25 bg-accent-secondary-soft text-accent-secondary',
    inverse: 'border-white/15 bg-white/10 text-ink-inverse',
  }[props.tone],
])
</script>

<template>
  <span :class="badgeClass">
    <slot />
  </span>
</template>
