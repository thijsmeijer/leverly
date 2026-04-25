<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { UiButton } from '@/shared/ui'
import ProfileChoiceGrid from '../components/ProfileChoiceGrid.vue'
import ProfileFormSection from '../components/ProfileFormSection.vue'
import ProfileSelectField from '../components/ProfileSelectField.vue'
import ProfileStatusBanner from '../components/ProfileStatusBanner.vue'
import ProfileTextAreaField from '../components/ProfileTextAreaField.vue'
import ProfileTextField from '../components/ProfileTextField.vue'
import { useSettings } from '../composables/useSettings'
import {
  bodyweightUnitOptions,
  deloadPreferenceOptions,
  effortTrackingOptions,
  equipmentOptions,
  experienceLevelOptions,
  goalOptions,
  intensityPreferenceOptions,
  limitationAreaOptions,
  limitationSeverityOptions,
  limitationStatusOptions,
  progressionPaceOptions,
  sessionStructureOptions,
  trainingDayOptions,
  trainingLocationOptions,
  trainingTimeOptions,
  unitSystemOptions,
} from '../data/profileOptions'

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
const primaryGoalLabel = computed(
  () => goalOptions.find((option) => option.value === form.primaryGoal)?.label ?? 'Strength',
)

onMounted(() => {
  void loadProfile()
})
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

        <div class="mt-5 flex flex-wrap gap-2" aria-label="Settings sections">
          <RouterLink
            :to="{ name: 'settings-profile' }"
            class="bg-accent-primary text-ink-inverse rounded-control shadow-control px-3 py-2 text-sm font-semibold"
            aria-current="page"
          >
            Profile
          </RouterLink>
          <RouterLink
            :to="{ name: 'settings-equipment' }"
            class="border-line-subtle bg-surface-primary text-ink-secondary hover:border-line-strong rounded-control border px-3 py-2 text-sm font-semibold transition"
          >
            Equipment
          </RouterLink>
          <RouterLink
            :to="{ name: 'settings-export' }"
            class="border-line-subtle bg-surface-primary text-ink-secondary hover:border-line-strong rounded-control border px-3 py-2 text-sm font-semibold transition"
          >
            Export
          </RouterLink>
        </div>
      </div>

      <aside class="border-line-subtle bg-surface-muted/70 border-t p-5 sm:p-7 lg:border-t-0 lg:border-l">
        <p class="text-ink-muted text-xs font-semibold tracking-[0.18em] uppercase">Current signal</p>
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

    <form class="space-y-5" novalidate @submit.prevent="saveProfile">
      <ProfileFormSection
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
        </div>
      </ProfileFormSection>

      <ProfileFormSection
        eyebrow="Training profile"
        title="Experience and goals"
        description="Give Leverly enough context to choose sensible options without guessing what kind of progress you want."
      >
        <div class="grid gap-4 md:grid-cols-3">
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
          <ProfileSelectField
            id="experience-level"
            v-model="form.experienceLevel"
            :error="fieldErrors.experienceLevel"
            label="Experience level"
            :options="experienceLevelOptions"
          />
        </div>

        <div class="mt-5 space-y-5">
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
            label="Secondary goals"
            multiple
            name="secondary-goals"
            :options="goalOptions"
          />
          <ProfileTextAreaField
            id="target-skills"
            v-model="form.targetSkillsText"
            :error="fieldErrors.targetSkillsText"
            help="Separate skills with commas or new lines."
            label="Target skills"
            placeholder="Freestanding handstand&#10;Strict muscle-up"
            :rows="3"
          />
        </div>
      </ProfileFormSection>

      <ProfileFormSection
        eyebrow="Training setup"
        title="Equipment and schedule"
        description="Match recommendations to the tools, places, and session shape you can reliably use."
      >
        <div class="space-y-5">
          <ProfileChoiceGrid
            v-model="form.availableEquipment"
            :error="fieldErrors.availableEquipment"
            label="Available equipment"
            multiple
            name="available-equipment"
            :options="equipmentOptions"
          />
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

        <div class="mt-5 grid gap-4 md:grid-cols-3">
          <ProfileTextField
            id="preferred-session-minutes"
            v-model="form.preferredSessionMinutes"
            :error="fieldErrors.preferredSessionMinutes"
            input-mode="numeric"
            label="Session length"
            placeholder="45"
            type="number"
          />
          <ProfileTextField
            id="weekly-session-goal"
            v-model="form.weeklySessionGoal"
            :error="fieldErrors.weeklySessionGoal"
            input-mode="numeric"
            label="Weekly sessions"
            placeholder="3"
            type="number"
          />
          <ProfileSelectField
            id="preferred-training-time"
            v-model="form.preferredTrainingTime"
            :error="fieldErrors.preferredTrainingTime"
            label="Preferred time"
            :options="trainingTimeOptions"
          />
        </div>
      </ProfileFormSection>

      <ProfileFormSection
        eyebrow="Recommendation bias"
        title="How Leverly should steer options"
        description="These settings help shape how conservative, intense, and structured your recommendations should feel."
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
            label="Session structure preferences"
            multiple
            name="session-structure-preferences"
            :options="sessionStructureOptions"
          />
        </div>
      </ProfileFormSection>

      <ProfileFormSection
        eyebrow="Limitations"
        title="Pain flags and private notes"
        description="Use this for training constraints. Leverly can adjust exercise options, but it is not medical software and cannot diagnose or treat injuries."
      >
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
