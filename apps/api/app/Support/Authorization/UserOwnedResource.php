<?php

declare(strict_types=1);

namespace App\Support\Authorization;

use App\Models\User;

interface UserOwnedResource
{
    public function ownerKey(): int|string|null;

    public function isOwnedBy(User $user): bool;
}
