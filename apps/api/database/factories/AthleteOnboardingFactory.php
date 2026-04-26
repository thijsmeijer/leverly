<?php

namespace Database\Factories;

use App\Domain\Onboarding\Support\AthleteOnboardingOptions;
use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use App\Models\AthleteOnboarding;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AthleteOnboarding>
 */
class AthleteOnboardingFactory extends Factory
{
    protected $model = AthleteOnboarding::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'age_years' => 29,
            'training_age_months' => 18,
            'experience_level' => 'intermediate',
            'current_bodyweight_value' => 72.5,
            'bodyweight_unit' => 'kg',
            'height_value' => 178,
            'height_unit' => 'cm',
            'prior_sport_background' => ['strength_training'],
            'primary_goal' => 'skill',
            'secondary_goals' => ['strength'],
            'target_skills' => ['strict_pull_up', 'handstand'],
            'primary_target_skill' => 'handstand',
            'secondary_target_skills' => ['strict_pull_up'],
            'long_term_target_skills' => ['front_lever'],
            'base_focus_areas' => ['pull_capacity', 'core_bodyline'],
            'roadmap_suggestions' => CalisthenicsRoadmapSuggester::empty(),
            'available_equipment' => ['pull_up_bar', 'rings', 'parallettes'],
            'training_locations' => ['home'],
            'preferred_training_days' => ['monday', 'wednesday', 'friday'],
            'preferred_session_minutes' => 60,
            'weekly_session_goal' => 3,
            'preferred_training_time' => 'evening',
            'current_level_tests' => [
                'push_ups' => ['progression' => 'strict_push_up', 'max_strict_reps' => 18, 'form_quality' => 4],
                'rows' => ['progression' => 'inverted_row', 'max_strict_reps' => 12],
                'pull_ups' => ['max_strict_reps' => 4, 'progression' => 'strict_pull_up', 'assistance' => null, 'form_quality' => 4],
                'dips' => ['progression' => 'bar_dip', 'max_strict_reps' => 6, 'support_hold_seconds' => 25],
                'squat' => ['max_reps' => 20, 'progression' => 'split_squat'],
                'hollow_hold_seconds' => 35,
                'arch_hold_seconds' => 25,
                'dead_hang_seconds' => 30,
                'support_hold_seconds' => 25,
                'wall_handstand_seconds' => 20,
                'l_sit_hold_seconds' => 8,
            ],
            'skill_statuses' => [
                'handstand' => ['status' => 'assisted', 'best_hold_seconds' => 20],
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
            'readiness_rating' => 4,
            'sleep_quality' => 4,
            'soreness_level' => 2,
            'pain_level' => 1,
            'pain_areas' => [],
            'pain_notes' => null,
            'starter_plan_key' => fake()->randomElement(AthleteOnboardingOptions::STARTER_PLANS),
            'completed_at' => null,
        ];
    }
}
