<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

interface RoadmapAdaptationEvidenceProvider
{
    /**
     * @param  array<string, mixed>  $portfolio
     */
    public function evidenceFor(RoadmapInput $input, array $portfolio): RoadmapAdaptationInput;
}
