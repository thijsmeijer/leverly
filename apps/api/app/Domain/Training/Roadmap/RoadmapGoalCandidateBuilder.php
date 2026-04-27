<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class RoadmapGoalCandidateBuilder
{
    private const array FOUNDATION_SKILLS = [
        'strict_push_up',
        'strict_pull_up',
        'strict_dip',
    ];

    /**
     * @param  list<array<string, mixed>>  $activeTracks
     * @param  list<array<string, mixed>>  $longTermTracks
     * @param  list<array<string, mixed>>  $deferredTracks
     * @param  array<string, SkillReadiness>  $readiness
     * @return array{
     *     primary: list<array<string, mixed>>,
     *     secondary: list<array<string, mixed>>,
     *     accessories: list<array<string, mixed>>,
     *     future: list<array<string, mixed>>,
     *     foundation: list<array<string, mixed>>
     * }
     */
    public static function fromInput(
        RoadmapInput $input,
        array $activeTracks,
        array $longTermTracks,
        array $deferredTracks,
        array $readiness,
    ): array {
        $activeCatalog = self::trackCatalog($activeTracks);
        $longTermCatalog = self::trackCatalog($longTermTracks);
        $deferredCatalog = self::trackCatalog($deferredTracks);
        $allCatalog = [...$longTermCatalog, ...$deferredCatalog, ...$activeCatalog];

        $foundation = [];
        $primary = [];
        $future = [];

        foreach (self::FOUNDATION_SKILLS as $skill) {
            $candidate = $readiness[$skill] ?? null;

            if ($candidate === null) {
                continue;
            }

            $track = $allCatalog[$skill] ?? null;
            $role = self::ownsFoundationSkill($skill, $input) ? 'owned_foundation' : 'foundation_bridge';
            $item = self::candidate(
                readiness: $candidate,
                role: $role,
                track: $track,
                reason: $role === 'owned_foundation'
                    ? 'Your baseline is high enough that this stays as support work instead of the main roadmap.'
                    : self::trackReason($track, 'Build this foundation first so later skill work has a reliable base.'),
            );

            $foundation[] = $item;

            if ($role === 'foundation_bridge') {
                $primary[] = $item;
            }
        }

        foreach ($readiness as $skill => $candidate) {
            if (in_array($skill, self::FOUNDATION_SKILLS, true)) {
                continue;
            }

            $activeTrack = $activeCatalog[$skill] ?? null;
            $track = $activeTrack ?? $allCatalog[$skill] ?? null;

            if ($candidate->status === 'blocked') {
                $future[] = self::candidate(
                    readiness: $candidate,
                    role: 'blocked',
                    track: $track,
                    reason: self::firstReason($candidate->hardBlockers, 'A hard gate blocks this skill right now.'),
                );

                continue;
            }

            if (self::isPrimaryCandidate($candidate, $activeTrack !== null, $input)) {
                $primary[] = self::candidate(
                    readiness: $candidate,
                    role: 'primary_candidate',
                    track: $track,
                    reason: self::trackReason($track, self::readinessReason($candidate)),
                );

                continue;
            }

            if (isset($longTermCatalog[$skill]) || isset($deferredCatalog[$skill]) || $candidate->readinessScore >= 35) {
                $future[] = self::candidate(
                    readiness: $candidate,
                    role: 'long_term',
                    track: $track,
                    reason: self::trackReason($track, self::firstReason(
                        [...$candidate->hardBlockers, ...$candidate->missingEvidence],
                        'Keep this visible for a later block while the current foundation improves.',
                    )),
                );
            }
        }

        $primary = self::dedupeAndSort($primary, $input->selectedPrimaryGoal);
        $primary = self::removeFoundationBridgePrimaryCandidates($primary, $input->selectedPrimaryGoal);
        $primary = self::moveLowFatiguePrimaryCandidates($primary, $input->selectedPrimaryGoal);
        $primaryReadiness = self::primaryReadiness($primary, $readiness);
        $secondary = [];
        $accessories = [];
        $incompatibleFuture = [];

        foreach ($readiness as $skill => $candidate) {
            if (
                self::hasSkill($primary, $skill)
                || (in_array($skill, self::FOUNDATION_SKILLS, true) && self::ownsFoundationSkill($skill, $input))
            ) {
                continue;
            }

            if ($candidate->status === 'blocked') {
                continue;
            }

            $track = $allCatalog[$skill] ?? null;
            $isActive = isset($activeCatalog[$skill]);
            $compatibility = $primaryReadiness === null
                ? ['compatible' => true, 'reason' => '', 'notes' => []]
                : GoalStressCatalog::compatibility($primaryReadiness, $candidate, $input);

            if (! $compatibility['compatible']) {
                $incompatibleFuture[] = self::candidate(
                    readiness: $candidate,
                    role: 'long_term',
                    track: $track,
                    reason: $compatibility['reason'],
                    compatibility: $compatibility,
                );

                continue;
            }

            if (GoalStressCatalog::isLowFatigueSupport($skill) && ($candidate->readinessScore >= 35 || $isActive)) {
                $accessories[] = self::candidate(
                    readiness: $candidate,
                    role: 'low_fatigue_accessory',
                    track: $track,
                    reason: self::firstReason($compatibility['notes'], self::trackReason($track, self::readinessReason($candidate))),
                    compatibility: $compatibility,
                );

                continue;
            }

            if (($candidate->status === 'ready' || $isActive) && $candidate->readinessScore >= 45) {
                $secondary[] = self::candidate(
                    readiness: $candidate,
                    role: 'secondary_candidate',
                    track: $track,
                    reason: self::trackReason($track, self::readinessReason($candidate)),
                    compatibility: $compatibility,
                );
            }
        }

        $future = self::dedupeAndSort([...$incompatibleFuture, ...$future], null);

        return [
            'primary' => array_slice($primary, 0, 8),
            'secondary' => array_slice(self::dedupeAndSort($secondary, $input->secondaryInterests[0] ?? null), 0, 6),
            'accessories' => array_slice(self::dedupeAndSort($accessories, null), 0, 6),
            'future' => array_slice($future, 0, 12),
            'foundation' => $foundation,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @return array<string, array<string, mixed>>
     */
    private static function trackCatalog(array $tracks): array
    {
        $catalog = [];

        foreach ($tracks as $track) {
            $skill = $track['skill'] ?? null;

            if (is_string($skill) && ! isset($catalog[$skill])) {
                $catalog[$skill] = $track;
            }
        }

        return $catalog;
    }

    private static function ownsFoundationSkill(string $skill, RoadmapInput $input): bool
    {
        $tests = $input->baselineTests;

        return match ($skill) {
            'strict_push_up' => self::intValue($tests['push_ups']['max_strict_reps'] ?? null) >= 20,
            'strict_pull_up' => self::intValue($tests['pull_ups']['max_strict_reps'] ?? null) >= 8,
            'strict_dip' => self::intValue($tests['dips']['max_strict_reps'] ?? null) >= 8,
            default => false,
        };
    }

    private static function isPrimaryCandidate(SkillReadiness $candidate, bool $hasActiveTrack, RoadmapInput $input): bool
    {
        if ($candidate->skill === $input->selectedPrimaryGoal) {
            return $candidate->readinessScore >= 35;
        }

        if ($candidate->status === 'ready') {
            return true;
        }

        return $hasActiveTrack && $candidate->readinessScore >= 40;
    }

    /**
     * @param  list<array<string, mixed>>  $primary
     * @return list<array<string, mixed>>
     */
    private static function removeFoundationBridgePrimaryCandidates(array $primary, ?string $selectedPrimaryGoal): array
    {
        $hasSkillCandidate = false;

        foreach ($primary as $candidate) {
            $skill = self::skill($candidate);

            if (
                ($candidate['role'] ?? '') === 'primary_candidate'
                && (
                    $skill === $selectedPrimaryGoal
                    || (
                        $skill !== null
                        && ! GoalStressCatalog::isLowFatigueSupport($skill)
                        && self::score($candidate) >= 50
                    )
                )
            ) {
                $hasSkillCandidate = true;

                break;
            }
        }

        if (! $hasSkillCandidate) {
            return $primary;
        }

        return array_values(array_filter(
            $primary,
            static function (array $candidate) use ($selectedPrimaryGoal): bool {
                return ($candidate['role'] ?? '') !== 'foundation_bridge'
                    || self::skill($candidate) === $selectedPrimaryGoal;
            },
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $primary
     * @return list<array<string, mixed>>
     */
    private static function moveLowFatiguePrimaryCandidates(array $primary, ?string $selectedPrimaryGoal): array
    {
        $hasMainStrengthCandidate = false;

        foreach ($primary as $candidate) {
            $skill = self::skill($candidate);

            if ($skill !== null && ! GoalStressCatalog::isLowFatigueSupport($skill) && $candidate['role'] === 'primary_candidate') {
                $hasMainStrengthCandidate = true;

                break;
            }
        }

        if (! $hasMainStrengthCandidate) {
            return $primary;
        }

        return array_values(array_filter(
            $primary,
            static function (array $candidate) use ($selectedPrimaryGoal): bool {
                $skill = self::skill($candidate);

                return $skill === $selectedPrimaryGoal || $skill === null || ! GoalStressCatalog::isLowFatigueSupport($skill);
            },
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $primary
     * @param  array<string, SkillReadiness>  $readiness
     */
    private static function primaryReadiness(array $primary, array $readiness): ?SkillReadiness
    {
        $skill = self::skill($primary[0] ?? []);

        return $skill === null ? null : ($readiness[$skill] ?? null);
    }

    /**
     * @param  array<string, mixed>|null  $track
     * @param  array{compatible: bool, reason: string, notes: list<string>}|null  $compatibility
     * @return array<string, mixed>
     */
    private static function candidate(
        SkillReadiness $readiness,
        string $role,
        ?array $track,
        string $reason,
        ?array $compatibility = null,
    ): array {
        return [
            'skill' => $readiness->skill,
            'label' => $readiness->label,
            'role' => $role,
            'status' => $readiness->status,
            'readiness_score' => $readiness->readinessScore,
            'confidence' => $readiness->confidence,
            'stress_class' => GoalStressCatalog::stressClass($readiness->skill),
            'stress_tags' => GoalStressCatalog::tags($readiness->skill),
            'reason' => $reason,
            'blockers' => self::uniqueStrings([...$readiness->hardBlockers, ...$readiness->safetyPenalties]),
            'unlock_conditions' => self::uniqueStrings([
                ...$readiness->hardBlockers,
                ...array_slice($readiness->missingEvidence, 0, 3),
                self::stringValue($track['next_gate'] ?? null),
            ]),
            'base_focus_areas' => self::stringList($track['base_focus_areas'] ?? []),
            'next_gate' => self::stringValue($track['next_gate'] ?? null),
            'compatible_with_primary' => $compatibility['compatible'] ?? null,
            'compatibility_reason' => $compatibility['reason'] ?? '',
        ];
    }

    private static function trackReason(?array $track, string $fallback): string
    {
        return self::stringValue($track['reason'] ?? null) ?: $fallback;
    }

    private static function readinessReason(SkillReadiness $candidate): string
    {
        if ($candidate->status === 'ready') {
            return 'Current baseline supports training this roadmap now.';
        }

        return self::firstReason(
            [...$candidate->softFactors, ...$candidate->hardBlockers],
            'Current baseline can support an introductory bridge for this roadmap.',
        );
    }

    /**
     * @param  list<string>  $values
     */
    private static function firstReason(array $values, string $fallback): string
    {
        return $values[0] ?? $fallback;
    }

    /**
     * @param  list<array<string, mixed>>  $candidates
     * @return list<array<string, mixed>>
     */
    private static function dedupeAndSort(array $candidates, ?string $preferredSkill): array
    {
        $deduped = [];

        foreach ($candidates as $candidate) {
            $skill = self::skill($candidate);

            if ($skill === null || isset($deduped[$skill])) {
                continue;
            }

            $deduped[$skill] = $candidate;
        }

        $values = array_values($deduped);

        usort(
            $values,
            static fn (array $left, array $right): int => [
                self::sortPreferred($right, $preferredSkill),
                self::roleRank($right),
                self::score($right),
            ] <=> [
                self::sortPreferred($left, $preferredSkill),
                self::roleRank($left),
                self::score($left),
            ],
        );

        return $values;
    }

    private static function sortPreferred(array $candidate, ?string $preferredSkill): int
    {
        return $preferredSkill !== null && self::skill($candidate) === $preferredSkill ? 1 : 0;
    }

    private static function roleRank(array $candidate): int
    {
        return match ($candidate['role'] ?? '') {
            'primary_candidate' => 5,
            'secondary_candidate' => 4,
            'low_fatigue_accessory' => 3,
            'foundation_bridge' => 2,
            'owned_foundation' => 1,
            default => 0,
        };
    }

    private static function score(array $candidate): int
    {
        return is_numeric($candidate['readiness_score'] ?? null) ? (int) $candidate['readiness_score'] : 0;
    }

    /**
     * @param  list<array<string, mixed>>  $candidates
     */
    private static function hasSkill(array $candidates, string $skill): bool
    {
        foreach ($candidates as $candidate) {
            if (self::skill($candidate) === $skill) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private static function skill(array $candidate): ?string
    {
        return is_string($candidate['skill'] ?? null) ? $candidate['skill'] : null;
    }

    private static function intValue(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        return is_array($value)
            ? array_values(array_filter($value, is_string(...)))
            : [];
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private static function uniqueStrings(array $values): array
    {
        return array_values(array_unique(array_filter($values, static fn (string $value): bool => $value !== '')));
    }

    private static function stringValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }
}
