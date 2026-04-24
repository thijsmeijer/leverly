import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import { DashboardPage } from '@/modules/dashboard'
import LoginPage from '../pages/auth/LoginPage.vue'

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
    path: '/login',
    name: 'login',
    component: LoginPage,
    meta: {
      title: 'Sign in',
    },
  },
  {
    path: '/app/dashboard',
    name: 'dashboard',
    component: DashboardPage,
    meta: {
      requiresAuth: true,
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
