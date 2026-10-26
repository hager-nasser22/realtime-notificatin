<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Events\BookingConfirmed;
use App\Events\MeetupCancelled;
use App\Models\Booking;
use App\Models\Meetup;
use App\Models\User;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\MeetupCancelledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeetupCancellationNotificationTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, Meetup|Booking> */
    private function meetupWithBookings(): array
    {
        $meetup = Meetup::factory()->create(['title' => 'Laravel Cairo']);
        $confirmed = Booking::factory()->for($meetup)->create(['status' => BookingStatus::Confirmed]);
        $pending = Booking::factory()->for($meetup)->create(['status' => BookingStatus::Pending]);
        $cancelled = Booking::factory()->for($meetup)->create(['status' => BookingStatus::Cancelled]);

        return [$meetup, $confirmed, $pending, $cancelled];
    }

    public function test_deleting_a_meetup_dispatches_event_with_active_attendees(): void
    {
        Event::fake([MeetupCancelled::class]);
        [$meetup, $confirmed, $pending] = $this->meetupWithBookings();
        Sanctum::actingAs($meetup->organizer);

        $this->deleteJson("/api/meetups/{$meetup->id}")->assertOk();

        $this->assertDatabaseMissing('meetups', ['id' => $meetup->id]);
        $this->assertDatabaseCount('bookings', 0);
        Event::assertDispatched(MeetupCancelled::class, fn (MeetupCancelled $e) => $e->title === 'Laravel Cairo'
            && collect($e->attendeeIds)->sort()->values()->all() === collect([$confirmed->user_id, $pending->user_id])->sort()->values()->all());
    }

    public function test_active_attendees_are_notified_and_cancelled_ones_are_not(): void
    {
        Notification::fake();
        [$meetup, $confirmed, $pending, $cancelled] = $this->meetupWithBookings();
        Sanctum::actingAs($meetup->organizer);

        $this->deleteJson("/api/meetups/{$meetup->id}")->assertOk();

        foreach ([$confirmed, $pending] as $booking) {
            Notification::assertSentTo(
                User::find($booking->user_id),
                MeetupCancelledNotification::class,
                fn ($n, array $channels) => $n->title === 'Laravel Cairo' && $channels === ['mail', 'database']
            );
        }
        Notification::assertNotSentTo(User::find($cancelled->user_id), MeetupCancelledNotification::class);
    }

    public function test_attendee_gets_a_stored_cancellation_notification(): void
    {
        [$meetup, $confirmed] = $this->meetupWithBookings();
        Sanctum::actingAs($meetup->organizer);

        $this->deleteJson("/api/meetups/{$meetup->id}")->assertOk();

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $confirmed->user_id,
            'type' => 'meetup.cancelled',
        ]);
        $subjects = collect(app('mailer')->getSymfonyTransport()->messages())
            ->map(fn ($sent) => $sent->getOriginalMessage()->getSubject());
        $this->assertContains('Cancelled: Laravel Cairo', $subjects);
    }

    public function test_other_user_cannot_cancel_and_nothing_is_dispatched(): void
    {
        Event::fake([MeetupCancelled::class]);
        [$meetup] = $this->meetupWithBookings();
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/meetups/{$meetup->id}")->assertForbidden();

        $this->assertDatabaseHas('meetups', ['id' => $meetup->id]);
        Event::assertNotDispatched(MeetupCancelled::class);
    }

    public function test_notifications_are_pushed_to_the_queue(): void
    {
        Queue::fake();
        [$meetup] = $this->meetupWithBookings();
        Sanctum::actingAs($meetup->organizer);

        $this->deleteJson("/api/meetups/{$meetup->id}")->assertOk();
        BookingConfirmed::dispatch(Booking::factory()->create());

        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->notification instanceof MeetupCancelledNotification);
        Queue::assertPushed(SendQueuedNotifications::class, fn ($job) => $job->notification instanceof BookingConfirmedNotification);
    }
}
