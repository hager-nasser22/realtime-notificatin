<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MeetupCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $title,
        public CarbonInterface $startsAt,
    ) {
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
            ->subject("Cancelled: {$this->title}")
            ->greeting("Hi {$notifiable->name},")
            ->line("Unfortunately, **{$this->title}** scheduled for {$this->startsAt->toDayDateTimeString()} has been cancelled.")
            ->line('Your booking has been removed.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'meetup_title' => $this->title,
            'starts_at'    => $this->startsAt->toIso8601String(),
            'message'      => "\"{$this->title}\" has been cancelled.",
        ];
    }

    public function databaseType(object $notifiable): string
    {
        return 'meetup.cancelled';
    }
}
