<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Correlation;

use App\Support\Correlation\CorrelationStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Tests\TestCase;

final class CorrelationStoreTest extends TestCase
{
    public function test_it_initializes_server_owned_request_correlation_context(): void
    {
        $request = Request::create('/api/v1/health', 'GET');
        $request->headers->set(CorrelationStore::HEADER_CORRELATION_ID, 'client-owned-id');

        $store = new CorrelationStore;
        $store->initializeRequest($request);

        $correlationId = $store->correlationIdForRequest($request);

        $this->assertNotSame('client-owned-id', $correlationId);
        $this->assertMatchesRegularExpression('/^[0-9A-HJKMNP-TV-Z]{26}$/', $correlationId);
        $this->assertSame($correlationId, Context::get('correlation_id'));
        $this->assertSame('client-owned-id', Context::getHidden('client_correlation_id'));
        $this->assertSame([
            'correlation_id' => $correlationId,
        ], $store->responseMetaForRequest($request));
    }
}
