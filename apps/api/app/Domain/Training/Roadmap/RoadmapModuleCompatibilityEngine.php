<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class RoadmapModuleCompatibilityEngine
{
    private const array TIER_SCORE = [
        'low' => 1,
        'medium' => 2,
        'high' => 3,
        'max' => 4,
    ];

    private const array RED_ZONE_PAIRS = [
        'front_lever|one_arm_pull_up' => 'Front lever development and one-arm pull-up development both stress elbow-pull tissues hard.',
        'front_lever|weighted_pull_up' => 'Front lever and heavy weighted pull-up work stack high pulling stress.',
        'handstand_push_up|planche' => 'Planche and HSPU development overlap wrist, shoulder, and pushing stress.',
        'muscle_up|one_arm_pull_up' => 'Muscle-up and one-arm pull-up development stack vertical pull and elbow tendon stress.',
        'human_flag|one_arm_pull_up' => 'Human flag and one-arm pull-up development stack shoulder and pulling stress.',
        'front_lever|human_flag' => 'Front lever and human flag development stack straight-arm pulling and trunk stress.',
        'planche|press_to_handstand' => 'Planche and press handstand development stack wrist, compression, and straight-arm push stress.',
    ];

    /**
     * @param  array<string, mixed>  $primary
     * @param  array<string, mixed>  $secondary
     */
    public static function compare(array $primary, array $secondary, RoadmapStressBudget $budget): RoadmapModuleCompatibility
    {
        $primaryId = self::stringValue($primary['module_id'] ?? null, 'primary');
        $secondaryId = self::stringValue($secondary['module_id'] ?? null, 'secondary');
        $primarySkill = self::stringValue($primary['skill_track_id'] ?? null, '');
        $secondarySkill = self::stringValue($secondary['skill_track_id'] ?? null, '');
        $primaryStress = self::canonicalStressVector($primary);
        $secondaryStress = self::canonicalStressVector($secondary);
        $overlap = self::overlappingAxes($primaryStress, $secondaryStress);
        $primaryTier = self::tierScore($primary);
        $secondaryTier = self::tierScore($secondary);
        $pairReason = self::redZoneReason($primarySkill, $secondarySkill);

        if ($pairReason !== null) {
            return self::redZoneCompatibility(
                primaryId: $primaryId,
                secondaryId: $secondaryId,
                primary: $primary,
                secondary: $secondary,
                primaryTier: $primaryTier,
                secondaryTier: $secondaryTier,
                overlap: $overlap,
                reason: $pairReason,
            );
        }

        $painWarnings = self::painWarnings($primaryStress, $secondaryStress, $budget, max($primaryTier, $secondaryTier));
        if ($painWarnings !== [] && max($primaryTier, $secondaryTier) >= 3) {
            return new RoadmapModuleCompatibility(
                primaryModuleId: $primaryId,
                secondaryModuleId: $secondaryId,
                state: 'orange',
                compatible: true,
                overlappingAxes: $overlap,
                reasons: ['Relevant pain reduces the available stress budget for this pairing.'],
                warnings: $painWarnings,
                suggestedAdjustment: [
                    'action' => 'reduce_high_stress_lane',
                    'target_module_id' => $secondaryId,
                    'limit' => 'Keep only one high-stress module on the painful axis.',
                ],
            );
        }

        $capState = self::capState($primaryStress, $secondaryStress, $budget, $overlap);
        if ($capState === 'red') {
            return new RoadmapModuleCompatibility(
                primaryModuleId: $primaryId,
                secondaryModuleId: $secondaryId,
                state: 'red',
                compatible: false,
                overlappingAxes: $overlap,
                reasons: ['Combined module stress would exceed a hard daily cap.'],
                warnings: $painWarnings,
            );
        }

        if ($capState === 'orange' || ($primaryTier >= 3 && $secondaryTier >= 3 && count($overlap) >= 2)) {
            return new RoadmapModuleCompatibility(
                primaryModuleId: $primaryId,
                secondaryModuleId: $secondaryId,
                state: 'orange',
                compatible: true,
                overlappingAxes: $overlap,
                reasons: ['Both modules are high enough to need spacing or a role downgrade.'],
                warnings: $painWarnings,
                suggestedAdjustment: [
                    'action' => 'downgrade_secondary_to_technical_practice',
                    'target_module_id' => $secondaryId,
                    'limit' => 'Keep the secondary module below high intensity in this phase.',
                ],
            );
        }

        if ($capState === 'yellow' && min($primaryTier, $secondaryTier) <= 1) {
            return new RoadmapModuleCompatibility(
                primaryModuleId: $primaryId,
                secondaryModuleId: $secondaryId,
                state: 'green',
                compatible: true,
                overlappingAxes: $overlap,
                reasons: ['Low-intensity technical overlap can coexist with normal spacing.'],
                warnings: $painWarnings,
            );
        }

        if ($capState === 'yellow' || ($overlap !== [] && max($primaryTier, $secondaryTier) >= 2)) {
            return new RoadmapModuleCompatibility(
                primaryModuleId: $primaryId,
                secondaryModuleId: $secondaryId,
                state: 'yellow',
                compatible: true,
                overlappingAxes: $overlap,
                reasons: ['Modules can coexist with dose caps and spacing.'],
                warnings: $painWarnings,
                suggestedAdjustment: [
                    'action' => 'cap_secondary_exposure',
                    'target_module_id' => $secondaryId,
                    'limit' => 'Keep secondary exposure at the low end of its target range.',
                ],
            );
        }

        return new RoadmapModuleCompatibility(
            primaryModuleId: $primaryId,
            secondaryModuleId: $secondaryId,
            state: 'green',
            compatible: true,
            overlappingAxes: $overlap,
            reasons: [$overlap === [] ? 'Modules use separate primary stress axes.' : 'Overlap is low enough for normal spacing.'],
            warnings: $painWarnings,
        );
    }

    /**
     * @param  array<string, mixed>  $primary
     * @param  array<string, mixed>  $secondary
     * @param  list<string>  $overlap
     */
    private static function redZoneCompatibility(
        string $primaryId,
        string $secondaryId,
        array $primary,
        array $secondary,
        int $primaryTier,
        int $secondaryTier,
        array $overlap,
        string $reason,
    ): RoadmapModuleCompatibility {
        $hasLowRole = min($primaryTier, $secondaryTier) <= 1
            || in_array(self::stringValue($primary['purpose'] ?? null, ''), ['technical_practice', 'maintenance'], true)
            || in_array(self::stringValue($secondary['purpose'] ?? null, ''), ['technical_practice', 'maintenance'], true);

        if ($hasLowRole) {
            return new RoadmapModuleCompatibility(
                primaryModuleId: $primaryId,
                secondaryModuleId: $secondaryId,
                state: 'yellow',
                compatible: true,
                overlappingAxes: $overlap,
                reasons: [$reason],
                warnings: ['Keep the lower-priority module light and separated from the high-stress exposure.'],
                suggestedAdjustment: [
                    'action' => 'cap_secondary_exposure',
                    'target_module_id' => $secondaryId,
                    'limit' => 'Use low or medium practice only.',
                ],
            );
        }

        if ($primaryTier >= 3 && $secondaryTier >= 3 && in_array(self::pairKey(
            self::stringValue($primary['skill_track_id'] ?? null, ''),
            self::stringValue($secondary['skill_track_id'] ?? null, ''),
        ), ['front_lever|one_arm_pull_up', 'muscle_up|one_arm_pull_up', 'front_lever|human_flag', 'human_flag|one_arm_pull_up'], true)) {
            return new RoadmapModuleCompatibility(
                primaryModuleId: $primaryId,
                secondaryModuleId: $secondaryId,
                state: 'red',
                compatible: false,
                overlappingAxes: $overlap,
                reasons: [$reason],
                warnings: ['Move one high-stress pulling lane to a future phase.'],
            );
        }

        return new RoadmapModuleCompatibility(
            primaryModuleId: $primaryId,
            secondaryModuleId: $secondaryId,
            state: 'orange',
            compatible: true,
            overlappingAxes: $overlap,
            reasons: [$reason],
            warnings: ['Only one of these modules should keep high-intensity development status.'],
            suggestedAdjustment: [
                'action' => 'downgrade_secondary_to_technical_practice',
                'target_module_id' => $secondaryId,
                'limit' => 'Keep the secondary module low or medium and schedule it away from the primary.',
            ],
        );
    }

    /**
     * @param  array<string, int>  $primary
     * @param  array<string, int>  $secondary
     */
    private static function capState(array $primary, array $secondary, RoadmapStressBudget $budget, array $overlap): string
    {
        $state = 'green';

        foreach ($overlap as $axis) {
            $load = ($primary[$axis] ?? 0) + ($secondary[$axis] ?? 0);

            if ($load > ($budget->perDayHardCap[$axis] ?? 99)) {
                return 'red';
            }

            if ($load > ($budget->perDaySoftCap[$axis] ?? 99)) {
                $state = 'orange';
            } elseif ($load >= max(1, ($budget->perDaySoftCap[$axis] ?? 99) - 1)) {
                $state = $state === 'orange' ? 'orange' : 'yellow';
            }
        }

        return $state;
    }

    /**
     * @param  array<string, int>  $primary
     * @param  array<string, int>  $secondary
     * @return list<string>
     */
    private static function painWarnings(array $primary, array $secondary, RoadmapStressBudget $budget, int $highestTier): array
    {
        if ($highestTier < 3) {
            return [];
        }

        $touchedAxes = [];

        foreach (RoadmapStressBudget::AXES as $axis) {
            if (($primary[$axis] ?? 0) + ($secondary[$axis] ?? 0) > 0) {
                $touchedAxes[] = $axis;
            }
        }

        $touchedPainAxes = array_values(array_intersect($budget->painReducedAxes, $touchedAxes));

        return array_map(
            static fn (string $axis): string => "Pain has reduced {$axis} capacity.",
            $touchedPainAxes,
        );
    }

    /**
     * @param  array<string, mixed>  $module
     * @return array<string, int>
     */
    private static function canonicalStressVector(array $module): array
    {
        $source = is_array($module['stress_vector'] ?? null) ? $module['stress_vector'] : [];
        $canonical = array_fill_keys(RoadmapStressBudget::AXES, 0);

        foreach ($source as $axis => $load) {
            $canonicalAxis = self::canonicalAxis((string) $axis);
            if ($canonicalAxis === null) {
                continue;
            }

            $canonical[$canonicalAxis] = max($canonical[$canonicalAxis], max(0, min(10, (int) $load)));
        }

        return $canonical;
    }

    private static function canonicalAxis(string $axis): ?string
    {
        return match ($axis) {
            'vertical_pull', 'grip_hang' => 'bent_arm_pull',
            'horizontal_pull', 'scapular_control' => 'straight_arm_pull',
            'lateral_chain' => 'trunk_rigidity',
            'ankle/knee' => 'ankle_knee',
            default => in_array($axis, RoadmapStressBudget::AXES, true) ? $axis : null,
        };
    }

    /**
     * @param  array<string, int>  $primary
     * @param  array<string, int>  $secondary
     * @return list<string>
     */
    private static function overlappingAxes(array $primary, array $secondary): array
    {
        return array_values(array_filter(
            RoadmapStressBudget::AXES,
            static fn (string $axis): bool => ($primary[$axis] ?? 0) > 0 && ($secondary[$axis] ?? 0) > 0,
        ));
    }

    /**
     * @param  array<string, mixed>  $module
     */
    private static function tierScore(array $module): int
    {
        return self::TIER_SCORE[self::stringValue($module['intensity_tier'] ?? null, 'low')] ?? 1;
    }

    private static function redZoneReason(string $left, string $right): ?string
    {
        return self::RED_ZONE_PAIRS[self::pairKey($left, $right)] ?? null;
    }

    private static function pairKey(string $left, string $right): string
    {
        $pair = [$left, $right];
        sort($pair);

        return implode('|', $pair);
    }

    private static function stringValue(mixed $value, string $fallback): string
    {
        return is_string($value) && $value !== '' ? $value : $fallback;
    }
}
