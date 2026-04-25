<?php

namespace Tests\Feature\Integration\Authorization;

use App\Models\User;
use App\Support\Authorization\HasUserOwnership;
use App\Support\Authorization\UserOwnedResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserOwnershipScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_checks_model_ownership_and_scopes_queries_to_the_owner(): void
    {
        Schema::create('authorization_owned_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('user_id')->constrained();
            $table->string('name');
            $table->timestamps();
        });

        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $ownedRecord = AuthorizationOwnedRecord::query()->create([
            'name' => 'owner record',
            'user_id' => $owner->getKey(),
        ]);
        $otherRecord = AuthorizationOwnedRecord::query()->create([
            'name' => 'other record',
            'user_id' => $otherUser->getKey(),
        ]);

        $this->assertTrue($ownedRecord->isOwnedBy($owner));
        $this->assertFalse($ownedRecord->isOwnedBy($otherUser));
        $this->assertSame($owner->getKey(), $ownedRecord->ownerKey());

        $ownedIds = AuthorizationOwnedRecord::query()
            ->ownedBy($owner)
            ->pluck('id')
            ->all();

        $this->assertSame([$ownedRecord->getKey()], $ownedIds);
        $this->assertNotContains($otherRecord->getKey(), $ownedIds);
    }
}

final class AuthorizationOwnedRecord extends Model implements UserOwnedResource
{
    use HasUserOwnership;

    protected $guarded = [];
}
