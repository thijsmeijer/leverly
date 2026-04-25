<?php

use App\Support\Observability\SentryEventSanitizer;
use Sentry\Event;

return [
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),
    'release' => env('SENTRY_RELEASE'),
    'environment' => env('SENTRY_ENVIRONMENT'),
    'sample_rate' => env('SENTRY_SAMPLE_RATE') === null ? 1.0 : (float) env('SENTRY_SAMPLE_RATE'),
    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE') === null ? null : (float) env('SENTRY_TRACES_SAMPLE_RATE'),
    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE') === null ? null : (float) env('SENTRY_PROFILES_SAMPLE_RATE'),
    'enable_logs' => env('SENTRY_ENABLE_LOGS', false),
    'logs_channel_level' => env('SENTRY_LOG_LEVEL', env('SENTRY_LOGS_LEVEL', env('LOG_LEVEL', 'error'))),
    'send_default_pii' => false,
    'ignore_transactions' => [
        '/up',
        '/api/v1/health',
    ],
    'breadcrumbs' => [
        'logs' => true,
        'cache' => false,
        'livewire' => false,
        'sql_queries' => true,
        'sql_bindings' => false,
        'queue_info' => true,
        'command_info' => true,
        'http_client_requests' => true,
        'notifications' => true,
    ],
    'tracing' => [
        'queue_job_transactions' => true,
        'queue_jobs' => true,
        'sql_queries' => true,
        'sql_bindings' => false,
        'sql_origin' => true,
        'sql_origin_threshold_ms' => 100,
        'views' => false,
        'livewire' => false,
        'http_client_requests' => true,
        'cache' => false,
        'redis_commands' => false,
        'redis_origin' => true,
        'notifications' => true,
        'missing_routes' => false,
        'continue_after_response' => true,
        'default_integrations' => true,
    ],
    'before_send' => static fn (Event $event): Event => app(SentryEventSanitizer::class)->sanitize($event),
    'before_send_transaction' => static fn (Event $event): Event => app(SentryEventSanitizer::class)->sanitize($event),
];
