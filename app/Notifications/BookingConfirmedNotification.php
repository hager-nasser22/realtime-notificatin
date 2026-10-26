<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Booking $booking) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $meetup = $this->booking->meetup;

        return (new MailMessage)
            ->subject("You're in: {$meetup->title}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Your seat at **{$meetup->title}** is confirmed.")
            ->line("Location: {$meetup->location}")
            ->line("Start Time: {$meetup->starts_at->toDayDateTimeString()}");
    }

    public function toArray(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'meetup_id' => $this->booking->meetup_id,
            'meetup_title' => $this->booking->meetup->title,
            'message' => "Your seat at \"{$this->booking->meetup->title}\" is confirmed.",
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'booking.confirmed';
    }
}
