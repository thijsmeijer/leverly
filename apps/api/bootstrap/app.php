<?php

use App\Http\Middleware\EnsureApiCorrelationContext;
use App\Support\Correlation\CorrelationStore;
use App\Support\Http\ApiErrorResponseFactory;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            EnsureApiCorrelationContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
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
