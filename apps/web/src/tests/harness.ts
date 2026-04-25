import { mount, type MountingOptions } from '@vue/test-utils'
import { createPinia, setActivePinia, type Pinia } from 'pinia'
import type { Component } from 'vue'
import { createMemoryHistory, type Router } from 'vue-router'
import { type QueryClient } from '@tanstack/vue-query'

import { createLeverlyQueryClient, VueQueryPlugin } from '@/app/plugins/query'
import { createLeverlyRouter } from '@/app/router'

type MountOptions = MountingOptions<Record<string, unknown>> & {
  route?: string
}

type AppTestHarness = {
  pinia: Pinia
  queryClient: QueryClient
  router: Router
}

export function createAppTestHarness(options: { route?: string } = {}): AppTestHarness {
  const pinia = createPinia()
  const queryClient = createLeverlyQueryClient({
    defaultOptions: {
      queries: {
        retry: false,
      },
    },
  })
  const router = createLeverlyRouter(createMemoryHistory())

  setActivePinia(pinia)

  if (options.route) {
    void router.push(options.route)
  }

  return {
    pinia,
    queryClient,
    router,
  }
}

export async function mountWithApp(component: Component, options: MountOptions = {}) {
  const harness = createAppTestHarness({ route: options.route })
  const extraPlugins = options.global?.plugins ?? []

  await harness.router.isReady()

  const wrapper = mount(component, {
    ...options,
    global: {
      ...options.global,
      plugins: [harness.pinia, harness.router, [VueQueryPlugin, { queryClient: harness.queryClient }], ...extraPlugins],
    },
  })

  return {
    ...harness,
    wrapper,
  }
}
