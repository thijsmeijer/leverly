<script setup lang="ts">
import type { ChoiceOption } from '../types'

const model = defineModel<string | string[]>({ required: true })

const props = withDefaults(
  defineProps<{
    columns?: 'compact' | 'wide'
    error?: string
    help?: string
    label: string
    multiple?: boolean
    name: string
    options: ChoiceOption[]
  }>(),
  {
    columns: 'wide',
    error: undefined,
    help: undefined,
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

  model.value = current.includes(value) ? current.filter((item) => item !== value) : [...current, value]
}
</script>

<template>
  <fieldset class="space-y-3" :aria-describedby="error ? `${name}-error` : help ? `${name}-help` : undefined">
    <legend class="text-ink-primary text-sm font-semibold">{{ label }}</legend>
    <div
      class="grid gap-3"
      :class="columns === 'compact' ? 'grid-cols-2 sm:grid-cols-4 lg:grid-cols-7' : 'sm:grid-cols-2 xl:grid-cols-3'"
    >
      <label
        v-for="option in options"
        :key="option.value"
        class="rounded-control focus-within:ring-accent-primary focus-within:ring-offset-surface-elevated border p-3 transition duration-200 focus-within:ring-2 focus-within:ring-offset-2"
        :class="
          isSelected(option.value)
            ? 'border-accent-primary bg-accent-primary-soft text-ink-primary shadow-card-soft'
            : 'border-line-subtle bg-surface-primary text-ink-secondary hover:border-line-strong hover:bg-surface-overlay'
        "
      >
        <input
          class="sr-only"
          :checked="isSelected(option.value)"
          :name="name"
          :type="multiple ? 'checkbox' : 'radio'"
          :value="option.value"
          @change="selectValue(option.value)"
        />
        <span class="flex min-h-10 items-center gap-3">
          <span
            class="border-line-strong flex h-5 w-5 shrink-0 items-center justify-center rounded-full border"
            :class="isSelected(option.value) ? 'border-accent-primary bg-accent-primary' : 'bg-surface-elevated'"
            aria-hidden="true"
          >
            <span v-if="isSelected(option.value)" class="bg-ink-inverse h-2 w-2 rounded-full" />
          </span>
          <span>
            <span class="block text-sm font-semibold">{{ option.label }}</span>
            <span v-if="option.description" class="text-ink-muted mt-1 block text-xs leading-5">
              {{ option.description }}
            </span>
          </span>
        </span>
      </label>
    </div>
    <p v-if="help && !error" :id="`${name}-help`" class="text-ink-muted text-sm leading-5">{{ help }}</p>
    <p v-if="error" :id="`${name}-error`" class="text-status-danger text-sm leading-5">{{ error }}</p>
  </fieldset>
</template>
