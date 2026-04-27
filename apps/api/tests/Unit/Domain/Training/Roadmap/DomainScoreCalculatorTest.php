<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\DomainScore;
use App\Domain\Training\Roadmap\DomainScoreCalculator;
use App\Domain\Training\Roadmap\RoadmapInput;
use App\Domain\Training\Roadmap\RoadmapInputMapper;
use PHPUnit\Framework\TestCase;

class DomainScoreCalculatorTest extends TestCase
{
    public function test_high_leverage_evidence_can_score_high_with_visible_uncertainty(): void
    {
        $scores = DomainScoreCalculator::fromInput($this->input([
            'current_bodyweight_value' => 82,
            'height_value' => 183,
            'training_age_months' => 18,
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'passive_hang_seconds' => 45,
            ],
            'weighted_baselines' => [
                'unit' => 'kg',
                'movements' => [
                    ['movement' => 'weighted_pull_up', 'external_load_value' => 32, 'reps' => 4, 'rir' => 1],
                ],
            ],
        ]));

        $verticalPull = $scores['vertical_pull'];

        $this->assertGreaterThan(75, $verticalPull->score);
        $this->assertGreaterThan(0.35, $verticalPull->uncertainty);
        $this->assertLessThan(0.65, $verticalPull->confidence);
        $this->assertContains('Weighted pull-up: 32kg for 4 reps.', $verticalPull->contributingInputs);
        $this->assertContains('One-arm pull-up progression evidence.', $verticalPull->missingInputs);
        $this->assertContains('Body mass: 82kg.', $verticalPull->modifiers);
        $this->assertSame('Strong signal, but missing inputs keep this domain uncertain.', $verticalPull->bottleneck);
    }

    public function test_low_score_can_have_high_confidence_when_weakness_is_observed(): void
    {
        $scores = DomainScoreCalculator::fromInput($this->input([
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 0],
                'dips' => ['max_strict_reps' => 0, 'fallback_variant' => 'none'],
                'top_support_hold_seconds' => 0,
            ],
        ]));

        $verticalPush = $scores['vertical_push'];

        $this->assertLessThan(10, $verticalPush->score);
        $this->assertGreaterThan(0.65, $verticalPush->confidence);
        $this->assertLessThan(0.35, $verticalPush->uncertainty);
        $this->assertContains('Push-ups: 0 reps.', $verticalPush->contributingInputs);
        $this->assertContains('Dips: 0 reps.', $verticalPush->contributingInputs);
        $this->assertSame('This is a clear bottleneck from observed tests.', $verticalPush->bottleneck);
    }

    public function test_pain_limits_tissue_tolerance_before_overload_decisions(): void
    {
        $scores = DomainScoreCalculator::fromInput($this->input([
            'pain_level' => 6,
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 8, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 8, 'fallback_variant' => 'none'],
                'passive_hang_seconds' => 50,
                'top_support_hold_seconds' => 40,
            ],
        ]));

        $tissue = $scores['tissue_tolerance'];

        $this->assertLessThanOrEqual(45, $tissue->score);
        $this->assertGreaterThan(0.6, $tissue->confidence);
        $this->assertContains('Pain level: 6/10.', $tissue->contributingInputs);
        $this->assertSame('Pain is the limiting tissue-tolerance signal; hold progression until it settles.', $tissue->bottleneck);
    }

    public function test_missing_data_returns_all_domains_with_high_uncertainty(): void
    {
        $scores = DomainScoreCalculator::fromInput($this->input([]));

        $this->assertSame([
            'vertical_push',
            'vertical_pull',
            'horizontal_pull_straight_arm_pull',
            'trunk_rigidity',
            'compression',
            'inversion_balance',
            'lower_body_strength',
            'tissue_tolerance',
        ], array_keys($scores));
        $this->assertContainsOnlyInstancesOf(DomainScore::class, $scores);

        foreach ($scores as $score) {
            $this->assertGreaterThan(0.65, $score->uncertainty);
            $this->assertNotEmpty($score->missingInputs);
            $this->assertSame('Missing inputs make this domain uncertain.', $score->bottleneck);
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function input(array $overrides): RoadmapInput
    {
        return RoadmapInputMapper::fromAthleteData(array_replace_recursive([
            'age_years' => 31,
            'height_value' => 178,
            'height_unit' => 'cm',
            'current_bodyweight_value' => 75,
            'bodyweight_unit' => 'kg',
            'weight_trend' => 'maintaining',
            'training_age_months' => 12,
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
            'weighted_baselines' => ['experience' => 'none', 'unit' => 'kg', 'movements' => []],
            'goal_modules' => [],
            'pain_level' => null,
        ], $overrides));
    }
}
