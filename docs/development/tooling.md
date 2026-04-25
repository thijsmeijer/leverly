# Development Tooling

## Formatting And Linting

- API formatting is checked with Laravel Pint through `make api-format-check`.
- Web linting is checked with ESLint through `make web-lint`.
- Web formatting is checked with Prettier through `make web-format-check`.
- `make lint` runs the API formatter check, web lint, and web format check.

## Focused Checks

Use focused checks while working on a narrow slice:

```sh
make api-format-check
make api-test
make api-test-unit
make api-test-api
make api-test-integration
make api-test-coverage
make contract-check
make modules-check
make web-type-check
make web-lint
make web-format-check
make web-test-foundation
make web-test-unit
make web-test-architecture
make web-test-a11y
make web-test-e2e
```

`make types` runs `make contract-check` and `make web-type-check`.
`make test` runs API tests, web foundation checks, web unit tests, architecture tests, accessibility tests, and E2E tests.

## API Test Suites

The Laravel app has three focused PHPUnit suites:

- `make api-test-unit` runs isolated unit tests from `tests/Unit`.
- `make api-test-api` runs HTTP contract tests from `tests/Feature/API`.
- `make api-test-integration` runs framework and infrastructure integration tests from `tests/Feature/Integration`.

`make api-test` runs all three suites in that order.

Coverage checks run through the API Docker service so the PCOV engine is available, and they write Clover XML reports into `apps/api/storage/logs`:

```sh
make api-test-unit-coverage
make api-test-api-coverage
make api-test-integration-coverage
make api-test-coverage
```

The local API Docker image includes PCOV. If the container was built before PCOV support was added, run `make up` once to rebuild it.

## Full Verification

Run the full CI-ready gate before finishing a broad slice:

```sh
make verify
```

`make verify` runs these layers in order: API format, API tests, module scaffold tests, module structure checks, contract checks, web type-check, web lint, web format, web test foundation checks, web unit tests, web architecture tests, web accessibility tests, and web E2E tests. Accessibility and E2E targets skip cleanly only when the corresponding suite does not exist yet.

## Frontend Test Foundation

Frontend tests have a small support layer under `apps/web/src/tests`:

- `harness.ts` creates a fresh Pinia instance, memory router, query client, and Vue Test Utils mount wrapper for component tests.
- `http.ts` provides response and fetch helpers for behavior tests that exercise API-facing code.
- `setupVitest.ts` keeps browser APIs stable in jsdom.

Run the foundation check directly with:

```sh
make web-test-foundation
```

The foundation check verifies the expected test scripts, support files, Vitest setup, Playwright desktop/mobile projects, accessibility scenario infrastructure, and keeps snapshot-first UI tests out of the behavior suite.

It also runs the design-system foundation check, which verifies semantic tokens, shared UI primitives, light/dark hooks, focus-visible styling, and touch target rules.

## Accessibility Checks

Accessibility checks are scenario-based under `apps/web/e2e/accessibility`.

- `scenarios.ts` lists built screens that should run through the accessibility suite.
- `scenarioRunner.ts` handles navigation and optional scenario setup before scanning.
- `assertions.ts` provides reusable axe, focus-visible, and keyboard reachability assertions.

Run the browser accessibility suite with:

```sh
make web-test-a11y
```

When a new workflow screen is implemented, add one scenario for its route or interaction state, then extend the scenario setup with any modal or form steps needed before scanning.

## Frontend Architecture Rules

The web ESLint config includes local architecture rules that enforce these boundaries:

- Feature and module code may not import generated OpenAPI client internals directly.
- `shared` code may not import `app`, `features`, or `modules` code.
- Feature and module code may not import `app` internals.
- Cross-feature and cross-module imports must use the target feature/module public index instead of deep imports.

Run the rule tests directly with:

```sh
corepack pnpm --dir apps/web test:architecture
```

## API Contract Generation

Use the root command when backend routes or response metadata change:

```sh
make api-client
```

The command runs Scribe in the Laravel app, syncs the generated OpenAPI YAML to `docs/api/openapi.yaml`, then regenerates and checks the web TypeScript contract at `apps/web/src/shared/api/leverlyApi/openapi/generated.ts`.

To regenerate only the API spec, run:

```sh
make api-openapi
```

## Git Hooks

Fast pre-commit checks are available through Lefthook.

Install hooks locally with:

```sh
corepack pnpm hooks:install
```

Run the pre-commit hook manually with:

```sh
corepack pnpm hooks:run
```

The hook runs formatting and lint checks for staged API and web files. When API code, OpenAPI tooling, or the contract file changes, it also runs `make api-client` and re-stages the generated OpenAPI spec and web TypeScript types. Review any generated changes before finishing the commit.

It does not run the full test suite. In CI or other non-interactive environments, skip hook installation by not running `hooks:install`. For urgent local commits, Git can bypass hooks with `git commit --no-verify`.
