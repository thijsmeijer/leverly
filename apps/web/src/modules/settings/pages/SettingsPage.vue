<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { requiredGoalModulesForGoal } from '@/modules/roadmap'
import type { RoadmapGoalCandidate } from '@/modules/roadmap'
import { UiButton } from '@/shared/ui'
import ProfileChoiceGrid from '../components/ProfileChoiceGrid.vue'
import ProfileFormSection from '../components/ProfileFormSection.vue'
import ProfileRoadmapModuleFields from '../components/ProfileRoadmapModuleFields.vue'
import ProfileSectionTabs from '../components/ProfileSectionTabs.vue'
import ProfileSelectField from '../components/ProfileSelectField.vue'
import ProfileStatusBanner from '../components/ProfileStatusBanner.vue'
import ProfileTextAreaField from '../components/ProfileTextAreaField.vue'
import ProfileTextField from '../components/ProfileTextField.vue'
import { useSettings } from '../composables/useSettings'
import {
  baseFocusOptions,
  bodyweightUnitOptions,
  compatibleSecondaryGoals,
  deloadPreferenceOptions,
  effortTrackingOptions,
  experienceLevelOptions,
  goalOptions,
  heightUnitOptions,
  intensityPreferenceOptions,
  limitationAreaOptions,
  limitationSeverityOptions,
  limitationStatusOptions,
  lowerBodyVariantOptions,
  mobilityCheckOptions,
  mobilityStatusOptions,
  progressionPaceOptions,
  priorSportOptions,
  rowVariantOptions,
  sessionStructureOptions,
  targetSkillOptions,
  trainingDayOptions,
  trainingLocationOptions,
  unitSystemOptions,
  weightedExperienceOptions,
  weightedMovementOptions,
  weightTrendOptions,
} from '../data/profileOptions'
import type { ChoiceOption, ProfileFieldErrors } from '../types'

type ProfileSectionId = 'basics' | 'training' | 'setup' | 'coaching' | 'limitations'

const {
  fieldErrors,
  form,
  hasErrors,
  isLoading,
  isSaving,
  loadError,
  loadProfile,
  profileId,
  saveError,
  saveProfile,
  saveSuccess,
} = useSettings()

const selectedEquipmentCount = computed(() => form.availableEquipment.length)
const selectedDaysLabel = computed(() =>
  form.preferredTrainingDays.length ? `${form.preferredTrainingDays.length} days selected` : 'No days selected yet',
)
const roadmapSummaryLabel = computed(() => form.roadmapSuggestions.summary || 'Save profile inputs to refresh roadmap.')
const roadmapEtaLabel = computed(() => form.roadmapSuggestions.etaRange.label || 'ETA pending')
const roadmapConfidenceLabel = computed(() => `${capitalize(form.roadmapSuggestions.confidence.level)} confidence`)
const primaryGoalLabel = computed(
  () => goalOptions.find((option) => option.value === form.primaryGoal)?.label ?? 'Strength',
)
const targetSkillValues = computed<string[]>({
  get: () => splitSelectedTargetSkills(form.targetSkillsText),
  set: (skills) => {
    form.targetSkillsText = skills.join('\n')
  },
})
const selectedTargetSkillOptions = computed(() =>
  targetSkillOptions.filter((option) => targetSkillValues.value.includes(option.value)),
)
const candidateGroups = computed(() => form.roadmapSuggestions.goalCandidates)
const hasGoalCandidates = computed(() =>
  [
    candidateGroups.value.primary,
    candidateGroups.value.secondary,
    candidateGroups.value.accessories,
    candidateGroups.value.future,
    candidateGroups.value.foundation,
  ].some((group) => group.length > 0),
)
const primaryTargetOptions = computed(() =>
  candidateGroups.value.primary.length
    ? candidateGroups.value.primary.map(candidateToOption)
    : selectedTargetSkillOptions.value,
)
const secondaryTargetSkillOptions = computed(() =>
  hasGoalCandidates.value
    ? [
        ...candidateGroups.value.secondary,
        ...candidateGroups.value.accessories,
        ...candidateGroups.value.foundation.filter((candidate) => candidate.role === 'foundation_bridge'),
      ]
        .filter((candidate) => candidate.skill !== form.primaryTargetSkill)
        .map(candidateToOption)
    : selectedTargetSkillOptions.value.filter((option) => option.value !== form.primaryTargetSkill),
)
const futureTargetOptions = computed(() =>
  candidateGroups.value.future.length
    ? candidateGroups.value.future
        .filter((candidate) => !targetSkillValues.value.includes(candidate.skill))
        .map(candidateToOption)
    : targetSkillOptions.filter((option) => !targetSkillValues.value.includes(option.value)),
)
const foundationCandidates = computed(() => candidateGroups.value.foundation)
const primaryTargetLabel = computed(
  () => targetSkillOptions.find((option) => option.value === form.primaryTargetSkill)?.label ?? 'Choose a roadmap',
)
const baseFocusLabel = computed(() => {
  if (!form.baseFocusAreas.length) {
    return 'No base focus selected'
  }

  return form.baseFocusAreas
    .map((value) => baseFocusOptions.find((option) => option.value === value)?.label ?? value)
    .slice(0, 3)
    .join(', ')
})
const weightedTargetSelected = computed(() =>
  [form.primaryTargetSkill, ...targetSkillValues.value].some((skill) => skill.startsWith('weighted_')),
)
const activeSection = ref<ProfileSectionId>('basics')
const profileSections: Array<{ id: ProfileSectionId; label: string; summary: string }> = [
  { id: 'basics', label: 'Basics', summary: 'Account and units' },
  { id: 'training', label: 'Training', summary: 'Goals and tools' },
  { id: 'setup', label: 'Schedule', summary: 'Places and session shape' },
  { id: 'coaching', label: 'Coaching', summary: 'Recommendation style' },
  { id: 'limitations', label: 'Limitations', summary: 'Pain flags and notes' },
]
const painFlagRegionOptions = [
  { label: 'Wrist', value: 'wrist' },
  { label: 'Elbow', value: 'elbow' },
  { label: 'Shoulder', value: 'shoulder' },
  { label: 'Low back', value: 'low_back' },
  { label: 'Knee', value: 'knee' },
  { label: 'Ankle', value: 'ankle' },
] as const
const compatibleSecondaryGoalOptions = computed(() => {
  const allowedGoals = compatibleSecondaryGoals[form.primaryGoal] ?? []

  return goalOptions.filter((option) => allowedGoals.includes(option.value))
})

watch(
  () => form.primaryGoal,
  () => {
    const allowedGoals = compatibleSecondaryGoals[form.primaryGoal] ?? []

    form.secondaryGoals = form.secondaryGoals.filter((goal) => allowedGoals.includes(goal)).slice(0, 2)
  },
)

watch(
  targetSkillValues,
  (skills) => {
    if (!skills.includes(form.primaryTargetSkill)) {
      form.primaryTargetSkill = skills[0] ?? ''
    }

    form.secondaryTargetSkills = form.secondaryTargetSkills
      .filter((skill) => skill !== form.primaryTargetSkill && skills.includes(skill))
      .slice(0, 3)
  },
  { deep: true },
)

watch(
  () => form.primaryTargetSkill,
  () => {
    form.targetSkillsText = form.primaryTargetSkill
    form.requiredGoalModules = requiredGoalModulesForGoal(form.primaryTargetSkill)
    const allowedSecondary = secondaryTargetSkillOptions.value.map((option) => option.value)
    form.secondaryTargetSkills = form.secondaryTargetSkills
      .filter(
        (skill) => skill !== form.primaryTargetSkill && (!allowedSecondary.length || allowedSecondary.includes(skill)),
      )
      .slice(0, 3)
  },
)

onMounted(() => {
  void loadProfile()
})

async function submitProfile(): Promise<void> {
  const saved = await saveProfile()

  if (!saved) {
    activeSection.value = sectionForErrors(fieldErrors.value)
  }
}

function sectionForErrors(errors: ProfileFieldErrors): ProfileSectionId {
  if (errors.displayName || errors.timezone || errors.unitSystem || errors.bodyweightUnit) {
    return 'basics'
  }

  if (
    errors.trainingAgeMonths ||
    errors.currentBodyweightValue ||
    errors.experienceLevel ||
    errors.primaryGoal ||
    errors.secondaryGoals ||
    errors.primaryTargetSkill ||
    errors.secondaryTargetSkills ||
    errors.baseFocusAreas ||
    errors.baselineTests ||
    errors.goalModules ||
    errors.weightedBaselines ||
    errors.targetSkillsText
  ) {
    return 'training'
  }

  if (
    errors.availableEquipment ||
    errors.trainingLocations ||
    errors.preferredTrainingDays ||
    errors.preferredSessionMinutes ||
    errors.weeklySessionGoal
  ) {
    return 'setup'
  }

  if (
    errors.progressionPace ||
    errors.intensityPreference ||
    errors.effortTrackingPreference ||
    errors.deloadPreference ||
    errors.sessionStructurePreferences
  ) {
    return 'coaching'
  }

  return 'limitations'
}

function splitSelectedTargetSkills(value: string): string[] {
  const supportedValues = new Set(targetSkillOptions.map((option) => option.value))

  return value
    .split(/[\n,]/)
    .map((item) => item.trim())
    .filter((item, index, items) => item !== '' && supportedValues.has(item) && items.indexOf(item) === index)
}

function candidateToOption(candidate: RoadmapGoalCandidate): ChoiceOption {
  const details = [candidate.reason]

  if (candidate.nextGate) {
    details.push(`Next gate: ${candidate.nextGate}`)
  }

  return {
    description: details.filter(Boolean).join(' '),
    label: candidate.label,
    value: candidate.skill,
  }
}

function candidateRoleLabel(candidate: RoadmapGoalCandidate): string {
  if (candidate.role === 'owned_foundation') {
    return 'Owned'
  }

  if (candidate.role === 'foundation_bridge') {
    return 'Bridge'
  }

  if (candidate.role === 'low_fatigue_accessory') {
    return 'Low fatigue'
  }

  if (candidate.role === 'blocked') {
    return 'Blocked'
  }

  return `${candidate.readinessScore}/100`
}

function addWeightedMovement(): void {
  form.weightedBaselines.movements = [
    ...form.weightedBaselines.movements,
    { externalLoadValue: '', movement: 'weighted_pull_up', reps: '', rir: '' },
  ].slice(0, 4)
}

function removeWeightedMovement(index: number): void {
  form.weightedBaselines.movements = form.weightedBaselines.movements.filter((_, itemIndex) => itemIndex !== index)
}

function capitalize(value: string): string {
  return value ? value.charAt(0).toUpperCase() + value.slice(1) : 'Low'
}
</script>

<template>
  <section class="space-y-6" aria-labelledby="profile-settings-heading">
    <div
      class="border-line-subtle bg-surface-elevated shadow-card rounded-card overflow-hidden border lg:grid lg:grid-cols-[minmax(0,1fr)_22rem]"
    >
      <div class="p-5 sm:p-7">
        <p class="text-accent-primary text-xs font-semibold tracking-[0.18em] uppercase">Athlete context</p>
        <h2 id="profile-settings-heading" class="text-ink-primary mt-3 text-2xl font-semibold tracking-normal">
          Tune the inputs behind your recommendations.
        </h2>
        <p class="text-ink-secondary mt-3 max-w-3xl text-base leading-7">
          Keep your goals, equipment, schedule, and training constraints current so progress options fit the work you
          can actually do.
        </p>
      </div>

      <aside class="border-line-subtle bg-surface-muted/70 border-t p-5 sm:p-7 lg:border-t-0 lg:border-l">
        <p class="text-ink-muted text-xs font-semibold tracking-[0.18em] uppercase">Current signal</p>
        <div class="border-line-subtle bg-surface-primary rounded-card mt-4 border p-4">
          <p class="text-accent-primary text-xs font-semibold tracking-[0.16em] uppercase">
            Roadmap recalculation inputs
          </p>
          <p class="text-ink-primary mt-2 text-sm font-semibold">{{ roadmapSummaryLabel }}</p>
          <div class="mt-3 flex flex-wrap gap-2">
            <span class="bg-accent-primary-soft text-ink-primary rounded-full px-3 py-1 text-xs font-semibold">
              {{ roadmapEtaLabel }}
            </span>
            <span class="bg-surface-muted text-ink-secondary rounded-full px-3 py-1 text-xs font-semibold">
              {{ roadmapConfidenceLabel }}
            </span>
          </div>
        </div>
        <dl class="divide-line-subtle mt-4 grid grid-cols-2 gap-x-4 gap-y-0 divide-y sm:divide-y-0">
          <div class="py-3">
            <dt class="text-ink-muted text-xs font-semibold">Primary goal</dt>
            <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ primaryGoalLabel }}</dd>
          </div>
          <div class="py-3">
            <dt class="text-ink-muted text-xs font-semibold">Equipment</dt>
            <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ selectedEquipmentCount }} selected</dd>
          </div>
          <div class="py-3">
            <dt class="text-ink-muted text-xs font-semibold">Schedule</dt>
            <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ selectedDaysLabel }}</dd>
          </div>
          <div class="py-3">
            <dt class="text-ink-muted text-xs font-semibold">Profile</dt>
            <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ profileId ? 'Saved' : 'New' }}</dd>
          </div>
        </dl>
      </aside>
    </div>

    <ProfileStatusBanner v-if="loadError" tone="danger" :message="loadError" />
    <ProfileStatusBanner v-if="saveError" tone="danger" :message="saveError" />
    <ProfileStatusBanner v-if="saveSuccess" tone="success" message="Profile settings saved." />
    <ProfileStatusBanner
      v-if="hasErrors"
      tone="warning"
      message="Some fields need attention before your profile can be saved."
    />

    <ProfileSectionTabs v-model="activeSection" :sections="profileSections" />

    <form class="space-y-5" novalidate @submit.prevent="submitProfile">
      <ProfileFormSection
        v-if="activeSection === 'basics'"
        id="profile-panel-basics"
        aria-labelledby="profile-tab-basics"
        role="tabpanel"
        eyebrow="Basics"
        title="Account and measurement"
        description="Set the identity and units used across training logs, recommendations, and progress summaries."
      >
        <div class="grid gap-4 md:grid-cols-2">
          <ProfileTextField
            id="profile-display-name"
            v-model="form.displayName"
            autocomplete="name"
            :error="fieldErrors.displayName"
            label="Display name"
            placeholder="Ada Athlete"
          />
          <ProfileTextField
            id="profile-timezone"
            v-model="form.timezone"
            :error="fieldErrors.timezone"
            help="Use an IANA timezone like Europe/Amsterdam."
            label="Timezone"
            placeholder="Europe/Amsterdam"
          />
        </div>

        <div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
          <ProfileChoiceGrid
            v-model="form.unitSystem"
            :error="fieldErrors.unitSystem"
            label="Preferred unit system"
            name="unit-system"
            :options="unitSystemOptions"
          />
          <ProfileChoiceGrid
            v-model="form.bodyweightUnit"
            columns="compact"
            :error="fieldErrors.bodyweightUnit"
            label="Bodyweight unit"
            name="bodyweight-unit"
            :options="bodyweightUnitOptions"
          />
          <ProfileChoiceGrid
            v-model="form.heightUnit"
            columns="compact"
            :error="fieldErrors.heightUnit"
            label="Height unit"
            name="height-unit"
            :options="heightUnitOptions"
          />
        </div>
      </ProfileFormSection>

      <ProfileFormSection
        v-if="activeSection === 'training'"
        id="profile-panel-training"
        aria-labelledby="profile-tab-training"
        role="tabpanel"
        eyebrow="Training profile"
        title="Experience, goals, and tools"
        description="Set the main outcome, support goals, and equipment context that shape your recommendations."
      >
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
          <ProfileTextField
            id="profile-age"
            v-model="form.ageYears"
            :error="fieldErrors.ageYears"
            input-mode="numeric"
            label="Age"
            placeholder="29"
            type="number"
          />
          <ProfileTextField
            id="training-age-months"
            v-model="form.trainingAgeMonths"
            :error="fieldErrors.trainingAgeMonths"
            input-mode="numeric"
            label="Training age in months"
            placeholder="18"
            type="number"
          />
          <ProfileTextField
            id="current-bodyweight"
            v-model="form.currentBodyweightValue"
            :error="fieldErrors.currentBodyweightValue"
            input-mode="decimal"
            label="Current bodyweight"
            placeholder="72.5"
            type="number"
          />
          <ProfileTextField
            id="profile-height"
            v-model="form.heightValue"
            :error="fieldErrors.heightValue"
            input-mode="decimal"
            label="Height"
            placeholder="178"
            type="number"
          />
          <ProfileSelectField
            id="experience-level"
            v-model="form.experienceLevel"
            :error="fieldErrors.experienceLevel"
            label="Experience level"
            :options="experienceLevelOptions"
          />
          <ProfileChoiceGrid
            v-model="form.weightTrend"
            columns="compact"
            :error="fieldErrors.weightTrend"
            label="Weight trend"
            name="profile-weight-trend"
            :options="weightTrendOptions"
          />
        </div>

        <div class="mt-5 space-y-5">
          <ProfileChoiceGrid
            v-model="form.priorSportBackground"
            :error="fieldErrors.priorSportBackground"
            help="Choose up to four. Pick 'None yet' if you are starting without a useful carryover."
            label="Relevant background"
            :max-selections="4"
            multiple
            name="profile-prior-sport"
            :options="priorSportOptions"
          />
          <ProfileChoiceGrid
            v-model="form.primaryGoal"
            :error="fieldErrors.primaryGoal"
            label="Primary goal"
            name="primary-goal"
            :options="goalOptions"
          />
          <ProfileChoiceGrid
            v-model="form.secondaryGoals"
            :error="fieldErrors.secondaryGoals"
            help="Pick up to two support goals that work with your primary goal."
            label="Secondary goals"
            :max-selections="2"
            multiple
            name="secondary-goals"
            :options="compatibleSecondaryGoalOptions"
          />
          <div class="border-line-subtle border-t pt-5">
            <div class="mb-4">
              <h3 class="text-ink-primary text-base font-semibold">Skill roadmap</h3>
              <p class="text-ink-secondary mt-1 text-sm leading-6">
                Keep this specific. The primary roadmap receives the main progression decisions while secondary targets
                get lighter exposure.
              </p>
            </div>
            <div class="space-y-5">
              <section
                v-if="hasGoalCandidates"
                class="border-line-subtle bg-surface-primary rounded-card shadow-card-soft border p-4"
              >
                <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                  <div>
                    <p class="text-accent-primary text-xs font-semibold tracking-[0.16em] uppercase">
                      Candidate roadmap mix
                    </p>
                    <h4 class="text-ink-primary mt-2 text-base font-semibold">
                      Use the calculated candidates from your current profile.
                    </h4>
                  </div>
                  <p class="text-ink-muted max-w-xl text-sm leading-6">
                    Basics that are already owned stay as support work instead of becoming the main roadmap.
                  </p>
                </div>
                <div v-if="foundationCandidates.length" class="mt-4 grid gap-3 md:grid-cols-3">
                  <article
                    v-for="candidate in foundationCandidates"
                    :key="candidate.skill"
                    class="border-line-subtle bg-surface-elevated rounded-control border p-3"
                  >
                    <div class="flex items-start justify-between gap-3">
                      <div>
                        <p class="text-ink-primary text-sm font-semibold">{{ candidate.label }}</p>
                        <p class="text-ink-muted mt-1 text-xs leading-5">{{ candidate.reason }}</p>
                      </div>
                      <span
                        class="bg-accent-primary-soft text-ink-primary rounded-full px-2.5 py-1 text-xs font-semibold"
                      >
                        {{ candidateRoleLabel(candidate) }}
                      </span>
                    </div>
                  </article>
                </div>
                <p class="text-ink-muted mt-3 text-sm font-semibold">Foundation kept in every plan</p>
              </section>
              <ProfileChoiceGrid
                v-else
                v-model="targetSkillValues"
                :error="fieldErrors.targetSkillsText"
                help="Choose up to three active skill or strength outcomes for the current block."
                label="Active skill targets"
                :max-selections="3"
                multiple
                name="profile-target-skills"
                :options="targetSkillOptions"
              />
              <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_18rem]">
                <div class="space-y-5">
                  <ProfileChoiceGrid
                    v-if="primaryTargetOptions.length"
                    v-model="form.primaryTargetSkill"
                    :error="fieldErrors.primaryTargetSkill"
                    help="Choose the one outcome the current block should prioritize."
                    label="Primary roadmap"
                    name="profile-primary-target"
                    :options="primaryTargetOptions"
                  />
                  <ProfileChoiceGrid
                    v-if="secondaryTargetSkillOptions.length"
                    v-model="form.secondaryTargetSkills"
                    :error="fieldErrors.secondaryTargetSkills"
                    help="Pick up to three support targets that should not compete with the primary roadmap."
                    label="Compatible side goals"
                    :max-selections="3"
                    multiple
                    name="profile-secondary-targets"
                    :options="secondaryTargetSkillOptions"
                  />
                </div>
                <div class="border-line-subtle bg-surface-primary rounded-card border p-4">
                  <p class="text-ink-muted text-xs font-semibold tracking-[0.14em] uppercase">Placement snapshot</p>
                  <dl class="mt-4 space-y-3">
                    <div>
                      <dt class="text-ink-muted text-xs font-semibold">Priority</dt>
                      <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ primaryTargetLabel }}</dd>
                    </div>
                    <div>
                      <dt class="text-ink-muted text-xs font-semibold">Base support</dt>
                      <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ baseFocusLabel }}</dd>
                    </div>
                    <div>
                      <dt class="text-ink-muted text-xs font-semibold">Equipment</dt>
                      <dd class="text-ink-primary mt-1 text-sm font-semibold">
                        {{ selectedEquipmentCount }} tools selected
                      </dd>
                    </div>
                  </dl>
                  <RouterLink
                    :to="{ name: 'settings-equipment' }"
                    class="border-line-subtle bg-surface-elevated text-ink-secondary hover:border-line-strong hover:text-ink-primary rounded-control mt-5 inline-flex min-h-10 w-full items-center justify-center border px-3 text-sm font-semibold transition"
                  >
                    Review equipment
                  </RouterLink>
                </div>
              </div>
              <ProfileChoiceGrid
                v-model="form.longTermTargetSkills"
                :error="fieldErrors.longTermTargetSkills"
                help="Keep later aspirations visible without making them drive the current block."
                label="Long-term aspirations"
                :max-selections="8"
                multiple
                name="profile-long-term-targets"
                :options="futureTargetOptions"
              />
              <ProfileChoiceGrid
                v-model="form.baseFocusAreas"
                :error="fieldErrors.baseFocusAreas"
                help="Choose up to four base areas that should guide regressions and support work."
                label="Base-development focus"
                :max-selections="4"
                multiple
                name="profile-base-focus"
                :options="baseFocusOptions"
              />
            </div>
          </div>

          <div class="border-line-subtle border-t pt-5">
            <div class="mb-4">
              <h3 class="text-ink-primary text-base font-semibold">Current baseline tests</h3>
              <p class="text-ink-secondary mt-1 text-sm leading-6">
                These repeatable numbers keep recommendations tied to your actual strength instead of broad experience
                labels.
              </p>
            </div>
            <div class="grid gap-4 xl:grid-cols-2">
              <div class="border-line-subtle bg-surface-elevated rounded-card shadow-card-soft border p-4">
                <h4 class="text-ink-primary text-sm font-semibold">Push-up strength</h4>
                <p class="text-ink-muted mt-1 text-sm leading-5">Floor reps with a rigid bodyline.</p>
                <ProfileTextField
                  id="profile-push-up-reps"
                  v-model="form.baselineTests.pushUpMaxReps"
                  class="mt-4"
                  input-mode="numeric"
                  label="Max push-ups"
                  placeholder="18"
                  type="number"
                />
              </div>
              <div class="border-line-subtle bg-surface-elevated rounded-card shadow-card-soft border p-4">
                <h4 class="text-ink-primary text-sm font-semibold">Pull-up strength</h4>
                <p class="text-ink-muted mt-1 text-sm leading-5">Reps from a dead hang or 0 if not there yet.</p>
                <ProfileTextField
                  id="profile-pull-up-reps"
                  v-model="form.baselineTests.pullUpMaxReps"
                  class="mt-4"
                  input-mode="numeric"
                  label="Max pull-ups"
                  placeholder="4"
                  type="number"
                />
              </div>
              <div class="border-line-subtle bg-surface-elevated rounded-card shadow-card-soft border p-4">
                <h4 class="text-ink-primary text-sm font-semibold">Row capacity</h4>
                <p class="text-ink-muted mt-1 text-sm leading-5">
                  Horizontal pulling evidence for regressions, volume, and readiness.
                </p>
                <div class="mt-4 grid gap-4 sm:grid-cols-[minmax(0,1fr)_8rem]">
                  <ProfileSelectField
                    id="profile-row-variant"
                    v-model="form.baselineTests.rowVariant"
                    label="Row type"
                    :options="rowVariantOptions"
                  />
                  <ProfileTextField
                    id="profile-row-reps"
                    v-model="form.baselineTests.rowMaxReps"
                    input-mode="numeric"
                    label="Best reps"
                    placeholder="12"
                    type="number"
                  />
                </div>
              </div>
              <div class="border-line-subtle bg-surface-elevated rounded-card shadow-card-soft border p-4">
                <h4 class="text-ink-primary text-sm font-semibold">Dip strength</h4>
                <p class="text-ink-muted mt-1 text-sm leading-5">Clean parallel-bar reps with controlled depth.</p>
                <ProfileTextField
                  id="profile-dip-reps"
                  v-model="form.baselineTests.dipMaxReps"
                  class="mt-4"
                  input-mode="numeric"
                  label="Max dips"
                  placeholder="6"
                  type="number"
                />
              </div>
              <div class="border-line-subtle bg-surface-elevated rounded-card shadow-card-soft border p-4">
                <h4 class="text-ink-primary text-sm font-semibold">Support and hang tolerance</h4>
                <p class="text-ink-muted mt-1 text-sm leading-5">
                  Useful for pull-up, dip, ring, and straight-arm roadmap placement.
                </p>
                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                  <ProfileTextField
                    id="profile-passive-hang"
                    v-model="form.baselineTests.passiveHangSeconds"
                    input-mode="numeric"
                    label="Passive hang"
                    placeholder="45"
                    type="number"
                  />
                  <ProfileTextField
                    id="profile-top-support"
                    v-model="form.baselineTests.topSupportHoldSeconds"
                    input-mode="numeric"
                    label="Top support hold"
                    placeholder="25"
                    type="number"
                  />
                </div>
              </div>
              <div class="border-line-subtle bg-surface-elevated rounded-card shadow-card-soft border p-4">
                <h4 class="text-ink-primary text-sm font-semibold">Legs and bodyline</h4>
                <p class="text-ink-muted mt-1 text-sm leading-5">
                  Barbell squat capacity and hollow body control for placement.
                </p>
                <div class="mt-4 grid gap-4 sm:grid-cols-3">
                  <ProfileTextField
                    id="profile-squat-load"
                    v-model="form.baselineTests.squatBarbellLoadValue"
                    input-mode="numeric"
                    :label="`Barbell squat load (${form.bodyweightUnit})`"
                    placeholder="100"
                    type="number"
                  />
                  <ProfileTextField
                    id="profile-squat-load-reps"
                    v-model="form.baselineTests.squatBarbellReps"
                    input-mode="numeric"
                    label="Reps at that load"
                    placeholder="5"
                    type="number"
                  />
                  <ProfileTextField
                    id="profile-hollow-hold"
                    v-model="form.baselineTests.hollowHoldSeconds"
                    input-mode="numeric"
                    label="Hollow body hold"
                    placeholder="35"
                    type="number"
                  />
                </div>
                <div class="border-line-subtle mt-5 border-t pt-4">
                  <h5 class="text-ink-primary text-sm font-semibold">Lower-body fallback</h5>
                  <p class="text-ink-muted mt-1 text-sm leading-5">
                    Use the strongest repeatable lower-body pattern if barbell data is not available.
                  </p>
                  <div class="mt-4 grid gap-4 sm:grid-cols-[minmax(0,1fr)_8rem_8rem]">
                    <ProfileSelectField
                      id="profile-lower-body-variant"
                      v-model="form.baselineTests.lowerBodyVariant"
                      label="Pattern"
                      :options="lowerBodyVariantOptions"
                    />
                    <ProfileTextField
                      id="profile-lower-body-reps"
                      v-model="form.baselineTests.lowerBodyReps"
                      input-mode="numeric"
                      label="Best reps"
                      placeholder="12"
                      type="number"
                    />
                    <ProfileTextField
                      id="profile-lower-body-load"
                      v-model="form.baselineTests.lowerBodyLoadValue"
                      input-mode="decimal"
                      :label="`Load (${form.baselineTests.lowerBodyLoadUnit})`"
                      placeholder="0"
                      type="number"
                    />
                  </div>
                </div>
              </div>
            </div>
            <p v-if="fieldErrors.baselineTests" class="text-status-danger mt-3 text-sm leading-5">
              {{ fieldErrors.baselineTests }}
            </p>
          </div>

          <ProfileRoadmapModuleFields v-model:form="form" :errors="fieldErrors" />

          <div class="border-line-subtle border-t pt-5">
            <div class="mb-4">
              <h3 class="text-ink-primary text-base font-semibold">Weighted strength baseline</h3>
              <p class="text-ink-secondary mt-1 text-sm leading-6">
                Fill this in when weighted calisthenics is part of the roadmap. It gives future plans a useful loading
                anchor.
              </p>
            </div>
            <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_12rem]">
              <ProfileChoiceGrid
                v-model="form.weightedBaselines.experience"
                columns="compact"
                label="Weighted experience"
                name="profile-weighted-experience"
                :options="weightedExperienceOptions"
              />
              <ProfileChoiceGrid
                v-model="form.weightedBaselines.unit"
                columns="compact"
                label="Load unit"
                name="profile-weighted-unit"
                :options="bodyweightUnitOptions"
              />
            </div>
            <div v-if="weightedTargetSelected || form.weightedBaselines.experience !== 'none'" class="mt-5 space-y-3">
              <div class="flex flex-wrap items-center justify-between gap-3">
                <h4 class="text-ink-primary text-sm font-semibold">Recent weighted test sets</h4>
                <UiButton variant="secondary" type="button" @click="addWeightedMovement">Add test set</UiButton>
              </div>
              <div
                v-for="(movement, index) in form.weightedBaselines.movements"
                :key="index"
                class="border-line-subtle bg-surface-primary rounded-card grid gap-3 border p-4 md:grid-cols-[1.2fr_1fr_1fr_1fr_auto]"
              >
                <ProfileSelectField
                  :id="`profile-weighted-movement-${index}`"
                  v-model="movement.movement"
                  label="Movement"
                  :options="weightedMovementOptions"
                />
                <ProfileTextField
                  :id="`profile-weighted-load-${index}`"
                  v-model="movement.externalLoadValue"
                  input-mode="decimal"
                  label="Added load"
                  placeholder="10"
                  type="number"
                />
                <ProfileTextField
                  :id="`profile-weighted-reps-${index}`"
                  v-model="movement.reps"
                  input-mode="numeric"
                  label="Reps"
                  placeholder="5"
                  type="number"
                />
                <ProfileTextField
                  :id="`profile-weighted-rir-${index}`"
                  v-model="movement.rir"
                  input-mode="numeric"
                  label="RIR"
                  placeholder="2"
                  type="number"
                />
                <UiButton class="self-end" variant="secondary" type="button" @click="removeWeightedMovement(index)">
                  Remove
                </UiButton>
              </div>
            </div>
          </div>
        </div>
      </ProfileFormSection>

      <ProfileFormSection
        v-if="activeSection === 'setup'"
        id="profile-panel-setup"
        aria-labelledby="profile-tab-setup"
        role="tabpanel"
        eyebrow="Training setup"
        title="Schedule and session shape"
        description="Set where, when, and how often you can train."
      >
        <div class="space-y-5">
          <ProfileChoiceGrid
            v-model="form.trainingLocations"
            columns="compact"
            :error="fieldErrors.trainingLocations"
            label="Training locations"
            multiple
            name="training-locations"
            :options="trainingLocationOptions"
          />
          <ProfileChoiceGrid
            v-model="form.preferredTrainingDays"
            columns="compact"
            :error="fieldErrors.preferredTrainingDays"
            label="Preferred training days"
            multiple
            name="preferred-training-days"
            :options="trainingDayOptions"
          />
        </div>

        <div class="mt-5 grid gap-4 md:grid-cols-2">
          <ProfileTextField
            id="preferred-session-minutes"
            v-model="form.preferredSessionMinutes"
            :error="fieldErrors.preferredSessionMinutes"
            help="Use this as an upper limit, not an exact duration."
            input-mode="numeric"
            label="Up to minutes"
            placeholder="45"
            type="number"
          />
          <ProfileTextField
            id="weekly-session-goal"
            v-model="form.weeklySessionGoal"
            :error="fieldErrors.weeklySessionGoal"
            input-mode="numeric"
            label="Max sessions per week"
            placeholder="3"
            type="number"
          />
        </div>
      </ProfileFormSection>

      <ProfileFormSection
        v-if="activeSection === 'coaching'"
        id="profile-panel-coaching"
        aria-labelledby="profile-tab-coaching"
        role="tabpanel"
        eyebrow="Coaching style"
        title="How recommendations should feel"
        description="Choose how assertive, intense, and structured your options should be when the app has enough evidence."
      >
        <div class="space-y-5">
          <ProfileChoiceGrid
            v-model="form.progressionPace"
            :error="fieldErrors.progressionPace"
            label="Progression pace"
            name="progression-pace"
            :options="progressionPaceOptions"
          />
          <div class="grid gap-4 md:grid-cols-3">
            <ProfileSelectField
              id="intensity-preference"
              v-model="form.intensityPreference"
              :error="fieldErrors.intensityPreference"
              label="Intensity preference"
              :options="intensityPreferenceOptions"
            />
            <ProfileSelectField
              id="effort-tracking-preference"
              v-model="form.effortTrackingPreference"
              :error="fieldErrors.effortTrackingPreference"
              label="Effort tracking"
              :options="effortTrackingOptions"
            />
            <ProfileSelectField
              id="deload-preference"
              v-model="form.deloadPreference"
              :error="fieldErrors.deloadPreference"
              label="Deload handling"
              :options="deloadPreferenceOptions"
            />
          </div>
          <ProfileChoiceGrid
            v-model="form.sessionStructurePreferences"
            :error="fieldErrors.sessionStructurePreferences"
            help="Pick up to three preferences. The app can still adapt each session when your plan needs it."
            label="Session structure preferences"
            :max-selections="3"
            multiple
            name="session-structure-preferences"
            :options="sessionStructureOptions"
          />
        </div>
      </ProfileFormSection>

      <ProfileFormSection
        v-if="activeSection === 'limitations'"
        id="profile-panel-limitations"
        aria-labelledby="profile-tab-limitations"
        role="tabpanel"
        eyebrow="Limitations"
        title="Pain flags and private notes"
        description="Use this for training constraints. Leverly can adjust exercise options, but it is not medical software and cannot diagnose or treat injuries."
      >
        <div class="mb-6 space-y-4">
          <div>
            <h3 class="text-ink-primary text-base font-semibold">Position checks</h3>
            <p class="text-ink-secondary mt-1 text-sm leading-6">
              Mark the positions that change skill selection, warm-ups, and progression speed.
            </p>
          </div>
          <div class="grid gap-4 lg:grid-cols-2">
            <div
              v-for="check in mobilityCheckOptions"
              :key="check.value"
              class="border-line-subtle bg-surface-primary rounded-card border p-4"
            >
              <ProfileSelectField
                :id="`profile-mobility-${check.value}`"
                v-model="form.mobilityChecks[check.value]"
                :help="check.description"
                :label="check.label"
                :options="mobilityStatusOptions"
              />
            </div>
          </div>
        </div>

        <div class="border-line-subtle mb-6 border-t pt-6">
          <div>
            <h3 class="text-ink-primary text-base font-semibold">Pain flags by area</h3>
            <p class="text-ink-secondary mt-1 text-sm leading-6">
              Keep these current so higher-risk progressions can slow down, swap, or wait when a region is irritated.
            </p>
            <p v-if="fieldErrors.painFlags" class="text-status-danger mt-2 text-sm leading-5">
              {{ fieldErrors.painFlags }}
            </p>
          </div>
          <div class="mt-4 grid gap-4 xl:grid-cols-2">
            <div
              v-for="region in painFlagRegionOptions"
              :key="region.value"
              class="border-line-subtle bg-surface-elevated rounded-card shadow-card-soft border p-4"
            >
              <h4 class="text-ink-primary text-sm font-semibold">{{ region.label }}</h4>
              <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <ProfileSelectField
                  :id="`profile-pain-${region.value}-severity`"
                  v-model="form.painFlags[region.value].severity"
                  label="Severity"
                  :options="limitationSeverityOptions"
                />
                <ProfileSelectField
                  :id="`profile-pain-${region.value}-status`"
                  v-model="form.painFlags[region.value].status"
                  label="Status"
                  :options="limitationStatusOptions"
                />
              </div>
              <ProfileTextAreaField
                :id="`profile-pain-${region.value}-notes`"
                v-model="form.painFlags[region.value].notes"
                class="mt-4"
                label="Notes"
                placeholder="What changes training for this area?"
              />
            </div>
          </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
          <ProfileSelectField
            id="limitation-area"
            v-model="form.movementLimitation.area"
            :error="fieldErrors.movementLimitation"
            label="Area"
            :options="limitationAreaOptions"
          />
          <ProfileSelectField
            id="limitation-severity"
            v-model="form.movementLimitation.severity"
            label="Severity"
            :options="limitationSeverityOptions"
          />
          <ProfileSelectField
            id="limitation-status"
            v-model="form.movementLimitation.status"
            label="Status"
            :options="limitationStatusOptions"
          />
        </div>
        <div class="mt-5 grid gap-4 lg:grid-cols-2">
          <ProfileTextAreaField
            id="limitation-notes"
            v-model="form.movementLimitation.notes"
            help="Short notes for exercise selection, warm-up needs, or movements to avoid."
            label="Limitation notes"
            placeholder="Wrist extension needs a longer warm-up."
          />
          <ProfileTextAreaField
            id="injury-notes"
            v-model="form.injuryNotes"
            :error="fieldErrors.injuryNotes"
            help="Private context for training decisions. Persistent or severe pain should be handled with a qualified professional."
            label="Injury notes"
            placeholder="Left wrist can get irritated under high volume."
          />
        </div>
      </ProfileFormSection>

      <div
        class="border-line-subtle bg-surface-elevated shadow-card rounded-card sticky bottom-20 z-20 flex flex-col gap-3 border p-4 sm:bottom-4 sm:flex-row sm:items-center sm:justify-between lg:static"
      >
        <p class="text-ink-secondary text-sm leading-6">
          {{ isLoading ? 'Loading your profile...' : 'Save changes before using these inputs for recommendations.' }}
        </p>
        <UiButton size="lg" type="submit" :disabled="isSaving || isLoading">
          {{ isSaving ? 'Saving...' : 'Save profile' }}
        </UiButton>
      </div>
    </form>
  </section>
</template>
