<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    label?: string
    max?: number
    showValue?: boolean
    tone?: 'primary' | 'success' | 'warning' | 'danger'
    value: number
  }>(),
  {
    label: undefined,
    max: 100,
    showValue: false,
    tone: 'primary',
  },
)

const normalizedValue = computed(() => Math.min(Math.max(props.value, 0), props.max))
const percentage = computed(() => (props.max === 0 ? 0 : Math.round((normalizedValue.value / props.max) * 100)))
const barClass = computed(
  () =>
    ({
      primary: 'bg-accent-primary',
      success: 'bg-status-success',
      warning: 'bg-status-warning',
      danger: 'bg-status-danger',
    })[props.tone],
)
</script>

<template>
  <div class="space-y-2">
    <div v-if="label || showValue" class="flex items-center justify-between gap-3 text-sm">
      <span v-if="label" class="text-ink-secondary font-medium">{{ label }}</span>
      <span v-if="showValue" class="text-ink-primary font-semibold">{{ percentage }}%</span>
    </div>
    <div
      class="bg-surface-muted h-2.5 overflow-hidden rounded-full"
      role="progressbar"
      :aria-label="label"
      :aria-valuemax="max"
      aria-valuemin="0"
      :aria-valuenow="normalizedValue"
    >
      <div
        class="h-full rounded-full transition-[width] duration-500"
        :class="barClass"
        :style="{ width: `${percentage}%` }"
      />
    </div>
  </div>
</template>
