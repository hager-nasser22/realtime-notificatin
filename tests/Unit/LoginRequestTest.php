<?php

namespace Tests\Unit;

use App\Http\Requests\Auth\LoginRequest;
use PHPUnit\Framework\TestCase;

class LoginRequestTest extends TestCase
{
    public function test_it_is_authorized_for_guests(): void
    {
        $this->assertTrue((new LoginRequest)->authorize());
    }

    public function test_email_and_password_are_required(): void
    {
        $rules = (new LoginRequest)->rules();

        $this->assertSame(['required', 'email'], $rules['email']);
        $this->assertSame(['required', 'string'], $rules['password']);
    }
}
