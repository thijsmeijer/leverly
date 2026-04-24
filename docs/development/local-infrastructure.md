# Local Infrastructure

Leverly uses Docker Compose for local database, cache, and mail services. Host-based API and web development still works; Dockerized API and web containers are available as an optional profile.

## Commands

Start the backend services:

```sh
make up
```

Start backend services plus optional API and web containers:

```sh
make app-up
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
| PostgreSQL + pgvector | `leverly_postgres` | `127.0.0.1:5432` | database `leverly`, user `leverly`, password `leverly` |
| Redis | `leverly_redis` | `127.0.0.1:6379` | no password |
| Mailpit SMTP | `leverly_mailpit` | `127.0.0.1:1025` | no auth |
| Mailpit UI | `leverly_mailpit` | `http://127.0.0.1:8025` | no auth |
| API container | `leverly_api` | `http://127.0.0.1:8000` | optional `app` profile |
| Web container | `leverly_web` | `http://127.0.0.1:5173` | optional `app` profile |

Compose volume names are `leverly_postgres_data`, `leverly_redis_data`, `leverly_composer_cache`, and `leverly_pnpm_store`.

## Host Aliases

Use this `/etc/hosts` line when you want local host aliases on the custom endpoint:

```txt
10.20.0.1 leverly.local api.leverly.local web.leverly.local mail.leverly.local
```

The custom endpoint only works when `10.20.0.1` exists on your host loopback interface and services bind to it:

```sh
LEVERLY_BIND_IP=10.20.0.1 make up
```

On Linux, a temporary loopback address can be added with:

```sh
sudo ip addr add 10.20.0.1/32 dev lo
```

If that address is not configured, use the default `127.0.0.1` binding and this fallback hosts line:

```txt
127.0.0.1 leverly.local api.leverly.local web.leverly.local mail.leverly.local
```

Without a reverse proxy, include ports in URLs:

- Web: `http://web.leverly.local:5173`
- API: `http://api.leverly.local:8000`
- Mailpit: `http://mail.leverly.local:8025`

## API Environment

The default `apps/api/.env.example` stays SQLite/log-mail based so the API can boot before Docker is running.

For Compose-backed development, copy the Docker example:

```sh
cp apps/api/.env.docker.example apps/api/.env
```

Then generate the Laravel app key from `apps/api`:

```sh
php artisan key:generate
```

The Docker example points Laravel at the Compose service names:

- `DB_HOST=leverly_postgres`
- `REDIS_HOST=leverly_redis`
- `MAIL_HOST=leverly_mailpit`

## API Contract Source

The web workspace reads the API contract from `docs/api/openapi.yaml` by default:

```sh
corepack pnpm --dir apps/web api-client:check
```

Override the path for local or CI workflows with:

```sh
OPENAPI_SPEC_PATH=/absolute/path/to/openapi.yaml corepack pnpm --dir apps/web api-client:check
```
