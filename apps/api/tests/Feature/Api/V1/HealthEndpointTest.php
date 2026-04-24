<?php

namespace Tests\Feature\Api\V1;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    public function test_it_returns_safe_health_metadata(): void
    {
        $response = $this->getJson('/api/v1/health');

        $response
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('meta.api_version', 'v1')
            ->assertJsonStructure([
                'status',
                'meta' => [
                    'api_version',
                    'timestamp',
                ],
            ]);

        $payload = $response->json();

        $this->assertSame(['api_version', 'timestamp'], array_keys($payload['meta']));
        $this->assertNotFalse(strtotime($payload['meta']['timestamp']));

        $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR);

        foreach (['APP_KEY', 'DB_PASSWORD', 'MAIL_PASSWORD', 'environment', 'debug', 'secret'] as $sensitiveFragment) {
            $this->assertStringNotContainsString($sensitiveFragment, $encodedPayload);
        }
    }
}
