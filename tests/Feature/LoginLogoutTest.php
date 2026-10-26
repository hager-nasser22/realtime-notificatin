<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create(['email' => 'hager@example.com']);

        $response = $this->postJson('/api/login', ['email' => 'hager@example.com', 'password' => 'password']);

        $response->assertOk()
            ->assertJsonPath('message', 'User Login Success')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonStructure(['token']);
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'name' => 'api']);
    }

    public function test_wrong_password_returns_validation_error(): void
    {
        User::factory()->create(['email' => 'hager@example.com']);

        $this->postJson('/api/login', ['email' => 'hager@example.com', 'password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email' => 'credentials are incorrect.']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_is_throttled_after_five_attempts(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->postJson('/api/login', ['email' => 'x@example.com', 'password' => 'wrong']);
        }

        $this->postJson('/api/login', ['email' => 'x@example.com', 'password' => 'wrong'])
            ->assertTooManyRequests();
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = User::factory()->create();

        $this->withToken($user->createToken('api')->plainTextToken)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'User Logout Success');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_guest_cannot_logout(): void
    {
        $this->postJson('/api/logout')->assertUnauthorized();
    }
}
