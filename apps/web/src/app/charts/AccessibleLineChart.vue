<script setup lang="ts">
import {
  CategoryScale,
  Chart as ChartJS,
  Filler,
  LinearScale,
  LineElement,
  PointElement,
  Tooltip,
  type ChartData,
  type ChartOptions,
} from 'chart.js'
import { computed } from 'vue'
import { Line } from 'vue-chartjs'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Filler, Tooltip)

type ChartPoint = {
  label: string
  value: number
}

const props = defineProps<{
  title: string
  summary: string
  valueLabel: string
  points: ChartPoint[]
}>()

function themeColor(name: string): string {
  return getComputedStyle(document.documentElement).getPropertyValue(name).trim()
}

const chartData = computed<ChartData<'line'>>(() => ({
  labels: props.points.map((point) => point.label),
  datasets: [
    {
      data: props.points.map((point) => point.value),
      borderColor: themeColor('--color-lab-emerald'),
      backgroundColor: themeColor('--color-lab-emerald-wash'),
      fill: true,
      pointBackgroundColor: themeColor('--color-lab-emerald'),
      pointBorderColor: themeColor('--color-lab-card'),
      pointBorderWidth: 2,
      tension: 0.35,
    },
  ],
}))

const chartOptions: ChartOptions<'line'> = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    tooltip: {
      callbacks: {
        label: (context) => `${props.valueLabel}: ${context.formattedValue}`,
      },
    },
  },
  scales: {
    x: {
      grid: {
        display: false,
      },
      ticks: {
        color: themeColor('--color-lab-muted'),
      },
    },
    y: {
      beginAtZero: true,
      max: 100,
      ticks: {
        color: themeColor('--color-lab-muted'),
      },
    },
  },
}
</script>

<template>
  <section
    class="bg-lab-card/90 shadow-lab-panel rounded-lg border border-white/60 p-5"
    :aria-labelledby="`${title}-title`"
  >
    <div class="flex flex-col gap-1 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h2 :id="`${title}-title`" class="text-lab-shell text-lg font-semibold tracking-normal">{{ title }}</h2>
        <p class="text-lab-muted mt-2 text-sm leading-6">{{ summary }}</p>
      </div>
    </div>

    <div class="mt-4 h-48" aria-hidden="true">
      <Line :data="chartData" :options="chartOptions" />
    </div>

    <details class="border-lab-line/10 text-lab-muted mt-4 rounded-lg border bg-white/50 px-3 py-2 text-sm">
      <summary class="text-lab-shell cursor-pointer font-medium">View chart data</summary>
      <table class="mt-3 w-full text-left">
        <caption class="sr-only">
          {{
            title
          }}
          data table
        </caption>
        <thead>
          <tr class="border-lab-line/10 border-b">
            <th class="py-2 pr-3 font-medium" scope="col">Label</th>
            <th class="py-2 font-medium" scope="col">{{ valueLabel }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="point in points" :key="point.label" class="border-lab-line/5 border-b last:border-b-0">
            <th class="py-2 pr-3 font-medium" scope="row">{{ point.label }}</th>
            <td class="py-2">{{ point.value }}</td>
          </tr>
        </tbody>
      </table>
    </details>
  </section>
</template>
