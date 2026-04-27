<?php

declare(strict_types=1);

namespace App\Domain\Training\Roadmap;

final readonly class RoadmapAdaptationModel
{
    /**
     * @param  array<string, mixed>  $activePortfolio
     * @return array<string, mixed>
     */
    public static function apply(array $activePortfolio, RoadmapAdaptationInput $input): array
    {
        $tracks = self::trackBuckets($activePortfolio);
        $evidenceWeeks = $input->evidenceWeeks();
        $blendWeight = self::blendWeight($evidenceWeeks);
        $warnings = [];
        $statuses = [];

        foreach ($tracks as $bucket => $bucketTracks) {
            $activePortfolio[$bucket] = array_values(array_map(
                static function (array $track) use ($input, $evidenceWeeks, $blendWeight, &$warnings, &$statuses): array {
                    $adapted = self::adaptTrack($track, $input, $evidenceWeeks, $blendWeight);
                    $warnings = self::uniqueStrings([...$warnings, ...self::stringList($adapted['adaptation']['warnings'] ?? [])]);
                    $statuses[] = self::stringValue($adapted['adaptation']['status'] ?? null, 'prior_based');

                    return $adapted;
                },
                $bucketTracks,
            ));
        }

        $activePortfolio['adaptation'] = [
            'status' => self::globalStatus($statuses),
            'eta_basis' => $input->hasEvidence() ? 'blended' : 'prior',
            'evidence_weeks' => $evidenceWeeks,
            'session_logs' => count($input->sessions),
            'completed_module_evidence' => $input->moduleEvidenceCount(),
            'blend_weight' => $blendWeight,
            'blend_schedule' => [
                'two_weeks' => 0.25,
                'four_weeks' => 0.4,
                'eight_weeks' => 0.65,
                'twelve_plus_weeks' => 0.8,
            ],
            'warnings' => $warnings,
            'inputs' => [
                'supports_session_logs' => true,
                'supports_completed_module_evidence' => true,
                'supports_pain' => true,
                'supports_rir_rpe' => true,
                'supports_form_score' => true,
            ],
        ];

        return $activePortfolio;
    }

    /**
     * @param  array<string, mixed>  $track
     * @return array<string, mixed>
     */
    private static function adaptTrack(array $track, RoadmapAdaptationInput $input, int $evidenceWeeks, float $blendWeight): array
    {
        $skillTrackId = self::stringValue($track['skill_track_id'] ?? null, '');
        $evidence = $input->moduleEvidenceForSkill($skillTrackId);

        if ($evidence === [] || $blendWeight <= 0.0) {
            $track['adaptation'] = [
                'status' => 'prior_based',
                'eta_basis' => 'prior',
                'evidence_weeks' => 0,
                'blend_weight' => 0.0,
                'completion_ratio' => null,
                'observed_progress_delta' => null,
                'next_action' => 'collect_training_evidence',
                'warnings' => ['No logged training evidence yet; ETA uses baseline and graph priors.'],
            ];

            return $track;
        }

        $summary = self::summarizeEvidence($evidence);
        $priorEta = self::etaValue($track['eta_to_next_node'] ?? []);
        $priorMidpoint = self::etaMidpoint($priorEta);
        $status = self::trackStatus($summary);
        $nextAction = self::nextAction($status);
        $eta = self::adaptEta($priorEta, $priorMidpoint, $summary, $blendWeight, $status);
        $warnings = self::warningsForStatus($status);

        $track['eta_to_next_node'] = $eta;
        $track['adaptation'] = [
            'status' => $status,
            'eta_basis' => 'blended',
            'evidence_weeks' => $evidenceWeeks,
            'blend_weight' => $blendWeight,
            'completion_ratio' => $summary['completion_ratio'],
            'observed_progress_delta' => $summary['progress_delta'],
            'max_pain_level' => $summary['max_pain'],
            'average_form_score' => $summary['average_form'],
            'average_rir' => $summary['average_rir'],
            'average_rpe' => $summary['average_rpe'],
            'next_action' => $nextAction,
            'warnings' => $warnings,
        ];

        return $track;
    }

    /**
     * @param  list<RoadmapAdaptationModuleEvidence>  $evidence
     * @return array{
     *     completion_ratio: float,
     *     progress_delta: float,
     *     max_pain: int,
     *     average_form: float|null,
     *     average_rir: float|null,
     *     average_rpe: float|null
     * }
     */
    private static function summarizeEvidence(array $evidence): array
    {
        $planned = array_sum(array_map(
            static fn (RoadmapAdaptationModuleEvidence $item): int => $item->plannedExposures,
            $evidence,
        ));
        $completed = array_sum(array_map(
            static fn (RoadmapAdaptationModuleEvidence $item): int => $item->completedExposures,
            $evidence,
        ));

        return [
            'completion_ratio' => $planned > 0 ? round(min(1.0, $completed / $planned), 3) : 1.0,
            'progress_delta' => self::average(array_map(
                static fn (RoadmapAdaptationModuleEvidence $item): float => $item->progressDelta,
                $evidence,
            )) ?? 0.0,
            'max_pain' => max(array_map(
                static fn (RoadmapAdaptationModuleEvidence $item): int => $item->painLevel ?? 0,
                $evidence,
            )),
            'average_form' => self::average(array_values(array_filter(
                array_map(static fn (RoadmapAdaptationModuleEvidence $item): ?float => $item->formScore, $evidence),
                is_numeric(...),
            ))),
            'average_rir' => self::average(array_values(array_filter(
                array_map(static fn (RoadmapAdaptationModuleEvidence $item): ?int => $item->rir, $evidence),
                is_numeric(...),
            ))),
            'average_rpe' => self::average(array_values(array_filter(
                array_map(static fn (RoadmapAdaptationModuleEvidence $item): ?int => $item->rpe, $evidence),
                is_numeric(...),
            ))),
        ];
    }

    /**
     * @param  array<string, mixed>  $summary
     */
    private static function trackStatus(array $summary): string
    {
        if (($summary['max_pain'] ?? 0) >= 4) {
            return 'downgrade';
        }

        if (($summary['average_form'] ?? 5.0) <= 2.0) {
            return 'retest_requested';
        }

        if (($summary['completion_ratio'] ?? 1.0) < 0.7 || ($summary['progress_delta'] ?? 0.0) < -0.02) {
            return 'widened';
        }

        if (($summary['progress_delta'] ?? 0.0) >= 0.15) {
            return 'tightened';
        }

        return 'maintained';
    }

    /**
     * @param  array<string, mixed>  $eta
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    private static function adaptEta(array $eta, int $priorMidpoint, array $summary, float $blendWeight, string $status): array
    {
        $observedMultiplier = match ($status) {
            'tightened' => max(0.55, 1.0 - min(0.45, (float) $summary['progress_delta'])),
            'widened' => 1.35,
            'downgrade', 'retest_requested' => 1.5,
            default => 1.0,
        };
        $observedMidpoint = max(1, (int) round($priorMidpoint * $observedMultiplier));
        $adjustedMidpoint = max(1, (int) round(($priorMidpoint * (1.0 - $blendWeight)) + ($observedMidpoint * $blendWeight)));
        $spread = $status === 'tightened' ? 2 : 4;
        $minWeeks = max(1, $adjustedMidpoint - $spread);
        $maxWeeks = max($minWeeks, $adjustedMidpoint + $spread);

        if ($status === 'tightened') {
            $maxWeeks = min($maxWeeks, max($minWeeks, (self::intOrNull($eta['max_weeks'] ?? null) ?? $maxWeeks) - 1));
        }

        if (in_array($status, ['widened', 'downgrade', 'retest_requested'], true)) {
            $maxWeeks = max($maxWeeks, (self::intOrNull($eta['max_weeks'] ?? null) ?? $maxWeeks) + 1);
        }

        return [
            ...$eta,
            'min_weeks' => $minWeeks,
            'max_weeks' => $maxWeeks,
            'p50_weeks' => $adjustedMidpoint,
            'p80_weeks' => $maxWeeks,
            'label' => "{$minWeeks}-{$maxWeeks} weeks",
            'modifiers' => self::uniqueStrings([
                ...self::stringList($eta['modifiers'] ?? []),
                ...self::etaModifiersForStatus($status),
            ]),
        ];
    }

    /**
     * @return list<string>
     */
    private static function etaModifiersForStatus(string $status): array
    {
        return match ($status) {
            'tightened' => ['Observed progress tightened the ETA range.'],
            'widened' => ['Observed evidence widened the ETA range.'],
            'downgrade' => ['Pain evidence widened the ETA range.'],
            'retest_requested' => ['Form evidence requests a retest before progression.'],
            default => ['Observed evidence maintained the prior ETA range.'],
        };
    }

    /**
     * @return list<string>
     */
    private static function warningsForStatus(string $status): array
    {
        return match ($status) {
            'downgrade' => ['Pain reached 4/10 or higher; hold progression and use the fallback/regression.'],
            'retest_requested' => ['Form quality is too low for progression; request a retest or technique block.'],
            'widened' => ['Missed exposures widen the ETA until weekly consistency improves.'],
            default => [],
        };
    }

    private static function nextAction(string $status): string
    {
        return match ($status) {
            'downgrade' => 'hold_or_deload',
            'retest_requested' => 'retest_or_technique_focus',
            'widened' => 'maintain_or_reduce',
            'tightened' => 'continue_current_progression',
            default => 'maintain',
        };
    }

    /**
     * @param  array<string, mixed>  $activePortfolio
     * @return array<string, list<array<string, mixed>>>
     */
    private static function trackBuckets(array $activePortfolio): array
    {
        return [
            'development_tracks' => self::arrayList($activePortfolio['development_tracks'] ?? []),
            'technical_practice_tracks' => self::arrayList($activePortfolio['technical_practice_tracks'] ?? []),
            'accessory_tracks' => self::arrayList($activePortfolio['accessory_tracks'] ?? []),
            'maintenance_tracks' => self::arrayList($activePortfolio['maintenance_tracks'] ?? []),
            'foundation_tracks' => self::arrayList($activePortfolio['foundation_tracks'] ?? []),
        ];
    }

    private static function blendWeight(int $evidenceWeeks): float
    {
        return match (true) {
            $evidenceWeeks >= 12 => 0.8,
            $evidenceWeeks >= 8 => 0.65,
            $evidenceWeeks >= 4 => 0.4,
            $evidenceWeeks >= 2 => 0.25,
            default => 0.0,
        };
    }

    /**
     * @param  list<string>  $statuses
     */
    private static function globalStatus(array $statuses): string
    {
        foreach (['downgrade', 'retest_requested', 'widened', 'tightened', 'maintained'] as $status) {
            if (in_array($status, $statuses, true)) {
                return $status;
            }
        }

        return 'prior_based';
    }

    /**
     * @param  array<string, mixed>  $eta
     */
    private static function etaMidpoint(array $eta): int
    {
        $p50 = self::intOrNull($eta['p50_weeks'] ?? null);
        if ($p50 !== null && $p50 > 0) {
            return $p50;
        }

        $min = self::intOrNull($eta['min_weeks'] ?? null) ?? 4;
        $max = self::intOrNull($eta['max_weeks'] ?? null) ?? $min;

        return max(1, (int) round(($min + $max) / 2));
    }

    /**
     * @return array<string, mixed>
     */
    private static function etaValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    /**
     * @param  list<float|int|null>  $values
     */
    private static function average(array $values): ?float
    {
        $numbers = array_values(array_filter($values, is_numeric(...)));

        if ($numbers === []) {
            return null;
        }

        return round(array_sum($numbers) / count($numbers), 3);
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

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private static function uniqueStrings(array $values): array
    {
        return array_values(array_unique(array_filter($values, static fn (mixed $value): bool => is_string($value) && $value !== '')));
    }

    private static function stringValue(mixed $value, string $fallback): string
    {
        return is_string($value) && $value !== '' ? $value : $fallback;
    }

    private static function intOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
