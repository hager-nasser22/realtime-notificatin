<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewBookingRequestNotification extends Notification implements ShouldQueue
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
        $meetup = $this->booking->meetup;
        return (new MailMessage)
            ->subject("New request for {$meetup->title}")
            ->greeting("Hi {$notifiable->name},")
            ->line("**{$this->booking->user->name}** wants to join **{$meetup->title}**.")
            ->line('Approve or decline the request from your dashboard.');
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
            'user_name'    => $this->booking->user->name,
            'message'      => "{$this->booking->user->name} wants to join \"{$this->booking->meetup->title}\".",
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'booking.requested';
    }
}
