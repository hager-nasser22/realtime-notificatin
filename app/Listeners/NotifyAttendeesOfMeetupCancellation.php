<?php

namespace App\Listeners;

use App\Events\MeetupCancelled;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class NotifyAttendeesOfMeetupCancellation implements ShouldQueue
{
    public function handle(MeetupCancelled $event): void
    {
        User::whereIn('id', $event->attendeeIds)->each(function (User $user) use ($event) {
            Mail::raw(
                "Unfortunately, \"{$event->title}\" scheduled for {$event->startsAt->format('D, M j \a\t H:i')} has been cancelled.",
                fn ($message) => $message->to($user->email)->subject('Meetup cancelled')
            );
        });
    }
}
