<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Logging;

use App\Logging\SanitizeSensitiveLogContext;
use Illuminate\Log\Logger as LaravelLogger;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

final class SanitizeSensitiveLogContextTest extends TestCase
{
    public function test_it_sanitizes_context_before_records_are_written(): void
    {
        $logger = new Logger('test');
        $handler = new TestHandler;
        $logger->pushHandler($handler);

        (new SanitizeSensitiveLogContext)($logger);

        $logger->log(Level::Warning, 'captured do not write this raw', [
            'session_notes' => 'do not write this raw',
            'safe_metric' => 12,
        ]);

        $record = $handler->getRecords()[0];

        $this->assertStringNotContainsString('do not write this raw', $record->message);
        $this->assertSame('[redacted]', $record->context['session_notes']);
        $this->assertSame(12, $record->context['safe_metric']);
    }

    public function test_it_accepts_laravels_logger_wrapper(): void
    {
        $monolog = new Logger('test');
        $handler = new TestHandler;
        $monolog->pushHandler($handler);

        (new SanitizeSensitiveLogContext)(new LaravelLogger($monolog));

        $monolog->warning('prompt leaked raw', [
            'prompt' => 'prompt leaked raw',
        ]);

        $record = $handler->getRecords()[0];

        $this->assertStringNotContainsString('prompt leaked raw', $record->message);
        $this->assertSame('[redacted]', $record->context['prompt']);
    }
}
