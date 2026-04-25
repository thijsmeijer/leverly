<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    as?: 'article' | 'aside' | 'div' | 'section'
    padding?: 'none' | 'sm' | 'md' | 'lg'
    tone?: 'elevated' | 'muted' | 'inverse' | 'soft'
  }>(),
  {
    as: 'div',
    padding: 'md',
    tone: 'elevated',
  },
)

const cardClass = computed(() => [
  'rounded-card border',
  props.padding === 'none' ? '' : props.padding === 'sm' ? 'p-4' : props.padding === 'lg' ? 'p-6 sm:p-7' : 'p-5',
  {
    elevated: 'border-line-subtle bg-surface-elevated text-ink-primary shadow-card',
    muted: 'border-line-subtle bg-surface-muted text-ink-primary shadow-card-soft',
    inverse: 'border-white/10 bg-surface-inverse text-ink-inverse shadow-shell',
    soft: 'border-line-subtle bg-surface-primary/80 text-ink-primary shadow-card-soft backdrop-blur',
  }[props.tone],
])
</script>

<template>
  <component :is="as" :class="cardClass">
    <slot />
  </component>
</template>
