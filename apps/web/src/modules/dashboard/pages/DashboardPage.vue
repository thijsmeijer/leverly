<script setup lang="ts">
import { computed, ref } from 'vue'
import AccessibleLineChart from '@/shared/charts/AccessibleLineChart.vue'
import { useDashboardApiStatus } from '../composables/useDashboardApiStatus'
import DashboardHero from '../components/DashboardHero.vue'
import DashboardSidebar from '../components/DashboardSidebar.vue'
import ProgressionSignalsPanel from '../components/ProgressionSignalsPanel.vue'
import RecommendationGuardPanel from '../components/RecommendationGuardPanel.vue'
import TrainingBlockGrid from '../components/TrainingBlockGrid.vue'
import TrainingFocusPanel from '../components/TrainingFocusPanel.vue'
import {
  focusOptions,
  progressionSignals,
  readinessTrend,
  trainingBlocks,
  type TrainingFocus,
} from '../data/dashboardPreview'

const activeFocus = ref<TrainingFocus>('today')
const { apiStatus, isRefreshing: isStatusRefreshing, refreshStatus } = useDashboardApiStatus()

const selectedFocus = computed(() => {
  return focusOptions.find((item) => item.id === activeFocus.value) ?? focusOptions[0]
})

function startWorkout(): void {
  activeFocus.value = 'today'
}
</script>

<template>
  <main class="bg-lab-void min-h-screen text-stone-100">
    <div class="mx-auto flex min-h-screen w-full max-w-[92rem] flex-col lg:flex-row">
      <DashboardSidebar
        :api-status="apiStatus"
        :is-status-refreshing="isStatusRefreshing"
        @refresh-status="refreshStatus"
        @start="startWorkout"
      />

      <section
        id="dashboard"
        class="text-ink-primary flex-1 bg-[radial-gradient(circle_at_top_left,var(--accent-primary-soft),transparent_34rem),linear-gradient(135deg,var(--surface-primary)_0%,var(--surface-canvas)_54%,var(--surface-muted)_100%)] px-5 py-6 sm:px-8 lg:px-10 lg:py-8"
      >
        <DashboardHero @start="startWorkout" />

        <div class="grid gap-6 py-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
          <section id="today" aria-labelledby="focus-heading" class="space-y-5">
            <TrainingFocusPanel
              :active-focus="activeFocus"
              :options="focusOptions"
              :selected-focus="selectedFocus"
              @select="activeFocus = $event"
            />

            <TrainingBlockGrid :blocks="trainingBlocks" />

            <AccessibleLineChart
              title="Readiness trend"
              summary="Readiness stayed within a productive range this week, with Friday currently strongest at 86."
              value-label="Readiness score"
              :points="readinessTrend"
            />
          </section>

          <aside id="progressions" class="space-y-4" aria-labelledby="signals-heading">
            <ProgressionSignalsPanel :signals="progressionSignals" />
            <RecommendationGuardPanel />
          </aside>
        </div>
      </section>
    </div>
  </main>
</template>
