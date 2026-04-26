<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Training\Support\CalisthenicsRoadmapSuggester;
use App\Models\AthleteProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AthleteProfile
 */
class AthleteProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'display_name' => $this->display_name,
            'timezone' => $this->timezone,
            'unit_system' => $this->unit_system,
            'age_years' => $this->age_years,
            'training_age_months' => $this->training_age_months,
            'experience_level' => $this->experience_level,
            'current_bodyweight_value' => $this->current_bodyweight_value,
            'bodyweight_unit' => $this->bodyweight_unit,
            'height_value' => $this->height_value,
            'height_unit' => $this->height_unit,
            'prior_sport_background' => $this->prior_sport_background ?? [],
            'primary_goal' => $this->primary_goal,
            'secondary_goals' => $this->secondary_goals ?? [],
            'target_skills' => $this->target_skills ?? [],
            'primary_target_skill' => $this->primary_target_skill,
            'secondary_target_skills' => $this->secondary_target_skills ?? [],
            'long_term_target_skills' => $this->long_term_target_skills ?? [],
            'base_focus_areas' => $this->base_focus_areas ?? [],
            'roadmap_suggestions' => $this->roadmap_suggestions ?? CalisthenicsRoadmapSuggester::empty(),
            'available_equipment' => $this->available_equipment ?? [],
            'training_locations' => $this->training_locations ?? [],
            'movement_limitations' => $this->movement_limitations ?? [],
            'baseline_tests' => $this->baseline_tests ?? [],
            'skill_statuses' => $this->skill_statuses ?? [],
            'mobility_checks' => $this->mobility_checks ?? [],
            'weighted_baselines' => $this->weighted_baselines ?? [],
            'injury_notes' => $this->injury_notes,
            'preferred_training_days' => $this->preferred_training_days ?? [],
            'preferred_session_minutes' => $this->preferred_session_minutes,
            'weekly_session_goal' => $this->weekly_session_goal,
            'progression_pace' => $this->progression_pace,
            'intensity_preference' => $this->intensity_preference,
            'effort_tracking_preference' => $this->effort_tracking_preference,
            'deload_preference' => $this->deload_preference,
            'session_structure_preferences' => $this->session_structure_preferences ?? [],
        ];
    }
}
