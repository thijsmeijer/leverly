import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import DashboardPage from '../pages/dashboard/DashboardPage.vue'

export const routes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: { name: 'dashboard' },
  },
  {
    path: '/app',
    redirect: { name: 'dashboard' },
  },
  {
    path: '/app/dashboard',
    name: 'dashboard',
    component: DashboardPage,
    meta: {
      title: 'Dashboard',
    },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: { name: 'dashboard' },
  },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
})
