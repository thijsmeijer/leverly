<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Support\Authorization\UserOwnedResource;
use Illuminate\Auth\Access\Response;

class UserOwnedResourcePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function view(User $user, UserOwnedResource $resource): Response
    {
        return $this->ownerOnly($user, $resource);
    }

    public function update(User $user, UserOwnedResource $resource): Response
    {
        return $this->ownerOnly($user, $resource);
    }

    public function delete(User $user, UserOwnedResource $resource): Response
    {
        return $this->ownerOnly($user, $resource);
    }

    public function restore(User $user, UserOwnedResource $resource): Response
    {
        return $this->ownerOnly($user, $resource);
    }

    public function forceDelete(User $user, UserOwnedResource $resource): Response
    {
        return $this->ownerOnly($user, $resource);
    }

    protected function ownerOnly(User $user, UserOwnedResource $resource): Response
    {
        if ($resource->isOwnedBy($user)) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }
}
