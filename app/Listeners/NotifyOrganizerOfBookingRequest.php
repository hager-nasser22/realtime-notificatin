<?php

namespace App\Listeners;

use App\Events\BookingRequested;
use App\Notifications\NewBookingRequestNotification;

class NotifyOrganizerOfBookingRequest
{
    public function handle(BookingRequested $event): void
    {
        $event->booking->meetup->organizer->notify(new NewBookingRequestNotification($event->booking));
    }
}
