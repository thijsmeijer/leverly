import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { useSessionStore } from '@/app/stores/sessionStore'
import { mountWithApp } from '@/tests/harness'

import LoginPage from './LoginPage.vue'

describe('LoginPage', () => {
  it('shows accessible validation messages before submitting', async () => {
    const { wrapper } = await mountWithApp(LoginPage, {
      route: '/login',
    })

    await wrapper.find('form').trigger('submit')

    expect(wrapper.text()).toContain('Enter your email address.')
    expect(wrapper.text()).toContain('Enter your password.')
    expect(wrapper.find('#login-email').attributes('aria-invalid')).toBe('true')
  })

  it('submits valid credentials and redirects to the requested app route', async () => {
    const { router, wrapper } = await mountWithApp(LoginPage, {
      route: '/login?redirect=/app/today',
    })
    const session = useSessionStore()
    const login = vi.spyOn(session, 'login').mockResolvedValue({
      email: 'ada@example.com',
      id: '01kaw4k7q6v7m9r6rddm4xyf2p',
      name: 'Ada Athlete',
    })

    await wrapper.find('#login-email').setValue('ada@example.com')
    await wrapper.find('#login-password').setValue('strong-password')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(login).toHaveBeenCalledWith({
      email: 'ada@example.com',
      password: 'strong-password',
      remember: true,
    })
    expect(router.currentRoute.value.fullPath).toBe('/app/today')
  })
})
