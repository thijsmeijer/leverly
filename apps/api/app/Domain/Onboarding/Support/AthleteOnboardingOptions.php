<?php

declare(strict_types=1);

namespace App\Domain\Onboarding\Support;

use App\Domain\Profile\Support\AthleteProfileOptions;
use App\Models\AthleteOnboarding;
use App\Models\User;

final class AthleteOnboardingOptions
{
    public const array TARGET_SKILLS = [
        'strict_push_up',
        'strict_pull_up',
        'strict_dip',
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
    ];

    public const array PULL_UP_PROGRESSIONS = [
        'dead_hang',
        'scapular_pull',
        'inverted_row',
        'band_assisted_pull_up',
        'eccentric_pull_up',
        'strict_pull_up',
        'chest_to_bar_pull_up',
        'weighted_pull_up',
    ];

    public const array SQUAT_PROGRESSIONS = [
        'box_squat',
        'air_squat',
        'reverse_lunge',
        'split_squat',
        'assisted_pistol',
        'shrimp_squat',
        'pistol_squat',
        'weighted_pistol',
    ];

    public const array SKILL_STATUS_KEYS = [
        'dip',
        'l_sit',
        'handstand',
        'front_lever',
        'planche',
    ];

    public const array SKILL_STATUSES = [
        'not_started',
        'building_base',
        'assisted',
        'partial_range',
        'single_rep',
        'multiple_reps',
        'short_hold',
        'solid_hold',
        'advanced_variation',
    ];

    public const array STARTER_PLANS = [
        'full_body_3_day',
        'upper_lower_4_day',
        'push_pull_legs_3_to_6_day',
        'skill_strength_split',
        'short_maintenance',
    ];

    /**
     * @return array<string, mixed>
     */
    public static function defaultsFor(User $user): array
    {
        return [
            'primary_goal' => null,
            'secondary_goals' => [],
            'target_skills' => [],
            'available_equipment' => [],
            'training_locations' => [],
            'preferred_training_days' => [],
            'preferred_session_minutes' => null,
            'weekly_session_goal' => null,
            'preferred_training_time' => 'flexible',
            'current_level_tests' => self::emptyLevelTests(),
            'skill_statuses' => [],
            'readiness_rating' => null,
            'sleep_quality' => null,
            'soreness_level' => null,
            'pain_level' => null,
            'pain_areas' => [],
            'pain_notes' => null,
            'starter_plan_key' => null,
        ];
    }

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
                'progression' => null,
            ],
            'squat' => [
                'max_reps' => null,
                'progression' => null,
            ],
            'hollow_hold_seconds' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function normalize(array $data): array
    {
        unset($data['complete']);

        foreach ([
            'secondary_goals',
            'target_skills',
            'available_equipment',
            'training_locations',
            'preferred_training_days',
            'pain_areas',
        ] as $key) {
            if (array_key_exists($key, $data) && is_array($data[$key])) {
                $data[$key] = array_values(array_unique($data[$key]));
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public static function mergeDraftData(array $base, array $incoming): array
    {
        $merged = [...$base, ...$incoming];

        foreach (['current_level_tests', 'skill_statuses'] as $key) {
            if (is_array($base[$key] ?? null) && is_array($incoming[$key] ?? null)) {
                $merged[$key] = array_replace_recursive($base[$key], $incoming[$key]);
            }
        }

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return list<string>
     */
    public static function missingSections(array $candidate): array
    {
        $missing = [];

        if (! is_string($candidate['primary_goal'] ?? null) || $candidate['primary_goal'] === '') {
            $missing[] = 'goal';
        }

        if (empty($candidate['target_skills']) || ! is_array($candidate['target_skills'])) {
            $missing[] = 'target_skills';
        }

        if (empty($candidate['training_locations']) || ! is_array($candidate['training_locations'])) {
            $missing[] = 'training_locations';
        }

        if (empty($candidate['preferred_training_days']) || ! is_array($candidate['preferred_training_days'])) {
            $missing[] = 'training_days';
        }

        if (! is_int($candidate['preferred_session_minutes'] ?? null)) {
            $missing[] = 'session_length';
        }

        if (! is_int($candidate['weekly_session_goal'] ?? null)) {
            $missing[] = 'weekly_sessions';
        }

        $levelTests = is_array($candidate['current_level_tests'] ?? null) ? $candidate['current_level_tests'] : [];
        $pushUps = is_array($levelTests['push_ups'] ?? null) ? $levelTests['push_ups'] : [];
        $pullUps = is_array($levelTests['pull_ups'] ?? null) ? $levelTests['pull_ups'] : [];
        $squat = is_array($levelTests['squat'] ?? null) ? $levelTests['squat'] : [];

        if (! is_int($pushUps['max_strict_reps'] ?? null)) {
            $missing[] = 'push_up_test';
        }

        if (! is_int($pullUps['max_strict_reps'] ?? null) && ! is_string($pullUps['progression'] ?? null)) {
            $missing[] = 'pull_up_test';
        }

        if (! is_int($squat['max_reps'] ?? null) && ! is_string($squat['progression'] ?? null)) {
            $missing[] = 'squat_test';
        }

        if (! is_int($levelTests['hollow_hold_seconds'] ?? null)) {
            $missing[] = 'hollow_hold_test';
        }

        if (! is_int($candidate['readiness_rating'] ?? null)) {
            $missing[] = 'readiness';
        }

        if (! is_int($candidate['soreness_level'] ?? null)) {
            $missing[] = 'soreness';
        }

        $painLevel = $candidate['pain_level'] ?? null;
        if (! is_int($painLevel)) {
            $missing[] = 'pain';
        }

        if (is_int($painLevel) && $painLevel >= 4 && empty($candidate['pain_areas'])) {
            $missing[] = 'pain_area';
        }

        if (! is_string($candidate['starter_plan_key'] ?? null) || $candidate['starter_plan_key'] === '') {
            $missing[] = 'starter_plan';
        }

        return $missing;
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public static function mergeForCompletion(?AthleteOnboarding $onboarding, array $incoming): array
    {
        return self::mergeDraftData(
            $onboarding === null ? [] : self::recordData($onboarding),
            self::normalize($incoming),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function recordData(AthleteOnboarding $onboarding): array
    {
        return [
            'primary_goal' => $onboarding->primary_goal,
            'secondary_goals' => $onboarding->secondary_goals ?? [],
            'target_skills' => $onboarding->target_skills ?? [],
            'available_equipment' => $onboarding->available_equipment ?? [],
            'training_locations' => $onboarding->training_locations ?? [],
            'preferred_training_days' => $onboarding->preferred_training_days ?? [],
            'preferred_session_minutes' => $onboarding->preferred_session_minutes,
            'weekly_session_goal' => $onboarding->weekly_session_goal,
            'preferred_training_time' => $onboarding->preferred_training_time,
            'current_level_tests' => $onboarding->current_level_tests ?? self::emptyLevelTests(),
            'skill_statuses' => $onboarding->skill_statuses ?? [],
            'readiness_rating' => $onboarding->readiness_rating,
            'sleep_quality' => $onboarding->sleep_quality,
            'soreness_level' => $onboarding->soreness_level,
            'pain_level' => $onboarding->pain_level,
            'pain_areas' => $onboarding->pain_areas ?? [],
            'pain_notes' => $onboarding->pain_notes,
            'starter_plan_key' => $onboarding->starter_plan_key,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function profileDataFor(array $data): array
    {
        $profileData = [];

        foreach ([
            'primary_goal',
            'secondary_goals',
            'target_skills',
            'available_equipment',
            'training_locations',
            'preferred_training_days',
            'preferred_session_minutes',
            'weekly_session_goal',
            'preferred_training_time',
        ] as $key) {
            if (array_key_exists($key, $data)) {
                $profileData[$key] = $data[$key];
            }
        }

        if (array_key_exists('pain_notes', $data)) {
            $profileData['injury_notes'] = $data['pain_notes'];
        }

        if (array_key_exists('pain_level', $data) || array_key_exists('pain_areas', $data) || array_key_exists('pain_notes', $data)) {
            $profileData['movement_limitations'] = self::movementLimitationsForPain($data);
        }

        return $profileData;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<array{area: string, severity: string, status: string, notes: string|null}>
     */
    private static function movementLimitationsForPain(array $data): array
    {
        $painLevel = $data['pain_level'] ?? null;

        if (! is_int($painLevel) || $painLevel === 0) {
            return [];
        }

        $severity = match (true) {
            $painLevel <= 3 => 'mild',
            $painLevel <= 6 => 'moderate',
            default => 'severe',
        };

        $areas = is_array($data['pain_areas'] ?? null) ? $data['pain_areas'] : ['general'];
        $notes = is_string($data['pain_notes'] ?? null) ? $data['pain_notes'] : null;

        return array_map(
            fn (string $area): array => [
                'area' => in_array($area, AthleteProfileOptions::LIMITATION_AREAS, true) ? $area : 'other',
                'severity' => $severity,
                'status' => 'active',
                'notes' => $notes,
            ],
            $areas,
        );
    }
}
