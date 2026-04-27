<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\RoadmapInputMapper;
use App\Domain\Training\Roadmap\RoadmapModuleCompatibilityEngine;
use App\Domain\Training\Roadmap\RoadmapStressBudget;
use App\Domain\Training\Roadmap\RoadmapStressBudgetFactory;
use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use PHPUnit\Framework\TestCase;

final class RoadmapModuleCompatibilityEngineTest extends TestCase
{
    public function test_stress_budget_contains_canonical_axes_caps_and_session_time_capacity(): void
    {
        $short = RoadmapStressBudgetFactory::fromInput(RoadmapInputMapper::fromAthleteData($this->athleteData([
            'weekly_session_goal' => 4,
            'preferred_session_minutes' => 45,
        ])));
        $long = RoadmapStressBudgetFactory::fromInput(RoadmapInputMapper::fromAthleteData($this->athleteData([
            'weekly_session_goal' => 4,
            'preferred_session_minutes' => 90,
        ])));
        $moreSessions = RoadmapStressBudgetFactory::fromInput(RoadmapInputMapper::fromAthleteData($this->athleteData([
            'weekly_session_goal' => 6,
            'preferred_session_minutes' => 45,
        ])));

        foreach (RoadmapStressBudget::AXES as $axis) {
            $this->assertArrayHasKey($axis, $short->weeklyBudget);
            $this->assertArrayHasKey($axis, $short->perDaySoftCap);
            $this->assertArrayHasKey($axis, $short->perDayHardCap);
        }

        $this->assertSame($short->weeklyBudget['elbow_pull_tendon'], $long->weeklyBudget['elbow_pull_tendon']);
        $this->assertSame($short->weeklyBudget['wrist_extension'], $long->weeklyBudget['wrist_extension']);
        $this->assertGreaterThan($short->timeCapacityMinutesPerWeek, $long->timeCapacityMinutesPerWeek);
        $this->assertSame($short->spacingCapacity, $long->spacingCapacity);
        $this->assertGreaterThan($short->spacingCapacity, $moreSessions->spacingCapacity);
    }

    public function test_relevant_pain_reduces_budget_and_high_stress_lanes(): void
    {
        $normal = RoadmapStressBudgetFactory::fromInput(RoadmapInputMapper::fromAthleteData($this->athleteData([
            'weekly_session_goal' => 5,
            'training_age_months' => 24,
        ])));
        $pain = RoadmapStressBudgetFactory::fromInput(RoadmapInputMapper::fromAthleteData($this->athleteData([
            'weekly_session_goal' => 5,
            'training_age_months' => 24,
            'pain_level' => 4,
            'pain_flags' => [
                'elbow' => ['severity' => 'moderate', 'status' => 'recent'],
            ],
        ])));

        $this->assertLessThan($normal->weeklyBudget['elbow_pull_tendon'], $pain->weeklyBudget['elbow_pull_tendon']);
        $this->assertLessThan($normal->highStressDevelopmentLanes, $pain->highStressDevelopmentLanes);
        $this->assertContains('elbow_pull_tendon', $pain->painReducedAxes);
        $this->assertNotEmpty($pain->recoveryRules);
    }

    public function test_planche_development_and_handstand_line_are_green(): void
    {
        $compatibility = RoadmapModuleCompatibilityEngine::compare(
            $this->module(
                id: 'planche.tuck_planche.development',
                skill: 'planche',
                purpose: 'development',
                intensity: 'high',
                stress: ['straight_arm_push' => 3, 'wrist_extension' => 3, 'elbow_push_tendon' => 2],
            ),
            $this->module(
                id: 'handstand.chest_to_wall_handstand.technical_practice',
                skill: 'handstand',
                purpose: 'technical_practice',
                intensity: 'low',
                stress: ['inversion_balance' => 2, 'wrist_extension' => 1, 'shoulder_flexion' => 1],
            ),
            $this->budget(),
        )->toArray();

        $this->assertSame('green', $compatibility['state']);
        $this->assertTrue($compatibility['compatible']);
        $this->assertContains('wrist_extension', $compatibility['overlapping_axes']);
    }

    public function test_planche_development_and_hspu_development_are_downgraded_or_blocked(): void
    {
        $compatibility = RoadmapModuleCompatibilityEngine::compare(
            $this->module(
                id: 'planche.tuck_planche.development',
                skill: 'planche',
                purpose: 'development',
                intensity: 'high',
                stress: ['straight_arm_push' => 3, 'wrist_extension' => 3, 'elbow_push_tendon' => 2],
            ),
            $this->module(
                id: 'handstand_push_up.wall_hspu_negative.development',
                skill: 'handstand_push_up',
                purpose: 'development',
                intensity: 'high',
                stress: ['overhead_push' => 3, 'wrist_extension' => 2, 'shoulder_flexion' => 2, 'elbow_push_tendon' => 2],
            ),
            $this->budget(),
        )->toArray();

        $this->assertSame('orange', $compatibility['state']);
        $this->assertTrue($compatibility['compatible']);
        $this->assertSame('downgrade_secondary_to_technical_practice', $compatibility['suggested_adjustment']['action']);
        $this->assertContains('wrist_extension', $compatibility['overlapping_axes']);
    }

    public function test_front_lever_high_and_one_arm_pull_up_high_are_red(): void
    {
        $compatibility = RoadmapModuleCompatibilityEngine::compare(
            $this->module(
                id: 'front_lever.advanced_tuck_front_lever.development',
                skill: 'front_lever',
                purpose: 'development',
                intensity: 'high',
                stress: ['straight_arm_pull' => 3, 'elbow_pull_tendon' => 2, 'trunk_rigidity' => 2],
            ),
            $this->module(
                id: 'one_arm_pull_up.assisted_one_arm_pull_up.development',
                skill: 'one_arm_pull_up',
                purpose: 'development',
                intensity: 'high',
                stress: ['bent_arm_pull' => 4, 'elbow_pull_tendon' => 3, 'systemic_fatigue' => 2],
            ),
            $this->budget(),
        )->toArray();

        $this->assertSame('red', $compatibility['state']);
        $this->assertFalse($compatibility['compatible']);
        $this->assertContains('elbow_pull_tendon', $compatibility['overlapping_axes']);
    }

    public function test_muscle_up_technique_and_weighted_base_are_allowed_with_dose_caps(): void
    {
        $compatibility = RoadmapModuleCompatibilityEngine::compare(
            $this->module(
                id: 'muscle_up.transition_drill.technical_practice',
                skill: 'muscle_up',
                purpose: 'technical_practice',
                intensity: 'medium',
                stress: ['explosive_pull' => 1, 'bent_arm_pull' => 1, 'elbow_pull_tendon' => 1],
            ),
            $this->module(
                id: 'weighted_pull_up.weighted_pull_up.development',
                skill: 'weighted_pull_up',
                purpose: 'development',
                intensity: 'medium',
                stress: ['bent_arm_pull' => 3, 'elbow_pull_tendon' => 2, 'systemic_fatigue' => 2],
            ),
            $this->budget(),
        )->toArray();

        $this->assertSame('yellow', $compatibility['state']);
        $this->assertTrue($compatibility['compatible']);
        $this->assertSame('cap_secondary_exposure', $compatibility['suggested_adjustment']['action']);
    }

    public function test_lower_body_lane_is_green_with_upper_body_stress(): void
    {
        $compatibility = RoadmapModuleCompatibilityEngine::compare(
            $this->module(
                id: 'planche.tuck_planche.development',
                skill: 'planche',
                purpose: 'development',
                intensity: 'high',
                stress: ['straight_arm_push' => 3, 'wrist_extension' => 3, 'elbow_push_tendon' => 2],
            ),
            $this->module(
                id: 'pistol_squat.assisted_pistol.development',
                skill: 'pistol_squat',
                purpose: 'development',
                intensity: 'medium',
                stress: ['lower_body' => 3, 'ankle_knee' => 2],
            ),
            $this->budget(),
        )->toArray();

        $this->assertSame('green', $compatibility['state']);
        $this->assertTrue($compatibility['compatible']);
        $this->assertSame([], $compatibility['overlapping_axes']);
    }

    public function test_portfolio_output_contains_stress_budget_and_module_compatibility_metadata(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->athleteData([
            'primary_target_skill' => 'front_lever',
            'secondary_target_skills' => ['handstand', 'l_sit'],
            'available_equipment' => ['pull_up_bar', 'rings', 'dip_bars', 'parallettes'],
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 28],
                'pull_ups' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 18],
                'lower_body' => ['variant' => 'split_squat', 'reps' => 14],
                'hollow_hold_seconds' => 45,
                'passive_hang_seconds' => 60,
                'top_support_hold_seconds' => 40,
            ],
            'skill_statuses' => [
                'front_lever' => ['status' => 'tuck_front_lever', 'best_hold_seconds' => 12],
                'handstand' => ['status' => 'wall_handstand', 'best_hold_seconds' => 35],
                'l_sit' => ['status' => 'full_l_sit', 'best_hold_seconds' => 12],
            ],
            'mobility_checks' => [
                'ankle_dorsiflexion' => 'clear',
                'pancake_compression' => 'clear',
                'shoulder_flexion' => 'clear',
                'wrist_extension' => 'clear',
            ],
        ]));

        $portfolioPayload = $portfolio['active_skill_portfolio'];

        $this->assertArrayHasKey('stress_budget', $portfolioPayload);
        $this->assertArrayHasKey('module_compatibility', $portfolioPayload);
        $this->assertArrayHasKey('weekly_budget', $portfolioPayload['stress_budget']);
        $this->assertNotEmpty($portfolioPayload['module_compatibility']);
    }

    /**
     * @param  array<string, int>  $stress
     * @return array<string, mixed>
     */
    private function module(string $id, string $skill, string $purpose, string $intensity, array $stress): array
    {
        return [
            'module_id' => $id,
            'skill_track_id' => $skill,
            'node_id' => $id,
            'title' => $id,
            'purpose' => $purpose,
            'pattern' => 'push',
            'intensity_tier' => $intensity,
            'fatigue_class' => 'strength',
            'stress_vector' => $stress,
            'dose' => ['metric' => 'reps', 'sets' => 3, 'reps' => ['min' => 3, 'max' => 6]],
            'time_cost_minutes' => ['min' => 10, 'max' => 16],
            'exposure_targets' => ['min_per_week' => 1, 'target_per_week' => 2, 'max_per_week' => 3],
            'recovery_requirements' => ['min_hours_by_stress_axis' => []],
            'allowed_session_slots' => ['skill_a'],
            'compatible_day_types' => ['general_skills'],
            'prerequisites' => [],
            'progression_rule' => ['action' => 'increase_reps'],
        ];
    }

    private function budget(): RoadmapStressBudget
    {
        return RoadmapStressBudgetFactory::fromInput(RoadmapInputMapper::fromAthleteData($this->athleteData([
            'weekly_session_goal' => 5,
            'preferred_session_minutes' => 60,
            'training_age_months' => 24,
        ])));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function athleteData(array $overrides): array
    {
        return array_replace_recursive([
            'age_years' => 29,
            'height_value' => 178,
            'height_unit' => 'cm',
            'current_bodyweight_value' => 75,
            'bodyweight_unit' => 'kg',
            'weight_trend' => 'maintaining',
            'training_age_months' => 24,
            'weekly_session_goal' => 4,
            'preferred_session_minutes' => 60,
            'available_equipment' => ['pull_up_bar', 'rings', 'dip_bars', 'parallettes'],
            'current_level_tests' => [],
            'skill_statuses' => [],
            'mobility_checks' => [],
            'weighted_baselines' => ['experience' => 'none', 'unit' => 'kg', 'movements' => []],
            'pain_level' => null,
            'pain_flags' => [],
            'primary_target_skill' => null,
            'secondary_target_skills' => [],
            'long_term_target_skills' => [],
        ], $overrides);
    }
}
