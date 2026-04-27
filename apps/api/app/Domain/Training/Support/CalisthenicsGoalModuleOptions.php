<?php

declare(strict_types=1);

namespace App\Domain\Training\Support;

final class CalisthenicsGoalModuleOptions
{
    public const array MODULES = [
        'inversion',
        'pull_skill',
        'push_compression',
        'lower_body',
        'lateral_chain',
    ];

    public const array METRIC_TYPES = [
        'reps',
        'hold_seconds',
        'load',
        'quality',
    ];

    public const array QUALITY_MARKERS = [
        'unknown',
        'rough',
        'solid',
        'clean',
    ];

    public const array GOAL_MODULES = [
        'handstand' => ['inversion'],
        'handstand_push_up' => ['inversion'],
        'muscle_up' => ['pull_skill'],
        'weighted_muscle_up' => ['pull_skill'],
        'front_lever' => ['pull_skill'],
        'back_lever' => ['pull_skill'],
        'weighted_pull_up' => ['pull_skill'],
        'one_arm_pull_up' => ['pull_skill'],
        'planche' => ['push_compression'],
        'l_sit' => ['push_compression'],
        'v_sit' => ['push_compression'],
        'press_to_handstand' => ['push_compression'],
        'pistol_squat' => ['lower_body'],
        'nordic_curl' => ['lower_body'],
        'human_flag' => ['lateral_chain', 'pull_skill'],
    ];

    public const array MODULE_PROGRESSIONS = [
        'inversion' => [
            'not_tested',
            'wall_plank',
            'chest_to_wall_handstand',
            'wall_handstand_shoulder_taps',
            'freestanding_kick_up',
            'freestanding_handstand',
            'pike_push_up',
            'elevated_pike_push_up',
            'wall_hspu_negative',
            'partial_wall_hspu',
            'full_wall_hspu',
            'deep_handstand_push_up',
            'freestanding_handstand_push_up',
        ],
        'pull_skill' => [
            'not_tested',
            'explosive_pull_up',
            'chest_to_bar_pull_up',
            'high_pull_up',
            'band_assisted_muscle_up',
            'negative_muscle_up',
            'strict_muscle_up',
            'weighted_pull_up_reps',
            'tuck_front_lever',
            'advanced_tuck_front_lever',
            'one_leg_front_lever',
            'half_lay_front_lever',
            'straddle_front_lever',
            'full_front_lever',
            'skin_the_cat_prep',
            'tuck_back_lever',
            'advanced_tuck_back_lever',
            'straddle_back_lever',
            'full_back_lever',
            'archer_pull_up',
            'typewriter_pull_up',
            'assisted_one_arm_pull_up',
            'one_arm_pull_up_negative',
            'strict_one_arm_pull_up',
        ],
        'push_compression' => [
            'not_tested',
            'tuck_support',
            'tuck_l_sit',
            'one_leg_l_sit',
            'full_l_sit',
            'v_sit_prep',
            'compression_lift',
            'elevated_press_lean',
            'wall_press_negative',
            'straddle_press_negative',
            'freestanding_press_to_handstand',
            'planche_lean',
            'frog_stand',
            'tuck_planche',
            'advanced_tuck_planche',
            'straddle_planche',
            'full_planche',
        ],
        'lower_body' => [
            'not_tested',
            'split_squat',
            'box_pistol',
            'assisted_pistol',
            'pistol_negative',
            'full_pistol_squat',
            'weighted_pistol',
            'nordic_eccentric',
            'nordic_curl',
        ],
        'lateral_chain' => [
            'not_tested',
            'side_plank',
            'vertical_flag_hold',
            'tuck_human_flag',
            'straddle_human_flag',
            'full_human_flag',
        ],
    ];

    public const array DEFAULT_METRIC_TYPES = [
        'inversion' => 'hold_seconds',
        'pull_skill' => 'reps',
        'push_compression' => 'hold_seconds',
        'lower_body' => 'reps',
        'lateral_chain' => 'hold_seconds',
    ];

    /**
     * @return list<string>
     */
    public static function modulesForGoal(?string $goal): array
    {
        if ($goal === null || $goal === '') {
            return [];
        }

        return self::GOAL_MODULES[$goal] ?? [];
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyModule(string $module): array
    {
        return [
            'highest_progression' => 'not_tested',
            'metric_type' => self::DEFAULT_METRIC_TYPES[$module] ?? 'quality',
            'reps' => null,
            'hold_seconds' => null,
            'load_value' => null,
            'load_unit' => 'kg',
            'quality' => 'unknown',
            'notes' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $modules
     * @return array<string, array<string, mixed>>
     */
    public static function normalizeForGoal(array $modules, ?string $goal): array
    {
        $normalized = [];

        foreach (self::modulesForGoal($goal) as $module) {
            $normalized[$module] = self::normalizeModule(
                $module,
                is_array($modules[$module] ?? null) ? $modules[$module] : [],
            );
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $moduleData
     * @return array<string, mixed>
     */
    public static function normalizeModule(string $module, array $moduleData): array
    {
        $default = self::emptyModule($module);
        $data = array_replace($default, $moduleData);

        $highestProgression = is_string($data['highest_progression'] ?? null)
            ? $data['highest_progression']
            : $default['highest_progression'];
        $metricType = is_string($data['metric_type'] ?? null)
            ? $data['metric_type']
            : $default['metric_type'];
        $quality = is_string($data['quality'] ?? null)
            ? $data['quality']
            : $default['quality'];
        $notes = is_string($data['notes'] ?? null) ? trim($data['notes']) : null;

        return [
            'highest_progression' => $highestProgression,
            'metric_type' => $metricType,
            'reps' => self::intOrNull($data['reps'] ?? null),
            'hold_seconds' => self::intOrNull($data['hold_seconds'] ?? null),
            'load_value' => self::numberOrNull($data['load_value'] ?? null),
            'load_unit' => is_string($data['load_unit'] ?? null) ? $data['load_unit'] : $default['load_unit'],
            'quality' => $quality,
            'notes' => $notes === '' ? null : $notes,
        ];
    }

    /**
     * @param  array<string, mixed>  $modules
     * @return list<string>
     */
    public static function missingRequiredModules(array $modules, ?string $goal): array
    {
        $missing = [];
        $normalized = self::normalizeForGoal($modules, $goal);

        foreach (self::modulesForGoal($goal) as $module) {
            if (! self::isTested($normalized[$module] ?? [])) {
                $missing[] = "goal_module_{$module}";
            }
        }

        return $missing;
    }

    /**
     * @param  array<string, mixed>  $module
     */
    public static function isTested(array $module): bool
    {
        $progression = $module['highest_progression'] ?? 'not_tested';
        $metricType = $module['metric_type'] ?? null;

        if (! is_string($progression) || $progression === '' || $progression === 'not_tested' || ! is_string($metricType)) {
            return false;
        }

        return match ($metricType) {
            'reps' => is_int($module['reps'] ?? null) && $module['reps'] > 0,
            'hold_seconds' => is_int($module['hold_seconds'] ?? null) && $module['hold_seconds'] > 0,
            'load' => is_numeric($module['load_value'] ?? null) && (float) $module['load_value'] > 0,
            'quality' => is_string($module['quality'] ?? null) && $module['quality'] !== 'unknown',
            default => false,
        };
    }

    public static function progressionBelongsToModule(string $module, string $progression): bool
    {
        return in_array($progression, self::MODULE_PROGRESSIONS[$module] ?? [], true);
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function numberOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
