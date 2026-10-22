<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class NotifyAttendeeOfConfirmation
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
    public function handle(BookingConfirmed $event): void
    {
        $booking = $event->booking;
        $meetup = $booking->meetup;
        Mail::raw(
        "Your seat at \"{$meetup->title}\" is confirmed. See you on {$meetup->starts_at->format('D, M j \a\t H:i')}!",
        fn ($message) => $message->to($booking->user->email)->subject('Booking confirmed')
    );
    }
}
