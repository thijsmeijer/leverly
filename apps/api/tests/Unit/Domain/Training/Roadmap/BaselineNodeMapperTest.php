<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\BaselineNodeMapper;
use App\Domain\Training\Roadmap\BaselineNodePlacement;
use App\Domain\Training\Roadmap\RoadmapInput;
use App\Domain\Training\Roadmap\RoadmapInputMapper;
use PHPUnit\Framework\TestCase;

class BaselineNodeMapperTest extends TestCase
{
    public function test_it_places_zero_pull_up_with_short_hang_before_eccentrics(): void
    {
        $placements = BaselineNodeMapper::fromInput($this->input([
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 0, 'fallback_variant' => 'none'],
                'passive_hang_seconds' => 6,
            ],
        ]));

        $pull = $placements['pull_up'];

        $this->assertSame('passive_hang', $pull->currentNode->slug);
        $this->assertSame('active_hang', $pull->nextNode?->slug);
        $this->assertSame(15, $pull->completionPercentage);
        $this->assertContains('Passive hang: 6 seconds.', $pull->observedEvidence);
        $this->assertContains('Active hang or scapular pull evidence.', $pull->missingEvidence);
        $this->assertSame(0.35, $pull->confidenceContribution);
    }

    public function test_it_places_zero_pull_up_with_useful_eccentric_capacity_later(): void
    {
        $placements = BaselineNodeMapper::fromInput($this->input([
            'current_level_tests' => [
                'pull_ups' => [
                    'max_strict_reps' => 0,
                    'fallback_variant' => 'eccentric',
                    'fallback_seconds' => 5,
                ],
                'passive_hang_seconds' => 32,
            ],
        ]));

        $pull = $placements['pull_up'];

        $this->assertSame('eccentric_pull_up', $pull->currentNode->slug);
        $this->assertSame('assisted_pull_up', $pull->nextNode?->slug);
        $this->assertGreaterThan(40, $pull->completionPercentage);
        $this->assertContains('Eccentric pull-up: 5 seconds.', $pull->observedEvidence);
        $this->assertSame([], $pull->missingEvidence);
    }

    public function test_it_places_zero_dip_as_support_or_assisted_capacity(): void
    {
        $supportOnly = BaselineNodeMapper::fromInput($this->input([
            'current_level_tests' => [
                'dips' => ['max_strict_reps' => 0, 'fallback_variant' => 'none'],
                'top_support_hold_seconds' => 18,
            ],
        ]))['dip'];

        $assisted = BaselineNodeMapper::fromInput($this->input([
            'current_level_tests' => [
                'dips' => [
                    'max_strict_reps' => 0,
                    'fallback_variant' => 'assisted',
                    'fallback_reps' => 5,
                ],
                'top_support_hold_seconds' => 20,
            ],
        ]))['dip'];

        $this->assertSame('top_support', $supportOnly->currentNode->slug);
        $this->assertSame('assisted_dip', $supportOnly->nextNode?->slug);
        $this->assertContains('Top support: 18 seconds.', $supportOnly->observedEvidence);
        $this->assertSame('assisted_dip', $assisted->currentNode->slug);
        $this->assertContains('Assisted dip: 5 reps.', $assisted->observedEvidence);
    }

    public function test_it_uses_high_pull_and_weighted_pull_evidence(): void
    {
        $placements = BaselineNodeMapper::fromInput($this->input([
            'current_bodyweight_value' => 80,
            'bodyweight_unit' => 'kg',
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'passive_hang_seconds' => 45,
            ],
            'weighted_baselines' => [
                'unit' => 'kg',
                'movements' => [
                    ['movement' => 'weighted_pull_up', 'external_load_value' => 30, 'reps' => 5, 'rir' => 2],
                ],
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

        $this->assertSame('weighted_pull_up', $placements['pull_up']->currentNode->slug);
        $this->assertSame('high_pull_up', $placements['muscle_up']->currentNode->slug);
        $this->assertContains('High pull-up: 4 reps.', $placements['muscle_up']->observedEvidence);
        $this->assertGreaterThan(0.6, $placements['pull_up']->confidenceContribution);
    }

    public function test_it_uses_high_dip_and_weighted_dip_evidence(): void
    {
        $placements = BaselineNodeMapper::fromInput($this->input([
            'current_bodyweight_value' => 72,
            'bodyweight_unit' => 'kg',
            'current_level_tests' => [
                'dips' => ['max_strict_reps' => 12, 'fallback_variant' => 'none'],
                'top_support_hold_seconds' => 40,
            ],
            'weighted_baselines' => [
                'unit' => 'kg',
                'movements' => [
                    ['movement' => 'weighted_dip', 'external_load_value' => 24, 'reps' => 5, 'rir' => 1],
                ],
            ],
        ]));

        $dip = $placements['dip'];

        $this->assertSame('weighted_dip', $dip->currentNode->slug);
        $this->assertNull($dip->nextNode);
        $this->assertGreaterThan(80, $dip->completionPercentage);
        $this->assertContains('Weighted dip: 24kg for 5 reps.', $dip->observedEvidence);
    }

    public function test_missing_row_is_uncertain_and_reduces_lever_confidence_without_erasing_other_evidence(): void
    {
        $placements = BaselineNodeMapper::fromInput($this->input([
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 4, 'fallback_variant' => 'none'],
                'hollow_hold_seconds' => 28,
            ],
        ]));

        $row = $placements['row'];
        $frontLever = $placements['front_lever'];

        $this->assertSame('no_horizontal_pull', $row->currentNode->slug);
        $this->assertContains('Horizontal row test.', $row->missingEvidence);
        $this->assertContains('Horizontal row capacity for lever confidence.', $frontLever->missingEvidence);
        $this->assertContains('Hollow body hold: 28 seconds.', $frontLever->observedEvidence);
        $this->assertLessThan(0.5, $frontLever->confidenceContribution);
    }

    public function test_missing_barbell_uses_bodyweight_lower_body_fallback_without_zeroing_confidence(): void
    {
        $placements = BaselineNodeMapper::fromInput($this->input([
            'current_level_tests' => [
                'squat' => ['barbell_load_value' => null, 'barbell_reps' => null],
                'lower_body' => ['variant' => 'split_squat', 'reps' => 12, 'load_unit' => 'kg', 'load_value' => null],
            ],
        ]));

        $lowerBody = $placements['lower_body'];
        $pistol = $placements['pistol_squat'];

        $this->assertSame('split_squat', $lowerBody->currentNode->slug);
        $this->assertContains('Barbell squat ratio.', $lowerBody->missingEvidence);
        $this->assertGreaterThan(0.4, $lowerBody->confidenceContribution);
        $this->assertSame('split_squat', $pistol->currentNode->slug);
        $this->assertContains('Lower-body fallback: split squat for 12 reps.', $pistol->observedEvidence);
    }

    public function test_mixed_evidence_returns_a_placement_for_each_graph_family(): void
    {
        $placements = BaselineNodeMapper::fromInput($this->input([
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 18],
                'pull_ups' => ['max_strict_reps' => 6, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 7, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 14],
                'hollow_hold_seconds' => 35,
                'passive_hang_seconds' => 50,
                'top_support_hold_seconds' => 35,
            ],
            'skill_statuses' => [
                'handstand' => ['status' => 'freestanding_kick_up', 'best_hold_seconds' => 12],
                'l_sit' => ['status' => 'full_l_sit', 'best_hold_seconds' => 15],
                'planche' => ['status' => 'advanced_tuck_planche', 'best_hold_seconds' => 7],
            ],
            'goal_modules' => [
                'push_compression' => [
                    'highest_progression' => 'full_l_sit',
                    'metric_type' => 'hold_seconds',
                    'reps' => null,
                    'hold_seconds' => 15,
                    'load_value' => null,
                    'load_unit' => 'kg',
                    'quality' => 'clean',
                    'notes' => null,
                ],
            ],
        ]));

        $this->assertSame(
            [
                'push_up',
                'pull_up',
                'dip',
                'row',
                'bodyline',
                'support',
                'lower_body',
                'compression',
                'handstand',
                'hspu',
                'muscle_up',
                'front_lever',
                'back_lever',
                'planche',
                'one_arm_pull_up',
                'pistol_squat',
                'human_flag',
            ],
            array_keys($placements),
        );
        $this->assertContainsOnlyInstancesOf(BaselineNodePlacement::class, $placements);
        $this->assertSame('freestanding_kick_up', $placements['handstand']->currentNode->slug);
        $this->assertSame('full_l_sit', $placements['compression']->currentNode->slug);
        $this->assertSame('advanced_tuck_planche', $placements['planche']->currentNode->slug);
        $this->assertContains('Ring row: 14 reps.', $placements['front_lever']->observedEvidence);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function input(array $overrides): RoadmapInput
    {
        return RoadmapInputMapper::fromAthleteData(array_replace_recursive([
            'current_bodyweight_value' => 75,
            'bodyweight_unit' => 'kg',
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
        ], $overrides));
    }
}
