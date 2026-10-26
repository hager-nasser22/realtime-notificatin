<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Support\Facades\Mail;

class NotifyAttendeeOfConfirmation
{
    public function handle(BookingConfirmed $event): void
    {
        $booking = $event->booking;
        $event->booking->user->notify(new BookingConfirmedNotification($booking));
    }
}
