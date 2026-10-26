<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanctumApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_authenticated_user_endpoint(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_user_with_valid_token_gets_their_profile(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        $this->assertDatabaseHas('personal_access_tokens', [
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'test-device',
        ]);

        $response = $this->withToken($token)->getJson('/api/user');

        $response->assertOk()
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->assertJsonMissingPath('password');

        $this->assertNotNull($user->tokens()->first()->last_used_at);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->withToken('1|invalid-token')
            ->getJson('/api/user')
            ->assertUnauthorized();
    }
}
