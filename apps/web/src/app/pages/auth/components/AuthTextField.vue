<script setup lang="ts">
defineProps<{
  autocomplete?: string
  error?: string
  id: string
  label: string
  modelValue: string
  type?: 'email' | 'password' | 'text'
}>()

defineEmits<{
  'update:modelValue': [value: string]
  blur: []
}>()
</script>

<template>
  <div class="space-y-2">
    <label :for="id" class="text-ink-primary block text-sm font-semibold">{{ label }}</label>
    <input
      :id="id"
      :autocomplete="autocomplete"
      :aria-describedby="error ? `${id}-error` : undefined"
      :aria-invalid="error ? 'true' : 'false'"
      :class="[
        'rounded-control bg-surface-primary text-ink-primary shadow-card-soft focus:border-accent-primary focus:ring-accent-primary min-h-12 w-full border px-4 text-base transition outline-none focus:ring-2',
        error ? 'border-status-danger' : 'border-line-subtle',
      ]"
      :type="type ?? 'text'"
      :value="modelValue"
      @blur="$emit('blur')"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    />
    <p v-if="error" :id="`${id}-error`" class="text-status-danger text-sm leading-5">
      {{ error }}
    </p>
  </div>
</template>
