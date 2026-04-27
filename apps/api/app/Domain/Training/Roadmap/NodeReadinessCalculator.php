<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

use App\Domain\Training\Support\CalisthenicsPlacementOptions;

final class NodeReadinessCalculator
{
    private const array HIGH_FORCE_SKILLS = [
        'weighted_pull_up',
        'weighted_dip',
        'weighted_muscle_up',
        'one_arm_pull_up',
        'planche',
        'front_lever',
        'back_lever',
        'human_flag',
    ];

    private const array KNOWN_MOBILITY_REQUIREMENTS = [
        'wrist_extension',
        'shoulder_flexion',
        'shoulder_extension',
        'ankle_dorsiflexion',
        'pancake_compression',
    ];

    /**
     * @return array<string, NodeReadiness>
     */
    public static function fromInput(RoadmapInput $input): array
    {
        $placements = BaselineNodeMapper::fromInput($input);
        $domains = DomainScoreCalculator::fromPlacements($placements, $input);
        $readiness = [];

        foreach (CalisthenicsPlacementOptions::TARGET_SKILLS as $skill) {
            $readiness[$skill] = self::forSkill($skill, $input, $placements, $domains);
        }

        return $readiness;
    }

    /**
     * @param  array<string, BaselineNodePlacement>|null  $placements
     * @param  array<string, DomainScore>|null  $domains
     */
    public static function forSkill(
        string $skill,
        RoadmapInput $input,
        ?array $placements = null,
        ?array $domains = null,
    ): NodeReadiness {
        $placements ??= BaselineNodeMapper::fromInput($input);
        $domains ??= DomainScoreCalculator::fromPlacements($placements, $input);

        $family = ProgressionGraphRegistry::targetFamily($skill) ?? 'push_up';
        $placement = $placements[$family] ?? null;
        $currentSlug = $placement?->currentNode->slug;
        $path = ProgressionGraphRegistry::targetNodePath($skill, $currentSlug);

        if ($path === null) {
            $path = ProgressionGraphRegistry::targetNodePath('strict_push_up');
        }

        /** @var array{family: string, current: ProgressionGraphNode, next: ProgressionGraphNode|null, target: ProgressionGraphNode} $path */
        $graph = ProgressionGraphRegistry::require($path['family']);
        $currentNode = $path['current'];
        $targetNode = $path['target'];
        $nextNode = self::nextNodeForTarget($graph, $currentNode, $targetNode);
        $edge = $nextNode === null ? null : $graph->edge($currentNode->slug, $nextNode->slug);
        $nodeForScoring = $nextNode ?? $targetNode;

        $blockers = [];
        $warnings = [];
        $positiveReasons = [];

        self::applyEquipment($input, $nodeForScoring, $blockers, $positiveReasons);
        self::applyMobility($input, $nodeForScoring, $blockers, $warnings, $positiveReasons);
        self::applyPain($input, $skill, $nodeForScoring, $blockers, $warnings);

        $domainFit = self::domainFit($domains, $edge, $nodeForScoring, $warnings, $positiveReasons);
        $graphFit = self::graphFit($placement, $currentNode, $targetNode, $positiveReasons);
        $tissueFit = self::tissueFit($domains, $nodeForScoring, $warnings);
        $mobilityFit = self::mobilityFit($input, $nodeForScoring);
        $contextFit = self::contextFit($input, $skill, $warnings, $positiveReasons);
        $uncertaintyPenalty = self::uncertaintyPenalty($domains, $edge, $nodeForScoring, $placement);
        $painPenalty = self::painPenalty($input, $skill, $nodeForScoring);
        $selectedBonus = self::isExplicitTarget($input, $skill) ? 5 : 0;

        $score = (int) round(
            ($domainFit * 0.35)
            + ($graphFit * 0.2)
            + ($tissueFit * 0.15)
            + ($mobilityFit * 0.1)
            + ($contextFit * 0.2)
            + $selectedBonus
            - $uncertaintyPenalty
            - $painPenalty,
        );
        $score = max(0, min(100, $score));
        $confidence = self::confidence($domains, $edge, $nodeForScoring, $placement);
        $hasHardGate = $blockers !== [];
        $pendingTests = RoadmapMicroTestRequestGenerator::fromInput($input);
        $hasPendingForSkill = self::hasPendingForSkill($pendingTests, $skill);
        $threshold = self::threshold($nodeForScoring, self::hasRelatedPain($input, $nodeForScoring));
        $status = self::status($input, $skill, $score, $confidence, $threshold, $hasHardGate, $hasPendingForSkill);

        if ($status === 'blocked_pending_input') {
            $blockers[] = 'Complete a focused micro-test before assigning a precise next-node ETA.';
        }

        if ($status === 'bridge_recommended') {
            $warnings[] = 'Selected target stays visible, but the next bridge node is the safer training step.';
        }

        if ($hasHardGate) {
            $score = min($score, 50);
        }

        $etaToNext = self::eta($edge, $input, $skill, $nodeForScoring, $confidence, $status);
        $etaToTarget = $currentNode->nodeId === $targetNode->nodeId
            ? null
            : self::targetEta($graph, $currentNode, $targetNode, $input, $skill, $nodeForScoring, $confidence, $status);

        return new NodeReadiness(
            skillTrackId: $skill,
            targetNode: $targetNode,
            currentNode: $currentNode,
            nextNode: $nextNode,
            status: $status,
            readinessScore: $score,
            confidence: $confidence,
            blockers: self::unique($blockers),
            warnings: self::unique($warnings),
            positiveReasons: self::unique($positiveReasons),
            etaToNextNode: $etaToNext,
            etaToTarget: $etaToTarget,
        );
    }

    private static function nextNodeForTarget(ProgressionGraph $graph, ProgressionGraphNode $currentNode, ProgressionGraphNode $targetNode): ?ProgressionGraphNode
    {
        if ($currentNode->order >= $targetNode->order) {
            return $targetNode;
        }

        $next = $graph->nextNode($currentNode->slug);

        if ($next === null) {
            return $targetNode;
        }

        return $next->order > $targetNode->order ? $targetNode : $next;
    }

    /**
     * @param  list<string>  $blockers
     * @param  list<string>  $positiveReasons
     */
    private static function applyEquipment(RoadmapInput $input, ProgressionGraphNode $node, array &$blockers, array &$positiveReasons): void
    {
        foreach ($node->requiredEquipment as $equipment) {
            if (self::hasEquipment($input->equipment, $equipment)) {
                $positiveReasons[] = "Equipment available for {$node->label}.";

                continue;
            }

            $blockers[] = "Required equipment for {$node->label} is missing: {$equipment}.";
        }
    }

    /**
     * @param  list<string>  $equipment
     */
    private static function hasEquipment(array $equipment, string $required): bool
    {
        if (in_array($required, $equipment, true)) {
            return true;
        }

        return match ($required) {
            'rings_or_pull_up_bar' => array_intersect(['rings', 'pull_up_bar'], $equipment) !== [],
            'low_bar_or_rings' => array_intersect(['low_bar', 'rings'], $equipment) !== [],
            'straight_bar' => in_array('pull_up_bar', $equipment, true),
            'external_load' => array_intersect(['dip_belt', 'weighted_vest', 'barbell', 'dumbbell', 'kettlebell'], $equipment) !== [],
            'vertical_pole_or_ladder' => array_intersect(['stall_bars', 'vertical_pole', 'ladder'], $equipment) !== [],
            'kettlebell_or_dumbbell' => array_intersect(['kettlebell', 'dumbbell'], $equipment) !== [],
            default => false,
        };
    }

    /**
     * @param  list<string>  $blockers
     * @param  list<string>  $warnings
     * @param  list<string>  $positiveReasons
     */
    private static function applyMobility(
        RoadmapInput $input,
        ProgressionGraphNode $node,
        array &$blockers,
        array &$warnings,
        array &$positiveReasons,
    ): void {
        $checks = self::arrayValue($input->goalModules['mobility_checks'] ?? []);

        foreach ($node->mobilityRequirements as $requirement) {
            if (! in_array($requirement, self::KNOWN_MOBILITY_REQUIREMENTS, true)) {
                continue;
            }

            $status = self::stringValue($checks[$requirement] ?? null, 'not_tested');
            $label = self::mobilityLabel($requirement);

            if (in_array($status, ['blocked', 'painful'], true)) {
                $blockers[] = "{$label} is {$status} for {$node->label}.";
            } elseif ($status === 'limited') {
                $warnings[] = "{$label} is limited, so the next node should stay conservative.";
            } elseif ($status === 'clear') {
                $positiveReasons[] = "{$label} is clear.";
            } else {
                $warnings[] = "{$label} is not tested yet.";
            }
        }
    }

    /**
     * @param  list<string>  $blockers
     * @param  list<string>  $warnings
     */
    private static function applyPain(RoadmapInput $input, string $skill, ProgressionGraphNode $node, array &$blockers, array &$warnings): void
    {
        $painLevel = self::intOrNull($input->painFlags['level'] ?? null);
        $relatedPain = self::hasRelatedPain($input, $node);

        if ($painLevel !== null && $painLevel >= 7 && in_array($node->fatigueClass, ['high', 'max'], true)) {
            $blockers[] = "Pain is too high for loaded {$node->label} progression.";

            return;
        }

        if ($relatedPain && in_array($node->fatigueClass, ['high', 'max'], true)) {
            $warnings[] = "Related pain keeps {$node->label} below progression threshold.";
        }

        if ($painLevel !== null && $painLevel >= 4) {
            $warnings[] = "Pain widens the ETA range for {$skill}.";
        }
    }

    /**
     * @param  array<string, DomainScore>  $domains
     * @param  list<string>  $warnings
     * @param  list<string>  $positiveReasons
     */
    private static function domainFit(
        array $domains,
        ?ProgressionGraphEdge $edge,
        ProgressionGraphNode $node,
        array &$warnings,
        array &$positiveReasons,
    ): float {
        $minimums = $edge?->minimumDomainScores ?? array_fill_keys($node->primaryDomains, self::threshold($node, false));

        if ($minimums === []) {
            return 55.0;
        }

        $fits = [];
        foreach ($minimums as $domain => $minimum) {
            $score = $domains[$domain]->score ?? null;

            if ($score === null) {
                $warnings[] = "{$domain} score is missing.";
                $fits[] = 25;

                continue;
            }

            $fits[] = min(100, (int) round(($score / max(1, $minimum)) * 80));

            if ($score >= $minimum) {
                $positiveReasons[] = "{$domains[$domain]->label} clears the {$node->label} minimum.";
            } else {
                $warnings[] = "{$domains[$domain]->label} is below the {$node->label} minimum.";
            }
        }

        return min($fits);
    }

    /**
     * @param  list<string>  $positiveReasons
     */
    private static function graphFit(?BaselineNodePlacement $placement, ProgressionGraphNode $currentNode, ProgressionGraphNode $targetNode, array &$positiveReasons): float
    {
        if ($placement === null) {
            return 20.0;
        }

        $positiveReasons[] = "Current graph placement is {$currentNode->label}.";

        if ($currentNode->order >= $targetNode->order) {
            return 100.0;
        }

        return max(20.0, min(100.0, $placement->completionPercentage + 15.0));
    }

    /**
     * @param  array<string, DomainScore>  $domains
     * @param  list<string>  $warnings
     */
    private static function tissueFit(array $domains, ProgressionGraphNode $node, array &$warnings): float
    {
        $keys = array_values(array_filter(
            $node->primaryDomains,
            static fn (string $domain): bool => in_array($domain, [
                'wrist_loaded_extension',
                'elbow_pull_tendon',
                'elbow_push_tendon',
                'shoulder_flexion',
                'shoulder_extension',
                'shoulder_straight_arm',
                'ankle_dorsiflexion',
                'recovery_capacity',
            ], true),
        ));

        if ($keys === []) {
            return 75.0;
        }

        $scores = [];
        foreach ($keys as $key) {
            $score = $domains[$key]->score ?? 45;
            $scores[] = $score;

            if ($score < 55) {
                $warnings[] = "{$key} is a tissue weak link for {$node->label}.";
            }
        }

        return (float) min($scores);
    }

    private static function mobilityFit(RoadmapInput $input, ProgressionGraphNode $node): float
    {
        $checks = self::arrayValue($input->goalModules['mobility_checks'] ?? []);
        $known = array_values(array_intersect($node->mobilityRequirements, self::KNOWN_MOBILITY_REQUIREMENTS));

        if ($known === []) {
            return 75.0;
        }

        $scores = [];
        foreach ($known as $key) {
            $scores[] = match (self::stringValue($checks[$key] ?? null, 'not_tested')) {
                'clear' => 95,
                'limited' => 55,
                'blocked', 'painful' => 10,
                default => 45,
            };
        }

        return (float) min($scores);
    }

    /**
     * @param  list<string>  $warnings
     * @param  list<string>  $positiveReasons
     */
    private static function contextFit(RoadmapInput $input, string $skill, array &$warnings, array &$positiveReasons): float
    {
        $score = 65.0;
        $sessions = self::intOrNull($input->trainingContext['weekly_session_goal'] ?? null) ?? 3;
        $trainingAge = self::intOrNull($input->trainingContext['training_age_months'] ?? null);
        $age = self::intOrNull($input->profileContext['age_years'] ?? null);
        $bodyweight = self::numberOrNull($input->profileContext['current_bodyweight_value'] ?? null);
        $height = self::numberOrNull($input->profileContext['height_value'] ?? null);

        if ($sessions >= 4) {
            $score += 10;
            $positiveReasons[] = 'Weekly frequency can support multiple exposures.';
        } elseif ($sessions <= 2) {
            $score -= 10;
            $warnings[] = 'Low weekly frequency slows node progress.';
        }

        if ($trainingAge === null) {
            $score -= 8;
            $warnings[] = 'Training age is missing.';
        } elseif ($trainingAge < 6) {
            $score -= 8;
            $warnings[] = 'Training age is still early for aggressive node jumps.';
        } else {
            $score += 5;
            $positiveReasons[] = 'Training age supports structured progression.';
        }

        if ($age !== null && $age >= 40) {
            $score -= 5;
            $warnings[] = 'Recovery margin should be more conservative.';
        }

        if (($bodyweight === null || $height === null) && in_array($skill, self::HIGH_FORCE_SKILLS, true)) {
            $score -= 8;
            $warnings[] = 'Body context is incomplete for a high-force skill.';
        }

        return max(0.0, min(100.0, $score));
    }

    /**
     * @param  array<string, DomainScore>  $domains
     */
    private static function uncertaintyPenalty(array $domains, ?ProgressionGraphEdge $edge, ProgressionGraphNode $node, ?BaselineNodePlacement $placement): float
    {
        $minimums = $edge?->minimumDomainScores ?? array_fill_keys($node->primaryDomains, 60);
        $uncertainty = [];

        foreach (array_keys($minimums) as $domain) {
            $uncertainty[] = $domains[$domain]->uncertainty ?? 0.8;
        }

        $domainPenalty = $uncertainty === [] ? 8.0 : (array_sum($uncertainty) / count($uncertainty)) * 18;
        $placementPenalty = $placement === null ? 12.0 : (1.0 - $placement->confidenceContribution) * 10;

        return $domainPenalty + $placementPenalty;
    }

    private static function painPenalty(RoadmapInput $input, string $skill, ProgressionGraphNode $node): float
    {
        $painLevel = self::intOrNull($input->painFlags['level'] ?? null);

        if ($painLevel === null || $painLevel <= 0) {
            return 0.0;
        }

        $penalty = $painLevel >= 7 ? 50.0 : ($painLevel >= 4 ? 22.0 : 6.0);

        if (self::hasRelatedPain($input, $node) || in_array($skill, self::HIGH_FORCE_SKILLS, true)) {
            $penalty += 8.0;
        }

        return $penalty;
    }

    /**
     * @param  array<string, DomainScore>  $domains
     */
    private static function confidence(array $domains, ?ProgressionGraphEdge $edge, ProgressionGraphNode $node, ?BaselineNodePlacement $placement): float
    {
        $minimums = $edge?->minimumDomainScores ?? array_fill_keys($node->primaryDomains, 60);
        $domainConfidence = [];

        foreach (array_keys($minimums) as $domain) {
            $domainConfidence[] = $domains[$domain]->confidence ?? 0.15;
        }

        $domainPart = $domainConfidence === [] ? 0.35 : array_sum($domainConfidence) / count($domainConfidence);
        $placementPart = $placement?->confidenceContribution ?? 0.1;
        $missingPenalty = $placement !== null && $placement->missingEvidence !== [] ? 0.12 : 0.0;

        return round(max(0.0, min(1.0, ($domainPart * 0.65) + ($placementPart * 0.35) - $missingPenalty)), 2);
    }

    private static function threshold(ProgressionGraphNode $node, bool $relatedPain): int
    {
        if ($relatedPain && in_array($node->fatigueClass, ['high', 'max'], true)) {
            return 85;
        }

        return match ($node->fatigueClass) {
            'max' => 80,
            'high' => 80,
            'medium' => 70,
            default => 60,
        };
    }

    private static function status(
        RoadmapInput $input,
        string $skill,
        int $score,
        float $confidence,
        int $threshold,
        bool $hasHardGate,
        bool $hasPendingForSkill,
    ): string {
        if ($hasHardGate) {
            return 'blocked_by_hard_gate';
        }

        if ($confidence < 0.3 && $hasPendingForSkill) {
            return 'blocked_pending_input';
        }

        if ($score >= $threshold) {
            return 'ready_for_next_node';
        }

        if (self::isExplicitTarget($input, $skill)) {
            return 'bridge_recommended';
        }

        return 'long_term_visible';
    }

    private static function hasRelatedPain(RoadmapInput $input, ProgressionGraphNode $node): bool
    {
        $regions = self::arrayValue($input->painFlags['regions'] ?? []);

        foreach ($node->contraindicatedPainKeys as $key) {
            $pain = self::arrayValue($regions[$key] ?? []);

            if ($pain === []) {
                continue;
            }

            if (($pain['severity'] ?? 'none') !== 'none' || ($pain['status'] ?? 'none') !== 'none') {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array<string, mixed>>  $pendingTests
     */
    private static function hasPendingForSkill(array $pendingTests, string $skill): bool
    {
        foreach ($pendingTests as $pendingTest) {
            if (($pendingTest['target_skill'] ?? null) === $skill) {
                return true;
            }
        }

        return false;
    }

    private static function isExplicitTarget(RoadmapInput $input, string $skill): bool
    {
        return $input->selectedPrimaryGoal === $skill
            || in_array($skill, $input->secondaryInterests, true)
            || in_array($skill, $input->longTermAspirations, true);
    }

    /**
     * @return array<string, mixed>
     */
    private static function eta(
        ?ProgressionGraphEdge $edge,
        RoadmapInput $input,
        string $skill,
        ProgressionGraphNode $node,
        float $confidence,
        string $status,
    ): array {
        if ($status === 'blocked_by_hard_gate') {
            return self::unknownEta('Blocked by hard gate', $confidence, ['Hard gates must change before ETA is useful.']);
        }

        if ($status === 'blocked_pending_input') {
            return self::unknownEta('Needs micro-test first', $confidence, ['Focused micro-tests should come before ETA estimates.']);
        }

        $lower = $edge?->p25Weeks ?? max(2, $node->minEdgeWeeks);
        $upper = $edge?->p80Weeks ?? max(4, $node->maxEdgeWeeks);

        return self::etaFromRange($lower, $upper, $input, $skill, $node, $confidence);
    }

    /**
     * @return array<string, mixed>
     */
    private static function targetEta(
        ProgressionGraph $graph,
        ProgressionGraphNode $currentNode,
        ProgressionGraphNode $targetNode,
        RoadmapInput $input,
        string $skill,
        ProgressionGraphNode $node,
        float $confidence,
        string $status,
    ): array {
        if ($status === 'blocked_by_hard_gate') {
            return self::unknownEta('Blocked by hard gate', $confidence, ['Hard gates must change before target ETA is useful.']);
        }

        if ($status === 'blocked_pending_input') {
            return self::unknownEta('Needs micro-test first', $confidence, ['Focused micro-tests should come before target ETA estimates.']);
        }

        $lower = 0;
        $upper = 0;
        $counting = false;

        foreach ($graph->nodes() as $candidate) {
            if ($candidate->slug === $currentNode->slug) {
                $counting = true;

                continue;
            }

            if (! $counting) {
                continue;
            }

            $lower += $candidate->minEdgeWeeks;
            $upper += $candidate->maxEdgeWeeks;

            if ($candidate->slug === $targetNode->slug) {
                break;
            }
        }

        return self::etaFromRange(max(1, $lower), max(2, $upper), $input, $skill, $node, min(0.75, $confidence));
    }

    /**
     * @param  list<string>  $modifiers
     * @return array<string, mixed>
     */
    private static function unknownEta(string $label, float $confidence, array $modifiers): array
    {
        return [
            'min_weeks' => null,
            'max_weeks' => null,
            'p50_weeks' => null,
            'p80_weeks' => null,
            'label' => $label,
            'confidence' => $confidence,
            'modifiers' => $modifiers,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function etaFromRange(
        int $lowerWeeks,
        int $upperWeeks,
        RoadmapInput $input,
        string $skill,
        ProgressionGraphNode $node,
        float $confidence,
    ): array {
        $modifiers = ['Edge priors set the base ETA range.'];
        $widening = 0.0;
        $confidencePenalty = 0.0;
        $painLevel = self::intOrNull($input->painFlags['level'] ?? null);
        $sessions = self::intOrNull($input->trainingContext['weekly_session_goal'] ?? null) ?? 3;
        $trainingAge = self::intOrNull($input->trainingContext['training_age_months'] ?? null);
        $age = self::intOrNull($input->profileContext['age_years'] ?? null);
        $weightTrend = self::stringValue($input->profileContext['weight_trend'] ?? null, 'unknown');

        if ($confidence < 0.45) {
            $modifiers[] = 'Missing objective data widens the ETA range.';
            $widening += 0.65;
            $confidencePenalty += 0.22;
        }

        if ($painLevel !== null && $painLevel >= 4) {
            $modifiers[] = 'Pain widens the ETA range.';
            $widening += 0.6;
            $confidencePenalty += 0.18;
        }

        if ($sessions <= 2) {
            $modifiers[] = 'Low weekly frequency widens the ETA range.';
            $widening += 0.3;
            $confidencePenalty += 0.06;
        }

        if ($trainingAge === null || $trainingAge < 6) {
            $modifiers[] = 'Training age uncertainty widens the ETA range.';
            $widening += 0.25;
            $confidencePenalty += 0.06;
        }

        if ($age !== null && $age >= 40) {
            $modifiers[] = 'Age recovery modifier widens the ETA range.';
            $widening += 0.15;
            $confidencePenalty += 0.04;
        }

        if ($weightTrend === 'cutting' && in_array($skill, self::HIGH_FORCE_SKILLS, true)) {
            $modifiers[] = 'Cutting phase widens high-force strength ETA.';
            $widening += 0.2;
            $confidencePenalty += 0.05;
        }

        if (self::hasRelatedPain($input, $node)) {
            $modifiers[] = 'Related joint history keeps the ETA conservative.';
            $widening += 0.2;
            $confidencePenalty += 0.05;
        }

        $lower = max(1, (int) floor($lowerWeeks * (1 + ($widening * 0.3))));
        $upper = max($lower + 1, (int) ceil($upperWeeks * (1 + $widening)));
        $p50 = (int) round(($lower + $upper) / 2);
        $p80 = (int) round($lower + (($upper - $lower) * 0.8));

        return [
            'min_weeks' => $lower,
            'max_weeks' => $upper,
            'p50_weeks' => $p50,
            'p80_weeks' => $p80,
            'label' => "{$lower}-{$upper} weeks",
            'confidence' => round(max(0.05, min(1.0, $confidence - $confidencePenalty)), 2),
            'modifiers' => array_values(array_unique($modifiers)),
        ];
    }

    private static function mobilityLabel(string $key): string
    {
        return match ($key) {
            'wrist_extension' => 'Wrist extension',
            'shoulder_flexion' => 'Shoulder flexion',
            'shoulder_extension' => 'Shoulder extension',
            'ankle_dorsiflexion' => 'Ankle dorsiflexion',
            'pancake_compression' => 'Pancake compression',
            default => str_replace('_', ' ', ucfirst($key)),
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

    private static function stringValue(mixed $value, string $fallback): string
    {
        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private static function unique(array $values): array
    {
        return array_values(array_unique(array_filter($values, static fn (string $value): bool => $value !== '')));
    }
}
