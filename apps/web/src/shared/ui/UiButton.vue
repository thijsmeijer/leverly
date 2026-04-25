<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    size?: 'sm' | 'md' | 'lg'
    type?: 'button' | 'submit' | 'reset'
    variant?: 'primary' | 'secondary' | 'ghost' | 'inverse'
  }>(),
  {
    size: 'md',
    type: 'button',
    variant: 'primary',
  },
)

const buttonClass = computed(() => [
  'inline-flex min-h-11 items-center justify-center rounded-control font-semibold tracking-normal outline-none transition duration-200 focus-visible:ring-2 focus-visible:ring-accent-primary focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-55',
  props.size === 'sm' ? 'px-3 py-2 text-sm' : props.size === 'lg' ? 'px-5 py-3 text-base' : 'px-4 py-2.5 text-sm',
  {
    primary:
      'bg-accent-primary text-white shadow-control hover:bg-accent-primary-strong hover:-translate-y-0.5 focus-visible:ring-offset-surface-primary',
    secondary:
      'border border-line-subtle bg-surface-elevated text-ink-primary shadow-card-soft hover:border-line-strong hover:bg-surface-primary hover:-translate-y-0.5 focus-visible:ring-offset-surface-primary',
    ghost:
      'text-ink-secondary hover:bg-accent-primary-soft hover:text-ink-primary focus-visible:ring-offset-surface-primary',
    inverse:
      'border border-white/15 bg-white/10 text-ink-inverse hover:border-white/30 hover:bg-white/15 focus-visible:ring-offset-surface-inverse',
  }[props.variant],
])
</script>

<template>
  <button :type="type" :class="buttonClass">
    <slot />
  </button>
</template>
