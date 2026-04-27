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
    private const array ROADMAP_EXAMPLE = [
        'version' => 'roadmap.v2',
        'level' => 'intermediate',
        'summary' => 'You have enough base strength for a focused skill roadmap plus one light secondary exposure.',
        'primary_goal' => [
            'skill' => 'handstand',
            'label' => 'Handstand',
            'lane' => 'primary',
        ],
        'compatible_secondary_goal' => [
            'skill' => 'strict_pull_up',
            'label' => 'Pull-up',
            'lane' => 'secondary',
        ],
        'foundation_lane' => [
            'slug' => 'foundation_strength',
            'label' => 'Foundation strength',
            'focus_areas' => ['pull_capacity', 'core_bodyline'],
        ],
        'deferred_goals' => [
            ['skill' => 'planche', 'label' => 'Planche', 'lane' => 'deferred'],
        ],
        'current_progression_node' => ['id' => 'handstand.current', 'label' => 'Handstand placement'],
        'next_node' => ['id' => 'handstand.next', 'label' => 'Build a clean line and controlled balance entries.'],
        'next_milestone' => ['id' => 'handstand.milestone', 'label' => 'Build a clean line and controlled balance entries.'],
        'eta_range' => ['min_weeks' => 8, 'max_weeks' => 16, 'label' => '8-16 weeks'],
        'confidence' => ['level' => 'medium', 'score' => 0.72, 'reasons' => ['Baseline tests are complete enough for a first block.']],
        'blockers' => [],
        'unlock_conditions' => [['skill' => 'handstand', 'label' => 'Build a clean line and controlled balance entries.', 'status' => 'next']],
        'compatibility_tags' => ['overhead', 'wrist_extension', 'skill_practice'],
        'explanation' => [
            'summary' => 'Handstand is the clearest first roadmap priority from the current assessment.',
            'why_this_goal' => ['Your pressing, bodyline, shoulder, and wrist signals are ready for regular handstand practice.'],
            'watch_out_for' => [],
            'fallback' => 'If readiness drops, keep the same target and use the previous easier progression.',
        ],
        'intermediate' => [
            'progression_graph_placement' => ['primary' => ['skill' => 'handstand', 'node' => 'handstand.current', 'completion' => 0.58]],
            'domain_scores' => ['vertical_pull' => ['label' => 'Vertical pull', 'score' => 0.4, 'inputs' => ['pull_ups']]],
            'domain_uncertainty' => ['vertical_pull' => ['score' => 0, 'missing_inputs' => []]],
            'hard_gate_results' => [['key' => 'pain', 'passed' => true, 'severity' => 'watch', 'message' => 'Pain is low enough for normal conservative placement.']],
            'readiness_scores' => [['skill' => 'handstand', 'score' => 0.58, 'reasons' => ['Handstand placement is supported.']]],
            'compatibility_costs' => [['skill' => 'strict_pull_up', 'cost' => 0.12, 'reasons' => ['Overlap is acceptable for a secondary exposure.']]],
            'eta_modifiers' => [['key' => 'training_age', 'multiplier' => 1, 'reason' => 'Training history supports a normal ramp.']],
        ],
        'body_context' => ['notes' => []],
        'base_focus_areas' => ['pull_capacity', 'core_bodyline'],
        'unlocked_tracks' => [],
        'bridge_tracks' => [],
        'long_term_tracks' => [],
        'deferred_tracks' => [],
    ];

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
            'weight_trend' => 'maintaining',
            'prior_sport_background' => ['strength_training'],
            'primary_goal' => 'skill',
            'secondary_goals' => ['strength'],
            'target_skills' => ['handstand'],
            'primary_target_skill' => 'handstand',
            'secondary_target_skills' => ['strict_pull_up'],
            'long_term_target_skills' => ['planche'],
            'base_focus_areas' => ['pull_capacity', 'core_bodyline'],
            'required_goal_modules' => ['inversion'],
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
            'roadmap_suggestions' => self::ROADMAP_EXAMPLE,
            'available_equipment' => ['pull_up_bar', 'rings'],
            'training_locations' => ['home'],
            'preferred_training_days' => ['monday', 'wednesday', 'friday'],
            'preferred_session_minutes' => 60,
            'weekly_session_goal' => 3,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 18],
                'pull_ups' => ['max_strict_reps' => 4, 'fallback_variant' => 'eccentric', 'fallback_reps' => null, 'fallback_seconds' => 6],
                'dips' => ['max_strict_reps' => 6, 'fallback_variant' => 'assisted', 'fallback_reps' => 5, 'fallback_seconds' => null],
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
            'mobility_checks' => ['wrist_extension' => 'limited', 'shoulder_flexion' => 'clear'],
            'weighted_baselines' => ['experience' => 'repetition_work', 'unit' => 'kg', 'movements' => []],
            'readiness_rating' => 4,
            'sleep_quality' => 4,
            'soreness_level' => 2,
            'pain_level' => 1,
            'pain_areas' => [],
            'pain_flags' => [
                'wrist' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
                'elbow' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
                'shoulder' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
                'low_back' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
                'knee' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
                'ankle' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
            ],
            'pain_notes' => null,
            'starter_plan_key' => 'skill_strength_split',
            'is_complete' => false,
            'completed_at' => null,
            'missing_sections' => [],
        ],
    ])]
    #[ResponseField('data', 'object', 'Athlete onboarding answers.', required: true)]
    #[ResponseField('data.current_level_tests', 'object', 'Baseline push-up, pull-up, dip, row, lower-body, hang, support, and hollow body tests.', required: true)]
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
            'weight_trend' => 'maintaining',
            'prior_sport_background' => ['strength_training'],
            'primary_goal' => 'skill',
            'secondary_goals' => ['strength'],
            'target_skills' => ['handstand'],
            'primary_target_skill' => 'handstand',
            'secondary_target_skills' => ['strict_pull_up'],
            'long_term_target_skills' => ['planche'],
            'base_focus_areas' => ['pull_capacity', 'core_bodyline'],
            'required_goal_modules' => ['inversion'],
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
            'roadmap_suggestions' => self::ROADMAP_EXAMPLE,
            'available_equipment' => ['pull_up_bar', 'rings'],
            'training_locations' => ['home'],
            'preferred_training_days' => ['monday', 'wednesday', 'friday'],
            'preferred_session_minutes' => 60,
            'weekly_session_goal' => 3,
            'current_level_tests' => [
                'push_ups' => ['max_strict_reps' => 18],
                'pull_ups' => ['max_strict_reps' => 4, 'fallback_variant' => 'eccentric', 'fallback_reps' => null, 'fallback_seconds' => 6],
                'dips' => ['max_strict_reps' => 6, 'fallback_variant' => 'assisted', 'fallback_reps' => 5, 'fallback_seconds' => null],
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
            'mobility_checks' => ['wrist_extension' => 'limited', 'shoulder_flexion' => 'clear'],
            'weighted_baselines' => ['experience' => 'repetition_work', 'unit' => 'kg', 'movements' => []],
            'readiness_rating' => 4,
            'sleep_quality' => 4,
            'soreness_level' => 2,
            'pain_level' => 1,
            'pain_areas' => [],
            'pain_flags' => [
                'wrist' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
                'elbow' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
                'shoulder' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
                'low_back' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
                'knee' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
                'ankle' => ['severity' => 'none', 'status' => 'none', 'notes' => null],
            ],
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
