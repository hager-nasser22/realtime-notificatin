<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Events\BookingRejected;
use App\Events\BookingRequested;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LogBookingActivity
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
    public function handle(BookingRequested|BookingConfirmed|BookingRejected|BookingCancelled $event): void
    {
        Log::info(class_basename($event), [
            'booking_id' => $event->booking->id,
            'user_id'    => $event->booking->user_id,
            'meetup_id'  => $event->booking->meetup_id,
            'status'     => $event->booking->status->value,
        ]);
    }
}
