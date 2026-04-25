import { createRouter, createWebHistory, type RouterHistory, type RouteRecordRaw } from 'vue-router'
import { dashboardRoutes } from '@/modules/dashboard'
import { settingsRoutes } from '@/modules/settings'
import AppShell from '@/app/shell/AppShell.vue'
import LoginPage from '../pages/auth/LoginPage.vue'
import RegisterPage from '../pages/auth/RegisterPage.vue'
import RoutePlaceholderPage from '../pages/placeholders/RoutePlaceholderPage.vue'
import StandalonePlaceholderPage from '../pages/placeholders/StandalonePlaceholderPage.vue'
import { routePlaceholders } from '../pages/placeholders/routePlaceholders'
import { standalonePlaceholders } from '../pages/placeholders/standalonePlaceholders'

const appPlaceholderRoutes: RouteRecordRaw[] = [
  {
    path: 'today',
    name: 'today',
    component: RoutePlaceholderPage,
    meta: {
      requiresAuth: true,
      section: 'Train',
      title: "Today's training",
      placeholder: routePlaceholders.today,
    },
  },
  {
    path: 'workouts',
    name: 'workouts',
    component: RoutePlaceholderPage,
    meta: {
      requiresAuth: true,
      section: 'Train',
      title: 'Workouts',
      placeholder: routePlaceholders.workouts,
    },
  },
  {
    path: 'workouts/:id',
    name: 'workout-detail',
    component: RoutePlaceholderPage,
    meta: {
      requiresAuth: true,
      section: 'Train',
      title: 'Workout detail',
      placeholder: routePlaceholders.workoutDetail,
    },
  },
  {
    path: 'programs',
    name: 'programs',
    component: RoutePlaceholderPage,
    meta: {
      requiresAuth: true,
      section: 'Train',
      title: 'Programs',
      placeholder: routePlaceholders.programs,
    },
  },
  {
    path: 'programs/:id',
    name: 'program-detail',
    component: RoutePlaceholderPage,
    meta: {
      requiresAuth: true,
      section: 'Train',
      title: 'Program detail',
      placeholder: routePlaceholders.programDetail,
    },
  },
  {
    path: 'exercises',
    name: 'exercises',
    component: RoutePlaceholderPage,
    meta: {
      requiresAuth: true,
      section: 'Library',
      title: 'Exercises',
      placeholder: routePlaceholders.exercises,
    },
  },
  {
    path: 'exercises/:slug',
    name: 'exercise-detail',
    component: RoutePlaceholderPage,
    meta: {
      requiresAuth: true,
      section: 'Library',
      title: 'Exercise detail',
      placeholder: routePlaceholders.exerciseDetail,
    },
  },
  {
    path: 'progressions',
    name: 'progressions',
    component: RoutePlaceholderPage,
    meta: {
      requiresAuth: true,
      section: 'Library',
      title: 'Progressions',
      placeholder: routePlaceholders.progressions,
    },
  },
  {
    path: 'progressions/:familySlug',
    name: 'progression-detail',
    component: RoutePlaceholderPage,
    meta: {
      requiresAuth: true,
      section: 'Library',
      title: 'Progression family',
      placeholder: routePlaceholders.progressionDetail,
    },
  },
  {
    path: 'insights',
    name: 'insights',
    component: RoutePlaceholderPage,
    meta: {
      requiresAuth: true,
      section: 'Review',
      title: 'Insights',
      placeholder: routePlaceholders.insights,
    },
  },
  {
    path: 'settings/equipment',
    name: 'settings-equipment',
    component: RoutePlaceholderPage,
    meta: {
      requiresAuth: true,
      section: 'Settings',
      title: 'Equipment settings',
      placeholder: routePlaceholders.settingsEquipment,
    },
  },
  {
    path: 'settings/export',
    name: 'settings-export',
    component: RoutePlaceholderPage,
    meta: {
      requiresAuth: true,
      section: 'Settings',
      title: 'Data export',
      placeholder: routePlaceholders.settingsExport,
    },
  },
]

export const routes: RouteRecordRaw[] = [
  {
    path: '/',
    redirect: { name: 'dashboard' },
  },
  {
    path: '/app',
    component: AppShell,
    redirect: { name: 'dashboard' },
    meta: {
      requiresAuth: true,
    },
    children: [...dashboardRoutes, ...settingsRoutes, ...appPlaceholderRoutes],
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
    path: '/register',
    name: 'register',
    component: RegisterPage,
    meta: {
      title: 'Create account',
    },
  },
  {
    path: '/onboarding',
    name: 'onboarding',
    component: StandalonePlaceholderPage,
    meta: {
      title: 'Onboarding',
      placeholder: standalonePlaceholders.onboarding,
    },
  },
  {
    path: '/:pathMatch(.*)*',
    redirect: { name: 'dashboard' },
  },
]

export function createLeverlyRouter(history: RouterHistory = createWebHistory()) {
  return createRouter({
    history,
    routes,
  })
}

export const router = createLeverlyRouter()
