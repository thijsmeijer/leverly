<?php

namespace Database\Factories;

use App\Domain\Profile\Support\AthleteProfileOptions;
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
            'training_age_months' => fake()->numberBetween(0, 84),
            'experience_level' => fake()->randomElement(AthleteProfileOptions::EXPERIENCE_LEVELS),
            'current_bodyweight_value' => fake()->randomFloat(2, 45, 115),
            'bodyweight_unit' => 'kg',
            'primary_goal' => fake()->randomElement(AthleteProfileOptions::GOALS),
            'secondary_goals' => ['strength', 'skill'],
            'target_skills' => ['freestanding handstand', 'strict pull-up'],
            'available_equipment' => ['pull_up_bar', 'rings', 'parallettes', 'resistance_band'],
            'training_locations' => ['home'],
            'movement_limitations' => [],
            'injury_notes' => null,
            'preferred_training_days' => ['monday', 'wednesday', 'friday'],
            'preferred_session_minutes' => 60,
            'weekly_session_goal' => 3,
            'preferred_training_time' => 'evening',
            'progression_pace' => 'balanced',
            'intensity_preference' => 'auto',
            'effort_tracking_preference' => 'simple',
            'deload_preference' => 'auto',
            'session_structure_preferences' => ['skill_first', 'mobility_finish'],
        ];
    }
}
