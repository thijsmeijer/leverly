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

The hook runs fast formatting and lint checks only. It does not run the full test suite. In CI or other non-interactive environments, skip hook installation by not running `hooks:install`. For urgent local commits, Git can bypass hooks with `git commit --no-verify`.
