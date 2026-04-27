<?php

declare(strict_types=1);

namespace App\Domain\Training\Support;

final class CalisthenicsPlacementOptions
{
    public const array TARGET_SKILLS = [
        'strict_push_up',
        'one_arm_push_up',
        'strict_pull_up',
        'weighted_pull_up',
        'strict_dip',
        'ring_dip',
        'weighted_dip',
        'muscle_up',
        'weighted_muscle_up',
        'l_sit',
        'v_sit',
        'handstand',
        'handstand_push_up',
        'press_to_handstand',
        'front_lever',
        'back_lever',
        'planche',
        'pistol_squat',
        'nordic_curl',
        'one_arm_pull_up',
        'human_flag',
    ];

    public const array BASE_FOCUS_AREAS = [
        'push_capacity',
        'pull_capacity',
        'dip_support',
        'row_volume',
        'leg_strength',
        'core_bodyline',
        'compression',
        'handstand_line',
        'straight_arm_tolerance',
        'mobility_positions',
        'weighted_strength',
        'conditioning_base',
    ];

    public const array SKILL_STATUS_KEYS = [
        'dip',
        'ring_dip',
        'muscle_up',
        'l_sit',
        'handstand',
        'handstand_push_up',
        'front_lever',
        'back_lever',
        'planche',
        'pistol_squat',
        'nordic_curl',
        'one_arm_pull_up',
        'human_flag',
        'press_to_handstand',
    ];

    public const array SKILL_STATUSES = [
        'not_tested',
        'archer_pull_up',
        'assisted_one_arm_pull_up',
        'advanced_tuck_back_lever',
        'advanced_tuck_front_lever',
        'advanced_tuck_planche',
        'band_assisted_muscle_up',
        'box_pistol',
        'chest_to_bar_pull_up',
        'chest_to_wall_handstand',
        'compression_lift',
        'elevated_pike_push_up',
        'elevated_press_lean',
        'explosive_pull_up',
        'freestanding_handstand',
        'freestanding_kick_up',
        'freestanding_press_to_handstand',
        'freestanding_handstand_push_up',
        'frog_stand',
        'full_back_lever',
        'full_front_lever',
        'full_planche',
        'full_human_flag',
        'full_l_sit',
        'full_pistol_squat',
        'full_wall_hspu',
        'deep_handstand_push_up',
        'half_lay_front_lever',
        'high_pull_up',
        'negative_muscle_up',
        'one_arm_pull_up_negative',
        'one_leg_front_lever',
        'one_leg_l_sit',
        'partial_wall_hspu',
        'pike_push_up',
        'pistol_negative',
        'planche_lean',
        'side_plank',
        'skin_the_cat_prep',
        'split_squat',
        'straddle_back_lever',
        'straddle_front_lever',
        'straddle_human_flag',
        'straddle_planche',
        'straddle_press_negative',
        'strict_muscle_up',
        'strict_one_arm_pull_up',
        'tuck_back_lever',
        'tuck_front_lever',
        'tuck_human_flag',
        'tuck_l_sit',
        'tuck_planche',
        'tuck_support',
        'typewriter_pull_up',
        'v_sit_prep',
        'vertical_flag_hold',
        'wall_handstand_shoulder_taps',
        'wall_hspu_negative',
        'wall_plank',
        'wall_press_negative',
    ];

    public const array MOBILITY_CHECK_KEYS = [
        'wrist_extension',
        'shoulder_flexion',
        'shoulder_extension',
        'ankle_dorsiflexion',
        'pancake_compression',
    ];

    public const array MOBILITY_STATUSES = [
        'not_tested',
        'clear',
        'limited',
        'blocked',
        'painful',
    ];

    public const array WEIGHTED_EXPERIENCE_LEVELS = [
        'none',
        'curious',
        'repetition_work',
        'strength_cycles',
        'competition_style',
    ];

    public const array WEIGHTED_MOVEMENTS = [
        'weighted_pull_up',
        'weighted_dip',
        'weighted_muscle_up',
        'weighted_pistol',
    ];

    public const array LOAD_UNITS = ['kg', 'lb'];

    public const array ROW_VARIANTS = [
        'bodyweight_row',
        'ring_row',
        'low_bar_row',
        'suspension_row',
    ];

    public const array PULL_UP_FALLBACK_VARIANTS = [
        'none',
        'passive_hang',
        'active_hang',
        'eccentric',
        'assisted',
    ];

    public const array DIP_FALLBACK_VARIANTS = [
        'none',
        'top_support',
        'assisted',
    ];

    public const array LOWER_BODY_TEST_VARIANTS = [
        'barbell_squat',
        'bodyweight_squat',
        'split_squat',
        'pistol_progression',
        'step_down',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function emptyLevelTests(): array
    {
        return [
            'push_ups' => [
                'max_strict_reps' => null,
            ],
            'pull_ups' => [
                'max_strict_reps' => null,
                'fallback_variant' => 'none',
                'fallback_reps' => null,
                'fallback_seconds' => null,
            ],
            'dips' => [
                'max_strict_reps' => null,
                'fallback_variant' => 'none',
                'fallback_reps' => null,
                'fallback_seconds' => null,
            ],
            'squat' => [
                'barbell_load_value' => null,
                'barbell_reps' => null,
            ],
            'rows' => [
                'variant' => 'bodyweight_row',
                'max_reps' => null,
            ],
            'lower_body' => [
                'variant' => 'bodyweight_squat',
                'load_value' => null,
                'load_unit' => 'kg',
                'reps' => null,
            ],
            'hollow_hold_seconds' => null,
            'passive_hang_seconds' => null,
            'top_support_hold_seconds' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $tests
     * @return array<string, mixed>
     */
    public static function normalizeLevelTests(array $tests): array
    {
        $pushUps = is_array($tests['push_ups'] ?? null) ? $tests['push_ups'] : [];
        $pullUps = is_array($tests['pull_ups'] ?? null) ? $tests['pull_ups'] : [];
        $dips = is_array($tests['dips'] ?? null) ? $tests['dips'] : [];
        $squat = is_array($tests['squat'] ?? null) ? $tests['squat'] : [];

        return [
            'push_ups' => [
                'max_strict_reps' => $pushUps['max_strict_reps'] ?? null,
            ],
            'pull_ups' => [
                'max_strict_reps' => $pullUps['max_strict_reps'] ?? null,
                'fallback_variant' => $pullUps['fallback_variant'] ?? 'none',
                'fallback_reps' => $pullUps['fallback_reps'] ?? null,
                'fallback_seconds' => $pullUps['fallback_seconds'] ?? null,
            ],
            'dips' => [
                'max_strict_reps' => $dips['max_strict_reps'] ?? null,
                'fallback_variant' => $dips['fallback_variant'] ?? 'none',
                'fallback_reps' => $dips['fallback_reps'] ?? null,
                'fallback_seconds' => $dips['fallback_seconds'] ?? null,
            ],
            'squat' => [
                'barbell_load_value' => $squat['barbell_load_value'] ?? null,
                'barbell_reps' => $squat['barbell_reps'] ?? null,
            ],
            'rows' => [
                'variant' => is_array($tests['rows'] ?? null) ? ($tests['rows']['variant'] ?? 'bodyweight_row') : 'bodyweight_row',
                'max_reps' => is_array($tests['rows'] ?? null) ? ($tests['rows']['max_reps'] ?? null) : null,
            ],
            'lower_body' => [
                'variant' => is_array($tests['lower_body'] ?? null) ? ($tests['lower_body']['variant'] ?? 'bodyweight_squat') : 'bodyweight_squat',
                'load_value' => is_array($tests['lower_body'] ?? null) ? ($tests['lower_body']['load_value'] ?? null) : null,
                'load_unit' => is_array($tests['lower_body'] ?? null) ? ($tests['lower_body']['load_unit'] ?? 'kg') : 'kg',
                'reps' => is_array($tests['lower_body'] ?? null) ? ($tests['lower_body']['reps'] ?? null) : null,
            ],
            'hollow_hold_seconds' => $tests['hollow_hold_seconds'] ?? null,
            'passive_hang_seconds' => $tests['passive_hang_seconds'] ?? null,
            'top_support_hold_seconds' => $tests['top_support_hold_seconds'] ?? null,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function emptyMobilityChecks(): array
    {
        return array_fill_keys(self::MOBILITY_CHECK_KEYS, 'not_tested');
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyWeightedBaselines(): array
    {
        return [
            'experience' => 'none',
            'unit' => 'kg',
            'movements' => [],
        ];
    }
}
