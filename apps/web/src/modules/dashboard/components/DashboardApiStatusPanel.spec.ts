import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import DashboardApiStatusPanel from './DashboardApiStatusPanel.vue'

describe('DashboardApiStatusPanel', () => {
  it('renders mapped API status and exposes a refresh command', async () => {
    const wrapper = mount(DashboardApiStatusPanel, {
      props: {
        apiStatus: {
          checkedAt: '2026-04-25T10:30:00.000000Z',
          detail: 'Connected to v1. Live training data can sync when flows are added.',
          label: 'Training API online',
          state: 'online',
        },
      },
    })

    expect(wrapper.text()).toContain('Training API online')
    expect(wrapper.text()).toContain('Connected to v1')

    await wrapper.get('[data-test="refresh-api-status"]').trigger('click')

    expect(wrapper.emitted('refresh-status')).toHaveLength(1)
  })
})
