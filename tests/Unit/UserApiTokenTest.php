<?php

namespace Tests\Unit;

use App\Models\User;
use Laravel\Sanctum\HasApiTokens;
use PHPUnit\Framework\TestCase;

class UserApiTokenTest extends TestCase
{
    public function test_user_model_uses_has_api_tokens_trait(): void
    {
        $this->assertContains(HasApiTokens::class, class_uses_recursive(User::class));
    }

    public function test_user_model_exposes_token_methods(): void
    {
        $this->assertTrue(method_exists(User::class, 'createToken'));
        $this->assertTrue(method_exists(User::class, 'tokens'));
    }
}
