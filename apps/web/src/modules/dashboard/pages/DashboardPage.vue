<script setup lang="ts">
import { computed, ref } from 'vue'
import { dashboardCopy } from '@/shared/brand'
import AccessibleLineChart from '@/shared/charts/AccessibleLineChart.vue'
import DashboardHero from '../components/DashboardHero.vue'
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

const selectedFocus = computed(() => {
  return focusOptions.find((item) => item.id === activeFocus.value) ?? focusOptions[0]
})

function startWorkout(): void {
  activeFocus.value = 'today'
}
</script>

<template>
  <section id="dashboard" class="space-y-6">
    <DashboardHero @start="startWorkout" />

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
      <section id="today" aria-labelledby="focus-heading" class="space-y-5">
        <TrainingFocusPanel
          :active-focus="activeFocus"
          :options="focusOptions"
          :selected-focus="selectedFocus"
          @select="activeFocus = $event"
        />

        <TrainingBlockGrid :blocks="trainingBlocks" />

        <AccessibleLineChart
          :title="dashboardCopy.readinessTrend.title"
          :summary="dashboardCopy.readinessTrend.summary"
          :value-label="dashboardCopy.readinessTrend.valueLabel"
          :points="readinessTrend"
        />
      </section>

      <aside id="progressions" class="space-y-4" aria-labelledby="signals-heading">
        <ProgressionSignalsPanel :signals="progressionSignals" />
        <RecommendationGuardPanel />
      </aside>
    </div>
  </section>
</template>
