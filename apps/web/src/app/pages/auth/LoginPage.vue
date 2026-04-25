<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { UiCard, UiSectionHeader } from '@/shared/ui'

const route = useRoute()
const redirectPath = computed(() =>
  typeof route.query.redirect === 'string' ? route.query.redirect : '/app/dashboard',
)
</script>

<template>
  <main
    class="text-ink-primary min-h-screen bg-[radial-gradient(circle_at_top_left,var(--accent-primary-soft),transparent_32rem),linear-gradient(135deg,var(--surface-primary),var(--surface-canvas))] px-6 py-8"
  >
    <section class="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-md flex-col justify-center">
      <UiCard padding="lg">
        <UiSectionHeader
          eyebrow="Leverly"
          title="Sign in"
          description="Account access is required before you can continue."
        />
        <div class="rounded-card border-line-subtle bg-surface-muted text-ink-secondary mt-6 border p-4 text-sm">
          After signing in, continue to
          <span class="text-ink-primary font-medium">{{ redirectPath }}</span>
        </div>
      </UiCard>
    </section>
  </main>
</template>
