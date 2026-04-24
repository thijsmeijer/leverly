<?php

namespace Tests\Unit\Support\OpenApi;

use App\Support\OpenApi\OpenApiDocumentCleaner;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

class OpenApiDocumentCleanerTest extends TestCase
{
    public function test_it_removes_examples_and_normalizes_scribe_output(): void
    {
        $document = $this->fixture('scribe-with-examples.yaml');

        $cleaned = (new OpenApiDocumentCleaner)->clean($document);

        $this->assertSame('Leverly API', $cleaned['info']['title']);
        $this->assertSame('0.1.0', $cleaned['info']['version']);
        $this->assertSame('Versioned JSON API for Leverly.', $cleaned['info']['description']);
        $this->assertSame([['url' => '/api/v1']], $cleaned['servers']);
        $this->assertArrayHasKey('/health', $cleaned['paths']);
        $this->assertArrayNotHasKey('/api/v1/health', $cleaned['paths']);
        $this->assertStringNotContainsString('example', Yaml::dump($cleaned, 12, 2));
    }

    public function test_it_can_remove_configured_root_keys(): void
    {
        $document = $this->fixture('root-metadata.yaml');

        $cleaned = (new OpenApiDocumentCleaner)->clean($document, rootKeysToRemove: ['tags', 'x-generated-at']);

        $this->assertArrayNotHasKey('tags', $cleaned);
        $this->assertArrayNotHasKey('x-generated-at', $cleaned);
        $this->assertArrayHasKey('paths', $cleaned);
    }

    public function test_it_can_deduplicate_repeated_inline_json_response_schemas(): void
    {
        $document = $this->fixture('repeated-inline-response-schemas.yaml');

        $cleaned = (new OpenApiDocumentCleaner)->clean($document, deduplicateInlineResponseSchemas: true);

        $this->assertSame(
            ['$ref' => '#/components/schemas/SharedResponse1'],
            $cleaned['paths']['/health']['get']['responses'][200]['content']['application/json']['schema'],
        );
        $this->assertSame(
            ['$ref' => '#/components/schemas/SharedResponse1'],
            $cleaned['paths']['/status']['get']['responses'][200]['content']['application/json']['schema'],
        );
        $this->assertSame(
            ['type' => 'object', 'properties' => ['version' => ['type' => 'string']]],
            $cleaned['paths']['/version']['get']['responses'][200]['content']['application/json']['schema'],
        );
        $this->assertArrayHasKey('SharedResponse1', $cleaned['components']['schemas']);
        $this->assertCount(1, $cleaned['components']['schemas']);
    }

    /**
     * @return array<string, mixed>
     */
    private function fixture(string $file): array
    {
        $document = Yaml::parseFile(__DIR__.'/../../../Fixtures/OpenApi/'.$file);

        $this->assertIsArray($document);

        return $document;
    }
}
