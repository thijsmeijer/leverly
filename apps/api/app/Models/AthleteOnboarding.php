<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Authorization\HasUserOwnership;
use App\Support\Authorization\UserOwnedResource;
use Database\Factories\AthleteOnboardingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperAthleteOnboarding
 */
#[Fillable([
    'user_id',
    'primary_goal',
    'secondary_goals',
    'target_skills',
    'available_equipment',
    'training_locations',
    'preferred_training_days',
    'preferred_session_minutes',
    'weekly_session_goal',
    'preferred_training_time',
    'current_level_tests',
    'skill_statuses',
    'readiness_rating',
    'sleep_quality',
    'soreness_level',
    'pain_level',
    'pain_areas',
    'pain_notes',
    'starter_plan_key',
    'completed_at',
])]
class AthleteOnboarding extends Model implements UserOwnedResource
{
    /** @use HasFactory<AthleteOnboardingFactory> */
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
            'secondary_goals' => 'array',
            'target_skills' => 'array',
            'available_equipment' => 'array',
            'training_locations' => 'array',
            'preferred_training_days' => 'array',
            'preferred_session_minutes' => 'integer',
            'weekly_session_goal' => 'integer',
            'current_level_tests' => 'array',
            'skill_statuses' => 'array',
            'readiness_rating' => 'integer',
            'sleep_quality' => 'integer',
            'soreness_level' => 'integer',
            'pain_level' => 'integer',
            'pain_areas' => 'array',
            'completed_at' => 'datetime',
        ];
    }
}
