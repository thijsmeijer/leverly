<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapInput
{
    /**
     * @param  array<string, mixed>  $profileContext
     * @param  array<string, mixed>  $trainingContext
     * @param  list<string>  $equipment
     * @param  array<string, mixed>  $painFlags
     * @param  array<string, mixed>  $baselineTests
     * @param  array<string, mixed>  $goalModules
     * @param  list<string>  $secondaryInterests
     * @param  list<string>  $longTermAspirations
     */
    public function __construct(
        public array $profileContext,
        public array $trainingContext,
        public array $equipment,
        public array $painFlags,
        public array $baselineTests,
        public array $goalModules,
        public ?string $selectedPrimaryGoal,
        public array $secondaryInterests,
        public array $longTermAspirations,
    ) {}
}
