<?php

declare(strict_types=1);

namespace App\Logging;

use App\Support\Logging\SensitiveLogSanitizer;
use Monolog\Logger;
use Monolog\LogRecord;

final class SanitizeSensitiveLogContext
{
    public function __invoke(Logger $logger): void
    {
        $sanitizer = new SensitiveLogSanitizer;

        $logger->pushProcessor(
            static fn (LogRecord $record): LogRecord => $record->with(
                message: $sanitizer->redactMessage($record->message, $record->context),
                context: $sanitizer->redact($record->context),
                extra: $sanitizer->redact($record->extra),
            ),
        );
    }
}
