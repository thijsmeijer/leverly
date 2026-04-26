<?php

namespace Tests\Feature\API\V1\Onboarding;

use App\Models\AthleteOnboarding;
use App\Models\AthleteProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AthleteOnboardingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_onboarding(): void
    {
        $this->getJson('/api/v1/me/onboarding')
            ->assertUnauthorized();

        $this->patchJson('/api/v1/me/onboarding', $this->completePayload())
            ->assertUnauthorized();
    }

    public function test_athlete_can_complete_onboarding_and_sync_profile_inputs(): void
    {
        $user = User::factory()->create(['name' => 'Ada Account']);

        Sanctum::actingAs($user);

        $response = $this->patchJson('/api/v1/me/onboarding', $this->completePayload());

        $response
            ->assertOk()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.age_years', 29)
            ->assertJsonPath('data.training_age_months', 18)
            ->assertJsonPath('data.current_bodyweight_value', 72.5)
            ->assertJsonPath('data.height_value', 178)
            ->assertJsonPath('data.prior_sport_background.0', 'strength_training')
            ->assertJsonPath('data.primary_goal', 'skill')
            ->assertJsonPath('data.target_skills.0', 'strict_pull_up')
            ->assertJsonPath('data.primary_target_skill', 'handstand')
            ->assertJsonPath('data.secondary_target_skills.0', 'strict_pull_up')
            ->assertJsonPath('data.long_term_target_skills.0', 'planche')
            ->assertJsonPath('data.base_focus_areas.0', 'pull_capacity')
            ->assertJsonPath('data.roadmap_suggestions.version', 'roadmap.v2')
            ->assertJsonPath('data.roadmap_suggestions.level', 'intermediate')
            ->assertJsonPath('data.roadmap_suggestions.primary_goal.skill', 'handstand')
            ->assertJsonPath('data.roadmap_suggestions.compatible_secondary_goal.skill', 'strict_pull_up')
            ->assertJsonPath('data.roadmap_suggestions.foundation_lane.slug', 'foundation_strength')
            ->assertJsonPath('data.roadmap_suggestions.current_progression_node.id', 'handstand.current')
            ->assertJsonPath('data.roadmap_suggestions.next_node.id', 'handstand.next')
            ->assertJsonPath('data.roadmap_suggestions.next_milestone.id', 'handstand.milestone')
            ->assertJsonPath('data.roadmap_suggestions.eta_range.min_weeks', 8)
            ->assertJsonPath('data.roadmap_suggestions.eta_range.max_weeks', 16)
            ->assertJsonPath('data.roadmap_suggestions.confidence.level', 'medium')
            ->assertJsonPath('data.roadmap_suggestions.blockers.0.key', 'wrist_extension')
            ->assertJsonPath('data.roadmap_suggestions.unlock_conditions.0.skill', 'handstand')
            ->assertJsonPath('data.roadmap_suggestions.compatibility_tags.0', 'overhead')
            ->assertJsonPath('data.roadmap_suggestions.explanation.summary', 'Handstand is the clearest first roadmap priority from the current assessment.')
            ->assertJsonPath('data.roadmap_suggestions.intermediate.progression_graph_placement.primary.skill', 'handstand')
            ->assertJsonPath('data.roadmap_suggestions.intermediate.domain_scores.vertical_pull.score', 0.4)
            ->assertJsonPath('data.roadmap_suggestions.intermediate.hard_gate_results.0.key', 'pain')
            ->assertJsonPath('data.roadmap_suggestions.unlocked_tracks.0.skill', 'strict_push_up')
            ->assertJsonPath('data.current_level_tests.push_ups.max_strict_reps', 18)
            ->assertJsonPath('data.current_level_tests.pull_ups.max_strict_reps', 4)
            ->assertJsonPath('data.current_level_tests.dips.max_strict_reps', 6)
            ->assertJsonPath('data.current_level_tests.squat.barbell_load_value', 100)
            ->assertJsonPath('data.current_level_tests.squat.barbell_reps', 5)
            ->assertJsonPath('data.current_level_tests.hollow_hold_seconds', 35)
            ->assertJsonPath('data.skill_statuses.handstand.status', 'freestanding_kick_up')
            ->assertJsonPath('data.mobility_checks.wrist_extension', 'limited')
            ->assertJsonPath('data.weighted_baselines.experience', 'repetition_work')
            ->assertJsonPath('data.readiness_rating', 4)
            ->assertJsonPath('data.pain_level', 2)
            ->assertJsonPath('data.starter_plan_key', 'skill_strength_split')
            ->assertJsonPath('data.is_complete', true)
            ->assertJsonPath('data.missing_sections', []);

        $onboardingId = $response->json('data.id');

        $this->assertIsString($onboardingId);
        $this->assertTrue(Str::isUlid($onboardingId));
        $this->assertDatabaseHas('athlete_onboardings', [
            'id' => $onboardingId,
            'user_id' => $user->id,
            'primary_goal' => 'skill',
            'starter_plan_key' => 'skill_strength_split',
        ]);

        $profile = AthleteProfile::query()
            ->where('user_id', $user->id)
            ->sole();

        $this->assertSame('skill', $profile->primary_goal);
        $this->assertSame(29, $profile->age_years);
        $this->assertSame(18, $profile->training_age_months);
        $this->assertSame(72.5, $profile->current_bodyweight_value);
        $this->assertSame(178.0, $profile->height_value);
        $this->assertSame(['strength_training'], $profile->prior_sport_background);
        $this->assertSame(['strength'], $profile->secondary_goals);
        $this->assertSame(['strict_pull_up', 'handstand'], $profile->target_skills);
        $this->assertSame('handstand', $profile->primary_target_skill);
        $this->assertSame(['strict_pull_up'], $profile->secondary_target_skills);
        $this->assertSame(['planche'], $profile->long_term_target_skills);
        $this->assertSame(['pull_capacity', 'core_bodyline', 'handstand_line'], $profile->base_focus_areas);
        $this->assertSame('roadmap.v2', $profile->roadmap_suggestions['version']);
        $this->assertSame('intermediate', $profile->roadmap_suggestions['level']);
        $this->assertSame('handstand', $profile->roadmap_suggestions['primary_goal']['skill']);
        $this->assertSame(['pull_up_bar', 'rings', 'parallettes'], $profile->available_equipment);
        $this->assertSame(100, $profile->baseline_tests['squat']['barbell_load_value']);
        $this->assertSame('limited', $profile->mobility_checks['wrist_extension']);
        $this->assertSame('repetition_work', $profile->weighted_baselines['experience']);
        $this->assertSame(['monday', 'wednesday', 'friday'], $profile->preferred_training_days);
        $this->assertSame(60, $profile->preferred_session_minutes);
        $this->assertSame(3, $profile->weekly_session_goal);
        $this->assertSame('Wrists feel loaded after high-volume handstand work.', $profile->injury_notes);
        $this->assertSame('wrist', $profile->movement_limitations[0]['area']);
        $this->assertSame('mild', $profile->movement_limitations[0]['severity']);
    }

    public function test_athlete_can_read_and_update_onboarding_draft(): void
    {
        $user = User::factory()->create(['name' => 'Draft Athlete']);

        Sanctum::actingAs($user);

        $draftResponse = $this->getJson('/api/v1/me/onboarding');

        $draftResponse
            ->assertOk()
            ->assertJsonPath('data.user_id', $user->id)
            ->assertJsonPath('data.is_complete', false)
            ->assertJsonPath('data.current_level_tests.push_ups.max_strict_reps', null)
            ->assertJsonPath('data.missing_sections.0', 'age');

        $draftId = $draftResponse->json('data.id');

        $this->patchJson('/api/v1/me/onboarding', [
            'primary_goal' => 'strength',
            'age_years' => 31,
            'training_age_months' => 12,
            'current_bodyweight_value' => 80,
            'height_value' => 181,
            'prior_sport_background' => ['none'],
            'target_skills' => ['strict_dip'],
            'available_equipment' => ['dip_bars'],
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 24],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $draftId)
            ->assertJsonPath('data.primary_goal', 'strength')
            ->assertJsonPath('data.age_years', 31)
            ->assertJsonPath('data.target_skills.0', 'strict_dip')
            ->assertJsonPath('data.available_equipment.0', 'dip_bars')
            ->assertJsonPath('data.current_level_tests.push_ups.max_strict_reps', 24)
            ->assertJsonPath('data.current_level_tests.pull_ups.max_strict_reps', null)
            ->assertJsonPath('data.is_complete', false);

        $this->patchJson('/api/v1/me/onboarding', [
            'current_level_tests' => [
                'pull_ups' => ['max_strict_reps' => 2],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $draftId)
            ->assertJsonPath('data.current_level_tests.push_ups.max_strict_reps', 24)
            ->assertJsonPath('data.current_level_tests.pull_ups.max_strict_reps', 2);

        $this->assertDatabaseCount('athlete_onboardings', 1);
        $this->assertDatabaseHas('athlete_profiles', [
            'user_id' => $user->id,
            'primary_goal' => 'strength',
        ]);
    }

    public function test_completion_requires_plan_ready_answers(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/v1/me/onboarding', [
            'complete' => true,
            'primary_goal' => 'skill',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['complete'])
            ->assertJsonPath('errors.complete.0', 'Onboarding cannot be completed until age is provided.');
    }

    public function test_active_targets_must_match_generated_current_or_bridge_tracks(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $payload = $this->completePayload([
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 0],
                'pull_ups' => ['max_strict_reps' => 0],
                'dips' => ['max_strict_reps' => 0],
                'squat' => ['barbell_load_value' => 0, 'barbell_reps' => 0],
                'hollow_hold_seconds' => 8,
            ],
            'target_skills' => ['planche'],
            'primary_target_skill' => 'planche',
            'secondary_target_skills' => [],
            'long_term_target_skills' => ['strict_pull_up'],
            'base_focus_areas' => ['push_capacity', 'core_bodyline'],
        ]);

        $this->patchJson('/api/v1/me/onboarding', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['target_skills.0', 'primary_target_skill']);
    }

    public function test_onboarding_validation_rejects_malformed_algorithm_inputs(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->patchJson('/api/v1/me/onboarding', [
            'primary_goal' => 'random',
            'age_years' => 5,
            'training_age_months' => -1,
            'experience_level' => 'superhuman',
            'current_bodyweight_value' => 3,
            'bodyweight_unit' => 'stone',
            'height_value' => 12,
            'height_unit' => 'hands',
            'prior_sport_background' => ['space_walking'],
            'secondary_goals' => ['conditioning', 'mobility', 'strength'],
            'target_skills' => ['generic fitness'],
            'primary_target_skill' => 'generic fitness',
            'secondary_target_skills' => ['handstand'],
            'long_term_target_skills' => ['generic fitness'],
            'base_focus_areas' => ['random'],
            'available_equipment' => ['machine'],
            'training_locations' => ['moon'],
            'preferred_training_days' => ['funday'],
            'preferred_session_minutes' => 5,
            'weekly_session_goal' => 15,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => -1],
                'pull_ups' => ['max_strict_reps' => -1],
                'dips' => ['max_strict_reps' => -1],
                'squat' => ['barbell_load_value' => -1, 'barbell_reps' => 31],
                'hollow_hold_seconds' => 700,
            ],
            'skill_statuses' => [
                'handstand' => ['status' => 'vibes'],
            ],
            'mobility_checks' => ['wrist_extension' => 'random'],
            'weighted_baselines' => [
                'experience' => 'reckless',
                'unit' => 'stone',
                'movements' => [
                    ['movement' => 'back_squat', 'external_load_value' => -1, 'reps' => 100, 'rir' => 99],
                ],
            ],
            'readiness_rating' => 0,
            'sleep_quality' => 6,
            'soreness_level' => 0,
            'pain_level' => 11,
            'pain_areas' => ['ego'],
            'pain_notes' => str_repeat('x', 1001),
            'starter_plan_key' => 'whatever',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'primary_goal',
                'age_years',
                'training_age_months',
                'experience_level',
                'current_bodyweight_value',
                'bodyweight_unit',
                'height_value',
                'height_unit',
                'prior_sport_background.0',
                'secondary_goals',
                'target_skills.0',
                'primary_target_skill',
                'secondary_target_skills.0',
                'long_term_target_skills.0',
                'base_focus_areas.0',
                'available_equipment.0',
                'training_locations.0',
                'preferred_training_days.0',
                'preferred_session_minutes',
                'weekly_session_goal',
                'current_level_tests.push_ups.max_strict_reps',
                'current_level_tests.pull_ups.max_strict_reps',
                'current_level_tests.dips.max_strict_reps',
                'current_level_tests.squat.barbell_load_value',
                'current_level_tests.squat.barbell_reps',
                'current_level_tests.hollow_hold_seconds',
                'skill_statuses.handstand.status',
                'mobility_checks.wrist_extension',
                'weighted_baselines.experience',
                'weighted_baselines.unit',
                'weighted_baselines.movements.0.movement',
                'weighted_baselines.movements.0.external_load_value',
                'weighted_baselines.movements.0.reps',
                'weighted_baselines.movements.0.rir',
                'readiness_rating',
                'sleep_quality',
                'soreness_level',
                'pain_level',
                'pain_areas.0',
                'pain_notes',
                'starter_plan_key',
            ]);
    }

    public function test_onboarding_data_is_scoped_to_the_signed_in_user(): void
    {
        $signedInUser = User::factory()->create();
        $otherOnboarding = AthleteOnboarding::factory()->create([
            'primary_goal' => 'endurance',
        ]);

        Sanctum::actingAs($signedInUser);

        $this->patchJson('/api/v1/me/onboarding', [
            'primary_goal' => 'strength',
            'target_skills' => ['strict_pull_up'],
        ])
            ->assertOk()
            ->assertJsonPath('data.user_id', $signedInUser->id)
            ->assertJsonPath('data.primary_goal', 'strength')
            ->assertJsonMissing(['id' => $otherOnboarding->id])
            ->assertJsonMissing(['primary_goal' => 'endurance']);
    }

    /**
     * @return array<string, mixed>
     */
    private function completePayload(array $overrides = []): array
    {
        return [
            'complete' => true,
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
            'long_term_target_skills' => ['planche'],
            'base_focus_areas' => ['pull_capacity', 'core_bodyline', 'handstand_line'],
            'available_equipment' => ['pull_up_bar', 'rings', 'parallettes'],
            'training_locations' => ['home'],
            'preferred_training_days' => ['monday', 'wednesday', 'friday'],
            'preferred_session_minutes' => 60,
            'weekly_session_goal' => 3,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 18],
                'pull_ups' => ['max_strict_reps' => 4],
                'dips' => ['max_strict_reps' => 6],
                'squat' => ['barbell_load_value' => 100, 'barbell_reps' => 5],
                'hollow_hold_seconds' => 35,
            ],
            'skill_statuses' => [
                'handstand' => ['status' => 'freestanding_kick_up', 'best_hold_seconds' => 20],
                'l_sit' => ['status' => 'full_l_sit', 'best_hold_seconds' => 8],
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
            'readiness_rating' => 4,
            'sleep_quality' => 4,
            'soreness_level' => 2,
            'pain_level' => 2,
            'pain_areas' => ['wrist'],
            'pain_notes' => 'Wrists feel loaded after high-volume handstand work.',
            'starter_plan_key' => 'skill_strength_split',
            ...$overrides,
        ];
    }
}
