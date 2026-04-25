<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Observability;

use App\Support\Correlation\CorrelationStore;
use App\Support\Observability\SentryEventSanitizer;
use Illuminate\Http\Request;
use Sentry\Event;
use Tests\TestCase;

final class SentryEventSanitizerTest extends TestCase
{
    public function test_it_adds_correlation_and_redacts_sensitive_event_payloads(): void
    {
        $request = Request::create('/api/v1/testing', 'POST');
        app(CorrelationStore::class)->initializeRequest($request);
        $correlationId = app(CorrelationStore::class)->correlationIdForRequest($request);

        $event = Event::createEvent();
        $event->setExtra([
            'session_notes' => 'felt unstable during the set',
            'safe_count' => 3,
        ]);
        $event->setRequest([
            'data' => [
                'injury_notes' => 'sharp elbow pain',
                'movement' => 'pull-up',
            ],
        ]);
        $event->setContext('ai', [
            'prompt' => 'summarize my shoulder issue',
        ]);

        $sanitized = app(SentryEventSanitizer::class)->sanitize($event);

        $this->assertSame($correlationId, $sanitized->getTags()['correlation_id']);
        $this->assertSame($correlationId, $sanitized->getContexts()['correlation']['correlation_id']);
        $this->assertSame('[redacted]', $sanitized->getExtra()['session_notes']);
        $this->assertSame(3, $sanitized->getExtra()['safe_count']);
        $this->assertSame('[redacted]', $sanitized->getRequest()['data']['injury_notes']);
        $this->assertSame('pull-up', $sanitized->getRequest()['data']['movement']);
        $this->assertSame('[redacted]', $sanitized->getContexts()['ai']['prompt']);
    }
}
