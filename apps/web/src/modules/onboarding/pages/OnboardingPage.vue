<script setup lang="ts">
import { computed, nextTick, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { requiredGoalModulesForGoal } from '@/modules/roadmap'
import type { RoadmapGoalCandidate } from '@/modules/roadmap'
import { UiButton } from '@/shared/ui'
import OnboardingChoiceGrid from '../components/OnboardingChoiceGrid.vue'
import OnboardingModulesStep from '../components/OnboardingModulesStep.vue'
import OnboardingNumberField from '../components/OnboardingNumberField.vue'
import OnboardingProgress from '../components/OnboardingProgress.vue'
import OnboardingRoadmapReviewStep from '../components/OnboardingRoadmapReviewStep.vue'
import OnboardingStepPanel from '../components/OnboardingStepPanel.vue'
import { useOnboardingSteps } from '../composables/useOnboardingSteps'
import {
  baseFocusOptions,
  bodyweightUnitOptions,
  equipmentCategories,
  experienceLevelOptions,
  heightUnitOptions,
  mobilityCheckOptions,
  mobilityStatusOptions,
  mobilityTestInstructions,
  painAreaOptions,
  painOptions,
  priorSportOptions,
  readinessOptions,
  sorenessOptions,
  targetSkillOptions,
  trainingDayOptions,
  trainingLocationOptions,
} from '../data/onboardingOptions'
import { hasCompleteBarbellSquatData, validateOnboardingStep } from '../services/onboardingService'
import { useOnboardingStore } from '../stores/onboardingStore'
import type { ChoiceOption, OnboardingFieldErrors, OnboardingStepId } from '../types'

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
  ...onboarding.form.roadmapSuggestions.unlockedTracks,
  ...onboarding.form.roadmapSuggestions.bridgeTracks,
])
const activeRoadmapOptions = computed(() =>
  activeRoadmapTracks.value.map((track) => ({
    description: `${track.reason} Next gate: ${track.nextGate}`,
    label: track.label,
    meta: onboarding.form.roadmapSuggestions.unlockedTracks.some((unlocked) => unlocked.skill === track.skill)
      ? 'Ready now'
      : 'Bridge',
    value: track.skill,
  })),
)
const candidateGroups = computed(() => onboarding.form.roadmapSuggestions.goalCandidates)
const hasGoalCandidates = computed(() =>
  [
    candidateGroups.value.primary,
    candidateGroups.value.secondary,
    candidateGroups.value.accessories,
    candidateGroups.value.future,
    candidateGroups.value.foundation,
  ].some((group) => group.length > 0),
)
const primaryRoadmapOptions = computed(() =>
  candidateGroups.value.primary.length
    ? candidateGroups.value.primary.map(candidateToOption)
    : activeRoadmapOptions.value.length
      ? activeRoadmapOptions.value
      : targetSkillOptions,
)
const activeRoadmapSkillValues = computed(() => primaryRoadmapOptions.value.map((option) => option.value))
const secondaryRoadmapCandidates = computed(() => [
  ...candidateGroups.value.secondary,
  ...candidateGroups.value.accessories,
  ...candidateGroups.value.foundation.filter((candidate) => candidate.role === 'foundation_bridge'),
])
const secondaryRoadmapOptions = computed(() =>
  secondaryRoadmapCandidates.value
    .filter((candidate) => candidate.skill !== onboarding.form.primaryTargetSkill)
    .map(candidateToOption),
)
const longTermRoadmapOptions = computed(() =>
  candidateGroups.value.future.length
    ? candidateGroups.value.future
        .filter((candidate) => !onboarding.form.targetSkills.includes(candidate.skill))
        .map(candidateToOption)
    : targetSkillOptions
        .filter((option) => !onboarding.form.targetSkills.includes(option.value))
        .map((option) => {
          const suggested = [
            ...onboarding.form.roadmapSuggestions.longTermTracks,
            ...onboarding.form.roadmapSuggestions.deferredTracks,
          ].find((track) => track.skill === option.value)

          return suggested
            ? {
                description: `${suggested.reason} ${suggested.nextGate}`,
                label: suggested.label,
                meta: 'Later',
                value: suggested.skill,
              }
            : option
        }),
)
const foundationCandidates = computed(() => candidateGroups.value.foundation)
const recommendedPrimaryCandidate = computed(
  () =>
    candidateGroups.value.primary.find((candidate) => candidate.skill === onboarding.form.primaryTargetSkill) ??
    candidateGroups.value.primary[0] ??
    null,
)
const recommendedSecondaryCandidate = computed(
  () =>
    secondaryRoadmapCandidates.value.find((candidate) =>
      onboarding.form.secondaryTargetSkills.includes(candidate.skill),
    ) ??
    secondaryRoadmapCandidates.value[0] ??
    null,
)
const suggestedBaseFocusOptions = computed(() => {
  const suggested = onboarding.form.roadmapSuggestions.baseFocusAreas

  if (!suggested.length) {
    return baseFocusOptions
  }

  return baseFocusOptions.filter((option) => suggested.includes(option.value))
})
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
const chosenSchedule = computed(() =>
  onboarding.form.preferredTrainingDays.length
    ? `${onboarding.form.preferredTrainingDays.length} days, max ${onboarding.form.weeklySessionGoal || '...'} sessions`
    : 'Schedule not set',
)
const showLowerBodyFallback = computed(() => !hasCompleteBarbellSquatData(onboarding.form.currentLevelTests))
const redirectPath = computed(() =>
  typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/app')
    ? route.query.redirect
    : '/app/dashboard',
)

function candidateToOption(candidate: RoadmapGoalCandidate): ChoiceOption {
  const descriptionParts = [candidate.reason]

  if (candidate.nextGate) {
    descriptionParts.push(`Next gate: ${candidate.nextGate}`)
  }

  return {
    description: descriptionParts.filter(Boolean).join(' '),
    label: candidate.label,
    meta: `${candidateRoleLabel(candidate)} · ${candidate.readinessScore}/100`,
    value: candidate.skill,
  }
}

function candidateRoleLabel(candidate: RoadmapGoalCandidate): string {
  if (candidate.role === 'owned_foundation') {
    return 'Foundation owned'
  }

  if (candidate.role === 'foundation_bridge') {
    return 'Foundation bridge'
  }

  if (candidate.role === 'low_fatigue_accessory') {
    return 'Low-fatigue support'
  }

  if (candidate.role === 'secondary_candidate') {
    return 'Compatible support'
  }

  if (candidate.role === 'primary_candidate') {
    return candidate.stressClass === 'high' ? 'Main skill' : 'Ready target'
  }

  if (candidate.role === 'blocked') {
    return 'Blocked for now'
  }

  return 'Future target'
}

function candidateBadgeClass(candidate: RoadmapGoalCandidate): string {
  if (candidate.role === 'owned_foundation') {
    return 'bg-status-success/10 text-status-success'
  }

  if (candidate.role === 'foundation_bridge') {
    return 'bg-accent-secondary-soft text-accent-secondary'
  }

  if (candidate.role === 'blocked') {
    return 'bg-status-danger/10 text-status-danger'
  }

  return 'bg-accent-primary-soft text-ink-primary'
}

watch(
  () => onboarding.form.targetSkills,
  () => {
    if (!onboarding.form.targetSkills.includes(onboarding.form.primaryTargetSkill)) {
      onboarding.form.primaryTargetSkill = onboarding.form.targetSkills[0] ?? ''
    }

    onboarding.form.secondaryTargetSkills = onboarding.form.secondaryTargetSkills
      .filter(
        (skill) =>
          skill !== onboarding.form.primaryTargetSkill && !onboarding.form.longTermTargetSkills.includes(skill),
      )
      .slice(0, 3)
  },
  { deep: true },
)

watch(
  () => onboarding.form.roadmapSuggestions,
  () => {
    onboarding.form.targetSkills = onboarding.form.targetSkills.filter((skill) =>
      activeRoadmapSkillValues.value.includes(skill),
    )

    if (
      onboarding.form.primaryTargetSkill &&
      !activeRoadmapSkillValues.value.includes(onboarding.form.primaryTargetSkill)
    ) {
      onboarding.form.primaryTargetSkill = primaryRoadmapOptions.value[0]?.value ?? ''
    }

    if (!onboarding.form.primaryTargetSkill && primaryRoadmapOptions.value[0]?.value) {
      onboarding.form.primaryTargetSkill = primaryRoadmapOptions.value[0].value
    }

    const allowedSecondarySkills = secondaryRoadmapOptions.value.map((option) => option.value)
    onboarding.form.secondaryTargetSkills = onboarding.form.secondaryTargetSkills
      .filter(
        (skill) =>
          skill !== onboarding.form.primaryTargetSkill &&
          (allowedSecondarySkills.length === 0 || allowedSecondarySkills.includes(skill)),
      )
      .slice(0, 3)

    if (onboarding.form.baseFocusAreas.length === 0 && onboarding.form.roadmapSuggestions.baseFocusAreas.length) {
      onboarding.form.baseFocusAreas = [...onboarding.form.roadmapSuggestions.baseFocusAreas].slice(0, 4)
    }
  },
  { deep: true },
)

watch(
  () => onboarding.form.primaryTargetSkill,
  () => {
    onboarding.form.targetSkills = onboarding.form.primaryTargetSkill ? [onboarding.form.primaryTargetSkill] : []
    onboarding.form.requiredGoalModules = requiredGoalModulesForGoal(onboarding.form.primaryTargetSkill)
    const allowedSecondarySkills = secondaryRoadmapOptions.value.map((option) => option.value)
    onboarding.form.secondaryTargetSkills = onboarding.form.secondaryTargetSkills
      .filter(
        (skill) =>
          skill !== onboarding.form.primaryTargetSkill &&
          (allowedSecondarySkills.length === 0 || allowedSecondarySkills.includes(skill)),
      )
      .slice(0, 3)
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
  activeStep.value = steps[activeIndex.value + 1]?.id ?? 'review'
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

function firstInvalidStepBeforeIndex(index: number): { id: OnboardingStepId } | null {
  if (index <= 0) {
    return null
  }

  return (
    steps.slice(0, index).find((step) => Object.keys(validateOnboardingStep(onboarding.form, step.id)).length > 0) ??
    null
  )
}

const rowVariantOptions = [
  { label: 'Bodyweight row', value: 'bodyweight_row' },
  { label: 'Ring row', value: 'ring_row' },
  { label: 'Low bar row', value: 'low_bar_row' },
  { label: 'Suspension row', value: 'suspension_row' },
]

const lowerBodyVariantOptions = [
  { label: 'Bodyweight squat', value: 'bodyweight_squat' },
  { label: 'Split squat', value: 'split_squat' },
  { label: 'Pistol progression', value: 'pistol_progression' },
  { label: 'Step-down', value: 'step_down' },
]
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
                <dt class="text-ink-muted text-xs font-semibold">Personal</dt>
                <dd class="text-ink-primary mt-1 text-sm font-semibold">
                  {{ contextSummary }}
                </dd>
              </div>
              <div class="rounded-control bg-surface-primary border-line-subtle border p-3">
                <dt class="text-ink-muted text-xs font-semibold">Equipment</dt>
                <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ chosenEquipmentCount }} tools selected</dd>
              </div>
              <div class="rounded-control bg-surface-primary border-line-subtle border p-3">
                <dt class="text-ink-muted text-xs font-semibold">Goal</dt>
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
                  <p class="text-ink-muted mt-1 text-sm leading-5">Floor reps with a rigid bodyline.</p>
                </div>
                <div class="max-w-sm">
                  <OnboardingNumberField
                    id="onboarding-push-ups"
                    v-model="onboarding.form.currentLevelTests.pushUpMaxReps"
                    :error="errorFor('currentLevelTests.pushUpMaxReps')"
                    label="Max reps"
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
                  <p class="text-ink-muted mt-1 text-sm leading-5">Reps from a dead hang or 0 if not there yet.</p>
                </div>
                <div class="max-w-sm">
                  <OnboardingNumberField
                    id="onboarding-pull-ups"
                    v-model="onboarding.form.currentLevelTests.pullUpMaxReps"
                    :error="errorFor('currentLevelTests.pullUpMaxReps')"
                    label="Max reps"
                    :max="100"
                    :min="0"
                    placeholder="4"
                    suffix="reps"
                  />
                </div>
              </div>

              <div class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4">
                <div class="mb-4">
                  <h4 class="text-ink-primary text-base font-semibold">Row capacity</h4>
                  <p class="text-ink-muted mt-1 text-sm leading-5">
                    Pick the row setup you tested and enter repeatable reps with a tight bodyline.
                  </p>
                </div>
                <div class="grid gap-3 sm:grid-cols-[1.1fr_0.9fr]">
                  <label class="block space-y-2">
                    <span class="text-ink-primary text-sm font-semibold">Row variation</span>
                    <select
                      v-model="onboarding.form.currentLevelTests.rowVariant"
                      class="border-line-subtle bg-surface-elevated text-ink-primary rounded-control focus:border-accent-primary focus:ring-accent-primary/20 min-h-12 w-full border px-4 py-3 text-base transition outline-none focus:ring-4"
                    >
                      <option v-for="option in rowVariantOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                      </option>
                    </select>
                  </label>
                  <OnboardingNumberField
                    id="onboarding-row-reps"
                    v-model="onboarding.form.currentLevelTests.rowMaxReps"
                    :error="errorFor('currentLevelTests.rowMaxReps')"
                    label="Row reps"
                    :max="100"
                    :min="0"
                    placeholder="12"
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
                  <h4 class="text-ink-primary text-base font-semibold">Hang and support</h4>
                  <p class="text-ink-muted mt-1 text-sm leading-5">
                    These show grip, shoulder, and support tolerance before harder pulling or ring work.
                  </p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                  <OnboardingNumberField
                    id="onboarding-passive-hang"
                    v-model="onboarding.form.currentLevelTests.passiveHangSeconds"
                    :error="errorFor('currentLevelTests.passiveHangSeconds')"
                    label="Passive hang"
                    :max="600"
                    :min="0"
                    placeholder="45"
                    suffix="sec"
                  />
                  <OnboardingNumberField
                    id="onboarding-top-support"
                    v-model="onboarding.form.currentLevelTests.topSupportHoldSeconds"
                    :error="errorFor('currentLevelTests.topSupportHoldSeconds')"
                    label="Dip support hold"
                    :max="600"
                    :min="0"
                    placeholder="25"
                    suffix="sec"
                  />
                </div>
              </div>

              <div class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4">
                <div class="mb-4">
                  <h4 class="text-ink-primary text-base font-semibold">Legs and bodyline</h4>
                  <p class="text-ink-muted mt-1 text-sm leading-5">
                    Use your barbell squat if you know it. If not, add the best lower-body fallback you can test
                    cleanly, plus trunk control.
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
                <div v-if="showLowerBodyFallback" class="mt-4 grid gap-3 sm:grid-cols-[1.2fr_0.9fr_0.9fr]">
                  <label class="block space-y-2">
                    <span class="text-ink-primary text-sm font-semibold">Lower-body fallback</span>
                    <select
                      v-model="onboarding.form.currentLevelTests.lowerBodyVariant"
                      class="border-line-subtle bg-surface-elevated text-ink-primary rounded-control focus:border-accent-primary focus:ring-accent-primary/20 min-h-12 w-full border px-4 py-3 text-base transition outline-none focus:ring-4"
                    >
                      <option v-for="option in lowerBodyVariantOptions" :key="option.value" :value="option.value">
                        {{ option.label }}
                      </option>
                    </select>
                  </label>
                  <OnboardingNumberField
                    id="onboarding-lower-body-reps"
                    v-model="onboarding.form.currentLevelTests.lowerBodyReps"
                    :error="errorFor('currentLevelTests.lowerBodyReps')"
                    label="Fallback reps"
                    :max="100"
                    :min="0"
                    placeholder="12"
                    suffix="reps"
                  />
                  <OnboardingNumberField
                    id="onboarding-lower-body-load"
                    v-model="onboarding.form.currentLevelTests.lowerBodyLoadValue"
                    :error="errorFor('currentLevelTests.lowerBodyLoadValue')"
                    label="Added load"
                    :max="1000"
                    :min="0"
                    placeholder="0"
                    :suffix="onboarding.form.currentLevelTests.lowerBodyLoadUnit"
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
            <section class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4 sm:p-5">
              <div class="mb-5">
                <h3 class="text-ink-primary text-base font-semibold">Pain and recovery signal</h3>
                <p class="text-ink-secondary mt-1 text-sm leading-6">
                  Add the signal that should make the first block more conservative when needed.
                </p>
              </div>
              <div class="grid gap-5 xl:grid-cols-3">
                <OnboardingChoiceGrid
                  v-model="onboarding.form.readinessRating"
                  columns="comfortable"
                  label="Readiness"
                  name="onboarding-readiness"
                  :options="readinessOptions"
                />
                <OnboardingChoiceGrid
                  v-model="onboarding.form.sleepQuality"
                  columns="comfortable"
                  label="Sleep quality"
                  name="onboarding-sleep"
                  :options="readinessOptions"
                />
                <OnboardingChoiceGrid
                  v-model="onboarding.form.sorenessLevel"
                  columns="comfortable"
                  label="Soreness"
                  name="onboarding-soreness"
                  :options="sorenessOptions"
                />
              </div>

              <div class="mt-5 space-y-5">
                <OnboardingChoiceGrid
                  v-model="onboarding.form.painLevel"
                  columns="comfortable"
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
            </section>

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
          v-if="activeStep === 'modules'"
          eyebrow="Step 6"
          title="Add the details that matter for that skill."
          description="Leverly only asks for extra tests connected to your selected roadmap, then uses them to choose the safest next progression."
        >
          <OnboardingModulesStep :errors="currentErrors" />
        </OnboardingStepPanel>

        <OnboardingStepPanel
          v-if="activeStep === 'availability'"
          eyebrow="Step 7"
          title="Set the schedule Leverly can actually use."
          description="The first plan should fit your week before it tries to be ambitious. Choose your training days and the most sessions Leverly can schedule."
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
            <div class="max-w-sm">
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
          v-if="activeStep === 'goal'"
          eyebrow="Step 5"
          title="Choose the roadmap mix Leverly should build around."
          description="Pick the main emphasis, add compatible side goals, and keep future skills visible without forcing them into the first block."
        >
          <div class="space-y-6">
            <section class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-5">
              <div class="flex flex-wrap items-center gap-3">
                <span class="bg-accent-primary-soft text-ink-primary rounded-full px-3 py-1 text-xs font-semibold">
                  {{ onboarding.form.roadmapSuggestions.level }}
                </span>
                <p class="text-ink-primary text-sm font-semibold">
                  {{ onboarding.form.roadmapSuggestions.summary }}
                </p>
              </div>
              <ul
                v-if="onboarding.form.roadmapSuggestions.bodyContext.notes.length"
                class="text-ink-secondary mt-4 grid gap-2 text-sm leading-6"
              >
                <li v-for="note in onboarding.form.roadmapSuggestions.bodyContext.notes" :key="note">
                  {{ note }}
                </li>
              </ul>
            </section>

            <section
              v-if="hasGoalCandidates"
              class="border-line-subtle from-surface-elevated to-surface-primary rounded-card shadow-card-soft border bg-linear-to-br p-5"
            >
              <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                  <p class="text-accent-primary text-xs font-semibold tracking-[0.16em] uppercase">Recommended mix</p>
                  <h3 class="text-ink-primary mt-2 text-xl font-semibold">A first block with one clear priority.</h3>
                </div>
                <p class="text-ink-muted max-w-xl text-sm leading-6">
                  The mix is based on your tests, equipment, recovery budget, and skill overlap.
                </p>
              </div>

              <div class="mt-5 grid gap-3 lg:grid-cols-3">
                <article class="border-line-subtle bg-surface-primary rounded-card border p-4">
                  <p class="text-ink-muted text-xs font-semibold tracking-[0.14em] uppercase">Main emphasis</p>
                  <h4 class="text-ink-primary mt-2 text-lg font-semibold">
                    {{ recommendedPrimaryCandidate?.label ?? 'Choose a target' }}
                  </h4>
                  <p class="text-ink-secondary mt-2 text-sm leading-6">
                    {{
                      recommendedPrimaryCandidate?.reason ??
                      'The best main roadmap appears after the baseline is saved.'
                    }}
                  </p>
                </article>
                <article class="border-line-subtle bg-surface-primary rounded-card border p-4">
                  <p class="text-ink-muted text-xs font-semibold tracking-[0.14em] uppercase">Side work</p>
                  <h4 class="text-ink-primary mt-2 text-lg font-semibold">
                    {{ recommendedSecondaryCandidate?.label ?? 'Optional' }}
                  </h4>
                  <p class="text-ink-secondary mt-2 text-sm leading-6">
                    {{
                      recommendedSecondaryCandidate?.reason ??
                      'Choose side goals that do not compete with the main priority.'
                    }}
                  </p>
                </article>
                <article class="border-line-subtle bg-surface-primary rounded-card border p-4">
                  <p class="text-ink-muted text-xs font-semibold tracking-[0.14em] uppercase">Foundation</p>
                  <h4 class="text-ink-primary mt-2 text-lg font-semibold">
                    {{ onboarding.form.roadmapSuggestions.foundationLane.label }}
                  </h4>
                  <p class="text-ink-secondary mt-2 text-sm leading-6">
                    Base work stays in the plan so progressions do not outrun strength, positions, or tolerance.
                  </p>
                </article>
              </div>
            </section>

            <OnboardingChoiceGrid
              v-model="onboarding.form.primaryTargetSkill"
              :error="errorFor('primaryTargetSkill')"
              help="Choose one target. This drives exercise selection, progression gates, and the first block structure."
              label="Main emphasis"
              name="onboarding-primary-target-skill"
              :options="primaryRoadmapOptions"
            />
            <OnboardingChoiceGrid
              v-if="secondaryRoadmapOptions.length"
              v-model="onboarding.form.secondaryTargetSkills"
              :error="errorFor('secondaryTargetSkills')"
              help="Choose up to three side goals that fit the main emphasis and recovery budget."
              label="Compatible side goals"
              :max-selections="3"
              multiple
              name="onboarding-secondary-target-skills"
              :options="secondaryRoadmapOptions"
            />
            <OnboardingChoiceGrid
              v-model="onboarding.form.longTermTargetSkills"
              :error="errorFor('longTermTargetSkills')"
              help="Keep bigger skills visible here without making them drive the first block yet."
              label="Future roadmap targets"
              :max-selections="8"
              multiple
              name="onboarding-long-term-targets"
              :options="longTermRoadmapOptions"
            />
            <section v-if="foundationCandidates.length" class="space-y-3">
              <div>
                <h3 class="text-ink-primary text-base font-semibold">Foundation kept in every plan</h3>
                <p class="text-ink-secondary mt-1 text-sm leading-6">
                  These basics stay as maintenance, warm-up, or bridge work depending on your current level.
                </p>
              </div>
              <div class="grid gap-3 md:grid-cols-3">
                <article
                  v-for="candidate in foundationCandidates"
                  :key="candidate.skill"
                  class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div>
                      <h4 class="text-ink-primary text-sm font-semibold">{{ candidate.label }}</h4>
                      <p class="text-ink-muted mt-1 text-xs leading-5">{{ candidate.reason }}</p>
                    </div>
                    <span
                      class="shrink-0 rounded-full px-2.5 py-1 text-[0.7rem] font-semibold"
                      :class="candidateBadgeClass(candidate)"
                    >
                      {{ candidateRoleLabel(candidate) }}
                    </span>
                  </div>
                </article>
              </div>
            </section>
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
          v-if="activeStep === 'review'"
          eyebrow="Step 8"
          title="Review your first roadmap."
          description="This is the current recommendation from your assessment. You can go back to adjust inputs before completing setup."
        >
          <OnboardingRoadmapReviewStep />
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
