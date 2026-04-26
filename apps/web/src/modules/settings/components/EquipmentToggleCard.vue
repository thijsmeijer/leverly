<script setup lang="ts">
import type { EquipmentOption } from '../data/profileOptions'

defineProps<{
  item: EquipmentOption
  selected: boolean
}>()

defineEmits<{
  toggle: [value: string]
}>()
</script>

<template>
  <label
    class="rounded-card focus-within:ring-accent-primary focus-within:ring-offset-surface-canvas block min-h-full border p-4 transition duration-200 focus-within:ring-2 focus-within:ring-offset-2"
    :class="
      selected
        ? 'border-accent-primary bg-accent-primary-soft shadow-card text-ink-primary'
        : 'border-line-subtle bg-surface-elevated hover:border-line-strong hover:bg-surface-overlay text-ink-secondary shadow-card-soft cursor-pointer'
    "
  >
    <input
      class="sr-only"
      :checked="selected"
      name="equipment"
      type="checkbox"
      :value="item.value"
      @change="$emit('toggle', item.value)"
    />
    <span class="flex min-h-full flex-col">
      <span class="flex items-start justify-between gap-3">
        <span>
          <span class="text-ink-muted block text-xs font-semibold tracking-[0.16em] uppercase">
            {{ item.category }}
          </span>
          <span class="text-ink-primary mt-2 block text-base font-semibold">{{ item.label }}</span>
        </span>
        <span
          class="mt-1 h-3.5 w-3.5 shrink-0 rounded-full border"
          :class="selected ? 'border-accent-primary bg-accent-primary shadow-control' : 'border-line-strong bg-white'"
          aria-hidden="true"
        />
      </span>
      <span class="mt-3 block text-sm leading-6">{{ item.description }}</span>
      <span class="mt-4 flex flex-wrap gap-2">
        <span
          v-for="unlock in item.unlocks"
          :key="unlock"
          class="bg-surface-primary text-ink-muted rounded-full px-2.5 py-1 text-xs font-semibold"
        >
          {{ unlock }}
        </span>
      </span>
    </span>
  </label>
</template>
