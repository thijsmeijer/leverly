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
            'training_age_months' => 18,
            'experience_level' => 'intermediate',
            'current_bodyweight_value' => 72.5,
            'bodyweight_unit' => 'kg',
            'primary_goal' => 'skill',
            'secondary_goals' => ['strength', 'mobility'],
            'target_skills' => ['freestanding handstand', 'strict muscle-up'],
            'available_equipment' => ['floor', 'pull_up_bar', 'rings'],
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
            'training_age_months' => 18,
            'experience_level' => 'intermediate',
            'current_bodyweight_value' => 72.5,
            'bodyweight_unit' => 'kg',
            'primary_goal' => 'skill',
            'secondary_goals' => ['strength', 'mobility'],
            'target_skills' => ['freestanding handstand', 'strict muscle-up'],
            'available_equipment' => ['floor', 'pull_up_bar', 'rings'],
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
