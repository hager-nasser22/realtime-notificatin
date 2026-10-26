<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Hager',
            'email' => 'hager@example.com',
            'password' => 'Secret123!',
            'password_confirmation' => 'Secret123!',
        ], $overrides);
    }

    public function test_user_can_register_and_gets_a_token(): void
    {
        $response = $this->postJson('/api/register', $this->payload());

        $response->assertCreated()
            ->assertJsonPath('message', 'User Created')
            ->assertJsonPath('user.email', 'hager@example.com')
            ->assertJsonMissingPath('user.password')
            ->assertJsonStructure(['token']);

        $user = User::where('email', 'hager@example.com')->first();
        $this->assertNotNull($user);
        $this->assertTrue(Hash::check('Secret123!', $user->password));
        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id, 'name' => 'api']);

        $this->withToken($response->json('token'))->getJson('/api/user')->assertOk();
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'hager@example.com']);

        $this->postJson('/api/register', $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_password_must_be_confirmed(): void
    {
        $this->postJson('/api/register', $this->payload(['password_confirmation' => 'nope']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertDatabaseMissing('users', ['email' => 'hager@example.com']);
    }
}
