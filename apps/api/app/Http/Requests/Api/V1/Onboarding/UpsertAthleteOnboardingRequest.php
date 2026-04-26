<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Onboarding;

use App\Domain\Onboarding\Support\AthleteOnboardingOptions;
use App\Domain\Profile\Support\AthleteProfileOptions;
use App\Models\AthleteOnboarding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpsertAthleteOnboardingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'complete' => ['sometimes', 'boolean'],
            'primary_goal' => ['sometimes', 'nullable', 'string', Rule::in(AthleteProfileOptions::GOALS)],
            'secondary_goals' => ['sometimes', 'array', 'max:2'],
            'secondary_goals.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::GOALS)],
            'target_skills' => ['sometimes', 'array', 'max:8'],
            'target_skills.*' => ['string', 'distinct', Rule::in(AthleteOnboardingOptions::TARGET_SKILLS)],
            'available_equipment' => ['sometimes', 'array', 'max:20'],
            'available_equipment.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::EQUIPMENT)],
            'training_locations' => ['sometimes', 'array', 'max:5'],
            'training_locations.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::TRAINING_LOCATIONS)],
            'preferred_training_days' => ['sometimes', 'array', 'max:7'],
            'preferred_training_days.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::TRAINING_DAYS)],
            'preferred_session_minutes' => ['sometimes', 'nullable', 'integer', 'min:10', 'max:240'],
            'weekly_session_goal' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:14'],
            'preferred_training_time' => ['sometimes', 'string', Rule::in(AthleteProfileOptions::TRAINING_TIMES)],
            'current_level_tests' => ['sometimes', 'array:push_ups,pull_ups,squat,hollow_hold_seconds'],
            'current_level_tests.push_ups' => ['sometimes', 'array:max_strict_reps'],
            'current_level_tests.push_ups.max_strict_reps' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:200'],
            'current_level_tests.pull_ups' => ['sometimes', 'array:max_strict_reps,progression'],
            'current_level_tests.pull_ups.max_strict_reps' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'current_level_tests.pull_ups.progression' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(AthleteOnboardingOptions::PULL_UP_PROGRESSIONS),
            ],
            'current_level_tests.squat' => ['sometimes', 'array:max_reps,progression'],
            'current_level_tests.squat.max_reps' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:300'],
            'current_level_tests.squat.progression' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(AthleteOnboardingOptions::SQUAT_PROGRESSIONS),
            ],
            'current_level_tests.hollow_hold_seconds' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:600'],
            'skill_statuses' => ['sometimes', 'array:dip,l_sit,handstand,front_lever,planche'],
            'skill_statuses.*' => ['array:status,max_strict_reps,best_hold_seconds,notes'],
            'skill_statuses.*.status' => ['required', 'string', Rule::in(AthleteOnboardingOptions::SKILL_STATUSES)],
            'skill_statuses.*.max_strict_reps' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'skill_statuses.*.best_hold_seconds' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:600'],
            'skill_statuses.*.notes' => ['sometimes', 'nullable', 'string', 'max:300'],
            'readiness_rating' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'sleep_quality' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'soreness_level' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:5'],
            'pain_level' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:10'],
            'pain_areas' => ['sometimes', 'array', 'max:8'],
            'pain_areas.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::LIMITATION_AREAS)],
            'pain_notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'starter_plan_key' => ['sometimes', 'nullable', 'string', Rule::in(AthleteOnboardingOptions::STARTER_PLANS)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function onboardingData(): array
    {
        return $this->validated();
    }

    public function shouldComplete(): bool
    {
        return $this->boolean('complete');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'complete' => ['description' => 'When true, validates and marks onboarding complete.', 'example' => true],
            'primary_goal' => ['description' => 'Primary training goal.', 'example' => 'skill'],
            'secondary_goals' => ['description' => 'Compatible secondary goals.', 'example' => ['strength']],
            'target_skills' => ['description' => 'Controlled calisthenics skill target slugs.', 'example' => ['strict_pull_up', 'handstand']],
            'available_equipment' => ['description' => 'Available equipment slugs.', 'example' => ['pull_up_bar', 'rings']],
            'training_locations' => ['description' => 'Where the athlete can train.', 'example' => ['home', 'park']],
            'preferred_training_days' => ['description' => 'Training days available for scheduling.', 'example' => ['monday', 'wednesday', 'friday']],
            'preferred_session_minutes' => ['description' => 'Maximum session length in minutes.', 'example' => 60],
            'weekly_session_goal' => ['description' => 'Target sessions per week.', 'example' => 3],
            'preferred_training_time' => ['description' => 'Preferred training time.', 'example' => 'evening'],
            'current_level_tests' => [
                'description' => 'Baseline tests for starter recommendation placement.',
                'example' => [
                    'push_ups' => ['max_strict_reps' => 18],
                    'pull_ups' => ['max_strict_reps' => 4, 'progression' => 'strict_pull_up'],
                    'squat' => ['max_reps' => 20, 'progression' => 'split_squat'],
                    'hollow_hold_seconds' => 35,
                ],
            ],
            'skill_statuses' => [
                'description' => 'Optional current skill statuses.',
                'example' => [
                    'handstand' => ['status' => 'assisted', 'best_hold_seconds' => 20],
                    'l_sit' => ['status' => 'short_hold', 'best_hold_seconds' => 8],
                ],
            ],
            'readiness_rating' => ['description' => 'Current readiness, 1-5.', 'example' => 4],
            'sleep_quality' => ['description' => 'Recent sleep quality, 1-5.', 'example' => 4],
            'soreness_level' => ['description' => 'Current soreness, 1-5.', 'example' => 2],
            'pain_level' => ['description' => 'Current pain, 0-10.', 'example' => 1],
            'pain_areas' => ['description' => 'Current pain areas if relevant.', 'example' => ['wrist']],
            'pain_notes' => ['description' => 'Private pain or limitation notes.', 'example' => 'Wrists need longer warm-up for planche leans.'],
            'starter_plan_key' => ['description' => 'Preferred starter plan shape.', 'example' => 'skill_strength_split'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('pain_notes') && is_string($this->input('pain_notes'))) {
            $value = trim((string) $this->input('pain_notes'));

            $this->merge(['pain_notes' => $value === '' ? null : $value]);
        }
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $this->validateSecondaryGoals($validator);
                $this->validateCompletion($validator);
            },
        ];
    }

    private function validateSecondaryGoals(Validator $validator): void
    {
        $primaryGoal = $this->input('primary_goal');
        $secondaryGoals = $this->input('secondary_goals', []);

        if (! is_string($primaryGoal) || ! is_array($secondaryGoals)) {
            return;
        }

        $compatibleGoals = AthleteProfileOptions::COMPATIBLE_SECONDARY_GOALS[$primaryGoal] ?? [];

        foreach ($secondaryGoals as $index => $secondaryGoal) {
            if (! is_string($secondaryGoal) || in_array($secondaryGoal, $compatibleGoals, true)) {
                continue;
            }

            $validator->errors()->add(
                "secondary_goals.{$index}",
                'The selected secondary goal does not fit the primary goal.',
            );
        }
    }

    private function validateCompletion(Validator $validator): void
    {
        if (! $this->shouldComplete()) {
            return;
        }

        $current = AthleteOnboarding::query()
            ->ownedBy($this->user())
            ->first();

        $missingSections = AthleteOnboardingOptions::missingSections(
            AthleteOnboardingOptions::mergeForCompletion($current, $this->all()),
        );

        foreach ($missingSections as $section) {
            $validator->errors()->add(
                'complete',
                "Onboarding cannot be completed until {$section} is provided.",
            );
        }
    }
}
