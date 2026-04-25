# Observability

Leverly uses a small observability setup:

- Laravel Telescope for local request, query, job, schedule, log, command, and exception inspection.
- Sentry Laravel SDK for production error reporting when a DSN is configured.
- The API correlation ID from `X-Correlation-ID` and `meta.correlation_id` as the shared support handle.

## Local Telescope

Telescope is enabled by default in local API environments and disabled elsewhere unless `TELESCOPE_ENABLED=true` is set.

After running migrations, open:

```txt
http://api.leverly.local/telescope
```

Telescope stores entries in the application database. Entries are pruned daily when Telescope is enabled. The default retention is 24 hours and can be changed with `TELESCOPE_PRUNE_HOURS`.

Only the foundational watchers are enabled: requests, queries, jobs, schedules, logs, commands, and exceptions. Dump, model, view, cache, event, notification, Redis, mail, and gate watchers stay off until there is a clear debugging need.

## Sensitive Data

Telescope request parameters and headers hide known sensitive fields, including notes, injury notes, pain notes, prompts, responses, tokens, cookies, and authorization headers.

Sentry events pass through the same sensitive-data sanitizer used by application logs. Request data, extra data, and event contexts are redacted before dispatch. Sentry PII collection is disabled.

Do not log or manually report raw workout notes, injury text, AI prompts, AI responses, or session notes. Report stable identifiers and the correlation ID instead.

## Production Sentry

Sentry is inert until `SENTRY_LARAVEL_DSN` or `SENTRY_DSN` is configured. Keep performance sampling explicit:

```txt
SENTRY_LARAVEL_DSN=https://example@sentry.invalid/1
SENTRY_TRACES_SAMPLE_RATE=0.05
SENTRY_PROFILES_SAMPLE_RATE=null
SENTRY_ENABLE_LOGS=false
```

Every Sentry event is tagged with `correlation_id` when a request correlation exists.
