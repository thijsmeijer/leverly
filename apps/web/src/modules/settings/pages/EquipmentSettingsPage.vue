<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { UiButton } from '@/shared/ui'
import EquipmentToggleCard from '../components/EquipmentToggleCard.vue'
import ProfileStatusBanner from '../components/ProfileStatusBanner.vue'
import { useSettings } from '../composables/useSettings'
import { equipmentCategories, equipmentPresets } from '../data/profileOptions'

const {
  fieldErrors,
  form,
  hasErrors,
  isLoading,
  isSaving,
  loadError,
  loadProfile,
  profileId,
  saveEquipment,
  saveError,
  saveSuccess,
} = useSettings()

const allEquipment = computed(() => equipmentCategories.flatMap((category) => category.items))
const selectedEquipment = computed(() =>
  allEquipment.value.filter((item) => form.availableEquipment.includes(item.value)),
)
const selectedCoverage = computed(() =>
  [...new Set(selectedEquipment.value.flatMap((item) => item.unlocks))].sort((first, second) =>
    first.localeCompare(second),
  ),
)
const selectedCategories = computed(() => new Set(selectedEquipment.value.map((item) => item.category)).size)
const selectedEquipmentLabel = computed(() =>
  selectedEquipment.value.length === 1 ? '1 item selected' : `${selectedEquipment.value.length} items selected`,
)

onMounted(() => {
  void loadProfile()
})

function isSelected(value: string): boolean {
  return form.availableEquipment.includes(value)
}

function toggleEquipment(value: string): void {
  form.availableEquipment = isSelected(value)
    ? form.availableEquipment.filter((item) => item !== value)
    : [...form.availableEquipment, value]
}

function addPreset(values: string[]): void {
  form.availableEquipment = [...new Set([...form.availableEquipment, ...values])]
}

function clearEquipment(): void {
  form.availableEquipment = []
}
</script>

<template>
  <section class="space-y-6" aria-labelledby="equipment-settings-heading">
    <div
      class="border-line-subtle bg-surface-elevated shadow-card rounded-card overflow-hidden border lg:grid lg:grid-cols-[minmax(0,1fr)_22rem]"
    >
      <div class="p-5 sm:p-7">
        <p class="text-accent-primary text-xs font-semibold tracking-[0.18em] uppercase">Equipment context</p>
        <h2 id="equipment-settings-heading" class="text-ink-primary mt-3 text-2xl font-semibold tracking-normal">
          Tell Leverly what you can actually train with.
        </h2>
        <p class="text-ink-secondary mt-3 max-w-3xl text-base leading-7">
          Keep this list honest so exercise options match your home setup, park setup, gym access, or travel kit.
        </p>
        <RouterLink
          :to="{ name: 'settings-profile' }"
          class="border-line-subtle bg-surface-primary text-ink-secondary hover:border-line-strong hover:text-ink-primary rounded-control mt-5 inline-flex min-h-11 items-center justify-center border px-4 text-sm font-semibold transition"
        >
          Back to profile
        </RouterLink>
      </div>

      <aside class="border-line-subtle bg-surface-muted/70 border-t p-5 sm:p-7 lg:border-t-0 lg:border-l">
        <p class="text-ink-muted text-xs font-semibold tracking-[0.18em] uppercase">Current setup</p>
        <dl class="divide-line-subtle mt-4 grid grid-cols-2 gap-x-4 gap-y-0 divide-y sm:divide-y-0">
          <div class="py-3">
            <dt class="text-ink-muted text-xs font-semibold">Equipment</dt>
            <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ selectedEquipmentLabel }}</dd>
          </div>
          <div class="py-3">
            <dt class="text-ink-muted text-xs font-semibold">Categories</dt>
            <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ selectedCategories }} covered</dd>
          </div>
          <div class="py-3">
            <dt class="text-ink-muted text-xs font-semibold">Profile</dt>
            <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ profileId ? 'Saved' : 'New' }}</dd>
          </div>
          <div class="py-3">
            <dt class="text-ink-muted text-xs font-semibold">Signals</dt>
            <dd class="text-ink-primary mt-1 text-sm font-semibold">{{ selectedCoverage.length }} unlocked</dd>
          </div>
        </dl>
      </aside>
    </div>

    <ProfileStatusBanner v-if="loadError" tone="danger" :message="loadError" />
    <ProfileStatusBanner v-if="saveError" tone="danger" :message="saveError" />
    <ProfileStatusBanner v-if="saveSuccess" tone="success" message="Equipment settings saved." />
    <ProfileStatusBanner
      v-if="hasErrors"
      tone="warning"
      message="Some equipment selections need attention before they can be saved."
    />

    <section class="space-y-3" aria-labelledby="equipment-presets-heading">
      <div>
        <p class="text-accent-primary text-xs font-semibold tracking-[0.18em] uppercase">Fast setup</p>
        <h3 id="equipment-presets-heading" class="text-ink-primary mt-2 text-lg font-semibold tracking-normal">
          Add a realistic training kit
        </h3>
      </div>

      <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
        <button
          v-for="preset in equipmentPresets"
          :key="preset.label"
          class="border-line-subtle bg-surface-elevated text-ink-secondary shadow-card-soft hover:border-line-strong hover:bg-surface-overlay rounded-card min-h-28 border p-4 text-left transition"
          type="button"
          @click="addPreset(preset.equipment)"
        >
          <span class="text-ink-primary block text-sm font-semibold">{{ preset.label }}</span>
          <span class="mt-2 block text-sm leading-6">{{ preset.description }}</span>
        </button>
      </div>
    </section>

    <form class="space-y-6" novalidate @submit.prevent="saveEquipment">
      <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="space-y-7">
          <section
            v-for="category in equipmentCategories"
            :key="category.id"
            class="space-y-3"
            :aria-labelledby="`equipment-category-${category.id}`"
          >
            <div>
              <h3 :id="`equipment-category-${category.id}`" class="text-ink-primary text-lg font-semibold">
                {{ category.title }}
              </h3>
              <p class="text-ink-secondary mt-1 text-sm leading-6">{{ category.description }}</p>
            </div>

            <div class="grid gap-3 md:grid-cols-2 2xl:grid-cols-3">
              <EquipmentToggleCard
                v-for="item in category.items"
                :key="item.value"
                :item="item"
                :selected="isSelected(item.value)"
                @toggle="toggleEquipment"
              />
            </div>
          </section>
        </div>

        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start" aria-label="Equipment summary">
          <section class="border-line-subtle bg-surface-elevated shadow-card rounded-card border p-5">
            <div class="flex items-start justify-between gap-4">
              <div>
                <p class="text-accent-primary text-xs font-semibold tracking-[0.18em] uppercase">Selected</p>
                <h3 class="text-ink-primary mt-2 text-lg font-semibold">{{ selectedEquipmentLabel }}</h3>
              </div>
              <button
                class="text-ink-muted hover:text-ink-primary rounded-control px-2 py-1 text-sm font-semibold transition"
                type="button"
                @click="clearEquipment"
              >
                Clear
              </button>
            </div>

            <div v-if="selectedEquipment.length" class="mt-4 flex flex-wrap gap-2">
              <span
                v-for="item in selectedEquipment"
                :key="item.value"
                class="bg-accent-primary-soft text-ink-primary rounded-full px-3 py-1.5 text-xs font-semibold"
              >
                {{ item.label }}
              </span>
            </div>
            <p v-else class="text-ink-secondary mt-4 text-sm leading-6">
              Select the tools you can reliably use. A blank setup still saves, but recommendations will stay basic.
            </p>
          </section>

          <section class="border-line-subtle bg-surface-elevated shadow-card rounded-card border p-5">
            <p class="text-accent-primary text-xs font-semibold tracking-[0.18em] uppercase">Unlocked signals</p>
            <div v-if="selectedCoverage.length" class="mt-4 flex flex-wrap gap-2">
              <span
                v-for="coverage in selectedCoverage"
                :key="coverage"
                class="border-line-subtle text-ink-secondary rounded-full border bg-white px-3 py-1.5 text-xs font-semibold"
              >
                {{ coverage }}
              </span>
            </div>
            <p v-else class="text-ink-secondary mt-4 text-sm leading-6">Coverage appears as you select equipment.</p>
          </section>
        </aside>
      </div>

      <p v-if="fieldErrors.availableEquipment" class="text-status-danger text-sm leading-5">
        {{ fieldErrors.availableEquipment }}
      </p>

      <div
        class="border-line-subtle bg-surface-elevated shadow-card rounded-card sticky bottom-20 z-20 flex flex-col gap-3 border p-4 sm:bottom-4 sm:flex-row sm:items-center sm:justify-between lg:static"
      >
        <p class="text-ink-secondary text-sm leading-6">
          {{ isLoading ? 'Loading your equipment...' : 'Save equipment before using it for recommendations.' }}
        </p>
        <UiButton size="lg" type="submit" :disabled="isSaving || isLoading">
          {{ isSaving ? 'Saving...' : 'Save equipment' }}
        </UiButton>
      </div>
    </form>
  </section>
</template>
