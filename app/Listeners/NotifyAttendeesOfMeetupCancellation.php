<?php

namespace App\Listeners;

use App\Events\MeetupCancelled;
use App\Models\User;
use App\Notifications\MeetupCancelledNotification;
use Illuminate\Support\Facades\Notification;

class NotifyAttendeesOfMeetupCancellation
{
    public function handle(MeetupCancelled $event): void
    {
        $attendees = User::whereIn('id', $event->attendeeIds)->get();
        Notification::send($attendees , new MeetupCancelledNotification($event->title , $event->startsAt));
    }
}
