<?php

namespace Database\Factories;

use App\Domain\Profile\Support\AthleteProfileOptions;
use App\Domain\Training\Support\CalisthenicsGoalModuleOptions;
use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use App\Models\AthleteProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AthleteProfile>
 */
class AthleteProfileFactory extends Factory
{
    protected $model = AthleteProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'display_name' => fake()->name(),
            'timezone' => fake()->timezone(),
            'unit_system' => 'metric',
            'age_years' => fake()->numberBetween(18, 45),
            'training_age_months' => fake()->numberBetween(0, 84),
            'experience_level' => fake()->randomElement(AthleteProfileOptions::EXPERIENCE_LEVELS),
            'current_bodyweight_value' => fake()->randomFloat(2, 45, 115),
            'bodyweight_unit' => 'kg',
            'height_value' => fake()->randomFloat(2, 155, 195),
            'height_unit' => 'cm',
            'weight_trend' => 'maintaining',
            'prior_sport_background' => ['strength_training'],
            'primary_goal' => fake()->randomElement(AthleteProfileOptions::GOALS),
            'secondary_goals' => ['strength', 'skill'],
            'target_skills' => ['handstand'],
            'primary_target_skill' => 'handstand',
            'secondary_target_skills' => ['strict_pull_up'],
            'long_term_target_skills' => ['front_lever'],
            'base_focus_areas' => ['pull_capacity', 'core_bodyline'],
            'goal_modules' => [
                'inversion' => [
                    ...CalisthenicsGoalModuleOptions::emptyModule('inversion'),
                    'highest_progression' => 'freestanding_kick_up',
                    'hold_seconds' => 20,
                    'quality' => 'solid',
                ],
            ],
            'roadmap_suggestions' => CalisthenicsRoadmapSuggester::empty(),
            'available_equipment' => ['pull_up_bar', 'rings', 'parallettes', 'resistance_band'],
            'training_locations' => ['home'],
            'movement_limitations' => [],
            'pain_flags' => AthleteProfileOptions::emptyPainFlags(),
            'baseline_tests' => [
                'push_ups' => ['max_strict_reps' => 18],
                'pull_ups' => ['max_strict_reps' => 4, 'fallback_variant' => 'none', 'fallback_reps' => null, 'fallback_seconds' => null],
                'dips' => ['max_strict_reps' => 6, 'fallback_variant' => 'none', 'fallback_reps' => null, 'fallback_seconds' => null],
                'squat' => ['barbell_load_value' => 100, 'barbell_reps' => 5],
                'rows' => ['variant' => 'ring_row', 'max_reps' => 12],
                'lower_body' => ['variant' => 'split_squat', 'load_value' => null, 'load_unit' => 'kg', 'reps' => 12],
                'hollow_hold_seconds' => 35,
                'passive_hang_seconds' => 45,
                'top_support_hold_seconds' => 25,
            ],
            'skill_statuses' => [
                'handstand' => ['status' => 'freestanding_kick_up', 'best_hold_seconds' => 20],
            ],
            'mobility_checks' => [
                'wrist_extension' => 'limited',
                'shoulder_flexion' => 'clear',
                'shoulder_extension' => 'clear',
                'ankle_dorsiflexion' => 'limited',
                'pancake_compression' => 'not_tested',
            ],
            'weighted_baselines' => [
                'experience' => 'curious',
                'unit' => 'kg',
                'movements' => [],
            ],
            'injury_notes' => null,
            'preferred_training_days' => ['monday', 'wednesday', 'friday'],
            'preferred_session_minutes' => 60,
            'weekly_session_goal' => 3,
            'progression_pace' => 'balanced',
            'intensity_preference' => 'auto',
            'effort_tracking_preference' => 'simple',
            'deload_preference' => 'auto',
            'session_structure_preferences' => ['skill_first', 'mobility_finish'],
        ];
    }
}
