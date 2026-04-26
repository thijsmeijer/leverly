<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useSessionStore } from '@/app/stores/sessionStore'
import { leverlyBrand } from '@/shared/brand'
import {
  appNavigationItems,
  mobileNavigationItems,
  navigationGroupLabels,
  type AppNavigationItem,
} from './appNavigation'

const route = useRoute()
const router = useRouter()
const session = useSessionStore()

const navigationGroups = computed(() =>
  Object.entries(navigationGroupLabels).map(([name, label]) => ({
    name: name as AppNavigationItem['group'],
    label,
    items: appNavigationItems.filter((item) => item.group === name),
  })),
)

const currentItem = computed(
  () => appNavigationItems.find((item) => item.matchNames.includes(String(route.name ?? ''))) ?? appNavigationItems[0],
)

const pageTitle = computed(() => (typeof route.meta.title === 'string' ? route.meta.title : currentItem.value.label))
const pageSection = computed(() =>
  typeof route.meta.section === 'string' ? route.meta.section : navigationGroupLabels[currentItem.value.group],
)

function isActive(item: AppNavigationItem): boolean {
  return item.matchNames.includes(String(route.name ?? ''))
}

async function signOut(): Promise<void> {
  await session.logout()
  await router.push({ name: 'login' })
}
</script>

<template>
  <div class="bg-surface-inverse text-ink-primary min-h-screen lg:flex">
    <aside
      class="border-line-subtle bg-surface-elevated px-6 py-7 max-lg:hidden lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-72 lg:flex-col lg:border-r"
      aria-label="Primary"
    >
      <RouterLink
        :to="{ name: 'dashboard' }"
        class="rounded-control focus-visible:ring-accent-primary focus-visible:ring-offset-surface-elevated inline-flex min-h-11 flex-col justify-center outline-none focus-visible:ring-2 focus-visible:ring-offset-4"
      >
        <span class="text-ink-primary text-2xl font-semibold tracking-normal">{{ leverlyBrand.productName }}</span>
        <span class="text-ink-muted mt-1 text-sm leading-5">{{ leverlyBrand.tagline }}</span>
      </RouterLink>

      <nav class="mt-9 space-y-7" aria-label="Application sections">
        <section v-for="group in navigationGroups" :key="group.name" class="space-y-2">
          <p class="text-accent-primary px-3 text-xs font-semibold tracking-[0.18em] uppercase">
            {{ group.label }}
          </p>
          <RouterLink
            v-for="item in group.items"
            :key="item.routeName"
            :to="item.to"
            class="rounded-control focus-visible:ring-accent-primary focus-visible:ring-offset-surface-elevated block border px-3 py-3 transition duration-200 outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
            :class="
              isActive(item)
                ? 'border-accent-primary/40 bg-accent-primary-soft text-ink-primary shadow-card-soft'
                : 'text-ink-secondary hover:text-ink-primary hover:border-line-subtle hover:bg-surface-overlay border-transparent'
            "
            :aria-current="isActive(item) ? 'page' : undefined"
          >
            <span class="block text-sm font-semibold">{{ item.label }}</span>
            <span class="text-ink-muted mt-1 block text-xs leading-5">{{ item.description }}</span>
          </RouterLink>
        </section>
      </nav>
    </aside>

    <div
      class="min-h-screen min-w-0 flex-1 bg-[radial-gradient(circle_at_top_left,var(--accent-primary-soft),transparent_34rem),linear-gradient(135deg,var(--surface-primary)_0%,var(--surface-canvas)_54%,var(--surface-muted)_100%)]"
    >
      <header
        class="border-line-subtle bg-surface-primary/86 sticky top-0 z-30 border-b px-4 py-3 backdrop-blur sm:px-6 lg:px-8"
      >
        <div class="mx-auto flex max-w-[88rem] items-center justify-between gap-4">
          <div class="min-w-0">
            <p class="text-ink-muted text-xs font-semibold tracking-[0.18em] uppercase">{{ pageSection }}</p>
            <h1 class="text-ink-primary mt-1 truncate text-xl font-semibold tracking-normal sm:text-2xl">
              {{ pageTitle }}
            </h1>
          </div>

          <div class="flex items-center gap-2">
            <RouterLink
              :to="{ name: 'today' }"
              class="rounded-control bg-accent-primary text-ink-inverse shadow-control focus-visible:ring-accent-primary focus-visible:ring-offset-surface-primary hover:bg-accent-primary-strong inline-flex min-h-11 items-center justify-center px-4 text-sm font-semibold transition outline-none hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-offset-2"
            >
              Start
            </RouterLink>
            <RouterLink
              :to="{ name: 'settings-profile' }"
              class="rounded-control border-line-subtle bg-surface-elevated text-ink-primary shadow-card-soft focus-visible:ring-accent-primary focus-visible:ring-offset-surface-primary hover:border-line-strong hidden min-h-11 items-center justify-center border px-4 text-sm font-semibold transition outline-none hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-offset-2 sm:inline-flex"
            >
              Profile
            </RouterLink>
            <button
              class="rounded-control text-ink-secondary hover:bg-accent-primary-soft hover:text-ink-primary focus-visible:ring-accent-primary focus-visible:ring-offset-surface-primary inline-flex min-h-11 items-center justify-center px-3 text-sm font-semibold transition outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
              type="button"
              @click="signOut"
            >
              Sign out
            </button>
          </div>
        </div>
      </header>

      <main class="mx-auto w-full max-w-[88rem] px-4 pt-5 pb-28 sm:px-6 lg:px-8 lg:pt-7 lg:pb-10">
        <RouterView />
      </main>
    </div>

    <nav
      class="border-line-subtle bg-surface-elevated/96 shadow-shell fixed inset-x-0 bottom-0 z-40 border-t px-2 pt-2 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur lg:hidden"
      aria-label="Mobile primary"
    >
      <div class="mx-auto grid max-w-md grid-cols-5 gap-1">
        <RouterLink
          v-for="item in mobileNavigationItems"
          :key="item.routeName"
          :to="item.to"
          class="rounded-control focus-visible:ring-accent-primary focus-visible:ring-offset-surface-elevated flex min-h-14 flex-col items-center justify-center px-1 text-center text-xs font-semibold transition outline-none focus-visible:ring-2 focus-visible:ring-offset-2"
          :class="
            isActive(item)
              ? 'bg-accent-primary shadow-control text-white'
              : 'text-ink-muted hover:bg-accent-primary-soft hover:text-ink-primary'
          "
          :aria-current="isActive(item) ? 'page' : undefined"
        >
          <span class="text-[0.72rem] leading-4">{{ item.shortLabel }}</span>
        </RouterLink>
      </div>
    </nav>
  </div>
</template>
