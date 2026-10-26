<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Notifications\BookingCancelledNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class NotifyOrganizerOfCancellation
{
    public function handle(BookingCancelled $event): void
    {
        $booking = $event->booking;
        $meetup = $booking->meetup;
        $meetup->organizer->notify(new BookingCancelledNotification($booking));
    }
}
