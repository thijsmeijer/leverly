import { createApp } from 'vue'
import './style.css'
import App from './App.vue'
import { queryClient, VueQueryPlugin } from './app/plugins/query'
import { router } from './app/router'
import { createPinia } from 'pinia'

createApp(App).use(createPinia()).use(router).use(VueQueryPlugin, { queryClient }).mount('#app')
