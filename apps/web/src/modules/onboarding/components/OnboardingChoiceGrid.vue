<script setup lang="ts">
import type { ChoiceOption } from '../types'

const model = defineModel<string | string[]>({ required: true })

const props = withDefaults(
  defineProps<{
    columns?: 'compact' | 'relaxed'
    error?: string
    help?: string
    label: string
    maxSelections?: number
    multiple?: boolean
    name: string
    options: ChoiceOption[]
  }>(),
  {
    columns: 'relaxed',
    error: undefined,
    help: undefined,
    maxSelections: undefined,
    multiple: false,
  },
)

function isSelected(value: string): boolean {
  return Array.isArray(model.value) ? model.value.includes(value) : model.value === value
}

function selectValue(value: string): void {
  if (!props.multiple) {
    model.value = value

    return
  }

  const current = Array.isArray(model.value) ? model.value : []

  if (current.includes(value)) {
    model.value = current.filter((item) => item !== value)

    return
  }

  if (props.maxSelections && current.length >= props.maxSelections) {
    return
  }

  model.value = [...current, value]
}

function isDisabled(value: string): boolean {
  if (!props.multiple || !props.maxSelections || isSelected(value)) {
    return false
  }

  const current = Array.isArray(model.value) ? model.value : []

  return current.length >= props.maxSelections
}
</script>

<template>
  <fieldset class="space-y-3" :aria-describedby="error ? `${name}-error` : help ? `${name}-help` : undefined">
    <legend class="text-ink-primary text-sm font-semibold">{{ label }}</legend>
    <div
      class="grid gap-3"
      :class="
        columns === 'compact' ? 'grid-cols-[repeat(auto-fit,minmax(4.75rem,1fr))]' : 'sm:grid-cols-2 xl:grid-cols-3'
      "
    >
      <label
        v-for="option in options"
        :key="option.value"
        class="rounded-card focus-within:ring-accent-primary focus-within:ring-offset-surface-elevated min-h-24 border p-4 transition duration-200 focus-within:ring-2 focus-within:ring-offset-2"
        :class="
          isDisabled(option.value)
            ? 'border-line-subtle bg-surface-muted text-ink-muted cursor-not-allowed opacity-60'
            : isSelected(option.value)
              ? 'border-accent-primary bg-accent-primary-soft text-ink-primary shadow-card'
              : 'border-line-subtle bg-surface-elevated text-ink-secondary hover:border-line-strong hover:bg-surface-overlay hover:shadow-card-soft cursor-pointer'
        "
      >
        <input
          class="sr-only"
          :checked="isSelected(option.value)"
          :disabled="isDisabled(option.value)"
          :name="name"
          :type="multiple ? 'checkbox' : 'radio'"
          :value="option.value"
          @change="selectValue(option.value)"
        />
        <span class="flex h-full items-start justify-between gap-3">
          <span class="min-w-0">
            <span class="block text-sm font-semibold">{{ option.label }}</span>
            <span v-if="option.meta" class="text-accent-secondary mt-1 block text-xs font-semibold">
              {{ option.meta }}
            </span>
            <span v-if="option.description" class="text-ink-muted mt-2 block text-xs leading-5">
              {{ option.description }}
            </span>
          </span>
          <span
            class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full border"
            :class="isSelected(option.value) ? 'border-accent-primary bg-accent-primary' : 'border-line-strong'"
            aria-hidden="true"
          >
            <span v-if="isSelected(option.value)" class="h-2 w-2 rounded-full bg-white" />
          </span>
        </span>
      </label>
    </div>
    <p v-if="help && !error" :id="`${name}-help`" class="text-ink-muted text-sm leading-5">{{ help }}</p>
    <p v-if="error" :id="`${name}-error`" class="text-status-danger text-sm leading-5">{{ error }}</p>
  </fieldset>
</template>
