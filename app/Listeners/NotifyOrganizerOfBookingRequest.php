<?php

namespace App\Listeners;

use App\Events\BookingRequested;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyOrganizerOfBookingRequest
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BookingRequested $event): void
    {
        $booking   = $event->booking;
        $meetup    = $booking->meetup;
        $organizer = $meetup->organizer;

        Mail::raw(
            "{$booking->user->name} wants to join \"{$meetup->title}\". Review the request in your dashboard.",
            fn($message) => $message->to($organizer->email)->subject('New booking request')
        );
    }
}
