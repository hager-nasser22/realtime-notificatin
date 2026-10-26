<?php

namespace Tests\Unit;

use App\Models\Meetup;
use App\Models\User;
use App\Policies\MeetupPolicy;
use PHPUnit\Framework\TestCase;

class MeetupPolicyTest extends TestCase
{
    private function user(int $id): User
    {
        $user = new User;
        $user->id = $id;

        return $user;
    }

    public function test_only_the_organizer_can_update_and_delete(): void
    {
        $policy = new MeetupPolicy;
        $meetup = new Meetup(['organizer_id' => 1]);

        $this->assertTrue($policy->update($this->user(1), $meetup));
        $this->assertTrue($policy->delete($this->user(1), $meetup));
        $this->assertFalse($policy->update($this->user(2), $meetup));
        $this->assertFalse($policy->delete($this->user(2), $meetup));
    }
}
