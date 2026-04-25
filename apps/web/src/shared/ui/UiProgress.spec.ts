import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import UiProgress from './UiProgress.vue'

describe('UiProgress', () => {
  it('exposes an accessible progressbar with clamped values', () => {
    const wrapper = mount(UiProgress, {
      props: {
        label: 'Readiness',
        showValue: true,
        value: 140,
      },
    })

    const progressbar = wrapper.get('[role="progressbar"]')

    expect(progressbar.attributes('aria-label')).toBe('Readiness')
    expect(progressbar.attributes('aria-valuenow')).toBe('100')
    expect(wrapper.text()).toContain('100%')
  })
})
