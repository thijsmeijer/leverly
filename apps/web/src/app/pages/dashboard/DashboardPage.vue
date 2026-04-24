<script setup lang="ts">
import { computed, ref } from 'vue'
import AccessibleLineChart from '../../charts/AccessibleLineChart.vue'

type TrainingFocus = 'today' | 'progressions' | 'recovery'

const activeFocus = ref<TrainingFocus>('today')

const focusOptions: Array<{
  id: TrainingFocus
  label: string
  summary: string
  metric: string
}> = [
  {
    id: 'today',
    label: 'Today',
    summary: 'Four focused blocks with one primary pull target.',
    metric: '42 min',
  },
  {
    id: 'progressions',
    label: 'Progressions',
    summary: 'Front lever and dip paths are ready for the next evidence check.',
    metric: '2 gates',
  },
  {
    id: 'recovery',
    label: 'Recovery',
    summary: 'Readiness is steady; keep pain and form gates visible.',
    metric: '86%',
  },
]

const selectedFocus = computed(() => {
  return focusOptions.find((item) => item.id === activeFocus.value) ?? focusOptions[0]
})

const trainingBlocks = [
  {
    name: 'Skill',
    target: 'Tuck front lever holds',
    detail: '5 x 12s with clean scapular position',
    status: 'Gate: form first',
    load: 'RPE 7',
  },
  {
    name: 'Strength',
    target: 'Weighted pull-ups',
    detail: '4 x 5 at controlled tempo',
    status: 'Ready to log',
    load: '+12.5 kg',
  },
  {
    name: 'Accessory',
    target: 'Ring rows',
    detail: '3 x 10 with full range',
    status: 'Balance pull volume',
    load: 'Volume',
  },
]

const progressionSignals = [
  { label: 'Readiness', value: 'Good', tone: 'Stable', color: 'text-emerald-300' },
  { label: 'Form trend', value: '4/5', tone: 'Progressing', color: 'text-amber-300' },
  { label: 'Pain signal', value: '0/10', tone: 'Clear', color: 'text-emerald-300' },
]

const readinessTrend = [
  { label: 'Mon', value: 78 },
  { label: 'Tue', value: 82 },
  { label: 'Wed', value: 76 },
  { label: 'Thu', value: 84 },
  { label: 'Fri', value: 86 },
]
</script>

<template>
  <main class="bg-lab-void min-h-screen text-stone-100">
    <div class="mx-auto flex min-h-screen w-full max-w-7xl flex-col lg:flex-row">
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
            @click="activeFocus = 'today'"
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
      </aside>

      <section
        id="dashboard"
        class="text-lab-surface flex-1 bg-[linear-gradient(135deg,var(--color-lab-paper)_0%,var(--color-lab-paper-soft)_48%,var(--color-lab-paper-deep)_100%)] px-5 py-6 sm:px-8 lg:px-10 lg:py-8"
      >
        <header
          class="border-lab-line/15 flex flex-col gap-5 border-b pb-6 md:flex-row md:items-center md:justify-between"
        >
          <div>
            <p class="text-sm font-semibold tracking-normal text-emerald-800 uppercase">Premium training lab</p>
            <h1
              class="text-lab-shell mt-2 max-w-3xl text-3xl leading-tight font-semibold tracking-normal sm:text-4xl lg:text-5xl"
            >
              Today&apos;s work is ready to log.
            </h1>
            <p class="text-lab-copy mt-3 max-w-2xl text-base leading-7">
              Calisthenics progress, readiness, and recommendation gates in one focused workspace.
            </p>
          </div>
          <button
            class="bg-lab-emerald shadow-lab-control hover:bg-lab-emerald-strong focus-visible:ring-offset-lab-paper inline-flex min-h-11 items-center justify-center rounded-lg px-5 py-3 text-sm font-semibold text-white transition outline-none hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2"
            type="button"
            @click="activeFocus = 'today'"
          >
            Start workout
          </button>
        </header>

        <div class="grid gap-6 py-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
          <section id="today" aria-labelledby="focus-heading" class="space-y-5">
            <div class="bg-lab-card/90 shadow-lab-panel rounded-lg border border-white/60 p-4 backdrop-blur sm:p-5">
              <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <h2 id="focus-heading" class="text-lg font-semibold tracking-normal">Training focus</h2>
                <p class="text-lab-soft text-sm font-medium">{{ selectedFocus.metric }}</p>
              </div>
              <div class="mt-4 grid gap-2 sm:grid-cols-3" role="group" aria-label="Training focus">
                <button
                  v-for="option in focusOptions"
                  :key="option.id"
                  class="focus-visible:ring-offset-lab-card rounded-lg border px-4 py-3 text-left text-sm font-medium transition outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2"
                  :class="
                    activeFocus === option.id
                      ? 'bg-lab-emerald shadow-lab-control border-emerald-800 text-white'
                      : 'bg-lab-card-high/55 text-lab-copy shadow-lab-panel-soft hover:bg-lab-card-high border-white/70 hover:-translate-y-0.5 hover:border-emerald-800/30'
                  "
                  type="button"
                  @click="activeFocus = option.id"
                >
                  <span class="block">{{ option.label }}</span>
                  <span class="mt-1 block text-xs opacity-75">{{ option.metric }}</span>
                </button>
              </div>
              <p class="text-lab-muted mt-4 text-sm leading-6">{{ selectedFocus.summary }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
              <article
                v-for="block in trainingBlocks"
                :key="block.name"
                class="bg-lab-card/90 shadow-lab-panel hover:bg-lab-card-high rounded-lg border border-white/60 p-5 transition hover:-translate-y-0.5"
              >
                <div class="flex items-start justify-between gap-3">
                  <p class="text-sm font-semibold text-emerald-800">{{ block.name }}</p>
                  <span class="bg-lab-surface rounded-lg px-2.5 py-1 text-xs font-semibold text-stone-100 shadow-sm">
                    {{ block.load }}
                  </span>
                </div>
                <h3 class="text-lab-shell mt-3 text-lg font-semibold tracking-normal">{{ block.target }}</h3>
                <p class="text-lab-muted mt-3 text-sm leading-6">{{ block.detail }}</p>
                <p
                  class="text-lab-amber mt-4 rounded-lg border border-amber-700/15 bg-amber-100/70 px-3 py-2 text-sm font-medium shadow-sm shadow-amber-950/5"
                >
                  {{ block.status }}
                </p>
              </article>
            </div>

            <AccessibleLineChart
              title="Readiness trend"
              summary="Readiness stayed within a productive range this week, with Friday currently strongest at 86."
              value-label="Readiness score"
              :points="readinessTrend"
            />
          </section>

          <aside id="progressions" class="space-y-4" aria-labelledby="signals-heading">
            <div class="bg-lab-shell/95 shadow-lab-shell rounded-lg border border-white/10 p-5 text-stone-100">
              <h2 id="signals-heading" class="text-lg font-semibold tracking-normal">Progression signals</h2>
              <dl class="mt-4 space-y-3">
                <div
                  v-for="signal in progressionSignals"
                  :key="signal.label"
                  class="flex items-center justify-between gap-4 rounded-lg border border-white/10 bg-white/5 px-3 py-3 shadow-sm shadow-black/15"
                >
                  <dt class="text-sm text-stone-300">{{ signal.label }}</dt>
                  <dd class="text-right">
                    <span class="block text-sm font-semibold text-stone-50">{{ signal.value }}</span>
                    <span class="block text-xs" :class="signal.color">{{ signal.tone }}</span>
                  </dd>
                </div>
              </dl>
            </div>

            <div class="bg-lab-card/90 shadow-lab-panel rounded-lg border border-white/60 p-5">
              <h2 class="text-lg font-semibold tracking-normal">Recommendation guard</h2>
              <p class="text-lab-muted mt-3 text-sm leading-6">
                No progression is suggested until hold quality, pain, and readiness stay inside safe bounds.
              </p>
              <div class="bg-lab-rest mt-4 h-2 rounded-full">
                <div class="bg-lab-emerald h-2 w-4/5 rounded-full"></div>
              </div>
            </div>
          </aside>
        </div>
      </section>
    </div>
  </main>
</template>
