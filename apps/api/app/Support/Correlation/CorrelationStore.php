<?php

declare(strict_types=1);

namespace App\Support\Correlation;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

final class CorrelationStore
{
    public const string HEADER_CORRELATION_ID = 'X-Correlation-ID';

    public const string ATTRIBUTE_CORRELATION_ID = 'correlation_id';

    private const string CONTEXT_KEY_CORRELATION_ID = 'correlation_id';

    private const string CONTEXT_KEY_TIMESTAMP = 'request_timestamp';

    private const string CONTEXT_KEY_HTTP_METHOD = 'http_method';

    private const string CONTEXT_KEY_HTTP_PATH = 'http_path';

    private const string HIDDEN_CONTEXT_KEY_CLIENT_CORRELATION_ID = 'client_correlation_id';

    /**
     * @var list<string>
     */
    private const array REQUEST_CONTEXT_KEYS = [
        self::CONTEXT_KEY_CORRELATION_ID,
        self::CONTEXT_KEY_TIMESTAMP,
        self::CONTEXT_KEY_HTTP_METHOD,
        self::CONTEXT_KEY_HTTP_PATH,
    ];

    /**
     * @var list<string>
     */
    private const array REQUEST_HIDDEN_CONTEXT_KEYS = [
        self::HIDDEN_CONTEXT_KEY_CLIENT_CORRELATION_ID,
    ];

    public function initializeRequest(Request $request): void
    {
        Context::forget(self::REQUEST_CONTEXT_KEYS);
        Context::forgetHidden(self::REQUEST_HIDDEN_CONTEXT_KEYS);

        $correlationId = $this->generateCorrelationId();

        $request->attributes->set(self::ATTRIBUTE_CORRELATION_ID, $correlationId);

        Context::add([
            self::CONTEXT_KEY_CORRELATION_ID => $correlationId,
            self::CONTEXT_KEY_TIMESTAMP => $this->generateTimestamp(),
            self::CONTEXT_KEY_HTTP_METHOD => $request->method(),
            self::CONTEXT_KEY_HTTP_PATH => $request->path(),
        ]);

        $incomingCorrelationId = $request->header(self::HEADER_CORRELATION_ID);

        if (is_string($incomingCorrelationId) && $incomingCorrelationId !== '') {
            Context::addHidden(self::HIDDEN_CONTEXT_KEY_CLIENT_CORRELATION_ID, $incomingCorrelationId);
        }
    }

    /**
     * @return array{correlation_id: string}
     */
    public function responseMetaForRequest(Request $request): array
    {
        return [
            'correlation_id' => $this->correlationIdForRequest($request),
        ];
    }

    public function correlationIdForRequest(Request $request): string
    {
        $correlationId = $request->attributes->get(self::ATTRIBUTE_CORRELATION_ID);

        if (is_string($correlationId) && $correlationId !== '') {
            return $correlationId;
        }

        $correlationId = $this->currentCorrelationId() ?? $this->generateCorrelationId();
        $request->attributes->set(self::ATTRIBUTE_CORRELATION_ID, $correlationId);
        Context::add(self::CONTEXT_KEY_CORRELATION_ID, $correlationId);

        return $correlationId;
    }

    public function currentCorrelationId(): ?string
    {
        $correlationId = Context::get(self::CONTEXT_KEY_CORRELATION_ID);

        return is_string($correlationId) && $correlationId !== ''
            ? $correlationId
            : null;
    }

    private function generateCorrelationId(): string
    {
        return (string) Str::ulid();
    }

    private function generateTimestamp(): string
    {
        return CarbonImmutable::now('UTC')->format('Y-m-d\TH:i:s.v\Z');
    }
}
