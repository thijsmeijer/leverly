# Leverly Web

Vue 3, TypeScript, Vite, and Tailwind CSS power the Leverly web app.

Useful commands:

```sh
corepack pnpm --dir apps/web dev
corepack pnpm --dir apps/web type-check
corepack pnpm --dir apps/web build
```

## Foundation Libraries

| Area                  | Library                   | Purpose                                                                     |
| --------------------- | ------------------------- | --------------------------------------------------------------------------- |
| Routing               | Vue Router                | Owns SPA routes and page-level navigation.                                  |
| Local state           | Pinia                     | Owns durable UI and client workflow state.                                  |
| Server state          | TanStack Vue Query        | Owns API query caching, retries, and invalidation once API calls are added. |
| Validation            | VeeValidate and Zod       | Own typed form schemas and validation adapters.                             |
| Charts                | Shared SVG components     | Own chart rendering; every chart needs a text summary or table alternative. |
| Offline storage       | Dexie                     | Owns typed IndexedDB access for offline drafts.                             |
| Unit tests            | Vitest and Vue Test Utils | Own Vue unit and component tests.                                           |
| E2E and accessibility | Playwright and axe-core   | Own browser workflow checks and accessibility scans.                        |
| Lint and format       | ESLint and Prettier       | Own frontend static checks and formatting.                                  |

PWA plugin wiring is deferred until a Vite 8-compatible tool is selected. The app has a small capability module ready for the later PWA slice.
