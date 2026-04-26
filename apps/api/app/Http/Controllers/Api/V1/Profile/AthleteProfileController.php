<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Profile;

use App\Domain\Profile\Actions\UpsertAthleteProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Profile\UpsertAthleteProfileRequest;
use App\Http\Resources\Api\V1\AthleteProfileResource;
use App\Models\AthleteProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseField;

class AthleteProfileController extends Controller
{
    #[Group('Profile')]
    #[Endpoint('Get athlete profile', 'Returns the signed-in athlete profile and recommendation input settings.')]
    #[Authenticated]
    #[Response([
        'data' => [
            'id' => '01kb0b6h4az3er8g7vnh9k5m1a',
            'user_id' => '01kaw4k7q6v7m9r6rddm4xyf2p',
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
            'prior_sport_background' => ['strength_training'],
            'primary_goal' => 'skill',
            'secondary_goals' => ['strength', 'mobility'],
            'target_skills' => ['handstand', 'strict_pull_up'],
            'primary_target_skill' => 'handstand',
            'secondary_target_skills' => ['strict_pull_up'],
            'long_term_target_skills' => ['planche'],
            'base_focus_areas' => ['pull_capacity', 'core_bodyline'],
            'roadmap_suggestions' => ['level' => 'intermediate', 'summary' => 'Ready for focused skill work.'],
            'available_equipment' => ['pull_up_bar', 'rings', 'parallettes'],
            'training_locations' => ['home', 'park'],
            'movement_limitations' => [
                [
                    'area' => 'wrist',
                    'severity' => 'mild',
                    'status' => 'recurring',
                    'notes' => 'Needs longer warm-up.',
                ],
            ],
            'baseline_tests' => [
                'push_ups' => ['max_strict_reps' => 18],
                'pull_ups' => ['max_strict_reps' => 4],
                'dips' => ['max_strict_reps' => 6],
                'squat' => ['barbell_load_value' => 100, 'barbell_reps' => 5],
                'hollow_hold_seconds' => 35,
            ],
            'skill_statuses' => ['handstand' => ['status' => 'freestanding_kick_up', 'best_hold_seconds' => 20]],
            'mobility_checks' => ['wrist_extension' => 'limited', 'shoulder_flexion' => 'clear'],
            'weighted_baselines' => ['experience' => 'repetition_work', 'unit' => 'kg', 'movements' => []],
            'injury_notes' => 'Left wrist can get irritated under high volume.',
            'preferred_training_days' => ['monday', 'wednesday', 'friday'],
            'preferred_session_minutes' => 60,
            'weekly_session_goal' => 4,
            'progression_pace' => 'balanced',
            'intensity_preference' => 'auto',
            'effort_tracking_preference' => 'rir',
            'deload_preference' => 'auto',
            'session_structure_preferences' => ['skill_first', 'mobility_finish'],
        ],
    ])]
    #[ResponseField('data', 'object', 'Athlete profile settings.', required: true)]
    #[ResponseField('data.id', 'string', 'Stable profile identifier.', required: true, example: '01kb0b6h4az3er8g7vnh9k5m1a')]
    #[ResponseField('data.user_id', 'string', 'Owner user identifier.', required: true, example: '01kaw4k7q6v7m9r6rddm4xyf2p')]
    #[ResponseField('data.display_name', 'string', 'Profile display name.', required: true, example: 'Ada Athlete')]
    public function show(Request $request): JsonResponse
    {
        $profile = AthleteProfile::query()
            ->ownedBy($request->user())
            ->first();

        abort_if($profile === null, 404, 'Athlete profile has not been created.');

        return AthleteProfileResource::make($profile)
            ->response()
            ->setStatusCode(200);
    }

    #[Group('Profile')]
    #[Endpoint('Update athlete profile', 'Creates or updates the signed-in athlete profile and recommendation input settings.')]
    #[Authenticated]
    #[Response([
        'data' => [
            'id' => '01kb0b6h4az3er8g7vnh9k5m1a',
            'user_id' => '01kaw4k7q6v7m9r6rddm4xyf2p',
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
            'prior_sport_background' => ['strength_training'],
            'primary_goal' => 'skill',
            'secondary_goals' => ['strength', 'mobility'],
            'target_skills' => ['handstand', 'strict_pull_up'],
            'primary_target_skill' => 'handstand',
            'secondary_target_skills' => ['strict_pull_up'],
            'long_term_target_skills' => ['planche'],
            'base_focus_areas' => ['pull_capacity', 'core_bodyline'],
            'roadmap_suggestions' => ['level' => 'intermediate', 'summary' => 'Ready for focused skill work.'],
            'available_equipment' => ['pull_up_bar', 'rings', 'parallettes'],
            'training_locations' => ['home', 'park'],
            'movement_limitations' => [
                [
                    'area' => 'wrist',
                    'severity' => 'mild',
                    'status' => 'recurring',
                    'notes' => 'Needs longer warm-up.',
                ],
            ],
            'baseline_tests' => [
                'push_ups' => ['max_strict_reps' => 18],
                'pull_ups' => ['max_strict_reps' => 4],
                'dips' => ['max_strict_reps' => 6],
                'squat' => ['barbell_load_value' => 100, 'barbell_reps' => 5],
                'hollow_hold_seconds' => 35,
            ],
            'skill_statuses' => ['handstand' => ['status' => 'freestanding_kick_up', 'best_hold_seconds' => 20]],
            'mobility_checks' => ['wrist_extension' => 'limited', 'shoulder_flexion' => 'clear'],
            'weighted_baselines' => ['experience' => 'repetition_work', 'unit' => 'kg', 'movements' => []],
            'injury_notes' => 'Left wrist can get irritated under high volume.',
            'preferred_training_days' => ['monday', 'wednesday', 'friday'],
            'preferred_session_minutes' => 60,
            'weekly_session_goal' => 4,
            'progression_pace' => 'balanced',
            'intensity_preference' => 'auto',
            'effort_tracking_preference' => 'rir',
            'deload_preference' => 'auto',
            'session_structure_preferences' => ['skill_first', 'mobility_finish'],
        ],
    ])]
    public function update(
        UpsertAthleteProfileRequest $request,
        UpsertAthleteProfileAction $upsertAthleteProfile,
    ): JsonResponse {
        $profile = $upsertAthleteProfile->execute($request->user(), $request->profileData());

        return AthleteProfileResource::make($profile)
            ->response()
            ->setStatusCode(200);
    }
}
