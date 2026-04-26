<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Onboarding;

use App\Domain\Onboarding\Support\AthleteOnboardingOptions;
use App\Domain\Profile\Support\AthleteProfileOptions;
use App\Domain\Training\Roadmap\RoadmapInputMapper;
use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
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
            'target_skills.*' => ['string', 'distinct', Rule::in(AthleteOnboardingOptions::TARGET_SKILLS)],
            'primary_target_skill' => ['sometimes', 'nullable', 'string', Rule::in(AthleteOnboardingOptions::TARGET_SKILLS)],
            'secondary_target_skills' => ['sometimes', 'array', 'max:2'],
            'secondary_target_skills.*' => ['string', 'distinct', Rule::in(AthleteOnboardingOptions::TARGET_SKILLS)],
            'long_term_target_skills' => ['sometimes', 'array', 'max:8'],
            'long_term_target_skills.*' => ['string', 'distinct', Rule::in(AthleteOnboardingOptions::TARGET_SKILLS)],
            'base_focus_areas' => ['sometimes', 'array', 'max:4'],
            'base_focus_areas.*' => ['string', 'distinct', Rule::in(AthleteOnboardingOptions::BASE_FOCUS_AREAS)],
            'available_equipment' => ['sometimes', 'array', 'max:20'],
            'available_equipment.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::EQUIPMENT)],
            'training_locations' => ['sometimes', 'array', 'max:5'],
            'training_locations.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::TRAINING_LOCATIONS)],
            'preferred_training_days' => ['sometimes', 'array', 'max:7'],
            'preferred_training_days.*' => ['string', 'distinct', Rule::in(AthleteProfileOptions::TRAINING_DAYS)],
            'preferred_session_minutes' => ['sometimes', 'nullable', 'integer', 'min:10', 'max:240'],
            'weekly_session_goal' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:14'],
            'current_level_tests' => ['sometimes', 'array:push_ups,pull_ups,dips,squat,hollow_hold_seconds'],
            'current_level_tests.push_ups' => ['sometimes', 'array:max_strict_reps'],
            'current_level_tests.push_ups.max_strict_reps' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:200'],
            'current_level_tests.pull_ups' => ['sometimes', 'array:max_strict_reps'],
            'current_level_tests.pull_ups.max_strict_reps' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'current_level_tests.dips' => ['sometimes', 'array:max_strict_reps'],
            'current_level_tests.dips.max_strict_reps' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'current_level_tests.squat' => ['sometimes', 'array:barbell_load_value,barbell_reps'],
            'current_level_tests.squat.barbell_load_value' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:1000'],
            'current_level_tests.squat.barbell_reps' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:30'],
            'current_level_tests.hollow_hold_seconds' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:600'],
            'skill_statuses' => ['sometimes', 'array:muscle_up,l_sit,handstand,handstand_push_up,front_lever,back_lever,planche,pistol_squat,one_arm_pull_up,human_flag,press_to_handstand'],
            'skill_statuses.*' => ['array:status,max_strict_reps,best_hold_seconds,notes'],
            'skill_statuses.*.status' => ['required', 'string', Rule::in(AthleteOnboardingOptions::SKILL_STATUSES)],
            'skill_statuses.*.max_strict_reps' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:100'],
            'skill_statuses.*.best_hold_seconds' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:600'],
            'skill_statuses.*.notes' => ['sometimes', 'nullable', 'string', 'max:300'],
            'mobility_checks' => ['sometimes', 'array:wrist_extension,shoulder_flexion,shoulder_extension,ankle_dorsiflexion,pancake_compression'],
            'mobility_checks.*' => ['string', Rule::in(AthleteOnboardingOptions::MOBILITY_STATUSES)],
            'weighted_baselines' => ['sometimes', 'array:experience,unit,movements'],
            'weighted_baselines.experience' => ['sometimes', 'string', Rule::in(AthleteOnboardingOptions::WEIGHTED_EXPERIENCE_LEVELS)],
            'weighted_baselines.unit' => ['sometimes', 'string', Rule::in(AthleteProfileOptions::BODYWEIGHT_UNITS)],
            'weighted_baselines.movements' => ['sometimes', 'array', 'max:4'],
            'weighted_baselines.movements.*' => ['array:movement,external_load_value,reps,rir'],
            'weighted_baselines.movements.*.movement' => ['required', 'string', Rule::in(AthleteOnboardingOptions::WEIGHTED_MOVEMENTS)],
            'weighted_baselines.movements.*.external_load_value' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:400'],
            'weighted_baselines.movements.*.reps' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:30'],
            'weighted_baselines.movements.*.rir' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:10'],
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
            'age_years' => ['description' => 'Athlete age in years.', 'example' => 29],
            'training_age_months' => ['description' => 'Total training experience in months.', 'example' => 18],
            'experience_level' => ['description' => 'Self-assessed training experience band.', 'example' => 'intermediate'],
            'current_bodyweight_value' => ['description' => 'Current bodyweight in the selected unit.', 'example' => 72.5],
            'bodyweight_unit' => ['description' => 'Bodyweight unit.', 'example' => 'kg'],
            'height_value' => ['description' => 'Current height in the selected unit.', 'example' => 178],
            'height_unit' => ['description' => 'Height unit.', 'example' => 'cm'],
            'prior_sport_background' => ['description' => 'Relevant prior sport or training background.', 'example' => ['strength_training']],
            'primary_goal' => ['description' => 'Primary training goal.', 'example' => 'skill'],
            'secondary_goals' => ['description' => 'Compatible secondary goals.', 'example' => ['strength']],
            'target_skills' => ['description' => 'Current active skill target slugs from the generated roadmap.', 'example' => ['strict_pull_up', 'handstand']],
            'primary_target_skill' => ['description' => 'The one roadmap the first plan should prioritize.', 'example' => 'handstand'],
            'secondary_target_skills' => ['description' => 'Optional target skills that can receive lighter exposure.', 'example' => ['strict_pull_up']],
            'long_term_target_skills' => ['description' => 'Later aspirations that should not drive the first block yet.', 'example' => ['planche']],
            'base_focus_areas' => ['description' => 'Base-development areas that should support the primary roadmap.', 'example' => ['pull_capacity', 'core_bodyline']],
            'available_equipment' => ['description' => 'Available equipment slugs.', 'example' => ['pull_up_bar', 'rings']],
            'training_locations' => ['description' => 'Where the athlete can train.', 'example' => ['home', 'park']],
            'preferred_training_days' => ['description' => 'Training days available for scheduling.', 'example' => ['monday', 'wednesday', 'friday']],
            'preferred_session_minutes' => ['description' => 'Maximum session length in minutes.', 'example' => 60],
            'weekly_session_goal' => ['description' => 'Maximum training sessions per week.', 'example' => 3],
            'current_level_tests' => [
                'description' => 'Baseline tests for starter recommendation placement.',
                'example' => [
                    'push_ups' => ['max_strict_reps' => 18],
                    'pull_ups' => ['max_strict_reps' => 4],
                    'dips' => ['max_strict_reps' => 6],
                    'squat' => ['barbell_load_value' => 100, 'barbell_reps' => 5],
                    'hollow_hold_seconds' => 35,
                ],
            ],
            'skill_statuses' => [
                'description' => 'Optional current skill statuses.',
                'example' => [
                    'handstand' => ['status' => 'freestanding_kick_up', 'best_hold_seconds' => 20],
                    'l_sit' => ['status' => 'full_l_sit', 'best_hold_seconds' => 8],
                ],
            ],
            'mobility_checks' => [
                'description' => 'Self-checks for positions that affect progression placement.',
                'example' => [
                    'wrist_extension' => 'limited',
                    'shoulder_flexion' => 'clear',
                    'shoulder_extension' => 'clear',
                    'ankle_dorsiflexion' => 'limited',
                    'pancake_compression' => 'not_tested',
                ],
            ],
            'weighted_baselines' => [
                'description' => 'Optional weighted calisthenics experience and recent tested sets.',
                'example' => [
                    'experience' => 'repetition_work',
                    'unit' => 'kg',
                    'movements' => [
                        ['movement' => 'weighted_pull_up', 'external_load_value' => 12.5, 'reps' => 5, 'rir' => 2],
                    ],
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
                $this->validatePlacementTargets($validator);
                $this->validateHeight($validator);
                $this->validateRoadmapTargets($validator);
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

    private function validatePlacementTargets(Validator $validator): void
    {
        $targetSkills = $this->input('target_skills', []);
        $primaryTargetSkill = $this->input('primary_target_skill');
        $secondaryTargetSkills = $this->input('secondary_target_skills', []);
        $longTermTargetSkills = $this->input('long_term_target_skills', []);

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

        if (! is_array($longTermTargetSkills)) {
            return;
        }

        foreach ($longTermTargetSkills as $index => $longTermTargetSkill) {
            if (is_string($longTermTargetSkill) && in_array($longTermTargetSkill, $targetSkills, true)) {
                $validator->errors()->add(
                    "long_term_target_skills.{$index}",
                    'A long-term target cannot also be an active target.',
                );
            }
        }
    }

    private function validateHeight(Validator $validator): void
    {
        $height = $this->input('height_value');
        $unit = $this->input('height_unit', 'cm');

        if (! is_numeric($height) || ! is_string($unit)) {
            return;
        }

        $height = (float) $height;

        if ($unit === 'cm' && ($height < 90 || $height > 250)) {
            $validator->errors()->add('height_value', 'Height must be between 90 and 250 cm.');
        }

        if ($unit === 'in' && ($height < 36 || $height > 100)) {
            $validator->errors()->add('height_value', 'Height must be between 36 and 100 inches.');
        }
    }

    private function validateRoadmapTargets(Validator $validator): void
    {
        $targetSkills = $this->input('target_skills', []);
        $primaryTargetSkill = $this->input('primary_target_skill');

        if (! is_array($targetSkills) || $targetSkills === []) {
            return;
        }

        $current = AthleteOnboarding::query()
            ->ownedBy($this->user())
            ->first();

        $candidate = AthleteOnboardingOptions::mergeForCompletion($current, $this->all());
        $activeSkillSlugs = CalisthenicsRoadmapSuggester::activeSkillSlugs(
            CalisthenicsRoadmapSuggester::suggest(RoadmapInputMapper::fromAthleteData($candidate)),
        );

        foreach ($targetSkills as $index => $targetSkill) {
            if (! is_string($targetSkill) || in_array($targetSkill, $activeSkillSlugs, true)) {
                continue;
            }

            $validator->errors()->add(
                "target_skills.{$index}",
                'Active targets must come from the current or bridge roadmap suggestions.',
            );
        }

        if (is_string($primaryTargetSkill) && ! in_array($primaryTargetSkill, $activeSkillSlugs, true)) {
            $validator->errors()->add(
                'primary_target_skill',
                'The primary target must come from the current or bridge roadmap suggestions.',
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
