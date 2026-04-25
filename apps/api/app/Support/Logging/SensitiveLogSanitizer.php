<?php

declare(strict_types=1);

namespace App\Support\Logging;

use BackedEnum;
use Stringable;
use UnitEnum;

final class SensitiveLogSanitizer
{
    public const string REDACTED = '[redacted]';

    /**
     * @var list<string>
     */
    private const array SENSITIVE_KEY_FRAGMENTS = [
        'ai_conversation',
        'conversation',
        'injury',
        'medical',
        'note',
        'pain_note',
        'prompt',
        'response',
        'session_note',
    ];

    public function redact(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->redactArray($value);
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_object($value)) {
            return sprintf('[object:%s]', $value::class);
        }

        if (is_resource($value)) {
            return '[resource]';
        }

        return $value;
    }

    /**
     * @param  array<array-key, mixed>  $context
     */
    public function redactMessage(string $message, array $context): string
    {
        foreach ($this->sensitiveStringValues($context) as $value) {
            $message = str_replace($value, self::REDACTED, $message);
        }

        return $message;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return array<array-key, mixed>
     */
    private function redactArray(array $payload): array
    {
        $redacted = [];

        foreach ($payload as $key => $value) {
            if ($this->isSensitiveKey($key)) {
                $redacted[$key] = self::REDACTED;

                continue;
            }

            $redacted[$key] = $this->redact($value);
        }

        return $redacted;
    }

    private function isSensitiveKey(int|string $key): bool
    {
        if (! is_string($key)) {
            return false;
        }

        $normalizedKey = strtolower(str_replace(['-', ' '], '_', $key));

        foreach (self::SENSITIVE_KEY_FRAGMENTS as $fragment) {
            if (str_contains($normalizedKey, $fragment)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<array-key, mixed>  $payload
     * @return list<string>
     */
    private function sensitiveStringValues(array $payload): array
    {
        $values = [];

        foreach ($payload as $key => $value) {
            if ($this->isSensitiveKey($key)) {
                foreach ($this->stringValues($value) as $stringValue) {
                    if ($stringValue !== '') {
                        $values[] = $stringValue;
                    }
                }

                continue;
            }

            if (is_array($value)) {
                array_push($values, ...$this->sensitiveStringValues($value));
            }
        }

        return array_values(array_unique($values));
    }

    /**
     * @return list<string>
     */
    private function stringValues(mixed $value): array
    {
        if (is_string($value)) {
            return [$value];
        }

        if ($value instanceof Stringable) {
            return [(string) $value];
        }

        if (is_array($value)) {
            $values = [];

            foreach ($value as $nestedValue) {
                array_push($values, ...$this->stringValues($nestedValue));
            }

            return $values;
        }

        return [];
    }
}
