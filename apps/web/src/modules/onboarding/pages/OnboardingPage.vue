<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { UiButton } from '@/shared/ui'
import OnboardingChoiceGrid from '../components/OnboardingChoiceGrid.vue'
import OnboardingNumberField from '../components/OnboardingNumberField.vue'
import OnboardingProgress from '../components/OnboardingProgress.vue'
import OnboardingStepPanel from '../components/OnboardingStepPanel.vue'
import { useOnboardingSteps } from '../composables/useOnboardingSteps'
import {
  baseFocusOptions,
  bodyweightUnitOptions,
  compatibleSecondaryGoals,
  equipmentCategories,
  experienceLevelOptions,
  goalOptions,
  heightUnitOptions,
  mobilityCheckOptions,
  mobilityStatusOptions,
  mobilityTestInstructions,
  painAreaOptions,
  painOptions,
  priorSportOptions,
  readinessOptions,
  sorenessOptions,
  skillStatusKeys,
  skillStatusLabels,
  skillStatusMeasurements,
  skillStatusOptions,
  starterPlanOptions,
  targetSkillOptions,
  trainingDayOptions,
  trainingLocationOptions,
  weightedExperienceOptions,
  weightedMovementOptions,
} from '../data/onboardingOptions'
import { validateOnboardingStep } from '../services/onboardingService'
import { useOnboardingStore } from '../stores/onboardingStore'
import type { OnboardingFieldErrors, OnboardingStepId } from '../types'

const route = useRoute()
const router = useRouter()
const onboarding = useOnboardingStore()

type StepScrollTarget = {
  scrollIntoView?: (options?: { behavior?: 'smooth'; block?: 'start' }) => void
}

const activeStep = ref<OnboardingStepId>('context')
const activeStepTop = ref<StepScrollTarget | null>(null)
const clientErrors = ref<OnboardingFieldErrors>({})
const { activeIndex, canContinue, canGoBack, progressPercent, steps } = useOnboardingSteps(activeStep)
const currentErrors = computed(() => ({ ...clientErrors.value, ...onboarding.fieldErrors }))
const compatibleSecondaryGoalOptions = computed(() => {
  const allowedGoals = compatibleSecondaryGoals[onboarding.form.primaryGoal] ?? []

  return goalOptions.filter((option) => allowedGoals.includes(option.value))
})
const chosenSkills = computed(() =>
  targetSkillOptions
    .filter(
      (option) =>
        onboarding.form.targetSkills.includes(option.value) ||
        onboarding.form.longTermTargetSkills.includes(option.value),
    )
    .map((option) => option.label),
)
const activeRoadmapTracks = computed(() => [
  ...onboarding.form.roadmapSuggestions.unlocked_tracks,
  ...onboarding.form.roadmapSuggestions.bridge_tracks,
])
const activeRoadmapSkillValues = computed(() => activeRoadmapTracks.value.map((track) => track.skill))
const activeRoadmapOptions = computed(() =>
  activeRoadmapTracks.value.map((track) => ({
    description: `${track.reason} Next gate: ${track.next_gate}`,
    label: track.label,
    meta: onboarding.form.roadmapSuggestions.unlocked_tracks.some((unlocked) => unlocked.skill === track.skill)
      ? 'Ready now'
      : 'Bridge',
    value: track.skill,
  })),
)
const longTermRoadmapOptions = computed(() =>
  targetSkillOptions
    .filter((option) => !onboarding.form.targetSkills.includes(option.value))
    .map((option) => {
      const suggested = [
        ...onboarding.form.roadmapSuggestions.long_term_tracks,
        ...onboarding.form.roadmapSuggestions.deferred_tracks,
      ].find((track) => track.skill === option.value)

      return suggested
        ? {
            description: `${suggested.reason} ${suggested.next_gate}`,
            label: suggested.label,
            meta: 'Later',
            value: suggested.skill,
          }
        : option
    }),
)
const suggestedBaseFocusOptions = computed(() => {
  const suggested = onboarding.form.roadmapSuggestions.base_focus_areas

  if (!suggested.length) {
    return baseFocusOptions
  }

  return baseFocusOptions.filter((option) => suggested.includes(option.value))
})
const selectedTargetSkillOptions = computed(() =>
  activeRoadmapOptions.value.filter((option) => onboarding.form.targetSkills.includes(option.value)),
)
const primaryTargetLabel = computed(
  () =>
    targetSkillOptions.find((option) => option.value === onboarding.form.primaryTargetSkill)?.label ??
    'No primary roadmap yet',
)
const chosenEquipmentCount = computed(() => onboarding.form.availableEquipment.length)
const contextSummary = computed(() => {
  const bodyweight = onboarding.form.currentBodyweightValue
    ? `${onboarding.form.currentBodyweightValue}${onboarding.form.bodyweightUnit}`
    : 'Bodyweight open'
  const height = onboarding.form.heightValue
    ? `${onboarding.form.heightValue}${onboarding.form.heightUnit}`
    : 'height open'

  return `${bodyweight}, ${height}`
})
const weightedSkillSelected = computed(() =>
  [onboarding.form.primaryTargetSkill, ...onboarding.form.targetSkills].some((skill) => skill.startsWith('weighted_')),
)
const chosenSchedule = computed(() =>
  onboarding.form.preferredTrainingDays.length
    ? `${onboarding.form.preferredTrainingDays.length} days, up to ${onboarding.form.preferredSessionMinutes || '...'} minutes`
    : 'Schedule not set',
)
const placementPreview = computed(() => {
  const push = `${onboarding.form.currentLevelTests.pushUpMaxReps || 0} push-ups`
  const pull = `${onboarding.form.currentLevelTests.pullUpMaxReps || 0} pull-ups`
  const dip = `${onboarding.form.currentLevelTests.dipMaxReps || 0} dips`
  const mobilityFlags = Object.entries(onboarding.form.mobilityChecks)
    .filter(([, status]) => ['limited', 'blocked', 'painful'].includes(status))
    .map(([key]) => mobilityCheckOptions.find((option) => option.value === key)?.label ?? key)

  return {
    focus: onboarding.form.baseFocusAreas.length
      ? onboarding.form.baseFocusAreas
          .map((value) => baseFocusOptions.find((option) => option.value === value)?.label ?? value)
          .slice(0, 3)
          .join(', ')
      : 'Base focus not selected',
    mobility: mobilityFlags.length ? mobilityFlags.slice(0, 3).join(', ') : 'No major position blockers marked',
    primary: primaryTargetLabel.value,
    tests: `${push}, ${pull}, ${dip}`,
  }
})
const redirectPath = computed(() =>
  typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/app')
    ? route.query.redirect
    : '/app/dashboard',
)

watch(
  () => onboarding.form.primaryGoal,
  () => {
    const allowedGoals = compatibleSecondaryGoals[onboarding.form.primaryGoal] ?? []

    onboarding.form.secondaryGoals = onboarding.form.secondaryGoals
      .filter((goal) => allowedGoals.includes(goal))
      .slice(0, 2)
  },
)

watch(
  () => onboarding.form.targetSkills,
  () => {
    if (!onboarding.form.targetSkills.includes(onboarding.form.primaryTargetSkill)) {
      onboarding.form.primaryTargetSkill = onboarding.form.targetSkills[0] ?? ''
    }

    onboarding.form.secondaryTargetSkills = onboarding.form.secondaryTargetSkills
      .filter((skill) => skill !== onboarding.form.primaryTargetSkill && onboarding.form.targetSkills.includes(skill))
      .slice(0, 2)
  },
  { deep: true },
)

watch(
  () => onboarding.form.roadmapSuggestions,
  () => {
    onboarding.form.targetSkills = onboarding.form.targetSkills.filter((skill) =>
      activeRoadmapSkillValues.value.includes(skill),
    )

    onboarding.form.secondaryTargetSkills = onboarding.form.secondaryTargetSkills.filter((skill) =>
      onboarding.form.targetSkills.includes(skill),
    )

    if (onboarding.form.baseFocusAreas.length === 0 && onboarding.form.roadmapSuggestions.base_focus_areas.length) {
      onboarding.form.baseFocusAreas = [...onboarding.form.roadmapSuggestions.base_focus_areas].slice(0, 4)
    }
  },
  { deep: true },
)

watch(
  () => onboarding.form.primaryTargetSkill,
  () => {
    onboarding.form.secondaryTargetSkills = onboarding.form.secondaryTargetSkills.filter(
      (skill) => skill !== onboarding.form.primaryTargetSkill,
    )
  },
)

watch(activeStep, () => {
  void scrollToActiveStepTop()
})

onMounted(() => {
  void onboarding.load()
})

function errorFor(key: string): string | undefined {
  return currentErrors.value[key]
}

const lockedStepIds = computed(() =>
  steps.filter((_, index) => firstInvalidStepBeforeIndex(index) !== null).map((step) => step.id),
)

function selectStep(step: OnboardingStepId): void {
  const targetIndex = steps.findIndex((candidate) => candidate.id === step)
  const blockedByStep = firstInvalidStepBeforeIndex(targetIndex)

  if (blockedByStep) {
    activeStep.value = blockedByStep.id
    clientErrors.value = validateOnboardingStep(onboarding.form, blockedByStep.id)

    return
  }

  clientErrors.value = {}
  activeStep.value = step
}

async function scrollToActiveStepTop(): Promise<void> {
  await nextTick()

  if (typeof activeStepTop.value?.scrollIntoView === 'function') {
    activeStepTop.value.scrollIntoView({ behavior: 'smooth', block: 'start' })
  }
}

async function goBack(): Promise<void> {
  if (!canGoBack.value) {
    return
  }

  await onboarding.saveDraft()
  clientErrors.value = {}
  activeStep.value = steps[activeIndex.value - 1]?.id ?? 'context'
}

async function goNext(): Promise<void> {
  const errors = validateOnboardingStep(onboarding.form, activeStep.value)
  clientErrors.value = errors

  if (Object.keys(errors).length > 0) {
    return
  }

  const saved = await onboarding.saveDraft()

  if (!saved || !canContinue.value) {
    return
  }

  clientErrors.value = {}
  activeStep.value = steps[activeIndex.value + 1]?.id ?? 'starter'
}

async function completeOnboarding(): Promise<void> {
  const firstInvalidStep = steps.find(
    (step) => Object.keys(validateOnboardingStep(onboarding.form, step.id)).length > 0,
  )

  if (firstInvalidStep) {
    activeStep.value = firstInvalidStep.id
    clientErrors.value = validateOnboardingStep(onboarding.form, firstInvalidStep.id)

    return
  }

  clientErrors.value = {}

  if (await onboarding.complete()) {
    await router.push(redirectPath.value)
  }
}

function addWeightedMovement(): void {
  onboarding.form.weightedBaselines.movements = [
    ...onboarding.form.weightedBaselines.movements,
    { externalLoadValue: '', movement: 'weighted_pull_up', reps: '', rir: '' },
  ].slice(0, 4)
}

function removeWeightedMovement(index: number): void {
  onboarding.form.weightedBaselines.movements = onboarding.form.weightedBaselines.movements.filter(
    (_, itemIndex) => itemIndex !== index,
  )
}

function firstInvalidStepBeforeIndex(index: number): { id: OnboardingStepId } | null {
  if (index <= 0) {
    return null
  }

  return (
    steps.slice(0, index).find((step) => Object.keys(validateOnboardingStep(onboarding.form, step.id)).length > 0) ??
    null
  )
}
</script>

<template>
  <main class="bg-surface-primary min-h-screen px-4 py-5 sm:px-6 lg:px-8">
    <div class="mx-auto flex w-full max-w-7xl flex-col gap-6">
      <header class="border-line-subtle bg-surface-elevated shadow-card rounded-card overflow-hidden border">
        <div class="grid gap-0 lg:grid-cols-[minmax(0,1fr)_24rem]">
          <div class="p-5 sm:p-8">
            <div class="flex flex-wrap items-center gap-3">
              <p class="text-accent-primary text-xs font-semibold tracking-[0.18em] uppercase">Leverly setup</p>
              <span class="bg-accent-primary-soft text-ink-primary rounded-full px-3 py-1 text-xs font-semibold">
                {{ progressPercent }}% mapped
              </span>
            </div>
            <h1 class="text-ink-primary mt-4 max-w-3xl text-3xl font-semibold tracking-normal sm:text-4xl">
              Level up your skills.
            </h1>
            <p class="text-ink-secondary mt-4 max-w-3xl text-base leading-7">
              Leverly turns your data into achievable roadmaps and helps you reach your goals.
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
              <UiButton variant="secondary" @click="onboarding.saveDraft()">Save draft</UiButton>
            </div>
          </div>

          <aside class="border-line-subtle bg-surface-muted/70 border-t p-5 sm:p-8 lg:border-t-0 lg:border-l">
            <p class="text-ink-muted text-xs font-semibold tracking-[0.18em] uppercase">Current map</p>
            <dl class="mt-4 grid gap-3">
              <div class="rounded-control bg-surface-primary border-line-subtle border p-3">
                <dt class="text-ink-muted text-xs font-semibold">Context</dt>
                <dd class="text-ink-primary mt-1 text-sm font-semibold">
                  {{ contextSummary }}
                </dd>
              </div>
              <div class="rounded-control bg-surface-primary border-line-subtle border p-3">
                <dt class="text-ink-muted text-xs font-semibold">Equipment</dt>
                <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ chosenEquipmentCount }} tools selected</dd>
              </div>
              <div class="rounded-control bg-surface-primary border-line-subtle border p-3">
                <dt class="text-ink-muted text-xs font-semibold">Roadmap</dt>
                <dd class="text-ink-primary mt-1 text-sm font-semibold">
                  {{ chosenSkills.length ? chosenSkills.slice(0, 2).join(', ') : chosenSchedule }}
                </dd>
              </div>
            </dl>
          </aside>
        </div>
      </header>

      <OnboardingProgress
        :active-step="activeStep"
        :locked-step-ids="lockedStepIds"
        :steps="steps"
        @select="selectStep"
      />

      <div
        v-if="onboarding.loadError"
        class="border-status-danger/30 bg-status-danger/10 text-status-danger rounded-card border p-4 text-sm"
      >
        {{ onboarding.loadError }}
      </div>
      <div
        v-if="onboarding.saveError"
        class="border-status-danger/30 bg-status-danger/10 text-status-danger rounded-card border p-4 text-sm"
      >
        {{ onboarding.saveError }}
      </div>

      <form ref="activeStepTop" class="scroll-mt-6 space-y-5" novalidate @submit.prevent="completeOnboarding">
        <OnboardingStepPanel
          v-if="activeStep === 'context'"
          eyebrow="Step 1"
          title="Start with the athlete behind the skills."
          description="Give Leverly the essentials so your first roadmap feels personal, realistic, and worth training for."
        >
          <div class="space-y-6">
            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-4">
              <OnboardingNumberField
                id="onboarding-age"
                v-model="onboarding.form.ageYears"
                :error="errorFor('ageYears')"
                label="Age"
                :max="90"
                :min="13"
                placeholder="29"
                suffix="years"
              />
              <OnboardingNumberField
                id="onboarding-training-age"
                v-model="onboarding.form.trainingAgeMonths"
                :error="errorFor('trainingAgeMonths')"
                label="Training age"
                :max="1200"
                :min="0"
                placeholder="18"
                suffix="months"
              />
              <OnboardingNumberField
                id="onboarding-bodyweight"
                v-model="onboarding.form.currentBodyweightValue"
                :error="errorFor('currentBodyweightValue')"
                label="Current bodyweight"
                :max="400"
                :min="20"
                placeholder="72.5"
                :suffix="onboarding.form.bodyweightUnit"
              />
              <OnboardingNumberField
                id="onboarding-height"
                v-model="onboarding.form.heightValue"
                :error="errorFor('heightValue')"
                label="Height"
                :max="onboarding.form.heightUnit === 'in' ? 100 : 250"
                :min="onboarding.form.heightUnit === 'in' ? 36 : 90"
                placeholder="178"
                :suffix="onboarding.form.heightUnit"
              />
            </div>
            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_14rem]">
              <OnboardingChoiceGrid
                v-model="onboarding.form.bodyweightUnit"
                columns="compact"
                label="Bodyweight unit"
                name="onboarding-bodyweight-unit"
                :options="bodyweightUnitOptions"
              />
              <OnboardingChoiceGrid
                v-model="onboarding.form.heightUnit"
                columns="compact"
                label="Height unit"
                name="onboarding-height-unit"
                :options="heightUnitOptions"
              />
            </div>
            <OnboardingChoiceGrid
              v-model="onboarding.form.priorSportBackground"
              :error="errorFor('priorSportBackground')"
              help="Choose up to four. Pick 'None yet' if you are starting without a useful carryover."
              label="Training background"
              :max-selections="4"
              multiple
              name="onboarding-prior-sport"
              :options="priorSportOptions"
            />
            <div class="space-y-3">
              <OnboardingChoiceGrid
                v-model="onboarding.form.experienceLevel"
                label="Calisthenics level"
                name="onboarding-experience"
                :options="experienceLevelOptions"
              />
            </div>
          </div>
        </OnboardingStepPanel>

        <OnboardingStepPanel
          v-if="activeStep === 'equipment'"
          eyebrow="Step 2"
          title="Map the places and tools you can rely on."
          description="Exercise recommendations and progression options will be based on the tools you actually have available."
        >
          <div class="space-y-6">
            <OnboardingChoiceGrid
              v-model="onboarding.form.trainingLocations"
              :error="errorFor('trainingLocations')"
              label="Training places"
              multiple
              name="onboarding-training-locations"
              :options="trainingLocationOptions"
            />
            <section v-for="category in equipmentCategories" :key="category.id" class="space-y-3">
              <OnboardingChoiceGrid
                v-model="onboarding.form.availableEquipment"
                :label="category.title"
                multiple
                :name="`onboarding-equipment-${category.id}`"
                :options="category.items"
              />
            </section>
          </div>
        </OnboardingStepPanel>

        <OnboardingStepPanel
          v-if="activeStep === 'level'"
          eyebrow="Step 4"
          title="Test your current baseline."
          description="These quick tests help Leverly create a program specific to you."
        >
          <section class="space-y-4">
            <div>
              <h3 class="text-ink-primary text-lg font-semibold">Required baseline tests</h3>
              <p class="text-ink-secondary mt-1 text-sm leading-6">
                Use clean, repeatable reps. Enter 0 when you cannot perform a movement yet so the first block starts
                from the right bridge.
              </p>
            </div>
            <div class="grid gap-4 xl:grid-cols-2">
              <div class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4">
                <div class="mb-4">
                  <h4 class="text-ink-primary text-base font-semibold">Push-up strength</h4>
                  <p class="text-ink-muted mt-1 text-sm leading-5">Strict floor reps with a rigid bodyline.</p>
                </div>
                <div class="max-w-sm">
                  <OnboardingNumberField
                    id="onboarding-push-ups"
                    v-model="onboarding.form.currentLevelTests.pushUpMaxReps"
                    :error="errorFor('currentLevelTests.pushUpMaxReps')"
                    label="Max strict reps"
                    :max="200"
                    :min="0"
                    placeholder="18"
                    suffix="reps"
                  />
                </div>
              </div>

              <div class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4">
                <div class="mb-4">
                  <h4 class="text-ink-primary text-base font-semibold">Pull-up strength</h4>
                  <p class="text-ink-muted mt-1 text-sm leading-5">
                    Strict reps from a dead hang or 0 if not there yet.
                  </p>
                </div>
                <div class="max-w-sm">
                  <OnboardingNumberField
                    id="onboarding-pull-ups"
                    v-model="onboarding.form.currentLevelTests.pullUpMaxReps"
                    :error="errorFor('currentLevelTests.pullUpMaxReps')"
                    label="Max strict reps"
                    :max="100"
                    :min="0"
                    placeholder="4"
                    suffix="reps"
                  />
                </div>
              </div>

              <div class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4">
                <div class="mb-4">
                  <h4 class="text-ink-primary text-base font-semibold">Dip strength</h4>
                  <p class="text-ink-muted mt-1 text-sm leading-5">Clean parallel-bar reps with controlled depth.</p>
                </div>
                <div class="max-w-sm">
                  <OnboardingNumberField
                    id="onboarding-dip-reps"
                    v-model="onboarding.form.currentLevelTests.dipMaxReps"
                    :error="errorFor('currentLevelTests.dipMaxReps')"
                    label="Dip reps"
                    :max="100"
                    :min="0"
                    placeholder="6"
                    suffix="reps"
                  />
                </div>
              </div>

              <div class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4">
                <div class="mb-4">
                  <h4 class="text-ink-primary text-base font-semibold">Legs and bodyline</h4>
                  <p class="text-ink-muted mt-1 text-sm leading-5">
                    Barbell squat capacity and trunk control for leg and bodyline placement.
                  </p>
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                  <OnboardingNumberField
                    id="onboarding-squat-load"
                    v-model="onboarding.form.currentLevelTests.squatBarbellLoadValue"
                    :error="errorFor('currentLevelTests.squatBarbellLoadValue')"
                    label="Barbell squat load"
                    :max="1000"
                    :min="0"
                    placeholder="100"
                    :suffix="onboarding.form.bodyweightUnit"
                  />
                  <OnboardingNumberField
                    id="onboarding-squat-load-reps"
                    v-model="onboarding.form.currentLevelTests.squatBarbellReps"
                    :error="errorFor('currentLevelTests.squatBarbellReps')"
                    label="Reps at that load"
                    :max="30"
                    :min="0"
                    placeholder="5"
                    suffix="reps"
                  />
                  <OnboardingNumberField
                    id="onboarding-hollow-hold"
                    v-model="onboarding.form.currentLevelTests.hollowHoldSeconds"
                    :error="errorFor('currentLevelTests.hollowHoldSeconds')"
                    label="Hollow body hold"
                    :max="600"
                    :min="0"
                    placeholder="35"
                    suffix="sec"
                  />
                </div>
              </div>
            </div>
          </section>

          <section class="mt-7 space-y-4">
            <div>
              <h3 class="text-ink-primary text-lg font-semibold">Optional skill progressions</h3>
              <p class="text-ink-secondary mt-1 text-sm leading-6">
                Add only skills you have actually tested. Pick the closest progression, then add reps or hold time if
                you know it.
              </p>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
              <div
                v-for="skill in skillStatusKeys"
                :key="skill"
                class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4"
              >
                <label class="block space-y-2">
                  <span class="text-ink-primary text-sm font-semibold">{{ skillStatusLabels[skill] }}</span>
                  <select
                    v-model="onboarding.form.skillStatuses[skill].status"
                    class="border-line-subtle bg-surface-elevated text-ink-primary rounded-control focus:border-accent-primary focus:ring-accent-primary/20 min-h-12 w-full border px-4 py-3 text-base transition outline-none focus:ring-4"
                  >
                    <option v-for="option in skillStatusOptions[skill]" :key="option.value" :value="option.value">
                      {{ option.label }}
                    </option>
                  </select>
                </label>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                  <OnboardingNumberField
                    v-if="skillStatusMeasurements[skill].reps"
                    :id="`onboarding-${skill}-reps`"
                    v-model="onboarding.form.skillStatuses[skill].maxStrictReps"
                    :label="skillStatusMeasurements[skill].reps"
                    :max="100"
                    :min="0"
                    placeholder="0"
                  />
                  <OnboardingNumberField
                    v-if="skillStatusMeasurements[skill].hold"
                    :id="`onboarding-${skill}-hold`"
                    v-model="onboarding.form.skillStatuses[skill].bestHoldSeconds"
                    :label="skillStatusMeasurements[skill].hold"
                    :max="600"
                    :min="0"
                    placeholder="0"
                    suffix="sec"
                  />
                </div>
              </div>
            </div>
          </section>
        </OnboardingStepPanel>

        <OnboardingStepPanel
          v-if="activeStep === 'mobility'"
          eyebrow="Step 3"
          title="What are your current mobility limits?"
          description="Test each position and choose the status that best matches what you can do today."
        >
          <div class="space-y-6">
            <div
              v-for="check in mobilityCheckOptions"
              :key="check.value"
              class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4 sm:p-5"
            >
              <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(22rem,32rem)]">
                <div class="space-y-3">
                  <div>
                    <h3 class="text-ink-primary text-base font-semibold">{{ check.label }}</h3>
                    <p class="text-ink-secondary mt-1 text-sm leading-6">{{ check.description }}</p>
                  </div>
                  <div class="bg-surface-muted border-line-subtle rounded-control border p-3">
                    <p class="text-ink-primary text-xs font-semibold tracking-[0.14em] uppercase">How to test</p>
                    <p class="text-ink-secondary mt-2 text-sm leading-6">
                      {{ mobilityTestInstructions[check.value].test }}
                    </p>
                    <p class="text-ink-muted mt-2 text-xs leading-5">
                      {{ mobilityTestInstructions[check.value].clear }}
                    </p>
                  </div>
                </div>

                <fieldset class="space-y-3" :aria-label="`${check.label} status`">
                  <legend class="text-ink-primary text-sm font-semibold">Current status</legend>
                  <div class="grid gap-2 sm:grid-cols-5 xl:grid-cols-1">
                    <label
                      v-for="status in mobilityStatusOptions"
                      :key="status.value"
                      class="rounded-control focus-within:ring-accent-primary focus-within:ring-offset-surface-primary flex min-h-11 cursor-pointer items-center justify-center border px-3 py-2 text-center text-sm font-semibold transition duration-200 focus-within:ring-2 focus-within:ring-offset-2"
                      :class="
                        onboarding.form.mobilityChecks[check.value] === status.value
                          ? 'border-accent-primary bg-accent-primary-soft text-ink-primary shadow-card'
                          : 'border-line-subtle bg-surface-elevated text-ink-secondary hover:border-line-strong hover:bg-surface-overlay hover:shadow-card-soft'
                      "
                    >
                      <input
                        v-model="onboarding.form.mobilityChecks[check.value]"
                        class="sr-only"
                        :name="`onboarding-mobility-${check.value}`"
                        type="radio"
                        :value="status.value"
                      />
                      <span>{{ status.label }}</span>
                    </label>
                  </div>
                </fieldset>
              </div>
            </div>
            <p v-if="errorFor('mobilityChecks')" class="text-status-danger text-sm leading-5">
              {{ errorFor('mobilityChecks') }}
            </p>
          </div>
        </OnboardingStepPanel>

        <OnboardingStepPanel
          v-if="activeStep === 'availability'"
          eyebrow="Step 5"
          title="Set the schedule Leverly can actually use."
          description="The first plan should fit your week before it tries to be ambitious. Choose training days, session ceiling, and max sessions per week."
        >
          <div class="space-y-6">
            <OnboardingChoiceGrid
              v-model="onboarding.form.preferredTrainingDays"
              :error="errorFor('preferredTrainingDays')"
              label="Training days"
              multiple
              name="onboarding-training-days"
              :options="trainingDayOptions"
            />
            <div class="grid gap-5 md:grid-cols-2">
              <OnboardingNumberField
                id="onboarding-session-minutes"
                v-model="onboarding.form.preferredSessionMinutes"
                :error="errorFor('preferredSessionMinutes')"
                label="Up to minutes"
                :max="240"
                :min="10"
                placeholder="45"
                suffix="min"
              />
              <OnboardingNumberField
                id="onboarding-weekly-sessions"
                v-model="onboarding.form.weeklySessionGoal"
                :error="errorFor('weeklySessionGoal')"
                label="Max sessions per week"
                :max="14"
                :min="1"
                placeholder="3"
              />
            </div>
          </div>
        </OnboardingStepPanel>

        <OnboardingStepPanel
          v-if="activeStep === 'roadmap'"
          eyebrow="Step 6"
          title="Choose from the roadmap your assessment unlocked."
          description="Active targets are limited to skills that are ready now or make sense as a bridge. Bigger aspirations can stay visible without forcing the first plan to skip foundations."
        >
          <div class="space-y-6">
            <div class="border-line-subtle bg-surface-primary rounded-card border p-5">
              <div class="flex flex-wrap items-center gap-3">
                <span class="bg-accent-primary-soft text-ink-primary rounded-full px-3 py-1 text-xs font-semibold">
                  {{ onboarding.form.roadmapSuggestions.level }}
                </span>
                <p class="text-ink-primary text-sm font-semibold">
                  {{ onboarding.form.roadmapSuggestions.summary }}
                </p>
              </div>
              <ul
                v-if="onboarding.form.roadmapSuggestions.body_context.notes.length"
                class="text-ink-secondary mt-4 grid gap-2 text-sm leading-6"
              >
                <li v-for="note in onboarding.form.roadmapSuggestions.body_context.notes" :key="note">
                  {{ note }}
                </li>
              </ul>
            </div>

            <OnboardingChoiceGrid
              v-model="onboarding.form.primaryGoal"
              :error="errorFor('primaryGoal')"
              label="Primary goal"
              name="onboarding-primary-goal"
              :options="goalOptions"
            />
            <OnboardingChoiceGrid
              v-model="onboarding.form.secondaryGoals"
              :error="errorFor('secondaryGoals')"
              help="Choose up to two supporting goals that fit the main outcome."
              label="Supporting goals"
              :max-selections="2"
              multiple
              name="onboarding-secondary-goals"
              :options="compatibleSecondaryGoalOptions"
            />
            <OnboardingChoiceGrid
              v-model="onboarding.form.targetSkills"
              :error="errorFor('targetSkills')"
              help="Choose up to three active targets from the ready-now and bridge recommendations."
              label="Active roadmap targets"
              :max-selections="3"
              multiple
              name="onboarding-target-skills"
              :options="activeRoadmapOptions"
            />
            <OnboardingChoiceGrid
              v-if="selectedTargetSkillOptions.length"
              v-model="onboarding.form.primaryTargetSkill"
              :error="errorFor('primaryTargetSkill')"
              help="Pick one target as the main priority for the first training block."
              label="Primary roadmap"
              name="onboarding-primary-target-skill"
              :options="selectedTargetSkillOptions"
            />
            <OnboardingChoiceGrid
              v-if="selectedTargetSkillOptions.length > 1"
              v-model="onboarding.form.secondaryTargetSkills"
              :error="errorFor('secondaryTargetSkills')"
              help="Choose up to two lighter exposures that should not compete with the main roadmap."
              label="Secondary exposure"
              :max-selections="2"
              multiple
              name="onboarding-secondary-target-skills"
              :options="
                selectedTargetSkillOptions.filter((option) => option.value !== onboarding.form.primaryTargetSkill)
              "
            />
            <OnboardingChoiceGrid
              v-model="onboarding.form.longTermTargetSkills"
              :error="errorFor('longTermTargetSkills')"
              help="Keep big skills visible here without making them the first block's main target."
              label="Long-term aspirations"
              :max-selections="8"
              multiple
              name="onboarding-long-term-targets"
              :options="longTermRoadmapOptions"
            />
            <OnboardingChoiceGrid
              v-model="onboarding.form.baseFocusAreas"
              :error="errorFor('baseFocusAreas')"
              help="These guide regressions and support work behind the selected roadmap."
              label="Base-development focus"
              :max-selections="4"
              multiple
              name="onboarding-base-focus"
              :options="suggestedBaseFocusOptions"
            />
          </div>
        </OnboardingStepPanel>

        <OnboardingStepPanel
          v-if="activeStep === 'readiness'"
          eyebrow="Step 7"
          title="Add today’s recovery and pain signal."
          description="This does not diagnose anything. It only keeps the first recommendations conservative when pain, soreness, or poor readiness should change the plan."
        >
          <div class="space-y-6">
            <div class="grid gap-5 xl:grid-cols-3">
              <OnboardingChoiceGrid
                v-model="onboarding.form.readinessRating"
                columns="compact"
                label="Readiness"
                name="onboarding-readiness"
                :options="readinessOptions"
              />
              <OnboardingChoiceGrid
                v-model="onboarding.form.sleepQuality"
                columns="compact"
                label="Sleep quality"
                name="onboarding-sleep"
                :options="readinessOptions"
              />
              <OnboardingChoiceGrid
                v-model="onboarding.form.sorenessLevel"
                columns="compact"
                label="Soreness"
                name="onboarding-soreness"
                :options="sorenessOptions"
              />
            </div>

            <OnboardingChoiceGrid
              v-model="onboarding.form.painLevel"
              columns="compact"
              label="Pain right now"
              name="onboarding-pain"
              :options="painOptions"
            />
            <OnboardingChoiceGrid
              v-model="onboarding.form.painAreas"
              :error="errorFor('painAreas')"
              label="Pain or limitation areas"
              multiple
              name="onboarding-pain-areas"
              :options="painAreaOptions"
            />
            <label class="block space-y-2">
              <span class="text-ink-primary text-sm font-semibold">Notes for limitations</span>
              <textarea
                v-model="onboarding.form.painNotes"
                class="border-line-subtle bg-surface-primary text-ink-primary rounded-control focus:border-accent-primary focus:ring-accent-primary/20 min-h-28 w-full resize-y border px-4 py-3 text-base transition outline-none focus:ring-4"
                placeholder="Example: wrists need longer warm-up before handstand or planche work."
              />
            </label>
          </div>
        </OnboardingStepPanel>

        <OnboardingStepPanel
          v-if="activeStep === 'starter'"
          eyebrow="Step 8"
          title="Review the first placement."
          description="This is the shape Leverly can start from before the exercise catalog and progression tree are attached."
        >
          <div class="mb-6 grid gap-3 lg:grid-cols-4">
            <div class="border-line-subtle bg-surface-primary rounded-card border p-4">
              <p class="text-ink-muted text-xs font-semibold">Primary roadmap</p>
              <p class="text-ink-primary mt-2 text-sm font-semibold">{{ placementPreview.primary }}</p>
            </div>
            <div class="border-line-subtle bg-surface-primary rounded-card border p-4">
              <p class="text-ink-muted text-xs font-semibold">Baseline signal</p>
              <p class="text-ink-primary mt-2 text-sm font-semibold">{{ placementPreview.tests }}</p>
            </div>
            <div class="border-line-subtle bg-surface-primary rounded-card border p-4">
              <p class="text-ink-muted text-xs font-semibold">Base support</p>
              <p class="text-ink-primary mt-2 text-sm font-semibold">{{ placementPreview.focus }}</p>
            </div>
            <div class="border-line-subtle bg-surface-primary rounded-card border p-4">
              <p class="text-ink-muted text-xs font-semibold">Safety limiters</p>
              <p class="text-ink-primary mt-2 text-sm font-semibold">{{ placementPreview.mobility }}</p>
            </div>
          </div>

          <div
            v-if="weightedSkillSelected || onboarding.form.weightedBaselines.experience !== 'none'"
            class="mb-6 space-y-4"
          >
            <OnboardingChoiceGrid
              v-model="onboarding.form.weightedBaselines.experience"
              columns="compact"
              label="Weighted calisthenics experience"
              name="onboarding-weighted-experience"
              :options="weightedExperienceOptions"
            />
            <div class="space-y-3">
              <div class="flex flex-wrap items-center justify-between gap-3">
                <h3 class="text-ink-primary text-sm font-semibold">Recent weighted test sets</h3>
                <UiButton variant="secondary" type="button" @click="addWeightedMovement">Add test set</UiButton>
              </div>
              <div
                v-for="(movement, index) in onboarding.form.weightedBaselines.movements"
                :key="index"
                class="border-line-subtle bg-surface-primary rounded-card grid gap-3 border p-4 md:grid-cols-[1.2fr_1fr_1fr_1fr_auto]"
              >
                <label class="block space-y-2">
                  <span class="text-ink-primary text-sm font-semibold">Movement</span>
                  <select
                    v-model="movement.movement"
                    class="border-line-subtle bg-surface-elevated text-ink-primary rounded-control focus:border-accent-primary focus:ring-accent-primary/20 min-h-12 w-full border px-4 py-3 text-base transition outline-none focus:ring-4"
                  >
                    <option v-for="option in weightedMovementOptions" :key="option.value" :value="option.value">
                      {{ option.label }}
                    </option>
                  </select>
                </label>
                <OnboardingNumberField
                  :id="`onboarding-weighted-load-${index}`"
                  v-model="movement.externalLoadValue"
                  label="Added load"
                  :max="400"
                  :min="0"
                  placeholder="10"
                  :suffix="onboarding.form.weightedBaselines.unit"
                />
                <OnboardingNumberField
                  :id="`onboarding-weighted-reps-${index}`"
                  v-model="movement.reps"
                  label="Reps"
                  :max="30"
                  :min="1"
                  placeholder="5"
                />
                <OnboardingNumberField
                  :id="`onboarding-weighted-rir-${index}`"
                  v-model="movement.rir"
                  label="RIR"
                  :max="10"
                  :min="0"
                  placeholder="2"
                />
                <UiButton class="self-end" variant="secondary" type="button" @click="removeWeightedMovement(index)">
                  Remove
                </UiButton>
              </div>
            </div>
          </div>

          <OnboardingChoiceGrid
            v-model="onboarding.form.starterPlanKey"
            :error="errorFor('starterPlanKey')"
            label="Starter plan"
            name="onboarding-starter-plan"
            :options="starterPlanOptions"
          />
        </OnboardingStepPanel>

        <div
          class="border-line-subtle bg-surface-elevated shadow-card rounded-card flex flex-col gap-3 border p-4 sm:flex-row sm:items-center sm:justify-between"
        >
          <p class="text-ink-secondary text-sm leading-6">
            {{
              onboarding.saveState === 'saving' ? 'Saving your setup...' : 'Drafts are saved as you move through setup.'
            }}
          </p>
          <div class="flex flex-col gap-2 sm:flex-row">
            <UiButton variant="secondary" :disabled="!canGoBack || onboarding.saveState === 'saving'" @click="goBack">
              Back
            </UiButton>
            <UiButton v-if="canContinue" :disabled="onboarding.saveState === 'saving'" size="lg" @click="goNext">
              Save and continue
            </UiButton>
            <UiButton v-else :disabled="onboarding.saveState === 'saving'" size="lg" type="submit">
              Complete onboarding
            </UiButton>
          </div>
        </div>
      </form>
    </div>
  </main>
</template>
