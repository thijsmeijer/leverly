import { mount } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import App from './App.vue'
import { queryClient, VueQueryPlugin } from './app/plugins/query'
import { router } from './app/router'

vi.mock('vue-chartjs', () => ({
  Line: {
    template: '<div data-test="readiness-chart"></div>',
  },
}))

describe('App', () => {
  it('renders the routed dashboard experience', async () => {
    router.push('/app/dashboard')
    await router.isReady()

    const wrapper = mount(App, {
      global: {
        plugins: [router, [VueQueryPlugin, { queryClient }]],
      },
    })

    expect(wrapper.text()).toContain('Leverly')
    expect(wrapper.text()).toContain("Today's work is ready to log.")
    expect(wrapper.find('[data-test="readiness-chart"]').exists()).toBe(true)
  })
})
