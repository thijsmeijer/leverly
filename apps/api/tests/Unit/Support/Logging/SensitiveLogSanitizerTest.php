<?php

declare(strict_types=1);

namespace Tests\Unit\Support\Logging;

use App\Support\Logging\SensitiveLogSanitizer;
use PHPUnit\Framework\TestCase;

final class SensitiveLogSanitizerTest extends TestCase
{
    public function test_it_redacts_sensitive_training_and_ai_fields_recursively(): void
    {
        $sanitizer = new SensitiveLogSanitizer;

        $payload = $sanitizer->redact([
            'exercise_id' => 'pull-up',
            'injury_notes' => 'sharp elbow pain after the third set',
            'session_notes' => 'felt unstable in the bottom position',
            'ai_conversation' => [
                'prompt' => 'summarize my shoulder issue',
                'response' => 'possible overload pattern',
            ],
            'blocks' => [
                [
                    'pain_note' => 'left wrist flare-up',
                    'reps' => 6,
                ],
            ],
        ]);

        $this->assertSame('pull-up', $payload['exercise_id']);
        $this->assertSame(6, $payload['blocks'][0]['reps']);
        $this->assertSame('[redacted]', $payload['injury_notes']);
        $this->assertSame('[redacted]', $payload['session_notes']);
        $this->assertSame('[redacted]', $payload['ai_conversation']);
        $this->assertSame('[redacted]', $payload['blocks'][0]['pain_note']);
    }
}
