<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\Support\Correlation\CorrelationStore;
use App\Support\Logging\SensitiveLogSanitizer;
use Sentry\Event;

final class SentryEventSanitizer
{
    public function __construct(
        private readonly CorrelationStore $correlationStore,
        private readonly SensitiveLogSanitizer $sensitiveLogSanitizer,
    ) {}

    public function sanitize(Event $event): Event
    {
        $this->attachCorrelation($event);

        $event->setExtra($this->redactArray($event->getExtra()));
        $event->setRequest($this->redactArray($event->getRequest()));

        foreach ($event->getContexts() as $name => $context) {
            $event->setContext($name, $this->redactArray($context));
        }

        return $event;
    }

    private function attachCorrelation(Event $event): void
    {
        $correlationId = $this->correlationStore->currentCorrelationId();

        if ($correlationId === null) {
            return;
        }

        $event->setTag('correlation_id', $correlationId);
        $event->setContext('correlation', [
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function redactArray(array $payload): array
    {
        $redacted = $this->sensitiveLogSanitizer->redact($payload);

        return is_array($redacted) ? $redacted : [];
    }
}
