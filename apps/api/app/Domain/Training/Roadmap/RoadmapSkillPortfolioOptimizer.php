<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class RoadmapSkillPortfolioOptimizer
{
    private const array TECHNICAL_DEFAULTS = ['handstand', 'press_to_handstand'];

    private const array ACCESSORY_DEFAULTS = ['l_sit', 'v_sit', 'pistol_squat', 'nordic_curl'];

    private const array FOUNDATION_BRIDGE_SKILLS = ['strict_push_up', 'strict_pull_up', 'strict_dip'];

    private const array HIGH_STRESS_SKILLS = [
        'front_lever',
        'back_lever',
        'planche',
        'handstand_push_up',
        'press_to_handstand',
        'muscle_up',
        'weighted_muscle_up',
        'one_arm_pull_up',
        'human_flag',
        'weighted_pull_up',
        'weighted_dip',
    ];

    /**
     * @param  list<array<string, mixed>>  $developmentTracks
     * @param  list<array<string, mixed>>  $technicalPracticeTracks
     * @param  list<array<string, mixed>>  $accessoryTracks
     * @param  list<array<string, mixed>>  $maintenanceTracks
     * @param  list<array<string, mixed>>  $foundationTracks
     * @param  list<array<string, mixed>>  $futureQueue
     * @param  list<array<string, mixed>>  $notRecommendedNow
     */
    public static function fromTracks(
        RoadmapInput $input,
        array $developmentTracks,
        array $technicalPracticeTracks,
        array $accessoryTracks,
        array $maintenanceTracks,
        array $foundationTracks,
        array $futureQueue,
        array $notRecommendedNow,
        RoadmapStressBudget $stressBudget,
    ): RoadmapSkillPortfolio {
        $development = [];
        $technical = [];
        $accessory = [];
        $maintenance = self::annotateTracks($maintenanceTracks, 'maintenance');
        $future = [];
        $notNow = [];
        $rejectedSkills = [];
        $seen = [];
        $highStressCount = 0;
        $candidates = self::candidatePool($developmentTracks, $technicalPracticeTracks, $accessoryTracks, $futureQueue);

        usort(
            $candidates,
            static fn (array $left, array $right): int => ($right['utility_score'] ?? 0) <=> ($left['utility_score'] ?? 0),
        );

        foreach ($candidates as $candidate) {
            $track = $candidate['track'];
            $skill = self::trackId($track);

            if ($skill === '' || isset($seen[$skill])) {
                continue;
            }

            $seen[$skill] = true;
            $utilityScore = self::utilityScore($track, $candidate['source_mode'], $input);
            $status = self::nodeStatus($track);
            $desiredMode = self::desiredMode($track, $candidate['source_mode'], $input);

            if ($status === 'blocked_by_hard_gate' && ! self::canTrainFoundationBridge($track)) {
                $notNow[] = self::reject($track, 'Hard gates must change before this module can enter the active portfolio.', $utilityScore);
                $future[] = self::reject($track, 'Keep this visible after the hard gate changes.', $utilityScore);
                $rejectedSkills[$skill] = true;

                continue;
            }

            if ($status === 'blocked_pending_input') {
                $future[] = self::reject($track, 'Complete focused tests before selecting this module.', $utilityScore);
                $notNow[] = self::reject($track, 'Complete focused tests before selecting this module.', $utilityScore);
                $rejectedSkills[$skill] = true;

                continue;
            }

            if ($desiredMode === 'development') {
                $deferralReason = self::developmentDeferralReason($track);
                if ($deferralReason !== null) {
                    $future[] = self::reject($track, $deferralReason, $utilityScore);
                    $notNow[] = self::reject($track, $deferralReason, $utilityScore);
                    $rejectedSkills[$skill] = true;

                    continue;
                }

                if (
                    $stressBudget->highStressDevelopmentLanes <= 1
                    && self::isHighStressTrack($track)
                    && ! self::isPrimarySelection($track, $input)
                ) {
                    $notNow[] = self::reject($track, 'Recovery budget only allows a primary high-stress development lane in this phase.', $utilityScore);
                    $future[] = self::reject($track, 'Add this after the current recovery budget can support another high-stress lane.', $utilityScore);
                    $rejectedSkills[$skill] = true;

                    continue;
                }

                $track = self::withMode($track, 'development', $utilityScore);
                $isHighStress = self::isHighStressTrack($track);

                if ($isHighStress && $highStressCount >= $stressBudget->highStressDevelopmentLanes) {
                    $notNow[] = self::reject($track, 'High-stress development cap is already reserved for this phase.', $utilityScore);
                    $future[] = self::reject($track, 'Build this after the current high-stress lane has adapted.', $utilityScore);
                    $rejectedSkills[$skill] = true;

                    continue;
                }

                $compatibility = self::developmentCompatibility($track, $development, $stressBudget);
                if ($compatibility?->state === 'red') {
                    $notNow[] = self::reject($track, implode(' ', $compatibility->reasons), $utilityScore);
                    $future[] = self::reject($track, 'Keep this visible for a later block with less stress overlap.', $utilityScore);
                    $rejectedSkills[$skill] = true;

                    continue;
                }

                if ($compatibility?->state === 'orange' && ! self::isPrimarySelection($track, $input)) {
                    $technical[] = self::withMode(
                        $track,
                        self::technicalModeFor($track),
                        $utilityScore,
                        'Downgraded from development because this phase already has a conflicting high-stress lane.',
                    );

                    continue;
                }

                $development[] = $track;
                $highStressCount += $isHighStress ? 1 : 0;

                continue;
            }

            if ($desiredMode === 'technical_practice') {
                $technical[] = self::withMode($track, 'technical_practice', $utilityScore);

                continue;
            }

            if ($desiredMode === 'accessory_transfer') {
                $accessory[] = self::withMode($track, 'accessory_transfer', $utilityScore);

                continue;
            }

            $future[] = self::reject($track, 'Keep this visible for a later block.', $utilityScore);
        }

        foreach ($futureQueue as $track) {
            $skill = self::trackId($track);
            if ($skill !== '' && ! isset($seen[$skill]) && ! isset($rejectedSkills[$skill])) {
                $future[] = self::reject($track, 'Keep this visible for a later block.', self::utilityScore($track, 'future_queue', $input));
            }
        }

        foreach ($notRecommendedNow as $track) {
            $skill = self::trackId($track);
            if ($skill !== '' && ! isset($rejectedSkills[$skill])) {
                $notNow[] = self::reject($track, self::firstReason($track, 'Not selected for this block.'), self::utilityScore($track, 'not_now', $input));
            }
        }

        return new RoadmapSkillPortfolio(
            developmentTracks: self::dedupe($development),
            technicalPracticeTracks: self::dedupe($technical),
            accessoryTracks: self::dedupe($accessory),
            maintenanceTracks: self::dedupe($maintenance),
            foundationTracks: self::dedupe(self::annotateTracks($foundationTracks, 'foundation')),
            futureQueue: self::dedupe($future),
            notRecommendedNow: self::dedupe($notNow),
            optimizer: [
                'high_stress_development_cap' => $stressBudget->highStressDevelopmentLanes,
                'selected_high_stress_development_count' => $highStressCount,
                'utility_inputs' => [
                    'goal_priority',
                    'node_readiness',
                    'transfer',
                    'foundation_gap',
                    'motivation',
                    'synergy',
                    'pain_tissue_risk',
                    'high_stress_overlap',
                    'uncertainty',
                    'time_cost',
                    'redundancy',
                ],
                'selection_notes' => [
                    'Foundation modules are reserved before skill modules.',
                    'Selected goals raise utility for the nearest trainable bridge, but gates still apply.',
                ],
            ],
        );
    }

    /**
     * @param  list<array<string, mixed>>  $developmentTracks
     * @param  list<array<string, mixed>>  $technicalPracticeTracks
     * @param  list<array<string, mixed>>  $accessoryTracks
     * @param  list<array<string, mixed>>  $futureQueue
     * @return list<array{track: array<string, mixed>, source_mode: string, utility_score: int}>
     */
    private static function candidatePool(array $developmentTracks, array $technicalPracticeTracks, array $accessoryTracks, array $futureQueue): array
    {
        $candidates = [];

        foreach ([
            'development' => $developmentTracks,
            'technical_practice' => $technicalPracticeTracks,
            'accessory_transfer' => $accessoryTracks,
            'future_queue' => $futureQueue,
        ] as $mode => $tracks) {
            foreach ($tracks as $track) {
                $candidates[] = [
                    'track' => $track,
                    'source_mode' => $mode,
                    'utility_score' => 0,
                ];
            }
        }

        return $candidates;
    }

    private static function desiredMode(array $track, string $sourceMode, RoadmapInput $input): string
    {
        $skill = self::trackId($track);

        if (self::isPrimarySelection($track, $input)) {
            return 'development';
        }

        if (in_array($skill, self::TECHNICAL_DEFAULTS, true)) {
            return 'technical_practice';
        }

        if (in_array($skill, self::ACCESSORY_DEFAULTS, true)) {
            return 'accessory_transfer';
        }

        if (in_array($skill, $input->secondaryInterests, true) && in_array($skill, self::HIGH_STRESS_SKILLS, true)) {
            return 'development';
        }

        if ($sourceMode === 'development') {
            return 'development';
        }

        return $sourceMode === 'future_queue' ? 'future_queue' : $sourceMode;
    }

    private static function utilityScore(array $track, string $sourceMode, RoadmapInput $input): int
    {
        $skill = self::trackId($track);
        $score = 0;

        if ($skill === $input->selectedPrimaryGoal) {
            $score += 60;
        } elseif (in_array($skill, $input->secondaryInterests, true)) {
            $score += 42;
        } elseif (in_array($skill, $input->longTermAspirations, true)) {
            $score += 25;
        }

        $score += match ($sourceMode) {
            'development' => 20,
            'technical_practice' => 14,
            'accessory_transfer' => 10,
            default => 0,
        };

        $readiness = is_array($track['node_readiness'] ?? null) ? $track['node_readiness'] : [];
        $score += (int) round((self::intOrNull($readiness['readiness_score'] ?? null) ?? 45) / 2);
        $confidence = is_numeric($readiness['confidence'] ?? null) ? (float) $readiness['confidence'] : 0.5;
        $score -= (int) round((1 - $confidence) * 20);
        $score -= self::estimatedModuleMinutes($track) > 35 ? 8 : 0;

        if (self::nodeStatus($track) === 'blocked_pending_input') {
            $score -= 30;
        }

        return max(0, $score);
    }

    private static function withMode(array $track, string $mode, int $utilityScore, string $note = ''): array
    {
        $track['mode'] = $mode;

        if (is_array($track['node_readiness'] ?? null)) {
            $modules = RoadmapTrainingModuleGenerator::fromReadinessPayload($track['node_readiness'], $mode);
            if ($modules !== []) {
                $track['modules'] = $modules;
                $track['weekly_exposures'] = self::moduleWeeklyExposures($modules);
                $track['estimated_minutes_per_week'] = self::estimatedModuleMinutesForModules($modules);
                $track['primary_stress_axes'] = self::moduleStressAxes($modules, self::stringList($track['primary_stress_axes'] ?? []));
            }
        }

        $track['optimizer'] = [
            'utility_score' => $utilityScore,
            'selected_mode' => $mode,
            'note' => $note,
        ];

        return $track;
    }

    private static function reject(array $track, string $reason, int $utilityScore): array
    {
        $track['why_not_higher_priority'] = self::unique([
            ...self::stringList($track['why_not_higher_priority'] ?? []),
            $reason,
        ]);
        $track['why_included'] = self::unique(self::stringList($track['why_included'] ?? []));
        $track['optimizer'] = [
            'utility_score' => $utilityScore,
            'rejected_reason' => $reason,
        ];

        return $track;
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     */
    private static function developmentCompatibility(array $candidate, array $tracks, RoadmapStressBudget $stressBudget): ?RoadmapModuleCompatibility
    {
        $candidateModule = self::firstModule($candidate);
        if ($candidateModule === null) {
            return null;
        }

        foreach ($tracks as $track) {
            $module = self::firstModule($track);
            if ($module === null) {
                continue;
            }

            $compatibility = RoadmapModuleCompatibilityEngine::compare($module, $candidateModule, $stressBudget);
            if (in_array($compatibility->state, ['red', 'orange'], true)) {
                return $compatibility;
            }
        }

        return null;
    }

    private static function isPrimarySelection(array $track, RoadmapInput $input): bool
    {
        return self::trackId($track) !== '' && self::trackId($track) === $input->selectedPrimaryGoal;
    }

    private static function isHighStressTrack(array $track): bool
    {
        foreach (self::arrayList($track['modules'] ?? []) as $module) {
            if (in_array($module['intensity_tier'] ?? null, ['high', 'max'], true)) {
                return true;
            }
        }

        return in_array(self::trackId($track), self::HIGH_STRESS_SKILLS, true);
    }

    private static function technicalModeFor(array $track): string
    {
        return in_array(self::trackId($track), self::ACCESSORY_DEFAULTS, true)
            ? 'accessory_transfer'
            : 'technical_practice';
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @return list<array<string, mixed>>
     */
    private static function annotateTracks(array $tracks, string $mode): array
    {
        return array_map(
            static fn (array $track): array => self::withMode($track, $mode, self::intOrNull($track['optimizer']['utility_score'] ?? null) ?? 0),
            $tracks,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @return list<array<string, mixed>>
     */
    private static function dedupe(array $tracks): array
    {
        $deduped = [];

        foreach ($tracks as $track) {
            $skill = self::trackId($track);
            if ($skill === '' || isset($deduped[$skill])) {
                continue;
            }

            $deduped[$skill] = $track;
        }

        return array_values($deduped);
    }

    private static function nodeStatus(array $track): string
    {
        return self::stringValue($track['node_readiness']['status'] ?? null, '');
    }

    private static function developmentDeferralReason(array $track): ?string
    {
        if (self::canTrainFoundationBridge($track)) {
            return null;
        }

        $status = self::nodeStatus($track);

        if ($status === 'bridge_recommended' && self::nodeReadinessScore($track) < 35) {
            return 'Selected target stays visible, but the nearest bridge should be trained before this becomes a development lane.';
        }

        if ($status === 'long_term_visible') {
            return 'Current readiness keeps this target in the future queue until the next objective test improves.';
        }

        if ($status === '' && self::isHighStressTrack($track) && self::nodeReadinessScore($track) < 60) {
            return 'High-stress targets need more readiness before entering development.';
        }

        return null;
    }

    private static function canTrainFoundationBridge(array $track): bool
    {
        return in_array(self::trackId($track), self::FOUNDATION_BRIDGE_SKILLS, true)
            && self::stringList($track['node_readiness']['blockers'] ?? []) === [];
    }

    private static function nodeReadinessScore(array $track): int
    {
        return self::intOrNull($track['node_readiness']['readiness_score'] ?? null) ?? 0;
    }

    private static function trackId(array $track): string
    {
        return self::stringValue($track['skill_track_id'] ?? null, '');
    }

    private static function firstReason(array $track, string $fallback): string
    {
        return self::stringList($track['why_not_higher_priority'] ?? [])[0]
            ?? self::stringList($track['why_included'] ?? [])[0]
            ?? $fallback;
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function firstModule(array $track): ?array
    {
        foreach (self::arrayList($track['modules'] ?? []) as $module) {
            return $module;
        }

        return null;
    }

    private static function estimatedModuleMinutes(array $track): int
    {
        return self::intOrNull($track['estimated_minutes_per_week'] ?? null) ?? 0;
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     */
    private static function estimatedModuleMinutesForModules(array $modules): int
    {
        return array_sum(array_map(
            static function (array $module): int {
                $time = is_array($module['time_cost_minutes'] ?? null) ? $module['time_cost_minutes'] : [];
                $exposures = is_array($module['exposure_targets'] ?? null) ? $module['exposure_targets'] : [];
                $min = self::intOrNull($time['min'] ?? null) ?? 0;
                $max = self::intOrNull($time['max'] ?? null) ?? $min;
                $target = self::intOrNull($exposures['target_per_week'] ?? null) ?? 1;

                return (int) round((($min + $max) / 2) * $target);
            },
            $modules,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     */
    private static function moduleWeeklyExposures(array $modules): int
    {
        return array_sum(array_map(
            static function (array $module): int {
                $exposures = is_array($module['exposure_targets'] ?? null) ? $module['exposure_targets'] : [];

                return self::intOrNull($exposures['target_per_week'] ?? null) ?? 1;
            },
            $modules,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     * @param  list<string>  $fallback
     * @return list<string>
     */
    private static function moduleStressAxes(array $modules, array $fallback): array
    {
        $axes = [];

        foreach ($modules as $module) {
            $stressVector = is_array($module['stress_vector'] ?? null) ? $module['stress_vector'] : [];

            foreach ($stressVector as $axis => $load) {
                if ((self::intOrNull($load) ?? 0) > 0) {
                    $axes[] = (string) $axis;
                }
            }
        }

        return self::unique($axes === [] ? $fallback : $axes);
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
    private static function unique(array $values): array
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
