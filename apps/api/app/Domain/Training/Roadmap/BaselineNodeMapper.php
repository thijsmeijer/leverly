<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

use InvalidArgumentException;

final class BaselineNodeMapper
{
    private const array SKILL_STATUS_FAMILIES = [
        'dip' => 'dip',
        'ring_dip' => 'dip',
        'muscle_up' => 'muscle_up',
        'l_sit' => 'compression',
        'handstand' => 'handstand',
        'handstand_push_up' => 'hspu',
        'front_lever' => 'front_lever',
        'back_lever' => 'back_lever',
        'planche' => 'planche',
        'pistol_squat' => 'pistol_squat',
        'one_arm_pull_up' => 'one_arm_pull_up',
        'human_flag' => 'human_flag',
        'press_to_handstand' => 'handstand',
    ];

    private const array MODULE_FAMILY_PRIORITY = [
        'inversion' => ['handstand', 'hspu'],
        'pull_skill' => ['muscle_up', 'front_lever', 'back_lever', 'one_arm_pull_up', 'pull_up'],
        'push_compression' => ['compression', 'planche', 'handstand', 'hspu'],
        'lower_body' => ['pistol_squat', 'lower_body'],
        'lateral_chain' => ['human_flag'],
    ];

    private const array PROGRESSION_ALIASES = [
        'strict_one_arm_pull_up' => 'one_arm_pull_up',
        'weighted_pull_up_reps' => 'weighted_pull_up',
        'nordic_eccentric' => 'nordic_curl_negative',
        'wall_handstand' => 'chest_to_wall_handstand',
    ];

    /**
     * @return array<string, BaselineNodePlacement>
     */
    public static function fromInput(RoadmapInput $input): array
    {
        $placements = self::defaults();

        self::raise($placements, self::mapPushUp($input));
        self::raise($placements, self::mapPullUp($input));
        self::raise($placements, self::mapDip($input));
        self::raise($placements, self::mapRow($input));
        self::raise($placements, self::mapBodyline($input));
        self::raise($placements, self::mapSupport($input));
        self::raise($placements, self::mapLowerBody($input));

        self::feedSharedBaselineEvidence($placements, $input);
        self::applySkillStatuses($placements, $input);
        self::applyGoalModules($placements, $input);

        return array_intersect_key($placements, array_flip(ProgressionGraphRegistry::families()));
    }

    /**
     * @return array<string, BaselineNodePlacement>
     */
    private static function defaults(): array
    {
        $placements = [];

        foreach (ProgressionGraphRegistry::families() as $family) {
            $graph = ProgressionGraphRegistry::require($family);
            $first = $graph->nodes()[0] ?? null;

            if ($first === null) {
                continue;
            }

            $placements[$family] = self::placement(
                family: $family,
                node: $first->slug,
                completionPercentage: 0,
                observedEvidence: [],
                missingEvidence: ["{$graph->label} progression evidence."],
                confidenceContribution: 0.1,
            );
        }

        return $placements;
    }

    /**
     * @param  array<string, BaselineNodePlacement>  $placements
     */
    private static function raise(array &$placements, BaselineNodePlacement $candidate): void
    {
        $existing = $placements[$candidate->family] ?? null;

        if ($existing === null || $candidate->currentNode->order > $existing->currentNode->order) {
            $placements[$candidate->family] = $candidate->withEvidence(
                observedEvidence: $existing === null ? [] : $existing->observedEvidence,
                confidenceFloor: max($candidate->confidenceContribution, $existing?->confidenceContribution ?? 0.0),
            );

            return;
        }

        if ($candidate->currentNode->order === $existing->currentNode->order && $candidate->completionPercentage >= $existing->completionPercentage) {
            $placements[$candidate->family] = $candidate->withEvidence(
                observedEvidence: $existing->observedEvidence,
                missingEvidence: $candidate->missingEvidence,
                confidenceFloor: max($candidate->confidenceContribution, $existing->confidenceContribution),
            );

            return;
        }

        $placements[$candidate->family] = $existing->withEvidence(
            observedEvidence: $candidate->observedEvidence,
            missingEvidence: $candidate->missingEvidence,
            confidenceFloor: $candidate->confidenceContribution,
        );
    }

    /**
     * @param  array<string, BaselineNodePlacement>  $placements
     * @param  list<string>  $observedEvidence
     * @param  list<string>  $missingEvidence
     */
    private static function addEvidence(
        array &$placements,
        string $family,
        array $observedEvidence = [],
        array $missingEvidence = [],
        ?float $confidenceFloor = null,
    ): void {
        if (! isset($placements[$family])) {
            return;
        }

        $placements[$family] = $placements[$family]->withEvidence($observedEvidence, $missingEvidence, $confidenceFloor);
    }

    private static function mapPushUp(RoadmapInput $input): BaselineNodePlacement
    {
        $pushUps = self::arrayValue($input->baselineTests['push_ups'] ?? []);
        $reps = self::intOrNull($pushUps['max_strict_reps'] ?? null);

        if ($reps === null) {
            return self::placement('push_up', 'no_push_up', 0, [], ['Push-up reps.'], 0.15);
        }

        $observed = ["Push-ups: {$reps} reps."];

        if ($reps <= 0) {
            return self::placement('push_up', 'no_push_up', 5, $observed, [], 0.35);
        }

        if ($reps === 1) {
            return self::placement('push_up', 'first_push_up', 15, $observed, [], 0.55);
        }

        if ($reps < 10) {
            return self::placement('push_up', 'multiple_push_ups', min(90, 20 + ($reps * 7)), $observed, [], 0.65);
        }

        if ($reps < 20) {
            return self::placement('push_up', 'three_by_ten_push_ups', min(85, 45 + ($reps * 2)), $observed, [], 0.75);
        }

        return self::placement('push_up', 'pseudo_planche_push_up', min(90, 55 + $reps), $observed, [], 0.8);
    }

    private static function mapPullUp(RoadmapInput $input): BaselineNodePlacement
    {
        $pullUps = self::arrayValue($input->baselineTests['pull_ups'] ?? []);
        $reps = self::intOrNull($pullUps['max_strict_reps'] ?? null);
        $fallback = self::stringValue($pullUps['fallback_variant'] ?? null, 'none');
        $fallbackReps = self::intOrNull($pullUps['fallback_reps'] ?? null);
        $fallbackSeconds = self::intOrNull($pullUps['fallback_seconds'] ?? null);
        $hangSeconds = self::intOrNull($input->baselineTests['passive_hang_seconds'] ?? null);
        $weighted = self::weightedMovement($input, 'weighted_pull_up');
        $observed = [];

        if ($reps !== null) {
            $observed[] = "Pull-ups: {$reps} reps.";
        }

        if ($hangSeconds !== null) {
            $observed[] = "Passive hang: {$hangSeconds} seconds.";
        }

        if ($weighted !== null) {
            $observed[] = self::weightedEvidence('Weighted pull-up', $weighted);

            return self::placement('pull_up', 'weighted_pull_up', 82, $observed, [], 0.75);
        }

        if ($reps !== null && $reps >= 8) {
            return self::placement('pull_up', 'three_by_eight_pull_ups', min(90, 45 + ($reps * 4)), $observed, [], 0.75);
        }

        if ($reps !== null && $reps >= 2) {
            return self::placement('pull_up', 'multiple_pull_ups', min(85, 25 + ($reps * 8)), $observed, [], 0.68);
        }

        if ($reps === 1) {
            return self::placement('pull_up', 'first_pull_up', 20, $observed, [], 0.58);
        }

        if ($fallback === 'assisted' && $fallbackReps !== null && $fallbackReps > 0) {
            $observed[] = "Assisted pull-up: {$fallbackReps} reps.";

            return self::placement('pull_up', 'assisted_pull_up', min(70, 30 + ($fallbackReps * 6)), $observed, [], 0.5);
        }

        if ($fallback === 'eccentric' && $fallbackSeconds !== null && $fallbackSeconds > 0) {
            $observed[] = "Eccentric pull-up: {$fallbackSeconds} seconds.";

            return self::placement('pull_up', 'eccentric_pull_up', min(70, 35 + ($fallbackSeconds * 3)), $observed, [], 0.48);
        }

        if ($hangSeconds !== null && $hangSeconds >= 20) {
            return self::placement('pull_up', 'active_hang', min(75, 25 + $hangSeconds), $observed, ['Scapular pull evidence.'], 0.4);
        }

        if ($hangSeconds !== null && $hangSeconds > 0) {
            return self::placement('pull_up', 'passive_hang', min(30, 9 + $hangSeconds), $observed, ['Active hang or scapular pull evidence.'], 0.35);
        }

        return self::placement('pull_up', 'no_vertical_pull', 0, $observed, ['Passive hang or vertical-pull fallback.'], 0.15);
    }

    private static function mapDip(RoadmapInput $input): BaselineNodePlacement
    {
        $dips = self::arrayValue($input->baselineTests['dips'] ?? []);
        $reps = self::intOrNull($dips['max_strict_reps'] ?? null);
        $fallback = self::stringValue($dips['fallback_variant'] ?? null, 'none');
        $fallbackReps = self::intOrNull($dips['fallback_reps'] ?? null);
        $supportSeconds = self::intOrNull($input->baselineTests['top_support_hold_seconds'] ?? null);
        $weighted = self::weightedMovement($input, 'weighted_dip');
        $observed = [];

        if ($reps !== null) {
            $observed[] = "Dips: {$reps} reps.";
        }

        if ($supportSeconds !== null) {
            $observed[] = "Top support: {$supportSeconds} seconds.";
        }

        if ($weighted !== null) {
            $observed[] = self::weightedEvidence('Weighted dip', $weighted);

            return self::placement('dip', 'weighted_dip', 88, $observed, [], 0.78);
        }

        if ($reps !== null && $reps >= 8) {
            return self::placement('dip', 'deep_dip_capacity', min(90, 45 + ($reps * 4)), $observed, ['Weighted or ring dip evidence.'], 0.75);
        }

        if ($reps !== null && $reps >= 2) {
            return self::placement('dip', 'multiple_dips', min(85, 25 + ($reps * 8)), $observed, [], 0.68);
        }

        if ($reps === 1) {
            return self::placement('dip', 'first_dip', 20, $observed, [], 0.58);
        }

        if ($fallback === 'assisted' && $fallbackReps !== null && $fallbackReps > 0) {
            $observed[] = "Assisted dip: {$fallbackReps} reps.";

            return self::placement('dip', 'assisted_dip', min(75, 35 + ($fallbackReps * 6)), $observed, [], 0.52);
        }

        if ($supportSeconds !== null && $supportSeconds > 0) {
            return self::placement('dip', 'top_support', min(75, 18 + $supportSeconds), $observed, [], 0.42);
        }

        return self::placement('dip', 'no_support', 0, $observed, ['Top support or assisted dip evidence.'], 0.15);
    }

    private static function mapRow(RoadmapInput $input): BaselineNodePlacement
    {
        $rows = self::arrayValue($input->baselineTests['rows'] ?? []);
        $variant = self::stringValue($rows['variant'] ?? null, 'bodyweight_row');
        $reps = self::intOrNull($rows['max_reps'] ?? null);

        if ($reps === null) {
            return self::placement('row', 'no_horizontal_pull', 0, [], ['Horizontal row test.'], 0.15);
        }

        $label = self::rowLabel($variant);
        $observed = ["{$label}: {$reps} reps."];
        $node = match ($variant) {
            'ring_row', 'suspension_row' => $reps >= 15 ? 'feet_elevated_row' : 'ring_row',
            'low_bar_row' => $reps >= 15 ? 'feet_elevated_row' : 'low_bar_row',
            default => $reps >= 12 ? 'bodyweight_row' : 'high_incline_row',
        };

        return self::placement('row', $node, min(90, 20 + ($reps * 5)), $observed, [], 0.62);
    }

    private static function mapBodyline(RoadmapInput $input): BaselineNodePlacement
    {
        $seconds = self::intOrNull($input->baselineTests['hollow_hold_seconds'] ?? null);

        if ($seconds === null) {
            return self::placement('bodyline', 'dead_bug', 0, [], ['Hollow body hold.'], 0.15);
        }

        $observed = ["Hollow body hold: {$seconds} seconds."];

        if ($seconds >= 45) {
            return self::placement('bodyline', 'hollow_rocks', min(90, 40 + $seconds), $observed, [], 0.72);
        }

        if ($seconds >= 20) {
            return self::placement('bodyline', 'hollow_body_hold', min(85, 35 + $seconds), $observed, [], 0.65);
        }

        if ($seconds >= 8) {
            return self::placement('bodyline', 'tuck_hollow_hold', min(75, 20 + ($seconds * 2)), $observed, [], 0.45);
        }

        return self::placement('bodyline', 'dead_bug', min(30, $seconds * 4), $observed, ['Longer hollow body hold.'], 0.3);
    }

    private static function mapSupport(RoadmapInput $input): BaselineNodePlacement
    {
        $seconds = self::intOrNull($input->baselineTests['top_support_hold_seconds'] ?? null);

        if ($seconds === null) {
            return self::placement('support', 'no_support', 0, [], ['Top support hold.'], 0.15);
        }

        $observed = ["Top support: {$seconds} seconds."];

        if ($seconds >= 30) {
            return self::placement('support', 'parallel_bar_support', min(90, 40 + $seconds), $observed, ['Ring support evidence.'], 0.65);
        }

        if ($seconds >= 10) {
            return self::placement('support', 'parallel_bar_support', min(75, 25 + $seconds), $observed, [], 0.55);
        }

        if ($seconds > 0) {
            return self::placement('support', 'box_support', min(60, 15 + ($seconds * 2)), $observed, [], 0.38);
        }

        return self::placement('support', 'no_support', 0, $observed, ['Top support hold.'], 0.25);
    }

    private static function mapLowerBody(RoadmapInput $input): BaselineNodePlacement
    {
        $squat = self::arrayValue($input->baselineTests['squat'] ?? []);
        $load = self::numberOrNull($squat['barbell_load_value'] ?? null);
        $reps = self::intOrNull($squat['barbell_reps'] ?? null);
        $bodyweight = self::numberValue($input->profileContext['current_bodyweight_value'] ?? null);

        if ($load !== null && $load > 0 && $reps !== null && $reps > 0 && $bodyweight > 0.0) {
            $ratio = $load / $bodyweight;
            $observed = [sprintf('Barbell squat: %skg for %d reps.', self::formatNumber($load), $reps)];

            if ($ratio >= 1.0 && $reps >= 3) {
                return self::placement('lower_body', 'loaded_squat_capacity', min(90, 55 + (int) round($ratio * 20)), $observed, [], 0.72);
            }

            if ($ratio >= 0.75 && $reps >= 3) {
                return self::placement('lower_body', 'barbell_squat_base', min(85, 45 + (int) round($ratio * 20)), $observed, [], 0.65);
            }

            return self::placement('lower_body', 'goblet_squat', min(75, 25 + (int) round($ratio * 20)), $observed, [], 0.52);
        }

        $lowerBody = self::arrayValue($input->baselineTests['lower_body'] ?? []);
        $variant = self::stringValue($lowerBody['variant'] ?? null, 'bodyweight_squat');
        $fallbackReps = self::intOrNull($lowerBody['reps'] ?? null);

        if ($fallbackReps !== null && $fallbackReps > 0) {
            $node = match ($variant) {
                'split_squat' => 'split_squat',
                'step_down' => 'step_down',
                'pistol_progression' => 'step_down',
                default => 'bodyweight_squat',
            };
            $label = self::lowerBodyLabel($variant);

            return self::placement(
                'lower_body',
                $node,
                min(80, 25 + ($fallbackReps * 4)),
                ["Lower-body fallback: {$label} for {$fallbackReps} reps."],
                ['Barbell squat ratio.'],
                0.52,
            );
        }

        return self::placement('lower_body', 'bodyweight_squat', 0, [], ['Barbell squat ratio.', 'Bodyweight lower-body fallback.'], 0.15);
    }

    /**
     * @param  array<string, BaselineNodePlacement>  $placements
     */
    private static function feedSharedBaselineEvidence(array &$placements, RoadmapInput $input): void
    {
        $row = $placements['row'];
        $bodyline = $placements['bodyline'];
        $support = $placements['support'];
        $lowerBody = $placements['lower_body'];

        if ($row->observedEvidence !== []) {
            self::addEvidence($placements, 'front_lever', $row->observedEvidence, [], 0.35);
            self::addEvidence($placements, 'back_lever', $row->observedEvidence, [], 0.35);
        } else {
            self::addEvidence($placements, 'front_lever', [], ['Horizontal row capacity for lever confidence.']);
            self::addEvidence($placements, 'back_lever', [], ['Horizontal row capacity for lever confidence.']);
        }

        if ($bodyline->observedEvidence !== []) {
            self::addEvidence($placements, 'front_lever', $bodyline->observedEvidence, [], 0.35);
            self::addEvidence($placements, 'back_lever', $bodyline->observedEvidence, [], 0.3);
            self::addEvidence($placements, 'compression', $bodyline->observedEvidence, [], 0.3);
            self::addEvidence($placements, 'planche', $bodyline->observedEvidence, [], 0.3);
        }

        if ($support->observedEvidence !== []) {
            self::addEvidence($placements, 'compression', $support->observedEvidence, [], 0.35);
            self::addEvidence($placements, 'planche', $support->observedEvidence, [], 0.35);
        }

        if ($lowerBody->observedEvidence !== []) {
            self::addEvidence($placements, 'pistol_squat', $lowerBody->observedEvidence, $lowerBody->missingEvidence, 0.42);
        }
    }

    /**
     * @param  array<string, BaselineNodePlacement>  $placements
     */
    private static function applySkillStatuses(array &$placements, RoadmapInput $input): void
    {
        $statuses = self::arrayValue($input->goalModules['skill_statuses'] ?? []);

        foreach ($statuses as $skill => $statusData) {
            if (! is_string($skill) || ! is_array($statusData)) {
                continue;
            }

            $family = self::SKILL_STATUS_FAMILIES[$skill] ?? null;
            $status = self::progressionSlug($statusData['status'] ?? null);

            if ($family === null || $status === null || $status === 'not_tested') {
                continue;
            }

            self::applyProgressionEvidence(
                placements: $placements,
                family: $family,
                progression: $status,
                metricType: self::nodeMetricType($family, $status),
                reps: self::intOrNull($statusData['max_strict_reps'] ?? null),
                holdSeconds: self::intOrNull($statusData['best_hold_seconds'] ?? null),
                loadValue: null,
                loadUnit: 'kg',
                quality: 'solid',
            );
        }
    }

    /**
     * @param  array<string, BaselineNodePlacement>  $placements
     */
    private static function applyGoalModules(array &$placements, RoadmapInput $input): void
    {
        $modules = self::arrayValue($input->goalModules['conditional_modules'] ?? []);

        foreach ($modules as $module => $moduleData) {
            if (! is_string($module) || ! is_array($moduleData)) {
                continue;
            }

            $progression = self::progressionSlug($moduleData['highest_progression'] ?? null);
            if ($progression === null || $progression === 'not_tested') {
                self::markMissingModule($placements, $module);

                continue;
            }

            foreach (self::MODULE_FAMILY_PRIORITY[$module] ?? [] as $family) {
                if (ProgressionGraphRegistry::node($family, $progression) === null) {
                    continue;
                }

                self::applyProgressionEvidence(
                    placements: $placements,
                    family: $family,
                    progression: $progression,
                    metricType: self::stringValue($moduleData['metric_type'] ?? null, self::nodeMetricType($family, $progression)),
                    reps: self::intOrNull($moduleData['reps'] ?? null),
                    holdSeconds: self::intOrNull($moduleData['hold_seconds'] ?? null),
                    loadValue: self::numberOrNull($moduleData['load_value'] ?? null),
                    loadUnit: self::stringValue($moduleData['load_unit'] ?? null, 'kg'),
                    quality: self::stringValue($moduleData['quality'] ?? null, 'unknown'),
                );

                break;
            }
        }
    }

    /**
     * @param  array<string, BaselineNodePlacement>  $placements
     */
    private static function markMissingModule(array &$placements, string $module): void
    {
        foreach (self::MODULE_FAMILY_PRIORITY[$module] ?? [] as $family) {
            self::addEvidence($placements, $family, [], ["{$module} module progression evidence."]);
        }
    }

    /**
     * @param  array<string, BaselineNodePlacement>  $placements
     */
    private static function applyProgressionEvidence(
        array &$placements,
        string $family,
        string $progression,
        string $metricType,
        ?int $reps,
        ?int $holdSeconds,
        ?float $loadValue,
        string $loadUnit,
        string $quality,
    ): void {
        $node = ProgressionGraphRegistry::node($family, $progression);

        if ($node === null) {
            return;
        }

        $evidence = self::progressionEvidence($node, $metricType, $reps, $holdSeconds, $loadValue, $loadUnit, $quality);
        $percentage = self::progressionCompletion($metricType, $reps, $holdSeconds, $loadValue, $quality);
        $confidence = $evidence === [] ? 0.28 : 0.62;

        self::raise($placements, self::placement($family, $progression, $percentage, $evidence, [], $confidence));
    }

    /**
     * @return list<string>
     */
    private static function progressionEvidence(
        ProgressionGraphNode $node,
        string $metricType,
        ?int $reps,
        ?int $holdSeconds,
        ?float $loadValue,
        string $loadUnit,
        string $quality,
    ): array {
        return match ($metricType) {
            'reps' => $reps !== null && $reps > 0 ? ["{$node->label}: {$reps} reps."] : [],
            'hold_seconds' => $holdSeconds !== null && $holdSeconds > 0 ? ["{$node->label}: {$holdSeconds} seconds."] : [],
            'load' => $loadValue !== null && $loadValue > 0 ? ["{$node->label}: ".self::formatNumber($loadValue).$loadUnit.'.'] : [],
            'quality' => $quality !== 'unknown' ? ["{$node->label}: {$quality} quality."] : [],
            default => [],
        };
    }

    private static function progressionCompletion(
        string $metricType,
        ?int $reps,
        ?int $holdSeconds,
        ?float $loadValue,
        string $quality,
    ): int {
        return match ($metricType) {
            'reps' => $reps === null ? 35 : min(95, 35 + ($reps * 8)),
            'hold_seconds' => $holdSeconds === null ? 35 : min(95, 35 + $holdSeconds),
            'load' => $loadValue === null ? 35 : min(95, 50 + (int) round($loadValue)),
            'quality' => match ($quality) {
                'clean' => 85,
                'solid' => 70,
                'rough' => 45,
                default => 35,
            },
            default => 35,
        };
    }

    private static function nodeMetricType(string $family, string $slug): string
    {
        return ProgressionGraphRegistry::node($family, $slug)?->metricType ?? 'quality';
    }

    /**
     * @param  list<string>  $observedEvidence
     * @param  list<string>  $missingEvidence
     */
    private static function placement(
        string $family,
        string $node,
        int $completionPercentage,
        array $observedEvidence,
        array $missingEvidence,
        float $confidenceContribution,
    ): BaselineNodePlacement {
        $currentNode = ProgressionGraphRegistry::node($family, $node);

        if ($currentNode === null) {
            throw new InvalidArgumentException(sprintf('Unknown progression node [%s] for family [%s].', $node, $family));
        }

        return new BaselineNodePlacement(
            family: $family,
            currentNode: $currentNode,
            nextNode: ProgressionGraphRegistry::nextNode($family, $node),
            completionPercentage: max(0, min(100, $completionPercentage)),
            observedEvidence: array_values(array_unique($observedEvidence)),
            missingEvidence: array_values(array_unique($missingEvidence)),
            confidenceContribution: round(max(0.0, min(1.0, $confidenceContribution)), 2),
        );
    }

    private static function progressionSlug(mixed $value): ?string
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return self::PROGRESSION_ALIASES[$value] ?? $value;
    }

    /**
     * @return array{load: float, reps: int|null, unit: string}|null
     */
    private static function weightedMovement(RoadmapInput $input, string $movement): ?array
    {
        $weightedBaselines = self::arrayValue($input->goalModules['weighted_baselines'] ?? []);
        $unit = self::stringValue($weightedBaselines['unit'] ?? null, self::stringValue($input->profileContext['bodyweight_unit'] ?? null, 'kg'));
        $movements = self::arrayValue($weightedBaselines['movements'] ?? []);

        foreach ($movements as $candidate) {
            if (! is_array($candidate) || ($candidate['movement'] ?? null) !== $movement) {
                continue;
            }

            $load = self::numberOrNull($candidate['external_load_value'] ?? null);

            if ($load === null || $load <= 0) {
                continue;
            }

            return [
                'load' => $load,
                'reps' => self::intOrNull($candidate['reps'] ?? null),
                'unit' => $unit,
            ];
        }

        return null;
    }

    /**
     * @param  array{load: float, reps: int|null, unit: string}  $weighted
     */
    private static function weightedEvidence(string $label, array $weighted): string
    {
        $reps = $weighted['reps'];

        if ($reps !== null) {
            return sprintf('%s: %s%s for %d reps.', $label, self::formatNumber($weighted['load']), $weighted['unit'], $reps);
        }

        return sprintf('%s: %s%s.', $label, self::formatNumber($weighted['load']), $weighted['unit']);
    }

    private static function rowLabel(string $variant): string
    {
        return match ($variant) {
            'ring_row' => 'Ring row',
            'low_bar_row' => 'Low bar row',
            'suspension_row' => 'Suspension row',
            default => 'Bodyweight row',
        };
    }

    private static function lowerBodyLabel(string $variant): string
    {
        return match ($variant) {
            'split_squat' => 'split squat',
            'step_down' => 'step-down',
            'pistol_progression' => 'pistol progression',
            default => 'bodyweight squat',
        };
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

    private static function numberValue(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }

    private static function stringValue(mixed $value, string $fallback): string
    {
        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    private static function formatNumber(float $value): string
    {
        if (floor($value) === $value) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }
}
