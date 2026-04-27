<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapResult
{
    public const string VERSION = 'roadmap.v2';

    /**
     * @param  array<string, mixed>  $payload
     */
    private function __construct(
        private array $payload,
    ) {}

    public static function empty(bool $includeIntermediate = false): self
    {
        $payload = [
            'version' => self::VERSION,
            'level' => 'foundation',
            'summary' => 'Complete the baseline tests to unlock a useful roadmap.',
            'body_context' => ['notes' => []],
            'base_focus_areas' => [],
            'unlocked_tracks' => [],
            'bridge_tracks' => [],
            'long_term_tracks' => [],
            'deferred_tracks' => [],
            'primary_goal' => null,
            'compatible_secondary_goal' => null,
            'foundation_lane' => self::foundationLane([]),
            'deferred_goals' => [],
            'current_progression_node' => self::node('foundation.current', 'Baseline not placed yet'),
            'next_node' => self::node('foundation.next', 'Complete the assessment'),
            'next_milestone' => self::node('foundation.milestone', 'First personalized roadmap'),
            'eta_range' => self::etaRange(null, null, 'Complete assessment'),
            'confidence' => self::confidence('low', 0.0, ['Baseline data is incomplete.']),
            'blockers' => [],
            'unlock_conditions' => [],
            'compatibility_tags' => [],
            'explanation' => [
                'summary' => 'Complete the baseline tests to unlock a useful roadmap.',
                'primary_now' => 'No primary roadmap is selected yet.',
                'why_this_goal' => [],
                'what_is_missing' => ['Objective baseline data'],
                'this_block_should_improve' => ['Foundation strength'],
                'not_trained_yet' => [],
                'what_would_change_recommendation' => ['Completing the baseline tests will unlock a specific roadmap.'],
                'watch_out_for' => [],
                'fallback' => 'Start with foundation strength until the assessment is complete.',
            ],
            'domain_bottlenecks' => [],
            'current_block_focus' => [
                'label' => 'Current 4-8 week prescriptive block',
                'eta_range' => self::etaRange(null, null, 'Complete assessment'),
                'lanes' => ['foundation_strength'],
                'focus_areas' => ['Foundation strength'],
                'should_improve' => ['Foundation strength'],
                'retest_cadence' => ['session updates', 'weekly review', '4-6 week block retest'],
            ],
        ];

        if ($includeIntermediate) {
            $payload['intermediate'] = self::emptyIntermediate();
        }

        return new self($payload);
    }

    /**
     * @param  array<string, mixed>  $signals
     * @param  array<string, mixed>  $legacy
     */
    public static function fromTrackBuckets(RoadmapInput $input, array $signals, array $legacy, bool $includeIntermediate = false): self
    {
        $activeTracks = [
            ...self::trackList($legacy['unlocked_tracks'] ?? []),
            ...self::trackList($legacy['bridge_tracks'] ?? []),
        ];
        $longTermTracks = self::trackList($legacy['long_term_tracks'] ?? []);
        $deferredTracks = self::trackList($legacy['deferred_tracks'] ?? []);
        $trackCatalog = self::trackCatalog($activeTracks, $longTermTracks, $deferredTracks);
        $placements = BaselineNodeMapper::fromInput($input);
        $domainScores = DomainScoreCalculator::fromPlacements($placements, $input);
        $readiness = SkillReadinessCalculator::fromInput($input);
        $laneSelection = GoalLaneSelector::fromInput($input);
        $layerEstimate = RoadmapLayerEstimator::fromInput($input);
        $primaryReadiness = $laneSelection->primaryLane;
        $secondaryReadiness = $laneSelection->secondaryLane;
        $primaryTrack = self::trackForReadiness($primaryReadiness, $trackCatalog) ?? self::primaryTrack($input, $activeTracks);
        $secondaryTrack = self::trackForReadiness($secondaryReadiness, $trackCatalog) ?? self::secondaryTrack($input, $activeTracks, $primaryTrack);
        $baseFocusAreas = self::stringList($legacy['base_focus_areas'] ?? []);
        $primarySkill = $primaryReadiness?->skill ?? self::trackSkill($primaryTrack) ?? 'foundation';
        $primaryLabel = $primaryReadiness?->label ?? self::trackLabel($primaryTrack, 'Foundation strength');
        $etaRange = self::etaRangeFromEstimate($layerEstimate->primaryEta);
        $confidence = self::confidenceFromReadiness($primaryReadiness, $layerEstimate->primaryEta, $input);
        $currentNode = self::node("{$primarySkill}.current", self::currentNodeLabel($primaryTrack));
        $nextNode = self::node("{$primarySkill}.next", self::nextNodeLabel($primaryTrack));
        $nextMilestone = self::node("{$primarySkill}.milestone", self::nextMilestoneLabel($primaryTrack));
        $blockers = self::blockersFromReadiness($input, $primaryReadiness);
        $unlockConditions = self::unlockConditionsFromReadiness($primaryReadiness, $primaryTrack, $blockers);
        $compatibilityTags = self::compatibilityTags($primarySkill);
        $domainBottlenecks = self::domainBottlenecks($domainScores);
        $deferredGoals = self::deferredGoalsFromSelection($laneSelection->deferredGoals, $trackCatalog, $longTermTracks);
        $explanation = self::explanation(
            primaryLabel: $primaryLabel,
            primaryTrack: $primaryTrack,
            secondaryTrack: $secondaryTrack,
            primaryReadiness: $primaryReadiness,
            deferredGoals: $deferredGoals,
            domainBottlenecks: $domainBottlenecks,
            blockFocus: self::primaryDomainFocus($primarySkill),
            input: $input,
        );
        $payload = [
            ...$legacy,
            'version' => self::VERSION,
            'primary_goal' => $primaryReadiness === null && $primaryTrack === null ? null : self::goal(
                $primaryTrack ?? self::trackFromReadiness($primaryReadiness, $primarySkill, $primaryLabel),
                'primary',
                $currentNode,
                $nextNode,
                $nextMilestone,
                $etaRange,
                $confidence,
                $blockers,
                $unlockConditions,
                $compatibilityTags,
                $explanation['summary'],
            ),
            'compatible_secondary_goal' => $secondaryReadiness === null && $secondaryTrack === null ? null : self::goal(
                $secondaryTrack ?? self::trackFromReadiness($secondaryReadiness, $secondaryReadiness?->skill ?? 'secondary', $secondaryReadiness?->label ?? 'Secondary target'),
                'secondary',
                self::node((string) ($secondaryReadiness?->skill ?? self::trackSkill($secondaryTrack)).'.current', self::currentNodeLabel($secondaryTrack)),
                self::node((string) ($secondaryReadiness?->skill ?? self::trackSkill($secondaryTrack)).'.next', self::nextNodeLabel($secondaryTrack)),
                self::node((string) ($secondaryReadiness?->skill ?? self::trackSkill($secondaryTrack)).'.milestone', self::nextMilestoneLabel($secondaryTrack)),
                self::etaRangeFromEstimate($layerEstimate->currentBlock['eta']),
                self::confidenceFromReadiness($secondaryReadiness, $layerEstimate->currentBlock['eta'], $input),
                self::blockersFromReadiness($input, $secondaryReadiness),
                self::unlockConditionsFromReadiness($secondaryReadiness, $secondaryTrack, []),
                self::compatibilityTags((string) ($secondaryReadiness?->skill ?? self::trackSkill($secondaryTrack))),
                'This target can receive lighter exposure without replacing the primary roadmap.',
            ),
            'foundation_lane' => self::foundationLane($baseFocusAreas),
            'deferred_goals' => $deferredGoals,
            'current_progression_node' => $currentNode,
            'next_node' => $nextNode,
            'next_milestone' => $nextMilestone,
            'eta_range' => $etaRange,
            'confidence' => $confidence,
            'blockers' => $blockers,
            'unlock_conditions' => $unlockConditions,
            'compatibility_tags' => $compatibilityTags,
            'domain_bottlenecks' => $domainBottlenecks,
            'current_block_focus' => self::currentBlockFocus($layerEstimate, $baseFocusAreas, $primarySkill),
            'explanation' => $explanation,
        ];

        if ($includeIntermediate) {
            $payload['intermediate'] = self::intermediate(
                $input,
                $signals,
                $primaryTrack,
                $secondaryTrack,
                $placements,
                $domainScores,
                $readiness,
                $laneSelection,
                $layerEstimate,
            );
        }

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
     * @param  list<array<string, mixed>>  $tracks
     */
    private static function primaryTrack(RoadmapInput $input, array $tracks): ?array
    {
        if ($input->selectedPrimaryGoal !== null) {
            $track = self::findTrack($tracks, $input->selectedPrimaryGoal);

            if ($track !== null) {
                return $track;
            }
        }

        return $tracks[0] ?? null;
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     */
    private static function secondaryTrack(RoadmapInput $input, array $tracks, ?array $primaryTrack): ?array
    {
        $primarySkill = self::trackSkill($primaryTrack);

        foreach ($input->secondaryInterests as $interest) {
            if ($interest === $primarySkill) {
                continue;
            }

            $track = self::findTrack($tracks, $interest);
            if ($track !== null) {
                return $track;
            }
        }

        $compatibleSkills = self::stringList($primaryTrack['compatible_secondary_skills'] ?? []);
        foreach ($compatibleSkills as $skill) {
            $track = self::findTrack($tracks, $skill);
            if ($track !== null && self::trackSkill($track) !== $primarySkill) {
                return $track;
            }
        }

        foreach ($tracks as $track) {
            if (self::trackSkill($track) !== $primarySkill) {
                return $track;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     */
    private static function findTrack(array $tracks, string $skill): ?array
    {
        foreach ($tracks as $track) {
            if (self::trackSkill($track) === $skill) {
                return $track;
            }
        }

        return null;
    }

    /**
     * @param  list<array<string, mixed>>  $deferredTracks
     * @param  list<array<string, mixed>>  $longTermTracks
     * @return list<array<string, mixed>>
     */
    private static function deferredGoals(array $deferredTracks, array $longTermTracks): array
    {
        $tracks = $deferredTracks === [] ? $longTermTracks : $deferredTracks;

        return array_values(array_map(
            fn (array $track): array => self::goal(
                $track,
                'deferred',
                self::node((string) self::trackSkill($track).'.locked', 'Locked behind prerequisites'),
                self::node((string) self::trackSkill($track).'.next', self::nextNodeLabel($track)),
                self::node((string) self::trackSkill($track).'.milestone', self::nextMilestoneLabel($track)),
                self::etaRange(52, 104, '12-24+ months'),
                self::confidence('low', 0.42, ['Advanced skill timing depends on future retests.']),
                [],
                [],
                self::compatibilityTags((string) self::trackSkill($track)),
                'Deferred until the prerequisite base is stronger.',
            ),
            $tracks,
        ));
    }

    /**
     * @param  list<array<string, mixed>>  $activeTracks
     * @param  list<array<string, mixed>>  $longTermTracks
     * @param  list<array<string, mixed>>  $deferredTracks
     * @return array<string, array<string, mixed>>
     */
    private static function trackCatalog(array $activeTracks, array $longTermTracks, array $deferredTracks): array
    {
        $catalog = [];

        foreach ([...$activeTracks, ...$longTermTracks, ...$deferredTracks] as $track) {
            $skill = self::trackSkill($track);

            if ($skill !== null && ! isset($catalog[$skill])) {
                $catalog[$skill] = $track;
            }
        }

        return $catalog;
    }

    /**
     * @param  array<string, array<string, mixed>>  $catalog
     * @return array<string, mixed>|null
     */
    private static function trackForReadiness(?SkillReadiness $readiness, array $catalog): ?array
    {
        return $readiness === null ? null : ($catalog[$readiness->skill] ?? null);
    }

    /**
     * @return array<string, mixed>
     */
    private static function trackFromReadiness(?SkillReadiness $readiness, string $skill, string $label): array
    {
        return [
            'skill' => $readiness?->skill ?? $skill,
            'label' => $readiness?->label ?? $label,
            'reason' => $readiness?->softFactors[0] ?? 'Baseline data supports this placement.',
            'base_focus_areas' => self::primaryDomainFocus($readiness?->skill ?? $skill),
            'next_gate' => 'Build the next useful progression.',
            'compatible_secondary_skills' => [],
        ];
    }

    /**
     * @param  list<array{skill: string, label: string, reason: string, unlock_conditions: list<string>}>  $selectedDeferred
     * @param  array<string, array<string, mixed>>  $catalog
     * @param  list<array<string, mixed>>  $longTermTracks
     * @return list<array<string, mixed>>
     */
    private static function deferredGoalsFromSelection(array $selectedDeferred, array $catalog, array $longTermTracks): array
    {
        $goals = [];

        foreach ($selectedDeferred as $deferred) {
            $skill = $deferred['skill'];
            $track = $catalog[$skill] ?? self::trackFromReadiness(null, $skill, $deferred['label']);
            $reason = self::deferredReason($deferred['reason'], $deferred['unlock_conditions']);

            $goals[] = self::goal(
                $track,
                'deferred',
                self::node("{$skill}.locked", 'Locked behind prerequisites'),
                self::node("{$skill}.next", self::nextNodeLabel($track)),
                self::node("{$skill}.milestone", self::nextMilestoneLabel($track)),
                self::etaRange(52, 104, '12-24+ months'),
                self::confidence('low', 0.42, ['Advanced skill timing depends on future retests.']),
                [],
                self::unlockConditionsFromStrings($skill, $deferred['unlock_conditions']),
                self::compatibilityTags($skill),
                $reason,
            );
        }

        foreach (self::deferredGoals([], $longTermTracks) as $legacyGoal) {
            $skill = self::stringValue($legacyGoal['skill'] ?? null, '');

            if ($skill === '' || self::hasGoal($goals, $skill)) {
                continue;
            }

            $goals[] = $legacyGoal;
        }

        return $goals;
    }

    /**
     * @param  list<string>  $unlockConditions
     */
    private static function deferredReason(string $reason, array $unlockConditions): string
    {
        foreach ($unlockConditions as $condition) {
            if (str_contains(strtolower($condition), 'pain') && ! str_contains(strtolower($reason), 'pain')) {
                return "{$reason} Pain also keeps this lane deferred.";
            }
        }

        return $reason;
    }

    /**
     * @param  list<array<string, mixed>>  $goals
     */
    private static function hasGoal(array $goals, string $skill): bool
    {
        foreach ($goals as $goal) {
            if (($goal['skill'] ?? null) === $skill) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    private static function goal(
        array $track,
        string $lane,
        array $currentNode,
        array $nextNode,
        array $nextMilestone,
        array $etaRange,
        array $confidence,
        array $blockers,
        array $unlockConditions,
        array $compatibilityTags,
        string $explanation,
    ): array {
        return [
            'skill' => self::trackSkill($track),
            'label' => self::trackLabel($track, 'Roadmap goal'),
            'lane' => $lane,
            'current_progression_node' => $currentNode,
            'next_node' => $nextNode,
            'next_milestone' => $nextMilestone,
            'eta_range' => $etaRange,
            'confidence' => $confidence,
            'blockers' => $blockers,
            'unlock_conditions' => $unlockConditions,
            'compatibility_tags' => $compatibilityTags,
            'explanation' => $explanation,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function blockers(RoadmapInput $input): array
    {
        $blockers = [];
        $painLevel = $input->painFlags['level'] ?? null;

        if (is_int($painLevel) && $painLevel >= 4) {
            $blockers[] = [
                'key' => 'pain',
                'label' => 'Pain signal',
                'severity' => $painLevel >= 7 ? 'stop' : 'limit',
                'message' => 'Pain is high enough to hold progression until it settles.',
            ];
        }

        $mobilityChecks = is_array($input->goalModules['mobility_checks'] ?? null)
            ? $input->goalModules['mobility_checks']
            : [];

        foreach ($mobilityChecks as $key => $status) {
            if (! is_string($key) || ! in_array($status, ['limited', 'blocked', 'painful'], true)) {
                continue;
            }

            $blockers[] = [
                'key' => $key,
                'label' => str_replace('_', ' ', $key),
                'severity' => $status === 'painful' || $status === 'blocked' ? 'limit' : 'watch',
                'message' => ucfirst(str_replace('_', ' ', $key)).' is marked '.$status.', so progression should stay conservative.',
            ];
        }

        return $blockers;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function unlockConditions(?array $track, array $blockers): array
    {
        if ($track === null) {
            return [];
        }

        $conditions = [[
            'skill' => self::trackSkill($track),
            'label' => self::nextMilestoneLabel($track),
            'status' => 'next',
        ]];

        foreach ($blockers as $blocker) {
            $conditions[] = [
                'skill' => self::trackSkill($track),
                'label' => 'Resolve '.$blocker['label'],
                'status' => 'blocked',
            ];
        }

        return $conditions;
    }

    /**
     * @return list<string>
     */
    private static function compatibilityTags(string $skill): array
    {
        return match ($skill) {
            'handstand', 'handstand_push_up', 'press_to_handstand' => ['overhead', 'wrist_extension', 'skill_practice'],
            'front_lever', 'back_lever' => ['pull', 'straight_arm_pull', 'core_bodyline'],
            'planche' => ['push', 'straight_arm_push', 'wrist_extension'],
            'muscle_up' => ['pull', 'dip_support', 'transition'],
            'one_arm_pull_up', 'weighted_pull_up' => ['pull', 'elbow_flexor_load'],
            'l_sit', 'v_sit' => ['compression', 'core_bodyline'],
            'pistol_squat' => ['lower_body', 'ankle_mobility'],
            default => ['foundation'],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function foundationLane(array $baseFocusAreas): array
    {
        return [
            'slug' => 'foundation_strength',
            'label' => 'Foundation strength',
            'focus_areas' => $baseFocusAreas,
            'current_progression_node' => self::node('foundation.current', 'Measured foundation'),
            'next_node' => self::node('foundation.next', 'Build repeatable base volume'),
            'next_milestone' => self::node('foundation.milestone', 'Stable weekly base'),
        ];
    }

    /**
     * @return array{id: string, label: string}
     */
    private static function node(string $id, string $label): array
    {
        return ['id' => $id, 'label' => $label];
    }

    /**
     * @return array{min_weeks: int|null, max_weeks: int|null, label: string}
     */
    private static function etaRange(?int $minWeeks, ?int $maxWeeks, string $label): array
    {
        return [
            'min_weeks' => $minWeeks,
            'max_weeks' => $maxWeeks,
            'label' => $label,
        ];
    }

    /**
     * @return array{level: string, score: float, reasons: list<string>}
     */
    private static function confidence(string $level, float $score, array $reasons): array
    {
        return [
            'level' => $level,
            'score' => $score,
            'reasons' => $reasons,
        ];
    }

    /**
     * @return array{min_weeks: int|null, max_weeks: int|null, label: string}
     */
    private static function etaFor(string $skill, mixed $level): array
    {
        if ($skill === 'foundation') {
            return self::etaRange(null, null, 'Complete assessment');
        }

        return match ($level) {
            'advanced' => self::etaRange(6, 12, '6-12 weeks'),
            'intermediate' => self::etaRange(8, 16, '8-16 weeks'),
            'beginner' => self::etaRange(12, 24, '12-24 weeks'),
            default => self::etaRange(16, 32, '16-32 weeks'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function etaRangeFromEstimate(RoadmapEtaRange $eta): array
    {
        return [
            'min_weeks' => $eta->lowerWeeks,
            'max_weeks' => $eta->upperWeeks,
            'p50_weeks' => $eta->p50Weeks,
            'p80_weeks' => $eta->p80Weeks,
            'label' => "{$eta->lowerWeeks}-{$eta->upperWeeks} weeks",
            'confidence' => $eta->confidence,
            'modifiers' => $eta->modifiers,
        ];
    }

    /**
     * @return array{level: string, score: float, reasons: list<string>}
     */
    private static function confidenceFromReadiness(?SkillReadiness $readiness, RoadmapEtaRange $eta, RoadmapInput $input): array
    {
        if ($readiness === null) {
            return self::confidenceFor($input);
        }

        $score = round(min($readiness->confidence, $eta->confidence), 2);
        $level = $score >= 0.85 ? 'high' : ($score >= 0.5 ? 'medium' : 'low');
        $reasons = self::uniqueStrings([
            ...array_slice($readiness->softFactors, 0, 3),
            ...array_slice($readiness->missingEvidence, 0, 3),
            ...array_slice($readiness->safetyPenalties, 0, 2),
        ]);

        if ($reasons === []) {
            $reasons[] = 'Baseline tests are complete enough for a first block.';
        }

        return self::confidence($level, $score, $reasons);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function blockersFromReadiness(RoadmapInput $input, ?SkillReadiness $readiness): array
    {
        $blockers = self::blockers($input);

        if ($readiness === null) {
            return $blockers;
        }

        foreach ([...$readiness->hardBlockers, ...$readiness->safetyPenalties] as $index => $message) {
            if ($message === '' || self::hasBlockerMessage($blockers, $message)) {
                continue;
            }

            $blockers[] = [
                'key' => 'readiness_'.($index + 1),
                'label' => 'Readiness gate',
                'severity' => str_contains(strtolower($message), 'pain') || str_contains(strtolower($message), 'missing') ? 'limit' : 'watch',
                'message' => $message,
            ];
        }

        return $blockers;
    }

    /**
     * @param  list<array<string, mixed>>  $blockers
     */
    private static function hasBlockerMessage(array $blockers, string $message): bool
    {
        foreach ($blockers as $blocker) {
            if (($blocker['message'] ?? null) === $message) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $blockers
     * @return list<array<string, mixed>>
     */
    private static function unlockConditionsFromReadiness(?SkillReadiness $readiness, ?array $track, array $blockers): array
    {
        $skill = $readiness?->skill ?? self::trackSkill($track);

        if ($skill === null) {
            return [];
        }

        return [
            ...self::unlockConditions($track, $blockers),
            ...self::unlockConditionsFromStrings($skill, [
                ...array_slice($readiness?->hardBlockers ?? [], 0, 3),
                ...array_slice($readiness?->missingEvidence ?? [], 0, 3),
            ]),
        ];
    }

    /**
     * @param  list<string>  $conditions
     * @return list<array<string, mixed>>
     */
    private static function unlockConditionsFromStrings(string $skill, array $conditions): array
    {
        return array_map(
            fn (string $condition): array => [
                'skill' => $skill,
                'label' => $condition,
                'status' => 'blocked',
            ],
            self::uniqueStrings($conditions),
        );
    }

    /**
     * @return array{level: string, score: float, reasons: list<string>}
     */
    private static function confidenceFor(RoadmapInput $input): array
    {
        $observed = 0;
        $tests = $input->baselineTests;

        foreach ([
            $tests['push_ups']['max_strict_reps'] ?? null,
            $tests['pull_ups']['max_strict_reps'] ?? null,
            $tests['dips']['max_strict_reps'] ?? null,
            $tests['hollow_hold_seconds'] ?? null,
            $tests['squat']['barbell_load_value'] ?? null,
        ] as $value) {
            if (is_numeric($value)) {
                $observed++;
            }
        }

        if ($observed >= 5) {
            return self::confidence('medium', 0.72, ['Baseline tests are complete enough for a first block.']);
        }

        if ($observed >= 3) {
            return self::confidence('medium', 0.58, ['Some baseline tests are present, but roadmap uncertainty remains.']);
        }

        return self::confidence('low', 0.32, ['Several baseline tests are missing.']);
    }

    /**
     * @param  array<string, DomainScore>  $domainScores
     * @return list<array<string, mixed>>
     */
    private static function domainBottlenecks(array $domainScores): array
    {
        $bottlenecks = array_values(array_filter(
            $domainScores,
            static fn (DomainScore $score): bool => $score->score < 70 || $score->missingInputs !== [],
        ));

        usort(
            $bottlenecks,
            static fn (DomainScore $left, DomainScore $right): int => [$left->score, -$left->uncertainty] <=> [$right->score, -$right->uncertainty],
        );

        return array_values(array_map(
            static fn (DomainScore $score): array => [
                'domain' => $score->domain,
                'label' => $score->label,
                'score' => $score->score,
                'confidence' => $score->confidence,
                'reason' => $score->bottleneck,
                'missing_inputs' => $score->missingInputs,
            ],
            array_slice($bottlenecks, 0, 4),
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private static function currentBlockFocus(RoadmapLayerEstimate $estimate, array $baseFocusAreas, string $primarySkill): array
    {
        $shouldImprove = self::primaryDomainFocus($primarySkill);

        return [
            'label' => $estimate->currentBlock['label'],
            'eta_range' => self::etaRangeFromEstimate($estimate->currentBlock['eta']),
            'lanes' => $estimate->currentBlock['lanes'],
            'focus_areas' => self::uniqueStrings([...$baseFocusAreas, ...$shouldImprove]),
            'should_improve' => $shouldImprove,
            'retest_cadence' => $estimate->retestCadence,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $deferredGoals
     * @param  list<array<string, mixed>>  $domainBottlenecks
     * @param  list<string>  $blockFocus
     * @return array<string, mixed>
     */
    private static function explanation(
        string $primaryLabel,
        ?array $primaryTrack,
        ?array $secondaryTrack,
        ?SkillReadiness $primaryReadiness,
        array $deferredGoals,
        array $domainBottlenecks,
        array $blockFocus,
        RoadmapInput $input,
    ): array {
        $missing = self::missingSummary($primaryReadiness, $domainBottlenecks);
        $notTrainedYet = array_values(array_filter(array_map(
            static fn (array $goal): ?string => is_string($goal['explanation'] ?? null) ? $goal['explanation'] : null,
            $deferredGoals,
        )));

        return [
            'summary' => "{$primaryLabel} is the clearest first roadmap priority from the current assessment.",
            'primary_now' => "{$primaryLabel} is the primary roadmap right now.",
            'why_this_goal' => self::whyThisGoal($primaryTrack, $secondaryTrack),
            'what_is_missing' => $missing,
            'this_block_should_improve' => $blockFocus,
            'not_trained_yet' => $notTrainedYet,
            'what_would_change_recommendation' => self::whatWouldChangeRecommendation($input, $missing),
            'watch_out_for' => self::uniqueStrings([
                ...($primaryReadiness?->hardBlockers ?? []),
                ...($primaryReadiness?->safetyPenalties ?? []),
            ]),
            'fallback' => self::fallbackFor($primaryTrack),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $domainBottlenecks
     * @return list<string>
     */
    private static function missingSummary(?SkillReadiness $readiness, array $domainBottlenecks): array
    {
        $missing = [];

        if ($readiness === null || $readiness->missingEvidence !== []) {
            $missing[] = 'Objective baseline data';
        }

        foreach ($readiness?->hardBlockers ?? [] as $blocker) {
            if (str_contains(strtolower($blocker), 'equipment')) {
                $missing[] = 'Required equipment';
            }
        }

        foreach ($domainBottlenecks as $bottleneck) {
            if (($bottleneck['missing_inputs'] ?? []) !== []) {
                $missing[] = (string) $bottleneck['label'];
            }
        }

        return self::uniqueStrings($missing);
    }

    /**
     * @param  list<string>  $missing
     * @return list<string>
     */
    private static function whatWouldChangeRecommendation(RoadmapInput $input, array $missing): array
    {
        $changes = [];
        $painLevel = $input->painFlags['level'] ?? null;

        if (is_int($painLevel) && $painLevel >= 4) {
            $changes[] = 'Pain returning below 4/10 would reopen progression choices.';
        }

        if (in_array('Objective baseline data', $missing, true)) {
            $changes[] = 'More completed baseline tests would tighten the roadmap confidence.';
        }

        if (in_array('Required equipment', $missing, true)) {
            $changes[] = 'Adding the required equipment would reopen blocked skill lanes.';
        }

        if ($changes === []) {
            $changes[] = 'A block retest can promote, hold, or defer the next progression.';
        }

        return $changes;
    }

    /**
     * @return list<string>
     */
    private static function primaryDomainFocus(string $skill): array
    {
        return match ($skill) {
            'strict_pull_up', 'weighted_pull_up', 'one_arm_pull_up' => ['Vertical pull', 'Tissue tolerance'],
            'strict_dip', 'ring_dip', 'weighted_dip' => ['Vertical push', 'Tissue tolerance'],
            'muscle_up', 'weighted_muscle_up' => ['Vertical pull', 'Vertical push', 'Transition strength'],
            'l_sit', 'v_sit', 'press_to_handstand' => ['Compression', 'Trunk rigidity'],
            'handstand' => ['Inversion and balance', 'Trunk rigidity', 'Tissue tolerance'],
            'handstand_push_up' => ['Inversion and balance', 'Vertical push', 'Tissue tolerance'],
            'front_lever', 'back_lever', 'human_flag' => ['Horizontal pull and straight-arm pull', 'Trunk rigidity', 'Tissue tolerance'],
            'planche', 'one_arm_push_up' => ['Vertical push', 'Trunk rigidity', 'Tissue tolerance'],
            'pistol_squat', 'nordic_curl' => ['Lower-body strength', 'Tissue tolerance'],
            default => ['Foundation strength'],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function intermediate(
        RoadmapInput $input,
        array $signals,
        ?array $primaryTrack,
        ?array $secondaryTrack,
        array $placements,
        array $domainScores,
        array $readiness,
        RoadmapLaneSelection $laneSelection,
        RoadmapLayerEstimate $layerEstimate,
    ): array {
        return [
            'progression_graph_placement' => [
                'primary' => [
                    'skill' => self::trackSkill($primaryTrack),
                    'node' => self::trackSkill($primaryTrack).'.current',
                    'completion' => self::completionFor($primaryTrack, $signals),
                ],
                'secondary' => $secondaryTrack === null ? null : [
                    'skill' => self::trackSkill($secondaryTrack),
                    'node' => self::trackSkill($secondaryTrack).'.current',
                    'completion' => self::completionFor($secondaryTrack, $signals),
                ],
            ],
            'placements' => array_map(static fn (BaselineNodePlacement $placement): array => $placement->toArray(), $placements),
            'domain_scores' => array_map(static fn (DomainScore $score): array => $score->toArray(), $domainScores),
            'domain_uncertainty' => self::domainUncertainty($input),
            'hard_gate_results' => self::hardGateResults($input),
            'readiness_scores' => array_values(array_map(static fn (SkillReadiness $item): array => $item->toArray(), $readiness)),
            'compatibility_costs' => self::compatibilityCosts($primaryTrack, $secondaryTrack),
            'lane_selection' => $laneSelection->toArray(),
            'roadmap_layers' => $layerEstimate->toArray(),
            'eta_modifiers' => array_map(
                static fn (string $modifier): array => ['reason' => $modifier],
                $layerEstimate->primaryEta->modifiers,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function domainScores(array $signals): array
    {
        return [
            'vertical_push' => [
                'label' => 'Vertical push',
                'score' => round(min(1.0, ((float) ($signals['dip_reps'] ?? 0)) / 10), 2),
                'inputs' => ['dips', 'push_ups'],
            ],
            'vertical_pull' => [
                'label' => 'Vertical pull',
                'score' => round(min(1.0, ((float) ($signals['pull_reps'] ?? 0)) / 10), 2),
                'inputs' => ['pull_ups'],
            ],
            'trunk_rigidity' => [
                'label' => 'Trunk rigidity',
                'score' => round(min(1.0, ((float) ($signals['hollow_hold'] ?? 0)) / 60), 2),
                'inputs' => ['hollow_body_hold'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function domainUncertainty(RoadmapInput $input): array
    {
        $tests = $input->baselineTests;

        return [
            'vertical_push' => [
                'score' => self::missingScore([$tests['push_ups']['max_strict_reps'] ?? null, $tests['dips']['max_strict_reps'] ?? null]),
                'missing_inputs' => self::missingInputs([
                    'push_ups' => $tests['push_ups']['max_strict_reps'] ?? null,
                    'dips' => $tests['dips']['max_strict_reps'] ?? null,
                ]),
            ],
            'vertical_pull' => [
                'score' => self::missingScore([$tests['pull_ups']['max_strict_reps'] ?? null]),
                'missing_inputs' => self::missingInputs([
                    'pull_ups' => $tests['pull_ups']['max_strict_reps'] ?? null,
                ]),
            ],
            'trunk_rigidity' => [
                'score' => self::missingScore([$tests['hollow_hold_seconds'] ?? null]),
                'missing_inputs' => self::missingInputs([
                    'hollow_body_hold' => $tests['hollow_hold_seconds'] ?? null,
                ]),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function hardGateResults(RoadmapInput $input): array
    {
        $painLevel = $input->painFlags['level'] ?? null;

        return [
            [
                'key' => 'pain',
                'passed' => ! is_int($painLevel) || $painLevel < 4,
                'severity' => is_int($painLevel) && $painLevel >= 7 ? 'stop' : (is_int($painLevel) && $painLevel >= 4 ? 'limit' : 'watch'),
                'message' => is_int($painLevel) && $painLevel >= 4
                    ? 'Pain should block progression until it settles.'
                    : 'Pain is low enough for normal conservative placement.',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function readinessScores(?array $primaryTrack, ?array $secondaryTrack, array $signals): array
    {
        return array_values(array_filter([
            $primaryTrack === null ? null : [
                'skill' => self::trackSkill($primaryTrack),
                'score' => self::completionFor($primaryTrack, $signals),
                'reasons' => [self::trackReason($primaryTrack)],
            ],
            $secondaryTrack === null ? null : [
                'skill' => self::trackSkill($secondaryTrack),
                'score' => self::completionFor($secondaryTrack, $signals),
                'reasons' => [self::trackReason($secondaryTrack)],
            ],
        ]));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function compatibilityCosts(?array $primaryTrack, ?array $secondaryTrack): array
    {
        if ($primaryTrack === null || $secondaryTrack === null) {
            return [];
        }

        return [[
            'skill' => self::trackSkill($secondaryTrack),
            'cost' => in_array(self::trackSkill($secondaryTrack), self::stringList($primaryTrack['compatible_secondary_skills'] ?? []), true) ? 0.12 : 0.35,
            'reasons' => ['Overlap is acceptable for a secondary exposure.'],
        ]];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function etaModifiers(RoadmapInput $input): array
    {
        $trainingAge = $input->trainingContext['training_age_months'] ?? null;

        return [[
            'key' => 'training_age',
            'multiplier' => is_int($trainingAge) && $trainingAge < 6 ? 1.2 : 1.0,
            'reason' => is_int($trainingAge) && $trainingAge < 6
                ? 'Short training history widens the timeline.'
                : 'Training history supports a normal ramp.',
        ]];
    }

    private static function completionFor(?array $track, array $signals): float
    {
        return match (self::trackSkill($track)) {
            'strict_pull_up', 'weighted_pull_up', 'front_lever', 'one_arm_pull_up' => round(min(1.0, ((float) ($signals['pull_reps'] ?? 0)) / 10), 2),
            'strict_dip', 'ring_dip', 'muscle_up' => round(min(1.0, ((float) ($signals['dip_reps'] ?? 0)) / 10), 2),
            'handstand', 'l_sit', 'planche' => round(min(1.0, ((float) ($signals['hollow_hold'] ?? 0)) / 60), 2),
            default => 0.5,
        };
    }

    /**
     * @param  list<mixed>  $values
     */
    private static function missingScore(array $values): float
    {
        $missing = count(array_filter($values, fn (mixed $value): bool => ! is_numeric($value)));

        return round($missing / max(1, count($values)), 2);
    }

    /**
     * @param  array<string, mixed>  $inputs
     * @return list<string>
     */
    private static function missingInputs(array $inputs): array
    {
        return array_keys(array_filter($inputs, fn (mixed $value): bool => ! is_numeric($value)));
    }

    private static function currentNodeLabel(?array $track): string
    {
        return self::trackLabel($track, 'Current baseline').' placement';
    }

    private static function nextNodeLabel(?array $track): string
    {
        return self::stringValue($track['next_gate'] ?? null, 'Build the next progression');
    }

    private static function nextMilestoneLabel(?array $track): string
    {
        return self::stringValue($track['next_gate'] ?? null, 'First roadmap milestone');
    }

    /**
     * @return list<string>
     */
    private static function whyThisGoal(?array $primaryTrack, ?array $secondaryTrack): array
    {
        $reasons = [];

        if ($primaryTrack !== null) {
            $reasons[] = self::trackReason($primaryTrack);
        }

        if ($secondaryTrack !== null) {
            $reasons[] = self::trackLabel($secondaryTrack, 'The secondary target').' fits as a lighter secondary lane.';
        }

        return $reasons;
    }

    private static function fallbackFor(?array $track): string
    {
        return $track === null
            ? 'Start with foundation strength until enough baseline data is available.'
            : 'If readiness drops, keep the same target and use the previous easier progression.';
    }

    /**
     * @return array<string, mixed>
     */
    private static function emptyIntermediate(): array
    {
        return [
            'progression_graph_placement' => [],
            'domain_scores' => [],
            'domain_uncertainty' => [],
            'hard_gate_results' => [],
            'readiness_scores' => [],
            'compatibility_costs' => [],
            'eta_modifiers' => [],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function trackList(mixed $value): array
    {
        return is_array($value) ? array_values(array_filter($value, is_array(...))) : [];
    }

    private static function trackSkill(?array $track): ?string
    {
        return is_string($track['skill'] ?? null) ? $track['skill'] : null;
    }

    private static function trackLabel(?array $track, string $fallback): string
    {
        return self::stringValue($track['label'] ?? null, $fallback);
    }

    private static function trackReason(?array $track): string
    {
        return self::stringValue($track['reason'] ?? null, 'Baseline data supports this placement.');
    }

    private static function stringValue(mixed $value, string $fallback): string
    {
        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    /**
     * @param  list<mixed>  $values
     * @return list<string>
     */
    private static function uniqueStrings(array $values): array
    {
        return array_values(array_unique(array_filter($values, static fn (mixed $value): bool => is_string($value) && $value !== '')));
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
