<script setup lang="ts">
import { computed } from 'vue'
import { UiCard, UiSectionHeader } from '@/shared/ui'

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

const chartWidth = 640
const chartHeight = 220
const chartInset = {
  top: 16,
  right: 18,
  bottom: 38,
  left: 42,
}
const plotWidth = chartWidth - chartInset.left - chartInset.right
const plotHeight = chartHeight - chartInset.top - chartInset.bottom

const chartId = computed(() =>
  props.title
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/(^-|-$)/g, ''),
)
const titleId = computed(() => `${chartId.value}-title`)
const chartTitleId = computed(() => `${chartId.value}-chart-title`)
const chartDescriptionId = computed(() => `${chartId.value}-chart-description`)
const fillId = computed(() => `${chartId.value}-fill`)

const yAxisBounds = computed(() => {
  const values = props.points.map((point) => point.value)
  if (values.length === 0) {
    return { min: 0, max: 100 }
  }

  const minimum = Math.min(...values)
  const maximum = Math.max(...values)
  const paddedMinimum = Math.max(0, Math.floor(minimum - 2))
  const paddedMaximum = Math.min(100, Math.ceil(maximum + 2))

  if (paddedMaximum - paddedMinimum < 12) {
    return { min: Math.max(0, paddedMinimum - 5), max: Math.min(100, paddedMaximum + 5) }
  }

  return { min: paddedMinimum, max: paddedMaximum }
})

const yAxisTicks = computed(() => {
  const tickCount = 5
  const step = (yAxisBounds.value.max - yAxisBounds.value.min) / (tickCount - 1)

  return Array.from({ length: tickCount }, (_, index) => yAxisBounds.value.max - step * index)
})

function xFor(index: number): number {
  if (props.points.length <= 1) {
    return chartInset.left + plotWidth / 2
  }

  return chartInset.left + (index / (props.points.length - 1)) * plotWidth
}

function yFor(value: number): number {
  const range = yAxisBounds.value.max - yAxisBounds.value.min || 1

  return chartInset.top + ((yAxisBounds.value.max - value) / range) * plotHeight
}

const plottedPoints = computed(() =>
  props.points.map((point, index) => ({
    ...point,
    x: xFor(index),
    y: yFor(point.value),
  })),
)

const linePath = computed(() =>
  plottedPoints.value.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' '),
)

const areaPath = computed(() => {
  const points = plottedPoints.value
  if (points.length === 0) {
    return ''
  }

  const first = points[0]
  const last = points[points.length - 1]

  return `${linePath.value} L ${last.x} ${chartHeight - chartInset.bottom} L ${first.x} ${chartHeight - chartInset.bottom} Z`
})

const chartSummary = computed(() => props.points.map((point) => `${point.label}: ${point.value}`).join(', '))
</script>

<template>
  <UiCard as="section" :aria-labelledby="titleId">
    <UiSectionHeader :title="title" :title-id="titleId" :description="summary" />

    <div
      class="rounded-card border-line-subtle bg-surface-primary/70 shadow-card-soft mt-4 overflow-hidden border px-2 py-3"
      data-test="readiness-chart"
    >
      <svg
        class="h-56 w-full overflow-visible"
        role="img"
        :aria-labelledby="`${chartTitleId} ${chartDescriptionId}`"
        :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
      >
        <title :id="chartTitleId">{{ title }}</title>
        <desc :id="chartDescriptionId">{{ chartSummary }}</desc>
        <defs>
          <linearGradient :id="fillId" x1="0" x2="0" y1="0" y2="1">
            <stop offset="0%" stop-color="var(--accent-primary)" stop-opacity="0.2" />
            <stop offset="100%" stop-color="var(--accent-primary)" stop-opacity="0.02" />
          </linearGradient>
        </defs>

        <g aria-hidden="true">
          <line
            v-for="tick in yAxisTicks"
            :key="tick"
            :x1="chartInset.left"
            :x2="chartWidth - chartInset.right"
            :y1="yFor(tick)"
            :y2="yFor(tick)"
            stroke="var(--line-subtle)"
            stroke-width="1"
          />
          <text
            v-for="tick in yAxisTicks"
            :key="`${tick}-label`"
            :x="chartInset.left - 12"
            :y="yFor(tick) + 4"
            fill="var(--ink-muted)"
            font-size="16"
            text-anchor="end"
          >
            {{ Math.round(tick) }}
          </text>
        </g>

        <g v-if="plottedPoints.length > 0">
          <path :d="areaPath" :fill="`url(#${fillId})`" />
          <path
            :d="linePath"
            fill="none"
            stroke="var(--accent-primary)"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="4"
          />
          <g v-for="point in plottedPoints" :key="point.label">
            <line
              :x1="point.x"
              :x2="point.x"
              :y1="chartHeight - chartInset.bottom"
              :y2="chartHeight - chartInset.bottom + 6"
              stroke="var(--line-strong)"
              stroke-width="1"
            />
            <circle :cx="point.x" :cy="point.y" fill="var(--surface-elevated)" r="6" />
            <circle :cx="point.x" :cy="point.y" fill="var(--accent-primary)" r="3.5">
              <title>{{ point.label }}: {{ point.value }}</title>
            </circle>
            <text :x="point.x" :y="chartHeight - 10" fill="var(--ink-muted)" font-size="16" text-anchor="middle">
              {{ point.label }}
            </text>
          </g>
        </g>
      </svg>
    </div>

    <details class="rounded-card border-line-subtle bg-surface-muted text-ink-secondary mt-4 border px-3 py-2 text-sm">
      <summary class="text-ink-primary cursor-pointer font-medium">View chart data</summary>
      <table class="mt-3 w-full text-left">
        <caption class="sr-only">
          {{
            title
          }}
          data table
        </caption>
        <thead>
          <tr class="border-line-subtle border-b">
            <th class="py-2 pr-3 font-medium" scope="col">Label</th>
            <th class="py-2 font-medium" scope="col">{{ valueLabel }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="point in points" :key="point.label" class="border-line-subtle border-b last:border-b-0">
            <th class="py-2 pr-3 font-medium" scope="row">{{ point.label }}</th>
            <td class="py-2">{{ point.value }}</td>
          </tr>
        </tbody>
      </table>
    </details>
  </UiCard>
</template>
