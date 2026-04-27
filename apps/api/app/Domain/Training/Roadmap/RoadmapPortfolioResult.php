<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapPortfolioResult
{
    public const string VERSION = 'roadmap.portfolio.v3';

    /**
     * @param  array<string, mixed>  $payload
     */
    private function __construct(
        private array $payload,
    ) {}

    public static function empty(): self
    {
        return self::fromRoadmapSuggestions(RoadmapResult::empty()->toArray());
    }

    /**
     * @param  array<string, mixed>  $suggestions
     */
    public static function fromRoadmapSuggestions(array $suggestions, ?RoadmapInput $input = null): self
    {
        $goalCandidates = self::goalCandidates($suggestions['goal_candidates'] ?? []);
        $nodeReadiness = $input === null ? [] : NodeReadinessCalculator::fromInput($input);
        $blockedTracks = self::blockedTracks($goalCandidates, $suggestions, $input);
        $blockedIds = self::trackIds($blockedTracks);
        $developmentTracks = self::tracksFromCandidates(
            self::filterCandidates($goalCandidates['primary'], excludeBlocked: true),
            'development',
            $suggestions,
            $nodeReadiness,
        );
        $technicalPracticeTracks = self::tracksFromCandidates(
            self::technicalPracticeCandidates($goalCandidates, $input),
            'technical_practice',
            $suggestions,
            $nodeReadiness,
        );
        $accessoryTracks = self::tracksFromCandidates(
            self::accessoryCandidates($goalCandidates, $input),
            'accessory_transfer',
            $suggestions,
            $nodeReadiness,
        );
        $foundationTracks = self::tracksFromCandidates($goalCandidates['foundation'], 'foundation', $suggestions, $nodeReadiness);
        $maintenanceTracks = self::tracksFromCandidates(
            array_values(array_filter(
                $goalCandidates['foundation'],
                static fn (array $candidate): bool => ($candidate['role'] ?? null) === 'owned_foundation',
            )),
            'maintenance',
            $suggestions,
            $nodeReadiness,
        );
        $futureQueue = self::dedupeTracks([
            ...self::tracksFromCandidates(
                self::futureCandidates($goalCandidates, $blockedIds),
                'future_queue',
                $suggestions,
                $nodeReadiness,
            ),
            ...self::tracksFromGoals(
                self::arrayList($suggestions['deferred_goals'] ?? []),
                'future_queue',
            ),
        ]);
        $notRecommendedNow = self::dedupeTracks([
            ...self::tracksFromCandidates(self::futureCandidates($goalCandidates, $blockedIds), 'not_now', $suggestions, $nodeReadiness),
            ...self::tracksFromGoals(self::arrayList($suggestions['deferred_goals'] ?? []), 'not_now'),
        ]);
        $foundationModules = $input === null
            ? []
            : RoadmapTrainingModuleGenerator::foundationModules($input, self::foundationTargetSkills($goalCandidates, $input));
        $stressBudget = $input === null
            ? RoadmapStressBudgetFactory::empty()
            : RoadmapStressBudgetFactory::fromInput($input);

        if ($input !== null) {
            $optimized = RoadmapSkillPortfolioOptimizer::fromTracks(
                input: $input,
                developmentTracks: $developmentTracks,
                technicalPracticeTracks: $technicalPracticeTracks,
                accessoryTracks: $accessoryTracks,
                maintenanceTracks: $maintenanceTracks,
                foundationTracks: $foundationTracks,
                futureQueue: $futureQueue,
                notRecommendedNow: $notRecommendedNow,
                stressBudget: $stressBudget,
            );
            $developmentTracks = $optimized->developmentTracks;
            $technicalPracticeTracks = $optimized->technicalPracticeTracks;
            $accessoryTracks = $optimized->accessoryTracks;
            $maintenanceTracks = $optimized->maintenanceTracks;
            $foundationTracks = $optimized->foundationTracks;
            $futureQueue = $optimized->futureQueue;
            $notRecommendedNow = $optimized->notRecommendedNow;
            $optimizer = $optimized->optimizer;
        } else {
            $optimizer = [
                'high_stress_development_cap' => 0,
                'selected_high_stress_development_count' => 0,
                'utility_inputs' => [],
                'selection_notes' => [],
            ];
        }

        $activeTracks = self::dedupeTracks([
            ...$developmentTracks,
            ...$technicalPracticeTracks,
            ...$accessoryTracks,
            ...$maintenanceTracks,
            ...$foundationTracks,
        ]);
        $maxSessions = self::maxSessions($input);
        $estimatedMinutes = self::estimatedMinutes($activeTracks) + self::estimatedModuleMinutes($foundationModules);
        $weeklySchedule = $input === null
            ? RoadmapWeeklyScheduler::empty()
            : RoadmapWeeklyScheduler::fromModules($input, $activeTracks, $foundationModules, $stressBudget);
        $phasePlan = $input === null
            ? RoadmapPhasePlan::empty()
            : RoadmapPhasePlan::fromPortfolio($input, $activeTracks, $foundationModules, $weeklySchedule);

        $pendingTests = $input === null
            ? []
            : RoadmapMicroTestRequestGenerator::fromInput($input, $goalCandidates);

        $payload = [
            'version' => self::VERSION,
            'source_version' => self::stringValue($suggestions['version'] ?? null, RoadmapResult::VERSION),
            'summary' => self::stringValue(
                $suggestions['summary'] ?? null,
                'Complete the baseline tests to unlock a useful roadmap.',
            ),
            'active_skill_portfolio' => [
                'development_tracks' => $developmentTracks,
                'technical_practice_tracks' => $technicalPracticeTracks,
                'accessory_tracks' => $accessoryTracks,
                'maintenance_tracks' => $maintenanceTracks,
                'foundation_tracks' => $foundationTracks,
                'foundation_modules' => $foundationModules,
                'future_queue' => $futureQueue,
                'weekly_schedule' => $weeklySchedule,
                'stress_ledger' => self::stressLedger($activeTracks, $maxSessions, $stressBudget),
                'stress_budget' => $stressBudget->toArray(),
                'module_compatibility' => self::moduleCompatibility($activeTracks, $stressBudget),
                'optimizer' => $optimizer,
                'phase_plan' => $phasePlan,
                'time_ledger' => self::timeLedger($input, $maxSessions, $estimatedMinutes),
                'explanation' => self::portfolioExplanation($suggestions, $developmentTracks, $futureQueue, $blockedTracks),
            ],
            'onboarding_goal_choices' => [
                'development' => self::trackIds($developmentTracks),
                'technical_practice' => self::trackIds($technicalPracticeTracks),
                'accessories' => self::trackIds($accessoryTracks),
                'future' => self::trackIds($futureQueue),
                'blocked' => self::trackIds($blockedTracks),
            ],
            'foundation_layer' => [
                'summary' => self::foundationSummary($foundationTracks),
                'focus_areas' => self::stringList($suggestions['base_focus_areas'] ?? []),
                'tracks' => $foundationTracks,
                'modules' => $foundationModules,
            ],
            'long_term_aspirations' => $futureQueue,
            'not_recommended_now' => array_values(array_filter(
                $notRecommendedNow,
                static fn (array $track): bool => ! in_array($track['skill_track_id'], $blockedIds, true),
            )),
            'blocked' => $blockedTracks,
            'pending_tests' => $pendingTests,
            'goal_candidates' => $goalCandidates,
        ];

        return new self($payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }

    /**
     * @return array{
     *     primary: list<array<string, mixed>>,
     *     secondary: list<array<string, mixed>>,
     *     accessories: list<array<string, mixed>>,
     *     future: list<array<string, mixed>>,
     *     foundation: list<array<string, mixed>>
     * }
     */
    private static function goalCandidates(mixed $value): array
    {
        $source = is_array($value) ? $value : [];

        return [
            'primary' => self::arrayList($source['primary'] ?? []),
            'secondary' => self::arrayList($source['secondary'] ?? []),
            'accessories' => self::arrayList($source['accessories'] ?? []),
            'future' => self::arrayList($source['future'] ?? []),
            'foundation' => self::arrayList($source['foundation'] ?? []),
        ];
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $goalCandidates
     * @param  array<string, mixed>  $suggestions
     * @return list<array<string, mixed>>
     */
    private static function blockedTracks(array $goalCandidates, array $suggestions, ?RoadmapInput $input): array
    {
        $blockedCandidates = [];

        foreach ($goalCandidates as $candidates) {
            foreach ($candidates as $candidate) {
                if (($candidate['role'] ?? null) === 'blocked' || ($candidate['status'] ?? null) === 'blocked') {
                    $blockedCandidates[] = $candidate;
                }
            }
        }

        $tracks = self::tracksFromCandidates($blockedCandidates, 'blocked', $suggestions);
        $painLevel = self::intOrNull($input?->painFlags['level'] ?? null);

        if ($painLevel !== null && $painLevel >= 7 && $input?->selectedPrimaryGoal !== null) {
            $tracks = array_map(
                static function (array $track) use ($input): array {
                    if (($track['skill_track_id'] ?? null) !== $input->selectedPrimaryGoal) {
                        return $track;
                    }

                    $track['why_included'] = self::uniqueStrings([
                        ...self::stringList($track['why_included'] ?? []),
                        'Pain is high enough that this target should stay blocked from loaded progression right now.',
                    ]);

                    return $track;
                },
                $tracks,
            );

            $selectedCandidate = self::candidateBySkill($goalCandidates, $input->selectedPrimaryGoal);
            $tracks[] = self::trackFromCandidate(
                $selectedCandidate ?? ['skill' => $input->selectedPrimaryGoal, 'label' => self::labelFromSkill($input->selectedPrimaryGoal)],
                'blocked',
                self::goalBySkill($suggestions, $input->selectedPrimaryGoal),
                extraReasons: ['Pain is high enough that this target should stay blocked from loaded progression right now.'],
            );
        }

        return self::dedupeTracks($tracks);
    }

    /**
     * @param  list<array<string, mixed>>  $candidates
     * @return list<array<string, mixed>>
     */
    private static function filterCandidates(array $candidates, bool $excludeBlocked): array
    {
        if (! $excludeBlocked) {
            return $candidates;
        }

        return array_values(array_filter(
            $candidates,
            static fn (array $candidate): bool => ($candidate['role'] ?? null) !== 'blocked'
                && ($candidate['status'] ?? null) !== 'blocked',
        ));
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $goalCandidates
     * @return list<array<string, mixed>>
     */
    private static function technicalPracticeCandidates(array $goalCandidates, ?RoadmapInput $input): array
    {
        $secondaryInterests = $input?->secondaryInterests ?? [];

        return array_values(array_filter(
            [
                ...$goalCandidates['secondary'],
                ...$goalCandidates['accessories'],
            ],
            static fn (array $candidate): bool => in_array($candidate['skill'] ?? null, $secondaryInterests, true)
                && in_array($candidate['skill'] ?? null, ['handstand', 'press_to_handstand'], true)
                && ($candidate['role'] ?? null) !== 'blocked'
                && ($candidate['status'] ?? null) !== 'blocked',
        ));
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $goalCandidates
     * @return list<array<string, mixed>>
     */
    private static function accessoryCandidates(array $goalCandidates, ?RoadmapInput $input): array
    {
        $technicalPracticeIds = array_map(
            static fn (array $candidate): string => self::stringValue($candidate['skill'] ?? null, ''),
            self::technicalPracticeCandidates($goalCandidates, $input),
        );

        return array_values(array_filter(
            $goalCandidates['accessories'],
            static fn (array $candidate): bool => ! in_array($candidate['skill'] ?? null, $technicalPracticeIds, true)
                && ($candidate['role'] ?? null) !== 'blocked'
                && ($candidate['status'] ?? null) !== 'blocked',
        ));
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $goalCandidates
     * @param  list<string>  $blockedIds
     * @return list<array<string, mixed>>
     */
    private static function futureCandidates(array $goalCandidates, array $blockedIds): array
    {
        return array_values(array_filter(
            $goalCandidates['future'],
            static fn (array $candidate): bool => ! in_array($candidate['skill'] ?? null, $blockedIds, true)
                && ($candidate['role'] ?? null) !== 'blocked'
                && ($candidate['status'] ?? null) !== 'blocked',
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $candidates
     * @param  array<string, mixed>  $suggestions
     * @param  array<string, NodeReadiness>  $nodeReadiness
     * @return list<array<string, mixed>>
     */
    private static function tracksFromCandidates(array $candidates, string $mode, array $suggestions, array $nodeReadiness = []): array
    {
        $tracks = [];

        foreach ($candidates as $candidate) {
            $skill = self::stringValue($candidate['skill'] ?? null, '');
            if ($skill === '') {
                continue;
            }

            $tracks[] = self::trackFromCandidate($candidate, $mode, self::goalBySkill($suggestions, $skill), nodeReadiness: $nodeReadiness[$skill] ?? null);
        }

        return self::dedupeTracks($tracks);
    }

    /**
     * @param  list<array<string, mixed>>  $goals
     * @return list<array<string, mixed>>
     */
    private static function tracksFromGoals(array $goals, string $mode): array
    {
        $tracks = [];

        foreach ($goals as $goal) {
            $skill = self::stringValue($goal['skill'] ?? null, '');
            if ($skill === '') {
                continue;
            }

            $tracks[] = self::trackFromCandidate([
                'skill' => $skill,
                'label' => self::stringValue($goal['label'] ?? null, self::labelFromSkill($skill)),
                'reason' => self::stringValue($goal['explanation'] ?? null, 'Keep this visible for a later block.'),
                'confidence' => self::numberOrNull($goal['confidence']['score'] ?? null),
                'stress_tags' => GoalStressCatalog::tags($skill),
            ], $mode, $goal);
        }

        return self::dedupeTracks($tracks);
    }

    /**
     * @param  array<string, mixed>  $candidate
     * @param  array<string, mixed>|null  $goal
     * @param  list<string>  $extraReasons
     * @return array<string, mixed>
     */
    private static function trackFromCandidate(
        array $candidate,
        string $mode,
        ?array $goal = null,
        array $extraReasons = [],
        ?NodeReadiness $nodeReadiness = null,
    ): array {
        $skill = self::stringValue($candidate['skill'] ?? null, '');
        $label = self::stringValue($candidate['label'] ?? $goal['label'] ?? null, self::labelFromSkill($skill));
        $stressAxes = self::stringList($candidate['stress_tags'] ?? GoalStressCatalog::tags($skill));
        $reason = self::stringValue($candidate['reason'] ?? $goal['explanation'] ?? null, 'Current data keeps this track visible.');
        $blockers = self::stringList($candidate['blockers'] ?? []);
        $unlockConditions = self::stringList($candidate['unlock_conditions'] ?? []);
        $whyNotHigher = array_values(array_filter([
            self::stringValue($candidate['compatibility_reason'] ?? null, ''),
            ...($mode === 'future_queue' || $mode === 'not_now' || $mode === 'blocked' ? $unlockConditions : []),
        ]));

        $track = [
            'skill_track_id' => $skill,
            'display_name' => $label,
            'current_node' => self::nodeValue($goal['current_progression_node'] ?? null, "{$skill}.current", 'Current placement'),
            'next_node' => self::nodeValue($goal['next_node'] ?? null, "{$skill}.next", self::stringValue($candidate['next_gate'] ?? null, 'Next useful progression')),
            'target_node' => self::nodeValue($goal['next_milestone'] ?? null, "{$skill}.target", $label),
            'mode' => $mode,
            'weekly_exposures' => self::weeklyExposures($mode),
            'estimated_minutes_per_week' => self::minutesForMode($mode),
            'primary_stress_axes' => $stressAxes,
            'eta_to_next_node' => self::etaValue($goal['eta_range'] ?? null),
            'confidence' => self::confidenceValue($candidate['confidence'] ?? $goal['confidence'] ?? null, $reason),
            'modules' => [],
            'why_included' => self::uniqueStrings([$reason, ...$blockers, ...$extraReasons]),
            'why_not_higher_priority' => self::uniqueStrings($whyNotHigher),
        ];

        if ($nodeReadiness === null) {
            return $track;
        }

        $readiness = $nodeReadiness->toArray();
        $modules = RoadmapTrainingModuleGenerator::fromNodeReadiness($nodeReadiness, $mode);

        $track['current_node'] = $readiness['current_node'];
        $track['next_node'] = $readiness['next_node'];
        $track['target_node'] = $readiness['target_node'];
        $track['eta_to_next_node'] = self::etaValue($readiness['eta_to_next_node']);
        $track['confidence'] = self::confidenceValue($readiness['confidence'], $reason);
        $track['modules'] = $modules;
        $track['node_readiness'] = $readiness;
        $track['mode_detail'] = [
            'node_status' => $readiness['status'],
            'readiness_score' => $readiness['readiness_score'],
            'confidence' => $readiness['confidence'],
        ];

        if ($modules !== []) {
            $track['weekly_exposures'] = self::moduleWeeklyExposures($modules);
            $track['estimated_minutes_per_week'] = self::estimatedModuleMinutes($modules);
            $track['primary_stress_axes'] = self::moduleStressAxes($modules, $stressAxes);
        }

        return $track;
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $goalCandidates
     * @return list<string>
     */
    private static function foundationTargetSkills(array $goalCandidates, RoadmapInput $input): array
    {
        $skills = array_values(array_filter([
            $input->selectedPrimaryGoal,
            ...$input->secondaryInterests,
            ...$input->longTermAspirations,
        ]));

        foreach (['primary', 'secondary', 'accessories', 'future'] as $bucket) {
            foreach ($goalCandidates[$bucket] as $candidate) {
                $skill = self::stringValue($candidate['skill'] ?? null, '');
                if ($skill !== '') {
                    $skills[] = $skill;
                }
            }
        }

        return array_values(array_unique($skills));
    }

    /**
     * @param  array<string, list<array<string, mixed>>>  $goalCandidates
     * @return array<string, mixed>|null
     */
    private static function candidateBySkill(array $goalCandidates, string $skill): ?array
    {
        foreach ($goalCandidates as $candidates) {
            foreach ($candidates as $candidate) {
                if (($candidate['skill'] ?? null) === $skill) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $suggestions
     * @return array<string, mixed>|null
     */
    private static function goalBySkill(array $suggestions, string $skill): ?array
    {
        foreach (['primary_goal', 'compatible_secondary_goal'] as $key) {
            $goal = $suggestions[$key] ?? null;
            if (is_array($goal) && ($goal['skill'] ?? null) === $skill) {
                return $goal;
            }
        }

        foreach (self::arrayList($suggestions['deferred_goals'] ?? []) as $goal) {
            if (($goal['skill'] ?? null) === $skill) {
                return $goal;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @return list<array<string, mixed>>
     */
    private static function dedupeTracks(array $tracks): array
    {
        $deduped = [];

        foreach ($tracks as $track) {
            $skill = self::stringValue($track['skill_track_id'] ?? null, '');
            if ($skill === '' || isset($deduped[$skill])) {
                continue;
            }

            $deduped[$skill] = $track;
        }

        return array_values($deduped);
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @return list<string>
     */
    private static function trackIds(array $tracks): array
    {
        return array_values(array_map(
            static fn (array $track): string => (string) $track['skill_track_id'],
            $tracks,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     */
    private static function estimatedMinutes(array $tracks): int
    {
        return array_sum(array_map(
            static fn (array $track): int => self::intOrNull($track['estimated_minutes_per_week'] ?? null) ?? 0,
            $tracks,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     */
    private static function estimatedModuleMinutes(array $modules): int
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

        return self::uniqueStrings($axes === [] ? $fallback : $axes);
    }

    private static function maxSessions(?RoadmapInput $input): int
    {
        if ($input === null) {
            return 0;
        }

        $sessions = self::intOrNull($input->trainingContext['weekly_session_goal'] ?? null) ?? 3;

        return max(1, min(6, $sessions));
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @return array{axes: list<array<string, mixed>>, notes: list<string>}
     */
    private static function stressLedger(array $tracks, int $maxSessions, RoadmapStressBudget $stressBudget): array
    {
        $loads = [];

        foreach ($tracks as $track) {
            foreach (self::arrayList($track['modules'] ?? []) as $module) {
                $stressVector = is_array($module['stress_vector'] ?? null) ? $module['stress_vector'] : [];
                $exposures = is_array($module['exposure_targets'] ?? null) ? $module['exposure_targets'] : [];
                $targetExposures = max(1, self::intOrNull($exposures['target_per_week'] ?? null) ?? 1);

                foreach ($stressVector as $axis => $load) {
                    $loads[(string) $axis] = ($loads[(string) $axis] ?? 0) + max(1, (self::intOrNull($load) ?? 0) * $targetExposures);
                }
            }

            if (self::arrayList($track['modules'] ?? []) !== []) {
                continue;
            }

            foreach (self::stringList($track['primary_stress_axes'] ?? []) as $axis) {
                $loads[$axis] = ($loads[$axis] ?? 0) + max(1, self::intOrNull($track['weekly_exposures'] ?? null) ?? 1);
            }
        }

        $axes = [];
        $budget = max(2, $maxSessions);
        foreach ($loads as $axis => $load) {
            $weeklyBudget = $stressBudget->weeklyBudget[$axis] ?? $budget;

            $axes[] = [
                'axis' => $axis,
                'load' => $load,
                'budget' => $weeklyBudget,
                'status' => $load > $weeklyBudget ? 'yellow' : 'green',
            ];
        }

        return [
            'axes' => $axes,
            'notes' => $axes === [] ? ['No portfolio stress has been assigned yet.'] : ['Portfolio stress is estimated from current track roles.'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @return list<array<string, mixed>>
     */
    private static function moduleCompatibility(array $tracks, RoadmapStressBudget $stressBudget): array
    {
        $modules = [];

        foreach ($tracks as $track) {
            foreach (self::arrayList($track['modules'] ?? []) as $module) {
                $modules[] = $module;
            }
        }

        $compatibility = [];
        $count = count($modules);

        for ($left = 0; $left < $count; $left++) {
            for ($right = $left + 1; $right < $count; $right++) {
                $compatibility[] = RoadmapModuleCompatibilityEngine::compare(
                    $modules[$left],
                    $modules[$right],
                    $stressBudget,
                )->toArray();
            }
        }

        return $compatibility;
    }

    /**
     * @return array{max_sessions_per_week: int, estimated_minutes_per_week: int, remaining_minutes_per_week: int, notes: list<string>}
     */
    private static function timeLedger(?RoadmapInput $input, int $maxSessions, int $estimatedMinutes): array
    {
        $sessionMinutes = self::intOrNull($input?->trainingContext['preferred_session_minutes'] ?? null) ?? 45;
        $weeklyBudget = $maxSessions * $sessionMinutes;

        return [
            'max_sessions_per_week' => $maxSessions,
            'estimated_minutes_per_week' => $estimatedMinutes,
            'remaining_minutes_per_week' => max(0, $weeklyBudget - $estimatedMinutes),
            'notes' => $input === null ? [] : ['Session duration uses the current profile value or a conservative default.'],
        ];
    }

    /**
     * @param  array<string, mixed>  $suggestions
     * @param  list<array<string, mixed>>  $developmentTracks
     * @param  list<array<string, mixed>>  $futureQueue
     * @param  list<array<string, mixed>>  $blockedTracks
     * @return array{summary: string, why_this_mix: list<string>, watch_out_for: list<string>, fallback: string}
     */
    private static function portfolioExplanation(array $suggestions, array $developmentTracks, array $futureQueue, array $blockedTracks): array
    {
        return [
            'summary' => self::stringValue($suggestions['explanation']['summary'] ?? null, self::stringValue($suggestions['summary'] ?? null, 'Complete the baseline tests to unlock a useful roadmap.')),
            'why_this_mix' => $developmentTracks === []
                ? ['Complete more baseline data before loading a development portfolio.']
                : ['Development tracks come from the current roadmap candidates while compatibility fields remain available.'],
            'watch_out_for' => $blockedTracks === []
                ? self::stringList($suggestions['explanation']['watch_out_for'] ?? [])
                : ['Blocked tracks should stay out of loaded progression until the blocker changes.'],
            'fallback' => self::stringValue($suggestions['explanation']['fallback'] ?? null, $futureQueue === [] ? 'Build the foundation layer first.' : 'Keep advanced goals visible while training the nearest bridge.'),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $foundationTracks
     */
    private static function foundationSummary(array $foundationTracks): string
    {
        if ($foundationTracks === []) {
            return 'Complete the baseline tests to place the foundation layer.';
        }

        return 'Foundation tracks stay visible so skill work has a recoverable base.';
    }

    private static function weeklyExposures(string $mode): int
    {
        return match ($mode) {
            'development', 'technical_practice', 'foundation' => 2,
            'accessory_transfer', 'maintenance' => 1,
            default => 0,
        };
    }

    private static function minutesForMode(string $mode): int
    {
        return match ($mode) {
            'development' => 30,
            'technical_practice', 'foundation' => 15,
            'accessory_transfer' => 12,
            'maintenance' => 10,
            default => 0,
        };
    }

    /**
     * @return array{id: string, label: string}
     */
    private static function nodeValue(mixed $value, string $fallbackId, string $fallbackLabel): array
    {
        $source = is_array($value) ? $value : [];

        return [
            'id' => self::stringValue($source['id'] ?? null, $fallbackId),
            'label' => self::stringValue($source['label'] ?? null, $fallbackLabel),
        ];
    }

    /**
     * @return array{min_weeks: int|null, max_weeks: int|null, p50_weeks: int|null, p80_weeks: int|null, label: string, confidence: float|null, modifiers: list<string>}
     */
    private static function etaValue(mixed $value): array
    {
        $source = is_array($value) ? $value : [];

        return [
            'min_weeks' => self::intOrNull($source['min_weeks'] ?? null),
            'max_weeks' => self::intOrNull($source['max_weeks'] ?? null),
            'p50_weeks' => self::intOrNull($source['p50_weeks'] ?? null),
            'p80_weeks' => self::intOrNull($source['p80_weeks'] ?? null),
            'label' => self::stringValue($source['label'] ?? null, 'Needs more evidence'),
            'confidence' => self::numberOrNull($source['confidence'] ?? null),
            'modifiers' => self::stringList($source['modifiers'] ?? []),
        ];
    }

    /**
     * @return array{level: string, score: float|null, reasons: list<string>}
     */
    private static function confidenceValue(mixed $value, string $fallbackReason): array
    {
        if (is_array($value)) {
            return [
                'level' => self::stringValue($value['level'] ?? null, 'low'),
                'score' => self::numberOrNull($value['score'] ?? null),
                'reasons' => self::stringList($value['reasons'] ?? [$fallbackReason]),
            ];
        }

        $score = self::numberOrNull($value);

        return [
            'level' => match (true) {
                $score === null => 'low',
                $score >= 0.75 => 'high',
                $score >= 0.55 => 'medium',
                default => 'low',
            },
            'score' => $score,
            'reasons' => [$fallbackReason],
        ];
    }

    private static function labelFromSkill(string $skill): string
    {
        return str_replace(' ', '-', ucwords(str_replace('_', ' ', $skill)));
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private static function uniqueStrings(array $values): array
    {
        return array_values(array_unique(array_filter($values, static fn (mixed $value): bool => is_string($value) && $value !== '')));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function arrayList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, is_array(...)));
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

    private static function stringValue(mixed $value, string $fallback): string
    {
        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function numberOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
