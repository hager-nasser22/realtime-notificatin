<?php

namespace Tests\Unit;

use App\Models\Meetup;
use App\Models\User;
use PHPUnit\Framework\TestCase;

class MeetupTest extends TestCase
{
    public function test_organized_by_returns_true_for_the_organizer(): void
    {
        $user = new User;
        $user->id = 7;

        $meetup = new Meetup(['organizer_id' => 7]);

        $this->assertTrue($meetup->organizedBy($user));
    }

    public function test_organized_by_returns_false_for_another_user(): void
    {
        $user = new User;
        $user->id = 8;

        $meetup = new Meetup(['organizer_id' => 7]);

        $this->assertFalse($meetup->organizedBy($user));
    }
}
