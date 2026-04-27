<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class ProgressionGraph
{
    /**
     * @param  list<ProgressionGraphNode>  $nodes
     */
    public function __construct(
        public string $family,
        public string $label,
        private array $nodes,
    ) {}

    /**
     * @return list<ProgressionGraphNode>
     */
    public function nodes(): array
    {
        return $this->nodes;
    }

    /**
     * @return list<string>
     */
    public function nodeSlugs(): array
    {
        return array_map(
            static fn (ProgressionGraphNode $node): string => $node->slug,
            $this->nodes,
        );
    }

    public function node(string $slug): ?ProgressionGraphNode
    {
        foreach ($this->nodes as $node) {
            if ($node->slug === $slug) {
                return $node;
            }
        }

        return null;
    }

    public function nextNode(string $slug): ?ProgressionGraphNode
    {
        foreach ($this->nodes as $index => $node) {
            if ($node->slug === $slug) {
                return $this->nodes[$index + 1] ?? null;
            }
        }

        return null;
    }

    /**
     * @return array{family: string, label: string, nodes: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'family' => $this->family,
            'label' => $this->label,
            'nodes' => array_map(
                static fn (ProgressionGraphNode $node): array => $node->toArray(),
                $this->nodes,
            ),
        ];
    }
}
