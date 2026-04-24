<?php

namespace App\Support\OpenApi;

use Illuminate\Support\Arr;
use JsonException;

class OpenApiDocumentCleaner
{
    private const BASE_PATH = '/api/v1';

    /**
     * @param  array<string, mixed>  $document
     * @param  list<string>  $rootKeysToRemove
     * @return array<string, mixed>
     */
    public function clean(
        array $document,
        array $rootKeysToRemove = [],
        bool $deduplicateInlineResponseSchemas = false,
    ): array {
        Arr::set($document, 'info.title', 'Leverly API');
        Arr::set($document, 'info.version', '0.1.0');
        Arr::set($document, 'info.description', 'Versioned JSON API for Leverly.');
        Arr::set($document, 'servers', [['url' => self::BASE_PATH]]);
        Arr::set($document, 'paths', $this->normalizePaths(Arr::get($document, 'paths', [])));

        foreach ($rootKeysToRemove as $rootKey) {
            unset($document[$rootKey]);
        }

        $document = $this->removeExampleFields($document);

        if ($deduplicateInlineResponseSchemas) {
            $document = $this->deduplicateInlineResponseSchemas($document);
        }

        return $document;
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizePaths(mixed $paths): array
    {
        if (! is_array($paths)) {
            return [];
        }

        $normalizedPaths = [];

        foreach ($paths as $path => $definition) {
            $normalizedPath = $path;

            if (is_string($path) && str_starts_with($path, self::BASE_PATH.'/')) {
                $normalizedPath = substr($path, strlen(self::BASE_PATH));
            }

            if ($path === self::BASE_PATH) {
                $normalizedPath = '/';
            }

            $normalizedPaths[$normalizedPath] = $definition;
        }

        return $normalizedPaths;
    }

    private function removeExampleFields(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        $cleaned = [];

        foreach ($value as $key => $childValue) {
            if ($key === 'example' || $key === 'examples') {
                continue;
            }

            $cleaned[$key] = $this->removeExampleFields($childValue);
        }

        return $cleaned;
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function deduplicateInlineResponseSchemas(array $document): array
    {
        $occurrencesByHash = $this->collectInlineResponseSchemas($document);
        $sharedSchemaNumber = 1;

        foreach ($occurrencesByHash as $occurrences) {
            if (count($occurrences) < 2) {
                continue;
            }

            $schemaName = 'SharedResponse'.$sharedSchemaNumber;
            $sharedSchemaNumber++;
            Arr::set($document, 'components.schemas.'.$schemaName, $occurrences[0]['schema']);

            foreach ($occurrences as $occurrence) {
                $this->setByPath(
                    $occurrence['path'],
                    $document,
                    ['$ref' => '#/components/schemas/'.$schemaName],
                );
            }
        }

        return $document;
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, list<array{path: list<string|int>, schema: array<string, mixed>}>>
     *
     * @throws JsonException
     */
    private function collectInlineResponseSchemas(array $document): array
    {
        $paths = Arr::get($document, 'paths', []);

        if (! is_array($paths)) {
            return [];
        }

        $occurrencesByHash = [];

        foreach ($paths as $path => $pathItem) {
            if (! is_string($path) || ! is_array($pathItem)) {
                continue;
            }

            foreach ($pathItem as $method => $operation) {
                if (! is_string($method) || ! is_array($operation)) {
                    continue;
                }

                $responses = $operation['responses'] ?? [];

                if (! is_array($responses)) {
                    continue;
                }

                foreach ($responses as $status => $response) {
                    if (! is_array($response)) {
                        continue;
                    }

                    $schema = $response['content']['application/json']['schema'] ?? null;

                    if (! is_array($schema) || array_key_exists('$ref', $schema)) {
                        continue;
                    }

                    $schemaHash = $this->hashSchema($schema);
                    $occurrencesByHash[$schemaHash][] = [
                        'path' => [
                            'paths',
                            $path,
                            $method,
                            'responses',
                            $status,
                            'content',
                            'application/json',
                            'schema',
                        ],
                        'schema' => $schema,
                    ];
                }
            }
        }

        return $occurrencesByHash;
    }

    /**
     * @param  array<string, mixed>  $schema
     *
     * @throws JsonException
     */
    private function hashSchema(array $schema): string
    {
        $json = json_encode(
            $this->canonicalize($schema),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        return sha1($json);
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => $this->canonicalize($item), $value);
        }

        ksort($value);

        foreach ($value as $key => $childValue) {
            $value[$key] = $this->canonicalize($childValue);
        }

        return $value;
    }

    /**
     * @param  list<string|int>  $path
     * @param  array<string, mixed>  $document
     */
    private function setByPath(array $path, array &$document, mixed $value): void
    {
        $target = &$document;

        foreach ($path as $segment) {
            if (! is_array($target)) {
                return;
            }

            $target = &$target[$segment];
        }

        $target = $value;
    }
}
