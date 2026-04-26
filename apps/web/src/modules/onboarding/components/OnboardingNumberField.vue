<script setup lang="ts">
const model = defineModel<string>({ required: true })

function onInput(event: unknown): void {
  const target = (event as { target?: { value?: unknown } }).target

  if (typeof target?.value === 'string') {
    model.value = target.value
  }
}

withDefaults(
  defineProps<{
    error?: string
    help?: string
    id: string
    label: string
    max?: number
    min?: number
    placeholder?: string
    suffix?: string
  }>(),
  {
    error: undefined,
    help: undefined,
    max: undefined,
    min: undefined,
    placeholder: undefined,
    suffix: undefined,
  },
)
</script>

<template>
  <label class="block space-y-2" :for="id">
    <span class="text-ink-primary text-sm font-semibold">{{ label }}</span>
    <span class="relative block">
      <input
        :id="id"
        :value="model"
        class="border-line-subtle bg-surface-primary text-ink-primary rounded-control focus:border-accent-primary focus:ring-accent-primary/20 min-h-12 w-full appearance-none border py-3 pl-4 text-base transition outline-none focus:ring-4"
        :class="suffix ? 'pr-16' : 'pr-4'"
        inputmode="numeric"
        :max="max"
        :min="min"
        :placeholder="placeholder"
        type="number"
        @input="onInput"
      />
      <span v-if="suffix" class="text-ink-muted pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-sm">
        {{ suffix }}
      </span>
    </span>
    <span v-if="help && !error" class="text-ink-muted block text-sm leading-5">{{ help }}</span>
    <span v-if="error" class="text-status-danger block text-sm leading-5">{{ error }}</span>
  </label>
</template>

<style scoped>
input[type='number'] {
  appearance: textfield;
}

input[type='number']::-webkit-inner-spin-button,
input[type='number']::-webkit-outer-spin-button {
  margin: 0;
  appearance: none;
}
</style>
