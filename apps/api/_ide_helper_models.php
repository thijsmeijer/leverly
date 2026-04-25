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
 * @property string $display_name
 * @property string $timezone
 * @property string $unit_system
 * @property int|null $training_age_months
 * @property string $experience_level
 * @property float|null $current_bodyweight_value
 * @property string $bodyweight_unit
 * @property string|null $primary_goal
 * @property array<array-key, mixed> $secondary_goals
 * @property array<array-key, mixed> $target_skills
 * @property array<array-key, mixed> $available_equipment
 * @property array<array-key, mixed> $training_locations
 * @property array<array-key, mixed> $movement_limitations
 * @property string|null $injury_notes
 * @property array<array-key, mixed> $preferred_training_days
 * @property int|null $preferred_session_minutes
 * @property int|null $weekly_session_goal
 * @property string $preferred_training_time
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereAvailableEquipment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereBodyweightUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereCurrentBodyweightValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereDeloadPreference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereDisplayName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereEffortTrackingPreference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereExperienceLevel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereInjuryNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereIntensityPreference($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereMovementLimitations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile wherePreferredSessionMinutes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile wherePreferredTrainingDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile wherePreferredTrainingTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile wherePrimaryGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereProgressionPace($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereSecondaryGoals($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereSessionStructurePreferences($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereTargetSkills($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereTrainingAgeMonths($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereTrainingLocations($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereUnitSystem($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AthleteProfile whereWeeklySessionGoal($value)
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

