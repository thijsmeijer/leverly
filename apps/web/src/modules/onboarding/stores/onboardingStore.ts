import { defineStore } from 'pinia'
import {
  defaultOnboardingForm,
  fetchOnboarding,
  saveOnboarding,
  type OnboardingValidationError,
} from '../services/onboardingService'
import type { OnboardingFieldErrors, OnboardingForm, OnboardingState } from '../types'

export type OnboardingLoadState = 'failed' | 'idle' | 'loaded' | 'loading'
export type OnboardingSaveState = 'failed' | 'idle' | 'saved' | 'saving'

export const useOnboardingStore = defineStore('onboarding', {
  state: () => ({
    fieldErrors: {} as OnboardingFieldErrors,
    form: defaultOnboardingForm() as OnboardingForm,
    isComplete: false,
    lastLoadedAt: null as number | null,
    loadError: '',
    loadState: 'idle' as OnboardingLoadState,
    missingSections: [] as string[],
    onboardingId: null as string | null,
    saveError: '',
    saveState: 'idle' as OnboardingSaveState,
  }),
  actions: {
    applyState(state: OnboardingState): void {
      this.form = state.form
      this.isComplete = state.isComplete
      this.missingSections = state.missingSections
      this.onboardingId = state.onboardingId
      this.lastLoadedAt = Date.now()
    },
    async ensureLoaded(): Promise<void> {
      if (this.loadState === 'loaded' || this.loadState === 'loading') {
        return
      }

      await this.load()
    },
    async load(): Promise<void> {
      this.loadState = 'loading'
      this.loadError = ''

      try {
        this.applyState(await fetchOnboarding())
        this.loadState = 'loaded'
      } catch {
        this.loadError = 'Onboarding could not be loaded.'
        this.loadState = 'failed'
      }
    },
    async saveDraft(): Promise<boolean> {
      return this.persist(false)
    },
    async complete(): Promise<boolean> {
      return this.persist(true)
    },
    reset(): void {
      this.fieldErrors = {}
      this.form = defaultOnboardingForm()
      this.isComplete = false
      this.lastLoadedAt = null
      this.loadError = ''
      this.loadState = 'idle'
      this.missingSections = []
      this.onboardingId = null
      this.saveError = ''
      this.saveState = 'idle'
    },
    async persist(complete: boolean): Promise<boolean> {
      this.saveState = 'saving'
      this.saveError = ''
      this.fieldErrors = {}

      try {
        this.applyState(await saveOnboarding(this.form, { complete }))
        this.saveState = 'saved'
        this.loadState = 'loaded'

        return true
      } catch (error) {
        const validationError = error as Partial<OnboardingValidationError>

        if (validationError.name === 'OnboardingValidationError' && validationError.errors) {
          this.fieldErrors = validationError.errors
          this.saveError = 'Some onboarding answers need attention.'
        } else {
          this.saveError = complete ? 'Onboarding could not be completed.' : 'Onboarding draft could not be saved.'
        }

        this.saveState = 'failed'

        return false
      }
    },
  },
})
