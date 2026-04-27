<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class RoadmapLayerEstimator
{
    private const array TARGET_NODES = [
        'strict_push_up' => ['push_up', 'first_push_up'],
        'one_arm_push_up' => ['push_up', 'one_arm_push_up'],
        'strict_pull_up' => ['pull_up', 'first_pull_up'],
        'weighted_pull_up' => ['pull_up', 'weighted_pull_up'],
        'strict_dip' => ['dip', 'first_dip'],
        'ring_dip' => ['dip', 'ring_dip'],
        'weighted_dip' => ['dip', 'weighted_dip'],
        'muscle_up' => ['muscle_up', 'strict_muscle_up'],
        'weighted_muscle_up' => ['muscle_up', 'weighted_muscle_up'],
        'l_sit' => ['compression', 'full_l_sit'],
        'v_sit' => ['compression', 'v_sit_prep'],
        'handstand' => ['handstand', 'freestanding_handstand'],
        'handstand_push_up' => ['hspu', 'full_wall_hspu'],
        'press_to_handstand' => ['handstand', 'freestanding_handstand'],
        'front_lever' => ['front_lever', 'full_front_lever'],
        'back_lever' => ['back_lever', 'full_back_lever'],
        'planche' => ['planche', 'full_planche'],
        'pistol_squat' => ['pistol_squat', 'full_pistol_squat'],
        'nordic_curl' => ['lower_body', 'nordic_curl'],
        'one_arm_pull_up' => ['one_arm_pull_up', 'one_arm_pull_up'],
        'human_flag' => ['human_flag', 'full_human_flag'],
    ];

    private const array HIGH_FORCE_SKILLS = [
        'weighted_pull_up',
        'weighted_dip',
        'weighted_muscle_up',
        'one_arm_pull_up',
        'planche',
        'front_lever',
        'human_flag',
    ];

    public static function fromInput(RoadmapInput $input): RoadmapLayerEstimate
    {
        $placements = BaselineNodeMapper::fromInput($input);
        $readiness = SkillReadinessCalculator::fromInput($input);
        $selection = GoalLaneSelector::fromInput($input);
        $primarySkill = $input->selectedPrimaryGoal
            ?? $selection->primaryLane?->skill
            ?? 'strict_push_up';
        $primaryReadiness = $readiness[$primarySkill] ?? $selection->primaryLane;
        $target = self::TARGET_NODES[$primarySkill] ?? [ProgressionGraphRegistry::targetFamily($primarySkill) ?? 'push_up', null];
        $family = $target[0];
        $targetNode = $target[1] ?? $placements[$family]->currentNode->slug;
        $currentPlacement = $placements[$family] ?? null;
        $primaryBase = $currentPlacement === null
            ? [12, 36]
            : self::edgeRange($family, $currentPlacement->currentNode->slug, $targetNode);
        $currentBlockBase = $currentPlacement === null || $currentPlacement->nextNode === null
            ? [4, 8]
            : [4, 8];
        $modifiers = self::modifiers($primarySkill, $input, $primaryReadiness);
        $primaryEta = self::eta($primaryBase[0], $primaryBase[1], $primaryReadiness?->confidence ?? 0.25, $modifiers);
        $currentBlockEta = self::eta($currentBlockBase[0], $currentBlockBase[1], $primaryReadiness?->confidence ?? 0.25, $modifiers);
        $forecastEta = self::eta(12, 26, $primaryReadiness?->confidence ?? 0.25, $modifiers);
        $aspirationEta = self::eta(26, 104, min(0.55, $primaryReadiness?->confidence ?? 0.25), $modifiers);
        $lanes = array_values(array_filter([
            $selection->primaryLane?->skill ?? $primarySkill,
            $selection->secondaryLane?->skill,
            $selection->foundationLane['slug'],
        ], is_string(...)));

        return new RoadmapLayerEstimate(
            primarySkill: $primarySkill,
            primaryEta: $primaryEta,
            currentBlock: [
                'label' => 'Current 4-8 week prescriptive block',
                'eta' => $currentBlockEta,
                'lanes' => $lanes,
            ],
            forecast: [
                'label' => '3-6 month forecast',
                'eta' => $forecastEta,
                'lanes' => $lanes,
            ],
            aspirationLayer: [
                'label' => '6-24+ month aspiration layer',
                'eta' => $aspirationEta,
                'lanes' => array_values(array_unique([...$lanes, ...$input->longTermAspirations])),
            ],
            retestCadence: [
                'session updates',
                'weekly review',
                '4-6 week block retest',
                'seasonal 12-24 week goal review',
            ],
        );
    }

    /**
     * @return array{0: int, 1: int}
     */
    private static function edgeRange(string $family, string $currentNode, string $targetNode): array
    {
        $nodes = ProgressionGraphRegistry::require($family)->nodes();
        $currentIndex = self::nodeIndex($nodes, $currentNode);
        $targetIndex = self::nodeIndex($nodes, $targetNode);

        if ($currentIndex === null || $targetIndex === null) {
            return [12, 36];
        }

        if ($targetIndex <= $currentIndex) {
            return [4, 8];
        }

        $min = 0;
        $max = 0;

        for ($index = $currentIndex + 1; $index <= $targetIndex; $index++) {
            $min += $nodes[$index]->minEdgeWeeks;
            $max += $nodes[$index]->maxEdgeWeeks;
        }

        return [max(4, $min), max(8, $max)];
    }

    /**
     * @param  list<ProgressionGraphNode>  $nodes
     */
    private static function nodeIndex(array $nodes, string $slug): ?int
    {
        foreach ($nodes as $index => $node) {
            if ($node->slug === $slug) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function modifiers(string $skill, RoadmapInput $input, ?SkillReadiness $readiness): array
    {
        $modifiers = [];
        $painLevel = self::intOrNull($input->painFlags['level'] ?? null);
        $weightTrend = is_string($input->profileContext['weight_trend'] ?? null) ? $input->profileContext['weight_trend'] : 'unknown';
        $age = self::intOrNull($input->profileContext['age_years'] ?? null);
        $trainingAge = self::intOrNull($input->trainingContext['training_age_months'] ?? null);
        $bodyweight = self::numberOrNull($input->profileContext['current_bodyweight_value'] ?? null);
        $height = self::numberOrNull($input->profileContext['height_value'] ?? null);

        if ($readiness !== null && $readiness->confidence < 0.45) {
            $modifiers[] = 'Missing objective data widens the ETA range.';
        }

        if ($readiness !== null && in_array('Required equipment is missing.', $readiness->hardBlockers, true)) {
            $modifiers[] = 'Missing required equipment widens the ETA range.';
        }

        if ($painLevel !== null && $painLevel >= 4) {
            $modifiers[] = 'Pain widens the ETA range.';
        }

        if ($weightTrend === 'cutting' && in_array($skill, self::HIGH_FORCE_SKILLS, true)) {
            $modifiers[] = 'Cutting phase widens high-force strength ETA.';
        }

        if ($age !== null && $age >= 40) {
            $modifiers[] = 'Age recovery modifier widens the ETA range.';
        }

        if ($trainingAge === null || $trainingAge < 6) {
            $modifiers[] = 'Training age uncertainty widens the ETA range.';
        }

        if (($bodyweight === null || $height === null) && in_array($skill, self::HIGH_FORCE_SKILLS, true)) {
            $modifiers[] = 'Body-lever context is incomplete.';
        }

        $modifiers[] = 'Adherence placeholder uses neutral consistency until workout history exists.';

        return array_values(array_unique($modifiers));
    }

    /**
     * @param  list<string>  $modifiers
     */
    private static function eta(int $lowerWeeks, int $upperWeeks, float $baseConfidence, array $modifiers): RoadmapEtaRange
    {
        $widening = 0.0;
        $confidencePenalty = 0.0;

        foreach ($modifiers as $modifier) {
            if ($modifier === 'Missing objective data widens the ETA range.') {
                $widening += 0.65;
                $confidencePenalty += 0.25;
            } elseif ($modifier === 'Missing required equipment widens the ETA range.') {
                $widening += 0.55;
                $confidencePenalty += 0.25;
            } elseif ($modifier === 'Pain widens the ETA range.') {
                $widening += 0.6;
                $confidencePenalty += 0.2;
            } elseif ($modifier === 'Cutting phase widens high-force strength ETA.') {
                $widening += 0.2;
                $confidencePenalty += 0.05;
            } elseif ($modifier === 'Training age uncertainty widens the ETA range.') {
                $widening += 0.25;
                $confidencePenalty += 0.08;
            } elseif ($modifier === 'Body-lever context is incomplete.') {
                $widening += 0.2;
                $confidencePenalty += 0.08;
            } elseif ($modifier === 'Age recovery modifier widens the ETA range.') {
                $widening += 0.15;
                $confidencePenalty += 0.04;
            }
        }

        $lower = max(1, (int) floor($lowerWeeks * (1 + ($widening * 0.35))));
        $upper = max($lower + 1, (int) ceil($upperWeeks * (1 + $widening)));
        $p50 = (int) round(($lower + $upper) / 2);
        $p80 = (int) round($lower + (($upper - $lower) * 0.8));
        $confidence = round(max(0.05, min(1.0, $baseConfidence - $confidencePenalty)), 2);

        return new RoadmapEtaRange(
            lowerWeeks: $lower,
            upperWeeks: $upper,
            p50Weeks: $p50,
            p80Weeks: $p80,
            confidence: $confidence,
            modifiers: $modifiers,
        );
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
