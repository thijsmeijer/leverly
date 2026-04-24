import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import DashboardSidebar from './DashboardSidebar.vue'

describe('DashboardSidebar', () => {
  it('renders mapped API status and exposes a refresh command', async () => {
    const wrapper = mount(DashboardSidebar, {
      props: {
        apiStatus: {
          checkedAt: '2026-04-25T10:30:00.000000Z',
          detail: 'Version v1',
          label: 'API online',
          state: 'online',
        },
      },
    })

    expect(wrapper.text()).toContain('API online')
    expect(wrapper.text()).toContain('Version v1')

    await wrapper.get('[data-test="refresh-api-status"]').trigger('click')

    expect(wrapper.emitted('refresh-status')).toHaveLength(1)
  })
})
