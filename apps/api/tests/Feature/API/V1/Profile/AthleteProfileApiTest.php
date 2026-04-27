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
            ->assertJsonPath('data.age_years', 29)
            ->assertJsonPath('data.training_age_months', 18)
            ->assertJsonPath('data.current_bodyweight_value', 72.5)
            ->assertJsonPath('data.height_value', 178)
            ->assertJsonPath('data.weight_trend', 'maintaining')
            ->assertJsonPath('data.prior_sport_background.0', 'strength_training')
            ->assertJsonPath('data.primary_goal', 'skill')
            ->assertJsonPath('data.secondary_goals.0', 'strength')
            ->assertJsonPath('data.target_skills.0', 'handstand')
            ->assertJsonPath('data.primary_target_skill', 'handstand')
            ->assertJsonPath('data.long_term_target_skills.0', 'planche')
            ->assertJsonPath('data.base_focus_areas.0', 'pull_capacity')
            ->assertJsonPath('data.required_goal_modules.0', 'inversion')
            ->assertJsonPath('data.goal_modules.inversion.highest_progression', 'freestanding_kick_up')
            ->assertJsonPath('data.goal_modules.inversion.metric_type', 'hold_seconds')
            ->assertJsonPath('data.goal_modules.inversion.hold_seconds', 20)
            ->assertJsonPath('data.roadmap_suggestions.version', 'roadmap.v2')
            ->assertJsonPath('data.roadmap_suggestions.level', 'intermediate')
            ->assertJsonPath('data.roadmap_suggestions.primary_goal.skill', 'handstand')
            ->assertJsonPath('data.roadmap_suggestions.compatible_secondary_goal.skill', 'strict_pull_up')
            ->assertJsonPath('data.roadmap_suggestions.deferred_goals.0.skill', 'one_arm_pull_up')
            ->assertJsonPath('data.roadmap_suggestions.eta_range.label', '8-16 weeks')
            ->assertJsonPath('data.roadmap_suggestions.confidence.level', 'medium')
            ->assertJsonPath('data.roadmap_suggestions.intermediate.compatibility_costs.0.skill', 'strict_pull_up')
            ->assertJsonPath('data.available_equipment.3', 'rings')
            ->assertJsonPath('data.movement_limitations.0.area', 'wrist')
            ->assertJsonPath('data.pain_flags.wrist.severity', 'mild')
            ->assertJsonPath('data.pain_flags.wrist.status', 'recurring')
            ->assertJsonPath('data.pain_flags.elbow.severity', 'none')
            ->assertJsonPath('data.baseline_tests.squat.barbell_load_value', 100)
            ->assertJsonPath('data.baseline_tests.rows.variant', 'ring_row')
            ->assertJsonPath('data.baseline_tests.rows.max_reps', 12)
            ->assertJsonPath('data.baseline_tests.pull_ups.fallback_variant', 'eccentric')
            ->assertJsonPath('data.baseline_tests.pull_ups.fallback_seconds', 6)
            ->assertJsonPath('data.baseline_tests.dips.fallback_variant', 'assisted')
            ->assertJsonPath('data.baseline_tests.dips.fallback_reps', 5)
            ->assertJsonPath('data.baseline_tests.lower_body.variant', 'split_squat')
            ->assertJsonPath('data.baseline_tests.lower_body.reps', 12)
            ->assertJsonPath('data.baseline_tests.passive_hang_seconds', 45)
            ->assertJsonPath('data.baseline_tests.top_support_hold_seconds', 25)
            ->assertJsonPath('data.mobility_checks.wrist_extension', 'limited')
            ->assertJsonPath('data.weighted_baselines.experience', 'repetition_work')
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

        $profile = AthleteProfile::query()->whereKey($profileId)->sole();

        $this->assertSame('maintaining', $profile->weight_trend);
        $this->assertSame('recurring', $profile->pain_flags['wrist']['status']);
        $this->assertSame('freestanding_kick_up', $profile->goal_modules['inversion']['highest_progression']);
        $this->assertSame(20, $profile->goal_modules['inversion']['hold_seconds']);
        $this->assertSame('ring_row', $profile->baseline_tests['rows']['variant']);
        $this->assertSame(45, $profile->baseline_tests['passive_hang_seconds']);
        $this->assertSame(25, $profile->baseline_tests['top_support_hold_seconds']);
        $this->assertSame('split_squat', $profile->baseline_tests['lower_body']['variant']);

        $this->getJson('/api/v1/me/profile')
            ->assertOk()
            ->assertJsonPath('data.id', $profileId)
            ->assertJsonPath('data.secondary_target_skills.0', 'strict_pull_up');

        $this->patchJson('/api/v1/me/profile', [
            'display_name' => 'Ada Bars',
            'preferred_session_minutes' => 45,
            'secondary_goals' => ['strength', 'mobility'],
            'available_equipment' => ['dip_bars', 'rings', 'box_bench'],
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $profileId)
            ->assertJsonPath('data.display_name', 'Ada Bars')
            ->assertJsonPath('data.timezone', 'Europe/Amsterdam')
            ->assertJsonPath('data.preferred_session_minutes', 45)
            ->assertJsonPath('data.secondary_goals.1', 'mobility')
            ->assertJsonPath('data.available_equipment.2', 'box_bench');
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
            ->assertJsonPath('data.height_unit', 'cm')
            ->assertJsonPath('data.experience_level', 'new')
            ->assertJsonPath('data.weight_trend', 'unknown')
            ->assertJsonPath('data.pain_flags.wrist.severity', 'none')
            ->assertJsonPath('data.pain_flags.ankle.status', 'none')
            ->assertJsonPath('data.prior_sport_background', [])
            ->assertJsonPath('data.available_equipment', [])
            ->assertJsonPath('data.base_focus_areas', [])
            ->assertJsonPath('data.long_term_target_skills', [])
            ->assertJsonPath('data.baseline_tests.rows.max_reps', null)
            ->assertJsonPath('data.baseline_tests.passive_hang_seconds', null)
            ->assertJsonPath('data.baseline_tests.top_support_hold_seconds', null)
            ->assertJsonPath('data.baseline_tests.lower_body.variant', 'bodyweight_squat')
            ->assertJsonPath('data.mobility_checks.wrist_extension', 'not_tested')
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
            'age_years' => 5,
            'training_age_months' => -1,
            'experience_level' => 'expertish',
            'current_bodyweight_value' => 3,
            'bodyweight_unit' => 'stone',
            'height_value' => 12,
            'height_unit' => 'hands',
            'weight_trend' => 'crashing',
            'prior_sport_background' => ['space_walking'],
            'primary_goal' => 'unsupported_goal',
            'secondary_goals' => ['strength', 'unsupported_goal', 'mobility'],
            'target_skills' => ['a', 'strict_pull_up'],
            'primary_target_skill' => 'handstand',
            'secondary_target_skills' => ['handstand'],
            'long_term_target_skills' => ['a'],
            'base_focus_areas' => ['random'],
            'goal_modules' => [
                'pull_skill' => [
                    'highest_progression' => 'made_up',
                    'metric_type' => 'reps',
                    'reps' => -1,
                    'hold_seconds' => 700,
                    'load_value' => -1,
                    'load_unit' => 'stone',
                    'quality' => 'messy',
                    'notes' => str_repeat('x', 301),
                ],
            ],
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
            'pain_flags' => [
                'wrist' => [
                    'severity' => 'catastrophic',
                    'status' => 'forever',
                    'notes' => str_repeat('x', 301),
                ],
                'ego' => [
                    'severity' => 'mild',
                    'status' => 'active',
                ],
            ],
            'baseline_tests' => [
                'push_ups' => ['max_strict_reps' => -1],
                'pull_ups' => [
                    'max_strict_reps' => -1,
                    'fallback_variant' => 'flying',
                    'fallback_reps' => -1,
                    'fallback_seconds' => -1,
                ],
                'dips' => [
                    'max_strict_reps' => -1,
                    'fallback_variant' => 'bench',
                    'fallback_reps' => -1,
                    'fallback_seconds' => -1,
                ],
                'squat' => ['barbell_load_value' => -1, 'barbell_reps' => 31],
                'rows' => ['variant' => 'machine_row', 'max_reps' => -1],
                'lower_body' => ['variant' => 'leg_press', 'load_value' => -1, 'reps' => 101, 'load_unit' => 'stone'],
                'hollow_hold_seconds' => 700,
                'passive_hang_seconds' => 700,
                'top_support_hold_seconds' => 700,
            ],
            'mobility_checks' => ['wrist_extension' => 'random'],
            'weighted_baselines' => [
                'experience' => 'reckless',
                'unit' => 'stone',
                'movements' => [
                    ['movement' => 'back_squat', 'external_load_value' => -1, 'reps' => 100, 'rir' => 99],
                ],
            ],
            'preferred_training_days' => ['funday'],
            'preferred_session_minutes' => 5,
            'weekly_session_goal' => 15,
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
                'age_years',
                'training_age_months',
                'experience_level',
                'current_bodyweight_value',
                'bodyweight_unit',
                'height_value',
                'height_unit',
                'weight_trend',
                'prior_sport_background.0',
                'primary_goal',
                'secondary_goals.1',
                'secondary_goals',
                'target_skills.0',
                'target_skills',
                'primary_target_skill',
                'secondary_target_skills.0',
                'long_term_target_skills.0',
                'base_focus_areas.0',
                'goal_modules.pull_skill',
                'goal_modules.pull_skill.highest_progression',
                'goal_modules.pull_skill.reps',
                'goal_modules.pull_skill.hold_seconds',
                'goal_modules.pull_skill.load_value',
                'goal_modules.pull_skill.load_unit',
                'goal_modules.pull_skill.quality',
                'goal_modules.pull_skill.notes',
                'available_equipment.0',
                'training_locations.0',
                'movement_limitations.0.area',
                'movement_limitations.0.severity',
                'movement_limitations.0.status',
                'movement_limitations.0.notes',
                'pain_flags',
                'pain_flags.wrist.severity',
                'pain_flags.wrist.status',
                'pain_flags.wrist.notes',
                'baseline_tests.push_ups.max_strict_reps',
                'baseline_tests.pull_ups.max_strict_reps',
                'baseline_tests.pull_ups.fallback_variant',
                'baseline_tests.pull_ups.fallback_reps',
                'baseline_tests.pull_ups.fallback_seconds',
                'baseline_tests.dips.max_strict_reps',
                'baseline_tests.dips.fallback_variant',
                'baseline_tests.dips.fallback_reps',
                'baseline_tests.dips.fallback_seconds',
                'baseline_tests.squat.barbell_load_value',
                'baseline_tests.squat.barbell_reps',
                'baseline_tests.rows.variant',
                'baseline_tests.rows.max_reps',
                'baseline_tests.lower_body.variant',
                'baseline_tests.lower_body.load_value',
                'baseline_tests.lower_body.reps',
                'baseline_tests.lower_body.load_unit',
                'baseline_tests.hollow_hold_seconds',
                'baseline_tests.passive_hang_seconds',
                'baseline_tests.top_support_hold_seconds',
                'mobility_checks.wrist_extension',
                'weighted_baselines.experience',
                'weighted_baselines.unit',
                'weighted_baselines.movements.0.movement',
                'weighted_baselines.movements.0.external_load_value',
                'weighted_baselines.movements.0.reps',
                'weighted_baselines.movements.0.rir',
                'preferred_training_days.0',
                'preferred_session_minutes',
                'weekly_session_goal',
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
            'age_years' => 29,
            'training_age_months' => 18,
            'experience_level' => 'intermediate',
            'current_bodyweight_value' => 72.5,
            'bodyweight_unit' => 'kg',
            'height_value' => 178,
            'height_unit' => 'cm',
            'weight_trend' => 'maintaining',
            'prior_sport_background' => ['strength_training'],
            'primary_goal' => 'skill',
            'secondary_goals' => ['strength', 'mobility'],
            'target_skills' => ['handstand'],
            'primary_target_skill' => 'handstand',
            'secondary_target_skills' => ['strict_pull_up'],
            'long_term_target_skills' => ['planche'],
            'base_focus_areas' => ['pull_capacity', 'core_bodyline', 'handstand_line'],
            'goal_modules' => [
                'inversion' => [
                    'highest_progression' => 'freestanding_kick_up',
                    'metric_type' => 'hold_seconds',
                    'reps' => null,
                    'hold_seconds' => 20,
                    'load_value' => null,
                    'load_unit' => 'kg',
                    'quality' => 'solid',
                    'notes' => null,
                ],
            ],
            'available_equipment' => ['pull_up_bar', 'low_bar', 'dip_bars', 'rings', 'parallettes', 'resistance_band'],
            'training_locations' => ['home', 'park'],
            'movement_limitations' => [
                [
                    'area' => 'wrist',
                    'severity' => 'mild',
                    'status' => 'recurring',
                    'notes' => 'Needs longer warm-up.',
                ],
            ],
            'pain_flags' => [
                'wrist' => [
                    'severity' => 'mild',
                    'status' => 'recurring',
                    'notes' => 'Needs longer warm-up.',
                ],
                'elbow' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
                'shoulder' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
                'low_back' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
                'knee' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
                'ankle' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
            ],
            'baseline_tests' => [
                'push_ups' => ['max_strict_reps' => 18],
                'pull_ups' => [
                    'max_strict_reps' => 4,
                    'fallback_variant' => 'eccentric',
                    'fallback_reps' => null,
                    'fallback_seconds' => 6,
                ],
                'dips' => [
                    'max_strict_reps' => 6,
                    'fallback_variant' => 'assisted',
                    'fallback_reps' => 5,
                    'fallback_seconds' => null,
                ],
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
                'experience' => 'repetition_work',
                'unit' => 'kg',
                'movements' => [
                    ['movement' => 'weighted_pull_up', 'external_load_value' => 10, 'reps' => 5, 'rir' => 2],
                ],
            ],
            'injury_notes' => 'Left wrist can get irritated under high volume.',
            'preferred_training_days' => ['monday', 'wednesday', 'friday'],
            'preferred_session_minutes' => 60,
            'weekly_session_goal' => 4,
            'progression_pace' => 'balanced',
            'intensity_preference' => 'auto',
            'effort_tracking_preference' => 'rir',
            'deload_preference' => 'auto',
            'session_structure_preferences' => ['skill_first', 'mobility_finish'],
        ];
    }
}
