# Local Infrastructure

Leverly uses Docker Compose for the local app runtime, database, cache, mail service, and host-based reverse proxy.

## Commands

Start the full local stack:

```sh
make up
```

Start only PostgreSQL, Redis, and Mailpit:

```sh
make services-up
```

Stop containers:

```sh
make down
```

Show container status or rendered Compose config:

```sh
make ps
make compose-config
```

## Services

| Service | Container | Default URL / Port | Credentials |
| --- | --- | --- | --- |
| PostgreSQL + pgvector | `leverly_postgres` | `10.20.0.1:5432` | database `leverly`, user `leverly`, password `leverly` |
| Redis | `leverly_redis` | `10.20.0.1:6379` | no password |
| Mailpit SMTP | `leverly_mailpit` | `10.20.0.1:1025` | no auth |
| Reverse proxy | `leverly_proxy` | `http://web.leverly.local`, `http://api.leverly.local`, `http://mail.leverly.local` | no auth |
| Mailpit UI | `leverly_mailpit` | `http://mail.leverly.local` | no auth |
| API container | `leverly_api` | `http://api.leverly.local` | local app container |
| Web container | `leverly_web` | `http://web.leverly.local` | local app container |
| Telescope UI | `leverly_api` | `http://api.leverly.local/telescope` | local only |

Compose volume names are `leverly_postgres_data`, `leverly_redis_data`, `leverly_composer_cache`, and `leverly_pnpm_store`.

## Host Aliases

Use this `/etc/hosts` line when you want local host aliases on the custom endpoint:

```txt
10.20.0.1 leverly.local api.leverly.local web.leverly.local mail.leverly.local
```

The Compose network uses `10.20.0.1` as its gateway, so `make up` creates the local address through Docker. The reverse proxy listens on port 80, so normal local URLs do not need explicit ports:

- Web: `http://web.leverly.local`
- API: `http://api.leverly.local`
- Mailpit: `http://mail.leverly.local`
- Telescope: `http://api.leverly.local/telescope`

Direct debug ports still remain available:

- Web: `http://web.leverly.local:5173`
- API: `http://api.leverly.local:8000`
- Mailpit: `http://mail.leverly.local:8025`

## Mailpit

Mailpit catches local email sent by the API. The Compose API environment uses:

- `MAIL_MAILER=smtp`
- `MAIL_HOST=leverly_mailpit`
- `MAIL_PORT=1025`

Messages sent by local API features can be inspected at `http://mail.leverly.local` without sending real email.

If the `10.20.0.0/24` subnet conflicts with something on your machine, use the default loopback address and this fallback hosts line:

```txt
127.0.0.1 leverly.local api.leverly.local web.leverly.local mail.leverly.local
```

## API Environment

The default `apps/api/.env.example` stays SQLite/log-mail based so the API can boot before Docker is running.

For Compose-backed development, keep `apps/api/.env` present for local secrets such as `APP_KEY`. The Compose service also passes its runtime database, Redis, mail, Telescope, and Sentry values directly into the API container so HTTP requests use the same settings as Artisan commands.

To rebuild the file from defaults, copy the Docker example:

```sh
cp apps/api/.env.docker.example apps/api/.env
```

Then generate the Laravel app key from `apps/api`:

```sh
php artisan key:generate
```

The Docker example points Laravel inside the container at the Compose service names:

- `DB_HOST=leverly_postgres`
- `REDIS_HOST=leverly_redis`
- `MAIL_HOST=leverly_mailpit`

When running Artisan directly on the host against the Docker services, use the bind IP instead:

- `DB_HOST=10.20.0.1`
- `REDIS_HOST=10.20.0.1`
- `MAIL_HOST=10.20.0.1`

## Web Environment

The Docker web service sets `VITE_API_BASE_URL=http://api.leverly.local` so browser API calls use the reverse-proxied API host.

When running the web app outside Docker, copy the web example first:

```sh
cp apps/web/.env.example apps/web/.env.local
```

The API client appends `/api/v1` automatically, so `VITE_API_BASE_URL` can be either the API origin or the full versioned API base URL.

## API Contract Source

The web workspace reads the API contract from `docs/api/openapi.yaml` by default:

```sh
corepack pnpm --dir apps/web api-client:check
```

Override the path for local or CI workflows with:

```sh
OPENAPI_SPEC_PATH=/absolute/path/to/openapi.yaml corepack pnpm --dir apps/web api-client:check
```
