<script setup lang="ts">
withDefaults(
  defineProps<{
    message: string
    tone?: 'danger' | 'info' | 'success' | 'warning'
  }>(),
  {
    tone: 'info',
  },
)
</script>

<template>
  <div
    class="rounded-card border px-4 py-3 text-sm leading-6"
    :class="{
      'border-status-danger/30 bg-status-danger-soft text-ink-primary': tone === 'danger',
      'border-status-success/30 bg-status-success-soft text-ink-primary': tone === 'success',
      'border-status-warning/30 bg-status-warning-soft text-ink-primary': tone === 'warning',
      'border-line-subtle bg-accent-secondary-soft text-ink-primary': tone === 'info',
    }"
    :role="tone === 'danger' ? 'alert' : 'status'"
    aria-live="polite"
  >
    {{ message }}
  </div>
</template>
