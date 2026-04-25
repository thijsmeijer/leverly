<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Correlation\CorrelationStore;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureApiCorrelationContext
{
    public function __construct(
        private readonly CorrelationStore $correlationStore
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->correlationStore->initializeRequest($request);

        $response = $next($request);
        $response->headers->set(
            CorrelationStore::HEADER_CORRELATION_ID,
            $this->correlationStore->correlationIdForRequest($request),
        );

        return $response;
    }
}
