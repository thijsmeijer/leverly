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
}>()
</script>

<template>
  <UiCard as="section" padding="md" tone="soft" :aria-label="dashboardCopy.connectionStatus.onlineLabel">
    <div class="flex items-start justify-between gap-4">
      <div>
        <UiBadge :tone="apiStatus.state === 'online' ? 'success' : 'warning'">
          {{ apiStatus.state === 'online' ? 'Connected' : 'Offline' }}
        </UiBadge>
        <h2 class="text-ink-primary mt-4 text-lg font-semibold">{{ apiStatus.label }}</h2>
        <p class="text-ink-secondary mt-2 text-sm leading-6">{{ apiStatus.detail }}</p>
      </div>
      <span
        class="mt-2 size-3 shrink-0 rounded-full"
        :class="apiStatus.state === 'online' ? 'bg-status-success' : 'bg-status-warning'"
        aria-hidden="true"
      />
    </div>

    <UiButton
      data-test="refresh-api-status"
      class="mt-5 w-full"
      size="sm"
      variant="secondary"
      :disabled="isStatusRefreshing"
      @click="$emit('refresh-status')"
    >
      {{ isStatusRefreshing ? sharedCopy.actions.refreshing : sharedCopy.actions.refresh }}
    </UiButton>
  </UiCard>
</template>
