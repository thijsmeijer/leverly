import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import { queryClient, VueQueryPlugin } from './app/plugins/query'
import { router } from './app/router'
import { createSessionGuard } from './app/router/sessionGuard'
import { setupLeverlyApiRuntime } from './app/runtime/apiClient'
import { useSessionStore } from './app/stores/sessionStore'
import { createPinia } from 'pinia'

const pinia = createPinia()
const sessionStore = useSessionStore(pinia)

setupLeverlyApiRuntime({ sessionStore })
router.beforeEach(createSessionGuard({ useSessionStore: () => sessionStore }))

createApp(App).use(pinia).use(router).use(VueQueryPlugin, { queryClient }).mount('#app')
