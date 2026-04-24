<script setup lang="ts">
import { computed, ref } from 'vue'

type TrainingFocus = 'today' | 'progressions' | 'recovery'

const activeFocus = ref<TrainingFocus>('today')

const focusOptions: Array<{
  id: TrainingFocus
  label: string
  summary: string
}> = [
  {
    id: 'today',
    label: 'Today',
    summary: 'Four focused blocks with one primary pull target.',
  },
  {
    id: 'progressions',
    label: 'Progressions',
    summary: 'Front lever and dip paths are ready for the next evidence check.',
  },
  {
    id: 'recovery',
    label: 'Recovery',
    summary: 'Readiness is steady; keep pain and form gates visible.',
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
  },
  {
    name: 'Strength',
    target: 'Weighted pull-ups',
    detail: '4 x 5 at controlled tempo',
    status: 'Ready to log',
  },
  {
    name: 'Accessory',
    target: 'Ring rows',
    detail: '3 x 10 with full range',
    status: 'Balance pull volume',
  },
]

const progressionSignals = [
  { label: 'Readiness', value: 'Good', tone: 'Stable' },
  { label: 'Form trend', value: '4/5', tone: 'Progressing' },
  { label: 'Pain signal', value: '0/10', tone: 'Clear' },
]
</script>

<template>
  <main class="min-h-screen bg-stone-50 text-slate-950">
    <div class="mx-auto flex min-h-screen w-full max-w-7xl flex-col lg:flex-row">
      <aside
        class="flex border-b border-slate-200 bg-white/90 px-5 py-4 backdrop-blur lg:w-72 lg:flex-col lg:border-b-0 lg:border-r lg:px-6 lg:py-8"
        aria-label="Application navigation"
      >
        <div class="flex w-full items-center justify-between gap-4 lg:block">
          <a
            href="#dashboard"
            class="inline-flex rounded-md text-2xl font-semibold tracking-normal text-slate-950 outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-4"
          >
            Leverly
          </a>
          <button
            class="rounded-md border border-slate-300 px-3 py-2 text-sm font-medium text-slate-800 outline-none transition hover:border-emerald-700 hover:text-emerald-800 focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2 lg:hidden"
            type="button"
            @click="activeFocus = 'today'"
          >
            Start
          </button>
        </div>

        <nav class="mt-8 hidden space-y-2 lg:block" aria-label="Primary">
          <a
            class="block rounded-md bg-slate-950 px-4 py-3 text-sm font-medium text-white outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2"
            href="#dashboard"
            aria-current="page"
          >
            Dashboard
          </a>
          <a
            class="block rounded-md px-4 py-3 text-sm font-medium text-slate-700 outline-none hover:bg-slate-100 focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2"
            href="#today"
          >
            Today
          </a>
          <a
            class="block rounded-md px-4 py-3 text-sm font-medium text-slate-700 outline-none hover:bg-slate-100 focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2"
            href="#progressions"
          >
            Progressions
          </a>
        </nav>

        <div class="mt-auto hidden rounded-md bg-emerald-50 p-4 text-sm text-emerald-950 lg:block">
          <p class="font-semibold">Next target</p>
          <p class="mt-2 text-emerald-900">Add evidence before increasing leverage.</p>
        </div>
      </aside>

      <section id="dashboard" class="flex-1 px-5 py-6 sm:px-8 lg:px-10 lg:py-8">
        <header class="flex flex-col gap-5 border-b border-slate-200 pb-6 md:flex-row md:items-center md:justify-between">
          <div>
            <p class="text-sm font-semibold uppercase tracking-normal text-emerald-700">
              Bodyweight strength
            </p>
            <h1 class="mt-2 max-w-3xl text-3xl font-semibold leading-tight tracking-normal text-slate-950 sm:text-4xl lg:text-5xl">
              Today&apos;s work is ready to log.
            </h1>
          </div>
          <button
            class="inline-flex min-h-11 items-center justify-center rounded-md bg-emerald-700 px-5 py-3 text-sm font-semibold text-white outline-none transition hover:bg-emerald-800 focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2"
            type="button"
            @click="activeFocus = 'today'"
          >
            Start workout
          </button>
        </header>

        <div class="grid gap-6 py-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
          <section id="today" aria-labelledby="focus-heading" class="space-y-5">
            <div class="rounded-md border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
              <h2 id="focus-heading" class="text-lg font-semibold tracking-normal">Training focus</h2>
              <div class="mt-4 grid gap-2 sm:grid-cols-3" role="group" aria-label="Training focus">
                <button
                  v-for="option in focusOptions"
                  :key="option.id"
                  class="rounded-md border px-4 py-3 text-left text-sm font-medium outline-none transition focus-visible:ring-2 focus-visible:ring-emerald-700 focus-visible:ring-offset-2"
                  :class="
                    activeFocus === option.id
                      ? 'border-emerald-700 bg-emerald-50 text-emerald-950'
                      : 'border-slate-200 bg-white text-slate-700 hover:border-slate-400'
                  "
                  type="button"
                  @click="activeFocus = option.id"
                >
                  {{ option.label }}
                </button>
              </div>
              <p class="mt-4 text-sm leading-6 text-slate-600">{{ selectedFocus.summary }}</p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
              <article
                v-for="block in trainingBlocks"
                :key="block.name"
                class="rounded-md border border-slate-200 bg-white p-5 shadow-sm"
              >
                <p class="text-sm font-semibold text-emerald-700">{{ block.name }}</p>
                <h3 class="mt-2 text-lg font-semibold tracking-normal text-slate-950">{{ block.target }}</h3>
                <p class="mt-3 text-sm leading-6 text-slate-600">{{ block.detail }}</p>
                <p class="mt-4 rounded-md bg-slate-100 px-3 py-2 text-sm font-medium text-slate-700">
                  {{ block.status }}
                </p>
              </article>
            </div>
          </section>

          <aside id="progressions" class="space-y-4" aria-labelledby="signals-heading">
            <div class="rounded-md border border-slate-200 bg-white p-5 shadow-sm">
              <h2 id="signals-heading" class="text-lg font-semibold tracking-normal">Progression signals</h2>
              <dl class="mt-4 space-y-3">
                <div
                  v-for="signal in progressionSignals"
                  :key="signal.label"
                  class="flex items-center justify-between gap-4 rounded-md bg-slate-50 px-3 py-3"
                >
                  <dt class="text-sm text-slate-600">{{ signal.label }}</dt>
                  <dd class="text-right">
                    <span class="block text-sm font-semibold text-slate-950">{{ signal.value }}</span>
                    <span class="block text-xs text-emerald-700">{{ signal.tone }}</span>
                  </dd>
                </div>
              </dl>
            </div>

            <div class="rounded-md border border-slate-200 bg-slate-950 p-5 text-white shadow-sm">
              <h2 class="text-lg font-semibold tracking-normal">Recommendation guard</h2>
              <p class="mt-3 text-sm leading-6 text-slate-200">
                No progression is suggested until hold quality, pain, and readiness stay inside safe bounds.
              </p>
            </div>
          </aside>
        </div>
      </section>
    </div>
  </main>
</template>
