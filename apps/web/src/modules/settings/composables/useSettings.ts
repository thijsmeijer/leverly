import { computed, reactive, ref } from 'vue'

import {
  defaultProfileSettingsForm,
  fetchProfileSettings,
  ProfileSettingsValidationError,
  saveProfileSettings,
  validateProfileSettingsForm,
} from '../services/settingsService'
import type { ProfileFieldErrors, ProfileSettingsForm } from '../types'

export function useSettings() {
  const form = reactive<ProfileSettingsForm>(defaultProfileSettingsForm())
  const fieldErrors = ref<ProfileFieldErrors>({})
  const profileId = ref<string | null>(null)
  const isLoading = ref(false)
  const isSaving = ref(false)
  const loadError = ref('')
  const saveError = ref('')
  const saveSuccess = ref(false)

  return {
    fieldErrors,
    form,
    hasErrors: computed(() => Object.keys(fieldErrors.value).length > 0),
    isLoading,
    isSaving,
    loadError,
    loadProfile,
    profileId,
    saveError,
    saveProfile,
    saveSuccess,
  }

  async function loadProfile(): Promise<void> {
    isLoading.value = true
    loadError.value = ''

    try {
      const state = await fetchProfileSettings()

      Object.assign(form, state.form)
      profileId.value = state.profileId
    } catch {
      loadError.value = 'Profile settings could not be loaded. Try refreshing in a moment.'
    } finally {
      isLoading.value = false
    }
  }

  async function saveProfile(): Promise<boolean> {
    saveSuccess.value = false
    saveError.value = ''
    fieldErrors.value = validateProfileSettingsForm(form)

    if (Object.keys(fieldErrors.value).length > 0) {
      saveError.value = 'Check the highlighted fields before saving.'

      return false
    }

    isSaving.value = true

    try {
      const state = await saveProfileSettings(form)

      Object.assign(form, state.form)
      profileId.value = state.profileId
      fieldErrors.value = {}
      saveSuccess.value = true

      return true
    } catch (error) {
      if (error instanceof ProfileSettingsValidationError) {
        fieldErrors.value = error.errors
        saveError.value = 'Check the highlighted fields before saving.'
      } else {
        saveError.value = 'Profile settings could not be saved. Your current form is still here.'
      }

      return false
    } finally {
      isSaving.value = false
    }
  }
}
