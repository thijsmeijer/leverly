<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class GoalLaneSelector
{
    public static function fromInput(RoadmapInput $input): RoadmapLaneSelection
    {
        $readiness = SkillReadinessCalculator::fromInput($input);
        $requested = self::requestedSkills($input);
        $primary = self::selectPrimary($readiness, $requested);
        $secondary = null;
        $deferred = [];
        $notes = [];

        foreach (self::secondaryCandidates($readiness, $requested, $primary) as $candidate) {
            if ($primary === null || $candidate->skill === $primary->skill) {
                continue;
            }

            $compatibility = GoalStressCatalog::compatibility($primary, $candidate, $input);

            if ($candidate->status === 'blocked') {
                $deferred[] = self::deferredGoal($candidate, self::firstReason($candidate->hardBlockers, 'Safety gate blocks this lane for now.'));

                continue;
            }

            if (! $compatibility['compatible']) {
                $deferred[] = self::deferredGoal($candidate, $compatibility['reason']);

                continue;
            }

            if ($candidate->status === 'ready' || GoalStressCatalog::isLowFatigueSupport($candidate->skill)) {
                $secondary = $candidate;
                $notes = [...$notes, ...$compatibility['notes']];

                break;
            }

            $deferred[] = self::deferredGoal($candidate, self::firstReason($candidate->hardBlockers, 'Readiness is not high enough for a secondary lane yet.'));
        }

        foreach ($requested as $skill) {
            if (($primary !== null && $skill === $primary->skill) || ($secondary !== null && $skill === $secondary->skill)) {
                continue;
            }

            if (self::hasDeferred($deferred, $skill)) {
                continue;
            }

            $candidate = $readiness[$skill] ?? null;
            if ($candidate !== null) {
                $deferred[] = self::deferredGoal($candidate, self::firstReason($candidate->hardBlockers, 'Not selected for this block.'));
            }
        }

        return new RoadmapLaneSelection(
            primaryLane: $primary,
            secondaryLane: $secondary,
            foundationLane: [
                'slug' => 'foundation_strength',
                'label' => 'Foundation strength',
                'focus' => ['push', 'pull', 'bodyline', 'tissue tolerance'],
            ],
            deferredGoals: $deferred,
            compatibilityNotes: self::unique($notes),
        );
    }

    /**
     * @return list<string>
     */
    private static function requestedSkills(RoadmapInput $input): array
    {
        return self::unique(array_values(array_filter([
            $input->selectedPrimaryGoal,
            ...$input->secondaryInterests,
            ...$input->longTermAspirations,
        ], is_string(...))));
    }

    /**
     * @param  array<string, SkillReadiness>  $readiness
     * @param  list<string>  $requested
     */
    private static function selectPrimary(array $readiness, array $requested): ?SkillReadiness
    {
        foreach ($requested as $skill) {
            $candidate = $readiness[$skill] ?? null;

            if ($candidate !== null && $candidate->status !== 'blocked') {
                return $candidate;
            }
        }

        $ready = array_values(array_filter(
            $readiness,
            static fn (SkillReadiness $candidate): bool => $candidate->status === 'ready',
        ));

        usort(
            $ready,
            static fn (SkillReadiness $left, SkillReadiness $right): int => $right->readinessScore <=> $left->readinessScore,
        );

        return $ready[0] ?? null;
    }

    /**
     * @param  array<string, SkillReadiness>  $readiness
     * @param  list<string>  $requested
     * @return list<SkillReadiness>
     */
    private static function secondaryCandidates(array $readiness, array $requested, ?SkillReadiness $primary): array
    {
        $candidates = [];

        foreach ($requested as $skill) {
            if ($primary !== null && $skill === $primary->skill) {
                continue;
            }

            if (isset($readiness[$skill])) {
                $candidates[] = $readiness[$skill];
            }
        }

        if (count($candidates) > 0) {
            return $candidates;
        }

        foreach (GoalStressCatalog::lowFatigueSkills() as $skill) {
            if ($primary !== null && $skill === $primary->skill) {
                continue;
            }

            if (isset($readiness[$skill]) && ! in_array($readiness[$skill], $candidates, true)) {
                $candidates[] = $readiness[$skill];
            }
        }

        return $candidates;
    }

    /**
     * @return array{skill: string, label: string, reason: string, unlock_conditions: list<string>}
     */
    private static function deferredGoal(SkillReadiness $candidate, string $reason): array
    {
        return [
            'skill' => $candidate->skill,
            'label' => $candidate->label,
            'reason' => $reason,
            'unlock_conditions' => self::unique([
                ...$candidate->hardBlockers,
                ...array_slice($candidate->missingEvidence, 0, 3),
                'Keep foundation strength in the active block.',
            ]),
        ];
    }

    /**
     * @param  list<array{skill: string, label: string, reason: string, unlock_conditions: list<string>}>  $deferred
     */
    private static function hasDeferred(array $deferred, string $skill): bool
    {
        foreach ($deferred as $item) {
            if ($item['skill'] === $skill) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $reasons
     */
    private static function firstReason(array $reasons, string $fallback): string
    {
        return $reasons[0] ?? $fallback;
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private static function unique(array $values): array
    {
        return array_values(array_unique(array_filter($values, static fn (string $value): bool => $value !== '')));
    }
}
