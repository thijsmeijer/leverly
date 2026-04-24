<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Symfony\Component\Yaml\Yaml;

class SyncScribeOpenApiCommand extends Command
{
    protected $signature = 'leverly:openapi:sync-scribe
        {--source= : Source Scribe OpenAPI YAML path}
        {--target= : Target OpenAPI YAML path}';

    protected $description = 'Sync Scribe OpenAPI output into the stable repository contract path.';

    public function handle(): int
    {
        $source = $this->option('source') ?: storage_path('app/private/scribe/openapi.yaml');
        $target = $this->option('target') ?: base_path('../../docs/api/openapi.yaml');

        if (! is_file($source)) {
            $this->error("Scribe OpenAPI output is missing at {$source}.");

            return self::FAILURE;
        }

        $document = Yaml::parseFile($source);

        if (! is_array($document)) {
            $this->error("Scribe OpenAPI output is not a YAML object: {$source}.");

            return self::FAILURE;
        }

        $document = $this->normalize($document);
        $yaml = Yaml::dump($document, 12, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);

        $targetDirectory = dirname($target);

        if (! is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        file_put_contents($target, $yaml);

        $this->info("OpenAPI contract synced to {$target}");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $document
     * @return array<string, mixed>
     */
    private function normalize(array $document): array
    {
        Arr::set($document, 'info.title', 'Leverly API');
        Arr::set($document, 'info.version', '0.1.0');
        Arr::set($document, 'info.description', 'Versioned JSON API for Leverly.');
        Arr::set($document, 'servers', [['url' => '/api/v1']]);

        $paths = Arr::get($document, 'paths', []);

        if (is_array($paths)) {
            $normalizedPaths = [];

            foreach ($paths as $path => $definition) {
                $normalizedPath = is_string($path) && str_starts_with($path, '/api/v1/')
                    ? substr($path, strlen('/api/v1'))
                    : $path;

                $normalizedPaths[$normalizedPath] = $definition;
            }

            Arr::set($document, 'paths', $normalizedPaths);
        }

        return $this->removeExampleFields($document);
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
}
