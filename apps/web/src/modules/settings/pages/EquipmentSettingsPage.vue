<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { UiButton } from '@/shared/ui'
import EquipmentToggleCard from '../components/EquipmentToggleCard.vue'
import ProfileStatusBanner from '../components/ProfileStatusBanner.vue'
import { useSettings } from '../composables/useSettings'
import { equipmentCategories } from '../data/profileOptions'

const {
  fieldErrors,
  form,
  hasErrors,
  isLoading,
  isSaving,
  loadError,
  loadProfile,
  saveEquipment,
  saveError,
  saveSuccess,
} = useSettings()

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
</script>

<template>
  <section class="space-y-6" aria-labelledby="equipment-settings-heading">
    <div class="border-line-subtle bg-surface-elevated shadow-card rounded-card border p-5 sm:p-7">
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

    <ProfileStatusBanner v-if="loadError" tone="danger" :message="loadError" />
    <ProfileStatusBanner v-if="saveError" tone="danger" :message="saveError" />
    <ProfileStatusBanner v-if="saveSuccess" tone="success" message="Equipment settings saved." />
    <ProfileStatusBanner
      v-if="hasErrors"
      tone="warning"
      message="Some equipment selections need attention before they can be saved."
    />

    <form class="space-y-6" novalidate @submit.prevent="saveEquipment">
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
