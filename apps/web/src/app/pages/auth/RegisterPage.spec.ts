import { flushPromises } from '@vue/test-utils'
import { describe, expect, it, vi } from 'vitest'
import { useSessionStore } from '@/app/stores/sessionStore'
import { mountWithApp } from '@/tests/harness'

import RegisterPage from './RegisterPage.vue'

describe('RegisterPage', () => {
  it('shows validation messages for required registration fields', async () => {
    const { wrapper } = await mountWithApp(RegisterPage, {
      route: '/register',
    })

    await wrapper.find('form').trigger('submit')

    expect(wrapper.text()).toContain('Enter your name.')
    expect(wrapper.text()).toContain('Enter your email address.')
    expect(wrapper.text()).toContain('Enter your password.')
  })

  it('creates an account from a valid registration form', async () => {
    const { router, wrapper } = await mountWithApp(RegisterPage, {
      route: '/register',
    })
    const session = useSessionStore()
    const register = vi.spyOn(session, 'register').mockResolvedValue({
      email: 'ada@example.com',
      id: '01kaw4k7q6v7m9r6rddm4xyf2p',
      name: 'Ada Athlete',
    })

    await wrapper.find('#register-name').setValue('Ada Athlete')
    await wrapper.find('#register-email').setValue('ada@example.com')
    await wrapper.find('#register-password').setValue('strong-password')
    await wrapper.find('#register-password-confirmation').setValue('strong-password')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(register).toHaveBeenCalledWith({
      email: 'ada@example.com',
      name: 'Ada Athlete',
      password: 'strong-password',
      password_confirmation: 'strong-password',
      remember: true,
    })
    expect(router.currentRoute.value.name).toBe('dashboard')
  })
})
