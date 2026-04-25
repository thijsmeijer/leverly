<?php

namespace Tests\Unit\Support\Authorization;

use App\Models\User;
use App\Policies\UserOwnedResourcePolicy;
use App\Support\Authorization\UserOwnedResource;
use PHPUnit\Framework\TestCase;

class UserOwnedResourcePolicyTest extends TestCase
{
    public function test_it_allows_owner_resource_actions(): void
    {
        $policy = new UserOwnedResourcePolicy;
        $user = $this->user(10);
        $resource = new OwnedAuthorizationResource(10);

        foreach (['view', 'update', 'delete', 'restore', 'forceDelete'] as $ability) {
            $response = $policy->{$ability}($user, $resource);

            $this->assertTrue($response->allowed(), "Expected {$ability} to be allowed for the owner.");
        }
    }

    public function test_it_denies_cross_user_resource_actions_as_not_found(): void
    {
        $policy = new UserOwnedResourcePolicy;
        $user = $this->user(10);
        $resource = new OwnedAuthorizationResource(99);

        foreach (['view', 'update', 'delete', 'restore', 'forceDelete'] as $ability) {
            $response = $policy->{$ability}($user, $resource);

            $this->assertTrue($response->denied(), "Expected {$ability} to be denied for a non-owner.");
            $this->assertSame(404, $response->status());
        }
    }

    public function test_it_allows_authenticated_users_to_list_and_create_their_own_resources(): void
    {
        $policy = new UserOwnedResourcePolicy;
        $user = $this->user(10);

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->create($user));
    }

    private function user(int $id): User
    {
        $user = new User;
        $user->setAttribute('id', $id);

        return $user;
    }
}

final class OwnedAuthorizationResource implements UserOwnedResource
{
    public function __construct(private readonly int|string|null $ownerKey) {}

    public function ownerKey(): int|string|null
    {
        return $this->ownerKey;
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->ownerKey !== null && (string) $this->ownerKey === (string) $user->getKey();
    }
}
