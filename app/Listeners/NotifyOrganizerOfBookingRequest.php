<?php

namespace App\Listeners;

use App\Events\BookingRequested;
use Illuminate\Support\Facades\Mail;

class NotifyOrganizerOfBookingRequest
{
    public function handle(BookingRequested $event): void
    {
        $booking = $event->booking;
        $meetup = $booking->meetup;

        Mail::raw(
            "{$booking->user->name} wants to join \"{$meetup->title}\". Review the request in your dashboard.",
            fn ($message) => $message->to($meetup->organizer->email)->subject('New booking request')
        );
    }
}
