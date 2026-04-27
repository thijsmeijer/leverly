<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapScheduledModule
{
    /**
     * @param  array<string, int>  $stressVector
     */
    public function __construct(
        public string $moduleId,
        public string $skillTrackId,
        public string $title,
        public string $purpose,
        public string $pattern,
        public string $intensityTier,
        public string $sourceMode,
        public string $slot,
        public int $slotRank,
        public int $order,
        public int $exposureIndex,
        public int $estimatedMinutes,
        public array $stressVector,
    ) {}

    /**
     * @return array{
     *     module_id: string,
     *     skill_track_id: string,
     *     title: string,
     *     purpose: string,
     *     pattern: string,
     *     intensity_tier: string,
     *     source_mode: string,
     *     slot: string,
     *     slot_rank: int,
     *     order: int,
     *     exposure_index: int,
     *     estimated_minutes: int,
     *     stress_vector: array<string, int>
     * }
     */
    public function toArray(): array
    {
        return [
            'module_id' => $this->moduleId,
            'skill_track_id' => $this->skillTrackId,
            'title' => $this->title,
            'purpose' => $this->purpose,
            'pattern' => $this->pattern,
            'intensity_tier' => $this->intensityTier,
            'source_mode' => $this->sourceMode,
            'slot' => $this->slot,
            'slot_rank' => $this->slotRank,
            'order' => $this->order,
            'exposure_index' => $this->exposureIndex,
            'estimated_minutes' => $this->estimatedMinutes,
            'stress_vector' => $this->stressVector,
        ];
    }
}
