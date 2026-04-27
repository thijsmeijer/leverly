<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final class DomainScoreCalculator
{
    private const array DOMAINS = [
        'vertical_push' => [
            'label' => 'Vertical push',
            'inputs' => ['push_up' => 0.45, 'dip' => 0.55],
        ],
        'vertical_pull' => [
            'label' => 'Vertical pull',
            'inputs' => ['pull_up' => 0.55, 'one_arm_pull_up' => 0.25, 'muscle_up' => 0.2],
        ],
        'horizontal_pull_straight_arm_pull' => [
            'label' => 'Horizontal pull and straight-arm pull',
            'inputs' => ['row' => 0.35, 'front_lever' => 0.35, 'back_lever' => 0.3],
        ],
        'trunk_rigidity' => [
            'label' => 'Trunk rigidity',
            'inputs' => ['bodyline' => 0.75, 'support' => 0.25],
        ],
        'compression' => [
            'label' => 'Compression',
            'inputs' => ['compression' => 0.7, 'bodyline' => 0.15, 'support' => 0.15],
        ],
        'inversion_balance' => [
            'label' => 'Inversion and balance',
            'inputs' => ['handstand' => 0.7, 'hspu' => 0.3],
        ],
        'lower_body_strength' => [
            'label' => 'Lower-body strength',
            'inputs' => ['lower_body' => 0.75, 'pistol_squat' => 0.25],
        ],
    ];

    /**
     * @return array<string, DomainScore>
     */
    public static function fromInput(RoadmapInput $input): array
    {
        return self::fromPlacements(BaselineNodeMapper::fromInput($input), $input);
    }

    /**
     * @param  array<string, BaselineNodePlacement>  $placements
     * @return array<string, DomainScore>
     */
    public static function fromPlacements(array $placements, RoadmapInput $input): array
    {
        $scores = [];
        $modifiers = self::modifiers($input);

        foreach (self::DOMAINS as $domain => $definition) {
            $scores[$domain] = self::scoreDomain(
                domain: $domain,
                label: $definition['label'],
                inputs: $definition['inputs'],
                placements: $placements,
                modifiers: $modifiers,
            );
        }

        $scores['tissue_tolerance'] = self::scoreTissueTolerance($placements, $input, $modifiers);

        return $scores;
    }

    /**
     * @param  array<string, float>  $inputs
     * @param  array<string, BaselineNodePlacement>  $placements
     * @param  list<string>  $modifiers
     */
    private static function scoreDomain(
        string $domain,
        string $label,
        array $inputs,
        array $placements,
        array $modifiers,
    ): DomainScore {
        $totalWeight = array_sum($inputs);
        $observedWeight = 0.0;
        $weightedScore = 0.0;
        $weightedConfidence = 0.0;
        $contributingInputs = [];
        $missingInputs = [];

        foreach ($inputs as $family => $weight) {
            $placement = $placements[$family] ?? null;

            if ($placement === null) {
                $missingInputs[] = "{$family} placement.";

                continue;
            }

            if ($placement->observedEvidence === []) {
                $missingInputs = [...$missingInputs, ...$placement->missingEvidence];

                continue;
            }

            $observedWeight += $weight;
            $weightedScore += $placement->completionPercentage * $weight;
            $weightedConfidence += $placement->confidenceContribution * $weight;
            $contributingInputs = [...$contributingInputs, ...$placement->observedEvidence];
            $missingInputs = [...$missingInputs, ...$placement->missingEvidence];
        }

        $coverage = $totalWeight > 0.0 ? $observedWeight / $totalWeight : 0.0;
        $score = $observedWeight > 0.0 ? (int) round($weightedScore / $observedWeight) : 0;
        $averageConfidence = $observedWeight > 0.0 ? $weightedConfidence / $observedWeight : 0.0;
        $confidence = self::clamp01(($coverage * 0.65) + ($averageConfidence * 0.35));
        $uncertainty = round(1.0 - $confidence, 2);
        $missingInputs = self::unique($missingInputs);

        return new DomainScore(
            domain: $domain,
            label: $label,
            score: $score,
            confidence: $confidence,
            uncertainty: $uncertainty,
            contributingInputs: self::unique($contributingInputs),
            missingInputs: $missingInputs,
            bottleneck: self::bottleneck($score, $confidence, $uncertainty, $missingInputs),
            modifiers: $modifiers,
        );
    }

    /**
     * @param  array<string, BaselineNodePlacement>  $placements
     * @param  list<string>  $modifiers
     */
    private static function scoreTissueTolerance(array $placements, RoadmapInput $input, array $modifiers): DomainScore
    {
        $base = self::scoreDomain(
            domain: 'tissue_tolerance',
            label: 'Tissue tolerance',
            inputs: ['support' => 0.35, 'pull_up' => 0.25, 'dip' => 0.25, 'bodyline' => 0.15],
            placements: $placements,
            modifiers: $modifiers,
        );
        $painLevel = self::intOrNull($input->painFlags['level'] ?? null);
        $score = $base->score;
        $confidence = $base->confidence;
        $contributingInputs = $base->contributingInputs;
        $missingInputs = $base->missingInputs;
        $bottleneck = $base->bottleneck;

        if ($painLevel === null) {
            $missingInputs[] = 'Pain level.';
        } else {
            $contributingInputs[] = "Pain level: {$painLevel}/10.";
            $confidence = max($confidence, 0.68);

            if ($painLevel >= 7) {
                $score = min($score, 20);
                $bottleneck = 'High pain is the limiting tissue-tolerance signal; stop progression and keep the plan conservative.';
            } elseif ($painLevel >= 4) {
                $score = min($score, 45);
                $bottleneck = 'Pain is the limiting tissue-tolerance signal; hold progression until it settles.';
            } elseif ($painLevel > 0) {
                $score = min($score, 75);
                $bottleneck = 'Mild pain is present; keep progression conservative until it stays quiet.';
            }
        }

        $confidence = self::clamp01($confidence);
        $uncertainty = round(1.0 - $confidence, 2);
        $missingInputs = self::unique($missingInputs);

        if ($contributingInputs === [] && $painLevel === null) {
            $bottleneck = 'Missing inputs make this domain uncertain.';
        }

        return new DomainScore(
            domain: 'tissue_tolerance',
            label: 'Tissue tolerance',
            score: $score,
            confidence: $confidence,
            uncertainty: $uncertainty,
            contributingInputs: self::unique($contributingInputs),
            missingInputs: $missingInputs,
            bottleneck: $bottleneck,
            modifiers: $modifiers,
        );
    }

    /**
     * @param  list<string>  $missingInputs
     */
    private static function bottleneck(int $score, float $confidence, float $uncertainty, array $missingInputs): string
    {
        if ($missingInputs !== [] && $confidence <= 0.35) {
            return 'Missing inputs make this domain uncertain.';
        }

        if ($score >= 70 && $uncertainty >= 0.35) {
            return 'Strong signal, but missing inputs keep this domain uncertain.';
        }

        if ($score < 40 && $confidence >= 0.65) {
            return 'This is a clear bottleneck from observed tests.';
        }

        if ($uncertainty >= 0.5) {
            return 'More evidence is needed before this domain should drive the roadmap.';
        }

        if ($score >= 70) {
            return 'This domain is currently a strength.';
        }

        if ($score < 40) {
            return 'This domain needs foundation work.';
        }

        return 'This domain has workable but incomplete evidence.';
    }

    /**
     * @return list<string>
     */
    private static function modifiers(RoadmapInput $input): array
    {
        $modifiers = [];
        $bodyweight = self::numberOrNull($input->profileContext['current_bodyweight_value'] ?? null);
        $bodyweightUnit = self::stringValue($input->profileContext['bodyweight_unit'] ?? null, 'kg');
        $height = self::numberOrNull($input->profileContext['height_value'] ?? null);
        $heightUnit = self::stringValue($input->profileContext['height_unit'] ?? null, 'cm');
        $weightTrend = self::stringValue($input->profileContext['weight_trend'] ?? null, 'unknown');
        $trainingAge = self::intOrNull($input->trainingContext['training_age_months'] ?? null);

        if ($bodyweight !== null && $bodyweight > 0) {
            $modifiers[] = 'Body mass: '.self::formatNumber($bodyweight).$bodyweightUnit.'.';
        }

        if ($height !== null && $height > 0) {
            $modifiers[] = 'Height: '.self::formatNumber($height).$heightUnit.'.';
        }

        if ($weightTrend !== 'unknown') {
            $modifiers[] = "Weight trend: {$weightTrend}.";
        }

        if ($trainingAge !== null) {
            $modifiers[] = "Training age: {$trainingAge} months.";
        }

        return $modifiers;
    }

    private static function clamp01(float $value): float
    {
        return round(max(0.0, min(1.0, $value)), 2);
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

    private static function formatNumber(float $value): string
    {
        if (floor($value) === $value) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
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
