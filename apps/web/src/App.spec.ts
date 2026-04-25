import { describe, expect, it, vi } from 'vitest'
import App from './App.vue'
import { mountWithApp } from './tests/harness'

vi.mock('vue-chartjs', () => ({
  Line: {
    template: '<div data-test="readiness-chart"></div>',
  },
}))

describe('App', () => {
  it('renders the routed dashboard experience', async () => {
    const { wrapper } = await mountWithApp(App, {
      route: '/app/dashboard',
    })

    expect(wrapper.text()).toContain('Leverly')
    expect(wrapper.text()).toContain("Today's work is ready to log.")
    expect(wrapper.find('[data-test="readiness-chart"]').exists()).toBe(true)
  })
})
