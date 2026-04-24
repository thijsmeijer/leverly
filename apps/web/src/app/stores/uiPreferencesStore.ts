import { defineStore } from 'pinia'

export const useUiPreferencesStore = defineStore('uiPreferences', {
  state: () => ({
    reducedMotion: typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches,
  }),
})
