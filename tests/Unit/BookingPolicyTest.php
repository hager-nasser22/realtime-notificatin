<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
use App\Policies\BookingPolicy;
use PHPUnit\Framework\TestCase;

class BookingPolicyTest extends TestCase
{
    private function user(int $id): User
    {
        $user = new User;
        $user->id = $id;

        return $user;
    }

    private function booking(): Booking
    {
        $booking = new Booking(['user_id' => 1]);
        $booking->setRelation('meetup', new Meetup(['organizer_id' => 2]));

        return $booking;
    }

    public function test_only_the_attendee_can_cancel(): void
    {
        $this->assertTrue((new BookingPolicy)->cancel($this->user(1), $this->booking()));
        $this->assertFalse((new BookingPolicy)->cancel($this->user(2), $this->booking()));
    }

    public function test_only_the_organizer_can_approve_and_reject(): void
    {
        $policy = new BookingPolicy;

        $this->assertTrue($policy->approve($this->user(2), $this->booking()));
        $this->assertTrue($policy->reject($this->user(2), $this->booking()));
        $this->assertFalse($policy->approve($this->user(1), $this->booking()));
        $this->assertFalse($policy->reject($this->user(1), $this->booking()));
    }
}
