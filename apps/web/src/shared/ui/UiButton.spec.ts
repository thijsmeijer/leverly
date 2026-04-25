import { mount } from '@vue/test-utils'
import { describe, expect, it } from 'vitest'

import UiButton from './UiButton.vue'

describe('UiButton', () => {
  it('uses a real button with a safe default type', () => {
    const wrapper = mount(UiButton, {
      slots: {
        default: 'Start workout',
      },
    })

    expect(wrapper.get('button').attributes('type')).toBe('button')
    expect(wrapper.text()).toBe('Start workout')
  })
})
