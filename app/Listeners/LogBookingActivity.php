<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Events\BookingRejected;
use App\Events\BookingRequested;
use Illuminate\Support\Facades\Log;

class LogBookingActivity
{
    public function handle(BookingRequested|BookingConfirmed|BookingRejected|BookingCancelled $event): void
    {
        Log::info(class_basename($event), [
            'booking_id' => $event->booking->id,
            'user_id' => $event->booking->user_id,
            'meetup_id' => $event->booking->meetup_id,
            'status' => $event->booking->status->value,
        ]);
    }
}
