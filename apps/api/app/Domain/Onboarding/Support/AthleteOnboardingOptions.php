<?php

declare(strict_types=1);

namespace App\Domain\Onboarding\Support;

use App\Domain\Profile\Support\AthleteProfileOptions;
use App\Domain\Training\Roadmap\RoadmapInputMapper;
use App\Domain\Training\Support\CalisthenicsGoalModuleOptions;
use App\Domain\Training\Support\CalisthenicsPlacementOptions;
use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use App\Models\AthleteOnboarding;
use App\Models\User;

final class AthleteOnboardingOptions
{
    public const array TARGET_SKILLS = CalisthenicsPlacementOptions::TARGET_SKILLS;

    public const array GOAL_MODULES = CalisthenicsGoalModuleOptions::MODULES;

    public const array GOAL_MODULE_METRIC_TYPES = CalisthenicsGoalModuleOptions::METRIC_TYPES;

    public const array GOAL_MODULE_QUALITY_MARKERS = CalisthenicsGoalModuleOptions::QUALITY_MARKERS;

    public const array BASE_FOCUS_AREAS = CalisthenicsPlacementOptions::BASE_FOCUS_AREAS;

    public const array SKILL_STATUS_KEYS = CalisthenicsPlacementOptions::SKILL_STATUS_KEYS;

    public const array SKILL_STATUSES = CalisthenicsPlacementOptions::SKILL_STATUSES;

    public const array MOBILITY_CHECK_KEYS = CalisthenicsPlacementOptions::MOBILITY_CHECK_KEYS;

    public const array MOBILITY_STATUSES = CalisthenicsPlacementOptions::MOBILITY_STATUSES;

    public const array WEIGHTED_EXPERIENCE_LEVELS = CalisthenicsPlacementOptions::WEIGHTED_EXPERIENCE_LEVELS;

    public const array WEIGHTED_MOVEMENTS = CalisthenicsPlacementOptions::WEIGHTED_MOVEMENTS;

    public const array LOWER_BODY_TEST_VARIANTS = CalisthenicsPlacementOptions::LOWER_BODY_TEST_VARIANTS;

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
            'age_years' => null,
            'training_age_months' => null,
            'experience_level' => 'new',
            'current_bodyweight_value' => null,
            'bodyweight_unit' => 'kg',
            'height_value' => null,
            'height_unit' => 'cm',
            'weight_trend' => 'unknown',
            'prior_sport_background' => [],
            'primary_goal' => null,
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
            'preferred_training_days' => [],
            'preferred_session_minutes' => null,
            'weekly_session_goal' => null,
            'current_level_tests' => self::emptyLevelTests(),
            'skill_statuses' => [],
            'mobility_checks' => self::emptyMobilityChecks(),
            'weighted_baselines' => self::emptyWeightedBaselines(),
            'readiness_rating' => null,
            'sleep_quality' => null,
            'soreness_level' => null,
            'pain_level' => null,
            'pain_areas' => [],
            'pain_flags' => AthleteProfileOptions::emptyPainFlags(),
            'pain_notes' => null,
            'starter_plan_key' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyLevelTests(): array
    {
        return CalisthenicsPlacementOptions::emptyLevelTests();
    }

    /**
     * @return array<string, string>
     */
    public static function emptyMobilityChecks(): array
    {
        return CalisthenicsPlacementOptions::emptyMobilityChecks();
    }

    /**
     * @return array<string, mixed>
     */
    public static function emptyWeightedBaselines(): array
    {
        return CalisthenicsPlacementOptions::emptyWeightedBaselines();
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
            'secondary_target_skills',
            'long_term_target_skills',
            'base_focus_areas',
            'available_equipment',
            'training_locations',
            'preferred_training_days',
            'pain_areas',
            'prior_sport_background',
        ] as $key) {
            if (array_key_exists($key, $data) && is_array($data[$key])) {
                $data[$key] = array_values(array_unique($data[$key]));
            }
        }

        if (array_key_exists('pain_flags', $data)) {
            if (is_array($data['pain_flags'])) {
                $data['pain_flags'] = AthleteProfileOptions::normalizePainFlags($data['pain_flags']);
            } else {
                unset($data['pain_flags']);
            }
        }

        $primaryTargetSkill = is_string($data['primary_target_skill'] ?? null) ? $data['primary_target_skill'] : null;

        if ($primaryTargetSkill !== null && $primaryTargetSkill !== '') {
            $data['target_skills'] = [$primaryTargetSkill];

            if (is_array($data['secondary_target_skills'] ?? null)) {
                $data['secondary_target_skills'] = array_values(array_filter(
                    array_values(array_unique($data['secondary_target_skills'])),
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

        unset($data['roadmap_suggestions']);

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

        foreach (['current_level_tests', 'skill_statuses', 'mobility_checks', 'weighted_baselines', 'pain_flags', 'goal_modules'] as $key) {
            if (is_array($base[$key] ?? null) && is_array($incoming[$key] ?? null)) {
                $merged[$key] = array_replace_recursive($base[$key], $incoming[$key]);
            }
        }

        $primaryTargetSkill = is_string($merged['primary_target_skill'] ?? null) ? $merged['primary_target_skill'] : null;

        if ($primaryTargetSkill !== null && $primaryTargetSkill !== '') {
            $merged['target_skills'] = [$primaryTargetSkill];
            $merged['secondary_target_skills'] = array_values(array_filter(
                array_values(array_unique(is_array($merged['secondary_target_skills'] ?? null) ? $merged['secondary_target_skills'] : [])),
                fn (mixed $skill): bool => is_string($skill) && $skill !== $primaryTargetSkill,
            ));
        }

        $merged['goal_modules'] = CalisthenicsGoalModuleOptions::normalizeForGoal(
            is_array($merged['goal_modules'] ?? null) ? $merged['goal_modules'] : [],
            $primaryTargetSkill,
        );
        unset($merged['required_goal_modules']);

        $merged['pain_flags'] = AthleteProfileOptions::completePainFlags(
            is_array($merged['pain_flags'] ?? null) ? $merged['pain_flags'] : [],
        );

        if (is_array($merged['current_level_tests'] ?? null)) {
            $merged['current_level_tests'] = CalisthenicsPlacementOptions::normalizeLevelTests($merged['current_level_tests']);
        }

        $merged['roadmap_suggestions'] = CalisthenicsRoadmapSuggester::portfolio(
            RoadmapInputMapper::fromAthleteData($merged),
        );

        return $merged;
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @return list<string>
     */
    public static function missingSections(array $candidate): array
    {
        $missing = [];

        if (! is_int($candidate['age_years'] ?? null)) {
            $missing[] = 'age';
        }

        if (! is_int($candidate['training_age_months'] ?? null)) {
            $missing[] = 'training_age';
        }

        if (! is_numeric($candidate['current_bodyweight_value'] ?? null)) {
            $missing[] = 'bodyweight';
        }

        if (! is_numeric($candidate['height_value'] ?? null)) {
            $missing[] = 'height';
        }

        if (empty($candidate['prior_sport_background']) || ! is_array($candidate['prior_sport_background'])) {
            $missing[] = 'training_background';
        }

        if (empty($candidate['target_skills']) || ! is_array($candidate['target_skills'])) {
            $missing[] = 'target_skills';
        }

        if (! is_string($candidate['primary_target_skill'] ?? null) || $candidate['primary_target_skill'] === '') {
            $missing[] = 'primary_target_skill';
        }

        foreach (CalisthenicsGoalModuleOptions::missingRequiredModules(
            is_array($candidate['goal_modules'] ?? null) ? $candidate['goal_modules'] : [],
            is_string($candidate['primary_target_skill'] ?? null) ? $candidate['primary_target_skill'] : null,
        ) as $missingGoalModule) {
            $missing[] = $missingGoalModule;
        }

        if (empty($candidate['base_focus_areas']) || ! is_array($candidate['base_focus_areas'])) {
            $missing[] = 'base_focus_areas';
        }

        if (! is_string($candidate['primary_goal'] ?? null) || $candidate['primary_goal'] === '') {
            $missing[] = 'goal';
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
        $dips = is_array($levelTests['dips'] ?? null) ? $levelTests['dips'] : [];
        $squat = is_array($levelTests['squat'] ?? null) ? $levelTests['squat'] : [];
        $rows = is_array($levelTests['rows'] ?? null) ? $levelTests['rows'] : [];
        $lowerBody = is_array($levelTests['lower_body'] ?? null) ? $levelTests['lower_body'] : [];

        if (! is_int($pushUps['max_strict_reps'] ?? null)) {
            $missing[] = 'push_up_test';
        }

        if (! is_int($pullUps['max_strict_reps'] ?? null)) {
            $missing[] = 'pull_up_test';
        }

        if (($pullUps['max_strict_reps'] ?? null) === 0 && ! self::hasPullUpFallback($levelTests)) {
            $missing[] = 'pull_up_fallback';
        }

        if (! is_int($dips['max_strict_reps'] ?? null)) {
            $missing[] = 'dip_test';
        }

        if (($dips['max_strict_reps'] ?? null) === 0 && ! self::hasDipFallback($levelTests)) {
            $missing[] = 'dip_fallback';
        }

        if (! is_int($rows['max_reps'] ?? null)) {
            $missing[] = 'row_test';
        }

        if (self::needsBarbellSquatTest($candidate) && (! is_numeric($squat['barbell_load_value'] ?? null) || ! is_int($squat['barbell_reps'] ?? null))) {
            $missing[] = 'barbell_squat_test';
        }

        if (! self::needsBarbellSquatTest($candidate) && ! self::hasLowerBodyFallback($lowerBody)) {
            $missing[] = 'lower_body_test';
        }

        if (! is_int($levelTests['hollow_hold_seconds'] ?? null)) {
            $missing[] = 'hollow_hold_test';
        }

        if (! is_int($levelTests['passive_hang_seconds'] ?? null)) {
            $missing[] = 'passive_hang_test';
        }

        if (! is_int($levelTests['top_support_hold_seconds'] ?? null)) {
            $missing[] = 'top_support_test';
        }

        $mobilityChecks = is_array($candidate['mobility_checks'] ?? null) ? $candidate['mobility_checks'] : [];
        if (empty($mobilityChecks) || count(array_filter($mobilityChecks, fn (mixed $status): bool => $status !== 'not_tested')) === 0) {
            $missing[] = 'mobility_checks';
        }

        if (self::needsWeightedBaseline($candidate) && ! self::hasWeightedBaseline($candidate)) {
            $missing[] = 'weighted_baseline';
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
            $onboarding === null ? self::defaultsFor(new User) : self::recordData($onboarding),
            self::normalize($incoming),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public static function recordData(AthleteOnboarding $onboarding): array
    {
        return [
            'age_years' => $onboarding->age_years,
            'training_age_months' => $onboarding->training_age_months,
            'experience_level' => $onboarding->experience_level,
            'current_bodyweight_value' => $onboarding->current_bodyweight_value,
            'bodyweight_unit' => $onboarding->bodyweight_unit,
            'height_value' => $onboarding->height_value,
            'height_unit' => $onboarding->height_unit,
            'weight_trend' => $onboarding->weight_trend,
            'prior_sport_background' => $onboarding->prior_sport_background ?? [],
            'primary_goal' => $onboarding->primary_goal,
            'secondary_goals' => $onboarding->secondary_goals ?? [],
            'target_skills' => $onboarding->target_skills ?? [],
            'primary_target_skill' => $onboarding->primary_target_skill,
            'secondary_target_skills' => $onboarding->secondary_target_skills ?? [],
            'long_term_target_skills' => $onboarding->long_term_target_skills ?? [],
            'base_focus_areas' => $onboarding->base_focus_areas ?? [],
            'required_goal_modules' => CalisthenicsGoalModuleOptions::modulesForGoal($onboarding->primary_target_skill),
            'goal_modules' => CalisthenicsGoalModuleOptions::normalizeForGoal(
                $onboarding->goal_modules ?? [],
                $onboarding->primary_target_skill,
            ),
            'roadmap_suggestions' => $onboarding->roadmap_suggestions ?? CalisthenicsRoadmapSuggester::empty(),
            'available_equipment' => $onboarding->available_equipment ?? [],
            'training_locations' => $onboarding->training_locations ?? [],
            'preferred_training_days' => $onboarding->preferred_training_days ?? [],
            'preferred_session_minutes' => $onboarding->preferred_session_minutes,
            'weekly_session_goal' => $onboarding->weekly_session_goal,
            'current_level_tests' => $onboarding->current_level_tests ?? self::emptyLevelTests(),
            'skill_statuses' => $onboarding->skill_statuses ?? [],
            'mobility_checks' => $onboarding->mobility_checks ?? self::emptyMobilityChecks(),
            'weighted_baselines' => $onboarding->weighted_baselines ?? self::emptyWeightedBaselines(),
            'readiness_rating' => $onboarding->readiness_rating,
            'sleep_quality' => $onboarding->sleep_quality,
            'soreness_level' => $onboarding->soreness_level,
            'pain_level' => $onboarding->pain_level,
            'pain_areas' => $onboarding->pain_areas ?? [],
            'pain_flags' => AthleteProfileOptions::completePainFlags($onboarding->pain_flags ?? []),
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
            'primary_target_skill',
            'secondary_target_skills',
            'long_term_target_skills',
            'base_focus_areas',
            'goal_modules',
            'age_years',
            'training_age_months',
            'experience_level',
            'current_bodyweight_value',
            'bodyweight_unit',
            'height_value',
            'height_unit',
            'weight_trend',
            'prior_sport_background',
            'available_equipment',
            'training_locations',
            'preferred_training_days',
            'preferred_session_minutes',
            'weekly_session_goal',
            'roadmap_suggestions',
        ] as $key) {
            if (array_key_exists($key, $data)) {
                $profileData[$key] = $data[$key];
            }
        }

        if (array_key_exists('current_level_tests', $data)) {
            $profileData['baseline_tests'] = $data['current_level_tests'];
        }

        foreach (['skill_statuses', 'mobility_checks', 'weighted_baselines'] as $key) {
            if (array_key_exists($key, $data)) {
                $profileData[$key] = $data[$key];
            }
        }

        if (array_key_exists('pain_flags', $data)) {
            $profileData['pain_flags'] = $data['pain_flags'];
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
     * @param  array<string, mixed>  $candidate
     */
    private static function needsWeightedBaseline(array $candidate): bool
    {
        $targets = array_filter([
            $candidate['primary_target_skill'] ?? null,
            ...array_values(is_array($candidate['target_skills'] ?? null) ? $candidate['target_skills'] : []),
        ]);

        return count(array_intersect($targets, self::WEIGHTED_MOVEMENTS)) > 0;
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private static function needsBarbellSquatTest(array $candidate): bool
    {
        $equipment = is_array($candidate['available_equipment'] ?? null) ? $candidate['available_equipment'] : [];
        $locations = is_array($candidate['training_locations'] ?? null) ? $candidate['training_locations'] : [];

        return in_array('barbell', $equipment, true) || in_array('squat_rack', $equipment, true) || in_array('gym', $locations, true);
    }

    /**
     * @param  array<string, mixed>  $levelTests
     */
    private static function hasPullUpFallback(array $levelTests): bool
    {
        $pullUps = is_array($levelTests['pull_ups'] ?? null) ? $levelTests['pull_ups'] : [];
        $variant = $pullUps['fallback_variant'] ?? 'none';

        return is_string($variant)
            && $variant !== 'none'
            && (is_int($pullUps['fallback_reps'] ?? null) || is_int($pullUps['fallback_seconds'] ?? null) || is_int($levelTests['passive_hang_seconds'] ?? null));
    }

    /**
     * @param  array<string, mixed>  $levelTests
     */
    private static function hasDipFallback(array $levelTests): bool
    {
        $dips = is_array($levelTests['dips'] ?? null) ? $levelTests['dips'] : [];
        $variant = $dips['fallback_variant'] ?? 'none';

        return is_string($variant)
            && $variant !== 'none'
            && (is_int($dips['fallback_reps'] ?? null) || is_int($dips['fallback_seconds'] ?? null) || is_int($levelTests['top_support_hold_seconds'] ?? null));
    }

    /**
     * @param  array<string, mixed>  $lowerBody
     */
    private static function hasLowerBodyFallback(array $lowerBody): bool
    {
        $variant = $lowerBody['variant'] ?? null;

        return is_string($variant)
            && $variant !== 'barbell_squat'
            && in_array($variant, self::LOWER_BODY_TEST_VARIANTS, true)
            && is_int($lowerBody['reps'] ?? null);
    }

    /**
     * @param  array<string, mixed>  $candidate
     */
    private static function hasWeightedBaseline(array $candidate): bool
    {
        $weightedBaselines = is_array($candidate['weighted_baselines'] ?? null) ? $candidate['weighted_baselines'] : [];
        $movements = is_array($weightedBaselines['movements'] ?? null) ? $weightedBaselines['movements'] : [];

        foreach ($movements as $movement) {
            if (! is_array($movement)) {
                continue;
            }

            $externalLoad = $movement['external_load_value'] ?? null;
            $reps = $movement['reps'] ?? null;

            if (is_numeric($externalLoad) && is_int($reps) && $reps > 0) {
                return true;
            }
        }

        return false;
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
