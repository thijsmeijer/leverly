import { describe, expect, it } from 'vitest'
import App from './App.vue'
import { mountWithApp } from './tests/harness'

describe('App', () => {
  it('renders the routed dashboard experience', async () => {
    const { wrapper } = await mountWithApp(App, {
      route: '/app/dashboard',
    })

    expect(wrapper.text()).toContain('Leverly')
    expect(wrapper.text()).toContain("Today's training is ready to log.")
    expect(wrapper.find('[data-test="readiness-chart"]').exists()).toBe(true)
  })
})
