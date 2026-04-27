<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapAdaptationInput
{
    /**
     * @param  list<RoadmapAdaptationSessionEvidence>  $sessions
     */
    public function __construct(public array $sessions) {}

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     */
    public static function fromSessions(array $sessions): self
    {
        return new self(array_values(array_map(
            static fn (array $session): RoadmapAdaptationSessionEvidence => RoadmapAdaptationSessionEvidence::fromArray($session),
            array_filter($sessions, is_array(...)),
        )));
    }

    public function hasEvidence(): bool
    {
        return $this->sessions !== [];
    }

    public function evidenceWeeks(): int
    {
        $weeks = array_map(
            static fn (RoadmapAdaptationSessionEvidence $session): int => $session->weekNumber,
            $this->sessions,
        );

        return count(array_unique($weeks));
    }

    /**
     * @return list<RoadmapAdaptationModuleEvidence>
     */
    public function moduleEvidenceForSkill(string $skillTrackId): array
    {
        $evidence = [];

        foreach ($this->sessions as $session) {
            foreach ($session->modules as $module) {
                if ($module->skillTrackId === $skillTrackId) {
                    $evidence[] = $module;
                }
            }
        }

        return $evidence;
    }

    public function moduleEvidenceCount(): int
    {
        return array_sum(array_map(
            static fn (RoadmapAdaptationSessionEvidence $session): int => count($session->modules),
            $this->sessions,
        ));
    }
}
