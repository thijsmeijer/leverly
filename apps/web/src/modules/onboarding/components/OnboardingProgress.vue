<script setup lang="ts">
import type { OnboardingStepId } from '../types'

defineProps<{
  activeStep: OnboardingStepId
  lockedStepIds?: OnboardingStepId[]
  steps: Array<{ id: OnboardingStepId; label: string }>
}>()

defineEmits<{
  select: [step: OnboardingStepId]
}>()
</script>

<template>
  <nav
    class="border-line-subtle bg-surface-elevated shadow-card-soft rounded-card border p-2"
    aria-label="Onboarding steps"
  >
    <ol class="grid gap-2 sm:grid-cols-4 xl:grid-cols-8">
      <li v-for="(step, index) in steps" :key="step.id">
        <button
          class="rounded-control flex min-h-12 w-full items-center gap-3 px-3 text-left text-sm transition"
          :disabled="lockedStepIds?.includes(step.id)"
          :class="
            step.id === activeStep
              ? 'bg-accent-primary shadow-control text-white'
              : lockedStepIds?.includes(step.id)
                ? 'text-ink-muted cursor-not-allowed opacity-55'
                : 'text-ink-secondary hover:bg-accent-primary-soft hover:text-ink-primary'
          "
          type="button"
          @click="$emit('select', step.id)"
        >
          <span
            class="grid h-7 w-7 shrink-0 place-items-center rounded-full text-xs font-bold"
            :class="step.id === activeStep ? 'bg-white/20 text-white' : 'bg-surface-muted text-ink-muted'"
          >
            {{ index + 1 }}
          </span>
          <span class="font-semibold">{{ step.label }}</span>
        </button>
      </li>
    </ol>
  </nav>
</template>
