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
        'goal_candidates' => [
            'primary' => [
                [
                    'skill' => 'handstand',
                    'label' => 'Handstand',
                    'role' => 'primary_candidate',
                    'status' => 'ready',
                    'readiness_score' => 78,
                    'confidence' => 0.78,
                    'stress_class' => 'low_fatigue',
                    'stress_tags' => ['overhead', 'wrist_extension', 'line_balance'],
                    'reason' => 'Your pressing, bodyline, shoulder, and wrist signals are ready for regular handstand practice.',
                    'blockers' => [],
                    'unlock_conditions' => ['Build a clean line and controlled balance entries.'],
                    'base_focus_areas' => ['handstand_line', 'core_bodyline'],
                    'next_gate' => 'Build a clean line and controlled balance entries.',
                    'compatible_with_primary' => null,
                    'compatibility_reason' => '',
                ],
            ],
            'secondary' => [
                [
                    'skill' => 'strict_pull_up',
                    'label' => 'Pull-up',
                    'role' => 'secondary_candidate',
                    'status' => 'ready',
                    'readiness_score' => 68,
                    'confidence' => 0.72,
                    'stress_class' => 'foundation',
                    'stress_tags' => ['pull', 'vertical_pull', 'elbow_flexor_load'],
                    'reason' => 'Pulling volume can support the primary roadmap without replacing it.',
                    'blockers' => [],
                    'unlock_conditions' => ['Build toward 3 clean sets of 6 to 8.'],
                    'base_focus_areas' => ['pull_capacity', 'row_volume'],
                    'next_gate' => 'Build toward 3 clean sets of 6 to 8.',
                    'compatible_with_primary' => true,
                    'compatibility_reason' => '',
                ],
            ],
            'accessories' => [],
            'future' => [
                [
                    'skill' => 'planche',
                    'label' => 'Planche',
                    'role' => 'long_term',
                    'status' => 'deferred',
                    'readiness_score' => 42,
                    'confidence' => 0.58,
                    'stress_class' => 'high',
                    'stress_tags' => ['push', 'straight_arm_push', 'wrist_extension', 'overhead'],
                    'reason' => 'Planche is a long-term strength skill.',
                    'blockers' => ['Minimum planche node is not reached yet.'],
                    'unlock_conditions' => ['Reach reliable push-up volume and pain-free straight-arm loading.'],
                    'base_focus_areas' => ['push_capacity', 'straight_arm_tolerance'],
                    'next_gate' => 'Reach reliable push-up volume and pain-free straight-arm loading.',
                    'compatible_with_primary' => null,
                    'compatibility_reason' => '',
                ],
            ],
            'foundation' => [
                [
                    'skill' => 'strict_push_up',
                    'label' => 'Push-up',
                    'role' => 'foundation_bridge',
                    'status' => 'ready',
                    'readiness_score' => 72,
                    'confidence' => 0.74,
                    'stress_class' => 'foundation',
                    'stress_tags' => ['push', 'trunk'],
                    'reason' => 'Build pressing volume as support for skill work.',
                    'blockers' => [],
                    'unlock_conditions' => ['Build repeatable sets of 8 to 12 reps.'],
                    'base_focus_areas' => ['push_capacity', 'core_bodyline'],
                    'next_gate' => 'Build repeatable sets of 8 to 12 reps.',
                    'compatible_with_primary' => null,
                    'compatibility_reason' => '',
                ],
            ],
        ],
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
        'eta_range' => [
            'min_weeks' => 8,
            'max_weeks' => 24,
            'p50_weeks' => 16,
            'p80_weeks' => 21,
            'label' => '8-24 weeks',
            'confidence' => 0.78,
            'modifiers' => ['Adherence placeholder uses neutral consistency until workout history exists.'],
        ],
        'confidence' => ['level' => 'medium', 'score' => 0.78, 'reasons' => ['Required equipment is available.']],
        'blockers' => [],
        'unlock_conditions' => [['skill' => 'handstand', 'label' => 'Build a clean line and controlled balance entries.', 'status' => 'next']],
        'compatibility_tags' => ['overhead', 'wrist_extension', 'skill_practice'],
        'domain_bottlenecks' => [
            [
                'domain' => 'vertical_pull',
                'label' => 'Vertical pull',
                'score' => 46,
                'confidence' => 0.74,
                'reason' => 'This domain has workable but incomplete evidence.',
                'missing_inputs' => [],
            ],
        ],
        'current_block_focus' => [
            'label' => 'Current 4-8 week prescriptive block',
            'eta_range' => ['min_weeks' => 5, 'max_weeks' => 10, 'p50_weeks' => 8, 'p80_weeks' => 9, 'label' => '5-10 weeks'],
            'lanes' => ['handstand', 'strict_pull_up', 'foundation_strength'],
            'focus_areas' => ['pull_capacity', 'core_bodyline', 'Inversion and balance'],
            'should_improve' => ['Inversion and balance', 'Trunk rigidity', 'Tissue tolerance'],
            'retest_cadence' => ['session updates', 'weekly review', '4-6 week block retest', 'seasonal 12-24 week goal review'],
        ],
        'explanation' => [
            'summary' => 'Handstand is the clearest first roadmap priority from the current assessment.',
            'primary_now' => 'Handstand is the primary roadmap right now.',
            'why_this_goal' => ['Your pressing, bodyline, shoulder, and wrist signals are ready for regular handstand practice.'],
            'what_is_missing' => [],
            'this_block_should_improve' => ['Inversion and balance', 'Trunk rigidity', 'Tissue tolerance'],
            'not_trained_yet' => ['Planche is deferred until pressing, wrist, and bodyline prerequisites improve.'],
            'what_would_change_recommendation' => ['A block retest can promote, hold, or defer the next progression.'],
            'watch_out_for' => [],
            'fallback' => 'If readiness drops, keep the same target and use the previous easier progression.',
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
