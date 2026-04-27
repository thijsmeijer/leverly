<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\RoadmapEvidenceNormalizer;
use App\Domain\Training\Roadmap\RoadmapInputMapper;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class RoadmapEvidenceNormalizerTest extends TestCase
{
    public function test_body_and_training_context_normalizes_units_and_defaults_session_duration(): void
    {
        $profile = RoadmapEvidenceNormalizer::fromInput(
            RoadmapInputMapper::fromAthleteData([
                'age_years' => 31,
                'height_value' => 70,
                'height_unit' => 'in',
                'current_bodyweight_value' => 165,
                'bodyweight_unit' => 'lb',
                'training_age_months' => 30,
                'resistance_training_age_months' => 18,
                'weight_trend' => 'cutting',
                'body_lever_context' => ['ape_index' => 1.03],
                'weekly_session_goal' => 5,
                'preferred_session_minutes' => null,
            ]),
            today: new DateTimeImmutable('2026-04-27'),
        )->toArray();

        $this->assertSame('roadmap.evidence.v3', $profile['version']);
        $this->assertSame(31, $profile['body_context']['age_years']);
        $this->assertSame(177.8, $profile['body_context']['height_cm']);
        $this->assertSame(74.84, $profile['body_context']['bodyweight_kg']);
        $this->assertSame(30, $profile['body_context']['training_age_months']);
        $this->assertSame(18, $profile['body_context']['resistance_training_age_months']);
        $this->assertSame('cutting', $profile['body_context']['weight_trend']);
        $this->assertSame(['ape_index' => 1.03], $profile['body_context']['body_lever_context']);
        $this->assertSame(5, $profile['training_context']['max_sessions_per_week']);
        $this->assertSame(45, $profile['training_context']['estimated_session_minutes']);
        $this->assertTrue($profile['training_context']['uses_default_session_minutes']);
    }

    public function test_weighted_evidence_converts_loads_and_estimates_total_system_e1rm(): void
    {
        $profile = RoadmapEvidenceNormalizer::fromInput(
            RoadmapInputMapper::fromAthleteData([
                'current_bodyweight_value' => 80,
                'bodyweight_unit' => 'kg',
                'current_level_tests' => [
                    'squat' => [
                        'barbell_load_value' => 100,
                        'barbell_reps' => 5,
                        'tested_at' => '2026-04-20',
                        'confidence' => 0.9,
                    ],
                ],
                'weighted_baselines' => [
                    'unit' => 'kg',
                    'movements' => [
                        ['movement' => 'weighted_pull_up', 'external_load_value' => 20, 'reps' => 5, 'rir' => 1],
                        ['movement' => 'weighted_dip', 'external_load_value' => 30, 'reps' => 3, 'rir' => 2],
                        ['movement' => 'weighted_muscle_up', 'external_load_value' => 10, 'reps' => 2, 'rir' => 1],
                        ['movement' => 'loaded_push_up', 'external_load_value' => 20, 'reps' => 8, 'rir' => 2],
                    ],
                ],
            ]),
            today: new DateTimeImmutable('2026-04-27'),
        )->toArray();

        $pullUp = $this->sampleByKey($profile, 'weighted.weighted_pull_up');
        $dip = $this->sampleByKey($profile, 'weighted.weighted_dip');
        $muscleUp = $this->sampleByKey($profile, 'weighted.weighted_muscle_up');
        $squat = $this->sampleByKey($profile, 'baseline.squat.barbell');
        $loadedPushUp = $this->sampleByKey($profile, 'weighted.loaded_push_up');

        $this->assertSame(20.0, $pullUp['estimates']['external_load_kg']);
        $this->assertSame(100.0, $pullUp['estimates']['total_system_load_kg']);
        $this->assertSame(120.0, $pullUp['estimates']['estimated_1rm_kg']);
        $this->assertSame(128.33, $dip['estimates']['estimated_1rm_kg']);
        $this->assertSame(99.0, $muscleUp['estimates']['estimated_1rm_kg']);
        $this->assertSame(210.0, $squat['estimates']['estimated_1rm_kg']);
        $this->assertSame(71.2, $loadedPushUp['estimates']['total_system_load_kg']);
        $this->assertContains('fuzzy_load_estimate', $loadedPushUp['flags']);
    }

    public function test_missing_stale_and_low_confidence_evidence_request_micro_tests(): void
    {
        $profile = RoadmapEvidenceNormalizer::fromInput(
            RoadmapInputMapper::fromAthleteData([
                'current_level_tests' => [
                    'push_ups' => [
                        'max_strict_reps' => 12,
                        'tested_at' => '2025-01-01',
                        'confidence' => 0.2,
                        'form_quality' => 'questionable',
                    ],
                    'pull_ups' => ['max_strict_reps' => null],
                ],
            ]),
            today: new DateTimeImmutable('2026-04-27'),
        )->toArray();

        $pushUps = $this->sampleByKey($profile, 'baseline.push_ups.max_reps');
        $pendingKeys = array_column($profile['pending_tests'], 'key');

        $this->assertSame(481, $pushUps['age_days']);
        $this->assertContains('stale_evidence', $pushUps['flags']);
        $this->assertContains('low_confidence_self_report', $pushUps['flags']);
        $this->assertLessThan(0.2, $pushUps['confidence']);
        $this->assertContains('baseline.pull_ups.max_reps', $pendingKeys);
        $this->assertContains('baseline.push_ups.max_reps', $pendingKeys);
        $this->assertContains('stale_evidence', $profile['uncertainty_flags']);
    }

    public function test_high_rep_push_up_is_endurance_biased_and_only_fuzzy_strength_support(): void
    {
        $profile = RoadmapEvidenceNormalizer::fromInput(
            RoadmapInputMapper::fromAthleteData([
                'current_bodyweight_value' => 75,
                'bodyweight_unit' => 'kg',
                'current_level_tests' => [
                    'push_ups' => [
                        'max_strict_reps' => 45,
                        'form_quality' => 'clean',
                        'confidence' => 0.85,
                    ],
                ],
            ]),
            today: new DateTimeImmutable('2026-04-27'),
        )->toArray();

        $pushUps = $this->sampleByKey($profile, 'baseline.push_ups.max_reps');

        $this->assertSame('supporting', $pushUps['decisiveness']);
        $this->assertContains('high_rep_endurance_bias', $pushUps['flags']);
        $this->assertContains('fuzzy_load_estimate', $pushUps['flags']);
        $this->assertSame(48.0, $pushUps['estimates']['approx_bodyweight_load_kg']);
        $this->assertLessThan(0.85, $pushUps['confidence']);
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function sampleByKey(array $profile, string $key): array
    {
        foreach ($profile['samples'] as $sample) {
            if (($sample['key'] ?? null) === $key) {
                return $sample;
            }
        }

        $this->fail("Evidence sample [{$key}] was not found.");
    }
}
