<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class BaselineNodePlacement
{
    /**
     * @param  list<string>  $observedEvidence
     * @param  list<string>  $missingEvidence
     */
    public function __construct(
        public string $family,
        public ProgressionGraphNode $currentNode,
        public ?ProgressionGraphNode $nextNode,
        public int $completionPercentage,
        public array $observedEvidence,
        public array $missingEvidence,
        public float $confidenceContribution,
    ) {}

    /**
     * @param  list<string>  $observedEvidence
     * @param  list<string>  $missingEvidence
     */
    public function withEvidence(array $observedEvidence = [], array $missingEvidence = [], ?float $confidenceFloor = null): self
    {
        return new self(
            family: $this->family,
            currentNode: $this->currentNode,
            nextNode: $this->nextNode,
            completionPercentage: $this->completionPercentage,
            observedEvidence: self::unique([...$this->observedEvidence, ...$observedEvidence]),
            missingEvidence: self::unique([...$this->missingEvidence, ...$missingEvidence]),
            confidenceContribution: max($this->confidenceContribution, $confidenceFloor ?? $this->confidenceContribution),
        );
    }

    /**
     * @return array{
     *     family: string,
     *     current_node: array<string, mixed>,
     *     next_node: array<string, mixed>|null,
     *     completion_percentage: int,
     *     observed_evidence: list<string>,
     *     missing_evidence: list<string>,
     *     confidence_contribution: float
     * }
     */
    public function toArray(): array
    {
        return [
            'family' => $this->family,
            'current_node' => $this->currentNode->toArray(),
            'next_node' => $this->nextNode?->toArray(),
            'completion_percentage' => $this->completionPercentage,
            'observed_evidence' => $this->observedEvidence,
            'missing_evidence' => $this->missingEvidence,
            'confidence_contribution' => $this->confidenceContribution,
        ];
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private static function unique(array $values): array
    {
        return array_values(array_unique(array_filter($values, static fn (string $value): bool => $value !== '')));
    }
}
