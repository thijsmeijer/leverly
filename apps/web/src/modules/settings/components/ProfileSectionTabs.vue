<script setup lang="ts">
type ProfileSection = {
  readonly id: string
  readonly label: string
  readonly summary: string
}

const model = defineModel<string>({ required: true })

defineProps<{
  sections: ProfileSection[]
}>()
</script>

<template>
  <div
    class="border-line-subtle bg-surface-primary shadow-card-soft rounded-card grid gap-2 border p-2 sm:grid-cols-2 xl:grid-cols-5"
    role="tablist"
    aria-label="Profile sections"
  >
    <button
      v-for="section in sections"
      :id="`profile-tab-${section.id}`"
      :key="section.id"
      class="rounded-control focus-visible:ring-accent-primary focus-visible:ring-offset-surface-primary min-h-16 px-3 py-3 text-left transition outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
      :class="
        model === section.id
          ? 'bg-accent-primary text-ink-inverse shadow-control'
          : 'text-ink-secondary hover:bg-accent-primary-soft hover:text-ink-primary'
      "
      :aria-controls="`profile-panel-${section.id}`"
      :aria-selected="model === section.id"
      role="tab"
      type="button"
      @click="model = section.id"
    >
      <span class="block text-sm font-semibold">{{ section.label }}</span>
      <span class="mt-1 block text-xs leading-5 opacity-75">{{ section.summary }}</span>
    </button>
  </div>
</template>
