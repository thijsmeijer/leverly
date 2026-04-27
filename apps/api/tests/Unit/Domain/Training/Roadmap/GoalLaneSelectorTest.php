<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\GoalLaneSelector;
use App\Domain\Training\Roadmap\RoadmapInput;
use App\Domain\Training\Roadmap\RoadmapInputMapper;
use PHPUnit\Framework\TestCase;

class GoalLaneSelectorTest extends TestCase
{
    public function test_planche_and_hspu_peak_are_not_selected_together(): void
    {
        $selection = GoalLaneSelector::fromInput($this->input([
            'primary_target_skill' => 'planche',
            'secondary_target_skills' => ['handstand_push_up'],
            'available_equipment' => ['parallettes'],
            'skill_statuses' => [
                'planche' => ['status' => 'full_planche', 'best_hold_seconds' => 4],
                'handstand_push_up' => ['status' => 'full_wall_hspu', 'max_strict_reps' => 3],
            ],
            'mobility_checks' => ['wrist_extension' => 'clear', 'shoulder_flexion' => 'clear'],
        ]));

        $this->assertSame('planche', $selection->primaryLane?->skill);
        $this->assertNull($selection->secondaryLane);
        $this->assertSame('foundation_strength', $selection->foundationLane['slug']);
        $this->assertSame(
            'Too much overlapping straight-arm push, overhead, and wrist-extension stress with the primary lane.',
            $this->deferredReason($selection->deferredGoals, 'handstand_push_up'),
        );
    }

    public function test_front_lever_and_one_arm_pull_up_peak_are_not_selected_together(): void
    {
        $selection = GoalLaneSelector::fromInput($this->input([
            'primary_target_skill' => 'front_lever',
            'secondary_target_skills' => ['one_arm_pull_up'],
            'available_equipment' => ['pull_up_bar'],
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 16],
                'hollow_hold_seconds' => 40,
                'passive_hang_seconds' => 50,
            ],
            'skill_statuses' => [
                'front_lever' => ['status' => 'full_front_lever', 'best_hold_seconds' => 5],
                'one_arm_pull_up' => ['status' => 'strict_one_arm_pull_up', 'max_strict_reps' => 1],
            ],
        ]));

        $this->assertSame('front_lever', $selection->primaryLane?->skill);
        $this->assertNull($selection->secondaryLane);
        $this->assertSame(
            'Too much overlapping straight-arm pull, vertical pull, and elbow-flexor stress with the primary lane.',
            $this->deferredReason($selection->deferredGoals, 'one_arm_pull_up'),
        );
    }

    public function test_muscle_up_and_one_arm_pull_up_peak_are_not_selected_together(): void
    {
        $selection = GoalLaneSelector::fromInput($this->input([
            'primary_target_skill' => 'muscle_up',
            'secondary_target_skills' => ['one_arm_pull_up'],
            'available_equipment' => ['pull_up_bar', 'dip_bars'],
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 15],
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
            'skill_statuses' => [
                'one_arm_pull_up' => ['status' => 'strict_one_arm_pull_up', 'max_strict_reps' => 1],
            ],
        ]));

        $this->assertSame('muscle_up', $selection->primaryLane?->skill);
        $this->assertNull($selection->secondaryLane);
        $this->assertSame(
            'Too much overlapping vertical pull and elbow-flexor stress with the primary lane.',
            $this->deferredReason($selection->deferredGoals, 'one_arm_pull_up'),
        );
    }

    public function test_muscle_up_and_handstand_line_can_be_selected_together(): void
    {
        $selection = GoalLaneSelector::fromInput($this->input([
            'primary_target_skill' => 'muscle_up',
            'secondary_target_skills' => ['handstand'],
            'available_equipment' => ['pull_up_bar', 'dip_bars'],
            'weekly_session_goal' => 4,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 18],
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
            'skill_statuses' => [
                'handstand' => ['status' => 'freestanding_kick_up', 'best_hold_seconds' => 12],
            ],
            'mobility_checks' => ['wrist_extension' => 'clear', 'shoulder_flexion' => 'clear'],
        ]));

        $this->assertSame('muscle_up', $selection->primaryLane?->skill);
        $this->assertSame('handstand', $selection->secondaryLane?->skill);
        $this->assertSame('foundation_strength', $selection->foundationLane['slug']);
        $this->assertContains('Handstand line fits as a lower-fatigue secondary lane.', $selection->compatibilityNotes);
    }

    /**
     * @param  list<array{skill: string, reason: string, unlock_conditions: list<string>}>  $deferredGoals
     */
    private function deferredReason(array $deferredGoals, string $skill): ?string
    {
        foreach ($deferredGoals as $deferredGoal) {
            if ($deferredGoal['skill'] === $skill) {
                return $deferredGoal['reason'];
            }
        }

        return null;
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
            'training_age_months' => 24,
            'weekly_session_goal' => 4,
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
