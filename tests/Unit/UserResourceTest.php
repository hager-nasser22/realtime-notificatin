<?php

namespace Tests\Unit;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class UserResourceTest extends TestCase
{
    public function test_it_exposes_public_fields_only(): void
    {
        $user = new User(['name' => 'Hager', 'email' => 'hager@example.com']);
        $user->id = 5;

        $data = (new UserResource($user))->toArray(new Request);

        $this->assertSame(['id', 'name', 'email', 'created_at'], array_keys($data));
        $this->assertSame(5, $data['id']);
        $this->assertSame('hager@example.com', $data['email']);
    }
}
