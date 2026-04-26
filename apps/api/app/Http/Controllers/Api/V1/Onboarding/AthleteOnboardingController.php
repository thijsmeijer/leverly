<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Onboarding;

use App\Domain\Onboarding\Actions\UpsertAthleteOnboardingAction;
use App\Domain\Onboarding\Support\AthleteOnboardingOptions;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Onboarding\UpsertAthleteOnboardingRequest;
use App\Http\Resources\Api\V1\AthleteOnboardingResource;
use App\Models\AthleteOnboarding;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Knuckles\Scribe\Attributes\Authenticated;
use Knuckles\Scribe\Attributes\Endpoint;
use Knuckles\Scribe\Attributes\Group;
use Knuckles\Scribe\Attributes\Response;
use Knuckles\Scribe\Attributes\ResponseField;

class AthleteOnboardingController extends Controller
{
    #[Group('Onboarding')]
    #[Endpoint('Get onboarding state', 'Returns the signed-in athlete onboarding draft or completed onboarding answers.')]
    #[Authenticated]
    #[Response([
        'data' => [
            'id' => '01kb0b6h4az3er8g7vnh9k5m1a',
            'user_id' => '01kaw4k7q6v7m9r6rddm4xyf2p',
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
            'base_focus_areas' => ['pull_capacity', 'core_bodyline'],
            'roadmap_suggestions' => ['level' => 'intermediate', 'summary' => 'Ready for focused skill work.'],
            'available_equipment' => ['pull_up_bar', 'rings'],
            'training_locations' => ['home'],
            'preferred_training_days' => ['monday', 'wednesday', 'friday'],
            'preferred_session_minutes' => 60,
            'weekly_session_goal' => 3,
            'preferred_training_time' => 'evening',
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 18],
                'pull_ups' => ['max_strict_reps' => 4],
                'dips' => ['max_strict_reps' => 6],
                'squat' => ['barbell_load_value' => 100, 'barbell_reps' => 5],
                'hollow_hold_seconds' => 35,
            ],
            'skill_statuses' => [
                'handstand' => ['status' => 'freestanding_kick_up', 'best_hold_seconds' => 20],
            ],
            'mobility_checks' => ['wrist_extension' => 'limited', 'shoulder_flexion' => 'clear'],
            'weighted_baselines' => ['experience' => 'repetition_work', 'unit' => 'kg', 'movements' => []],
            'readiness_rating' => 4,
            'sleep_quality' => 4,
            'soreness_level' => 2,
            'pain_level' => 1,
            'pain_areas' => [],
            'pain_notes' => null,
            'starter_plan_key' => 'skill_strength_split',
            'is_complete' => false,
            'completed_at' => null,
            'missing_sections' => [],
        ],
    ])]
    #[ResponseField('data', 'object', 'Athlete onboarding answers.', required: true)]
    #[ResponseField('data.current_level_tests', 'object', 'Baseline push-up, pull-up, squat, and hollow hold tests.', required: true)]
    #[ResponseField('data.missing_sections', 'string[]', 'Sections still needed before onboarding can be completed.', required: true)]
    public function show(Request $request): JsonResponse
    {
        $onboarding = AthleteOnboarding::query()
            ->firstOrCreate(
                ['user_id' => $request->user()->getKey()],
                AthleteOnboardingOptions::defaultsFor($request->user()),
            );

        return AthleteOnboardingResource::make($onboarding)
            ->response()
            ->setStatusCode(200);
    }

    #[Group('Onboarding')]
    #[Endpoint('Update onboarding state', 'Creates, updates, resumes, or completes signed-in athlete onboarding answers.')]
    #[Authenticated]
    #[Response([
        'data' => [
            'id' => '01kb0b6h4az3er8g7vnh9k5m1a',
            'user_id' => '01kaw4k7q6v7m9r6rddm4xyf2p',
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
            'base_focus_areas' => ['pull_capacity', 'core_bodyline'],
            'roadmap_suggestions' => ['level' => 'intermediate', 'summary' => 'Ready for focused skill work.'],
            'available_equipment' => ['pull_up_bar', 'rings'],
            'training_locations' => ['home'],
            'preferred_training_days' => ['monday', 'wednesday', 'friday'],
            'preferred_session_minutes' => 60,
            'weekly_session_goal' => 3,
            'preferred_training_time' => 'evening',
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 18],
                'pull_ups' => ['max_strict_reps' => 4],
                'dips' => ['max_strict_reps' => 6],
                'squat' => ['barbell_load_value' => 100, 'barbell_reps' => 5],
                'hollow_hold_seconds' => 35,
            ],
            'skill_statuses' => [
                'handstand' => ['status' => 'freestanding_kick_up', 'best_hold_seconds' => 20],
            ],
            'mobility_checks' => ['wrist_extension' => 'limited', 'shoulder_flexion' => 'clear'],
            'weighted_baselines' => ['experience' => 'repetition_work', 'unit' => 'kg', 'movements' => []],
            'readiness_rating' => 4,
            'sleep_quality' => 4,
            'soreness_level' => 2,
            'pain_level' => 1,
            'pain_areas' => [],
            'pain_notes' => null,
            'starter_plan_key' => 'skill_strength_split',
            'is_complete' => true,
            'completed_at' => '2026-04-26T00:00:00.000000Z',
            'missing_sections' => [],
        ],
    ])]
    public function update(
        UpsertAthleteOnboardingRequest $request,
        UpsertAthleteOnboardingAction $upsertAthleteOnboarding,
    ): JsonResponse {
        $onboarding = $upsertAthleteOnboarding->execute(
            $request->user(),
            $request->onboardingData(),
            $request->shouldComplete(),
        );

        return AthleteOnboardingResource::make($onboarding)
            ->response()
            ->setStatusCode(200);
    }
}
