<?php

namespace App\Console\Commands;

use App\Support\OpenApi\OpenApiDocumentCleaner;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

class SyncScribeOpenApiCommand extends Command
{
    protected $signature = 'leverly:openapi:sync-scribe
        {--source= : Source Scribe OpenAPI YAML path}
        {--target= : Target OpenAPI YAML path}
        {--remove-root-key=* : Remove a root-level OpenAPI key before writing the stable spec}
        {--dedupe-inline-response-schemas : Deduplicate repeated inline JSON response schemas}';

    protected $description = 'Sync Scribe OpenAPI output into the stable repository contract path.';

    public function __construct(private readonly OpenApiDocumentCleaner $cleaner)
    {
        parent::__construct();
    }

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

        $document = $this->cleaner->clean(
            document: $document,
            rootKeysToRemove: $this->rootKeysToRemove(),
            deduplicateInlineResponseSchemas: (bool) $this->option('dedupe-inline-response-schemas'),
        );
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
     * @return list<string>
     */
    private function rootKeysToRemove(): array
    {
        $rootKeys = $this->option('remove-root-key');

        if (! is_array($rootKeys)) {
            return [];
        }

        return array_values(array_filter($rootKeys, is_string(...)));
    }
}
