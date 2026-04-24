# Development Tooling

## Formatting And Linting

- API formatting is checked with Laravel Pint through `make lint`.
- Web linting is checked with ESLint through `make lint`.
- Web formatting is checked with Prettier through `make lint`.
- `make verify` runs lint, type checks, and tests.

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
