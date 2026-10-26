<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'meetup_id' => $this->booking->meetup_id,
            'meetup_title' => $this->booking->meetup->title,
            'user_name' => $this->booking->user->name,
            'message' => "{$this->booking->user->name} cancelled their booking for \"{$this->booking->meetup->title}\".",
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'booking.cancelled';
    }
}
