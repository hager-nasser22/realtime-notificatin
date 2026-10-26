<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Notifications\BookingConfirmedNotification;

class NotifyAttendeeOfConfirmation
{
    public function handle(BookingConfirmed $event): void
    {
        $event->booking->user->notify(new BookingConfirmedNotification($event->booking));
    }
}
