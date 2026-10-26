<?php

namespace Tests\Unit;

use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Events\BookingRejected;
use App\Events\BookingRequested;
use App\Models\Booking;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class BookingEventsTest extends TestCase
{
    public static function events(): array
    {
        return [
            [BookingRequested::class],
            [BookingConfirmed::class],
            [BookingRejected::class],
            [BookingCancelled::class],
        ];
    }

    #[DataProvider('events')]
    public function test_event_carries_the_booking_and_waits_for_commit(string $event): void
    {
        $booking = new Booking;

        $instance = new $event($booking);

        $this->assertSame($booking, $instance->booking);
        $this->assertInstanceOf(ShouldDispatchAfterCommit::class, $instance);
    }
}
