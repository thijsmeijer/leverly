<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapModuleCompatibility
{
    /**
     * @param  list<string>  $overlappingAxes
     * @param  list<string>  $reasons
     * @param  list<string>  $warnings
     * @param  array<string, mixed>|null  $suggestedAdjustment
     */
    public function __construct(
        public string $primaryModuleId,
        public string $secondaryModuleId,
        public string $state,
        public bool $compatible,
        public array $overlappingAxes,
        public array $reasons,
        public array $warnings,
        public ?array $suggestedAdjustment = null,
    ) {}

    /**
     * @return array{
     *     primary_module_id: string,
     *     secondary_module_id: string,
     *     state: string,
     *     compatible: bool,
     *     overlapping_axes: list<string>,
     *     reasons: list<string>,
     *     warnings: list<string>,
     *     suggested_adjustment: array<string, mixed>|null
     * }
     */
    public function toArray(): array
    {
        return [
            'primary_module_id' => $this->primaryModuleId,
            'secondary_module_id' => $this->secondaryModuleId,
            'state' => $this->state,
            'compatible' => $this->compatible,
            'overlapping_axes' => $this->overlappingAxes,
            'reasons' => $this->reasons,
            'warnings' => $this->warnings,
            'suggested_adjustment' => $this->suggestedAdjustment,
        ];
    }
}
