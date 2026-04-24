import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import { queryClient, VueQueryPlugin } from './app/plugins/query'
import { router } from './app/router'
import { setupLeverlyApiRuntime } from './app/runtime/apiClient'
import { createPinia } from 'pinia'

setupLeverlyApiRuntime()

createApp(App).use(createPinia()).use(router).use(VueQueryPlugin, { queryClient }).mount('#app')
