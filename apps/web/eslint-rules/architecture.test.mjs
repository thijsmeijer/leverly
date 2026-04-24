import { RuleTester } from 'eslint'
import architecture from './architecture.js'

RuleTester.afterAll = undefined
RuleTester.describe = undefined
RuleTester.it = undefined

const tester = new RuleTester({
  languageOptions: {
    ecmaVersion: 2024,
    sourceType: 'module',
  },
})

tester.run('no-generated-openapi-internals', architecture.rules['no-generated-openapi-internals'], {
  valid: [
    {
      filename: '/repo/apps/web/src/app/pages/dashboard/DashboardPage.ts',
      code: "import { listWorkouts } from '../../api/workouts'",
    },
    {
      filename: '/repo/apps/web/src/features/workouts/index.ts',
      code: "import { leverlyApiRequest } from '@/shared/api/leverlyApi/runtimeClient'",
    },
  ],
  invalid: [
    {
      filename: '/repo/apps/web/src/features/workouts/index.ts',
      code: "import { client } from '@/api/generated/client'",
      errors: [{ message: 'Use typed API wrapper modules instead of generated OpenAPI client internals.' }],
    },
    {
      filename: '/repo/apps/web/src/modules/workouts/index.ts',
      code: "import { paths } from '@/shared/api/leverlyApi/openapi/generated'",
      errors: [{ message: 'Use typed API wrapper modules instead of generated OpenAPI client internals.' }],
    },
  ],
})

tester.run('no-shared-upstream-imports', architecture.rules['no-shared-upstream-imports'], {
  valid: [
    {
      filename: '/repo/apps/web/src/shared/format/date.ts',
      code: "import { clamp } from './number'",
    },
  ],
  invalid: [
    {
      filename: '/repo/apps/web/src/shared/format/date.ts',
      code: "import { router } from '@/app/router'",
      errors: [{ message: 'Shared code must not import app, feature, or module code.' }],
    },
  ],
})

tester.run('no-feature-app-imports', architecture.rules['no-feature-app-imports'], {
  valid: [
    {
      filename: '/repo/apps/web/src/features/workouts/index.ts',
      code: "import { formatDate } from '@/shared/format/date'",
    },
  ],
  invalid: [
    {
      filename: '/repo/apps/web/src/features/workouts/index.ts',
      code: "import { router } from '@/app/router'",
      errors: [{ message: 'Feature and module code must not import app internals.' }],
    },
  ],
})

tester.run('no-cross-feature-deep-imports', architecture.rules['no-cross-feature-deep-imports'], {
  valid: [
    {
      filename: '/repo/apps/web/src/features/workouts/index.ts',
      code: "import { ExerciseSummary } from '@/features/exercises'",
    },
  ],
  invalid: [
    {
      filename: '/repo/apps/web/src/features/workouts/index.ts',
      code: "import { ExerciseSummary } from '@/features/exercises/components/ExerciseSummary'",
      errors: [{ message: 'Import features code through its public index instead of deep imports.' }],
    },
  ],
})

tester.run('no-app-module-deep-imports', architecture.rules['no-app-module-deep-imports'], {
  valid: [
    {
      filename: '/repo/apps/web/src/app/router/index.ts',
      code: "import { DashboardPage } from '@/modules/dashboard'",
    },
  ],
  invalid: [
    {
      filename: '/repo/apps/web/src/app/router/index.ts',
      code: "import DashboardPage from '@/modules/dashboard/pages/DashboardPage.vue'",
      errors: [{ message: 'Import feature and module code through its public index instead of deep imports.' }],
    },
  ],
})
