<script setup lang="ts">
import { computed } from 'vue'
import { requiredGoalModulesForGoal } from '@/modules/roadmap'

import { targetSkillOptions } from '../data/profileOptions'
import type { ProfileFieldErrors, ProfileSettingsForm } from '../types'
import ProfileChoiceGrid from './ProfileChoiceGrid.vue'
import ProfileSelectField from './ProfileSelectField.vue'
import ProfileTextField from './ProfileTextField.vue'

const form = defineModel<ProfileSettingsForm>('form', { required: true })

defineProps<{
  errors: ProfileFieldErrors
}>()

const activeGoalModuleIds = computed(() =>
  form.value.requiredGoalModules.length
    ? [...form.value.requiredGoalModules]
    : requiredGoalModulesForGoal(form.value.primaryTargetSkill),
)
const activeGoalModules = computed(() =>
  activeGoalModuleIds.value.map((key) => ({
    id: key,
    ...(goalModuleContent[key] ?? fallbackGoalModuleContent(key)),
  })),
)
const primaryTargetLabel = computed(
  () => targetSkillOptions.find((option) => option.value === form.value.primaryTargetSkill)?.label ?? 'roadmap',
)

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
    description: 'Update inversion, balance, and overhead pressing evidence for handstand and HSPU paths.',
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
    description: 'Update side-body and flag evidence for lateral-chain recommendations.',
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
    description: 'Update single-leg and posterior-chain evidence for lower-body skill paths.',
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
    description: 'Update muscle-up, lever, weighted pull-up, and one-arm pull-up evidence.',
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
    description: 'Update compression and straight-arm push evidence for L-sit, press, planche, and V-sit paths.',
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
    description: 'Update the most relevant tested progression for this roadmap family.',
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
  <div class="space-y-4">
    <div>
      <h3 class="text-ink-primary text-base font-semibold">Goal-specific skill evidence</h3>
      <p class="text-ink-secondary mt-1 text-sm leading-6">
        These fields affect roadmap recalculation for the selected primary skill.
      </p>
      <p v-if="errors.goalModules" class="text-status-danger mt-2 text-sm leading-5">{{ errors.goalModules }}</p>
    </div>

    <div v-if="activeGoalModules.length === 0" class="border-line-subtle bg-surface-elevated rounded-card border p-4">
      <p class="text-ink-secondary text-sm leading-6">
        Choose a primary roadmap to reveal the specific skill module fields that affect recalculation.
      </p>
    </div>

    <div
      v-for="module in activeGoalModules"
      :key="module.id"
      class="border-line-subtle bg-surface-elevated rounded-card shadow-card-soft border p-4"
    >
      <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(20rem,0.95fr)]">
        <div>
          <p class="text-accent-primary text-xs font-semibold tracking-[0.16em] uppercase">
            {{ primaryTargetLabel }}
          </p>
          <h4 class="text-ink-primary mt-2 text-base font-semibold">{{ module.title }}</h4>
          <p class="text-ink-secondary mt-2 text-sm leading-6">{{ module.description }}</p>
        </div>

        <div class="space-y-4">
          <ProfileSelectField
            :id="`profile-goal-module-${module.id}-progression`"
            v-model="form.goalModules[module.id].highestProgression"
            label="Highest tested progression"
            :options="module.progressionOptions"
          />
          <ProfileChoiceGrid
            v-model="form.goalModules[module.id].metricType"
            columns="compact"
            label="Measured by"
            :name="`profile-goal-module-${module.id}-metric`"
            :options="goalMetricOptions"
          />
          <div class="grid gap-4 sm:grid-cols-2">
            <ProfileTextField
              v-if="form.goalModules[module.id].metricType === 'reps'"
              :id="`profile-goal-module-${module.id}-reps`"
              v-model="form.goalModules[module.id].reps"
              input-mode="numeric"
              label="Best reps"
              placeholder="3"
              type="number"
            />
            <ProfileTextField
              v-if="form.goalModules[module.id].metricType === 'hold_seconds'"
              :id="`profile-goal-module-${module.id}-hold`"
              v-model="form.goalModules[module.id].holdSeconds"
              input-mode="numeric"
              label="Best hold"
              placeholder="20"
              type="number"
            />
            <ProfileTextField
              v-if="form.goalModules[module.id].metricType === 'load'"
              :id="`profile-goal-module-${module.id}-load`"
              v-model="form.goalModules[module.id].loadValue"
              input-mode="decimal"
              label="Added load"
              placeholder="10"
              type="number"
            />
          </div>
          <ProfileChoiceGrid
            v-if="form.goalModules[module.id].metricType === 'quality'"
            v-model="form.goalModules[module.id].quality"
            columns="compact"
            label="Quality"
            :name="`profile-goal-module-${module.id}-quality`"
            :options="goalQualityOptions"
          />
        </div>
      </div>
    </div>
  </div>
</template>
