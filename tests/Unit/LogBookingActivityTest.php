<?php

namespace Tests\Unit;

use App\Enums\BookingStatus;
use App\Events\BookingRejected;
use App\Listeners\LogBookingActivity;
use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class LogBookingActivityTest extends TestCase
{
    public function test_it_logs_the_event_name_and_booking_details(): void
    {
        Log::spy();
        $booking = new Booking(['user_id' => 4, 'meetup_id' => 6, 'status' => BookingStatus::Rejected]);
        $booking->id = 12;

        (new LogBookingActivity)->handle(new BookingRejected($booking));

        Log::shouldHaveReceived('info')->once()->with('BookingRejected', [
            'booking_id' => 12,
            'user_id' => 4,
            'meetup_id' => 6,
            'status' => 'rejected',
        ]);
    }
}
