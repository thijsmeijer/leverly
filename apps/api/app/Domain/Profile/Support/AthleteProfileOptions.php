<?php

declare(strict_types=1);

namespace App\Domain\Profile\Support;

use App\Models\User;

final class AthleteProfileOptions
{
    public const array UNIT_SYSTEMS = ['metric', 'imperial'];

    public const array BODYWEIGHT_UNITS = ['kg', 'lb'];

    public const array EXPERIENCE_LEVELS = ['new', 'beginner', 'intermediate', 'advanced', 'elite'];

    public const array GOALS = [
        'strength',
        'hypertrophy',
        'skill',
        'endurance',
        'general_fitness',
        'mobility',
        'conditioning',
    ];

    public const array COMPATIBLE_SECONDARY_GOALS = [
        'conditioning' => ['endurance', 'hypertrophy', 'general_fitness'],
        'endurance' => ['conditioning', 'general_fitness', 'mobility'],
        'general_fitness' => ['strength', 'endurance', 'mobility'],
        'hypertrophy' => ['strength', 'conditioning', 'mobility'],
        'mobility' => ['skill', 'strength', 'general_fitness'],
        'skill' => ['strength', 'mobility', 'endurance'],
        'strength' => ['skill', 'hypertrophy', 'mobility'],
    ];

    public const array EQUIPMENT = [
        'pull_up_bar',
        'dip_bars',
        'parallel_bars',
        'parallettes',
        'rings',
        'resistance_band',
        'box_bench',
        'weight_vest',
        'dip_belt',
        'suspension_trainer',
        'ab_wheel',
    ];

    public const array TRAINING_LOCATIONS = ['home', 'gym', 'park', 'travel', 'other'];

    public const array TRAINING_DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    public const array TRAINING_TIMES = ['morning', 'midday', 'evening', 'flexible'];

    public const array LIMITATION_AREAS = [
        'wrist',
        'elbow',
        'shoulder',
        'neck',
        'back',
        'hip',
        'knee',
        'ankle',
        'general',
        'other',
    ];

    public const array LIMITATION_SEVERITIES = ['mild', 'moderate', 'severe'];

    public const array LIMITATION_STATUSES = ['active', 'recurring', 'past'];

    public const array PROGRESSION_PACES = ['conservative', 'balanced', 'ambitious'];

    public const array INTENSITY_PREFERENCES = ['low', 'moderate', 'high', 'auto'];

    public const array EFFORT_TRACKING_PREFERENCES = ['simple', 'rir', 'rpe', 'both'];

    public const array DELOAD_PREFERENCES = ['auto', 'scheduled', 'manual'];

    public const array SESSION_STRUCTURE_PREFERENCES = [
        'skill_first',
        'strength_first',
        'hypertrophy_focus',
        'mobility_finish',
        'conditioning_finish',
        'longer_warmup',
        'unilateral_work',
        'isometrics',
        'explosive_work',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function defaultsFor(User $user): array
    {
        return [
            'display_name' => $user->name,
            'timezone' => 'UTC',
            'unit_system' => 'metric',
            'bodyweight_unit' => 'kg',
            'experience_level' => 'new',
            'secondary_goals' => [],
            'target_skills' => [],
            'available_equipment' => [],
            'training_locations' => [],
            'movement_limitations' => [],
            'preferred_training_days' => [],
            'preferred_training_time' => 'flexible',
            'progression_pace' => 'balanced',
            'intensity_preference' => 'auto',
            'effort_tracking_preference' => 'simple',
            'deload_preference' => 'auto',
            'session_structure_preferences' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        foreach ([
            'secondary_goals',
            'target_skills',
            'available_equipment',
            'training_locations',
            'preferred_training_days',
            'session_structure_preferences',
        ] as $key) {
            if (array_key_exists($key, $data) && is_array($data[$key])) {
                $data[$key] = self::uniqueList($data[$key]);
            }
        }

        if (array_key_exists('movement_limitations', $data) && is_array($data['movement_limitations'])) {
            $data['movement_limitations'] = array_values($data['movement_limitations']);
        }

        return $data;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return list<mixed>
     */
    private static function uniqueList(array $values): array
    {
        return array_values(array_unique($values, SORT_REGULAR));
    }
}
