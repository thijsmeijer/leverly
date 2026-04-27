<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class RoadmapStressBudgetFactory
{
    public static function empty(): RoadmapStressBudget
    {
        return new RoadmapStressBudget(
            weeklyBudget: array_fill_keys(RoadmapStressBudget::AXES, 0),
            perDaySoftCap: array_fill_keys(RoadmapStressBudget::AXES, 0),
            perDayHardCap: array_fill_keys(RoadmapStressBudget::AXES, 0),
            recoveryRules: [],
            highStressDevelopmentLanes: 0,
            spacingCapacity: 0,
            timeCapacityMinutesPerWeek: 0,
            painReducedAxes: [],
        );
    }

    public static function fromInput(RoadmapInput $input): RoadmapStressBudget
    {
        $sessions = max(1, min(6, self::intOrNull($input->trainingContext['weekly_session_goal'] ?? null) ?? 3));
        $sessionMinutes = max(30, min(120, self::intOrNull($input->trainingContext['preferred_session_minutes'] ?? null) ?? 45));
        $trainingAge = self::intOrNull($input->trainingContext['training_age_months'] ?? null) ?? 0;
        $recoveryScore = self::recoveryScore($input);
        $weekly = self::baseWeeklyBudget($sessions, $trainingAge);
        $painReducedAxes = self::painReducedAxes($input);
        $highStressDevelopmentLanes = self::highStressDevelopmentLanes($sessions, $trainingAge, $recoveryScore);

        if ($painReducedAxes !== []) {
            foreach ($painReducedAxes as $axis) {
                $weekly[$axis] = max(1, ($weekly[$axis] ?? 3) - 2);
            }

            $highStressDevelopmentLanes = max(0, $highStressDevelopmentLanes - 1);
        }

        [$softCaps, $hardCaps] = self::dailyCaps($weekly, $painReducedAxes);

        return new RoadmapStressBudget(
            weeklyBudget: $weekly,
            perDaySoftCap: $softCaps,
            perDayHardCap: $hardCaps,
            recoveryRules: self::recoveryRules($painReducedAxes),
            highStressDevelopmentLanes: $highStressDevelopmentLanes,
            spacingCapacity: $sessions,
            timeCapacityMinutesPerWeek: $sessions * $sessionMinutes,
            painReducedAxes: $painReducedAxes,
        );
    }

    /**
     * @return array<string, int>
     */
    private static function baseWeeklyBudget(int $sessions, int $trainingAgeMonths): array
    {
        $distributionBonus = max(0, $sessions - 3);
        $experienceBonus = $trainingAgeMonths >= 24 ? 1 : 0;
        $budget = [];

        foreach (RoadmapStressBudget::AXES as $axis) {
            $base = match ($axis) {
                'wrist_extension', 'elbow_pull_tendon', 'elbow_push_tendon', 'shoulder_extension', 'shoulder_flexion', 'ankle_knee' => 5,
                'systemic_fatigue' => 7,
                default => 6,
            };

            $budget[$axis] = $base + $distributionBonus + $experienceBonus;
        }

        return $budget;
    }

    /**
     * @param  array<string, int>  $weekly
     * @param  list<string>  $painReducedAxes
     * @return array{0: array<string, int>, 1: array<string, int>}
     */
    private static function dailyCaps(array $weekly, array $painReducedAxes): array
    {
        $soft = [];
        $hard = [];

        foreach (RoadmapStressBudget::AXES as $axis) {
            $weeklyCap = $weekly[$axis] ?? 0;
            $soft[$axis] = max(1, min(5, (int) ceil($weeklyCap / 2)));
            $hard[$axis] = $soft[$axis] + (in_array($axis, $painReducedAxes, true) ? 1 : 2);
        }

        return [$soft, $hard];
    }

    private static function highStressDevelopmentLanes(int $sessions, int $trainingAgeMonths, int $recoveryScore): int
    {
        if ($sessions <= 3) {
            return 1;
        }

        if ($sessions >= 6 && $trainingAgeMonths >= 36 && $recoveryScore >= 75) {
            return 3;
        }

        if ($sessions >= 5 && $trainingAgeMonths >= 12) {
            return 2;
        }

        return 1;
    }

    /**
     * @return list<string>
     */
    private static function painReducedAxes(RoadmapInput $input): array
    {
        $painLevel = self::intOrNull($input->painFlags['level'] ?? null);
        $areas = self::stringList($input->painFlags['areas'] ?? []);

        if (($painLevel ?? 0) < 4 || $areas === []) {
            return [];
        }

        $axes = [];
        foreach ($areas as $area) {
            $axes = [...$axes, ...match ($area) {
                'elbow' => ['elbow_pull_tendon', 'elbow_push_tendon', 'bent_arm_pull', 'bent_arm_push'],
                'wrist' => ['wrist_extension', 'straight_arm_push'],
                'shoulder' => ['shoulder_extension', 'shoulder_flexion', 'overhead_push', 'straight_arm_pull', 'straight_arm_push'],
                'knee', 'ankle' => ['ankle_knee', 'lower_body'],
                'low_back', 'back' => ['trunk_rigidity', 'systemic_fatigue'],
                default => ['systemic_fatigue'],
            }];
        }

        return array_values(array_intersect(RoadmapStressBudget::AXES, array_unique($axes)));
    }

    /**
     * @param  list<string>  $painReducedAxes
     * @return list<array<string, mixed>>
     */
    private static function recoveryRules(array $painReducedAxes): array
    {
        $rules = [];

        foreach (['wrist_extension', 'elbow_pull_tendon', 'elbow_push_tendon', 'shoulder_extension', 'shoulder_flexion', 'straight_arm_pull', 'straight_arm_push', 'overhead_push'] as $axis) {
            $rules[] = [
                'axis' => $axis,
                'min_hours_after_high' => in_array($axis, $painReducedAxes, true) ? 72 : 48,
                'min_hours_after_max' => in_array($axis, $painReducedAxes, true) ? 96 : 72,
                'reason' => in_array($axis, $painReducedAxes, true)
                    ? 'Relevant pain reduces capacity and increases recovery spacing.'
                    : 'High stress on this axis should be spaced before repeating.',
            ];
        }

        return $rules;
    }

    private static function recoveryScore(RoadmapInput $input): int
    {
        $readiness = self::intOrNull($input->trainingContext['readiness_rating'] ?? null);
        $sleep = self::intOrNull($input->trainingContext['sleep_quality'] ?? null);
        $soreness = self::intOrNull($input->trainingContext['soreness_level'] ?? null);
        $score = 70;

        if ($readiness !== null) {
            $score += ($readiness - 3) * 8;
        }

        if ($sleep !== null) {
            $score += ($sleep - 3) * 6;
        }

        if ($soreness !== null) {
            $score -= max(0, $soreness - 2) * 6;
        }

        return max(0, min(100, $score));
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, is_string(...)));
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
