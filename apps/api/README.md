# Leverly API

This is the Laravel 13 JSON API for Leverly.

## Local Commands

Run commands from this directory:

- `composer test`: clear configuration and run the API test suite.
- `composer format`: format PHP files with Laravel Pint.
- `composer format:test`: check PHP formatting without changing files.
- `composer openapi`: generate the Scribe OpenAPI output and sync it to the repository contract.
- `composer ide-helper`: refresh IDE helper files and model mixins.
- `php artisan about --only=environment`: verify that the application can boot.

Run `composer ide-helper` after changing models, migrations, service container bindings, facades, or framework dependencies. Model helper metadata is also refreshed after migrations through the IDE helper post-migrate hook when the dev package is installed.

## Environment

Copy `.env.example` to `.env` for standalone local development. The default example uses SQLite and log mail so the API can boot without Docker.

Copy `.env.docker.example` to `.env` when using the Compose services. That example points the API at PostgreSQL, Redis, and Mailpit service names and uses the local host aliases documented in `docs/development/local-infrastructure.md`.

Do not commit `.env`, generated SQLite databases, `vendor`, logs, or local secrets.
