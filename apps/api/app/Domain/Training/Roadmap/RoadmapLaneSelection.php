<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapLaneSelection
{
    /**
     * @param  array{slug: string, label: string, focus: list<string>}  $foundationLane
     * @param  list<array{skill: string, label: string, reason: string, unlock_conditions: list<string>}>  $deferredGoals
     * @param  list<string>  $compatibilityNotes
     */
    public function __construct(
        public ?SkillReadiness $primaryLane,
        public ?SkillReadiness $secondaryLane,
        public array $foundationLane,
        public array $deferredGoals,
        public array $compatibilityNotes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'primary_lane' => $this->primaryLane?->toArray(),
            'secondary_lane' => $this->secondaryLane?->toArray(),
            'foundation_lane' => $this->foundationLane,
            'deferred_goals' => $this->deferredGoals,
            'compatibility_notes' => $this->compatibilityNotes,
        ];
    }
}
