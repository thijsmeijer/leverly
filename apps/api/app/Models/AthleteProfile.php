<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Authorization\HasUserOwnership;
use App\Support\Authorization\UserOwnedResource;
use Database\Factories\AthleteProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperAthleteProfile
 */
#[Fillable([
    'user_id',
    'display_name',
    'timezone',
    'unit_system',
    'training_age_months',
    'experience_level',
    'current_bodyweight_value',
    'bodyweight_unit',
    'primary_goal',
    'secondary_goals',
    'target_skills',
    'available_equipment',
    'training_locations',
    'movement_limitations',
    'injury_notes',
    'preferred_training_days',
    'preferred_session_minutes',
    'weekly_session_goal',
    'preferred_training_time',
    'progression_pace',
    'intensity_preference',
    'effort_tracking_preference',
    'deload_preference',
    'session_structure_preferences',
])]
class AthleteProfile extends Model implements UserOwnedResource
{
    /** @use HasFactory<AthleteProfileFactory> */
    use HasFactory, HasUlids, HasUserOwnership;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'training_age_months' => 'integer',
            'current_bodyweight_value' => 'float',
            'secondary_goals' => 'array',
            'target_skills' => 'array',
            'available_equipment' => 'array',
            'training_locations' => 'array',
            'movement_limitations' => 'array',
            'preferred_training_days' => 'array',
            'preferred_session_minutes' => 'integer',
            'weekly_session_goal' => 'integer',
            'session_structure_preferences' => 'array',
        ];
    }
}
