<?php

declare(strict_types=1);

namespace Tests\Feature\API\V1\Errors;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

final class SensitiveLoggingPolicyTest extends TestCase
{
    public function test_api_errors_include_correlation_metadata_without_echoing_sensitive_payloads(): void
    {
        Route::middleware('api')->post('/api/v1/testing/sensitive-error', static function (Request $request): never {
            throw new RuntimeException('Synthetic failure.');
        });

        $response = $this->postJson('/api/v1/testing/sensitive-error', [
            'injury_notes' => 'sharp elbow pain after the third set',
            'session_notes' => 'felt unstable in the bottom position',
            'ai_conversation' => [
                'prompt' => 'summarize my shoulder issue',
            ],
        ]);

        $response->assertStatus(500);
        $response->assertHeader('X-Correlation-ID');
        $response->assertJsonStructure([
            'message',
            'meta' => [
                'correlation_id',
            ],
        ]);

        $correlationId = $response->headers->get('X-Correlation-ID');

        $this->assertSame($correlationId, $response->json('meta.correlation_id'));

        $encodedPayload = $response->getContent();
        $this->assertIsString($encodedPayload);
        $this->assertStringNotContainsString('sharp elbow pain', $encodedPayload);
        $this->assertStringNotContainsString('felt unstable', $encodedPayload);
        $this->assertStringNotContainsString('summarize my shoulder', $encodedPayload);
    }
}
