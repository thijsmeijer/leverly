<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class RoadmapPhasePlan
{
    /**
     * @param  list<array<string, mixed>>  $tracks
     * @param  list<array<string, mixed>>  $foundationModules
     * @param  array<string, mixed>  $weeklySchedule
     * @return array<string, mixed>
     */
    public static function fromPortfolio(
        RoadmapInput $input,
        array $tracks,
        array $foundationModules,
        array $weeklySchedule,
    ): array {
        $duration = self::duration($input, $tracks, $foundationModules, $weeklySchedule);
        $rules = self::progressionRules($input, $tracks, $foundationModules);

        return [
            'phase_id' => 'current_block',
            'duration_weeks' => $duration['duration_weeks'],
            'duration_reason' => $duration['reason'],
            'weekly_emphasis' => self::weeklyEmphasis($tracks, $foundationModules),
            'roles' => [
                'development' => self::roleSummaries($tracks, 'development'),
                'technical_practice' => self::roleSummaries($tracks, 'technical_practice'),
                'accessory' => self::roleSummaries($tracks, 'accessory_transfer'),
                'maintenance' => self::roleSummaries($tracks, 'maintenance'),
                'foundation' => [
                    ...self::roleSummaries($tracks, 'foundation'),
                    ...self::foundationModuleSummaries($foundationModules),
                ],
            ],
            'foundation_layer' => self::foundationModuleSummaries($foundationModules),
            'retest_timing' => [
                'session_update' => 'Log pain, readiness, and quality after each exposure.',
                'weekly_review' => 'Review quality, pain, and completed exposures every week.',
                'block_retest_week' => $duration['duration_weeks']['target'],
                'seasonal_goal_review_weeks' => [12, 24],
            ],
            'deload_guidance' => [
                'planned_week' => $duration['duration_weeks']['target'],
                'triggers' => self::deloadTriggers($rules),
                'retest_guidance' => 'Retest the relevant baseline or node gate after the deload/retest week, not after one good session.',
            ],
            'progression_rules' => $rules,
            'safety_notes' => [
                'Progression changes only one major lever at a time by default.',
                'Pain, form breakdown, and poor readiness override overload.',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function empty(): array
    {
        return [
            'phase_id' => 'current_block',
            'duration_weeks' => ['min' => 0, 'target' => 0, 'max' => 0],
            'duration_reason' => '',
            'weekly_emphasis' => [],
            'roles' => [
                'development' => [],
                'technical_practice' => [],
                'accessory' => [],
                'maintenance' => [],
                'foundation' => [],
            ],
            'foundation_layer' => [],
            'retest_timing' => [],
            'deload_guidance' => ['planned_week' => null, 'triggers' => [], 'retest_guidance' => ''],
            'progression_rules' => [],
            'safety_notes' => [],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @param  list<array<string, mixed>>  $foundationModules
     * @param  array<string, mixed>  $weeklySchedule
     * @return array{duration_weeks: array{min: int, target: int, max: int}, reason: string}
     */
    private static function duration(RoadmapInput $input, array $tracks, array $foundationModules, array $weeklySchedule): array
    {
        if (self::isStableLongPhase($input, $tracks, $foundationModules, $weeklySchedule)) {
            return [
                'duration_weeks' => ['min' => 6, 'target' => 6, 'max' => 8],
                'reason' => 'Stable recovery and weighted-strength evidence can support a longer 6-8 week loading phase.',
            ];
        }

        return [
            'duration_weeks' => ['min' => 4, 'target' => 4, 'max' => 4],
            'reason' => 'First blocks, beginners, pain-sensitive, uncertain, or new advanced exposures use a 4-week phase.',
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @param  list<array<string, mixed>>  $foundationModules
     * @param  array<string, mixed>  $weeklySchedule
     */
    private static function isStableLongPhase(RoadmapInput $input, array $tracks, array $foundationModules, array $weeklySchedule): bool
    {
        $trainingAge = self::intOrNull($input->trainingContext['training_age_months'] ?? null) ?? 0;
        $sessions = self::intOrNull($input->trainingContext['weekly_session_goal'] ?? null) ?? 0;
        $painLevel = self::intOrNull($input->painFlags['level'] ?? null) ?? 0;
        $readiness = self::intOrNull($input->trainingContext['readiness_rating'] ?? null) ?? 3;
        $sleep = self::intOrNull($input->trainingContext['sleep_quality'] ?? null) ?? 3;
        $warnings = self::stringList($weeklySchedule['warnings'] ?? []);

        return $trainingAge >= 24
            && $sessions >= 4
            && $painLevel < 4
            && $readiness >= 4
            && $sleep >= 4
            && $warnings === []
            && self::hasWeightedModule($tracks, $foundationModules);
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @param  list<array<string, mixed>>  $foundationModules
     */
    private static function hasWeightedModule(array $tracks, array $foundationModules): bool
    {
        foreach (self::allModules($tracks, $foundationModules) as $module) {
            $dose = is_array($module['dose'] ?? null) ? $module['dose'] : [];
            $skill = self::stringValue($module['skill_track_id'] ?? null, '');

            if (($dose['metric'] ?? null) === 'load' || str_starts_with($skill, 'weighted_')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @param  list<array<string, mixed>>  $foundationModules
     * @return list<string>
     */
    private static function weeklyEmphasis(array $tracks, array $foundationModules): array
    {
        $development = self::roleSummaries($tracks, 'development');
        $technical = self::roleSummaries($tracks, 'technical_practice');
        $foundation = self::foundationModuleSummaries($foundationModules);
        $emphasis = [];

        if ($development !== []) {
            $emphasis[] = 'Prioritize development exposures while quality is freshest.';
        }

        if ($technical !== []) {
            $emphasis[] = 'Keep technical practice low enough fatigue that consistency can improve.';
        }

        if ($foundation !== []) {
            $emphasis[] = 'Reserve foundation and tissue capacity before adding harder levers.';
        }

        return $emphasis === [] ? ['Complete baseline evidence before increasing roadmap ambition.'] : $emphasis;
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @return list<array<string, mixed>>
     */
    private static function roleSummaries(array $tracks, string $mode): array
    {
        $summaries = [];

        foreach ($tracks as $track) {
            if (($track['mode'] ?? null) !== $mode) {
                continue;
            }

            $summaries[] = [
                'skill_track_id' => self::stringValue($track['skill_track_id'] ?? null, ''),
                'display_name' => self::stringValue($track['display_name'] ?? null, self::stringValue($track['skill_track_id'] ?? null, 'Track')),
                'module_ids' => array_values(array_map(
                    static fn (array $module): string => self::stringValue($module['module_id'] ?? null, ''),
                    self::arrayList($track['modules'] ?? []),
                )),
            ];
        }

        return $summaries;
    }

    /**
     * @param  list<array<string, mixed>>  $foundationModules
     * @return list<array<string, mixed>>
     */
    private static function foundationModuleSummaries(array $foundationModules): array
    {
        return array_values(array_map(
            static fn (array $module): array => [
                'module_id' => self::stringValue($module['module_id'] ?? null, ''),
                'title' => self::stringValue($module['title'] ?? null, 'Foundation module'),
                'purpose' => self::stringValue($module['purpose'] ?? null, 'foundation'),
            ],
            $foundationModules,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @param  list<array<string, mixed>>  $foundationModules
     * @return list<array<string, mixed>>
     */
    private static function progressionRules(RoadmapInput $input, array $tracks, array $foundationModules): array
    {
        $rules = [];

        foreach (self::allModules($tracks, $foundationModules) as $module) {
            $moduleId = self::stringValue($module['module_id'] ?? null, '');
            if ($moduleId === '' || isset($rules[$moduleId])) {
                continue;
            }

            $rules[$moduleId] = RoadmapProgressionRuleFactory::fromModule($module, $input);
        }

        return array_values($rules);
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @param  list<array<string, mixed>>  $foundationModules
     * @return list<array<string, mixed>>
     */
    private static function allModules(array $tracks, array $foundationModules): array
    {
        $modules = [];

        foreach ($tracks as $track) {
            foreach (self::arrayList($track['modules'] ?? []) as $module) {
                $modules[] = $module;
            }
        }

        return [...$modules, ...$foundationModules];
    }

    /**
     * @param  list<array<string, mixed>>  $rules
     * @return list<string>
     */
    private static function deloadTriggers(array $rules): array
    {
        $triggers = ['Planned deload/retest at the end of the phase.'];

        foreach ($rules as $rule) {
            $triggers = [...$triggers, ...self::stringList($rule['deload_triggers'] ?? [])];
        }

        return self::uniqueStrings($triggers);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function arrayList(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, is_array(...))) : [];
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, is_string(...))) : [];
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private static function uniqueStrings(array $values): array
    {
        return array_values(array_unique(array_filter($values, static fn (string $value): bool => $value !== '')));
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function stringValue(mixed $value, string $fallback): string
    {
        return is_string($value) && $value !== '' ? $value : $fallback;
    }
}
