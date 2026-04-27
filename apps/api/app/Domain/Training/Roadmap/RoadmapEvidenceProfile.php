<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapEvidenceProfile
{
    public const string VERSION = 'roadmap.evidence.v3';

    /**
     * @param  array<string, mixed>  $bodyContext
     * @param  array<string, mixed>  $trainingContext
     * @param  list<array<string, mixed>>  $samples
     * @param  list<array<string, mixed>>  $pendingTests
     * @param  list<string>  $uncertaintyFlags
     */
    public function __construct(
        private array $bodyContext,
        private array $trainingContext,
        private array $samples,
        private array $pendingTests,
        private array $uncertaintyFlags,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'version' => self::VERSION,
            'body_context' => $this->bodyContext,
            'training_context' => $this->trainingContext,
            'samples' => $this->samples,
            'pending_tests' => $this->pendingTests,
            'uncertainty_flags' => $this->uncertaintyFlags,
        ];
    }
}
