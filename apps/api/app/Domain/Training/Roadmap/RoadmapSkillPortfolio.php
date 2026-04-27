<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapSkillPortfolio
{
    /**
     * @param  list<array<string, mixed>>  $developmentTracks
     * @param  list<array<string, mixed>>  $technicalPracticeTracks
     * @param  list<array<string, mixed>>  $accessoryTracks
     * @param  list<array<string, mixed>>  $maintenanceTracks
     * @param  list<array<string, mixed>>  $foundationTracks
     * @param  list<array<string, mixed>>  $futureQueue
     * @param  list<array<string, mixed>>  $notRecommendedNow
     * @param  array<string, mixed>  $optimizer
     */
    public function __construct(
        public array $developmentTracks,
        public array $technicalPracticeTracks,
        public array $accessoryTracks,
        public array $maintenanceTracks,
        public array $foundationTracks,
        public array $futureQueue,
        public array $notRecommendedNow,
        public array $optimizer,
    ) {}
}
