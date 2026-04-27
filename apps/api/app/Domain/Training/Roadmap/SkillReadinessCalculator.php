<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

use App\Domain\Training\Support\CalisthenicsPlacementOptions;

final class SkillReadinessCalculator
{
    private const array SKILL_LABELS = [
        'strict_push_up' => 'Push-up',
        'one_arm_push_up' => 'One-arm push-up',
        'strict_pull_up' => 'Pull-up',
        'weighted_pull_up' => 'Weighted pull-up',
        'strict_dip' => 'Dip',
        'ring_dip' => 'Ring dip',
        'weighted_dip' => 'Weighted dip',
        'muscle_up' => 'Muscle-up',
        'weighted_muscle_up' => 'Weighted muscle-up',
        'l_sit' => 'L-sit',
        'v_sit' => 'V-sit',
        'handstand' => 'Handstand',
        'handstand_push_up' => 'Handstand push-up',
        'press_to_handstand' => 'Press to handstand',
        'front_lever' => 'Front lever',
        'back_lever' => 'Back lever',
        'planche' => 'Planche',
        'pistol_squat' => 'Pistol squat',
        'nordic_curl' => 'Nordic curl',
        'one_arm_pull_up' => 'One-arm pull-up',
        'human_flag' => 'Human flag',
    ];

    private const array DEFINITIONS = [
        'strict_push_up' => [
            'domains' => ['vertical_push' => 0.65, 'trunk_rigidity' => 0.2, 'tissue_tolerance' => 0.15],
            'minimum_nodes' => [['push_up', 'first_push_up', 'Minimum push-up node is not reached yet.']],
            'family' => 'push_up',
        ],
        'one_arm_push_up' => [
            'domains' => ['vertical_push' => 0.55, 'trunk_rigidity' => 0.25, 'tissue_tolerance' => 0.2],
            'minimum_nodes' => [['push_up', 'archer_push_up', 'Minimum push-up node is not reached yet.']],
            'family' => 'push_up',
            'advanced_anthropometry' => true,
            'high_fatigue' => true,
        ],
        'strict_pull_up' => [
            'domains' => ['vertical_pull' => 0.75, 'tissue_tolerance' => 0.25],
            'equipment_groups' => [['pull_up_bar', 'rings']],
            'minimum_nodes' => [['pull_up', 'first_pull_up', 'Minimum pull-up node is not reached yet.']],
            'family' => 'pull_up',
        ],
        'weighted_pull_up' => [
            'domains' => ['vertical_pull' => 0.7, 'tissue_tolerance' => 0.3],
            'equipment_groups' => [['pull_up_bar', 'rings'], ['dip_belt', 'weighted_vest']],
            'minimum_nodes' => [['pull_up', 'three_by_eight_pull_ups', 'Minimum pull-up node is not reached yet.']],
            'family' => 'pull_up',
            'high_fatigue' => true,
        ],
        'strict_dip' => [
            'domains' => ['vertical_push' => 0.7, 'tissue_tolerance' => 0.3],
            'equipment_groups' => [['dip_bars', 'rings', 'parallettes']],
            'minimum_nodes' => [['dip', 'first_dip', 'Minimum dip node is not reached yet.']],
            'family' => 'dip',
        ],
        'ring_dip' => [
            'domains' => ['vertical_push' => 0.55, 'tissue_tolerance' => 0.3, 'trunk_rigidity' => 0.15],
            'equipment_groups' => [['rings']],
            'minimum_nodes' => [['dip', 'deep_dip_capacity', 'Minimum dip node is not reached yet.']],
            'family' => 'dip',
            'high_fatigue' => true,
        ],
        'weighted_dip' => [
            'domains' => ['vertical_push' => 0.65, 'tissue_tolerance' => 0.35],
            'equipment_groups' => [['dip_bars', 'rings'], ['dip_belt', 'weighted_vest']],
            'minimum_nodes' => [['dip', 'deep_dip_capacity', 'Minimum dip node is not reached yet.']],
            'family' => 'dip',
            'high_fatigue' => true,
        ],
        'muscle_up' => [
            'domains' => ['vertical_pull' => 0.45, 'vertical_push' => 0.35, 'horizontal_pull_straight_arm_pull' => 0.1, 'tissue_tolerance' => 0.1],
            'equipment_groups' => [['pull_up_bar', 'rings'], ['dip_bars', 'rings']],
            'minimum_nodes' => [
                ['pull_up', 'three_by_eight_pull_ups', 'Minimum pull-up node is not reached yet.'],
                ['dip', 'multiple_dips', 'Minimum dip node is not reached yet.'],
            ],
            'family' => 'muscle_up',
            'high_fatigue' => true,
        ],
        'weighted_muscle_up' => [
            'domains' => ['vertical_pull' => 0.45, 'vertical_push' => 0.35, 'tissue_tolerance' => 0.2],
            'equipment_groups' => [['pull_up_bar', 'rings'], ['dip_bars', 'rings'], ['dip_belt', 'weighted_vest']],
            'minimum_nodes' => [['muscle_up', 'strict_muscle_up', 'Minimum muscle-up node is not reached yet.']],
            'family' => 'muscle_up',
            'advanced_anthropometry' => true,
            'high_fatigue' => true,
        ],
        'l_sit' => [
            'domains' => ['compression' => 0.6, 'trunk_rigidity' => 0.25, 'tissue_tolerance' => 0.15],
            'minimum_nodes' => [['compression', 'tuck_l_sit', 'Minimum compression node is not reached yet.']],
            'family' => 'compression',
        ],
        'v_sit' => [
            'domains' => ['compression' => 0.65, 'trunk_rigidity' => 0.2, 'tissue_tolerance' => 0.15],
            'minimum_nodes' => [['compression', 'full_l_sit', 'Minimum compression node is not reached yet.']],
            'family' => 'compression',
            'advanced_anthropometry' => true,
        ],
        'handstand' => [
            'domains' => ['inversion_balance' => 0.55, 'trunk_rigidity' => 0.25, 'tissue_tolerance' => 0.2],
            'mobility' => ['wrist_extension', 'shoulder_flexion'],
            'family' => 'handstand',
            'line_skill' => true,
        ],
        'handstand_push_up' => [
            'domains' => ['inversion_balance' => 0.45, 'vertical_push' => 0.35, 'tissue_tolerance' => 0.2],
            'mobility' => ['wrist_extension', 'shoulder_flexion'],
            'minimum_nodes' => [['hspu', 'pike_push_up', 'Minimum handstand push-up node is not reached yet.']],
            'family' => 'hspu',
            'high_fatigue' => true,
        ],
        'press_to_handstand' => [
            'domains' => ['compression' => 0.4, 'inversion_balance' => 0.35, 'trunk_rigidity' => 0.25],
            'mobility' => ['wrist_extension', 'pancake_compression'],
            'minimum_nodes' => [['compression', 'full_l_sit', 'Minimum compression node is not reached yet.']],
            'family' => 'handstand',
            'advanced_anthropometry' => true,
        ],
        'front_lever' => [
            'domains' => ['horizontal_pull_straight_arm_pull' => 0.5, 'vertical_pull' => 0.25, 'trunk_rigidity' => 0.25],
            'equipment_groups' => [['pull_up_bar', 'rings']],
            'minimum_nodes' => [
                ['pull_up', 'multiple_pull_ups', 'Minimum pull-up node is not reached yet.'],
                ['bodyline', 'hollow_body_hold', 'Minimum bodyline node is not reached yet.'],
            ],
            'family' => 'front_lever',
            'advanced_anthropometry' => true,
            'high_fatigue' => true,
        ],
        'back_lever' => [
            'domains' => ['horizontal_pull_straight_arm_pull' => 0.45, 'trunk_rigidity' => 0.25, 'tissue_tolerance' => 0.3],
            'equipment_groups' => [['pull_up_bar', 'rings']],
            'mobility' => ['shoulder_extension'],
            'minimum_nodes' => [['back_lever', 'skin_the_cat_prep', 'Minimum back lever node is not reached yet.']],
            'family' => 'back_lever',
            'high_fatigue' => true,
        ],
        'planche' => [
            'domains' => ['vertical_push' => 0.35, 'trunk_rigidity' => 0.25, 'compression' => 0.2, 'tissue_tolerance' => 0.2],
            'mobility' => ['wrist_extension'],
            'minimum_nodes' => [['planche', 'tuck_planche', 'Minimum planche node is not reached yet.']],
            'family' => 'planche',
            'advanced_anthropometry' => true,
            'high_fatigue' => true,
        ],
        'pistol_squat' => [
            'domains' => ['lower_body_strength' => 0.8, 'tissue_tolerance' => 0.2],
            'mobility' => ['ankle_dorsiflexion'],
            'minimum_nodes' => [['pistol_squat', 'assisted_pistol', 'Minimum pistol squat node is not reached yet.']],
            'family' => 'pistol_squat',
        ],
        'nordic_curl' => [
            'domains' => ['lower_body_strength' => 0.65, 'tissue_tolerance' => 0.35],
            'minimum_nodes' => [['lower_body', 'split_squat', 'Minimum lower-body node is not reached yet.']],
            'family' => 'lower_body',
            'high_fatigue' => true,
        ],
        'one_arm_pull_up' => [
            'domains' => ['vertical_pull' => 0.6, 'horizontal_pull_straight_arm_pull' => 0.2, 'tissue_tolerance' => 0.2],
            'equipment_groups' => [['pull_up_bar', 'rings']],
            'minimum_nodes' => [['pull_up', 'three_by_eight_pull_ups', 'Minimum pull-up node is not reached yet.']],
            'family' => 'one_arm_pull_up',
            'advanced_anthropometry' => true,
            'high_fatigue' => true,
        ],
        'human_flag' => [
            'domains' => ['horizontal_pull_straight_arm_pull' => 0.35, 'trunk_rigidity' => 0.3, 'vertical_push' => 0.2, 'tissue_tolerance' => 0.15],
            'equipment_groups' => [['stall_bars', 'pull_up_bar']],
            'minimum_nodes' => [['human_flag', 'tuck_human_flag', 'Minimum human flag node is not reached yet.']],
            'family' => 'human_flag',
            'advanced_anthropometry' => true,
            'high_fatigue' => true,
        ],
    ];

    /**
     * @return array<string, SkillReadiness>
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
     * @param  array<string, BaselineNodePlacement>  $placements
     * @param  array<string, DomainScore>  $domains
     */
    public static function forSkill(string $skill, RoadmapInput $input, array $placements, array $domains): SkillReadiness
    {
        $definition = self::definition($skill);
        $hardBlockers = [];
        $softFactors = [];
        $safetyPenalties = [];
        $missingEvidence = [];
        $blockingGate = false;
        $deferralGate = false;

        self::applyEquipmentGates($definition, $input, $hardBlockers, $softFactors, $blockingGate);
        self::applyMobilityGates($definition, $input, $hardBlockers, $blockingGate);
        self::applyPainGates($definition, $input, $hardBlockers, $safetyPenalties, $blockingGate, $deferralGate);
        self::applyPrerequisiteGates($definition, $placements, $hardBlockers, $softFactors, $deferralGate);

        $domainReadiness = self::domainReadiness($definition['domains'], $domains, $softFactors, $missingEvidence);
        $nodeBonus = self::currentNodeBonus($definition, $placements);
        $trainingModifier = self::trainingAgeModifier($input, $softFactors);
        $bodyModifier = self::bodyContextModifier($skill, $definition, $input, $safetyPenalties);
        $equipmentModifier = ($definition['equipment_groups'] ?? []) === [] || self::equipmentSatisfied($definition['equipment_groups'], $input->equipment) ? 5 : 0;
        $painPenalty = self::painPenalty($input);
        $uncertaintyPenalty = self::uncertaintyPenalty($definition['domains'], $domains);
        $score = (int) round($domainReadiness + $nodeBonus + $trainingModifier + $bodyModifier + $equipmentModifier - $painPenalty - $uncertaintyPenalty);

        if ($blockingGate) {
            $score = 0;
            $safetyPenalties[] = 'Hard safety gates ran before readiness math.';
        } elseif ($deferralGate) {
            $score = min($score, 65);
        }

        $confidence = self::confidence($definition, $domains, $placements, $missingEvidence, $safetyPenalties);
        $status = self::status($score, $blockingGate, $deferralGate);

        return new SkillReadiness(
            skill: $skill,
            label: self::SKILL_LABELS[$skill] ?? $skill,
            status: $status,
            readinessScore: max(0, min(100, $score)),
            confidence: $confidence,
            hardBlockers: self::unique($hardBlockers),
            softFactors: self::unique($softFactors),
            safetyPenalties: self::unique($safetyPenalties),
            missingEvidence: self::unique($missingEvidence),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function definition(string $skill): array
    {
        return self::DEFINITIONS[$skill] ?? [
            'domains' => ['trunk_rigidity' => 0.4, 'tissue_tolerance' => 0.3, 'vertical_push' => 0.3],
            'family' => ProgressionGraphRegistry::targetFamily($skill) ?? 'bodyline',
        ];
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  list<string>  $hardBlockers
     * @param  list<string>  $softFactors
     */
    private static function applyEquipmentGates(array $definition, RoadmapInput $input, array &$hardBlockers, array &$softFactors, bool &$blockingGate): void
    {
        $groups = $definition['equipment_groups'] ?? [];

        if ($groups === []) {
            return;
        }

        if (self::equipmentSatisfied($groups, $input->equipment)) {
            $softFactors[] = 'Required equipment is available.';

            return;
        }

        $hardBlockers[] = 'Required equipment is missing.';
        $blockingGate = true;
    }

    /**
     * @param  list<list<string>>  $groups
     * @param  list<string>  $equipment
     */
    private static function equipmentSatisfied(array $groups, array $equipment): bool
    {
        foreach ($groups as $group) {
            if (array_intersect($group, $equipment) === []) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  list<string>  $hardBlockers
     */
    private static function applyMobilityGates(array $definition, RoadmapInput $input, array &$hardBlockers, bool &$blockingGate): void
    {
        $mobility = is_array($input->goalModules['mobility_checks'] ?? null) ? $input->goalModules['mobility_checks'] : [];

        foreach ($definition['mobility'] ?? [] as $mobilityKey) {
            $status = $mobility[$mobilityKey] ?? null;

            if (! in_array($status, ['blocked', 'painful'], true)) {
                continue;
            }

            $hardBlockers[] = self::mobilityLabel((string) $mobilityKey).' is '.(string) $status.' for this skill.';
            $blockingGate = true;
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  list<string>  $hardBlockers
     * @param  list<string>  $safetyPenalties
     */
    private static function applyPainGates(
        array $definition,
        RoadmapInput $input,
        array &$hardBlockers,
        array &$safetyPenalties,
        bool &$blockingGate,
        bool &$deferralGate,
    ): void {
        $painLevel = self::intOrNull($input->painFlags['level'] ?? null);
        $highFatigue = (bool) ($definition['high_fatigue'] ?? false);
        $lineSkill = (bool) ($definition['line_skill'] ?? false);

        if ($painLevel !== null && $painLevel >= 7) {
            $hardBlockers[] = $highFatigue && ! $lineSkill
                ? 'High pain blocks aggressive loading for this skill.'
                : 'High pain defers progression for this skill.';
            $safetyPenalties[] = 'High pain overrides readiness.';
            $blockingGate = $highFatigue && ! $lineSkill;
            $deferralGate = true;
        } elseif ($painLevel !== null && $painLevel >= 4) {
            $hardBlockers[] = 'Moderate pain prevents progression; use regression or prep work.';
            $safetyPenalties[] = 'Moderate pain prevents progression.';
            $deferralGate = true;
        }

        foreach (self::arrayValue($input->painFlags['regions'] ?? []) as $region => $flag) {
            if (! is_string($region) || ! is_array($flag)) {
                continue;
            }

            $status = $flag['status'] ?? 'none';
            $severity = $flag['severity'] ?? 'none';

            if ($status === 'recurring') {
                $safetyPenalties[] = "Recurring {$region} pain increases uncertainty.";
            }

            if (in_array($status, ['recent', 'acute'], true) || in_array($severity, ['moderate', 'severe'], true)) {
                $hardBlockers[] = "Recent or significant {$region} pain defers this skill.";
                $deferralGate = true;
            }
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, BaselineNodePlacement>  $placements
     * @param  list<string>  $hardBlockers
     * @param  list<string>  $softFactors
     */
    private static function applyPrerequisiteGates(
        array $definition,
        array $placements,
        array &$hardBlockers,
        array &$softFactors,
        bool &$deferralGate,
    ): void {
        foreach ($definition['minimum_nodes'] ?? [] as [$family, $node, $message]) {
            $placement = $placements[$family] ?? null;
            $minimum = ProgressionGraphRegistry::node((string) $family, (string) $node);

            if ($placement === null || $minimum === null || $placement->currentNode->order < $minimum->order) {
                $hardBlockers[] = (string) $message;
                $deferralGate = true;

                continue;
            }

            $softFactors[] = 'Minimum '.self::familyLabel((string) $family).' node reached.';
        }
    }

    /**
     * @param  array<string, float>  $domainWeights
     * @param  array<string, DomainScore>  $domains
     * @param  list<string>  $softFactors
     * @param  list<string>  $missingEvidence
     */
    private static function domainReadiness(array $domainWeights, array $domains, array &$softFactors, array &$missingEvidence): float
    {
        $score = 0.0;
        $weightSum = array_sum($domainWeights);

        foreach ($domainWeights as $domain => $weight) {
            $domainScore = $domains[$domain] ?? null;

            if ($domainScore === null) {
                $missingEvidence[] = "{$domain} domain score.";

                continue;
            }

            $score += $domainScore->score * $weight;
            $missingEvidence = [...$missingEvidence, ...$domainScore->missingInputs];
            $softFactors[] = "{$domainScore->label} score: {$domainScore->score}.";
        }

        return $weightSum > 0.0 ? $score / $weightSum : 0.0;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, BaselineNodePlacement>  $placements
     */
    private static function currentNodeBonus(array $definition, array $placements): int
    {
        $family = $definition['family'] ?? null;

        if (! is_string($family) || ! isset($placements[$family])) {
            return 0;
        }

        return (int) round($placements[$family]->completionPercentage * 0.1);
    }

    /**
     * @param  list<string>  $softFactors
     */
    private static function trainingAgeModifier(RoadmapInput $input, array &$softFactors): int
    {
        $months = self::intOrNull($input->trainingContext['training_age_months'] ?? null);

        if ($months === null) {
            return -4;
        }

        $softFactors[] = "Training age: {$months} months.";

        if ($months >= 18) {
            return 5;
        }

        if ($months >= 6) {
            return 2;
        }

        return -5;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  list<string>  $safetyPenalties
     */
    private static function bodyContextModifier(string $skill, array $definition, RoadmapInput $input, array &$safetyPenalties): int
    {
        if (! (bool) ($definition['advanced_anthropometry'] ?? false)) {
            return 0;
        }

        $bodyweight = self::numberOrNull($input->profileContext['current_bodyweight_value'] ?? null);
        $height = self::numberOrNull($input->profileContext['height_value'] ?? null);

        if ($bodyweight === null || $height === null) {
            $safetyPenalties[] = (self::SKILL_LABELS[$skill] ?? $skill).' is anthropometry-sensitive; missing body context increases uncertainty.';

            return -8;
        }

        return 0;
    }

    private static function painPenalty(RoadmapInput $input): int
    {
        $painLevel = self::intOrNull($input->painFlags['level'] ?? null);

        if ($painLevel === null) {
            return 0;
        }

        if ($painLevel >= 7) {
            return 60;
        }

        if ($painLevel >= 4) {
            return 25;
        }

        return $painLevel >= 1 ? 5 : 0;
    }

    /**
     * @param  array<string, float>  $domainWeights
     * @param  array<string, DomainScore>  $domains
     */
    private static function uncertaintyPenalty(array $domainWeights, array $domains): int
    {
        $penalty = 0.0;
        $weightSum = array_sum($domainWeights);

        foreach ($domainWeights as $domain => $weight) {
            $penalty += ($domains[$domain]->uncertainty ?? 1.0) * $weight * 20;
        }

        return $weightSum > 0.0 ? (int) round($penalty / $weightSum) : 20;
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, DomainScore>  $domains
     * @param  array<string, BaselineNodePlacement>  $placements
     * @param  list<string>  $missingEvidence
     * @param  list<string>  $safetyPenalties
     */
    private static function confidence(
        array $definition,
        array $domains,
        array $placements,
        array $missingEvidence,
        array &$safetyPenalties,
    ): float {
        $domainWeights = $definition['domains'];
        $weightSum = array_sum($domainWeights);
        $domainConfidence = 0.0;

        foreach ($domainWeights as $domain => $weight) {
            $domainConfidence += ($domains[$domain]->confidence ?? 0.0) * $weight;
        }

        $domainConfidence = $weightSum > 0.0 ? $domainConfidence / $weightSum : 0.0;
        $family = is_string($definition['family'] ?? null) ? $definition['family'] : null;
        $placement = $family === null ? null : ($placements[$family] ?? null);
        $objectiveInput = $placement !== null && $placement->observedEvidence !== [] ? 1.0 : 0.0;
        $recencyPlaceholder = 0.5;
        $consistency = $missingEvidence === [] ? 1.0 : 0.55;
        $confidence = ($domainConfidence * 0.55) + ($objectiveInput * 0.2) + ($recencyPlaceholder * 0.1) + ($consistency * 0.15);

        if ($missingEvidence !== []) {
            $safetyPenalties[] = 'Missing objective inputs reduce confidence.';
        }

        if ((bool) ($definition['advanced_anthropometry'] ?? false) && self::containsAnthropometryPenalty($safetyPenalties)) {
            $confidence -= 0.12;
        }

        return round(max(0.0, min(1.0, $confidence)), 2);
    }

    /**
     * @param  list<string>  $safetyPenalties
     */
    private static function containsAnthropometryPenalty(array $safetyPenalties): bool
    {
        foreach ($safetyPenalties as $penalty) {
            if (str_contains($penalty, 'anthropometry-sensitive')) {
                return true;
            }
        }

        return false;
    }

    private static function status(int $score, bool $blockingGate, bool $deferralGate): string
    {
        if ($blockingGate) {
            return 'blocked';
        }

        if ($deferralGate) {
            return 'deferred';
        }

        return $score >= 70 ? 'ready' : 'deferred';
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

    private static function familyLabel(string $family): string
    {
        return match ($family) {
            'pull_up' => 'pull-up',
            'dip' => 'dip',
            'planche' => 'planche',
            'bodyline' => 'bodyline',
            'compression' => 'compression',
            'hspu' => 'handstand push-up',
            'muscle_up' => 'muscle-up',
            'back_lever' => 'back lever',
            'pistol_squat' => 'pistol squat',
            'human_flag' => 'human flag',
            default => str_replace('_', ' ', $family),
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

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private static function unique(array $values): array
    {
        return array_values(array_unique(array_filter($values, static fn (string $value): bool => $value !== '')));
    }
}
