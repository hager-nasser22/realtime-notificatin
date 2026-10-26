<?php

namespace Tests\Unit;

use App\Events\MeetupCancelled;
use App\Listeners\NotifyAttendeeOfConfirmation;
use App\Listeners\NotifyAttendeeOfRejection;
use App\Listeners\NotifyAttendeesOfMeetupCancellation;
use App\Listeners\NotifyOrganizerOfBookingRequest;
use App\Listeners\NotifyOrganizerOfCancellation;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Contracts\Queue\ShouldQueue;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class MeetupCancelledTest extends TestCase
{
    public function test_event_keeps_the_data_needed_after_the_meetup_is_deleted(): void
    {
        $startsAt = CarbonImmutable::parse('2026-12-01 18:00:00');

        $event = new MeetupCancelled('Laravel Cairo', $startsAt, [3, 7]);

        $this->assertSame('Laravel Cairo', $event->title);
        $this->assertSame($startsAt, $event->startsAt);
        $this->assertSame([3, 7], $event->attendeeIds);
        $this->assertInstanceOf(ShouldDispatchAfterCommit::class, $event);
    }

    public static function emailListeners(): array
    {
        return [
            [NotifyAttendeeOfConfirmation::class],
            [NotifyAttendeeOfRejection::class],
            [NotifyOrganizerOfBookingRequest::class],
            [NotifyOrganizerOfCancellation::class],
            [NotifyAttendeesOfMeetupCancellation::class],
        ];
    }

    #[DataProvider('emailListeners')]
    public function test_email_listeners_are_queued(string $listener): void
    {
        $this->assertContains(ShouldQueue::class, class_implements($listener));
    }
}
