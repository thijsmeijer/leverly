<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class RoadmapWeeklyScheduler
{
    private const array SLOT_RANKS = [
        'warmup_prep' => 10,
        'skill_a' => 20,
        'skill_b' => 30,
        'primary_strength' => 40,
        'accessory' => 50,
        'core_compression' => 60,
        'cooldown_mobility' => 70,
    ];

    private const array DAY_TEMPLATES = [
        1 => ['full_body'],
        2 => ['upper_strength', 'full_body'],
        3 => ['push_strength', 'pull_strength', 'legs'],
        4 => ['general_skills', 'pull_strength', 'push_strength', 'legs'],
        5 => ['general_skills', 'legs', 'pull_strength', 'push_strength', 'pull_skills'],
        6 => ['general_skills', 'legs', 'pull_strength', 'push_strength', 'pull_skills', 'push_skills'],
    ];

    private const array SLOT_ALIASES = [
        'strength' => 'primary_strength',
        'mobility' => 'cooldown_mobility',
        'cooldown' => 'cooldown_mobility',
        'core' => 'core_compression',
    ];

    private const array PROTECT_48H_AXES = [
        'elbow_pull_tendon',
        'wrist_extension',
        'straight_arm_push',
    ];

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @param  list<array<string, mixed>>  $foundationModules
     * @return array{
     *     days: list<array<string, mixed>>,
     *     rest_days: list<array<string, mixed>>,
     *     template: array<string, mixed>,
     *     stress_ledger: array<string, mixed>,
     *     time_ledger: array<string, mixed>,
     *     warnings: list<string>
     * }
     */
    public static function fromModules(
        RoadmapInput $input,
        array $tracks,
        array $foundationModules,
        RoadmapStressBudget $stressBudget,
    ): array {
        $maxSessions = self::maxSessions($input);

        if ($maxSessions < 1) {
            return self::empty();
        }

        $sessionMinutes = self::sessionMinutes($input);
        $days = self::initialDays($input, $maxSessions);
        $warnings = [];
        $entries = self::moduleEntries($tracks, $foundationModules, $maxSessions);

        usort(
            $entries,
            static fn (array $left, array $right): int => self::entryPriority($left) <=> self::entryPriority($right),
        );

        foreach ($entries as $position => $entry) {
            $dayIndex = self::bestDayIndex($days, $entry['module'], $stressBudget);
            $slot = self::bestSlot($days[$dayIndex], $entry['module']);
            $days[$dayIndex]['modules'][] = (new RoadmapScheduledModule(
                moduleId: self::moduleId($entry['module']),
                skillTrackId: self::stringValue($entry['module']['skill_track_id'] ?? null, ''),
                title: self::stringValue($entry['module']['title'] ?? null, 'Training module'),
                purpose: self::stringValue($entry['module']['purpose'] ?? null, 'training'),
                pattern: self::stringValue($entry['module']['pattern'] ?? null, 'general'),
                intensityTier: self::stringValue($entry['module']['intensity_tier'] ?? null, 'medium'),
                sourceMode: $entry['source_mode'],
                slot: $slot,
                slotRank: self::slotRank($slot),
                order: $position + 1,
                exposureIndex: $entry['exposure_index'],
                estimatedMinutes: self::estimatedMinutes($entry['module']),
                stressVector: self::stressVector($entry['module']),
            ))->toArray();
        }

        $scheduledDays = [];
        foreach ($days as $day) {
            usort(
                $day['modules'],
                static fn (array $left, array $right): int => [$left['slot_rank'], $left['order']] <=> [$right['slot_rank'], $right['order']],
            );

            $stressLedger = self::dayStressLedger($day['modules'], $stressBudget);
            $timeLedger = self::dayTimeLedger($day['modules'], $sessionMinutes);
            $dayWarnings = self::dayWarnings($day, $stressLedger, $timeLedger);
            $warnings = [...$warnings, ...$dayWarnings];

            $scheduledDays[] = (new RoadmapScheduledDay(
                dayIndex: $day['day_index'],
                label: $day['label'],
                dayType: $day['day_type'],
                modules: $day['modules'],
                stressLedger: $stressLedger,
                timeLedger: $timeLedger,
                warnings: $dayWarnings,
            ))->toArray();
        }

        $recoveryWarnings = self::recoveryWarnings($scheduledDays);
        $warnings = [...$warnings, ...$recoveryWarnings];

        return [
            'days' => $scheduledDays,
            'rest_days' => self::restDays($maxSessions),
            'template' => [
                'sessions_per_week' => $maxSessions,
                'day_types' => self::dayTypes($maxSessions),
                'slot_order' => array_keys(self::SLOT_RANKS),
            ],
            'stress_ledger' => self::weeklyStressLedger($scheduledDays, $stressBudget),
            'time_ledger' => self::weeklyTimeLedger($scheduledDays, $maxSessions, $sessionMinutes),
            'warnings' => self::uniqueStrings($warnings),
        ];
    }

    /**
     * @return array{days: list<array<string, mixed>>, rest_days: list<array<string, mixed>>, template: array<string, mixed>, stress_ledger: array<string, mixed>, time_ledger: array<string, mixed>, warnings: list<string>}
     */
    public static function empty(): array
    {
        return [
            'days' => [],
            'rest_days' => [],
            'template' => ['sessions_per_week' => 0, 'day_types' => [], 'slot_order' => array_keys(self::SLOT_RANKS)],
            'stress_ledger' => ['axes' => [], 'warnings' => []],
            'time_ledger' => ['estimated_minutes_per_week' => 0, 'budget_minutes_per_week' => 0, 'overflow_minutes_per_week' => 0],
            'warnings' => [],
        ];
    }

    /**
     * @return array<int, array{day_index: int, label: string, day_type: string, modules: list<array<string, mixed>>}>
     */
    private static function initialDays(RoadmapInput $input, int $maxSessions): array
    {
        $preferredDays = self::stringList($input->trainingContext['preferred_training_days'] ?? []);
        $dayTypes = self::dayTypes($maxSessions);
        $days = [];

        foreach ($dayTypes as $index => $dayType) {
            $days[$index] = [
                'day_index' => $index + 1,
                'label' => self::dayLabel($preferredDays[$index] ?? null, $index + 1),
                'day_type' => $dayType,
                'modules' => [],
            ];
        }

        return $days;
    }

    /**
     * @return list<string>
     */
    private static function dayTypes(int $maxSessions): array
    {
        return self::DAY_TEMPLATES[max(1, min(6, $maxSessions))];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function restDays(int $maxSessions): array
    {
        $restDays = [];

        for ($index = $maxSessions + 1; $index <= 7; $index++) {
            $restDays[] = [
                'day_index' => $index,
                'label' => 'Rest day '.($index - $maxSessions),
                'day_type' => 'rest',
            ];
        }

        return $restDays;
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @param  list<array<string, mixed>>  $foundationModules
     * @return list<array{module: array<string, mixed>, source_mode: string, exposure_index: int}>
     */
    private static function moduleEntries(array $tracks, array $foundationModules, int $maxSessions): array
    {
        $entries = [];

        foreach ($tracks as $track) {
            $sourceMode = self::stringValue($track['mode'] ?? null, 'training');
            foreach (self::arrayList($track['modules'] ?? []) as $module) {
                foreach (range(1, self::targetExposures($module, $maxSessions)) as $exposureIndex) {
                    $entries[] = ['module' => $module, 'source_mode' => $sourceMode, 'exposure_index' => $exposureIndex];
                }
            }
        }

        foreach ($foundationModules as $module) {
            foreach (range(1, self::targetExposures($module, $maxSessions)) as $exposureIndex) {
                $entries[] = ['module' => $module, 'source_mode' => 'foundation', 'exposure_index' => $exposureIndex];
            }
        }

        return $entries;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private static function entryPriority(array $entry): int
    {
        $module = is_array($entry['module'] ?? null) ? $entry['module'] : [];
        $purpose = self::stringValue($module['purpose'] ?? null, '');
        $intensity = self::stringValue($module['intensity_tier'] ?? null, 'medium');

        $priority = match ($purpose) {
            'technical_practice' => 10,
            'development' => 20,
            'tissue_capacity', 'mobility_prep' => 30,
            'foundation_strength' => 40,
            'accessory_transfer' => 50,
            'maintenance' => 60,
            default => 70,
        };

        return $priority - (in_array($intensity, ['high', 'max'], true) ? 2 : 0);
    }

    /**
     * @param  array<int, array{day_index: int, label: string, day_type: string, modules: list<array<string, mixed>>}>  $days
     * @param  array<string, mixed>  $module
     */
    private static function bestDayIndex(array $days, array $module, RoadmapStressBudget $stressBudget): int
    {
        $bestIndex = array_key_first($days) ?? 0;
        $bestScore = PHP_INT_MIN;

        foreach ($days as $index => $day) {
            $score = 0;
            $score += self::matchesDayType($day['day_type'], $module) ? 80 : -30;
            $score += self::hasRecoverySpacing($days, $index, $module) ? 45 : -95;
            $score += self::staysUnderHardCap($day['modules'], $module, $stressBudget) ? 30 : -70;
            $score -= self::dayMinutes($day['modules']);
            $score -= count($day['modules']) * 5;

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestIndex = $index;
            }
        }

        return $bestIndex;
    }

    /**
     * @param  array{day_index: int, label: string, day_type: string, modules: list<array<string, mixed>>}  $day
     * @param  array<string, mixed>  $module
     */
    private static function bestSlot(array $day, array $module): string
    {
        $slots = self::stringList($module['allowed_session_slots'] ?? []);

        if ($slots === []) {
            $slots = ['accessory'];
        }

        usort($slots, static fn (string $left, string $right): int => self::slotRank($left) <=> self::slotRank($right));

        foreach ($slots as $slot) {
            $slot = self::canonicalSlot($slot);
            if (self::slotCanRepeat($slot) || ! self::dayHasSlot($day['modules'], $slot)) {
                return $slot;
            }
        }

        return 'accessory';
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     */
    private static function dayHasSlot(array $modules, string $slot): bool
    {
        foreach ($modules as $module) {
            if (($module['slot'] ?? null) === $slot) {
                return true;
            }
        }

        return false;
    }

    private static function slotCanRepeat(string $slot): bool
    {
        return in_array($slot, ['accessory', 'core_compression', 'cooldown_mobility'], true);
    }

    private static function canonicalSlot(string $slot): string
    {
        return self::SLOT_ALIASES[$slot] ?? $slot;
    }

    private static function slotRank(string $slot): int
    {
        return self::SLOT_RANKS[self::canonicalSlot($slot)] ?? 99;
    }

    /**
     * @param  array<string, mixed>  $module
     */
    private static function targetExposures(array $module, int $maxSessions): int
    {
        $targets = is_array($module['exposure_targets'] ?? null) ? $module['exposure_targets'] : [];
        $target = self::intOrNull($targets['target_per_week'] ?? null) ?? 1;

        return max(1, min($target, $maxSessions));
    }

    /**
     * @param  array<string, mixed>  $module
     */
    private static function matchesDayType(string $dayType, array $module): bool
    {
        $compatible = self::stringList($module['compatible_day_types'] ?? []);
        $pattern = self::stringValue($module['pattern'] ?? null, 'general');

        if (in_array($dayType, $compatible, true) || $dayType === 'full_body') {
            return true;
        }

        if ($dayType === 'upper_strength') {
            return ! in_array($pattern, ['legs', 'lower_body'], true);
        }

        if ($dayType === 'general_skills') {
            return in_array($pattern, ['inversion', 'transition', 'mobility', 'trunk', 'compression'], true);
        }

        if ($dayType === 'legs') {
            return in_array($pattern, ['legs', 'trunk', 'compression', 'mobility'], true);
        }

        return false;
    }

    /**
     * @param  array<int, array{day_index: int, label: string, day_type: string, modules: list<array<string, mixed>>}>  $days
     * @param  array<string, mixed>  $module
     */
    private static function hasRecoverySpacing(array $days, int $candidateIndex, array $module): bool
    {
        if (! self::isHighStressModule($module)) {
            return true;
        }

        $candidateAxes = self::highRecoveryAxes($module);
        if ($candidateAxes === []) {
            return true;
        }

        foreach ($days as $index => $day) {
            if (abs($candidateIndex - $index) > 1) {
                continue;
            }

            foreach ($day['modules'] as $scheduled) {
                if (array_intersect($candidateAxes, self::highRecoveryAxes($scheduled)) !== []) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $module
     * @return list<string>
     */
    private static function highRecoveryAxes(array $module): array
    {
        $axes = [];

        foreach (self::stressVector($module) as $axis => $load) {
            if ($load >= 3 || in_array($axis, self::PROTECT_48H_AXES, true)) {
                $axes[] = $axis;
            }
        }

        return array_values(array_unique($axes));
    }

    /**
     * @param  array<string, mixed>  $module
     */
    private static function isHighStressModule(array $module): bool
    {
        return in_array(self::stringValue($module['intensity_tier'] ?? null, ''), ['high', 'max'], true);
    }

    /**
     * @param  list<array<string, mixed>>  $scheduled
     * @param  array<string, mixed>  $module
     */
    private static function staysUnderHardCap(array $scheduled, array $module, RoadmapStressBudget $stressBudget): bool
    {
        $loads = self::loadsForModules([...$scheduled, self::scheduledLikeModule($module)]);

        foreach ($loads as $axis => $load) {
            if ($load > ($stressBudget->perDayHardCap[$axis] ?? 99)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     * @return array{axes: list<array<string, mixed>>, warnings: list<string>}
     */
    private static function dayStressLedger(array $modules, RoadmapStressBudget $stressBudget): array
    {
        $axes = [];
        $warnings = [];

        foreach (self::loadsForModules($modules) as $axis => $load) {
            $softCap = $stressBudget->perDaySoftCap[$axis] ?? 4;
            $hardCap = $stressBudget->perDayHardCap[$axis] ?? max(5, $softCap + 2);
            $status = $load > $hardCap ? 'red' : ($load > $softCap ? 'yellow' : 'green');

            if ($status !== 'green') {
                $warnings[] = "Stress {$status} flag for {$axis}.";
            }

            $axes[] = [
                'axis' => $axis,
                'load' => $load,
                'soft_cap' => $softCap,
                'hard_cap' => $hardCap,
                'status' => $status,
            ];
        }

        return ['axes' => $axes, 'warnings' => $warnings];
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     * @return array{estimated_minutes: int, budget_minutes: int, overflow_minutes: int, status: string}
     */
    private static function dayTimeLedger(array $modules, int $sessionMinutes): array
    {
        $estimated = self::dayMinutes($modules);

        return [
            'estimated_minutes' => $estimated,
            'budget_minutes' => $sessionMinutes,
            'overflow_minutes' => max(0, $estimated - $sessionMinutes),
            'status' => $estimated > $sessionMinutes ? 'yellow' : 'green',
        ];
    }

    /**
     * @param  array{day_index: int, label: string, day_type: string, modules: list<array<string, mixed>>}  $day
     * @param  array<string, mixed>  $stressLedger
     * @param  array<string, mixed>  $timeLedger
     * @return list<string>
     */
    private static function dayWarnings(array $day, array $stressLedger, array $timeLedger): array
    {
        $warnings = [];

        if (($timeLedger['overflow_minutes'] ?? 0) > 0) {
            $warnings[] = "{$day['label']} has time overflow of {$timeLedger['overflow_minutes']} minutes.";
        }

        foreach (self::stringList($stressLedger['warnings'] ?? []) as $warning) {
            $warnings[] = "{$day['label']} {$warning}";
        }

        return $warnings;
    }

    /**
     * @param  list<array<string, mixed>>  $scheduledDays
     * @return list<string>
     */
    private static function recoveryWarnings(array $scheduledDays): array
    {
        $warnings = [];

        foreach ($scheduledDays as $left) {
            foreach ($scheduledDays as $right) {
                if (($right['day_index'] ?? 0) <= ($left['day_index'] ?? 0) || abs((int) $right['day_index'] - (int) $left['day_index']) > 1) {
                    continue;
                }

                if (array_intersect(self::dayHighAxes($left), self::dayHighAxes($right)) !== []) {
                    $warnings[] = "{$left['label']} and {$right['label']} have close high-stress overlap.";
                }
            }
        }

        return $warnings;
    }

    /**
     * @param  array<string, mixed>  $day
     * @return list<string>
     */
    private static function dayHighAxes(array $day): array
    {
        $axes = [];

        foreach (self::arrayList($day['modules'] ?? []) as $module) {
            if (! self::isHighStressModule($module)) {
                continue;
            }

            $axes = [...$axes, ...self::highRecoveryAxes($module)];
        }

        return array_values(array_unique($axes));
    }

    /**
     * @param  list<array<string, mixed>>  $scheduledDays
     * @return array{axes: list<array<string, mixed>>, warnings: list<string>}
     */
    private static function weeklyStressLedger(array $scheduledDays, RoadmapStressBudget $stressBudget): array
    {
        $loads = [];
        $warnings = [];

        foreach ($scheduledDays as $day) {
            foreach (self::arrayList($day['modules'] ?? []) as $module) {
                foreach (self::stressVector($module) as $axis => $load) {
                    $loads[$axis] = ($loads[$axis] ?? 0) + $load;
                }
            }

            $warnings = [...$warnings, ...self::stringList($day['stress_ledger']['warnings'] ?? [])];
        }

        $axes = [];
        foreach ($loads as $axis => $load) {
            $budget = $stressBudget->weeklyBudget[$axis] ?? 99;
            $axes[] = [
                'axis' => $axis,
                'load' => $load,
                'budget' => $budget,
                'status' => $load > $budget ? 'yellow' : 'green',
            ];
        }

        return ['axes' => $axes, 'warnings' => self::uniqueStrings($warnings)];
    }

    /**
     * @param  list<array<string, mixed>>  $scheduledDays
     * @return array{estimated_minutes_per_week: int, budget_minutes_per_week: int, overflow_minutes_per_week: int}
     */
    private static function weeklyTimeLedger(array $scheduledDays, int $maxSessions, int $sessionMinutes): array
    {
        $estimated = array_sum(array_map(
            static fn (array $day): int => (int) ($day['time_ledger']['estimated_minutes'] ?? 0),
            $scheduledDays,
        ));
        $budget = $maxSessions * $sessionMinutes;

        return [
            'estimated_minutes_per_week' => $estimated,
            'budget_minutes_per_week' => $budget,
            'overflow_minutes_per_week' => max(0, $estimated - $budget),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     * @return array<string, int>
     */
    private static function loadsForModules(array $modules): array
    {
        $loads = [];

        foreach ($modules as $module) {
            foreach (self::stressVector($module) as $axis => $load) {
                $loads[$axis] = ($loads[$axis] ?? 0) + $load;
            }
        }

        return $loads;
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     */
    private static function dayMinutes(array $modules): int
    {
        return array_sum(array_map(
            static fn (array $module): int => self::intOrNull($module['estimated_minutes'] ?? null) ?? self::estimatedMinutes($module),
            $modules,
        ));
    }

    /**
     * @param  array<string, mixed>  $module
     */
    private static function estimatedMinutes(array $module): int
    {
        $time = is_array($module['time_cost_minutes'] ?? null) ? $module['time_cost_minutes'] : [];
        $min = self::intOrNull($time['min'] ?? null) ?? 8;
        $max = self::intOrNull($time['max'] ?? null) ?? $min;

        return (int) round(($min + $max) / 2);
    }

    /**
     * @param  array<string, mixed>  $module
     * @return array<string, int>
     */
    private static function stressVector(array $module): array
    {
        $vector = is_array($module['stress_vector'] ?? null) ? $module['stress_vector'] : [];
        $normalized = [];

        foreach ($vector as $axis => $load) {
            $value = self::intOrNull($load) ?? 0;
            if ($value > 0) {
                $normalized[(string) $axis] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $module
     * @return array<string, mixed>
     */
    private static function scheduledLikeModule(array $module): array
    {
        return [
            'stress_vector' => self::stressVector($module),
            'estimated_minutes' => self::estimatedMinutes($module),
            'intensity_tier' => self::stringValue($module['intensity_tier'] ?? null, 'medium'),
        ];
    }

    /**
     * @param  array<string, mixed>  $module
     */
    private static function moduleId(array $module): string
    {
        return self::stringValue($module['module_id'] ?? null, self::stringValue($module['node_id'] ?? null, 'module'));
    }

    private static function maxSessions(RoadmapInput $input): int
    {
        return max(0, min(6, self::intOrNull($input->trainingContext['weekly_session_goal'] ?? null) ?? 0));
    }

    private static function sessionMinutes(RoadmapInput $input): int
    {
        return max(15, self::intOrNull($input->trainingContext['preferred_session_minutes'] ?? null) ?? 45);
    }

    private static function dayLabel(?string $preferredDay, int $index): string
    {
        if ($preferredDay === null || $preferredDay === '') {
            return "Day {$index}";
        }

        return ucfirst($preferredDay);
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
