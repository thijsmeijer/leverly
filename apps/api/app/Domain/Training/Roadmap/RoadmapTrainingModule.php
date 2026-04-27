<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapTrainingModule
{
    /**
     * @param  array<string, int>  $stressVector
     * @param  array<string, mixed>  $dose
     * @param  array{min: int, max: int}  $timeCostMinutes
     * @param  array{min_per_week: int, target_per_week: int, max_per_week: int}  $exposureTargets
     * @param  array{min_hours_by_stress_axis: array<string, int>}  $recoveryRequirements
     * @param  list<string>  $allowedSessionSlots
     * @param  list<string>  $compatibleDayTypes
     * @param  list<array<string, string>>  $prerequisites
     * @param  array<string, mixed>  $progressionRule
     */
    public function __construct(
        public string $moduleId,
        public string $skillTrackId,
        public string $nodeId,
        public string $title,
        public string $purpose,
        public string $pattern,
        public string $intensityTier,
        public string $fatigueClass,
        public array $stressVector,
        public array $dose,
        public array $timeCostMinutes,
        public array $exposureTargets,
        public array $recoveryRequirements,
        public array $allowedSessionSlots,
        public array $compatibleDayTypes,
        public array $prerequisites,
        public array $progressionRule,
    ) {}

    /**
     * @return array{
     *     module_id: string,
     *     skill_track_id: string,
     *     node_id: string,
     *     title: string,
     *     purpose: string,
     *     pattern: string,
     *     intensity_tier: string,
     *     fatigue_class: string,
     *     stress_vector: array<string, int>,
     *     dose: array<string, mixed>,
     *     time_cost_minutes: array{min: int, max: int},
     *     exposure_targets: array{min_per_week: int, target_per_week: int, max_per_week: int},
     *     recovery_requirements: array{min_hours_by_stress_axis: array<string, int>},
     *     allowed_session_slots: list<string>,
     *     compatible_day_types: list<string>,
     *     prerequisites: list<array<string, string>>,
     *     progression_rule: array<string, mixed>
     * }
     */
    public function toArray(): array
    {
        return [
            'module_id' => $this->moduleId,
            'skill_track_id' => $this->skillTrackId,
            'node_id' => $this->nodeId,
            'title' => $this->title,
            'purpose' => $this->purpose,
            'pattern' => $this->pattern,
            'intensity_tier' => $this->intensityTier,
            'fatigue_class' => $this->fatigueClass,
            'stress_vector' => $this->stressVector,
            'dose' => $this->dose,
            'time_cost_minutes' => $this->timeCostMinutes,
            'exposure_targets' => $this->exposureTargets,
            'recovery_requirements' => $this->recoveryRequirements,
            'allowed_session_slots' => $this->allowedSessionSlots,
            'compatible_day_types' => $this->compatibleDayTypes,
            'prerequisites' => $this->prerequisites,
            'progression_rule' => $this->progressionRule,
        ];
    }
}
