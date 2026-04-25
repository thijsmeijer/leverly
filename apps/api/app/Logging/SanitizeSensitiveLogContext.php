<?php

declare(strict_types=1);

namespace App\Logging;

use App\Support\Logging\SensitiveLogSanitizer;
use Illuminate\Log\Logger as LaravelLogger;
use Monolog\Logger;
use Monolog\LogRecord;

final class SanitizeSensitiveLogContext
{
    public function __invoke(LaravelLogger|Logger $logger): void
    {
        $sanitizer = new SensitiveLogSanitizer;
        $monolog = $logger instanceof LaravelLogger ? $logger->getLogger() : $logger;

        $monolog->pushProcessor(
            static fn (LogRecord $record): LogRecord => $record->with(
                message: $sanitizer->redactMessage($record->message, $record->context),
                context: $sanitizer->redact($record->context),
                extra: $sanitizer->redact($record->extra),
            ),
        );
    }
}
