<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class RoadmapInputMapper
{
    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromAthleteData(array $data): RoadmapInput
    {
        $baselineTests = self::arrayValue($data['current_level_tests'] ?? $data['baseline_tests'] ?? []);
        $skillStatuses = self::arrayValue($data['skill_statuses'] ?? []);
        $mobilityChecks = self::arrayValue($data['mobility_checks'] ?? []);
        $weightedBaselines = self::arrayValue($data['weighted_baselines'] ?? []);

        return new RoadmapInput(
            profileContext: [
                'age_years' => self::intOrNull($data['age_years'] ?? null),
                'height_value' => self::numberOrNull($data['height_value'] ?? null),
                'height_unit' => self::stringValue($data['height_unit'] ?? null, 'cm'),
                'current_bodyweight_value' => self::numberOrNull($data['current_bodyweight_value'] ?? null),
                'bodyweight_unit' => self::stringValue($data['bodyweight_unit'] ?? null, 'kg'),
                'weight_trend' => self::stringValue($data['weight_trend'] ?? null, 'unknown'),
                'resistance_training_age_months' => self::intOrNull($data['resistance_training_age_months'] ?? null),
                'body_lever_context' => self::arrayValue($data['body_lever_context'] ?? []),
                'primary_goal' => self::stringOrNull($data['primary_goal'] ?? null),
                'secondary_goals' => self::stringList($data['secondary_goals'] ?? []),
                'long_term_target_skills' => self::stringList($data['long_term_target_skills'] ?? []),
            ],
            trainingContext: [
                'experience_level' => self::stringValue($data['experience_level'] ?? null, 'new'),
                'training_age_months' => self::intOrNull($data['training_age_months'] ?? null),
                'weekly_session_goal' => self::intOrNull($data['weekly_session_goal'] ?? null),
                'preferred_session_minutes' => self::intOrNull($data['preferred_session_minutes'] ?? null),
                'preferred_training_days' => self::stringList($data['preferred_training_days'] ?? []),
                'training_locations' => self::stringList($data['training_locations'] ?? []),
                'readiness_rating' => self::intOrNull($data['readiness_rating'] ?? null),
                'sleep_quality' => self::intOrNull($data['sleep_quality'] ?? null),
                'soreness_level' => self::intOrNull($data['soreness_level'] ?? null),
            ],
            equipment: self::stringList($data['available_equipment'] ?? []),
            painFlags: [
                'level' => self::intOrNull($data['pain_level'] ?? null),
                'areas' => self::painAreas($data),
                'regions' => self::arrayValue($data['pain_flags'] ?? []),
                'notes_present' => (
                    is_string($data['pain_notes'] ?? $data['injury_notes'] ?? null)
                    && trim((string) ($data['pain_notes'] ?? $data['injury_notes'])) !== ''
                ) || self::painFlagsHaveNotes($data['pain_flags'] ?? []),
                'movement_limitations' => self::arrayValue($data['movement_limitations'] ?? []),
            ],
            baselineTests: $baselineTests,
            goalModules: [
                'skill_statuses' => $skillStatuses,
                'mobility_checks' => $mobilityChecks,
                'weighted_baselines' => $weightedBaselines,
                'required_modules' => self::stringList($data['required_goal_modules'] ?? []),
                'conditional_modules' => self::arrayValue($data['goal_modules'] ?? []),
            ],
            selectedPrimaryGoal: self::stringOrNull($data['primary_target_skill'] ?? null),
            secondaryInterests: self::stringList($data['secondary_target_skills'] ?? []),
            longTermAspirations: self::stringList($data['long_term_target_skills'] ?? []),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private static function painAreas(array $data): array
    {
        $areas = self::stringList($data['pain_areas'] ?? []);
        $limitations = self::arrayValue($data['movement_limitations'] ?? []);

        foreach ($limitations as $limitation) {
            if (is_array($limitation) && is_string($limitation['area'] ?? null)) {
                $areas[] = $limitation['area'];
            }
        }

        foreach (self::arrayValue($data['pain_flags'] ?? []) as $region => $flag) {
            if (! is_string($region) || ! is_array($flag)) {
                continue;
            }

            if (($flag['severity'] ?? 'none') !== 'none' || ($flag['status'] ?? 'none') !== 'none') {
                $areas[] = $region;
            }
        }

        return array_values(array_unique($areas));
    }

    private static function painFlagsHaveNotes(mixed $painFlags): bool
    {
        foreach (self::arrayValue($painFlags) as $flag) {
            if (is_array($flag) && is_string($flag['notes'] ?? null) && trim($flag['notes']) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private static function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function numberOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private static function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }

    private static function stringValue(mixed $value, string $fallback): string
    {
        return self::stringOrNull($value) ?? $fallback;
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter($value, is_string(...))));
    }
}
