<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { leverlyBrand } from '@/shared/brand'
import { UiBadge, UiCard } from '@/shared/ui'
import type { PlaceholderMetric, RoutePlaceholderContent } from '@/app/router/routeMeta'

const route = useRoute()
const activeStepIndex = ref(0)

const fallbackContent: RoutePlaceholderContent = {
  eyebrow: 'Leverly',
  title: 'Setup',
  description: 'This flow is ready for the next interactive surface.',
  status: 'Ready',
  primaryAction: { label: 'Sign in', to: { name: 'login' } },
  secondaryAction: { label: 'Open dashboard', to: { name: 'dashboard' } },
  metrics: [],
  steps: [],
}

const content = computed(() => route.meta.placeholder ?? fallbackContent)
const activeStep = computed(() => content.value.steps[activeStepIndex.value] ?? content.value.steps[0])

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
  <main
    class="text-ink-primary min-h-screen bg-[radial-gradient(circle_at_top_left,var(--accent-primary-soft),transparent_34rem),linear-gradient(135deg,var(--surface-primary),var(--surface-canvas)_58%,var(--surface-muted))] px-4 py-6 sm:px-6 lg:px-8"
  >
    <section
      class="mx-auto grid min-h-[calc(100vh-3rem)] w-full max-w-6xl gap-6 lg:grid-cols-[minmax(0,1fr)_24rem] lg:items-center"
    >
      <div class="space-y-6">
        <RouterLink
          :to="{ name: 'dashboard' }"
          class="rounded-control focus-visible:ring-accent-primary focus-visible:ring-offset-surface-primary inline-flex min-h-11 flex-col justify-center outline-none focus-visible:ring-2 focus-visible:ring-offset-4"
        >
          <span class="text-ink-primary text-2xl font-semibold tracking-normal">{{ leverlyBrand.productName }}</span>
          <span class="text-ink-muted mt-1 text-sm leading-5">{{ leverlyBrand.tagline }}</span>
        </RouterLink>

        <div class="border-line-subtle bg-surface-elevated/92 shadow-card rounded-card border p-5 backdrop-blur sm:p-7">
          <UiBadge tone="info">{{ content.eyebrow }}</UiBadge>
          <h1 class="text-ink-primary mt-4 text-3xl font-semibold tracking-normal sm:text-5xl">
            {{ content.title }}
          </h1>
          <p class="text-ink-secondary mt-5 max-w-3xl text-base leading-7 sm:text-lg">
            {{ content.description }}
          </p>

          <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <RouterLink
              :to="content.primaryAction.to"
              class="rounded-control bg-accent-primary text-ink-inverse shadow-control focus-visible:ring-accent-primary focus-visible:ring-offset-surface-primary hover:bg-accent-primary-strong inline-flex min-h-11 items-center justify-center px-5 text-sm font-semibold transition outline-none hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-offset-2"
            >
              {{ content.primaryAction.label }}
            </RouterLink>
            <RouterLink
              :to="content.secondaryAction.to"
              class="rounded-control border-line-subtle bg-surface-elevated text-ink-primary shadow-card-soft focus-visible:ring-accent-primary focus-visible:ring-offset-surface-primary hover:border-line-strong inline-flex min-h-11 items-center justify-center border px-5 text-sm font-semibold transition outline-none hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-offset-2"
            >
              {{ content.secondaryAction.label }}
            </RouterLink>
          </div>
        </div>
      </div>

      <aside class="space-y-4">
        <UiCard as="section" padding="md" tone="soft">
          <p class="text-ink-muted text-xs font-semibold tracking-[0.18em] uppercase">Status</p>
          <p class="text-ink-primary mt-2 text-xl font-semibold">{{ content.status }}</p>
          <p class="text-ink-secondary mt-3 text-sm leading-6">{{ activeStep?.detail }}</p>
        </UiCard>

        <UiCard v-for="metric in content.metrics" :key="metric.label" as="article" padding="sm" tone="elevated">
          <span
            :class="[
              'rounded-control inline-flex min-h-9 items-center px-3 text-xs font-semibold',
              metricClass(metric),
            ]"
          >
            {{ metric.label }}
          </span>
          <p class="text-ink-primary mt-3 text-xl font-semibold">{{ metric.value }}</p>
          <p class="text-ink-secondary mt-2 text-sm leading-6">{{ metric.detail }}</p>
        </UiCard>

        <div class="grid gap-2">
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
      </aside>
    </section>
  </main>
</template>
