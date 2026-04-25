import type { RoutePlaceholderContent } from '@/app/router/routeMeta'

export const standalonePlaceholders = {
  register: {
    eyebrow: 'Account',
    title: 'Create your Leverly account',
    description:
      'Registration will connect your profile, training history, and future recommendations to one private athlete account.',
    status: 'Ready for auth form',
    primaryAction: { label: 'Sign in', to: { name: 'login' } },
    secondaryAction: { label: 'Preview onboarding', to: { name: 'onboarding' } },
    metrics: [
      {
        label: 'Auth',
        value: 'Private',
        detail: 'Training data should belong to one athlete account.',
        tone: 'success',
      },
      { label: 'Session', value: 'First-party', detail: 'The SPA will use protected session cookies.', tone: 'info' },
      {
        label: 'Trust',
        value: 'Clear',
        detail: 'Validation and loading states belong directly in the form.',
        tone: 'neutral',
      },
    ],
    steps: [
      { label: 'Create account', detail: 'Email, password, and validation states will be handled in the auth flow.' },
      { label: 'Confirm session', detail: 'The SPA should know whether the athlete is signed in.' },
      { label: 'Start onboarding', detail: 'New accounts move into training context setup.' },
    ],
  },
  onboarding: {
    eyebrow: 'Setup',
    title: 'Start with useful training context',
    description:
      'Onboarding will capture goals, available equipment, current level checks, schedule, readiness, and pain signals.',
    status: 'Ready for onboarding flow',
    primaryAction: { label: 'Open dashboard', to: { name: 'dashboard' } },
    secondaryAction: { label: 'Create account', to: { name: 'register' } },
    metrics: [
      {
        label: 'Goals',
        value: 'Specific',
        detail: 'Skill and strength targets shape the first program.',
        tone: 'success',
      },
      { label: 'Equipment', value: 'Practical', detail: 'Suggestions should only use available tools.', tone: 'info' },
      {
        label: 'Readiness',
        value: 'Careful',
        detail: 'Pain and fatigue should keep early recommendations conservative.',
        tone: 'warning',
      },
    ],
    steps: [
      { label: 'Choose goals', detail: 'Select the skills and strength outcomes that matter now.' },
      { label: 'Set constraints', detail: 'Equipment, days, session length, and current level tests guide setup.' },
      { label: 'Begin training', detail: 'The first dashboard should show a sensible starting point.' },
    ],
  },
} satisfies Record<string, RoutePlaceholderContent>
