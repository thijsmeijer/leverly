<?php

namespace Tests\Feature\Integration\Console;

use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

class ScribeOpenApiGenerationTest extends TestCase
{
    public function test_scribe_openapi_generation_is_available_and_configured(): void
    {
        $this->assertArrayHasKey('scribe:generate', Artisan::all());
        $this->assertArrayHasKey('leverly:openapi:sync-scribe', Artisan::all());
        $this->assertTrue(config('scribe.openapi.enabled'));
        $this->assertFalse(config('scribe.postman.enabled'));
        $this->assertFalse(config('scribe.laravel.add_routes'));
    }

    public function test_scribe_sync_command_cleans_fixture_output(): void
    {
        $target = tempnam(sys_get_temp_dir(), 'leverly-openapi-');

        $this->assertIsString($target);

        $exitCode = Artisan::call('leverly:openapi:sync-scribe', [
            '--source' => base_path('tests/Fixtures/OpenApi/repeated-inline-response-schemas.yaml'),
            '--target' => $target,
            '--remove-root-key' => ['tags', 'x-generated-at'],
            '--dedupe-inline-response-schemas' => true,
        ]);

        $this->assertSame(0, $exitCode);

        $yaml = file_get_contents($target);

        $this->assertIsString($yaml);
        $this->assertStringNotContainsString('example', $yaml);

        $document = Yaml::parse($yaml);

        $this->assertIsArray($document);
        $this->assertArrayNotHasKey('tags', $document);
        $this->assertArrayNotHasKey('x-generated-at', $document);
        $this->assertSame([['url' => '/api/v1']], $document['servers']);
        $this->assertArrayHasKey('/health', $document['paths']);
        $this->assertArrayNotHasKey('/api/v1/health', $document['paths']);
        $this->assertSame(
            ['$ref' => '#/components/schemas/SharedResponse1'],
            $document['paths']['/health']['get']['responses'][200]['content']['application/json']['schema'],
        );
        $this->assertSame(
            ['$ref' => '#/components/schemas/SharedResponse1'],
            $document['paths']['/status']['get']['responses'][200]['content']['application/json']['schema'],
        );
    }
}
