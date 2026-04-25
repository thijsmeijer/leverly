<?php

namespace Tests\Feature\API\V1\Profile;

use App\Models\AthleteProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AthleteProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_athlete_profile(): void
    {
        $this->getJson('/api/v1/me/profile')
            ->assertUnauthorized();

        $this->patchJson('/api/v1/me/profile', $this->validPayload())
            ->assertUnauthorized();
    }

    public function test_athlete_can_create_read_and_update_their_profile(): void
    {
        $user = User::factory()->create(['name' => 'Ada Account']);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/me/profile', $this->validPayload());

        $response
            ->assertOk()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.display_name', 'Ada Athlete')
            ->assertJsonPath('data.timezone', 'Europe/Amsterdam')
            ->assertJsonPath('data.unit_system', 'metric')
            ->assertJsonPath('data.training_age_months', 18)
            ->assertJsonPath('data.current_bodyweight_value', 72.5)
            ->assertJsonPath('data.primary_goal', 'skill')
            ->assertJsonPath('data.secondary_goals.0', 'strength')
            ->assertJsonPath('data.available_equipment.2', 'rings')
            ->assertJsonPath('data.movement_limitations.0.area', 'wrist')
            ->assertJsonPath('data.preferred_training_days.2', 'friday')
            ->assertJsonPath('data.progression_pace', 'balanced')
            ->assertJsonPath('data.effort_tracking_preference', 'rir');

        $profileId = $response->json('data.id');

        $this->assertIsString($profileId);
        $this->assertTrue(Str::isUlid($profileId));
        $this->assertDatabaseHas('athlete_profiles', [
            'id' => $profileId,
            'user_id' => $user->id,
            'display_name' => 'Ada Athlete',
            'timezone' => 'Europe/Amsterdam',
        ]);

        $this->getJson('/api/v1/me/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $profileId)
            ->assertJsonPath('data.target_skills.1', 'strict muscle-up');

        $this->patchJson('/api/v1/me/profile', [
            'display_name' => 'Ada Bars',
            'preferred_session_minutes' => 45,
            'secondary_goals' => ['mobility', 'conditioning'],
            'available_equipment' => ['floor', 'wall', 'rings'],
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $profileId)
            ->assertJsonPath('data.display_name', 'Ada Bars')
            ->assertJsonPath('data.timezone', 'Europe/Amsterdam')
            ->assertJsonPath('data.preferred_session_minutes', 45)
            ->assertJsonPath('data.secondary_goals.1', 'conditioning')
            ->assertJsonPath('data.available_equipment.2', 'rings');
    }

    public function test_profile_data_is_scoped_to_the_signed_in_user(): void
    {
        $signedInUser = User::factory()->create();
        $otherProfile = AthleteProfile::factory()->create([
            'display_name' => 'Other Athlete',
        ]);

        Sanctum::actingAs($signedInUser);

        $this->getJson('/api/v1/me/profile')
            ->assertNotFound()
            ->assertJsonPath('message', 'Athlete profile has not been created.');

        $this->patchJson('/api/v1/me/profile', [
            'display_name' => 'Signed In Athlete',
        ])
            ->assertOk()
            ->assertJsonPath('data.user_id', $signedInUser->id)
            ->assertJsonPath('data.display_name', 'Signed In Athlete');

        $this->getJson('/api/v1/me/profile')
            ->assertOk()
            ->assertJsonPath('data.user_id', $signedInUser->id)
            ->assertJsonMissing(['id' => $otherProfile->id])
            ->assertJsonMissing(['display_name' => 'Other Athlete']);
    }

    public function test_profile_defaults_allow_incremental_setup(): void
    {
        $user = User::factory()->create(['name' => 'Default Name']);

        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me/profile', [])
            ->assertOk()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.display_name', 'Default Name')
            ->assertJsonPath('data.timezone', 'UTC')
            ->assertJsonPath('data.unit_system', 'metric')
            ->assertJsonPath('data.bodyweight_unit', 'kg')
            ->assertJsonPath('data.experience_level', 'new')
            ->assertJsonPath('data.available_equipment.0', 'floor')
            ->assertJsonPath('data.preferred_training_time', 'flexible')
            ->assertJsonPath('data.progression_pace', 'balanced')
            ->assertJsonPath('data.intensity_preference', 'auto');
    }

    public function test_profile_validation_rejects_malformed_algorithm_inputs(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/v1/me/profile', [
            'display_name' => '',
            'timezone' => 'Mars/Olympus',
            'unit_system' => 'stones',
            'training_age_months' => -1,
            'experience_level' => 'expertish',
            'current_bodyweight_value' => 3,
            'bodyweight_unit' => 'stone',
            'primary_goal' => 'unsupported_goal',
            'secondary_goals' => ['strength', 'unsupported_goal'],
            'target_skills' => ['a'],
            'available_equipment' => ['trampoline'],
            'training_locations' => ['moon'],
            'movement_limitations' => [
                [
                    'area' => 'unsupported_area',
                    'severity' => 'catastrophic',
                    'status' => 'forever',
                    'notes' => str_repeat('x', 501),
                ],
            ],
            'preferred_training_days' => ['funday'],
            'preferred_session_minutes' => 5,
            'weekly_session_goal' => 15,
            'preferred_training_time' => 'midnight',
            'progression_pace' => 'reckless',
            'intensity_preference' => 'maximum',
            'effort_tracking_preference' => 'vibes',
            'deload_preference' => 'never',
            'session_structure_preferences' => ['random'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'display_name',
                'timezone',
                'unit_system',
                'training_age_months',
                'experience_level',
                'current_bodyweight_value',
                'bodyweight_unit',
                'primary_goal',
                'secondary_goals.1',
                'target_skills.0',
                'available_equipment.0',
                'training_locations.0',
                'movement_limitations.0.area',
                'movement_limitations.0.severity',
                'movement_limitations.0.status',
                'movement_limitations.0.notes',
                'preferred_training_days.0',
                'preferred_session_minutes',
                'weekly_session_goal',
                'preferred_training_time',
                'progression_pace',
                'intensity_preference',
                'effort_tracking_preference',
                'deload_preference',
                'session_structure_preferences.0',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'display_name' => 'Ada Athlete',
            'timezone' => 'Europe/Amsterdam',
            'unit_system' => 'metric',
            'training_age_months' => 18,
            'experience_level' => 'intermediate',
            'current_bodyweight_value' => 72.5,
            'bodyweight_unit' => 'kg',
            'primary_goal' => 'skill',
            'secondary_goals' => ['strength', 'mobility'],
            'target_skills' => ['freestanding handstand', 'strict muscle-up'],
            'available_equipment' => ['floor', 'pull_up_bar', 'rings', 'parallettes', 'resistance_band'],
            'training_locations' => ['home', 'park'],
            'movement_limitations' => [
                [
                    'area' => 'wrist',
                    'severity' => 'mild',
                    'status' => 'recurring',
                    'notes' => 'Needs longer warm-up.',
                ],
            ],
            'injury_notes' => 'Left wrist can get irritated under high volume.',
            'preferred_training_days' => ['monday', 'wednesday', 'friday'],
            'preferred_session_minutes' => 60,
            'weekly_session_goal' => 4,
            'preferred_training_time' => 'evening',
            'progression_pace' => 'balanced',
            'intensity_preference' => 'auto',
            'effort_tracking_preference' => 'rir',
            'deload_preference' => 'auto',
            'session_structure_preferences' => ['skill_first', 'mobility_finish'],
        ];
    }
}
