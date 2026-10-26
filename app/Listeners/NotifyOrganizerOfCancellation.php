<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Notifications\BookingCancelledNotification;

class NotifyOrganizerOfCancellation
{
    public function handle(BookingCancelled $event): void
    {
        $event->booking->meetup->organizer->notify(new BookingCancelledNotification($event->booking));
    }
}
