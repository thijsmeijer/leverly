<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapEtaRange
{
    /**
     * @param  list<string>  $modifiers
     */
    public function __construct(
        public int $lowerWeeks,
        public int $upperWeeks,
        public int $p50Weeks,
        public int $p80Weeks,
        public float $confidence,
        public array $modifiers,
    ) {}

    /**
     * @return array{
     *     lower_weeks: int,
     *     upper_weeks: int,
     *     p50_weeks: int,
     *     p80_weeks: int,
     *     confidence: float,
     *     modifiers: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'lower_weeks' => $this->lowerWeeks,
            'upper_weeks' => $this->upperWeeks,
            'p50_weeks' => $this->p50Weeks,
            'p80_weeks' => $this->p80Weeks,
            'confidence' => $this->confidence,
            'modifiers' => $this->modifiers,
        ];
    }
}
