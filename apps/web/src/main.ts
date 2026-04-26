import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import { queryClient, VueQueryPlugin } from './app/plugins/query'
import { router } from './app/router'
import { createOnboardingGuard } from './app/router/onboardingGuard'
import { createSessionGuard } from './app/router/sessionGuard'
import { setupLeverlyApiRuntime } from './app/runtime/apiClient'
import { useAuthorizationStore } from './app/stores/authorizationStore'
import { useSessionStore } from './app/stores/sessionStore'
import { useOnboardingStore } from './modules/onboarding'
import { createPinia } from 'pinia'

const pinia = createPinia()
const authorizationStore = useAuthorizationStore(pinia)
const onboardingStore = useOnboardingStore(pinia)
const sessionStore = useSessionStore(pinia)

setupLeverlyApiRuntime({ authorizationStore, sessionStore })
router.beforeEach(createSessionGuard({ useSessionStore: () => sessionStore }))
router.beforeEach(createOnboardingGuard({ useOnboardingStore: () => onboardingStore }))

createApp(App).use(pinia).use(router).use(VueQueryPlugin, { queryClient }).mount('#app')
