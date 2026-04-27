<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapStressBudget
{
    public const array AXES = [
        'bent_arm_push',
        'bent_arm_pull',
        'straight_arm_push',
        'straight_arm_pull',
        'explosive_pull',
        'overhead_push',
        'wrist_extension',
        'elbow_pull_tendon',
        'elbow_push_tendon',
        'shoulder_extension',
        'shoulder_flexion',
        'inversion_balance',
        'compression',
        'trunk_rigidity',
        'lower_body',
        'ankle_knee',
        'systemic_fatigue',
    ];

    /**
     * @param  array<string, int>  $weeklyBudget
     * @param  array<string, int>  $perDaySoftCap
     * @param  array<string, int>  $perDayHardCap
     * @param  list<array<string, mixed>>  $recoveryRules
     * @param  list<string>  $painReducedAxes
     */
    public function __construct(
        public array $weeklyBudget,
        public array $perDaySoftCap,
        public array $perDayHardCap,
        public array $recoveryRules,
        public int $highStressDevelopmentLanes,
        public int $spacingCapacity,
        public int $timeCapacityMinutesPerWeek,
        public array $painReducedAxes,
    ) {}

    /**
     * @return array{
     *     weekly_budget: array<string, int>,
     *     per_day_soft_cap: array<string, int>,
     *     per_day_hard_cap: array<string, int>,
     *     recovery_rules: list<array<string, mixed>>,
     *     high_stress_development_lanes: int,
     *     spacing_capacity: int,
     *     time_capacity_minutes_per_week: int,
     *     pain_reduced_axes: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'weekly_budget' => $this->weeklyBudget,
            'per_day_soft_cap' => $this->perDaySoftCap,
            'per_day_hard_cap' => $this->perDayHardCap,
            'recovery_rules' => $this->recoveryRules,
            'high_stress_development_lanes' => $this->highStressDevelopmentLanes,
            'spacing_capacity' => $this->spacingCapacity,
            'time_capacity_minutes_per_week' => $this->timeCapacityMinutesPerWeek,
            'pain_reduced_axes' => $this->painReducedAxes,
        ];
    }
}
