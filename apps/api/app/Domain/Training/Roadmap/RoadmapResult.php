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

    public static function empty(): self
    {
        return new self([
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
                'why_this_goal' => [],
                'watch_out_for' => [],
                'fallback' => 'Start with foundation strength until the assessment is complete.',
            ],
            'intermediate' => self::emptyIntermediate(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $signals
     * @param  array<string, mixed>  $legacy
     */
    public static function fromTrackBuckets(RoadmapInput $input, array $signals, array $legacy): self
    {
        $activeTracks = [
            ...self::trackList($legacy['unlocked_tracks'] ?? []),
            ...self::trackList($legacy['bridge_tracks'] ?? []),
        ];
        $longTermTracks = self::trackList($legacy['long_term_tracks'] ?? []);
        $deferredTracks = self::trackList($legacy['deferred_tracks'] ?? []);
        $primaryTrack = self::primaryTrack($input, $activeTracks);
        $secondaryTrack = self::secondaryTrack($input, $activeTracks, $primaryTrack);
        $baseFocusAreas = self::stringList($legacy['base_focus_areas'] ?? []);
        $blockers = self::blockers($input);
        $primarySkill = self::trackSkill($primaryTrack) ?? 'foundation';
        $primaryLabel = self::trackLabel($primaryTrack, 'Foundation strength');
        $etaRange = self::etaFor($primarySkill, $legacy['level'] ?? 'foundation');
        $confidence = self::confidenceFor($input);
        $currentNode = self::node("{$primarySkill}.current", self::currentNodeLabel($primaryTrack));
        $nextNode = self::node("{$primarySkill}.next", self::nextNodeLabel($primaryTrack));
        $nextMilestone = self::node("{$primarySkill}.milestone", self::nextMilestoneLabel($primaryTrack));
        $unlockConditions = self::unlockConditions($primaryTrack, $blockers);
        $compatibilityTags = self::compatibilityTags($primarySkill);

        return new self([
            ...$legacy,
            'version' => self::VERSION,
            'primary_goal' => $primaryTrack === null ? null : self::goal(
                $primaryTrack,
                'primary',
                $currentNode,
                $nextNode,
                $nextMilestone,
                $etaRange,
                $confidence,
                $blockers,
                $unlockConditions,
                $compatibilityTags,
                "{$primaryLabel} is the clearest first roadmap priority from the current assessment.",
            ),
            'compatible_secondary_goal' => $secondaryTrack === null ? null : self::goal(
                $secondaryTrack,
                'secondary',
                self::node(self::trackSkill($secondaryTrack).'.current', self::currentNodeLabel($secondaryTrack)),
                self::node(self::trackSkill($secondaryTrack).'.next', self::nextNodeLabel($secondaryTrack)),
                self::node(self::trackSkill($secondaryTrack).'.milestone', self::nextMilestoneLabel($secondaryTrack)),
                self::etaRange(6, 12, '6-12 weeks'),
                self::confidence('medium', 0.7, ['The secondary target uses measured baseline data.']),
                [],
                [],
                self::compatibilityTags((string) self::trackSkill($secondaryTrack)),
                'This target can receive lighter exposure without replacing the primary roadmap.',
            ),
            'foundation_lane' => self::foundationLane($baseFocusAreas),
            'deferred_goals' => self::deferredGoals($deferredTracks, $longTermTracks),
            'current_progression_node' => $currentNode,
            'next_node' => $nextNode,
            'next_milestone' => $nextMilestone,
            'eta_range' => $etaRange,
            'confidence' => $confidence,
            'blockers' => $blockers,
            'unlock_conditions' => $unlockConditions,
            'compatibility_tags' => $compatibilityTags,
            'explanation' => [
                'summary' => "{$primaryLabel} is the clearest first roadmap priority from the current assessment.",
                'why_this_goal' => self::whyThisGoal($primaryTrack, $secondaryTrack),
                'watch_out_for' => array_map(
                    fn (array $blocker): string => (string) $blocker['message'],
                    $blockers,
                ),
                'fallback' => self::fallbackFor($primaryTrack),
            ],
            'intermediate' => self::intermediate($input, $signals, $primaryTrack, $secondaryTrack),
        ]);
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
     * @return array<string, mixed>
     */
    private static function intermediate(RoadmapInput $input, array $signals, ?array $primaryTrack, ?array $secondaryTrack): array
    {
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
            'domain_scores' => self::domainScores($signals),
            'domain_uncertainty' => self::domainUncertainty($input),
            'hard_gate_results' => self::hardGateResults($input),
            'readiness_scores' => self::readinessScores($primaryTrack, $secondaryTrack, $signals),
            'compatibility_costs' => self::compatibilityCosts($primaryTrack, $secondaryTrack),
            'eta_modifiers' => self::etaModifiers($input),
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
