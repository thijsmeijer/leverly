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

  it('renders app shell placeholders with route-backed navigation', async () => {
    const { wrapper } = await mountWithApp(App, {
      route: '/app/workouts',
    })

    expect(wrapper.text()).toContain('Workout history')
    expect(wrapper.text()).toContain('Start')
    expect(wrapper.find('[data-test="route-placeholder"]').exists()).toBe(true)
    expect(wrapper.find('[aria-label="Mobile primary"]').text()).toContain('Logs')
  })

  it('renders standalone setup routes outside the app shell', async () => {
    const { wrapper } = await mountWithApp(App, {
      route: '/onboarding',
    })

    expect(wrapper.text()).toContain('Start with useful training context')
    expect(wrapper.text()).toContain('Create account')
    expect(wrapper.find('[aria-label="Mobile primary"]').exists()).toBe(false)
  })
})
