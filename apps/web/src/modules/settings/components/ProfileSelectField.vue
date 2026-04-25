<script setup lang="ts">
import type { ChoiceOption } from '../types'

const model = defineModel<string>({ required: true })

withDefaults(
  defineProps<{
    error?: string
    help?: string
    id: string
    label: string
    options: ChoiceOption[]
  }>(),
  {
    error: undefined,
    help: undefined,
  },
)
</script>

<template>
  <div class="space-y-2">
    <label :for="id" class="text-ink-primary block text-sm font-semibold">{{ label }}</label>
    <select
      :id="id"
      v-model="model"
      :aria-describedby="error ? `${id}-error` : help ? `${id}-help` : undefined"
      :aria-invalid="error ? 'true' : 'false'"
      class="border-line-subtle bg-surface-primary text-ink-primary focus:border-accent-primary focus:ring-accent-primary/25 rounded-control shadow-card-soft min-h-12 w-full border px-3.5 text-base transition outline-none focus:ring-4"
      :class="error ? 'border-status-danger focus:border-status-danger focus:ring-status-danger/20' : ''"
    >
      <option v-for="option in options" :key="option.value" :value="option.value">
        {{ option.label }}
      </option>
    </select>
    <p v-if="help && !error" :id="`${id}-help`" class="text-ink-muted text-sm leading-5">{{ help }}</p>
    <p v-if="error" :id="`${id}-error`" class="text-status-danger text-sm leading-5">{{ error }}</p>
  </div>
</template>
