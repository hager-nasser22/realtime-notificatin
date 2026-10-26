<?php

namespace App\Listeners;

use App\Events\BookingRequested;
use App\Notifications\NewBookingRequestNotification;
use Illuminate\Support\Facades\Mail;

class NotifyOrganizerOfBookingRequest
{
    public function handle(BookingRequested $event): void
    {
        $booking = $event->booking;
        $meetup = $booking->meetup;
        $meetup->organizer->notify(new NewBookingRequestNotification($booking));
    }
}
