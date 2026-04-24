<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const redirectPath = computed(() =>
  typeof route.query.redirect === 'string' ? route.query.redirect : '/app/dashboard',
)
</script>

<template>
  <main class="bg-surface-primary text-ink-primary min-h-screen px-6 py-8">
    <section class="mx-auto flex min-h-[calc(100vh-4rem)] w-full max-w-md flex-col justify-center">
      <div class="border-line-subtle bg-surface-elevated shadow-elevated rounded-2xl border p-6">
        <p class="text-accent-primary text-sm font-semibold">Leverly</p>
        <h1 class="mt-3 text-3xl font-semibold">Sign in</h1>
        <p class="text-ink-secondary mt-3 text-sm leading-6">Account access is required before you can continue.</p>
        <div class="border-line-subtle bg-surface-muted text-ink-secondary mt-6 rounded-xl border p-4 text-sm">
          After signing in, continue to
          <span class="text-ink-primary font-medium">{{ redirectPath }}</span>
        </div>
      </div>
    </section>
  </main>
</template>
