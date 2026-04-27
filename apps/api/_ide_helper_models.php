<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property string $id
 * @property string $user_id
 * @property int|null $age_years
 * @property int|null $training_age_months
 * @property string $experience_level
 * @property float|null $current_bodyweight_value
 * @property string $bodyweight_unit
 * @property float|null $height_value
 * @property string $height_unit
 * @property string $weight_trend
 * @property array<array-key, mixed> $prior_sport_background
 * @property string|null $primary_goal
 * @property array<array-key, mixed> $secondary_goals
 * @property array<array-key, mixed> $target_skills
 * @property string|null $primary_target_skill
 * @property array<array-key, mixed> $secondary_target_skills
 * @property array<array-key, mixed> $long_term_target_skills
 * @property array<array-key, mixed> $base_focus_areas
 * @property array<array-key, mixed> $goal_modules
 * @property array<array-key, mixed> $roadmap_suggestions
 * @property array<array-key, mixed> $available_equipment
 * @property array<array-key, mixed> $training_locations
 * @property array<array-key, mixed> $preferred_training_days
 * @property int|null $preferred_session_minutes
 * @property int|null $weekly_session_goal
 * @property array<array-key, mixed> $current_level_tests
 * @property array<array-key, mixed> $skill_statuses
 * @property array<array-key, mixed> $mobility_checks
 * @property array<array-key, mixed> $weighted_baselines
 * @property int|null $readiness_rating
 * @property int|null $sleep_quality
 * @property int|null $soreness_level
 * @property int|null $pain_level
 * @property array<array-key, mixed> $pain_areas
 * @property array<array-key, mixed> $pain_flags
 * @property string|null $pain_notes
 * @property string|null $starter_plan_key
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\AthleteOnboardingFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding ownedBy(\App\Models\User|string|int $user)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereAgeYears($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereAvailableEquipment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereBaseFocusAreas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereBodyweightUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereCurrentBodyweightValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereCurrentLevelTests($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereExperienceLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereGoalModules($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereHeightUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereHeightValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereLongTermTargetSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereMobilityChecks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding wherePainAreas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding wherePainFlags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding wherePainLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding wherePainNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding wherePreferredSessionMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding wherePreferredTrainingDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding wherePrimaryGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding wherePrimaryTargetSkill($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding wherePriorSportBackground($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereReadinessRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereRoadmapSuggestions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereSecondaryGoals($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereSecondaryTargetSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereSkillStatuses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereSleepQuality($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereSorenessLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereStarterPlanKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereTargetSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereTrainingAgeMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereTrainingLocations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereWeeklySessionGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereWeightTrend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteOnboarding whereWeightedBaselines($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAthleteOnboarding {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $user_id
 * @property string $display_name
 * @property string $timezone
 * @property string $unit_system
 * @property int|null $age_years
 * @property int|null $training_age_months
 * @property string $experience_level
 * @property float|null $current_bodyweight_value
 * @property string $bodyweight_unit
 * @property float|null $height_value
 * @property string $height_unit
 * @property string $weight_trend
 * @property array<array-key, mixed> $prior_sport_background
 * @property string|null $primary_goal
 * @property array<array-key, mixed> $secondary_goals
 * @property array<array-key, mixed> $target_skills
 * @property string|null $primary_target_skill
 * @property array<array-key, mixed> $secondary_target_skills
 * @property array<array-key, mixed> $long_term_target_skills
 * @property array<array-key, mixed> $base_focus_areas
 * @property array<array-key, mixed> $goal_modules
 * @property array<array-key, mixed> $roadmap_suggestions
 * @property array<array-key, mixed> $available_equipment
 * @property array<array-key, mixed> $training_locations
 * @property array<array-key, mixed> $movement_limitations
 * @property array<array-key, mixed> $pain_flags
 * @property array<array-key, mixed> $baseline_tests
 * @property array<array-key, mixed> $skill_statuses
 * @property array<array-key, mixed> $mobility_checks
 * @property array<array-key, mixed> $weighted_baselines
 * @property string|null $injury_notes
 * @property array<array-key, mixed> $preferred_training_days
 * @property int|null $preferred_session_minutes
 * @property int|null $weekly_session_goal
 * @property string $progression_pace
 * @property string $intensity_preference
 * @property string $effort_tracking_preference
 * @property string $deload_preference
 * @property array<array-key, mixed> $session_structure_preferences
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Database\Factories\AthleteProfileFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile ownedBy(\App\Models\User|string|int $user)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereAgeYears($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereAvailableEquipment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereBaseFocusAreas($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereBaselineTests($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereBodyweightUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereCurrentBodyweightValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereDeloadPreference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereEffortTrackingPreference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereExperienceLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereGoalModules($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereHeightUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereHeightValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereInjuryNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereIntensityPreference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereLongTermTargetSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereMobilityChecks($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereMovementLimitations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile wherePainFlags($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile wherePreferredSessionMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile wherePreferredTrainingDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile wherePrimaryGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile wherePrimaryTargetSkill($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile wherePriorSportBackground($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereProgressionPace($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereRoadmapSuggestions($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereSecondaryGoals($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereSecondaryTargetSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereSessionStructurePreferences($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereSkillStatuses($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereTargetSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereTrainingAgeMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereTrainingLocations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereUnitSystem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereWeeklySessionGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereWeightTrend($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereWeightedBaselines($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperAthleteProfile {}
}

namespace App\Models{
/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\AthleteProfile|null $athleteProfile
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Laravel\Sanctum\PersonalAccessToken> $tokens
 * @property-read int|null $tokens_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @mixin \Eloquent
 */
	#[\AllowDynamicProperties]
	class IdeHelperUser {}
}

