<?php

namespace Tests\Feature\API\V1\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_fetch_current_user(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Ada Athlete',
            'email' => 'Ada@Example.com',
            'password' => 'strong-password',
            'password_confirmation' => 'strong-password',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Ada Athlete')
            ->assertJsonPath('data.email', 'ada@example.com');

        $userId = $response->json('data.id');

        $this->assertIsString($userId);
        $this->assertTrue(Str::isUlid($userId));
        $this->assertDatabaseHas('users', [
            'id' => $userId,
            'email' => 'ada@example.com',
        ]);

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $userId)
            ->assertJsonPath('data.email', 'ada@example.com');
    }

    public function test_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create([
            'email' => 'athlete@example.com',
            'password' => 'strong-password',
        ]);

        $this->postJson('/login', [
            'email' => 'athlete@example.com',
            'password' => 'strong-password',
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', 'athlete@example.com');

        $this->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id);

        $this->postJson('/logout')->assertNoContent();
        $this->assertGuest('web');

        Auth::forgetGuards();

        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_invalid_auth_flows_return_validation_errors(): void
    {
        User::factory()->create([
            'email' => 'athlete@example.com',
            'password' => 'strong-password',
        ]);

        $this->postJson('/login', [
            'email' => 'athlete@example.com',
            'password' => 'wrong-password',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $this->postJson('/register', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'different',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_auth_endpoints_are_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'limited@example.com',
            'password' => 'strong-password',
        ]);

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->postJson('/login', [
                'email' => 'limited@example.com',
                'password' => 'wrong-password',
            ])->assertUnprocessable();
        }

        $this->postJson('/login', [
            'email' => 'limited@example.com',
            'password' => 'wrong-password',
        ])->assertTooManyRequests();
    }

    public function test_spa_auth_configuration_supports_csrf_and_credentials(): void
    {
        $this->get('/sanctum/csrf-cookie')->assertNoContent();

        $this->assertContains('sanctum/csrf-cookie', Config::get('cors.paths'));
        $this->assertContains('login', Config::get('cors.paths'));
        $this->assertContains('register', Config::get('cors.paths'));
        $this->assertTrue(Config::get('cors.supports_credentials'));
        $this->assertContains('web.leverly.local', Config::get('sanctum.stateful'));
    }
}
