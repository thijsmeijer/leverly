<script setup lang="ts">
import { computed } from 'vue'
import { requiredGoalModulesForGoal } from '@/modules/roadmap'
import { UiButton } from '@/shared/ui'

import {
  skillStatusKeys,
  skillStatusLabels,
  skillStatusMeasurements,
  skillStatusOptions,
  targetSkillOptions,
  weightedExperienceOptions,
  weightedMovementOptions,
} from '../data/onboardingOptions'
import { useOnboardingStore } from '../stores/onboardingStore'
import type { OnboardingFieldErrors } from '../types'
import OnboardingChoiceGrid from './OnboardingChoiceGrid.vue'
import OnboardingNumberField from './OnboardingNumberField.vue'

const props = defineProps<{
  errors: OnboardingFieldErrors
}>()

const onboarding = useOnboardingStore()

const activeGoalModuleIds = computed(() =>
  onboarding.form.requiredGoalModules.length
    ? [...onboarding.form.requiredGoalModules]
    : requiredGoalModulesForGoal(onboarding.form.primaryTargetSkill),
)
const activeGoalModules = computed(() =>
  activeGoalModuleIds.value.map((key) => ({
    id: key,
    ...(goalModuleContent[key] ?? fallbackGoalModuleContent(key)),
  })),
)
const primaryTargetLabel = computed(
  () =>
    targetSkillOptions.find((option) => option.value === onboarding.form.primaryTargetSkill)?.label ??
    'selected roadmap',
)
const weightedSkillSelected = computed(() =>
  [onboarding.form.primaryTargetSkill, ...onboarding.form.targetSkills].some((skill) => skill.startsWith('weighted_')),
)

function errorFor(key: string): string | undefined {
  return props.errors[key]
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

const goalMetricOptions = [
  { label: 'Reps', value: 'reps' },
  { label: 'Hold seconds', value: 'hold_seconds' },
  { label: 'Added load', value: 'load' },
  { label: 'Quality only', value: 'quality' },
]

const goalQualityOptions = [
  { label: 'Unknown', value: 'unknown' },
  { label: 'Rough', value: 'rough' },
  { label: 'Solid', value: 'solid' },
  { label: 'Clean', value: 'clean' },
]

const goalModuleContent: Record<
  string,
  { description: string; progressionOptions: Array<{ label: string; value: string }>; title: string }
> = {
  inversion: {
    description: 'Use the best inversion or overhead pressing progression you can repeat without rushing the line.',
    progressionOptions: progressionOptions([
      'not_tested',
      'wall_plank',
      'chest_to_wall_handstand',
      'wall_handstand_shoulder_taps',
      'freestanding_kick_up',
      'freestanding_handstand',
      'pike_push_up',
      'elevated_pike_push_up',
      'wall_hspu_negative',
      'partial_wall_hspu',
      'full_wall_hspu',
      'deep_handstand_push_up',
      'freestanding_handstand_push_up',
    ]),
    title: 'Inversion skill check',
  },
  lateral_chain: {
    description: 'Side-body and flag signals decide whether lateral-chain skills should be trained now or deferred.',
    progressionOptions: progressionOptions([
      'not_tested',
      'side_plank',
      'vertical_flag_hold',
      'tuck_human_flag',
      'straddle_human_flag',
      'full_human_flag',
    ]),
    title: 'Lateral-chain skill check',
  },
  lower_body: {
    description: 'Single-leg and posterior-chain tests keep lower-body progressions realistic.',
    progressionOptions: progressionOptions([
      'not_tested',
      'split_squat',
      'box_pistol',
      'assisted_pistol',
      'pistol_negative',
      'full_pistol_squat',
      'weighted_pistol',
      'nordic_eccentric',
      'nordic_curl',
    ]),
    title: 'Lower-body skill check',
  },
  pull_skill: {
    description: 'Pulling skill evidence separates muscle-up, lever, weighted pull-up, and one-arm pull-up paths.',
    progressionOptions: progressionOptions([
      'not_tested',
      'explosive_pull_up',
      'chest_to_bar_pull_up',
      'high_pull_up',
      'band_assisted_muscle_up',
      'negative_muscle_up',
      'strict_muscle_up',
      'weighted_pull_up_reps',
      'tuck_front_lever',
      'advanced_tuck_front_lever',
      'one_leg_front_lever',
      'half_lay_front_lever',
      'straddle_front_lever',
      'full_front_lever',
      'skin_the_cat_prep',
      'tuck_back_lever',
      'advanced_tuck_back_lever',
      'straddle_back_lever',
      'full_back_lever',
      'archer_pull_up',
      'typewriter_pull_up',
      'assisted_one_arm_pull_up',
      'one_arm_pull_up_negative',
      'strict_one_arm_pull_up',
    ]),
    title: 'Pulling skill check',
  },
  push_compression: {
    description: 'Compression and straight-arm push evidence shape L-sit, press, planche, and V-sit recommendations.',
    progressionOptions: progressionOptions([
      'not_tested',
      'tuck_support',
      'tuck_l_sit',
      'one_leg_l_sit',
      'full_l_sit',
      'v_sit_prep',
      'compression_lift',
      'elevated_press_lean',
      'wall_press_negative',
      'straddle_press_negative',
      'freestanding_press_to_handstand',
      'planche_lean',
      'frog_stand',
      'tuck_planche',
      'advanced_tuck_planche',
      'straddle_planche',
      'full_planche',
    ]),
    title: 'Push and compression skill check',
  },
}

function fallbackGoalModuleContent(key: string) {
  return {
    description: 'Add the best tested progression you can repeat today.',
    progressionOptions: progressionOptions(['not_tested']),
    title: `${humanizeProgression(key)} skill check`,
  }
}

function progressionOptions(values: string[]): Array<{ label: string; value: string }> {
  return values.map((value) => ({ label: humanizeProgression(value), value }))
}

function humanizeProgression(value: string): string {
  const labels: Record<string, string> = {
    freestanding_kick_up: 'Freestanding kick-up',
    high_pull_up: 'High pull-up',
    not_tested: 'Not tested',
    one_arm_pull_up_negative: 'One-arm pull-up negative',
    strict_one_arm_pull_up: 'One-arm pull-up',
    v_sit_prep: 'V-sit prep',
  }

  return (
    labels[value] ??
    value
      .split('_')
      .filter(Boolean)
      .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
      .join(' ')
  )
}
</script>

<template>
  <div class="space-y-6">
    <div
      v-if="activeGoalModules.length === 0"
      class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-5"
    >
      <h3 class="text-ink-primary text-base font-semibold">No extra skill module needed yet.</h3>
      <p class="text-ink-secondary mt-2 text-sm leading-6">
        The baseline tests are enough for the selected first roadmap. You can continue to availability.
      </p>
    </div>

    <div
      v-for="module in activeGoalModules"
      :key="module.id"
      class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4 sm:p-5"
    >
      <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(22rem,32rem)]">
        <div>
          <p class="text-accent-primary text-xs font-semibold tracking-[0.16em] uppercase">
            {{ primaryTargetLabel }}
          </p>
          <h3 class="text-ink-primary mt-2 text-lg font-semibold">{{ module.title }}</h3>
          <p class="text-ink-secondary mt-2 text-sm leading-6">{{ module.description }}</p>
          <p v-if="errorFor(`goalModules.${module.id}`)" class="text-status-danger mt-3 text-sm leading-5">
            {{ errorFor(`goalModules.${module.id}`) }}
          </p>
        </div>

        <div class="space-y-4">
          <label class="block space-y-2">
            <span class="text-ink-primary text-sm font-semibold">Highest tested progression</span>
            <select
              v-model="onboarding.form.goalModules[module.id].highestProgression"
              class="border-line-subtle bg-surface-elevated text-ink-primary rounded-control focus:border-accent-primary focus:ring-accent-primary/20 min-h-12 w-full border px-4 py-3 text-base transition outline-none focus:ring-4"
            >
              <option v-for="option in module.progressionOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </option>
            </select>
          </label>

          <OnboardingChoiceGrid
            v-model="onboarding.form.goalModules[module.id].metricType"
            columns="compact"
            label="Measured by"
            :name="`onboarding-goal-module-${module.id}-metric`"
            :options="goalMetricOptions"
          />

          <div class="grid gap-3 sm:grid-cols-2">
            <OnboardingNumberField
              v-if="onboarding.form.goalModules[module.id].metricType === 'reps'"
              :id="`onboarding-goal-module-${module.id}-reps`"
              v-model="onboarding.form.goalModules[module.id].reps"
              label="Best reps"
              :max="100"
              :min="1"
              placeholder="3"
              suffix="reps"
            />
            <OnboardingNumberField
              v-if="onboarding.form.goalModules[module.id].metricType === 'hold_seconds'"
              :id="`onboarding-goal-module-${module.id}-hold`"
              v-model="onboarding.form.goalModules[module.id].holdSeconds"
              label="Best hold"
              :max="600"
              :min="1"
              placeholder="20"
              suffix="sec"
            />
            <OnboardingNumberField
              v-if="onboarding.form.goalModules[module.id].metricType === 'load'"
              :id="`onboarding-goal-module-${module.id}-load`"
              v-model="onboarding.form.goalModules[module.id].loadValue"
              label="Added load"
              :max="400"
              :min="1"
              placeholder="10"
              :suffix="onboarding.form.goalModules[module.id].loadUnit"
            />
          </div>

          <OnboardingChoiceGrid
            v-if="onboarding.form.goalModules[module.id].metricType === 'quality'"
            v-model="onboarding.form.goalModules[module.id].quality"
            columns="compact"
            label="Quality"
            :name="`onboarding-goal-module-${module.id}-quality`"
            :options="goalQualityOptions"
          />
        </div>
      </div>
    </div>

    <div
      v-if="weightedSkillSelected || onboarding.form.weightedBaselines.experience !== 'none'"
      class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft space-y-4 border p-4 sm:p-5"
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
          class="border-line-subtle bg-surface-elevated rounded-card grid gap-3 border p-4 md:grid-cols-[1.2fr_1fr_1fr_1fr_auto]"
        >
          <label class="block space-y-2">
            <span class="text-ink-primary text-sm font-semibold">Movement</span>
            <select
              v-model="movement.movement"
              class="border-line-subtle bg-surface-primary text-ink-primary rounded-control focus:border-accent-primary focus:ring-accent-primary/20 min-h-12 w-full border px-4 py-3 text-base transition outline-none focus:ring-4"
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

    <details class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4 sm:p-5">
      <summary class="text-ink-primary cursor-pointer text-base font-semibold">
        Add other tested skill progressions
      </summary>
      <p class="text-ink-secondary mt-2 text-sm leading-6">
        Optional evidence can help later roadmap updates, but it is not required for the first block.
      </p>
      <div class="mt-4 grid gap-4 lg:grid-cols-2">
        <div
          v-for="skill in skillStatusKeys"
          :key="skill"
          class="border-line-subtle bg-surface-elevated rounded-card border p-4"
        >
          <label class="block space-y-2">
            <span class="text-ink-primary text-sm font-semibold">{{ skillStatusLabels[skill] }}</span>
            <select
              v-model="onboarding.form.skillStatuses[skill].status"
              class="border-line-subtle bg-surface-primary text-ink-primary rounded-control focus:border-accent-primary focus:ring-accent-primary/20 min-h-12 w-full border px-4 py-3 text-base transition outline-none focus:ring-4"
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
              v-model="onboarding.form.skillStatuses[skill].maxReps"
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
    </details>
  </div>
</template>
