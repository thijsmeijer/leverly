# Leverly

Leverly is a calisthenics-first training app for fast workout logging, structured progression tracking, and deterministic coaching recommendations.

Tagline: intelligent progression for bodyweight strength.

## Stack

- API: Laravel 13 JSON API in `apps/api`
- Web: Vue 3, TypeScript, Vite, and Tailwind CSS v4 in `apps/web`
- Database: PostgreSQL with pgvector available for later semantic features
- Cache and queues: Redis where needed
- Local mail: Mailpit
- API contract: OpenAPI under `docs/api/openapi.yaml`

## Repository Layout

```txt
apps/
  api/
  web/
packages/
  shared-types/
  design-tokens/
docs/
  api/openapi.yaml
  assumptions.md
  product-roadmap.md
  progression-engine.md
infra/
  docker-compose.yml
  nginx/
  scripts/
```

## Local Commands

The root command surface is intentionally small:

- `make setup`: install or prepare local dependencies
- `make up`: start PostgreSQL, Redis, and Mailpit
- `make app-up`: start infrastructure plus optional API and web containers
- `make down`: stop local containers
- `make ps`: show local container status
- `make doctor`: check local setup and print concrete fixes
- `make api`: run the API development process once the app exists
- `make web`: run the web development process once the app exists
- `make test`: run available tests
- `make lint`: run available linters and format checks
- `make types`: run available type and contract checks

Local infrastructure, service ports, credentials, and host aliases are documented in `docs/development/local-infrastructure.md`.

Development tooling, lint boundaries, and optional git hooks are documented in `docs/development/tooling.md`.
