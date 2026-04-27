<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\RoadmapInput;
use App\Domain\Training\Roadmap\RoadmapInputMapper;
use App\Domain\Training\Roadmap\RoadmapLayerEstimate;
use App\Domain\Training\Roadmap\RoadmapLayerEstimator;
use PHPUnit\Framework\TestCase;

class RoadmapLayerEstimatorTest extends TestCase
{
    public function test_high_confidence_next_step_uses_a_short_range_not_a_precise_date(): void
    {
        $estimate = RoadmapLayerEstimator::fromInput($this->input([
            'primary_target_skill' => 'muscle_up',
            'available_equipment' => ['pull_up_bar', 'dip_bars'],
            'training_age_months' => 24,
            'weekly_session_goal' => 4,
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 9, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 9, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 12],
                'hollow_hold_seconds' => 35,
                'passive_hang_seconds' => 45,
                'top_support_hold_seconds' => 35,
            ],
            'goal_modules' => [
                'pull_skill' => [
                    'highest_progression' => 'high_pull_up',
                    'metric_type' => 'reps',
                    'reps' => 4,
                    'hold_seconds' => null,
                    'load_value' => null,
                    'load_unit' => 'kg',
                    'quality' => 'clean',
                    'notes' => null,
                ],
            ],
        ]));

        $this->assertInstanceOf(RoadmapLayerEstimate::class, $estimate);
        $this->assertSame('muscle_up', $estimate->primarySkill);
        $this->assertGreaterThanOrEqual(4, $estimate->currentBlock['eta']->lowerWeeks);
        $this->assertLessThanOrEqual(8, $estimate->currentBlock['eta']->upperWeeks);
        $this->assertGreaterThan(0.55, $estimate->currentBlock['eta']->confidence);
        $this->assertContains('4-6 week block retest', $estimate->retestCadence);
    }

    public function test_low_confidence_missing_data_creates_a_wide_range(): void
    {
        $estimate = RoadmapLayerEstimator::fromInput($this->input([
            'primary_target_skill' => 'front_lever',
            'available_equipment' => ['pull_up_bar'],
            'current_bodyweight_value' => null,
            'height_value' => null,
            'training_age_months' => null,
        ]));

        $this->assertSame('front_lever', $estimate->primarySkill);
        $this->assertGreaterThanOrEqual(24, $estimate->primaryEta->upperWeeks);
        $this->assertLessThan(0.4, $estimate->primaryEta->confidence);
        $this->assertContains('Missing objective data widens the ETA range.', $estimate->primaryEta->modifiers);
        $this->assertSame('6-24+ month aspiration layer', $estimate->aspirationLayer['label']);
    }

    public function test_pain_widens_eta_and_lowers_confidence(): void
    {
        $estimate = RoadmapLayerEstimator::fromInput($this->input([
            'primary_target_skill' => 'muscle_up',
            'available_equipment' => ['pull_up_bar', 'dip_bars'],
            'pain_level' => 6,
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 8, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 8, 'fallback_variant' => 'none'],
                'passive_hang_seconds' => 40,
                'top_support_hold_seconds' => 35,
            ],
        ]));

        $this->assertGreaterThanOrEqual(10, $estimate->currentBlock['eta']->upperWeeks);
        $this->assertLessThan(0.55, $estimate->currentBlock['eta']->confidence);
        $this->assertContains('Pain widens the ETA range.', $estimate->primaryEta->modifiers);
    }

    public function test_weight_trend_adjusts_strength_skill_eta(): void
    {
        $estimate = RoadmapLayerEstimator::fromInput($this->input([
            'primary_target_skill' => 'weighted_pull_up',
            'available_equipment' => ['pull_up_bar', 'dip_belt'],
            'weight_trend' => 'cutting',
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 9, 'fallback_variant' => 'none'],
                'passive_hang_seconds' => 45,
            ],
        ]));

        $this->assertContains('Cutting phase widens high-force strength ETA.', $estimate->primaryEta->modifiers);
    }

    public function test_missing_equipment_widens_eta_instead_of_creating_a_precise_failure_date(): void
    {
        $estimate = RoadmapLayerEstimator::fromInput($this->input([
            'primary_target_skill' => 'weighted_pull_up',
            'available_equipment' => ['pull_up_bar'],
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 9, 'fallback_variant' => 'none'],
                'passive_hang_seconds' => 45,
            ],
        ]));

        $this->assertGreaterThanOrEqual(12, $estimate->primaryEta->upperWeeks);
        $this->assertLessThan(0.55, $estimate->primaryEta->confidence);
        $this->assertContains('Missing required equipment widens the ETA range.', $estimate->primaryEta->modifiers);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function input(array $overrides): RoadmapInput
    {
        return RoadmapInputMapper::fromAthleteData(array_replace_recursive([
            'age_years' => 29,
            'height_value' => 178,
            'height_unit' => 'cm',
            'current_bodyweight_value' => 75,
            'bodyweight_unit' => 'kg',
            'weight_trend' => 'maintaining',
            'training_age_months' => 12,
            'weekly_session_goal' => 3,
            'available_equipment' => [],
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => null],
                'pull_ups' => ['max_strict_reps' => null, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => null, 'fallback_variant' => 'none'],
                'squat' => ['barbell_load_value' => null, 'barbell_reps' => null],
                'rows' => ['variant' => 'bodyweight_row', 'max_reps' => null],
                'lower_body' => ['variant' => 'bodyweight_squat', 'reps' => null, 'load_unit' => 'kg', 'load_value' => null],
                'hollow_hold_seconds' => null,
                'passive_hang_seconds' => null,
                'top_support_hold_seconds' => null,
            ],
            'skill_statuses' => [],
            'mobility_checks' => [],
            'weighted_baselines' => ['experience' => 'none', 'unit' => 'kg', 'movements' => []],
            'goal_modules' => [],
            'pain_level' => null,
            'pain_flags' => [],
            'primary_target_skill' => null,
            'secondary_target_skills' => [],
            'long_term_target_skills' => [],
        ], $overrides));
    }
}
