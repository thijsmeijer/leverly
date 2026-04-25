<?php

declare(strict_types=1);

namespace App\Support\Authorization;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Stringable;

/**
 * @method static Builder<static> ownedBy(User|int|string $user)
 */
trait HasUserOwnership
{
    public function ownerKeyName(): string
    {
        return 'user_id';
    }

    public function ownerKey(): int|string|null
    {
        $value = $this->getAttribute($this->ownerKeyName());

        if (is_int($value) || is_string($value) || $value === null) {
            return $value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        return null;
    }

    public function isOwnedBy(User $user): bool
    {
        $ownerKey = $this->ownerKey();
        $userKey = $user->getKey();

        return $ownerKey !== null
            && $userKey !== null
            && (string) $ownerKey === (string) $userKey;
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOwnedBy(Builder $query, User|int|string $user): Builder
    {
        $ownerKey = $user instanceof User ? $user->getKey() : $user;

        return $query->where($query->getModel()->qualifyColumn($this->ownerKeyName()), $ownerKey);
    }
}
