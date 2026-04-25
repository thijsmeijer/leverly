<script setup lang="ts">
defineProps<{
  message: string
}>()
</script>

<template>
  <div
    class="border-status-danger/30 bg-status-danger-soft text-ink-primary rounded-card border px-4 py-3 text-sm leading-6"
    role="alert"
  >
    {{ message }}
  </div>
</template>
