<script setup lang="ts">
import { dashboardCopy, sharedCopy } from '@/shared/brand'
import { UiBadge, UiButton, UiCard } from '@/shared/ui'
import type { DashboardApiStatus } from '../types'

withDefaults(
  defineProps<{
    apiStatus: DashboardApiStatus
    isStatusRefreshing?: boolean
  }>(),
  {
    isStatusRefreshing: false,
  },
)

defineEmits<{
  'refresh-status': []
  start: []
}>()
</script>

<template>
  <aside
    class="bg-surface-inverse text-ink-inverse shadow-shell flex border-b border-white/10 px-5 py-4 lg:w-72 lg:flex-col lg:border-r lg:border-b-0 lg:px-6 lg:py-8"
    :aria-label="dashboardCopy.navigation.ariaLabel"
  >
    <div class="flex w-full items-center justify-between gap-4 lg:block">
      <a
        href="#dashboard"
        class="rounded-control text-ink-inverse focus-visible:ring-accent-primary focus-visible:ring-offset-surface-inverse inline-flex text-2xl font-semibold tracking-normal outline-none focus-visible:ring-2 focus-visible:ring-offset-4"
      >
        {{ dashboardCopy.brand.name }}
      </a>
      <p class="text-ink-inverse/60 mt-2 hidden max-w-48 text-sm leading-5 lg:block">
        {{ dashboardCopy.brand.tagline }}
      </p>
      <UiButton class="lg:hidden" size="sm" variant="inverse" @click="$emit('start')">
        {{ dashboardCopy.navigation.startShort }}
      </UiButton>
    </div>

    <nav class="mt-8 hidden space-y-2 lg:block" :aria-label="dashboardCopy.navigation.primaryAriaLabel">
      <a
        class="rounded-control text-ink-inverse shadow-control focus-visible:ring-accent-primary focus-visible:ring-offset-surface-inverse block border border-white/15 bg-white/10 px-4 py-3 text-sm font-medium outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
        href="#dashboard"
        aria-current="page"
      >
        {{ dashboardCopy.navigation.dashboard }}
      </a>
      <a
        class="rounded-control text-ink-inverse/75 hover:text-ink-inverse focus-visible:ring-accent-primary focus-visible:ring-offset-surface-inverse block px-4 py-3 text-sm font-medium outline-none hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-offset-2"
        href="#today"
      >
        {{ dashboardCopy.navigation.today }}
      </a>
      <a
        class="rounded-control text-ink-inverse/75 hover:text-ink-inverse focus-visible:ring-accent-primary focus-visible:ring-offset-surface-inverse block px-4 py-3 text-sm font-medium outline-none hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-offset-2"
        href="#progressions"
      >
        {{ dashboardCopy.navigation.progressions }}
      </a>
    </nav>

    <UiCard as="div" tone="inverse" padding="sm" class="mt-auto hidden lg:block">
      <UiBadge tone="warning">{{ dashboardCopy.nextTarget.label }}</UiBadge>
      <p class="text-ink-inverse/75 mt-3 text-sm leading-6">{{ dashboardCopy.nextTarget.description }}</p>
    </UiCard>

    <UiCard as="div" tone="inverse" padding="sm" class="mt-4 hidden lg:block">
      <div class="flex items-start justify-between gap-3">
        <div>
          <p class="text-ink-inverse font-semibold">{{ apiStatus.label }}</p>
          <p class="text-ink-inverse/75 mt-2 text-sm leading-6">{{ apiStatus.detail }}</p>
        </div>
        <span
          class="mt-1 size-2.5 rounded-full"
          :class="apiStatus.state === 'online' ? 'bg-emerald-300' : 'bg-amber-300'"
          aria-hidden="true"
        />
      </div>
      <UiButton
        data-test="refresh-api-status"
        class="mt-4"
        size="sm"
        variant="inverse"
        :disabled="isStatusRefreshing"
        @click="$emit('refresh-status')"
      >
        {{ isStatusRefreshing ? sharedCopy.actions.refreshing : sharedCopy.actions.refresh }}
      </UiButton>
    </UiCard>
  </aside>
</template>
