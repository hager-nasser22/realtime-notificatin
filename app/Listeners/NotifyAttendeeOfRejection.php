<?php

namespace App\Listeners;

use App\Events\BookingRejected;
use App\Notifications\BookingRejectedNotification;
use Illuminate\Support\Facades\Mail;

class NotifyAttendeeOfRejection
{
    public function handle(BookingRejected $event): void
    {
        $booking = $event->booking;

        $event->booking->user->notify(new BookingRejectedNotification($booking));
    }
}
