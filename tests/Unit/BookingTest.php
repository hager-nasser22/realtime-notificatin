<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Exceptions\InvalidTransitionsBookingException;
use App\Models\Booking;
use PHPUnit\Framework\TestCase;

class BookingTest extends TestCase
{
    public function test_status_is_cast_to_enum(): void
    {
        $booking = new Booking(['status' => 'pending']);

        $this->assertSame(BookingStatus::Pending, $booking->status);
    }

    public function test_transition_to_throws_for_a_disallowed_status(): void
    {
        $booking = new Booking(['status' => BookingStatus::Rejected]);

        $this->expectException(InvalidTransitionsBookingException::class);
        $this->expectExceptionMessage('invalid transition from rejected to confirmed');

        $booking->transitionTo(BookingStatus::Confirmed);
    }
}
