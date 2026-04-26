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
            'age_years' => ['sometimes', 'nullable', 'integer', 'min:13', 'max:90'],
            'training_age_months' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:1200'],
            'experience_level' => ['sometimes', 'string', Rule::in(AthleteProfileOptions::EXPERIENCE_LEVELS)],
            'current_bodyweight_value' => ['sometimes', 'nullable', 'numeric', 'min:20', 'max:400'],
            'bodyweight_unit' => ['sometimes', 'string', Rule::in(AthleteProfileOptions::BODYWEIGHT_UNITS)],
            'height_value' => ['sometimes', 'nullable', 'numeric', 'min:36', 'max:250'],
            'height_unit' => ['sometimes', 'string', Rule::in(AthleteProfileOptions::HEIGHT_UNITS)],
            'prior_sport_background' => ['sometimes', 'array', 'max:4'],
            'prior_sport_background.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::PRIOR_SPORT_BACKGROUNDS)],
            'primary_goal' => ['sometimes', 'nullable', 'string', Rule::in(AthleteProfileOptions::GOALS)],
            'secondary_goals' => ['sometimes', 'array', 'max:2'],
            'secondary_goals.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::GOALS)],
            'target_skills' => ['sometimes', 'array', 'max:3'],
            'target_skills.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::TARGET_SKILLS)],
            'primary_target_skill' => ['sometimes', 'nullable', 'string', Rule::in(AthleteProfileOptions::TARGET_SKILLS)],
            'secondary_target_skills' => ['sometimes', 'array', 'max:2'],
            'secondary_target_skills.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::TARGET_SKILLS)],
            'long_term_target_skills' => ['sometimes', 'array', 'max:8'],
            'long_term_target_skills.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::TARGET_SKILLS)],
            'base_focus_areas' => ['sometimes', 'array', 'max:4'],
            'base_focus_areas.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::BASE_FOCUS_AREAS)],
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
            'baseline_tests' => ['sometimes', 'array'],
            'skill_statuses' => ['sometimes', 'array'],
            'mobility_checks' => ['sometimes', 'array:wrist_extension,shoulder_flexion,shoulder_extension,ankle_dorsiflexion,pancake_compression'],
            'mobility_checks.*' => ['string', Rule::in(AthleteProfileOptions::MOBILITY_STATUSES)],
            'weighted_baselines' => ['sometimes', 'array:experience,unit,movements'],
            'weighted_baselines.experience' => ['sometimes', 'string', Rule::in(AthleteProfileOptions::WEIGHTED_EXPERIENCE_LEVELS)],
            'weighted_baselines.unit' => ['sometimes', 'string', Rule::in(AthleteProfileOptions::BODYWEIGHT_UNITS)],
            'weighted_baselines.movements' => ['sometimes', 'array', 'max:4'],
            'weighted_baselines.movements.*' => ['array:movement,external_load_value,reps,rir'],
            'weighted_baselines.movements.*.movement' => ['required', 'string', Rule::in(AthleteProfileOptions::WEIGHTED_MOVEMENTS)],
            'weighted_baselines.movements.*.external_load_value' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:400'],
            'weighted_baselines.movements.*.reps' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:30'],
            'weighted_baselines.movements.*.rir' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:10'],
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
            'age_years' => ['description' => 'Athlete age in years.', 'example' => 29],
            'training_age_months' => ['description' => 'Total training experience in months.', 'example' => 18],
            'experience_level' => ['description' => 'Training experience band.', 'example' => 'intermediate'],
            'current_bodyweight_value' => ['description' => 'Current bodyweight in the selected unit.', 'example' => 72.5],
            'bodyweight_unit' => ['description' => 'Bodyweight unit.', 'example' => 'kg'],
            'height_value' => ['description' => 'Current height in the selected unit.', 'example' => 178],
            'height_unit' => ['description' => 'Height unit.', 'example' => 'cm'],
            'prior_sport_background' => ['description' => 'Relevant prior sport or training background.', 'example' => ['strength_training']],
            'primary_goal' => ['description' => 'Primary training goal.', 'example' => 'skill'],
            'secondary_goals' => ['description' => 'Additional training goals.', 'example' => ['strength', 'mobility']],
            'target_skills' => ['description' => 'Current active skill targets.', 'example' => ['handstand', 'strict_pull_up']],
            'primary_target_skill' => ['description' => 'The one roadmap the current plan should prioritize.', 'example' => 'handstand'],
            'secondary_target_skills' => ['description' => 'Optional target skills that can receive lighter exposure.', 'example' => ['strict_pull_up']],
            'long_term_target_skills' => ['description' => 'Later aspirations that should not drive the current block yet.', 'example' => ['planche']],
            'base_focus_areas' => ['description' => 'Base-development areas that support the selected roadmap.', 'example' => ['pull_capacity', 'core_bodyline']],
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
            'baseline_tests' => [
                'description' => 'Durable baseline tests for progression placement.',
                'example' => [
                    'push_ups' => ['progression' => 'strict_push_up', 'max_strict_reps' => 18, 'form_quality' => 4],
                    'rows' => ['progression' => 'inverted_row', 'max_strict_reps' => 12],
                    'pull_ups' => ['progression' => 'strict_pull_up', 'max_strict_reps' => 4],
                    'dips' => ['progression' => 'bar_dip', 'support_hold_seconds' => 25],
                    'hollow_hold_seconds' => 35,
                    'wall_handstand_seconds' => 25,
                ],
            ],
            'skill_statuses' => [
                'description' => 'Current status for selected skill families.',
                'example' => ['handstand' => ['status' => 'assisted', 'best_hold_seconds' => 20]],
            ],
            'mobility_checks' => [
                'description' => 'Position self-checks that affect progression placement.',
                'example' => ['wrist_extension' => 'limited', 'shoulder_flexion' => 'clear'],
            ],
            'weighted_baselines' => [
                'description' => 'Optional weighted calisthenics experience and recent tested sets.',
                'example' => [
                    'experience' => 'repetition_work',
                    'unit' => 'kg',
                    'movements' => [
                        ['movement' => 'weighted_pull_up', 'external_load_value' => 10, 'reps' => 5, 'rir' => 2],
                    ],
                ],
            ],
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
                $height = $this->input('height_value');
                $heightUnit = $this->input('height_unit', 'cm');

                if (is_numeric($height) && is_string($heightUnit)) {
                    $height = (float) $height;

                    if ($heightUnit === 'cm' && ($height < 90 || $height > 250)) {
                        $validator->errors()->add('height_value', 'Height must be between 90 and 250 cm.');
                    }

                    if ($heightUnit === 'in' && ($height < 36 || $height > 100)) {
                        $validator->errors()->add('height_value', 'Height must be between 36 and 100 inches.');
                    }
                }

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

                $targetSkills = $this->input('target_skills', []);
                $primaryTargetSkill = $this->input('primary_target_skill');
                $secondaryTargetSkills = $this->input('secondary_target_skills', []);

                if (! is_array($targetSkills)) {
                    return;
                }

                if (is_string($primaryTargetSkill) && ! in_array($primaryTargetSkill, $targetSkills, true)) {
                    $validator->errors()->add(
                        'primary_target_skill',
                        'The primary target skill must be one of the selected target skills.',
                    );
                }

                if (! is_array($secondaryTargetSkills)) {
                    return;
                }

                foreach ($secondaryTargetSkills as $index => $secondaryTargetSkill) {
                    if (! is_string($secondaryTargetSkill)) {
                        continue;
                    }

                    if ($secondaryTargetSkill === $primaryTargetSkill) {
                        $validator->errors()->add(
                            "secondary_target_skills.{$index}",
                            'A secondary target cannot match the primary target skill.',
                        );
                    }

                    if (! in_array($secondaryTargetSkill, $targetSkills, true)) {
                        $validator->errors()->add(
                            "secondary_target_skills.{$index}",
                            'Secondary targets must also be selected target skills.',
                        );
                    }
                }

                $longTermTargetSkills = $this->input('long_term_target_skills', []);

                if (is_array($longTermTargetSkills)) {
                    foreach ($longTermTargetSkills as $index => $longTermTargetSkill) {
                        if (is_string($longTermTargetSkill) && in_array($longTermTargetSkill, $targetSkills, true)) {
                            $validator->errors()->add(
                                "long_term_target_skills.{$index}",
                                'A long-term target cannot also be an active target.',
                            );
                        }
                    }
                }

            },
        ];
    }
}
