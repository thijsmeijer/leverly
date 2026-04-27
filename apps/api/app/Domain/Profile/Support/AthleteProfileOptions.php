<?php

declare(strict_types=1);

namespace App\Domain\Profile\Support;

use App\Domain\Training\Roadmap\RoadmapInputMapper;
use App\Domain\Training\Support\CalisthenicsGoalModuleOptions;
use App\Domain\Training\Support\CalisthenicsPlacementOptions;
use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use App\Models\AthleteProfile;
use App\Models\User;

final class AthleteProfileOptions
{
    public const array UNIT_SYSTEMS = ['metric', 'imperial'];

    public const array BODYWEIGHT_UNITS = ['kg', 'lb'];

    public const array HEIGHT_UNITS = ['cm', 'in'];

    public const array WEIGHT_TRENDS = ['cutting', 'maintaining', 'gaining', 'unknown'];

    public const array PRIOR_SPORT_BACKGROUNDS = [
        'none',
        'strength_training',
        'gymnastics',
        'climbing',
        'martial_arts',
        'endurance_sport',
        'team_sport',
        'dance_or_mobility',
        'other',
    ];

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
        'low_bar',
        'dip_bars',
        'parallel_bars',
        'stall_bars',
        'parallettes',
        'rings',
        'resistance_band',
        'box_bench',
        'barbell',
        'squat_rack',
        'weight_vest',
        'dip_belt',
        'weighted_backpack',
        'suspension_trainer',
        'ab_wheel',
        'jump_rope',
        'training_mat',
    ];

    public const array TRAINING_LOCATIONS = ['home', 'gym', 'park', 'travel'];

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

    public const array PAIN_REGIONS = ['wrist', 'elbow', 'shoulder', 'low_back', 'knee', 'ankle'];

    public const array PAIN_SEVERITIES = ['none', 'mild', 'moderate', 'severe'];

    public const array PAIN_STATUSES = ['none', 'active', 'recent', 'recurring', 'past'];

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

    public const array TARGET_SKILLS = CalisthenicsPlacementOptions::TARGET_SKILLS;

    public const array GOAL_MODULES = CalisthenicsGoalModuleOptions::MODULES;

    public const array GOAL_MODULE_METRIC_TYPES = CalisthenicsGoalModuleOptions::METRIC_TYPES;

    public const array GOAL_MODULE_QUALITY_MARKERS = CalisthenicsGoalModuleOptions::QUALITY_MARKERS;

    public const array BASE_FOCUS_AREAS = CalisthenicsPlacementOptions::BASE_FOCUS_AREAS;

    public const array MOBILITY_CHECK_KEYS = CalisthenicsPlacementOptions::MOBILITY_CHECK_KEYS;

    public const array MOBILITY_STATUSES = CalisthenicsPlacementOptions::MOBILITY_STATUSES;

    public const array WEIGHTED_EXPERIENCE_LEVELS = CalisthenicsPlacementOptions::WEIGHTED_EXPERIENCE_LEVELS;

    public const array WEIGHTED_MOVEMENTS = CalisthenicsPlacementOptions::WEIGHTED_MOVEMENTS;

    public const array ROW_VARIANTS = CalisthenicsPlacementOptions::ROW_VARIANTS;

    public const array PULL_UP_FALLBACK_VARIANTS = CalisthenicsPlacementOptions::PULL_UP_FALLBACK_VARIANTS;

    public const array DIP_FALLBACK_VARIANTS = CalisthenicsPlacementOptions::DIP_FALLBACK_VARIANTS;

    public const array LOWER_BODY_TEST_VARIANTS = CalisthenicsPlacementOptions::LOWER_BODY_TEST_VARIANTS;

    /**
     * @return array<string, mixed>
     */
    public static function defaultsFor(User $user): array
    {
        return [
            'display_name' => $user->name,
            'timezone' => 'UTC',
            'unit_system' => 'metric',
            'age_years' => null,
            'bodyweight_unit' => 'kg',
            'height_unit' => 'cm',
            'weight_trend' => 'unknown',
            'experience_level' => 'new',
            'prior_sport_background' => [],
            'secondary_goals' => [],
            'target_skills' => [],
            'primary_target_skill' => null,
            'secondary_target_skills' => [],
            'long_term_target_skills' => [],
            'base_focus_areas' => [],
            'goal_modules' => [],
            'roadmap_suggestions' => CalisthenicsRoadmapSuggester::empty(),
            'available_equipment' => [],
            'training_locations' => [],
            'movement_limitations' => [],
            'pain_flags' => self::emptyPainFlags(),
            'baseline_tests' => CalisthenicsPlacementOptions::emptyLevelTests(),
            'skill_statuses' => [],
            'mobility_checks' => CalisthenicsPlacementOptions::emptyMobilityChecks(),
            'weighted_baselines' => CalisthenicsPlacementOptions::emptyWeightedBaselines(),
            'preferred_training_days' => [],
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
            'secondary_target_skills',
            'long_term_target_skills',
            'base_focus_areas',
            'available_equipment',
            'training_locations',
            'prior_sport_background',
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

        if (array_key_exists('pain_flags', $data)) {
            if (is_array($data['pain_flags'])) {
                $data['pain_flags'] = self::normalizePainFlags($data['pain_flags']);
            } else {
                unset($data['pain_flags']);
            }
        }

        $primaryTargetSkill = is_string($data['primary_target_skill'] ?? null) ? $data['primary_target_skill'] : null;

        if ($primaryTargetSkill !== null && $primaryTargetSkill !== '') {
            $data['target_skills'] = [$primaryTargetSkill];

            if (is_array($data['secondary_target_skills'] ?? null)) {
                $data['secondary_target_skills'] = array_values(array_filter(
                    self::uniqueList($data['secondary_target_skills']),
                    fn (mixed $skill): bool => is_string($skill) && $skill !== $primaryTargetSkill,
                ));
            }
        }

        if (array_key_exists('goal_modules', $data) && $primaryTargetSkill !== null && $primaryTargetSkill !== '') {
            if (is_array($data['goal_modules'])) {
                $data['goal_modules'] = CalisthenicsGoalModuleOptions::normalizeForGoal(
                    $data['goal_modules'],
                    $primaryTargetSkill,
                );
            } else {
                unset($data['goal_modules']);
            }
        } elseif (array_key_exists('goal_modules', $data) && ! is_array($data['goal_modules'])) {
            unset($data['goal_modules']);
        }

        foreach (['baseline_tests', 'skill_statuses', 'mobility_checks', 'weighted_baselines'] as $key) {
            if (array_key_exists($key, $data) && ! is_array($data[$key])) {
                unset($data[$key]);
            }
        }

        unset($data['roadmap_suggestions']);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public static function recordData(AthleteProfile $profile): array
    {
        return [
            'display_name' => $profile->display_name,
            'timezone' => $profile->timezone,
            'unit_system' => $profile->unit_system,
            'age_years' => $profile->age_years,
            'training_age_months' => $profile->training_age_months,
            'experience_level' => $profile->experience_level,
            'current_bodyweight_value' => $profile->current_bodyweight_value,
            'bodyweight_unit' => $profile->bodyweight_unit,
            'height_value' => $profile->height_value,
            'height_unit' => $profile->height_unit,
            'weight_trend' => $profile->weight_trend,
            'prior_sport_background' => $profile->prior_sport_background ?? [],
            'primary_goal' => $profile->primary_goal,
            'secondary_goals' => $profile->secondary_goals ?? [],
            'target_skills' => $profile->target_skills ?? [],
            'primary_target_skill' => $profile->primary_target_skill,
            'secondary_target_skills' => $profile->secondary_target_skills ?? [],
            'long_term_target_skills' => $profile->long_term_target_skills ?? [],
            'base_focus_areas' => $profile->base_focus_areas ?? [],
            'required_goal_modules' => CalisthenicsGoalModuleOptions::modulesForGoal($profile->primary_target_skill),
            'goal_modules' => CalisthenicsGoalModuleOptions::normalizeForGoal(
                $profile->goal_modules ?? [],
                $profile->primary_target_skill,
            ),
            'roadmap_suggestions' => $profile->roadmap_suggestions ?? CalisthenicsRoadmapSuggester::empty(),
            'available_equipment' => $profile->available_equipment ?? [],
            'training_locations' => $profile->training_locations ?? [],
            'movement_limitations' => $profile->movement_limitations ?? [],
            'pain_flags' => self::completePainFlags($profile->pain_flags ?? []),
            'baseline_tests' => $profile->baseline_tests ?? CalisthenicsPlacementOptions::emptyLevelTests(),
            'skill_statuses' => $profile->skill_statuses ?? [],
            'mobility_checks' => $profile->mobility_checks ?? CalisthenicsPlacementOptions::emptyMobilityChecks(),
            'weighted_baselines' => $profile->weighted_baselines ?? CalisthenicsPlacementOptions::emptyWeightedBaselines(),
            'injury_notes' => $profile->injury_notes,
            'preferred_training_days' => $profile->preferred_training_days ?? [],
            'preferred_session_minutes' => $profile->preferred_session_minutes,
            'weekly_session_goal' => $profile->weekly_session_goal,
            'progression_pace' => $profile->progression_pace,
            'intensity_preference' => $profile->intensity_preference,
            'effort_tracking_preference' => $profile->effort_tracking_preference,
            'deload_preference' => $profile->deload_preference,
            'session_structure_preferences' => $profile->session_structure_preferences ?? [],
        ];
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    public static function mergeProfileData(array $base, array $incoming): array
    {
        $merged = [...$base, ...$incoming];

        foreach (['baseline_tests', 'skill_statuses', 'mobility_checks', 'weighted_baselines', 'pain_flags', 'goal_modules'] as $key) {
            if (is_array($base[$key] ?? null) && is_array($incoming[$key] ?? null)) {
                $merged[$key] = array_replace_recursive($base[$key], $incoming[$key]);
            }
        }

        $primaryTargetSkill = is_string($merged['primary_target_skill'] ?? null) ? $merged['primary_target_skill'] : null;

        if ($primaryTargetSkill !== null && $primaryTargetSkill !== '') {
            $merged['target_skills'] = [$primaryTargetSkill];
            $merged['secondary_target_skills'] = array_values(array_filter(
                self::uniqueList(is_array($merged['secondary_target_skills'] ?? null) ? $merged['secondary_target_skills'] : []),
                fn (mixed $skill): bool => is_string($skill) && $skill !== $primaryTargetSkill,
            ));
        }

        $merged['goal_modules'] = CalisthenicsGoalModuleOptions::normalizeForGoal(
            is_array($merged['goal_modules'] ?? null) ? $merged['goal_modules'] : [],
            $primaryTargetSkill,
        );
        unset($merged['required_goal_modules']);

        $merged['pain_flags'] = self::completePainFlags(
            is_array($merged['pain_flags'] ?? null) ? $merged['pain_flags'] : [],
        );

        if (is_array($merged['baseline_tests'] ?? null)) {
            $merged['baseline_tests'] = CalisthenicsPlacementOptions::normalizeLevelTests($merged['baseline_tests']);
        }

        $roadmapSource = $merged;
        $roadmapSource['current_level_tests'] = $merged['baseline_tests'] ?? CalisthenicsPlacementOptions::emptyLevelTests();
        $merged['roadmap_suggestions'] = CalisthenicsRoadmapSuggester::suggest(
            RoadmapInputMapper::fromAthleteData($roadmapSource),
        );

        return $merged;
    }

    /**
     * @param  array<int, mixed>  $values
     * @return list<mixed>
     */
    private static function uniqueList(array $values): array
    {
        return array_values(array_unique($values, SORT_REGULAR));
    }

    /**
     * @return array<string, array{severity: string, status: string, notes: string|null}>
     */
    public static function emptyPainFlags(): array
    {
        return array_fill_keys(
            self::PAIN_REGIONS,
            [
                'severity' => 'none',
                'status' => 'none',
                'notes' => null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $flags
     * @return array<string, array{severity: string, status: string, notes: string|null}>
     */
    public static function completePainFlags(array $flags): array
    {
        return array_replace_recursive(self::emptyPainFlags(), self::normalizePainFlags($flags));
    }

    /**
     * @param  array<string, mixed>  $flags
     * @return array<string, array{severity: string, status: string, notes: string|null}>
     */
    public static function normalizePainFlags(array $flags): array
    {
        $normalized = [];

        foreach (self::PAIN_REGIONS as $region) {
            $flag = $flags[$region] ?? null;

            if (! is_array($flag)) {
                continue;
            }

            $severity = is_string($flag['severity'] ?? null) ? $flag['severity'] : 'none';
            $status = is_string($flag['status'] ?? null) ? $flag['status'] : 'none';
            $notes = is_string($flag['notes'] ?? null) ? trim($flag['notes']) : null;

            $normalized[$region] = [
                'severity' => $severity,
                'status' => $status,
                'notes' => $notes === '' ? null : $notes,
            ];
        }

        return $normalized;
    }
}
