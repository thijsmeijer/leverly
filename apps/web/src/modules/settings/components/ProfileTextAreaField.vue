<script setup lang="ts">
const model = defineModel<string>({ required: true })

withDefaults(
  defineProps<{
    error?: string
    help?: string
    id: string
    label: string
    placeholder?: string
    rows?: number
  }>(),
  {
    error: undefined,
    help: undefined,
    placeholder: undefined,
    rows: 4,
  },
)
</script>

<template>
  <div class="space-y-2">
    <label :for="id" class="text-ink-primary block text-sm font-semibold">{{ label }}</label>
    <textarea
      :id="id"
      v-model="model"
      :aria-describedby="error ? `${id}-error` : help ? `${id}-help` : undefined"
      :aria-invalid="error ? 'true' : 'false'"
      class="border-line-subtle bg-surface-primary text-ink-primary placeholder:text-ink-muted focus:border-accent-primary focus:ring-accent-primary/25 rounded-control shadow-card-soft min-h-28 w-full resize-y border px-3.5 py-3 text-base transition outline-none focus:ring-4"
      :class="error ? 'border-status-danger focus:border-status-danger focus:ring-status-danger/20' : ''"
      :placeholder="placeholder"
      :rows="rows"
    />
    <p v-if="help && !error" :id="`${id}-help`" class="text-ink-muted text-sm leading-5">{{ help }}</p>
    <p v-if="error" :id="`${id}-error`" class="text-status-danger text-sm leading-5">{{ error }}</p>
  </div>
</template>
