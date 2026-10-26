<?php

namespace App\Listeners;

use App\Events\BookingRejected;
use App\Notifications\BookingRejectedNotification;

class NotifyAttendeeOfRejection
{
    public function handle(BookingRejected $event): void
    {
        $event->booking->user->notify(new BookingRejectedNotification($event->booking));
    }
}
