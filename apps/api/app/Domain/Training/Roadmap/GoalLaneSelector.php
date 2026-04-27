<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class GoalLaneSelector
{
    private const array STRESS_TAGS = [
        'strict_push_up' => ['push', 'trunk'],
        'one_arm_push_up' => ['push', 'straight_arm_push', 'wrist_extension'],
        'strict_pull_up' => ['pull', 'vertical_pull', 'elbow_flexor_load'],
        'weighted_pull_up' => ['pull', 'vertical_pull', 'elbow_flexor_load'],
        'strict_dip' => ['push', 'dip', 'shoulder_extension'],
        'ring_dip' => ['push', 'dip', 'shoulder_extension', 'stability'],
        'weighted_dip' => ['push', 'dip', 'shoulder_extension'],
        'muscle_up' => ['pull', 'vertical_pull', 'push', 'dip', 'elbow_flexor_load'],
        'weighted_muscle_up' => ['pull', 'vertical_pull', 'push', 'dip', 'elbow_flexor_load'],
        'l_sit' => ['compression', 'support'],
        'v_sit' => ['compression', 'support'],
        'handstand' => ['overhead', 'wrist_extension', 'line_balance'],
        'handstand_push_up' => ['push', 'overhead', 'wrist_extension', 'straight_arm_push'],
        'press_to_handstand' => ['compression', 'overhead', 'wrist_extension'],
        'front_lever' => ['pull', 'straight_arm_pull', 'vertical_pull', 'elbow_flexor_load'],
        'back_lever' => ['straight_arm_pull', 'shoulder_extension', 'elbow_tolerance'],
        'planche' => ['push', 'straight_arm_push', 'wrist_extension', 'overhead'],
        'pistol_squat' => ['lower_body'],
        'nordic_curl' => ['lower_body', 'posterior_chain'],
        'one_arm_pull_up' => ['pull', 'vertical_pull', 'elbow_flexor_load'],
        'human_flag' => ['lateral_chain', 'pull', 'push', 'straight_arm_pull'],
    ];

    private const array HIGH_STRESS = [
        'one_arm_push_up',
        'weighted_pull_up',
        'weighted_dip',
        'muscle_up',
        'weighted_muscle_up',
        'handstand_push_up',
        'front_lever',
        'back_lever',
        'planche',
        'nordic_curl',
        'one_arm_pull_up',
        'human_flag',
    ];

    private const array LOW_FATIGUE_SUPPORT = [
        'handstand',
        'l_sit',
        'v_sit',
        'pistol_squat',
    ];

    private const array PAIR_BLOCKS = [
        'handstand_push_up|planche' => 'Too much overlapping straight-arm push, overhead, and wrist-extension stress with the primary lane.',
        'front_lever|one_arm_pull_up' => 'Too much overlapping straight-arm pull, vertical pull, and elbow-flexor stress with the primary lane.',
        'muscle_up|one_arm_pull_up' => 'Too much overlapping vertical pull and elbow-flexor stress with the primary lane.',
    ];

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

            $compatibility = self::compatibility($primary, $candidate, $input);

            if ($candidate->status === 'blocked') {
                $deferred[] = self::deferredGoal($candidate, self::firstReason($candidate->hardBlockers, 'Safety gate blocks this lane for now.'));

                continue;
            }

            if (! $compatibility['compatible']) {
                $deferred[] = self::deferredGoal($candidate, $compatibility['reason']);

                continue;
            }

            if ($candidate->status === 'ready' || in_array($candidate->skill, self::LOW_FATIGUE_SUPPORT, true)) {
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

        foreach (self::LOW_FATIGUE_SUPPORT as $skill) {
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
     * @return array{compatible: bool, reason: string, notes: list<string>}
     */
    private static function compatibility(SkillReadiness $primary, SkillReadiness $candidate, RoadmapInput $input): array
    {
        $pairReason = self::pairBlockReason($primary->skill, $candidate->skill);
        if ($pairReason !== null) {
            return ['compatible' => false, 'reason' => $pairReason, 'notes' => []];
        }

        $weeklySessions = self::intOrNull($input->trainingContext['weekly_session_goal'] ?? null) ?? 3;
        $primaryHighStress = in_array($primary->skill, self::HIGH_STRESS, true);
        $candidateHighStress = in_array($candidate->skill, self::HIGH_STRESS, true);

        if ($primaryHighStress && $candidateHighStress && $weeklySessions <= 4) {
            return [
                'compatible' => false,
                'reason' => 'Recovery budget is too tight for two high-stress advanced lanes.',
                'notes' => [],
            ];
        }

        $overlap = array_values(array_intersect(self::tags($primary->skill), self::tags($candidate->skill)));
        if ($primaryHighStress && $candidateHighStress && count($overlap) >= 2) {
            return [
                'compatible' => false,
                'reason' => 'Stress overlap is too high with the primary lane.',
                'notes' => [],
            ];
        }

        $notes = [];
        if ($candidate->skill === 'handstand') {
            $notes[] = 'Handstand line fits as a lower-fatigue secondary lane.';
        } elseif (in_array($candidate->skill, self::LOW_FATIGUE_SUPPORT, true)) {
            $notes[] = "{$candidate->label} fits as lower-fatigue supporting work.";
        }

        return ['compatible' => true, 'reason' => '', 'notes' => $notes];
    }

    private static function pairBlockReason(string $left, string $right): ?string
    {
        $pair = [$left, $right];
        sort($pair);

        return self::PAIR_BLOCKS[implode('|', $pair)] ?? null;
    }

    /**
     * @return list<string>
     */
    private static function tags(string $skill): array
    {
        return self::STRESS_TAGS[$skill] ?? [];
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

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
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
