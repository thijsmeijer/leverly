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

    expect(wrapper.text()).toContain('Find the strongest path for your next calisthenics block')
    expect(wrapper.text()).toContain('Start with the athlete behind the skills.')
    expect(wrapper.find('[aria-label="Mobile primary"]').exists()).toBe(false)
  })

  it('renders real auth routes outside the app shell', async () => {
    const { wrapper: loginWrapper } = await mountWithApp(App, {
      route: '/login',
    })

    expect(loginWrapper.text()).toContain('Pick up where your last set left off.')
    expect(loginWrapper.find('input[type="email"]').exists()).toBe(true)
    expect(loginWrapper.find('[aria-label="Mobile primary"]').exists()).toBe(false)

    const { wrapper: registerWrapper } = await mountWithApp(App, {
      route: '/register',
    })

    expect(registerWrapper.text()).toContain('Start tracking real calisthenics progress.')
    expect(registerWrapper.find('#register-password-confirmation').exists()).toBe(true)
    expect(registerWrapper.find('[aria-label="Mobile primary"]').exists()).toBe(false)
  })
})
