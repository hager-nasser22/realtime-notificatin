<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use Illuminate\Support\Facades\Mail;

class NotifyOrganizerOfCancellation
{
    public function handle(BookingCancelled $event): void
    {
        $booking = $event->booking;
        $meetup = $booking->meetup;

        Mail::raw(
            "{$booking->user->name} cancelled their booking for \"{$meetup->title}\".",
            fn ($message) => $message->to($meetup->organizer->email)->subject('Booking cancelled')
        );
    }
}
