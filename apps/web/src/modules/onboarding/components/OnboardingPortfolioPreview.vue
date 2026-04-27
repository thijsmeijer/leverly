<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type { RoadmapMicroTestRequest, RoadmapPortfolio, RoadmapPortfolioTrack } from '@/modules/roadmap'

const props = withDefaults(
  defineProps<{
    portfolio: RoadmapPortfolio
    showMicroTests?: boolean
  }>(),
  {
    showMicroTests: false,
  },
)

const skippedMicroTests = ref<string[]>([])

const activePortfolio = computed(() => props.portfolio.activeSkillPortfolio)
const alsoTrainingTracks = computed(() =>
  uniqueTracks([...activePortfolio.value.technicalPracticeTracks, ...activePortfolio.value.accessoryTracks]),
)
const foundationTracks = computed(() =>
  uniqueTracks([...props.portfolio.foundationLayer.tracks, ...activePortfolio.value.foundationTracks]),
)
const notThisPhaseTracks = computed(() =>
  uniqueTracks([...props.portfolio.notRecommendedNow, ...props.portfolio.blocked]),
)
const portfolioGroups = computed(() => [
  {
    description: 'The main skill work with the clearest progress intent this phase.',
    empty: 'No development track is loaded yet.',
    id: 'development',
    label: 'Development focus',
    tracks: activePortfolio.value.developmentTracks,
  },
  {
    description: 'Compatible practice or accessory work that can fit beside the main focus.',
    empty: 'No extra skill work is loaded yet.',
    id: 'also-training',
    label: 'Also training',
    tracks: alsoTrainingTracks.value,
  },
  {
    description: 'Base strength and positions kept in the week so progressions do not outrun the foundation.',
    empty: 'Foundation work appears after the baseline is complete.',
    id: 'foundation',
    label: 'Foundation',
    tracks: foundationTracks.value,
  },
  {
    description: 'Owned patterns kept warm without stealing recovery from development work.',
    empty: 'No maintenance track is needed yet.',
    id: 'maintenance',
    label: 'Maintenance',
    tracks: activePortfolio.value.maintenanceTracks,
  },
  {
    description: 'Ambitious goals kept visible until the engine can load them safely.',
    empty: 'No future queue yet.',
    id: 'future',
    label: 'Future queue',
    tracks: activePortfolio.value.futureQueue.length
      ? activePortfolio.value.futureQueue
      : props.portfolio.longTermAspirations,
  },
  {
    description: 'Skills held back by readiness, equipment, pain, or recovery constraints.',
    empty: 'No blocked or held-back skills right now.',
    id: 'not-this-phase',
    label: 'Not this phase',
    tracks: notThisPhaseTracks.value,
  },
])
const visibleMicroTests = computed(() => props.portfolio.pendingTests.filter((test) => test.key && test.prompt))
const phaseDuration = computed(() => {
  const duration = activePortfolio.value.phasePlan.durationWeeks

  if (!duration.target) {
    return 'First phase'
  }

  return `First phase: ${duration.target} weeks`
})
const weeklyBudgetLabel = computed(() => {
  const sessions = activePortfolio.value.timeLedger.maxSessionsPerWeek
  const minutes = activePortfolio.value.timeLedger.estimatedMinutesPerWeek

  if (!sessions && !minutes) {
    return 'Schedule pending'
  }

  return `${sessions || '...'} sessions · ${minutes || 0} min/week`
})

watch(
  () => visibleMicroTests.value.map((test) => test.key).join('|'),
  () => {
    const available = new Set(visibleMicroTests.value.map((test) => test.key))
    skippedMicroTests.value = skippedMicroTests.value.filter((key) => available.has(key))
  },
)

function uniqueTracks(tracks: readonly RoadmapPortfolioTrack[]): RoadmapPortfolioTrack[] {
  const seen = new Set<string>()

  return tracks.filter((track) => {
    const key = track.skillTrackId || track.displayName

    if (seen.has(key)) {
      return false
    }

    seen.add(key)

    return true
  })
}

function trackKey(track: RoadmapPortfolioTrack, groupId: string): string {
  return `${groupId}-${track.skillTrackId || track.displayName}`
}

function trackMeta(track: RoadmapPortfolioTrack): string {
  const exposures = track.weeklyExposures ? `${track.weeklyExposures}x/week` : 'Dose pending'
  const eta = track.etaToNextNode.label || 'ETA after retest'

  return `${exposures} · ${eta}`
}

function trackReason(track: RoadmapPortfolioTrack): string {
  return track.whyIncluded[0] || track.whyNotHigherPriority[0] || 'Loaded from your current assessment.'
}

function confidenceLabel(track: RoadmapPortfolioTrack): string {
  return `${capitalize(track.confidence.level)} confidence`
}

function skipMicroTest(test: RoadmapMicroTestRequest): void {
  if (!skippedMicroTests.value.includes(test.key)) {
    skippedMicroTests.value = [...skippedMicroTests.value, test.key]
  }
}

function isSkipped(test: RoadmapMicroTestRequest): boolean {
  return skippedMicroTests.value.includes(test.key)
}

function testImpact(test: RoadmapMicroTestRequest): string {
  const completed = Math.round(test.confidenceImpact.completedDelta * 100)
  const missing = Math.abs(Math.round(test.confidenceImpact.missingDelta * 100))

  if (!completed && !missing) {
    return 'Improves placement confidence when it is filled in.'
  }

  return `Can improve confidence by about ${completed} points; skipping can keep it about ${missing} points lower.`
}

function capitalize(value: string): string {
  return value ? value.charAt(0).toUpperCase() + value.slice(1) : 'Low'
}
</script>

<template>
  <section aria-label="Roadmap portfolio preview" class="space-y-5">
    <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_minmax(17rem,0.38fr)]">
      <div class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-5">
        <p class="text-accent-primary text-xs font-semibold tracking-[0.16em] uppercase">Active skill portfolio</p>
        <h3 class="text-ink-primary mt-2 text-xl font-semibold">
          {{ props.portfolio.summary || activePortfolio.explanation.summary || 'Your first roadmap is being built.' }}
        </h3>
        <p class="text-ink-secondary mt-3 text-sm leading-6">
          Active roles are calculated after the engine checks stress, recovery, and time. Your choices guide the
          priority, but the loaded work stays inside the current budget.
        </p>
      </div>
      <dl class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1">
        <div class="rounded-control bg-surface-primary border-line-subtle border p-4">
          <dt class="text-ink-muted text-xs font-semibold">Phase</dt>
          <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ phaseDuration }}</dd>
        </div>
        <div class="rounded-control bg-surface-primary border-line-subtle border p-4">
          <dt class="text-ink-muted text-xs font-semibold">Weekly fit</dt>
          <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ weeklyBudgetLabel }}</dd>
        </div>
        <div class="rounded-control bg-surface-primary border-line-subtle border p-4">
          <dt class="text-ink-muted text-xs font-semibold">Safety watch</dt>
          <dd class="text-ink-primary mt-1 text-sm font-semibold">
            {{ activePortfolio.stressLedger.axes[0]?.axis || 'No major stress warning' }}
          </dd>
        </div>
      </dl>
    </div>

    <div class="grid gap-3 xl:grid-cols-3">
      <section
        v-for="group in portfolioGroups"
        :key="group.id"
        class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <h4 class="text-ink-primary text-base font-semibold">{{ group.label }}</h4>
            <p class="text-ink-muted mt-1 text-xs leading-5">{{ group.description }}</p>
          </div>
          <span class="bg-surface-muted text-ink-muted rounded-full px-2.5 py-1 text-xs font-semibold">
            {{ group.tracks.length }}
          </span>
        </div>

        <div v-if="group.tracks.length" class="mt-4 grid gap-3">
          <article
            v-for="track in group.tracks"
            :key="trackKey(track, group.id)"
            class="rounded-control bg-surface-elevated border-line-subtle border p-3"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <h5 class="text-ink-primary text-sm font-semibold">{{ track.displayName }}</h5>
              <span class="bg-accent-primary-soft text-ink-primary rounded-full px-2.5 py-1 text-xs font-semibold">
                {{ confidenceLabel(track) }}
              </span>
            </div>
            <p class="text-accent-secondary mt-2 text-xs font-semibold">{{ trackMeta(track) }}</p>
            <p class="text-ink-secondary mt-2 text-sm leading-5">{{ trackReason(track) }}</p>
          </article>
        </div>

        <p v-else class="text-ink-secondary mt-4 text-sm leading-6">{{ group.empty }}</p>
      </section>
    </div>

    <section
      v-if="showMicroTests && visibleMicroTests.length"
      class="border-accent-secondary/30 bg-accent-secondary-soft/45 rounded-card border p-5"
    >
      <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-accent-secondary text-xs font-semibold tracking-[0.16em] uppercase">Optional checks</p>
          <h4 class="text-ink-primary mt-2 text-lg font-semibold">Micro-tests that sharpen placement</h4>
        </div>
        <p class="text-ink-secondary max-w-xl text-sm leading-6">
          Only tests that can change placement or confidence are shown here. Skipping keeps the roadmap usable, but may
          keep a skill in bridge or future status.
        </p>
      </div>

      <div class="mt-4 grid gap-3 lg:grid-cols-2">
        <article
          v-for="test in visibleMicroTests"
          :key="test.key"
          class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4"
        >
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
              <p class="text-ink-muted text-xs font-semibold">{{ test.targetLabel }}</p>
              <h5 class="text-ink-primary mt-1 text-base font-semibold">{{ test.prompt }}</h5>
            </div>
            <span
              class="w-fit rounded-full px-2.5 py-1 text-xs font-semibold"
              :class="
                isSkipped(test) ? 'bg-status-warning/15 text-status-warning' : 'bg-accent-primary-soft text-ink-primary'
              "
            >
              {{ isSkipped(test) ? 'Skipped for now' : 'Confidence helper' }}
            </span>
          </div>
          <p class="text-ink-secondary mt-3 text-sm leading-6">{{ test.whyItMatters }}</p>
          <p class="text-ink-muted mt-2 text-xs leading-5">
            {{
              isSkipped(test)
                ? 'This stays usable, but confidence remains lower until the test is filled in.'
                : test.skipBehavior
            }}
          </p>
          <p class="text-ink-muted mt-2 text-xs leading-5">{{ testImpact(test) }}</p>
          <button
            v-if="!isSkipped(test)"
            class="text-accent-primary focus:ring-accent-primary/30 hover:text-accent-secondary mt-4 min-h-10 rounded-full px-1 text-sm font-semibold transition outline-none focus:ring-4"
            type="button"
            @click="skipMicroTest(test)"
          >
            Skip micro-test
          </button>
        </article>
      </div>
    </section>
  </section>
</template>
