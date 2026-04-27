<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

use DateTimeImmutable;

final class RoadmapEvidenceNormalizer
{
    private const int DEFAULT_SESSION_MINUTES = 45;

    public static function fromInput(RoadmapInput $input, ?DateTimeImmutable $today = null): RoadmapEvidenceProfile
    {
        $today ??= new DateTimeImmutable('today');
        $bodyContext = self::bodyContext($input);
        $samples = [];
        $pendingTests = [];

        self::addBaselineSamples($samples, $pendingTests, $input, $bodyContext, $today);
        self::addWeightedSamples($samples, $input, $bodyContext, $today);

        $uncertaintyFlags = self::uncertaintyFlags($samples, $pendingTests);

        return new RoadmapEvidenceProfile(
            bodyContext: $bodyContext,
            trainingContext: self::trainingContext($input),
            samples: $samples,
            pendingTests: $pendingTests,
            uncertaintyFlags: $uncertaintyFlags,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function bodyContext(RoadmapInput $input): array
    {
        $heightValue = self::numberOrNull($input->profileContext['height_value'] ?? null);
        $heightUnit = self::stringValue($input->profileContext['height_unit'] ?? null, 'cm');
        $bodyweightValue = self::numberOrNull($input->profileContext['current_bodyweight_value'] ?? null);
        $bodyweightUnit = self::stringValue($input->profileContext['bodyweight_unit'] ?? null, 'kg');

        return [
            'age_years' => self::intOrNull($input->profileContext['age_years'] ?? null),
            'height_cm' => $heightValue === null ? null : self::round($heightUnit === 'in' ? $heightValue * 2.54 : $heightValue),
            'height_unit' => $heightUnit,
            'bodyweight_kg' => $bodyweightValue === null ? null : self::loadToKg($bodyweightValue, $bodyweightUnit),
            'bodyweight_unit' => $bodyweightUnit,
            'training_age_months' => self::intOrNull($input->trainingContext['training_age_months'] ?? null),
            'resistance_training_age_months' => self::intOrNull($input->profileContext['resistance_training_age_months'] ?? null),
            'weight_trend' => self::stringValue($input->profileContext['weight_trend'] ?? null, 'unknown'),
            'body_lever_context' => self::arrayValue($input->profileContext['body_lever_context'] ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function trainingContext(RoadmapInput $input): array
    {
        $preferredSessionMinutes = self::intOrNull($input->trainingContext['preferred_session_minutes'] ?? null);

        return [
            'max_sessions_per_week' => max(1, min(14, self::intOrNull($input->trainingContext['weekly_session_goal'] ?? null) ?? 3)),
            'estimated_session_minutes' => $preferredSessionMinutes ?? self::DEFAULT_SESSION_MINUTES,
            'uses_default_session_minutes' => $preferredSessionMinutes === null,
            'preferred_training_days' => self::stringList($input->trainingContext['preferred_training_days'] ?? []),
            'training_locations' => self::stringList($input->trainingContext['training_locations'] ?? []),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $samples
     * @param  list<array<string, mixed>>  $pendingTests
     * @param  array<string, mixed>  $bodyContext
     */
    private static function addBaselineSamples(array &$samples, array &$pendingTests, RoadmapInput $input, array $bodyContext, DateTimeImmutable $today): void
    {
        $tests = $input->baselineTests;

        self::addRepSample(
            $samples,
            $pendingTests,
            'baseline.push_ups.max_reps',
            'push_up',
            'Push-up max reps',
            self::arrayValue($tests['push_ups'] ?? []),
            'max_strict_reps',
            $today,
            flags: ['fuzzy_load_estimate'],
            estimates: self::pushUpEstimates($bodyContext),
            decisiveness: 'supporting',
            highRepBiasAt: 30,
        );

        self::addRepSample($samples, $pendingTests, 'baseline.pull_ups.max_reps', 'pull_up', 'Pull-up max reps', self::arrayValue($tests['pull_ups'] ?? []), 'max_strict_reps', $today);
        self::addRepSample($samples, $pendingTests, 'baseline.dips.max_reps', 'dip', 'Dip max reps', self::arrayValue($tests['dips'] ?? []), 'max_strict_reps', $today);
        self::addRepSample($samples, $pendingTests, 'baseline.rows.max_reps', 'horizontal_row', 'Horizontal row max reps', self::arrayValue($tests['rows'] ?? []), 'max_reps', $today, blocking: false);
        self::addHoldSample($samples, $pendingTests, 'baseline.hollow_body_hold.seconds', 'hollow_body_hold', 'Hollow body hold', $tests['hollow_hold_seconds'] ?? null, $tests, $today);
        self::addHoldSample($samples, $pendingTests, 'baseline.passive_hang.seconds', 'passive_hang', 'Passive hang', $tests['passive_hang_seconds'] ?? null, $tests, $today, blocking: false);
        self::addHoldSample($samples, $pendingTests, 'baseline.dip_support_hold.seconds', 'dip_support_hold', 'Dip support hold', $tests['top_support_hold_seconds'] ?? null, $tests, $today, blocking: false);

        $squat = self::arrayValue($tests['squat'] ?? []);
        $squatLoad = self::numberOrNull($squat['barbell_load_value'] ?? null);
        $squatReps = self::intOrNull($squat['barbell_reps'] ?? null);

        if ($squatLoad !== null && $squatReps !== null) {
            $externalLoadKg = self::loadToKg($squatLoad, self::stringValue($squat['load_unit'] ?? null, 'kg'));
            $totalLoadKg = self::round($externalLoadKg + (self::numberOrNull($bodyContext['bodyweight_kg'] ?? null) ?? 0.0));

            $samples[] = self::sample(
                key: 'baseline.squat.barbell',
                movement: 'barbell_squat',
                label: 'Barbell squat',
                metric: 'external_load',
                value: $squatLoad,
                unit: self::stringValue($squat['load_unit'] ?? null, 'kg'),
                normalizedValue: $externalLoadKg,
                normalizedUnit: 'kg',
                source: 'baseline_tests',
                sourceData: $squat,
                today: $today,
                estimates: [
                    'external_load_kg' => $externalLoadKg,
                    'bodyweight_kg' => self::numberOrNull($bodyContext['bodyweight_kg'] ?? null),
                    'total_system_load_kg' => $totalLoadKg,
                    'estimated_1rm_kg' => self::e1rm($totalLoadKg, $squatReps, self::intOrNull($squat['rir'] ?? null)),
                ],
            );
        } else {
            $pendingTests[] = self::pendingTest('baseline.squat.barbell', 'Barbell squat test', 'Barbell squat data is missing; lower-body placement falls back to bodyweight evidence.', false);
        }

        $lowerBody = self::arrayValue($tests['lower_body'] ?? []);
        self::addRepSample($samples, $pendingTests, 'baseline.lower_body.reps', self::stringValue($lowerBody['variant'] ?? null, 'bodyweight_squat'), 'Lower-body fallback reps', $lowerBody, 'reps', $today, blocking: false);
    }

    /**
     * @param  list<array<string, mixed>>  $samples
     * @param  array<string, mixed>  $bodyContext
     */
    private static function addWeightedSamples(array &$samples, RoadmapInput $input, array $bodyContext, DateTimeImmutable $today): void
    {
        $weighted = self::arrayValue($input->goalModules['weighted_baselines'] ?? []);
        $unit = self::stringValue($weighted['unit'] ?? null, 'kg');
        $bodyweightKg = self::numberOrNull($bodyContext['bodyweight_kg'] ?? null) ?? 0.0;

        foreach (self::arrayList($weighted['movements'] ?? []) as $movement) {
            $movementKey = self::stringValue($movement['movement'] ?? null, '');
            $externalLoad = self::numberOrNull($movement['external_load_value'] ?? null);
            $reps = self::intOrNull($movement['reps'] ?? null);

            if ($movementKey === '' || $externalLoad === null || $reps === null) {
                continue;
            }

            $externalLoadKg = self::loadToKg($externalLoad, $unit);
            $fuzzy = $movementKey === 'loaded_push_up';
            $bodyweightContribution = $fuzzy ? self::round($bodyweightKg * 0.64) : $bodyweightKg;
            $totalLoadKg = self::round($bodyweightContribution + $externalLoadKg);

            $samples[] = self::sample(
                key: "weighted.{$movementKey}",
                movement: $movementKey,
                label: self::labelFromKey($movementKey),
                metric: 'external_load',
                value: $externalLoad,
                unit: $unit,
                normalizedValue: $externalLoadKg,
                normalizedUnit: 'kg',
                source: 'weighted_baselines',
                sourceData: $movement,
                today: $today,
                flags: $fuzzy ? ['fuzzy_load_estimate'] : [],
                estimates: [
                    'external_load_kg' => $externalLoadKg,
                    'bodyweight_kg' => $bodyweightKg === 0.0 ? null : $bodyweightKg,
                    'total_system_load_kg' => $totalLoadKg,
                    'estimated_1rm_kg' => self::e1rm($totalLoadKg, $reps, self::intOrNull($movement['rir'] ?? null)),
                    'bodyweight_contribution_kg' => $fuzzy ? $bodyweightContribution : null,
                ],
                decisiveness: $fuzzy ? 'supporting' : 'primary',
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $samples
     * @param  list<array<string, mixed>>  $pendingTests
     * @param  array<string, mixed>  $sourceData
     * @param  list<string>  $flags
     * @param  array<string, mixed>  $estimates
     */
    private static function addRepSample(
        array &$samples,
        array &$pendingTests,
        string $key,
        string $movement,
        string $label,
        array $sourceData,
        string $field,
        DateTimeImmutable $today,
        bool $blocking = true,
        array $flags = [],
        array $estimates = [],
        string $decisiveness = 'primary',
        ?int $highRepBiasAt = null,
    ): void {
        $value = self::intOrNull($sourceData[$field] ?? null);

        if ($value === null) {
            $pendingTests[] = self::pendingTest($key, $label, "{$label} is missing.", $blocking);

            return;
        }

        if ($highRepBiasAt !== null && $value >= $highRepBiasAt) {
            $flags[] = 'high_rep_endurance_bias';
        }

        $sample = self::sample(
            key: $key,
            movement: $movement,
            label: $label,
            metric: 'reps',
            value: $value,
            unit: 'reps',
            normalizedValue: $value,
            normalizedUnit: 'reps',
            source: 'baseline_tests',
            sourceData: $sourceData,
            today: $today,
            flags: $flags,
            estimates: $estimates,
            decisiveness: $decisiveness,
        );

        $samples[] = $sample;
        self::requestRetestWhenUncertain($pendingTests, $sample, $label, $blocking);
    }

    /**
     * @param  list<array<string, mixed>>  $samples
     * @param  list<array<string, mixed>>  $pendingTests
     * @param  array<string, mixed>  $sourceData
     */
    private static function addHoldSample(
        array &$samples,
        array &$pendingTests,
        string $key,
        string $movement,
        string $label,
        mixed $value,
        array $sourceData,
        DateTimeImmutable $today,
        bool $blocking = true,
    ): void {
        $seconds = self::intOrNull($value);

        if ($seconds === null) {
            $pendingTests[] = self::pendingTest($key, $label, "{$label} is missing.", $blocking);

            return;
        }

        $sample = self::sample(
            key: $key,
            movement: $movement,
            label: $label,
            metric: 'hold_seconds',
            value: $seconds,
            unit: 'seconds',
            normalizedValue: $seconds,
            normalizedUnit: 'seconds',
            source: 'baseline_tests',
            sourceData: $sourceData,
            today: $today,
        );

        $samples[] = $sample;
        self::requestRetestWhenUncertain($pendingTests, $sample, $label, $blocking);
    }

    /**
     * @param  array<string, mixed>  $sourceData
     * @param  list<string>  $flags
     * @param  array<string, mixed>  $estimates
     * @return array<string, mixed>
     */
    private static function sample(
        string $key,
        string $movement,
        string $label,
        string $metric,
        int|float $value,
        string $unit,
        int|float $normalizedValue,
        string $normalizedUnit,
        string $source,
        array $sourceData,
        DateTimeImmutable $today,
        array $flags = [],
        array $estimates = [],
        string $decisiveness = 'primary',
    ): array {
        $testedAt = self::dateValue($sourceData['tested_at'] ?? $sourceData['test_date'] ?? null);
        $ageDays = $testedAt === null ? null : (int) $today->diff($testedAt)->format('%a');
        $flags = self::evidenceFlags($flags, $sourceData, $ageDays);
        $reasonParts = [];

        return [
            'key' => $key,
            'movement' => $movement,
            'label' => $label,
            'metric' => $metric,
            'value' => $value,
            'unit' => $unit,
            'normalized_value' => self::round($normalizedValue),
            'normalized_unit' => $normalizedUnit,
            'source' => $source,
            'form_quality' => self::stringValue($sourceData['form_quality'] ?? $sourceData['quality'] ?? null, 'unknown'),
            'effort' => [
                'rir' => self::intOrNull($sourceData['rir'] ?? null),
                'rpe' => self::numberOrNull($sourceData['rpe'] ?? null),
            ],
            'tested_at' => $testedAt?->format('Y-m-d'),
            'age_days' => $ageDays,
            'confidence' => self::confidence($sourceData, $flags, $reasonParts),
            'confidence_reasons' => $reasonParts,
            'decisiveness' => $decisiveness,
            'flags' => $flags,
            'estimates' => $estimates,
        ];
    }

    /**
     * @param  list<string>  $flags
     * @param  array<string, mixed>  $sourceData
     * @return list<string>
     */
    private static function evidenceFlags(array $flags, array $sourceData, ?int $ageDays): array
    {
        $confidence = self::numberOrNull($sourceData['confidence'] ?? null);

        if ($ageDays !== null && $ageDays > 180) {
            $flags[] = 'stale_evidence';
        }

        if ($confidence !== null && $confidence < 0.35) {
            $flags[] = 'low_confidence_self_report';
        }

        return self::uniqueStrings($flags);
    }

    /**
     * @param  list<string>  $flags
     * @param  list<string>  $reasons
     */
    private static function confidence(array $sourceData, array $flags, array &$reasons): float
    {
        $confidence = self::numberOrNull($sourceData['confidence'] ?? null) ?? 0.62;
        $quality = self::stringValue($sourceData['form_quality'] ?? $sourceData['quality'] ?? null, 'unknown');

        $confidence += match ($quality) {
            'clean' => 0.04,
            'solid' => 0.02,
            'questionable', 'shaky' => -0.1,
            'poor' => -0.2,
            default => -0.05,
        };

        if (in_array('stale_evidence', $flags, true)) {
            $confidence -= 0.12;
            $reasons[] = 'Evidence is stale and should be retested.';
        }

        if (in_array('low_confidence_self_report', $flags, true)) {
            $confidence -= 0.05;
            $reasons[] = 'Self-reported confidence is low.';
        }

        if (in_array('high_rep_endurance_bias', $flags, true)) {
            $confidence -= 0.07;
            $reasons[] = 'High-rep results are biased toward endurance.';
        }

        if (in_array('fuzzy_load_estimate', $flags, true)) {
            $confidence -= 0.06;
            $reasons[] = 'Load estimate is fuzzy support evidence.';
        }

        if ($reasons === []) {
            $reasons[] = 'Recent enough self-report evidence.';
        }

        return self::round(max(0.0, min(1.0, $confidence)));
    }

    /**
     * @param  list<array<string, mixed>>  $pendingTests
     * @param  array<string, mixed>  $sample
     */
    private static function requestRetestWhenUncertain(array &$pendingTests, array $sample, string $label, bool $blocking): void
    {
        $flags = self::stringList($sample['flags'] ?? []);

        if (! in_array('stale_evidence', $flags, true) && ! in_array('low_confidence_self_report', $flags, true)) {
            return;
        }

        $pendingTests[] = self::pendingTest((string) $sample['key'], $label, "{$label} should be retested to improve roadmap confidence.", $blocking);
    }

    /**
     * @return array<string, mixed>
     */
    private static function pendingTest(string $key, string $label, string $reason, bool $blocking): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'reason' => $reason,
            'blocking' => $blocking,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $samples
     * @param  list<array<string, mixed>>  $pendingTests
     * @return list<string>
     */
    private static function uncertaintyFlags(array $samples, array $pendingTests): array
    {
        $flags = $pendingTests === [] ? [] : ['missing_evidence'];

        foreach ($samples as $sample) {
            $flags = [...$flags, ...self::stringList($sample['flags'] ?? [])];
        }

        return self::uniqueStrings($flags);
    }

    /**
     * @return array<string, mixed>
     */
    private static function pushUpEstimates(array $bodyContext): array
    {
        $bodyweightKg = self::numberOrNull($bodyContext['bodyweight_kg'] ?? null);

        return [
            'approx_bodyweight_load_kg' => $bodyweightKg === null ? null : self::round($bodyweightKg * 0.64),
        ];
    }

    private static function e1rm(float $totalLoadKg, int $reps, ?int $rir): float
    {
        $failureReps = $reps + min(max($rir ?? 0, 0), 5);

        return self::round($totalLoadKg * (1 + ($failureReps / 30)));
    }

    private static function loadToKg(float $value, string $unit): float
    {
        return self::round($unit === 'lb' ? $value * 0.45359237 : $value);
    }

    private static function dateValue(mixed $value): ?DateTimeImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date instanceof DateTimeImmutable ? $date : null;
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
     * @return array<string, mixed>
     */
    private static function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * @return list<string>
     */
    private static function stringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, is_string(...)));
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private static function uniqueStrings(array $values): array
    {
        return array_values(array_unique(array_filter($values, static fn (mixed $value): bool => is_string($value) && $value !== '')));
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

    private static function labelFromKey(string $key): string
    {
        return ucwords(str_replace('_', ' ', $key));
    }

    private static function round(int|float $value): float
    {
        return round((float) $value, 2);
    }
}
