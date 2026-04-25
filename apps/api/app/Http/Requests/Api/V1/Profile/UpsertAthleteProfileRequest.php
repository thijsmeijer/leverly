<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Profile;

use App\Domain\Profile\Support\AthleteProfileOptions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpsertAthleteProfileRequest extends FormRequest
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
            'display_name' => ['sometimes', 'string', 'min:1', 'max:120'],
            'timezone' => ['sometimes', 'string', 'max:80', 'timezone:all'],
            'unit_system' => ['sometimes', 'string', Rule::in(AthleteProfileOptions::UNIT_SYSTEMS)],
            'training_age_months' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1200'],
            'experience_level' => ['sometimes', 'string', Rule::in(AthleteProfileOptions::EXPERIENCE_LEVELS)],
            'current_bodyweight_value' => ['sometimes', 'nullable', 'numeric', 'min:20', 'max:400'],
            'bodyweight_unit' => ['sometimes', 'string', Rule::in(AthleteProfileOptions::BODYWEIGHT_UNITS)],
            'primary_goal' => ['sometimes', 'nullable', 'string', Rule::in(AthleteProfileOptions::GOALS)],
            'secondary_goals' => ['sometimes', 'array', 'max:2'],
            'secondary_goals.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::GOALS)],
            'target_skills' => ['sometimes', 'array', 'max:20'],
            'target_skills.*' => ['string', 'distinct', 'min:2', 'max:80'],
            'available_equipment' => ['sometimes', 'array', 'max:20'],
            'available_equipment.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::EQUIPMENT)],
            'training_locations' => ['sometimes', 'array', 'max:5'],
            'training_locations.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::TRAINING_LOCATIONS)],
            'movement_limitations' => ['sometimes', 'array', 'max:12'],
            'movement_limitations.*' => ['array:area,severity,status,notes'],
            'movement_limitations.*.area' => ['required', 'string', Rule::in(AthleteProfileOptions::LIMITATION_AREAS)],
            'movement_limitations.*.severity' => ['required', 'string', Rule::in(AthleteProfileOptions::LIMITATION_SEVERITIES)],
            'movement_limitations.*.status' => ['required', 'string', Rule::in(AthleteProfileOptions::LIMITATION_STATUSES)],
            'movement_limitations.*.notes' => ['sometimes', 'nullable', 'string', 'max:500'],
            'injury_notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'preferred_training_days' => ['sometimes', 'array', 'max:7'],
            'preferred_training_days.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::TRAINING_DAYS)],
            'preferred_session_minutes' => ['sometimes', 'nullable', 'integer', 'min:10', 'max:240'],
            'weekly_session_goal' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:14'],
            'preferred_training_time' => ['sometimes', 'string', Rule::in(AthleteProfileOptions::TRAINING_TIMES)],
            'progression_pace' => ['sometimes', 'string', Rule::in(AthleteProfileOptions::PROGRESSION_PACES)],
            'intensity_preference' => ['sometimes', 'string', Rule::in(AthleteProfileOptions::INTENSITY_PREFERENCES)],
            'effort_tracking_preference' => ['sometimes', 'string', Rule::in(AthleteProfileOptions::EFFORT_TRACKING_PREFERENCES)],
            'deload_preference' => ['sometimes', 'string', Rule::in(AthleteProfileOptions::DELOAD_PREFERENCES)],
            'session_structure_preferences' => ['sometimes', 'array', 'max:3'],
            'session_structure_preferences.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::SESSION_STRUCTURE_PREFERENCES)],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function profileData(): array
    {
        return $this->validated();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'display_name' => ['description' => 'Profile display name.', 'example' => 'Ada Athlete'],
            'timezone' => ['description' => 'IANA timezone.', 'example' => 'Europe/Amsterdam'],
            'unit_system' => ['description' => 'Preferred unit system.', 'example' => 'metric'],
            'training_age_months' => ['description' => 'Total training experience in months.', 'example' => 18],
            'experience_level' => ['description' => 'Training experience band.', 'example' => 'intermediate'],
            'current_bodyweight_value' => ['description' => 'Current bodyweight in the selected unit.', 'example' => 72.5],
            'bodyweight_unit' => ['description' => 'Bodyweight unit.', 'example' => 'kg'],
            'primary_goal' => ['description' => 'Primary training goal.', 'example' => 'skill'],
            'secondary_goals' => ['description' => 'Additional training goals.', 'example' => ['strength', 'mobility']],
            'target_skills' => ['description' => 'Skills the athlete wants to unlock.', 'example' => ['freestanding handstand', 'strict muscle-up']],
            'available_equipment' => ['description' => 'Available equipment slugs.', 'example' => ['pull_up_bar', 'rings', 'parallettes']],
            'training_locations' => ['description' => 'Preferred training locations.', 'example' => ['home', 'park']],
            'movement_limitations' => [
                'description' => 'Movement limitations or pain flags.',
                'example' => [
                    [
                        'area' => 'wrist',
                        'severity' => 'mild',
                        'status' => 'recurring',
                        'notes' => 'Needs longer warm-up.',
                    ],
                ],
            ],
            'injury_notes' => ['description' => 'Private injury notes.', 'example' => 'Left wrist can get irritated under high volume.'],
            'preferred_training_days' => ['description' => 'Preferred training weekdays.', 'example' => ['monday', 'wednesday', 'friday']],
            'preferred_session_minutes' => ['description' => 'Maximum session length in minutes.', 'example' => 60],
            'weekly_session_goal' => ['description' => 'Target sessions per week.', 'example' => 4],
            'preferred_training_time' => ['description' => 'Preferred training time.', 'example' => 'evening'],
            'progression_pace' => ['description' => 'Preferred progression pace.', 'example' => 'balanced'],
            'intensity_preference' => ['description' => 'Preferred intensity bias.', 'example' => 'auto'],
            'effort_tracking_preference' => ['description' => 'Preferred effort tracking style.', 'example' => 'rir'],
            'deload_preference' => ['description' => 'Preferred deload handling.', 'example' => 'auto'],
            'session_structure_preferences' => ['description' => 'Session structure preferences.', 'example' => ['skill_first', 'mobility_finish']],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['display_name', 'timezone', 'injury_notes'] as $key) {
            if ($this->has($key) && is_string($this->input($key))) {
                $value = trim((string) $this->input($key));

                $this->merge([$key => $value === '' && $key !== 'display_name' ? null : $value]);
            }
        }
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
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
            },
        ];
    }
}
