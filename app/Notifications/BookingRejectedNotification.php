<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingRejectedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Booking $booking)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Update on your request for {$this->booking->meetup->title}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Sorry, your request to join **{$this->booking->meetup->title}** was not accepted.");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'booking_id'   => $this->booking->id,
            'meetup_id'    => $this->booking->meetup_id,
            'meetup_title' => $this->booking->meetup->title,
            'message'      => "Your request to join \"{$this->booking->meetup->title}\" was not accepted.",
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'booking.rejected';
    }
}
