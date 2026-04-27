<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\RoadmapInputMapper;
use App\Domain\Training\Roadmap\RoadmapStressBudgetFactory;
use App\Domain\Training\Roadmap\RoadmapWeeklyScheduler;
use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use PHPUnit\Framework\TestCase;

final class RoadmapWeeklySchedulerTest extends TestCase
{
    public function test_two_day_schedule_assigns_slots_in_quality_first_order_and_rest_days(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'weekly_session_goal' => 2,
            'preferred_session_minutes' => 60,
        ]));

        $schedule = RoadmapWeeklyScheduler::fromModules(
            input: $input,
            tracks: [
                $this->track('front_lever', 'development', [
                    $this->module('front_lever.tuck.development', 'front_lever', 'Tuck front lever', 'development', 'pull', 'high', ['straight_arm_pull' => 3, 'elbow_pull_tendon' => 2], ['skill_a', 'primary_strength'], ['general_skills', 'pull_strength']),
                ]),
                $this->track('handstand', 'technical_practice', [
                    $this->module('handstand.wall.technical', 'handstand', 'Wall line', 'technical_practice', 'inversion', 'low', ['wrist_extension' => 1, 'shoulder_flexion' => 1], ['skill_a', 'skill_b'], ['general_skills', 'push_skills']),
                ]),
            ],
            foundationModules: [
                $this->module('foundation.mobility', 'foundation_mobility', 'Mobility prep', 'mobility_prep', 'mobility', 'low', ['wrist_extension' => 1], ['warmup_prep', 'cooldown_mobility'], ['general_skills', 'full_body']),
            ],
            stressBudget: RoadmapStressBudgetFactory::fromInput($input),
        );

        $this->assertCount(2, $schedule['days']);
        $this->assertCount(5, $schedule['rest_days']);
        $this->assertNotEmpty($schedule['days'][0]['modules']);
        $this->assertEveryDayUsesQualityFirstSlotOrder($schedule['days']);
        $this->assertContains('warmup_prep', $this->slots($schedule));
        $this->assertContains('skill_a', $this->slots($schedule));
    }

    public function test_four_day_schedule_separates_high_same_axis_modules(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'weekly_session_goal' => 4,
            'preferred_session_minutes' => 70,
        ]));

        $schedule = RoadmapWeeklyScheduler::fromModules(
            input: $input,
            tracks: [
                $this->track('front_lever', 'development', [
                    $this->module('front_lever.tuck.development', 'front_lever', 'Tuck front lever', 'development', 'pull', 'high', ['straight_arm_pull' => 3, 'elbow_pull_tendon' => 3], ['skill_a', 'primary_strength'], ['general_skills', 'pull_strength', 'push_strength', 'legs']),
                ]),
                $this->track('one_arm_pull_up', 'development', [
                    $this->module('one_arm_pull_up.assisted.development', 'one_arm_pull_up', 'Assisted one-arm pull-up', 'development', 'pull', 'high', ['bent_arm_pull' => 3, 'elbow_pull_tendon' => 3], ['skill_a', 'primary_strength'], ['general_skills', 'pull_strength', 'push_strength', 'legs']),
                ]),
            ],
            foundationModules: [],
            stressBudget: RoadmapStressBudgetFactory::fromInput($input),
        );

        $frontLeverDay = $this->dayIndexForModule($schedule, 'front_lever.tuck.development');
        $oneArmDay = $this->dayIndexForModule($schedule, 'one_arm_pull_up.assisted.development');

        $this->assertIsInt($frontLeverDay);
        $this->assertIsInt($oneArmDay);
        $this->assertGreaterThan(1, abs($frontLeverDay - $oneArmDay));
    }

    public function test_six_day_portfolio_schedule_uses_expected_templates_and_modules(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->strongAthlete([
            'primary_target_skill' => 'muscle_up',
            'secondary_target_skills' => ['front_lever', 'handstand', 'l_sit'],
            'weekly_session_goal' => 6,
            'preferred_session_minutes' => 70,
            'skill_statuses' => [
                'handstand' => ['status' => 'freestanding_handstand', 'best_hold_seconds' => 20],
                'l_sit' => ['status' => 'full_l_sit', 'best_hold_seconds' => 20],
                'front_lever' => ['status' => 'tuck_front_lever', 'best_hold_seconds' => 12],
                'muscle_up' => ['status' => 'chest_to_bar_pull_up', 'max_reps' => 3],
            ],
        ]));

        $schedule = $portfolio['active_skill_portfolio']['weekly_schedule'];
        $dayTypes = array_column($schedule['days'], 'day_type');

        $this->assertCount(6, $schedule['days']);
        $this->assertCount(1, $schedule['rest_days']);
        $this->assertContains('general_skills', $dayTypes);
        $this->assertContains('legs', $dayTypes);
        $this->assertContains('pull_strength', $dayTypes);
        $this->assertContains('push_strength', $dayTypes);
        $this->assertContains('pull_skills', $dayTypes);
        $this->assertContains('push_skills', $dayTypes);
        $this->assertNotEmpty(array_merge(...array_map(
            static fn (array $day): array => $day['modules'],
            $schedule['days'],
        )));
        $this->assertArrayHasKey('stress_ledger', $schedule['days'][0]);
        $this->assertArrayHasKey('time_ledger', $schedule['days'][0]);
    }

    public function test_scheduler_warns_when_time_or_stress_caps_overflow(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'weekly_session_goal' => 2,
            'preferred_session_minutes' => 15,
        ]));

        $schedule = RoadmapWeeklyScheduler::fromModules(
            input: $input,
            tracks: [
                $this->track('planche', 'development', [
                    $this->module('planche.tuck.development', 'planche', 'Tuck planche', 'development', 'push', 'max', ['straight_arm_push' => 4, 'wrist_extension' => 4], ['skill_a', 'primary_strength'], ['full_body'], ['min' => 20, 'max' => 24]),
                    $this->module('hspu.wall.development', 'handstand_push_up', 'Wall HSPU', 'development', 'push', 'high', ['overhead_push' => 3, 'wrist_extension' => 3], ['skill_a', 'primary_strength'], ['full_body'], ['min' => 18, 'max' => 22]),
                ]),
            ],
            foundationModules: [
                $this->module('foundation.push', 'foundation_push', 'Push foundation', 'foundation_strength', 'push', 'medium', ['bent_arm_push' => 3, 'elbow_push_tendon' => 2], ['primary_strength', 'accessory'], ['full_body'], ['min' => 14, 'max' => 18]),
            ],
            stressBudget: RoadmapStressBudgetFactory::fromInput($input),
        );

        $warnings = implode(' ', $schedule['warnings']);

        $this->assertStringContainsString('time', strtolower($warnings));
        $this->assertStringContainsString('stress', strtolower($warnings));
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     * @return array<string, mixed>
     */
    private function track(string $skill, string $mode, array $modules): array
    {
        return [
            'skill_track_id' => $skill,
            'mode' => $mode,
            'modules' => $modules,
        ];
    }

    /**
     * @param  array<string, int>  $stressVector
     * @param  list<string>  $slots
     * @param  list<string>  $dayTypes
     * @param  array{min: int, max: int}  $timeCost
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
        array $slots,
        array $dayTypes,
        array $timeCost = ['min' => 10, 'max' => 16],
    ): array {
        return [
            'module_id' => $id,
            'skill_track_id' => $skill,
            'node_id' => "{$skill}.node",
            'title' => $title,
            'purpose' => $purpose,
            'pattern' => $pattern,
            'intensity_tier' => $intensity,
            'fatigue_class' => $intensity === 'low' ? 'skill' : 'strength',
            'stress_vector' => $stressVector,
            'time_cost_minutes' => $timeCost,
            'exposure_targets' => ['min_per_week' => 1, 'target_per_week' => 1, 'max_per_week' => 2],
            'recovery_requirements' => ['min_hours_by_stress_axis' => []],
            'allowed_session_slots' => $slots,
            'compatible_day_types' => $dayTypes,
            'dose' => ['metric' => 'quality'],
            'prerequisites' => [],
            'progression_rule' => ['action' => 'repeat'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $days
     */
    private function assertEveryDayUsesQualityFirstSlotOrder(array $days): void
    {
        foreach ($days as $day) {
            $ranks = array_map(
                static fn (array $module): int => (int) $module['slot_rank'],
                $day['modules'],
            );

            $sorted = $ranks;
            sort($sorted);

            $this->assertSame($sorted, $ranks);
        }
    }

    /**
     * @param  array<string, mixed>  $schedule
     * @return list<string>
     */
    private function slots(array $schedule): array
    {
        return array_values(array_map(
            static fn (array $module): string => (string) $module['slot'],
            array_merge(...array_map(
                static fn (array $day): array => $day['modules'],
                $schedule['days'],
            )),
        ));
    }

    /**
     * @param  array<string, mixed>  $schedule
     */
    private function dayIndexForModule(array $schedule, string $moduleId): ?int
    {
        foreach ($schedule['days'] as $day) {
            foreach ($day['modules'] as $module) {
                if (($module['module_id'] ?? null) === $moduleId) {
                    return (int) $day['day_index'];
                }
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function strongAthlete(array $overrides): array
    {
        return $this->athleteData(array_replace_recursive([
            'available_equipment' => ['pull_up_bar', 'rings', 'dip_bars', 'parallettes', 'box_bench'],
            'training_age_months' => 48,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 35],
                'pull_ups' => ['max_strict_reps' => 14, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 14, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 20],
                'lower_body' => ['variant' => 'split_squat', 'reps' => 18],
                'hollow_hold_seconds' => 60,
                'passive_hang_seconds' => 75,
                'top_support_hold_seconds' => 50,
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
