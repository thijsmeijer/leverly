<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\RoadmapInput;
use App\Domain\Training\Roadmap\RoadmapInputMapper;
use App\Domain\Training\Roadmap\SkillReadiness;
use App\Domain\Training\Roadmap\SkillReadinessCalculator;
use PHPUnit\Framework\TestCase;

class SkillReadinessCalculatorTest extends TestCase
{
    public function test_muscle_up_can_be_ready_when_pull_dip_equipment_and_power_signals_align(): void
    {
        $readiness = SkillReadinessCalculator::fromInput($this->input([
            'available_equipment' => ['pull_up_bar', 'dip_bars'],
            'training_age_months' => 20,
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

        $muscleUp = $readiness['muscle_up'];

        $this->assertSame('ready', $muscleUp->status);
        $this->assertGreaterThanOrEqual(70, $muscleUp->readinessScore);
        $this->assertGreaterThan(0.55, $muscleUp->confidence);
        $this->assertSame([], $muscleUp->hardBlockers);
        $this->assertContains('Required equipment is available.', $muscleUp->softFactors);
        $this->assertContains('Minimum pull-up node reached.', $muscleUp->softFactors);
    }

    public function test_planche_is_deferred_when_prerequisite_nodes_are_missing(): void
    {
        $readiness = SkillReadinessCalculator::fromInput($this->input([
            'available_equipment' => ['parallettes'],
            'training_age_months' => 18,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 18],
                'dips' => ['max_strict_reps' => 6, 'fallback_variant' => 'none'],
                'hollow_hold_seconds' => 30,
                'top_support_hold_seconds' => 30,
            ],
        ]));

        $planche = $readiness['planche'];

        $this->assertSame('deferred', $planche->status);
        $this->assertContains('Minimum planche node is not reached yet.', $planche->hardBlockers);
        $this->assertLessThan(70, $planche->readinessScore);
        $this->assertContains('Planche is anthropometry-sensitive; missing body context increases uncertainty.', $planche->safetyPenalties);
    }

    public function test_handstand_is_blocked_by_critical_wrist_mobility_even_when_balance_signal_is_good(): void
    {
        $readiness = SkillReadinessCalculator::fromInput($this->input([
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 12],
                'hollow_hold_seconds' => 30,
            ],
            'skill_statuses' => [
                'handstand' => ['status' => 'freestanding_kick_up', 'best_hold_seconds' => 12],
            ],
            'mobility_checks' => [
                'wrist_extension' => 'blocked',
                'shoulder_flexion' => 'clear',
            ],
        ]));

        $handstand = $readiness['handstand'];

        $this->assertSame('blocked', $handstand->status);
        $this->assertSame(0, $handstand->readinessScore);
        $this->assertContains('Wrist extension is blocked for this skill.', $handstand->hardBlockers);
        $this->assertContains('Hard safety gates ran before readiness math.', $handstand->safetyPenalties);
    }

    public function test_one_arm_pull_up_is_deferred_when_pulling_prerequisites_are_weak(): void
    {
        $readiness = SkillReadinessCalculator::fromInput($this->input([
            'available_equipment' => ['pull_up_bar'],
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 2, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'bodyweight_row', 'max_reps' => 10],
                'passive_hang_seconds' => 30,
            ],
        ]));

        $oneArmPullUp = $readiness['one_arm_pull_up'];

        $this->assertSame('deferred', $oneArmPullUp->status);
        $this->assertContains('Minimum pull-up node is not reached yet.', $oneArmPullUp->hardBlockers);
        $this->assertLessThan(50, $oneArmPullUp->readinessScore);
        $this->assertContains('One-arm pull-up is anthropometry-sensitive; missing body context increases uncertainty.', $oneArmPullUp->safetyPenalties);
    }

    public function test_wrist_pain_limits_planche_without_blocking_unrelated_pull_skills(): void
    {
        $readiness = SkillReadinessCalculator::fromInput($this->input([
            'available_equipment' => ['pull_up_bar', 'rings', 'parallettes'],
            'current_bodyweight_value' => 76,
            'height_value' => 178,
            'training_age_months' => 24,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 25],
                'pull_ups' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 14],
                'hollow_hold_seconds' => 40,
                'passive_hang_seconds' => 50,
                'top_support_hold_seconds' => 40,
            ],
            'skill_statuses' => [
                'planche' => ['status' => 'tuck_planche', 'best_hold_seconds' => 6],
                'front_lever' => ['status' => 'advanced_tuck_front_lever', 'best_hold_seconds' => 8],
            ],
            'pain_flags' => [
                'wrist' => ['severity' => 'moderate', 'status' => 'recent'],
            ],
        ]));

        $planche = $readiness['planche'];
        $frontLever = $readiness['front_lever'];

        $this->assertSame('deferred', $planche->status);
        $this->assertContains('Wrist loaded extension is limited by current pain for this skill.', $planche->hardBlockers);
        $this->assertNotContainsText('wrist', $frontLever->hardBlockers);
        $this->assertNotContainsText('wrist', $frontLever->safetyPenalties);
    }

    public function test_elbow_pull_pain_limits_front_lever_without_becoming_a_global_push_blocker(): void
    {
        $readiness = SkillReadinessCalculator::fromInput($this->input([
            'available_equipment' => ['pull_up_bar', 'parallettes'],
            'current_bodyweight_value' => 76,
            'height_value' => 178,
            'training_age_months' => 24,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 24],
                'pull_ups' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 14],
                'hollow_hold_seconds' => 40,
                'passive_hang_seconds' => 50,
            ],
            'skill_statuses' => [
                'front_lever' => ['status' => 'one_leg_front_lever', 'best_hold_seconds' => 5],
                'planche' => ['status' => 'tuck_planche', 'best_hold_seconds' => 6],
            ],
            'pain_flags' => [
                'elbow' => ['severity' => 'moderate', 'status' => 'recent'],
            ],
        ]));

        $frontLever = $readiness['front_lever'];
        $planche = $readiness['planche'];

        $this->assertSame('deferred', $frontLever->status);
        $this->assertContains('Elbow pull tendon readiness is limited by current pain for this skill.', $frontLever->hardBlockers);
        $this->assertNotContainsText('elbow pull tendon', $planche->hardBlockers);
    }

    public function test_shoulder_extension_limits_dip_and_back_lever_without_blocking_pull_up(): void
    {
        $readiness = SkillReadinessCalculator::fromInput($this->input([
            'available_equipment' => ['pull_up_bar', 'dip_bars', 'rings'],
            'current_bodyweight_value' => 76,
            'height_value' => 178,
            'training_age_months' => 18,
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 8, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 8, 'fallback_variant' => 'none'],
                'passive_hang_seconds' => 45,
                'top_support_hold_seconds' => 40,
            ],
            'skill_statuses' => [
                'back_lever' => ['status' => 'tuck_back_lever', 'best_hold_seconds' => 8],
            ],
            'mobility_checks' => [
                'shoulder_extension' => 'painful',
            ],
        ]));

        $this->assertContains('Shoulder extension is painful for this skill.', $readiness['strict_dip']->hardBlockers);
        $this->assertContains('Shoulder extension is painful for this skill.', $readiness['back_lever']->hardBlockers);
        $this->assertNotContainsText('Shoulder extension', $readiness['strict_pull_up']->hardBlockers);
    }

    public function test_shoulder_flexion_and_ankle_limits_are_targeted_to_hspu_and_pistol(): void
    {
        $readiness = SkillReadinessCalculator::fromInput($this->input([
            'available_equipment' => ['parallettes'],
            'current_bodyweight_value' => 76,
            'height_value' => 178,
            'training_age_months' => 18,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 20],
                'lower_body' => ['variant' => 'bodyweight_squat', 'reps' => 24, 'load_unit' => 'kg', 'load_value' => null],
            ],
            'skill_statuses' => [
                'handstand_push_up' => ['status' => 'pike_push_up', 'max_strict_reps' => 8],
                'pistol_squat' => ['status' => 'assisted_pistol', 'max_strict_reps' => 6],
            ],
            'mobility_checks' => [
                'shoulder_flexion' => 'blocked',
                'ankle_dorsiflexion' => 'painful',
            ],
        ]));

        $this->assertContains('Shoulder flexion is blocked for this skill.', $readiness['handstand_push_up']->hardBlockers);
        $this->assertContains('Ankle dorsiflexion is painful for this skill.', $readiness['pistol_squat']->hardBlockers);
        $this->assertNotContainsText('Ankle dorsiflexion', $readiness['handstand_push_up']->hardBlockers);
        $this->assertNotContainsText('Shoulder flexion', $readiness['pistol_squat']->hardBlockers);
    }

    public function test_missing_data_reduces_confidence_without_faking_a_blocker(): void
    {
        $readiness = SkillReadinessCalculator::fromInput($this->input([
            'available_equipment' => ['pull_up_bar'],
            'current_bodyweight_value' => null,
            'height_value' => null,
            'training_age_months' => null,
        ]));

        $frontLever = $readiness['front_lever'];

        $this->assertSame('deferred', $frontLever->status);
        $this->assertLessThan(0.35, $frontLever->confidence);
        $this->assertNotEmpty($frontLever->missingEvidence);
        $this->assertContains('Missing objective inputs reduce confidence.', $frontLever->safetyPenalties);
        $this->assertNotContains('Hard safety gates ran before readiness math.', $frontLever->safetyPenalties);
        $this->assertContainsOnlyInstancesOf(SkillReadiness::class, $readiness);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function input(array $overrides): RoadmapInput
    {
        return RoadmapInputMapper::fromAthleteData(array_replace_recursive([
            'age_years' => 29,
            'height_value' => null,
            'height_unit' => 'cm',
            'current_bodyweight_value' => null,
            'bodyweight_unit' => 'kg',
            'weight_trend' => 'unknown',
            'training_age_months' => 6,
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
        ], $overrides));
    }

    /**
     * @param  list<string>  $values
     */
    private function assertNotContainsText(string $needle, array $values): void
    {
        foreach ($values as $value) {
            $this->assertStringNotContainsString($needle, $value);
        }
    }
}
