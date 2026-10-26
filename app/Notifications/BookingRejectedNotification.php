<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Update on your request for {$this->booking->meetup->title}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Sorry, your request to join **{$this->booking->meetup->title}** was not accepted.");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'meetup_id' => $this->booking->meetup_id,
            'meetup_title' => $this->booking->meetup->title,
            'message' => "Your request to join \"{$this->booking->meetup->title}\" was not accepted.",
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'booking.rejected';
    }
}
