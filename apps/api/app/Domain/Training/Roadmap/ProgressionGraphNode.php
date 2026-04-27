<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class ProgressionGraphNode
{
    public function __construct(
        public string $slug,
        public string $label,
        public int $order,
        public string $family,
        public string $metricType,
        public int $minEdgeWeeks,
        public int $maxEdgeWeeks,
        public string $unlock,
    ) {}

    /**
     * @return array{min_weeks: int, max_weeks: int}
     */
    public function edgeTimeBand(): array
    {
        return [
            'min_weeks' => $this->minEdgeWeeks,
            'max_weeks' => $this->maxEdgeWeeks,
        ];
    }

    /**
     * @return array{
     *     slug: string,
     *     label: string,
     *     order: int,
     *     family: string,
     *     metric_type: string,
     *     edge_time_band: array{min_weeks: int, max_weeks: int},
     *     unlock: string
     * }
     */
    public function toArray(): array
    {
        return [
            'slug' => $this->slug,
            'label' => $this->label,
            'order' => $this->order,
            'family' => $this->family,
            'metric_type' => $this->metricType,
            'edge_time_band' => $this->edgeTimeBand(),
            'unlock' => $this->unlock,
        ];
    }
}
