<script setup lang="ts">
import { computed, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { AuthRequestError } from '@/app/services/authService'
import { useSessionStore } from '@/app/stores/sessionStore'
import { authCopy } from '@/shared/brand'
import AuthLayout from './components/AuthLayout.vue'
import AuthSubmitError from './components/AuthSubmitError.vue'
import AuthTextField from './components/AuthTextField.vue'
import { firstServerErrors, validateRegister, type AuthFieldErrors } from './authValidation'

const router = useRouter()
const session = useSessionStore()

const form = reactive({
  email: '',
  name: '',
  password: '',
  password_confirmation: '',
  remember: true,
})
const touched = reactive({
  email: false,
  name: false,
  password: false,
  password_confirmation: false,
})
const submitted = ref(false)
const submitting = ref(false)
const serverErrors = ref<AuthFieldErrors>({})
const submitError = ref('')

const clientErrors = computed(() => validateRegister(form))

function fieldError(field: keyof AuthFieldErrors): string | undefined {
  if (!submitted.value && !touched[field]) {
    return serverErrors.value[field]
  }

  return serverErrors.value[field] ?? clientErrors.value[field]
}

async function submitRegister(): Promise<void> {
  submitted.value = true
  submitError.value = ''
  serverErrors.value = {}

  if (Object.values(clientErrors.value).some(Boolean)) {
    return
  }

  submitting.value = true

  try {
    await session.register(form)
    await router.push({ name: 'dashboard' })
  } catch (error) {
    if (error instanceof AuthRequestError) {
      serverErrors.value = firstServerErrors(error.errors)
      submitError.value = Object.keys(error.errors).length ? 'Check the highlighted fields.' : error.message
    } else {
      submitError.value = authCopy.errors.unavailable
    }
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <AuthLayout
    :eyebrow="authCopy.register.eyebrow"
    :title="authCopy.register.title"
    :switch-text="authCopy.register.switchText"
    :switch-label="authCopy.register.switchLabel"
    switch-route="login"
  >
    <form class="space-y-5" novalidate @submit.prevent="submitRegister">
      <AuthSubmitError v-if="submitError" :message="submitError" />

      <AuthTextField
        id="register-name"
        v-model="form.name"
        autocomplete="name"
        :error="fieldError('name')"
        label="Name"
        @blur="touched.name = true"
      />

      <AuthTextField
        id="register-email"
        v-model="form.email"
        autocomplete="email"
        :error="fieldError('email')"
        label="Email"
        type="email"
        @blur="touched.email = true"
      />

      <AuthTextField
        id="register-password"
        v-model="form.password"
        autocomplete="new-password"
        :error="fieldError('password')"
        label="Password"
        type="password"
        @blur="touched.password = true"
      />

      <AuthTextField
        id="register-password-confirmation"
        v-model="form.password_confirmation"
        autocomplete="new-password"
        :error="fieldError('password_confirmation')"
        label="Confirm password"
        type="password"
        @blur="touched.password_confirmation = true"
      />

      <button
        class="rounded-control bg-accent-primary text-ink-inverse shadow-control focus-visible:ring-accent-primary focus-visible:ring-offset-surface-elevated hover:bg-accent-primary-strong flex min-h-12 w-full items-center justify-center px-5 text-base font-semibold transition outline-none hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-60"
        :disabled="submitting"
        type="submit"
      >
        {{ submitting ? authCopy.register.submitting : authCopy.register.submit }}
      </button>
    </form>
  </AuthLayout>
</template>
