<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class ProgressionGraphEdge
{
    public function __construct(
        public string $sourceSlug,
        public string $targetSlug,
        public string $sourceNodeId,
        public string $targetNodeId,
        public int $p25Weeks,
        public int $p50Weeks,
        public int $p80Weeks,
        /** @var array<string, int> */
        public array $minimumDomainScores,
        /** @var array<string, string> */
        public array $previousOwnershipRequirements,
        public string $progressionType,
        public string $riskLevel,
        public string $notes,
    ) {}

    /**
     * @return array{
     *     source_slug: string,
     *     target_slug: string,
     *     source_node_id: string,
     *     target_node_id: string,
     *     base_weeks: array{p25: int, p50: int, p80: int},
     *     minimum_domain_scores: array<string, int>,
     *     previous_ownership_requirements: array<string, string>,
     *     progression_type: string,
     *     risk_level: string,
     *     notes: string
     * }
     */
    public function toArray(): array
    {
        return [
            'source_slug' => $this->sourceSlug,
            'target_slug' => $this->targetSlug,
            'source_node_id' => $this->sourceNodeId,
            'target_node_id' => $this->targetNodeId,
            'base_weeks' => [
                'p25' => $this->p25Weeks,
                'p50' => $this->p50Weeks,
                'p80' => $this->p80Weeks,
            ],
            'minimum_domain_scores' => $this->minimumDomainScores,
            'previous_ownership_requirements' => $this->previousOwnershipRequirements,
            'progression_type' => $this->progressionType,
            'risk_level' => $this->riskLevel,
            'notes' => $this->notes,
        ];
    }
}
