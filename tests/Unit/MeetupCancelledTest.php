<?php

namespace Tests\Unit;

use App\Events\MeetupCancelled;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\BookingRejectedNotification;
use App\Notifications\MeetupCancelledNotification;
use App\Notifications\NewBookingRequestNotification;
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

    public static function mailNotifications(): array
    {
        return [
            [NewBookingRequestNotification::class],
            [BookingConfirmedNotification::class],
            [BookingRejectedNotification::class],
            [MeetupCancelledNotification::class],
        ];
    }

    #[DataProvider('mailNotifications')]
    public function test_notifications_that_send_email_are_queued(string $notification): void
    {
        $this->assertContains(ShouldQueue::class, class_implements($notification));
    }
}
