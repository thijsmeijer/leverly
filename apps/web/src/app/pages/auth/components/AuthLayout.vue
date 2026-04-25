<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { leverlyBrand } from '@/shared/brand'

defineProps<{
  eyebrow: string
  switchLabel: string
  switchRoute: 'login' | 'register'
  switchText: string
  title: string
}>()
</script>

<template>
  <main
    class="text-ink-primary min-h-screen overflow-hidden bg-[radial-gradient(circle_at_top_left,var(--accent-primary-soft),transparent_34rem),linear-gradient(135deg,var(--surface-primary)_0%,var(--surface-canvas)_52%,var(--surface-muted)_100%)] px-4 py-5 sm:px-6 lg:px-8"
  >
    <section
      class="mx-auto grid min-h-[calc(100vh-2.5rem)] w-full max-w-6xl items-center gap-6 lg:grid-cols-[1fr_minmax(24rem,29rem)]"
    >
      <div>
        <div class="rounded-card border-line-subtle bg-surface-elevated/82 shadow-card p-5 backdrop-blur sm:p-7">
          <p class="text-ink-muted text-xs font-semibold tracking-[0.18em] uppercase">{{ eyebrow }}</p>
          <h1 class="mt-3 text-3xl leading-tight font-semibold tracking-normal sm:text-4xl">
            {{ title }}
          </h1>
          <div class="mt-7">
            <slot />
          </div>
          <p class="text-ink-secondary mt-6 text-center text-sm">
            {{ switchText }}
            <RouterLink
              :to="{ name: switchRoute }"
              class="text-accent-primary hover:text-accent-primary-strong focus-visible:ring-accent-primary rounded-control px-1 font-semibold outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
            >
              {{ switchLabel }}
            </RouterLink>
          </p>
        </div>
      </div>

      <aside
        class="rounded-card bg-surface-inverse text-ink-inverse shadow-shell relative overflow-hidden p-6 sm:p-8 lg:min-h-[38rem]"
      >
        <div
          class="absolute inset-x-0 top-0 h-32 bg-[linear-gradient(90deg,rgba(123,209,173,0.24),rgba(148,191,231,0.2),transparent)]"
        />
        <div class="relative flex h-full flex-col">
          <RouterLink
            :to="{ name: 'dashboard' }"
            class="rounded-control focus-visible:ring-accent-primary focus-visible:ring-offset-surface-inverse inline-flex flex-col outline-none focus-visible:ring-2 focus-visible:ring-offset-4"
          >
            <span class="text-3xl font-semibold tracking-normal">{{ leverlyBrand.productName }}</span>
            <span class="text-ink-inverse/62 mt-2 text-sm">{{ leverlyBrand.tagline }}</span>
          </RouterLink>

          <div class="mt-12 grid gap-3">
            <div class="rounded-card border border-white/10 bg-white/10 p-4">
              <p class="text-sm font-semibold">Know what to train next</p>
              <p class="text-ink-inverse/68 mt-2 text-sm leading-6">
                Get smart options that keep your skills moving forward.
              </p>
            </div>
            <div class="rounded-card border border-white/10 bg-white/10 p-4">
              <p class="text-sm font-semibold">Made for bodyweight strength</p>
              <p class="text-ink-inverse/68 mt-2 text-sm leading-6">
                Built for calisthenics athletes chasing control, strength, and clean progress.
              </p>
            </div>
          </div>

          <div class="mt-auto pt-10">
            <div class="bg-accent-primary/25 h-2 w-28 rounded-full" />
            <p class="text-ink-inverse/72 mt-4 max-w-sm text-sm leading-6">
              Track the work. See the progress. Unlock what your body can do.
            </p>
          </div>
        </div>
      </aside>
    </section>
  </main>
</template>
