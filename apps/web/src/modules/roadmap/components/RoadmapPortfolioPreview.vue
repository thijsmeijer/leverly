<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import type {
  RoadmapMicroTestRequest,
  RoadmapPortfolio,
  RoadmapPortfolioAdaptation,
  RoadmapPortfolioScheduledDay,
  RoadmapPortfolioStressAxis,
  RoadmapPortfolioTrack,
} from '../types'

const props = withDefaults(
  defineProps<{
    portfolio: RoadmapPortfolio
    showMicroTests?: boolean
    showSchedule?: boolean
    showStress?: boolean
  }>(),
  {
    showMicroTests: false,
    showSchedule: true,
    showStress: true,
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
    role: 'Development',
    tracks: activePortfolio.value.developmentTracks,
  },
  {
    description: 'Compatible practice or accessory work that can fit beside the main focus.',
    empty: 'No extra skill work is loaded yet.',
    id: 'also-training',
    label: 'Also training',
    role: 'Practice or accessory',
    tracks: alsoTrainingTracks.value,
  },
  {
    description: 'Base strength and positions kept in the week so progressions do not outrun the foundation.',
    empty: 'Foundation work appears after the baseline is complete.',
    id: 'foundation',
    label: 'Foundation',
    role: 'Foundation',
    tracks: foundationTracks.value,
  },
  {
    description: 'Owned patterns kept warm without stealing recovery from development work.',
    empty: 'No maintenance track is needed yet.',
    id: 'maintenance',
    label: 'Maintenance',
    role: 'Maintenance',
    tracks: activePortfolio.value.maintenanceTracks,
  },
  {
    description: 'Ambitious goals kept visible until the engine can load them safely.',
    empty: 'No future queue yet.',
    id: 'future',
    label: 'Future queue',
    role: 'Future',
    tracks: activePortfolio.value.futureQueue.length
      ? activePortfolio.value.futureQueue
      : props.portfolio.longTermAspirations,
  },
  {
    description: 'Skills held back by readiness, equipment, pain, or recovery constraints.',
    empty: 'No blocked or held-back skills right now.',
    id: 'not-this-phase',
    label: 'Not this phase',
    role: 'Not loaded',
    tracks: notThisPhaseTracks.value,
  },
])
const visibleMicroTests = computed(() => props.portfolio.pendingTests.filter((test) => test.key && test.prompt))
const scheduledDays = computed(() => activePortfolio.value.weeklySchedule.days)
const stressBuckets = computed(() => [
  {
    axes: axesFor(['push', 'wrist', 'planche', 'overhead', 'straight_arm_push']),
    empty: 'Pressing stress is quiet.',
    label: 'Push / wrist',
  },
  {
    axes: axesFor(['pull', 'elbow', 'grip', 'front_lever', 'straight_arm_pull']),
    empty: 'Pulling stress is quiet.',
    label: 'Pull / elbow',
  },
  {
    axes: axesFor(['leg', 'knee', 'ankle', 'squat', 'pistol']),
    empty: 'Leg stress is quiet.',
    label: 'Legs',
  },
  {
    axes: axesFor(['trunk', 'core', 'compression', 'bodyline', 'hollow']),
    empty: 'Bodyline stress is quiet.',
    label: 'Trunk / compression',
  },
  {
    axes: recoveryAxes.value,
    empty: recoveryEmptyLabel.value,
    label: 'Recovery margin',
  },
])
const recoveryAxes = computed<RoadmapPortfolioStressAxis[]>(() => {
  const timeLedger = activePortfolio.value.timeLedger
  const weeklyTime = activePortfolio.value.weeklySchedule.timeLedger
  const remaining =
    timeLedger.remainingMinutesPerWeek || weeklyTime.budgetMinutesPerWeek - weeklyTime.estimatedMinutesPerWeek
  const budget = weeklyTime.budgetMinutesPerWeek || timeLedger.estimatedMinutesPerWeek || 1
  const load = Math.max(0, budget - Math.max(0, remaining))
  const status = weeklyTime.overflowMinutesPerWeek > 0 || remaining < 0 ? 'overflow' : remaining < 20 ? 'watch' : 'ok'

  return [{ axis: 'recovery_margin', budget, load, status }]
})
const recoveryEmptyLabel = computed(() => {
  const remaining = activePortfolio.value.timeLedger.remainingMinutesPerWeek

  if (remaining > 0) {
    return `${remaining} minutes remain in the weekly budget.`
  }

  return 'Weekly time budget is tightly allocated.'
})
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

  return `${sessions || '...'} sessions / ${minutes || 0} min per week`
})
const portfolioEtaBasisLabel = computed(() => etaSourceLabel(activePortfolio.value.adaptation))
const portfolioEtaEvidenceLabel = computed(() => {
  const adaptation = activePortfolio.value.adaptation

  if (adaptation.etaBasis === 'blended' && adaptation.evidenceWeeks > 0) {
    return `${adaptation.evidenceWeeks} weeks of logs blended in.`
  }

  return adaptation.warnings[0] || 'ETA starts from baseline tests and graph priors.'
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

function axesFor(keywords: readonly string[]): RoadmapPortfolioStressAxis[] {
  return activePortfolio.value.stressLedger.axes.filter((axis) =>
    keywords.some((keyword) => axis.axis.toLowerCase().includes(keyword)),
  )
}

function trackKey(track: RoadmapPortfolioTrack, groupId: string): string {
  return `${groupId}-${track.skillTrackId || track.displayName}`
}

function confidenceLabel(track: RoadmapPortfolioTrack): string {
  return `${capitalize(track.confidence.level)} confidence`
}

function frequencyLabel(track: RoadmapPortfolioTrack): string {
  if (!track.weeklyExposures) {
    return 'Queued'
  }

  return `${track.weeklyExposures} exposures/week`
}

function intensityLabel(track: RoadmapPortfolioTrack): string {
  const moduleIntensity = firstStringFromModules(track, 'intensity_tier', 'intensityTier')

  return moduleIntensity ? humanize(moduleIntensity) : humanize(track.mode)
}

function roleLabel(track: RoadmapPortfolioTrack, fallbackRole: string): string {
  return humanize(track.mode || fallbackRole)
}

function etaLabel(track: RoadmapPortfolioTrack): string {
  return track.etaToNextNode.label || 'ETA after retest'
}

function etaSourceLabel(adaptation: RoadmapPortfolioAdaptation): string {
  if (adaptation.etaBasis === 'blended') {
    return 'Log-adjusted ETA'
  }

  if (adaptation.etaBasis === 'prior') {
    return 'Baseline prior'
  }

  return humanize(adaptation.etaBasis || adaptation.status || 'prior_based')
}

function trackReason(track: RoadmapPortfolioTrack): string {
  return track.whyIncluded[0] || 'Loaded from your current assessment.'
}

function trackWatchOut(track: RoadmapPortfolioTrack): string {
  return (
    track.whyNotHigherPriority[0] ||
    activePortfolio.value.explanation.watchOutFor[0] ||
    'Keep quality high before adding difficulty or volume.'
  )
}

function trackStressLabel(track: RoadmapPortfolioTrack): string {
  if (!track.primaryStressAxes.length) {
    return 'Stress pending'
  }

  return track.primaryStressAxes.map(humanize).join(', ')
}

function firstStringFromModules(track: RoadmapPortfolioTrack, snakeKey: string, camelKey: string): string {
  const firstModule = track.modules[0]

  if (!firstModule) {
    return ''
  }

  const value = firstModule[snakeKey] ?? firstModule[camelKey]

  return typeof value === 'string' ? value : ''
}

function dayTypeLabel(day: RoadmapPortfolioScheduledDay): string {
  return humanize(day.dayType || 'training')
}

function moduleSlotLabel(value: string): string {
  return humanize(value || 'training')
}

function stressRatio(axis: RoadmapPortfolioStressAxis): number {
  if (!axis.budget) {
    return 0
  }

  return Math.min(100, Math.round((axis.load / axis.budget) * 100))
}

function stressStatusLabel(axes: readonly RoadmapPortfolioStressAxis[]): string {
  const status = strongestStatus(axes)

  if (status === 'overflow' || status === 'red') {
    return 'Overloaded'
  }

  if (status === 'watch' || status === 'yellow' || status === 'orange') {
    return 'Watch'
  }

  return 'Good'
}

function strongestStatus(axes: readonly RoadmapPortfolioStressAxis[]): string {
  if (axes.some((axis) => ['overflow', 'red'].includes(axis.status))) {
    return 'overflow'
  }

  if (axes.some((axis) => ['watch', 'yellow', 'orange'].includes(axis.status))) {
    return 'watch'
  }

  return axes[0]?.status || 'ok'
}

function statusClasses(status: string): string {
  if (['overflow', 'red'].includes(status)) {
    return 'bg-status-danger/12 text-status-danger'
  }

  if (['watch', 'yellow', 'orange'].includes(status)) {
    return 'bg-status-warning/15 text-status-warning'
  }

  return 'bg-status-success/15 text-status-success'
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

function humanize(value: string): string {
  return value
    .replace(/[_-]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .replace(/\b\w/g, (letter) => letter.toUpperCase())
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
          Active roles are calculated after the engine checks stress, recovery, and time. Readiness keeps the loaded
          work inside the current budget while your choices guide priority.
        </p>
      </div>
      <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-1">
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
            {{ activePortfolio.explanation.watchOutFor[0] || 'No major stress warning' }}
          </dd>
        </div>
        <div class="rounded-control bg-surface-primary border-line-subtle border p-4">
          <dt class="text-ink-muted text-xs font-semibold">ETA source</dt>
          <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ portfolioEtaBasisLabel }}</dd>
          <p class="text-ink-muted mt-1 text-xs leading-5">{{ portfolioEtaEvidenceLabel }}</p>
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
            <dl class="mt-3 grid gap-2 text-xs sm:grid-cols-2">
              <div>
                <dt class="text-ink-muted font-semibold">Role</dt>
                <dd class="text-ink-primary mt-0.5 font-semibold">{{ roleLabel(track, group.role) }}</dd>
              </div>
              <div>
                <dt class="text-ink-muted font-semibold">Frequency</dt>
                <dd class="text-ink-primary mt-0.5 font-semibold">{{ frequencyLabel(track) }}</dd>
              </div>
              <div>
                <dt class="text-ink-muted font-semibold">Intensity</dt>
                <dd class="text-ink-primary mt-0.5 font-semibold">{{ intensityLabel(track) }}</dd>
              </div>
              <div>
                <dt class="text-ink-muted font-semibold">ETA</dt>
                <dd class="text-ink-primary mt-0.5 font-semibold">{{ etaLabel(track) }}</dd>
              </div>
              <div>
                <dt class="text-ink-muted font-semibold">ETA source</dt>
                <dd class="text-ink-primary mt-0.5 font-semibold">{{ etaSourceLabel(track.adaptation) }}</dd>
              </div>
              <div>
                <dt class="text-ink-muted font-semibold">Next node</dt>
                <dd class="text-ink-primary mt-0.5 font-semibold">{{ track.nextNode.label }}</dd>
              </div>
              <div>
                <dt class="text-ink-muted font-semibold">Next milestone</dt>
                <dd class="text-ink-primary mt-0.5 font-semibold">{{ track.targetNode.label }}</dd>
              </div>
            </dl>
            <p class="text-accent-secondary mt-3 text-xs font-semibold">{{ trackStressLabel(track) }}</p>
            <div class="mt-3 space-y-2">
              <p class="text-ink-secondary text-sm leading-5">
                <strong>Why included:</strong> {{ trackReason(track) }}
              </p>
              <p class="text-ink-secondary text-sm leading-5">
                <strong>Watch-outs:</strong> {{ trackWatchOut(track) }}
              </p>
            </div>
          </article>
        </div>

        <p v-else class="text-ink-secondary mt-4 text-sm leading-6">{{ group.empty }}</p>
      </section>
    </div>

    <section
      v-if="showSchedule && scheduledDays.length"
      aria-labelledby="portfolio-schedule-heading"
      class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-5"
    >
      <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
          <p class="text-accent-primary text-xs font-semibold tracking-[0.16em] uppercase">Weekly schedule</p>
          <h4 id="portfolio-schedule-heading" class="text-ink-primary mt-2 text-lg font-semibold">
            Day cards from the current portfolio
          </h4>
        </div>
        <p class="text-ink-secondary max-w-xl text-sm leading-6">
          Each day shows the planned module slots, estimated time, and warnings that matter before the block is built.
        </p>
      </div>

      <div class="mt-4 grid gap-3 lg:grid-cols-3">
        <article
          v-for="day in scheduledDays"
          :key="day.dayIndex || day.label"
          class="border-line-subtle bg-surface-elevated rounded-card border p-4"
        >
          <div class="flex items-start justify-between gap-3">
            <div>
              <h5 class="text-ink-primary text-base font-semibold">{{ day.label }}</h5>
              <p class="text-ink-muted mt-1 text-xs font-semibold">{{ dayTypeLabel(day) }}</p>
            </div>
            <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusClasses(day.timeLedger.status)">
              {{ day.timeLedger.estimatedMinutes }} / {{ day.timeLedger.budgetMinutes }} min
            </span>
          </div>

          <div class="mt-4 space-y-3">
            <div
              v-for="module in day.modules"
              :key="module.moduleId || `${day.label}-${module.order}`"
              class="rounded-control bg-surface-primary border-line-subtle border p-3"
            >
              <div class="flex flex-wrap items-center justify-between gap-2">
                <p class="text-ink-primary text-sm font-semibold">{{ module.title }}</p>
                <span class="bg-accent-primary-soft text-ink-primary rounded-full px-2 py-0.5 text-xs font-semibold">
                  {{ moduleSlotLabel(module.slot) }}
                </span>
              </div>
              <p class="text-ink-muted mt-2 text-xs leading-5">
                {{ humanize(module.purpose) }} / {{ humanize(module.intensityTier) }} /
                {{ module.estimatedMinutes }} min
              </p>
            </div>
          </div>

          <ul v-if="day.warnings.length || day.stressLedger.warnings.length" class="mt-4 space-y-1">
            <li
              v-for="warning in [...day.warnings, ...day.stressLedger.warnings]"
              :key="warning"
              class="text-status-warning text-xs leading-5"
            >
              {{ warning }}
            </li>
          </ul>
        </article>
      </div>
    </section>

    <section
      v-if="showStress"
      aria-labelledby="portfolio-stress-heading"
      class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-5"
    >
      <div>
        <p class="text-accent-primary text-xs font-semibold tracking-[0.16em] uppercase">Stress heatmap</p>
        <h4 id="portfolio-stress-heading" class="text-ink-primary mt-2 text-lg font-semibold">
          Where the current week spends recovery
        </h4>
      </div>

      <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
        <article
          v-for="bucket in stressBuckets"
          :key="bucket.label"
          class="border-line-subtle bg-surface-elevated rounded-card border p-4"
        >
          <div class="flex items-start justify-between gap-3">
            <h5 class="text-ink-primary text-sm font-semibold">{{ bucket.label }}</h5>
            <span
              class="rounded-full px-2.5 py-1 text-xs font-semibold"
              :class="statusClasses(strongestStatus(bucket.axes))"
            >
              {{ stressStatusLabel(bucket.axes) }}
            </span>
          </div>
          <div v-if="bucket.axes.length" class="mt-3 space-y-3">
            <div v-for="axis in bucket.axes" :key="axis.axis">
              <div class="flex items-center justify-between gap-3">
                <p class="text-ink-muted text-xs font-semibold">{{ humanize(axis.axis) }}</p>
                <p class="text-ink-primary text-xs font-semibold">{{ axis.load }} / {{ axis.budget }}</p>
              </div>
              <div class="bg-surface-muted mt-1 h-2 overflow-hidden rounded-full">
                <div
                  class="h-full rounded-full"
                  :class="statusClasses(axis.status)"
                  :style="{ width: `${stressRatio(axis)}%` }"
                />
              </div>
            </div>
          </div>
          <p v-else class="text-ink-muted mt-3 text-xs leading-5">{{ bucket.empty }}</p>
        </article>
      </div>
    </section>

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
