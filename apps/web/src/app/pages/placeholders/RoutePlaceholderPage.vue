<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { UiBadge, UiCard } from '@/shared/ui'
import type { PlaceholderMetric, RoutePlaceholderContent } from '@/app/router/routeMeta'

const route = useRoute()
const router = useRouter()
const activeStepIndex = ref(0)

const fallbackContent: RoutePlaceholderContent = {
  eyebrow: 'Leverly',
  title: 'Route',
  description: 'This area is ready for the next product surface.',
  status: 'Ready',
  primaryAction: { label: 'Open dashboard', to: { name: 'dashboard' } },
  secondaryAction: { label: 'Start today', to: { name: 'today' } },
  metrics: [],
  steps: [],
}

const content = computed(() => route.meta.placeholder ?? fallbackContent)
const activeStep = computed(() => content.value.steps[activeStepIndex.value] ?? content.value.steps[0])

function goBack(): void {
  router.back()
}

function metricClass(metric: PlaceholderMetric): string {
  return {
    success: 'bg-status-success-soft text-status-success',
    warning: 'bg-status-warning-soft text-status-warning',
    info: 'bg-accent-secondary-soft text-accent-secondary',
    neutral: 'bg-surface-muted text-ink-secondary',
  }[metric.tone]
}
</script>

<template>
  <section class="space-y-6" data-test="route-placeholder">
    <div
      class="border-line-subtle bg-surface-elevated/92 shadow-card rounded-card border p-5 backdrop-blur sm:p-7 lg:p-8"
    >
      <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-end">
        <div class="min-w-0">
          <UiBadge tone="info">{{ content.eyebrow }}</UiBadge>
          <h2 class="text-ink-primary mt-4 text-3xl font-semibold tracking-normal sm:text-4xl">
            {{ content.title }}
          </h2>
          <p class="text-ink-secondary mt-4 max-w-3xl text-base leading-7">
            {{ content.description }}
          </p>
        </div>

        <div class="border-line-subtle bg-surface-primary/80 rounded-card shadow-card-soft border p-4">
          <p class="text-ink-muted text-xs font-semibold tracking-[0.18em] uppercase">Status</p>
          <p class="text-ink-primary mt-2 text-lg font-semibold">{{ content.status }}</p>
          <div class="mt-4 flex flex-col gap-2 sm:flex-row lg:flex-col">
            <RouterLink
              :to="content.primaryAction.to"
              class="rounded-control bg-accent-primary text-ink-inverse shadow-control focus-visible:ring-accent-primary focus-visible:ring-offset-surface-primary hover:bg-accent-primary-strong inline-flex min-h-11 items-center justify-center px-4 text-sm font-semibold transition outline-none hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-offset-2"
            >
              {{ content.primaryAction.label }}
            </RouterLink>
            <RouterLink
              :to="content.secondaryAction.to"
              class="rounded-control border-line-subtle bg-surface-elevated text-ink-primary shadow-card-soft focus-visible:ring-accent-primary focus-visible:ring-offset-surface-primary hover:border-line-strong inline-flex min-h-11 items-center justify-center border px-4 text-sm font-semibold transition outline-none hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-offset-2"
            >
              {{ content.secondaryAction.label }}
            </RouterLink>
            <button
              class="rounded-control text-ink-secondary focus-visible:ring-accent-primary focus-visible:ring-offset-surface-primary hover:bg-accent-primary-soft hover:text-ink-primary inline-flex min-h-11 items-center justify-center px-4 text-sm font-semibold transition outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
              type="button"
              @click="goBack"
            >
              Back
            </button>
          </div>
        </div>
      </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
      <UiCard v-for="metric in content.metrics" :key="metric.label" as="article" padding="md" tone="soft">
        <span
          :class="['rounded-control inline-flex min-h-9 items-center px-3 text-xs font-semibold', metricClass(metric)]"
        >
          {{ metric.label }}
        </span>
        <p class="text-ink-primary mt-4 text-2xl font-semibold">{{ metric.value }}</p>
        <p class="text-ink-secondary mt-2 text-sm leading-6">{{ metric.detail }}</p>
      </UiCard>
    </div>

    <section
      v-if="content.steps.length > 0"
      class="border-line-subtle bg-surface-primary/82 shadow-card-soft rounded-card border p-5 sm:p-6"
      aria-labelledby="route-path-heading"
    >
      <div class="grid gap-5 lg:grid-cols-[18rem_minmax(0,1fr)] lg:items-start">
        <div>
          <p id="route-path-heading" class="text-ink-primary text-lg font-semibold">Working path</p>
          <p class="text-ink-secondary mt-2 text-sm leading-6">
            {{ activeStep?.detail }}
          </p>
        </div>

        <div class="grid gap-2 sm:grid-cols-3">
          <button
            v-for="(step, index) in content.steps"
            :key="step.label"
            type="button"
            class="rounded-control focus-visible:ring-accent-primary focus-visible:ring-offset-surface-primary border px-4 py-3 text-left text-sm font-semibold transition outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
            :class="
              index === activeStepIndex
                ? 'border-accent-primary bg-accent-primary-soft text-ink-primary shadow-card-soft'
                : 'border-line-subtle bg-surface-elevated text-ink-secondary hover:border-line-strong hover:text-ink-primary'
            "
            @click="activeStepIndex = index"
          >
            {{ step.label }}
          </button>
        </div>
      </div>
    </section>
  </section>
</template>
