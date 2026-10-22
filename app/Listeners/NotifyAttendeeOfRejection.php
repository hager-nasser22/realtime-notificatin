<?php

namespace App\Listeners;

use App\Events\BookingRejected;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyAttendeeOfRejection
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
    public function handle(BookingRejected $event): void
    {
        $booking = $event->booking;
        Mail::raw(
            "Sorry, your request to join \"{$booking->meetup->title}\" was not accepted.",
            fn($message) => $message->to($booking->user->email)->subject('Booking request declined')
        );
    }
}
