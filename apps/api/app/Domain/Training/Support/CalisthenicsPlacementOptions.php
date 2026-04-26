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

    public const array PUSH_UP_PROGRESSIONS = [
        'wall_push_up',
        'incline_push_up',
        'knee_push_up',
        'strict_push_up',
        'diamond_push_up',
        'decline_push_up',
        'pseudo_planche_push_up',
        'ring_push_up',
        'archer_push_up',
        'one_arm_assisted_push_up',
        'one_arm_push_up',
    ];

    public const array ROW_PROGRESSIONS = [
        'vertical_row',
        'inverted_row',
        'horizontal_row',
        'feet_elevated_row',
        'ring_row',
        'tuck_front_lever_row',
    ];

    public const array PULL_UP_PROGRESSIONS = [
        'dead_hang',
        'scapular_pull',
        'flexed_arm_hang',
        'inverted_row',
        'band_assisted_pull_up',
        'foot_assisted_pull_up',
        'eccentric_pull_up',
        'strict_pull_up',
        'chest_to_bar_pull_up',
        'weighted_pull_up',
        'archer_pull_up',
    ];

    public const array DIP_PROGRESSIONS = [
        'support_hold',
        'box_dip',
        'bench_dip',
        'assisted_bar_dip',
        'bar_dip',
        'deep_bar_dip',
        'straight_bar_dip',
        'ring_support_hold',
        'assisted_ring_dip',
        'ring_dip',
        'weighted_dip',
    ];

    public const array SQUAT_PROGRESSIONS = [
        'box_squat',
        'air_squat',
        'reverse_lunge',
        'split_squat',
        'deep_step_up',
        'assisted_pistol',
        'shrimp_squat',
        'pistol_squat',
        'weighted_pistol',
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
        'not_started',
        'building_base',
        'assisted',
        'partial_range',
        'single_rep',
        'multiple_reps',
        'short_hold',
        'solid_hold',
        'advanced_variation',
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
        'frog_stand',
        'full_back_lever',
        'full_front_lever',
        'full_human_flag',
        'full_l_sit',
        'full_pistol_squat',
        'full_wall_hspu',
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

    /**
     * @return array<string, mixed>
     */
    public static function emptyLevelTests(): array
    {
        return [
            'push_ups' => [
                'progression' => null,
                'max_strict_reps' => null,
                'form_quality' => null,
            ],
            'rows' => [
                'progression' => null,
                'max_strict_reps' => null,
            ],
            'pull_ups' => [
                'max_strict_reps' => null,
                'progression' => null,
                'assistance' => null,
                'form_quality' => null,
            ],
            'dips' => [
                'progression' => null,
                'max_strict_reps' => null,
                'support_hold_seconds' => null,
            ],
            'squat' => [
                'max_reps' => null,
                'progression' => null,
            ],
            'hollow_hold_seconds' => null,
            'arch_hold_seconds' => null,
            'dead_hang_seconds' => null,
            'support_hold_seconds' => null,
            'wall_handstand_seconds' => null,
            'l_sit_hold_seconds' => null,
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
