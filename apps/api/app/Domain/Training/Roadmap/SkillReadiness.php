<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class SkillReadiness
{
    /**
     * @param  list<string>  $hardBlockers
     * @param  list<string>  $softFactors
     * @param  list<string>  $safetyPenalties
     * @param  list<string>  $missingEvidence
     */
    public function __construct(
        public string $skill,
        public string $label,
        public string $status,
        public int $readinessScore,
        public float $confidence,
        public array $hardBlockers,
        public array $softFactors,
        public array $safetyPenalties,
        public array $missingEvidence,
    ) {}

    /**
     * @return array{
     *     skill: string,
     *     label: string,
     *     status: string,
     *     readiness_score: int,
     *     confidence: float,
     *     hard_blockers: list<string>,
     *     soft_factors: list<string>,
     *     safety_penalties: list<string>,
     *     missing_evidence: list<string>
     * }
     */
    public function toArray(): array
    {
        return [
            'skill' => $this->skill,
            'label' => $this->label,
            'status' => $this->status,
            'readiness_score' => $this->readinessScore,
            'confidence' => $this->confidence,
            'hard_blockers' => $this->hardBlockers,
            'soft_factors' => $this->softFactors,
            'safety_penalties' => $this->safetyPenalties,
            'missing_evidence' => $this->missingEvidence,
        ];
    }
}
