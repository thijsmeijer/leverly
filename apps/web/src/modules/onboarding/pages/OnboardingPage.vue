<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { UiButton } from '@/shared/ui'
import OnboardingChoiceGrid from '../components/OnboardingChoiceGrid.vue'
import OnboardingNumberField from '../components/OnboardingNumberField.vue'
import OnboardingProgress from '../components/OnboardingProgress.vue'
import OnboardingStepPanel from '../components/OnboardingStepPanel.vue'
import { useOnboardingSteps } from '../composables/useOnboardingSteps'
import {
  compatibleSecondaryGoals,
  equipmentCategories,
  goalOptions,
  onboardingTrainingTimeOptions,
  painAreaOptions,
  painOptions,
  pullUpProgressionOptions,
  readinessOptions,
  sorenessOptions,
  skillStatusKeys,
  skillStatusLabels,
  skillStatusOptions,
  squatProgressionOptions,
  starterPlanOptions,
  targetSkillOptions,
  trainingDayOptions,
  trainingLocationOptions,
} from '../data/onboardingOptions'
import { validateOnboardingStep } from '../services/onboardingService'
import { useOnboardingStore } from '../stores/onboardingStore'
import type { OnboardingFieldErrors, OnboardingStepId } from '../types'

const route = useRoute()
const router = useRouter()
const onboarding = useOnboardingStore()

const activeStep = ref<OnboardingStepId>('goals')
const clientErrors = ref<OnboardingFieldErrors>({})
const { activeIndex, canContinue, canGoBack, progressPercent, steps } = useOnboardingSteps(activeStep)
const currentErrors = computed(() => ({ ...clientErrors.value, ...onboarding.fieldErrors }))
const compatibleSecondaryGoalOptions = computed(() => {
  const allowedGoals = compatibleSecondaryGoals[onboarding.form.primaryGoal] ?? []

  return goalOptions.filter((option) => allowedGoals.includes(option.value))
})
const chosenSkills = computed(() =>
  targetSkillOptions
    .filter((option) => onboarding.form.targetSkills.includes(option.value))
    .map((option) => option.label),
)
const chosenEquipmentCount = computed(() => onboarding.form.availableEquipment.length)
const chosenSchedule = computed(() =>
  onboarding.form.preferredTrainingDays.length
    ? `${onboarding.form.preferredTrainingDays.length} days, up to ${onboarding.form.preferredSessionMinutes || '...'} minutes`
    : 'Schedule not set',
)
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

onMounted(() => {
  void onboarding.load()
})

function errorFor(key: string): string | undefined {
  return currentErrors.value[key]
}

function selectStep(step: OnboardingStepId): void {
  clientErrors.value = {}
  activeStep.value = step
}

async function goBack(): Promise<void> {
  if (!canGoBack.value) {
    return
  }

  await onboarding.saveDraft()
  clientErrors.value = {}
  activeStep.value = steps[activeIndex.value - 1]?.id ?? 'goals'
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
              Build the signal for your first calisthenics plan.
            </h1>
            <p class="text-ink-secondary mt-4 max-w-3xl text-base leading-7">
              Choose the skills, equipment, baseline tests, and recovery context Leverly needs to place you on the right
              progressions from day one.
            </p>
            <div class="mt-6 flex flex-wrap gap-3">
              <UiButton variant="secondary" @click="onboarding.saveDraft()">Save draft</UiButton>
            </div>
          </div>

          <aside class="border-line-subtle bg-surface-muted/70 border-t p-5 sm:p-8 lg:border-t-0 lg:border-l">
            <p class="text-ink-muted text-xs font-semibold tracking-[0.18em] uppercase">Current map</p>
            <dl class="mt-4 grid gap-3">
              <div class="rounded-control bg-surface-primary border-line-subtle border p-3">
                <dt class="text-ink-muted text-xs font-semibold">Targets</dt>
                <dd class="text-ink-primary mt-1 text-sm font-semibold">
                  {{ chosenSkills.length ? chosenSkills.slice(0, 2).join(', ') : 'No targets yet' }}
                </dd>
              </div>
              <div class="rounded-control bg-surface-primary border-line-subtle border p-3">
                <dt class="text-ink-muted text-xs font-semibold">Equipment</dt>
                <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ chosenEquipmentCount }} tools selected</dd>
              </div>
              <div class="rounded-control bg-surface-primary border-line-subtle border p-3">
                <dt class="text-ink-muted text-xs font-semibold">Schedule</dt>
                <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ chosenSchedule }}</dd>
              </div>
            </dl>
          </aside>
        </div>
      </header>

      <OnboardingProgress :active-step="activeStep" :steps="steps" @select="selectStep" />

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

      <form class="space-y-5" novalidate @submit.prevent="completeOnboarding">
        <OnboardingStepPanel
          v-if="activeStep === 'goals'"
          eyebrow="Step 1"
          title="Pick the outcome and exact skills."
          description="Leverly works best when the target is specific. Choose the main goal, supporting goals, and the skill targets you actually want to move toward."
        >
          <div class="space-y-6">
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
              help="Choose one to eight. These become the first progression families Leverly cares about."
              label="Skill and strength targets"
              :max-selections="8"
              multiple
              name="onboarding-target-skills"
              :options="targetSkillOptions"
            />
          </div>
        </OnboardingStepPanel>

        <OnboardingStepPanel
          v-if="activeStep === 'equipment'"
          eyebrow="Step 2"
          title="Map the places and tools you can rely on."
          description="Equipment changes the progression path. A pull-up bar, rings, low bar, bands, or parallettes can unlock very different recommendations."
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
          eyebrow="Step 3"
          title="Place your current progressions."
          description="These quick tests tell Leverly whether to suggest reps, assistance, holds, regressions, or harder variations."
        >
          <div class="grid gap-5 lg:grid-cols-2">
            <OnboardingNumberField
              id="onboarding-push-ups"
              v-model="onboarding.form.currentLevelTests.pushUpMaxReps"
              :error="errorFor('currentLevelTests.pushUpMaxReps')"
              label="Max strict push-ups"
              :max="200"
              :min="0"
              placeholder="18"
              suffix="reps"
            />
            <OnboardingNumberField
              id="onboarding-hollow-hold"
              v-model="onboarding.form.currentLevelTests.hollowHoldSeconds"
              :error="errorFor('currentLevelTests.hollowHoldSeconds')"
              label="Best hollow hold"
              :max="600"
              :min="0"
              placeholder="35"
              suffix="sec"
            />
          </div>

          <div class="mt-6 grid gap-6 xl:grid-cols-2">
            <div class="space-y-4">
              <OnboardingChoiceGrid
                v-model="onboarding.form.currentLevelTests.pullUpProgression"
                :error="errorFor('currentLevelTests.pullUpProgression')"
                label="Current pull-up progression"
                name="onboarding-pull-up-progression"
                :options="pullUpProgressionOptions"
              />
              <OnboardingNumberField
                id="onboarding-pull-ups"
                v-model="onboarding.form.currentLevelTests.pullUpMaxReps"
                label="Strict pull-ups if you have them"
                :max="100"
                :min="0"
                placeholder="4"
                suffix="reps"
              />
            </div>

            <div class="space-y-4">
              <OnboardingChoiceGrid
                v-model="onboarding.form.currentLevelTests.squatProgression"
                :error="errorFor('currentLevelTests.squatProgression')"
                label="Current squat or pistol progression"
                name="onboarding-squat-progression"
                :options="squatProgressionOptions"
              />
              <OnboardingNumberField
                id="onboarding-squat-reps"
                v-model="onboarding.form.currentLevelTests.squatMaxReps"
                label="Best clean reps at that level"
                :max="300"
                :min="0"
                placeholder="20"
                suffix="reps"
              />
            </div>
          </div>

          <section class="mt-7 space-y-4">
            <div>
              <h3 class="text-ink-primary text-lg font-semibold">Optional skill statuses</h3>
              <p class="text-ink-secondary mt-1 text-sm leading-6">
                Add only what you know. These help avoid suggesting skill work that skips your current base.
              </p>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
              <div
                v-for="skill in skillStatusKeys"
                :key="skill"
                class="border-line-subtle bg-surface-primary rounded-card border p-4"
              >
                <label class="block space-y-2">
                  <span class="text-ink-primary text-sm font-semibold">{{ skillStatusLabels[skill] }}</span>
                  <select
                    v-model="onboarding.form.skillStatuses[skill].status"
                    class="border-line-subtle bg-surface-elevated text-ink-primary rounded-control focus:border-accent-primary focus:ring-accent-primary/20 min-h-12 w-full border px-4 py-3 text-base transition outline-none focus:ring-4"
                  >
                    <option v-for="option in skillStatusOptions" :key="option.value" :value="option.value">
                      {{ option.label }}
                    </option>
                  </select>
                </label>
                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                  <OnboardingNumberField
                    :id="`onboarding-${skill}-reps`"
                    v-model="onboarding.form.skillStatuses[skill].maxStrictReps"
                    label="Clean reps"
                    :max="100"
                    :min="0"
                    placeholder="0"
                  />
                  <OnboardingNumberField
                    :id="`onboarding-${skill}-hold`"
                    v-model="onboarding.form.skillStatuses[skill].bestHoldSeconds"
                    label="Best hold"
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
          v-if="activeStep === 'availability'"
          eyebrow="Step 4"
          title="Set the schedule Leverly can actually use."
          description="The first plan should fit your week before it tries to be ambitious. Choose training days, session ceiling, and the weekly rhythm."
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
            <div class="grid gap-5 md:grid-cols-3">
              <OnboardingNumberField
                id="onboarding-session-minutes"
                v-model="onboarding.form.preferredSessionMinutes"
                :error="errorFor('preferredSessionMinutes')"
                label="Session length"
                :max="240"
                :min="10"
                placeholder="45"
                suffix="min"
              />
              <OnboardingNumberField
                id="onboarding-weekly-sessions"
                v-model="onboarding.form.weeklySessionGoal"
                :error="errorFor('weeklySessionGoal')"
                label="Weekly sessions"
                :max="14"
                :min="1"
                placeholder="3"
              />
              <OnboardingChoiceGrid
                v-model="onboarding.form.preferredTrainingTime"
                columns="compact"
                label="Training time"
                name="onboarding-training-time"
                :options="onboardingTrainingTimeOptions"
              />
            </div>
          </div>
        </OnboardingStepPanel>

        <OnboardingStepPanel
          v-if="activeStep === 'readiness'"
          eyebrow="Step 5"
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
          eyebrow="Step 6"
          title="Choose the first plan shape."
          description="This gives Leverly a starting structure. The exact sessions can later adapt around your tests, equipment, pain flags, and progress."
        >
          <OnboardingChoiceGrid
            v-model="onboarding.form.starterPlanKey"
            :error="errorFor('starterPlanKey')"
            label="Starter plan"
            name="onboarding-starter-plan"
            :options="starterPlanOptions"
          />
        </OnboardingStepPanel>

        <div
          class="border-line-subtle bg-surface-elevated shadow-card rounded-card sticky bottom-4 z-20 flex flex-col gap-3 border p-4 sm:flex-row sm:items-center sm:justify-between"
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
