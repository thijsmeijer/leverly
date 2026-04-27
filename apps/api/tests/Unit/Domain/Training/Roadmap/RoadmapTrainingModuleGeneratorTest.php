<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Training\Roadmap;

use App\Domain\Training\Roadmap\NodeReadinessCalculator;
use App\Domain\Training\Roadmap\RoadmapInputMapper;
use App\Domain\Training\Roadmap\RoadmapTrainingModuleGenerator;
use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use PHPUnit\Framework\TestCase;

final class RoadmapTrainingModuleGeneratorTest extends TestCase
{
    public function test_development_module_contains_schedulable_metadata_from_next_node_readiness(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'primary_target_skill' => 'front_lever',
            'available_equipment' => ['pull_up_bar', 'rings'],
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 10, 'fallback_variant' => 'none'],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 16],
                'hollow_hold_seconds' => 45,
                'passive_hang_seconds' => 60,
            ],
            'skill_statuses' => [
                'front_lever' => ['status' => 'tuck_front_lever', 'best_hold_seconds' => 12],
            ],
        ]));
        $readiness = NodeReadinessCalculator::forSkill('front_lever', $input);

        $modules = RoadmapTrainingModuleGenerator::fromNodeReadiness($readiness, 'development');

        $this->assertNotEmpty($modules);

        $module = $modules[0];

        $this->assertModuleContract($module);
        $this->assertSame('front_lever.advanced_tuck_front_lever.development', $module['module_id']);
        $this->assertSame('front_lever', $module['skill_track_id']);
        $this->assertSame('front_lever.advanced_tuck_front_lever', $module['node_id']);
        $this->assertSame('development', $module['purpose']);
        $this->assertSame('pull', $module['pattern']);
        $this->assertSame('high', $module['intensity_tier']);
        $this->assertSame('static_strength', $module['fatigue_class']);
        $this->assertSame('hold_seconds', $module['dose']['metric']);
        $this->assertGreaterThanOrEqual(2, $module['dose']['sets']);
        $this->assertArrayHasKey('straight_arm_pull', $module['stress_vector']);
        $this->assertGreaterThanOrEqual(48, $module['recovery_requirements']['min_hours_by_stress_axis']['straight_arm_pull']);
        $this->assertContains('skill_a', $module['allowed_session_slots']);
        $this->assertContains('pull_skills', $module['compatible_day_types']);
        $this->assertSame('increase_hold_time', $module['progression_rule']['action']);
    }

    public function test_foundation_modules_are_generated_before_skill_modules_with_required_purposes(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'available_equipment' => ['pull_up_bar', 'dip_bars', 'parallettes'],
            'mobility_checks' => [
                'ankle_dorsiflexion' => 'limited',
                'shoulder_flexion' => 'clear',
                'wrist_extension' => 'clear',
            ],
        ]));

        $modules = RoadmapTrainingModuleGenerator::foundationModules($input, []);
        $ids = array_column($modules, 'module_id');
        $purposes = array_unique(array_column($modules, 'purpose'));

        $this->assertSame([
            'foundation.push',
            'foundation.pull',
            'foundation.lower_body',
            'foundation.trunk_bodyline',
            'foundation.mobility',
            'foundation.tissue_prep',
        ], array_slice($ids, 0, 6));
        $this->assertContains('foundation_strength', $purposes);
        $this->assertContains('mobility_prep', $purposes);
        $this->assertContains('tissue_capacity', $purposes);

        foreach ($modules as $module) {
            $this->assertModuleContract($module);
        }
    }

    public function test_target_specific_foundation_modules_are_created_even_when_general_basics_are_owned(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'primary_target_skill' => 'muscle_up',
            'secondary_target_skills' => ['planche'],
            'available_equipment' => ['pull_up_bar', 'dip_bars', 'parallettes'],
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 35],
                'pull_ups' => ['max_strict_reps' => 14, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => 14, 'fallback_variant' => 'none'],
                'hollow_hold_seconds' => 60,
                'passive_hang_seconds' => 75,
                'top_support_hold_seconds' => 50,
            ],
        ]));

        $modules = RoadmapTrainingModuleGenerator::foundationModules($input, ['muscle_up', 'planche']);
        $ids = array_column($modules, 'module_id');

        $this->assertContains('foundation.muscle_up.high_pull', $ids);
        $this->assertContains('foundation.planche.planche_lean', $ids);

        $highPull = $this->moduleById($modules, 'foundation.muscle_up.high_pull');
        $plancheLean = $this->moduleById($modules, 'foundation.planche.planche_lean');

        $this->assertSame('accessory_transfer', $highPull['purpose']);
        $this->assertSame('tissue_capacity', $plancheLean['purpose']);
        $this->assertArrayHasKey('explosive_pull', $highPull['stress_vector']);
        $this->assertArrayHasKey('straight_arm_push', $plancheLean['stress_vector']);
    }

    public function test_handstand_practice_and_hspu_development_have_different_stress_and_intensity(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'primary_target_skill' => 'handstand',
            'secondary_target_skills' => ['handstand_push_up'],
            'available_equipment' => ['parallettes'],
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 28],
                'dips' => ['max_strict_reps' => 8, 'fallback_variant' => 'none'],
                'hollow_hold_seconds' => 45,
                'top_support_hold_seconds' => 35,
            ],
            'skill_statuses' => [
                'handstand' => ['status' => 'wall_plank', 'best_hold_seconds' => 30],
                'handstand_push_up' => ['status' => 'pike_push_up', 'max_reps' => 8],
            ],
            'mobility_checks' => [
                'shoulder_flexion' => 'clear',
                'wrist_extension' => 'clear',
            ],
        ]));

        $handstand = RoadmapTrainingModuleGenerator::fromNodeReadiness(
            NodeReadinessCalculator::forSkill('handstand', $input),
            'technical_practice',
        )[0];
        $hspu = RoadmapTrainingModuleGenerator::fromNodeReadiness(
            NodeReadinessCalculator::forSkill('handstand_push_up', $input),
            'development',
        )[0];

        $this->assertSame('technical_practice', $handstand['purpose']);
        $this->assertContains($handstand['intensity_tier'], ['low', 'medium']);
        $this->assertSame('development', $hspu['purpose']);
        $this->assertSame('handstand_push_up', $hspu['skill_track_id']);
        $this->assertContains($hspu['intensity_tier'], ['high', 'max']);
        $this->assertLessThan($hspu['stress_vector']['overhead_push'], $handstand['stress_vector']['overhead_push'] ?? 0);
        $this->assertContains('general_skills', $handstand['compatible_day_types']);
        $this->assertContains('push_strength', $hspu['compatible_day_types']);
    }

    public function test_module_examples_exist_for_required_skill_tracks(): void
    {
        $examples = RoadmapTrainingModuleGenerator::exampleModules();
        $ids = array_column($examples, 'skill_track_id');

        foreach ([
            'planche',
            'front_lever',
            'muscle_up',
            'handstand',
            'handstand_push_up',
            'one_arm_pull_up',
            'l_sit',
            'pistol_squat',
            'weighted_pull_up',
            'weighted_dip',
            'human_flag',
        ] as $skill) {
            $this->assertContains($skill, $ids, "Missing module example for {$skill}.");
        }
    }

    public function test_hard_gated_readiness_generates_no_training_modules(): void
    {
        $input = RoadmapInputMapper::fromAthleteData($this->athleteData([
            'primary_target_skill' => 'front_lever',
            'available_equipment' => [],
            'pain_level' => 7,
            'pain_flags' => [
                'elbow' => ['severity' => 'severe', 'status' => 'acute'],
            ],
        ]));

        $modules = RoadmapTrainingModuleGenerator::fromNodeReadiness(
            NodeReadinessCalculator::forSkill('front_lever', $input),
            'development',
        );

        $this->assertSame([], $modules);
    }

    public function test_portfolio_tracks_include_generated_modules_and_foundation_layer_modules(): void
    {
        $portfolio = CalisthenicsRoadmapSuggester::portfolioFromAthleteData($this->athleteData([
            'primary_target_skill' => 'front_lever',
            'secondary_target_skills' => ['handstand', 'l_sit'],
            'available_equipment' => ['pull_up_bar', 'rings', 'dip_bars', 'parallettes'],
            'weekly_session_goal' => 4,
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

        $development = $this->trackById($portfolio['active_skill_portfolio']['development_tracks'], 'front_lever');

        $this->assertIsArray($development);
        $this->assertNotEmpty($development['modules']);
        $this->assertNotEmpty($portfolio['active_skill_portfolio']['foundation_modules']);
        $this->assertSame(
            $portfolio['active_skill_portfolio']['foundation_modules'],
            $portfolio['foundation_layer']['modules'],
        );
        $this->assertModuleContract($development['modules'][0]);
    }

    /**
     * @param  array<string, mixed>  $module
     */
    private function assertModuleContract(array $module): void
    {
        foreach ([
            'module_id',
            'skill_track_id',
            'node_id',
            'title',
            'purpose',
            'pattern',
            'intensity_tier',
            'fatigue_class',
            'stress_vector',
            'dose',
            'time_cost_minutes',
            'exposure_targets',
            'recovery_requirements',
            'allowed_session_slots',
            'compatible_day_types',
            'prerequisites',
            'progression_rule',
        ] as $key) {
            $this->assertArrayHasKey($key, $module);
        }

        $this->assertArrayHasKey('min', $module['time_cost_minutes']);
        $this->assertArrayHasKey('max', $module['time_cost_minutes']);
        $this->assertArrayHasKey('min_per_week', $module['exposure_targets']);
        $this->assertArrayHasKey('target_per_week', $module['exposure_targets']);
        $this->assertArrayHasKey('max_per_week', $module['exposure_targets']);
        $this->assertArrayHasKey('min_hours_by_stress_axis', $module['recovery_requirements']);
        $this->assertContains($module['purpose'], [
            'development',
            'technical_practice',
            'foundation_strength',
            'accessory_transfer',
            'mobility_prep',
            'tissue_capacity',
            'maintenance',
        ]);
        $this->assertContains($module['intensity_tier'], ['low', 'medium', 'high', 'max']);
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     * @return array<string, mixed>
     */
    private function moduleById(array $modules, string $moduleId): array
    {
        foreach ($modules as $module) {
            if (($module['module_id'] ?? null) === $moduleId) {
                return $module;
            }
        }

        $this->fail("Missing module {$moduleId}.");
    }

    /**
     * @param  list<array<string, mixed>>  $tracks
     * @return array<string, mixed>|null
     */
    private function trackById(array $tracks, string $id): ?array
    {
        foreach ($tracks as $track) {
            if (($track['skill_track_id'] ?? null) === $id) {
                return $track;
            }
        }

        return null;
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
            'available_equipment' => [],
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => null],
                'pull_ups' => ['max_strict_reps' => null, 'fallback_variant' => 'none'],
                'dips' => ['max_strict_reps' => null, 'fallback_variant' => 'none'],
                'squat' => ['barbell_load_value' => null, 'barbell_reps' => null],
                'rows' => ['variant' => 'bodyweight_row', 'max_reps' => null],
                'lower_body' => ['variant' => 'bodyweight_squat', 'load_value' => null, 'load_unit' => 'kg', 'reps' => null],
                'hollow_hold_seconds' => null,
                'passive_hang_seconds' => null,
                'top_support_hold_seconds' => null,
            ],
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
