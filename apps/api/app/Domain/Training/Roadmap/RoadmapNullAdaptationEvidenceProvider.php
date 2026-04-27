<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapNullAdaptationEvidenceProvider implements RoadmapAdaptationEvidenceProvider
{
    /**
     * @param  array<string, mixed>  $portfolio
     */
    public function evidenceFor(RoadmapInput $input, array $portfolio): RoadmapAdaptationInput
    {
        return RoadmapAdaptationInput::empty();
    }
}
