<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class GoalStressCatalog
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

    /**
     * @return list<string>
     */
    public static function tags(string $skill): array
    {
        return self::STRESS_TAGS[$skill] ?? [];
    }

    /**
     * @return list<string>
     */
    public static function lowFatigueSkills(): array
    {
        return self::LOW_FATIGUE_SUPPORT;
    }

    public static function isHighStress(string $skill): bool
    {
        return in_array($skill, self::HIGH_STRESS, true);
    }

    public static function isLowFatigueSupport(string $skill): bool
    {
        return in_array($skill, self::LOW_FATIGUE_SUPPORT, true);
    }

    public static function stressClass(string $skill): string
    {
        if (self::isHighStress($skill)) {
            return 'high';
        }

        if (self::isLowFatigueSupport($skill)) {
            return 'low_fatigue';
        }

        if (in_array($skill, ['strict_push_up', 'strict_pull_up', 'strict_dip'], true)) {
            return 'foundation';
        }

        return 'moderate';
    }

    /**
     * @return array{compatible: bool, reason: string, notes: list<string>}
     */
    public static function compatibility(SkillReadiness $primary, SkillReadiness $candidate, RoadmapInput $input): array
    {
        $pairReason = self::pairBlockReason($primary->skill, $candidate->skill);
        if ($pairReason !== null) {
            return ['compatible' => false, 'reason' => $pairReason, 'notes' => []];
        }

        $weeklySessions = self::intOrNull($input->trainingContext['weekly_session_goal'] ?? null) ?? 3;
        $primaryHighStress = self::isHighStress($primary->skill);
        $candidateHighStress = self::isHighStress($candidate->skill);

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
        } elseif (self::isLowFatigueSupport($candidate->skill)) {
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

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
