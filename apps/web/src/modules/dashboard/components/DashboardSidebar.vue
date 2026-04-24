<script setup lang="ts">
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
    class="bg-lab-shell/95 shadow-lab-shell flex border-b border-white/10 px-5 py-4 backdrop-blur lg:w-72 lg:flex-col lg:border-r lg:border-b-0 lg:px-6 lg:py-8"
    aria-label="Application navigation"
  >
    <div class="flex w-full items-center justify-between gap-4 lg:block">
      <a
        href="#dashboard"
        class="focus-visible:ring-offset-lab-shell inline-flex rounded-md text-2xl font-semibold tracking-normal text-stone-50 outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-4"
      >
        Leverly
      </a>
      <button
        class="focus-visible:ring-offset-lab-shell rounded-md border border-emerald-300/30 bg-emerald-300/10 px-3 py-2 text-sm font-medium text-emerald-100 transition outline-none hover:border-emerald-300 hover:bg-emerald-300/15 focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2 lg:hidden"
        type="button"
        @click="$emit('start')"
      >
        Start
      </button>
    </div>

    <nav class="mt-8 hidden space-y-2 lg:block" aria-label="Primary">
      <a
        class="shadow-lab-control focus-visible:ring-offset-lab-shell block rounded-lg border border-emerald-300/25 bg-emerald-300/10 px-4 py-3 text-sm font-medium text-emerald-50 outline-none focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2"
        href="#dashboard"
        aria-current="page"
      >
        Dashboard
      </a>
      <a
        class="focus-visible:ring-offset-lab-shell block rounded-md px-4 py-3 text-sm font-medium text-stone-300 outline-none hover:bg-white/10 hover:text-stone-50 focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2"
        href="#today"
      >
        Today
      </a>
      <a
        class="focus-visible:ring-offset-lab-shell block rounded-md px-4 py-3 text-sm font-medium text-stone-300 outline-none hover:bg-white/10 hover:text-stone-50 focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2"
        href="#progressions"
      >
        Progressions
      </a>
    </nav>

    <div
      class="bg-lab-overlay shadow-lab-shell mt-auto hidden rounded-lg border border-amber-300/20 p-4 text-sm text-stone-100 lg:block"
    >
      <p class="font-semibold text-amber-200">Next target</p>
      <p class="mt-2 leading-6 text-stone-300">Add evidence before increasing leverage.</p>
    </div>

    <div
      class="bg-lab-overlay shadow-lab-shell mt-4 hidden rounded-lg border border-white/10 p-4 text-sm text-stone-100 lg:block"
    >
      <div class="flex items-start justify-between gap-3">
        <div>
          <p class="font-semibold text-stone-100">{{ apiStatus.label }}</p>
          <p class="mt-2 leading-6 text-stone-300">{{ apiStatus.detail }}</p>
        </div>
        <span
          class="mt-1 size-2.5 rounded-full"
          :class="apiStatus.state === 'online' ? 'bg-emerald-300' : 'bg-amber-300'"
          aria-hidden="true"
        />
      </div>
      <button
        data-test="refresh-api-status"
        class="focus-visible:ring-offset-lab-shell mt-4 rounded-md border border-white/10 px-3 py-2 text-xs font-semibold text-stone-200 transition outline-none hover:border-emerald-300/50 hover:text-emerald-100 focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-60"
        type="button"
        :disabled="isStatusRefreshing"
        @click="$emit('refresh-status')"
      >
        {{ isStatusRefreshing ? 'Refreshing' : 'Refresh' }}
      </button>
    </div>
  </aside>
</template>
