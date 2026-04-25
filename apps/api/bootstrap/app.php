<?php

use App\Http\Middleware\EnsureApiCorrelationContext;
use App\Support\Correlation\CorrelationStore;
use App\Support\Http\ApiErrorResponseFactory;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();

        $middleware->api(prepend: [
            EnsureApiCorrelationContext::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        if (! config('telescope.enabled', false)) {
            return;
        }

        $schedule->command('telescope:prune', [
            '--hours' => max(1, (int) config('telescope.prune.hours', 24)),
        ])->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);

        $exceptions->context(function (): array {
            $correlationId = app(CorrelationStore::class)->currentCorrelationId();

            return is_string($correlationId) ? ['correlation_id' => $correlationId] : [];
        });

        $exceptions->render(function (Throwable $exception, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            return app(ApiErrorResponseFactory::class)->make($exception, $request);
        });
    })->create();
