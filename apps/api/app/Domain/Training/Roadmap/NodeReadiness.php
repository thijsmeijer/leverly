<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class NodeReadiness
{
    /**
     * @param  list<string>  $blockers
     * @param  list<string>  $warnings
     * @param  list<string>  $positiveReasons
     * @param  array<string, mixed>  $etaToNextNode
     * @param  array<string, mixed>|null  $etaToTarget
     */
    public function __construct(
        public string $skillTrackId,
        public ProgressionGraphNode $targetNode,
        public ProgressionGraphNode $currentNode,
        public ?ProgressionGraphNode $nextNode,
        public string $status,
        public int $readinessScore,
        public float $confidence,
        public array $blockers,
        public array $warnings,
        public array $positiveReasons,
        public array $etaToNextNode,
        public ?array $etaToTarget,
    ) {}

    /**
     * @return array{
     *     skill_track_id: string,
     *     target_node: array{id: string, label: string},
     *     current_node: array{id: string, label: string},
     *     next_node: array{id: string, label: string}|null,
     *     long_term_target_node: array{id: string, label: string},
     *     status: string,
     *     readiness_score: int,
     *     confidence: float,
     *     blockers: list<string>,
     *     warnings: list<string>,
     *     positive_reasons: list<string>,
     *     eta_to_next_node: array<string, mixed>,
     *     eta_to_target: array<string, mixed>|null
     * }
     */
    public function toArray(): array
    {
        return [
            'skill_track_id' => $this->skillTrackId,
            'target_node' => self::node($this->targetNode),
            'current_node' => self::node($this->currentNode),
            'next_node' => $this->nextNode === null ? null : self::node($this->nextNode),
            'long_term_target_node' => self::node($this->targetNode),
            'status' => $this->status,
            'readiness_score' => $this->readinessScore,
            'confidence' => $this->confidence,
            'blockers' => $this->blockers,
            'warnings' => $this->warnings,
            'positive_reasons' => $this->positiveReasons,
            'eta_to_next_node' => $this->etaToNextNode,
            'eta_to_target' => $this->etaToTarget,
        ];
    }

    /**
     * @return array{id: string, label: string}
     */
    private static function node(ProgressionGraphNode $node): array
    {
        return [
            'id' => $node->nodeId,
            'label' => $node->label,
        ];
    }
}
