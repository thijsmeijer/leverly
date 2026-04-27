<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\RoadmapInputMapper;
use App\Domain\Training\Roadmap\RoadmapPhasePlan;
use App\Domain\Training\Roadmap\RoadmapProgressionRuleFactory;
use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use PHPUnit\Framework\TestCase;

final class RoadmapPhasePlanTest extends TestCase
{
    public function test_beginner_phase_uses_four_weeks_with_retest_and_deload_guidance(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->athleteData([
            'primary_target_skill' => 'planche',
            'weekly_session_goal' => 3,
            'training_age_months' => 4,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 0],
                'pull_ups' => ['max_strict_reps' => 0, 'fallback_variant' => 'eccentric'],
                'dips' => ['max_strict_reps' => 0, 'fallback_variant' => 'assisted'],
                'lower_body' => ['variant' => 'bodyweight_squat', 'reps' => 8],
                'hollow_hold_seconds' => 10,
                'passive_hang_seconds' => 12,
                'top_support_hold_seconds' => 4,
            ],
        ]));

        $phase = $portfolio['active_skill_portfolio']['phase_plan'];

        $this->assertSame(4, $phase['duration_weeks']['target']);
        $this->assertSame(4, $phase['retest_timing']['block_retest_week']);
        $this->assertNotEmpty($phase['weekly_emphasis']);
        $this->assertNotEmpty($phase['roles']['foundation']);
        $this->assertStringContainsString('deload', strtolower(implode(' ', $phase['deload_guidance']['triggers'])));
    }

    public function test_stable_weighted_strength_phase_can_use_six_to_eight_weeks(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'weekly_session_goal' => 5,
            'training_age_months' => 48,
            'readiness_rating' => 5,
            'sleep_quality' => 5,
            'weighted_baselines' => [
                'experience' => 'experienced',
                'unit' => 'kg',
                'movements' => [
                    'weighted_pull_up' => ['load_value' => 32, 'reps' => 4],
                ],
            ],
        ]));

        $phase = RoadmapPhasePlan::fromPortfolio(
            input: $input,
            tracks: [
                $this->track('weighted_pull_up', 'development', [
                    $this->module('weighted_pull_up.load.development', 'weighted_pull_up', 'Weighted pull-up', 'development', 'pull', 'high', ['bent_arm_pull' => 3], ['metric' => 'load']),
                ]),
            ],
            foundationModules: [],
            weeklySchedule: ['warnings' => []],
        );

        $this->assertSame(6, $phase['duration_weeks']['target']);
        $this->assertSame(8, $phase['duration_weeks']['max']);
        $this->assertStringContainsString('stable', strtolower($phase['duration_reason']));
    }

    public function test_progression_rules_cover_dynamic_static_technical_and_weighted_modules(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([]));

        $dynamic = RoadmapProgressionRuleFactory::fromModule(
            $this->module('push.reps', 'strict_push_up', 'Push-up', 'development', 'push', 'medium', ['bent_arm_push' => 2], ['metric' => 'reps']),
            $input,
        );
        $static = RoadmapProgressionRuleFactory::fromModule(
            $this->module('lever.hold', 'front_lever', 'Front lever', 'development', 'pull', 'high', ['straight_arm_pull' => 3], ['metric' => 'hold_seconds']),
            $input,
        );
        $technical = RoadmapProgressionRuleFactory::fromModule(
            $this->module('handstand.quality', 'handstand', 'Handstand', 'technical_practice', 'inversion', 'low', ['wrist_extension' => 1], ['metric' => 'quality']),
            $input,
        );
        $weighted = RoadmapProgressionRuleFactory::fromModule(
            $this->module('weighted.load', 'weighted_pull_up', 'Weighted pull-up', 'development', 'pull', 'high', ['bent_arm_pull' => 3], ['metric' => 'load']),
            $input,
        );

        $this->assertSame('dynamic_reps', $dynamic['rule_type']);
        $this->assertContains('two_successful_exposures', $dynamic['success_requirements']);
        $this->assertSame('static_hold', $static['rule_type']);
        $this->assertContains('total_quality_time', $static['success_requirements']);
        $this->assertSame('technical_practice', $technical['rule_type']);
        $this->assertContains('consistency', $technical['success_requirements']);
        $this->assertSame('weighted_load', $weighted['rule_type']);
        $this->assertStringContainsString('2-5%', $weighted['next_adjustment']);
        $this->assertTrue($weighted['only_one_major_lever']);
    }

    public function test_pain_conservative_rules_hold_progression_and_add_deload_triggers(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'pain_level' => 4,
            'pain_flags' => ['elbow' => ['severity' => 'moderate', 'status' => 'recent']],
        ]));

        $rule = RoadmapProgressionRuleFactory::fromModule(
            $this->module('pull.reps', 'strict_pull_up', 'Pull-up', 'development', 'pull', 'medium', ['bent_arm_pull' => 2, 'elbow_pull_tendon' => 2], ['metric' => 'reps']),
            $input,
        );

        $this->assertFalse($rule['progression_allowed']);
        $this->assertSame('maintain_or_regress', $rule['next_action']);
        $this->assertStringContainsString('pain', strtolower(implode(' ', $rule['deload_triggers'])));
    }

    public function test_portfolio_output_contains_phase_plan_rules(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->strongAthlete([
            'primary_target_skill' => 'front_lever',
            'secondary_target_skills' => ['handstand', 'l_sit'],
            'weekly_session_goal' => 4,
        ]));

        $phase = $portfolio['active_skill_portfolio']['phase_plan'];

        $this->assertArrayHasKey('duration_weeks', $phase);
        $this->assertArrayHasKey('roles', $phase);
        $this->assertArrayHasKey('progression_rules', $phase);
        $this->assertNotEmpty($phase['progression_rules']);
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     * @return array<string, mixed>
     */
    private function track(string $skill, string $mode, array $modules): array
    {
        return [
            'skill_track_id' => $skill,
            'display_name' => $skill,
            'mode' => $mode,
            'modules' => $modules,
        ];
    }

    /**
     * @param  array<string, int>  $stressVector
     * @param  array<string, mixed>  $dose
     * @return array<string, mixed>
     */
    private function module(
        string $id,
        string $skill,
        string $title,
        string $purpose,
        string $pattern,
        string $intensity,
        array $stressVector,
        array $dose,
    ): array {
        return [
            'module_id' => $id,
            'skill_track_id' => $skill,
            'node_id' => "{$skill}.node",
            'title' => $title,
            'purpose' => $purpose,
            'pattern' => $pattern,
            'intensity_tier' => $intensity,
            'fatigue_class' => 'strength',
            'stress_vector' => $stressVector,
            'dose' => $dose,
            'time_cost_minutes' => ['min' => 10, 'max' => 16],
            'exposure_targets' => ['min_per_week' => 1, 'target_per_week' => 2, 'max_per_week' => 3],
            'recovery_requirements' => ['min_hours_by_stress_axis' => []],
            'allowed_session_slots' => ['skill_a', 'primary_strength'],
            'compatible_day_types' => ['general_skills'],
            'prerequisites' => [],
            'progression_rule' => ['action' => 'repeat'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function strongAthlete(array $overrides): array
    {
        return $this->athleteData(array_replace_recursive([
            'available_equipment' => ['pull_up_bar', 'rings', 'dip_bars', 'parallettes', 'box_bench'],
            'training_age_months' => 36,
            'readiness_rating' => 5,
            'sleep_quality' => 5,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 32],
                'pull_ups' => ['max_strict_reps' => 12, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 12, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 18],
                'lower_body' => ['variant' => 'split_squat', 'reps' => 18],
                'hollow_hold_seconds' => 55,
                'passive_hang_seconds' => 70,
                'top_support_hold_seconds' => 45,
            ],
            'skill_statuses' => [
                'handstand' => ['status' => 'chest_to_wall_handstand', 'best_hold_seconds' => 45],
                'l_sit' => ['status' => 'full_l_sit', 'best_hold_seconds' => 15],
                'front_lever' => ['status' => 'tuck_front_lever', 'best_hold_seconds' => 12],
            ],
            'mobility_checks' => [
                'ankle_dorsiflexion' => 'clear',
                'pancake_compression' => 'clear',
                'shoulder_extension' => 'clear',
                'shoulder_flexion' => 'clear',
                'wrist_extension' => 'clear',
            ],
        ], $overrides));
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
            'training_age_months' => 18,
            'weekly_session_goal' => 4,
            'preferred_session_minutes' => 60,
            'available_equipment' => [],
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
